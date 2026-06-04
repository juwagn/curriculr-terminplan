<?php
/**
 * Integration test for the WP-bound Data Layer with stubbed WordPress.
 *
 * Exercises the glue that `php -l` alone cannot reach: repository put/get,
 * optimistic-concurrency conflict, the REST PUT paths (400/409/200), the
 * feed URL shape, the after_put → gsh_tp_do_refresh wiring, and the feed
 * token check — all without a real WordPress/DB. Dependency-free, runs with
 * plain `php`. Real-WP specifics (dbDelta, route registration, Application
 * Passwords, loopback refresh, CORS preflight) still require a live install.
 */
define( 'GSH_TP_CURRICULR_TEST', true );
define( 'GSH_TP_VERSION', '4.5.0-test' );
define( 'ARRAY_A', 'ARRAY_A' );

require __DIR__ . '/assert.php';

/* ---------- minimale WordPress-Stubs ---------- */
class Gsh_Fake_Wpdb {
    public $prefix = 'wp_';
    public $rows   = array();
    public function get_charset_collate() { return ''; }
    public function prepare( $q, $a = null ) { return $a; }            // bindet die schoolyear
    public function get_row( $key, $out = null ) { return $this->rows[ $key ] ?? null; }
    public function insert( $t, $data ) { $this->rows[ $data['schoolyear'] ] = $data; }
    public function update( $t, $data, $where ) { $this->rows[ $where['schoolyear'] ] = array_merge( $this->rows[ $where['schoolyear'] ] ?? array(), $data ); }
}
$GLOBALS['wpdb']      = new Gsh_Fake_Wpdb();
$GLOBALS['options']   = array();
$GLOBALS['refreshed'] = array();
function get_option( $k, $d = false ) { return $GLOBALS['options'][ $k ] ?? $d; }
function update_option( $k, $v, $a = null ) { $GLOBALS['options'][ $k ] = $v; return true; }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $k ) ); }
function wp_generate_password( $l = 12, $s = true, $e = true ) { return substr( str_repeat( 'aB3xY9Qz', 8 ), 0, $l ); }
function current_time( $t ) { return '2026-01-01 12:00:00'; }
function get_current_user_id() { return 7; }
function wp_json_encode( $d ) { return json_encode( $d ); }
function current_user_can( $c ) { return true; }
function rest_url( $p ) { return 'https://wp.test/wp-json/' . $p; }
function gsh_tp_get_profiles() { return $GLOBALS['profiles'] ?? array(); }
function gsh_tp_active_profile_id() { return 'p1'; }
function gsh_tp_do_refresh( $pid ) { $GLOBALS['refreshed'][] = $pid; }
function add_action() {}
function add_filter() {}
function register_activation_hook() {}
class WP_REST_Response { public $data; public $status; public function __construct( $d, $s = 200 ) { $this->data = $d; $this->status = $s; } }

require __DIR__ . '/../../plugin/curriculr-data-layer.php';

$doc = json_decode( file_get_contents( __DIR__ . '/fixtures/sample-doc.json' ), true );

/* ---------- Repository: anlegen / Konflikt / akzeptiertes Update ---------- */
$r1 = gsh_tp_curriculr_repo_put( 'sj_2026_27', $doc, 0 );
gsh_assert_eq( $r1['status'], 'ok', 'first PUT ok' );
gsh_assert_eq( $r1['version'], 1, 'first PUT version 1' );
gsh_assert_true( ! empty( $r1['feed_token'] ), 'first PUT issues feed_token' );

$row = gsh_tp_curriculr_repo_get( 'sj_2026_27' );
gsh_assert_true( $row !== null && (int) $row['version'] === 1, 'repo_get returns stored row v1' );

$rc = gsh_tp_curriculr_repo_put( 'sj_2026_27', $doc, 0 );   // veraltete baseVersion
gsh_assert_eq( $rc['status'], 'conflict', 'stale PUT -> conflict' );
gsh_assert_eq( (int) $rc['current']['version'], 1, 'conflict returns current version 1' );

$r2 = gsh_tp_curriculr_repo_put( 'sj_2026_27', $doc, 1 );   // korrekte baseVersion
gsh_assert_true( $r2['status'] === 'ok' && $r2['version'] === 2, 'accepted PUT -> version 2' );
gsh_assert_eq( $r2['feed_token'], $r1['feed_token'], 'feed_token stable across updates' );

/* ---------- Feed aus gespeichertem JSON ---------- */
$stored = gsh_tp_curriculr_repo_get( 'sj_2026_27' );
$ics    = gsh_tp_curriculr_build_ics( json_decode( $stored['json'], true ) );
gsh_assert_contains( $ics, 'UID:ev1@curriculr-planner', 'feed from stored doc contains event' );

/* ---------- rest_put: Validierung + Konflikt + Erfolg + after_put-Refresh ---------- */
class Gsh_Fake_Req implements ArrayAccess {
    public $body; public $params;
    public function __construct( $body, $params ) { $this->body = $body; $this->params = $params; }
    public function get_json_params() { return $this->body; }
    public function get_method() { return 'PUT'; }
    public function get_route() { return '/curriculr/v1/doc/sj_2026_27'; }
    public function offsetExists( $o ): bool { return isset( $this->params[ $o ] ); }
    public function offsetGet( $o ): mixed { return $this->params[ $o ] ?? null; }
    public function offsetSet( $o, $v ): void { $this->params[ $o ] = $v; }
    public function offsetUnset( $o ): void { unset( $this->params[ $o ] ); }
}

$bad = gsh_tp_curriculr_rest_put( new Gsh_Fake_Req( array( 'doc' => array() ), array( 'sj' => 'sj_2026_27' ) ) );
gsh_assert_eq( $bad->status, 400, 'rest_put invalid envelope -> 400' );

$conf = gsh_tp_curriculr_rest_put( new Gsh_Fake_Req( array( 'doc' => $doc, 'baseVersion' => 0 ), array( 'sj' => 'sj_2026_27' ) ) );
gsh_assert_true( $conf->status === 409 && $conf->data['error'] === 'conflict', 'rest_put stale -> 409 conflict' );

$GLOBALS['refreshed'] = array();
$good = gsh_tp_curriculr_rest_put( new Gsh_Fake_Req( array( 'doc' => $doc, 'baseVersion' => 2 ), array( 'sj' => 'sj_2026_27' ) ) );
gsh_assert_true( $good->status === 200 && $good->data['version'] === 3, 'rest_put accepted -> 200 version 3' );
gsh_assert_true( strpos( $good->data['feedUrl'], '/feed/sj_2026_27/' ) !== false && substr( $good->data['feedUrl'], -4 ) === '.ics', 'rest_put returns .ics feedUrl' );
gsh_assert_eq( $GLOBALS['refreshed'], array( 'p1' ), 'after_put triggered gsh_tp_do_refresh for active profile' );

/* ---------- Feed-Token: rest_feed 404 bei falschem Token ---------- */
$nf = gsh_tp_curriculr_rest_feed( new Gsh_Fake_Req( array(), array( 'sj' => 'sj_2026_27', 'token' => 'WRONGTOKEN' ) ) );
gsh_assert_true( $nf instanceof WP_REST_Response && $nf->status === 404, 'rest_feed wrong token -> 404' );

gsh_test_done();
