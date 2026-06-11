<?php
/**
 * Tests für Revisions-Snapshot, Prune und REST-Handler.
 *
 * Dependency-free, läuft mit plain `php`. Benutzt dieselbe Stub-Strategie
 * wie test-integration-stubbed.php.
 */
define( 'GSH_TP_CURRICULR_TEST', true );
define( 'GSH_TP_VERSION', '4.9.0-test' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'FS_CHMOD_FILE', 0644 );

require __DIR__ . '/assert.php';

/* ---------- Erweiterte WordPress-Stubs ---------- */
class Gsh_Fake_Wpdb_Rev {
    public $prefix    = 'wp_';
    public $docs      = array();
    public $revs      = array();
    public $next_id   = 1;
    public $insert_id = 0;

    public function get_charset_collate() { return ''; }
    public function prepare( $q, ...$args ) {
        return isset( $args[0] ) ? $args[0] : $q;
    }
    public function get_row( $key, $out = null ) {
        if ( is_int( $key ) || ctype_digit( (string) $key ) ) {
            return $this->revs[ (int) $key ] ?? null;
        }
        return $this->docs[ $key ] ?? null;
    }
    public function get_results( $query, $out = null ) {
        return array_values( $this->revs );
    }
    public function insert( $table, $data ) {
        if ( strpos( $table, 'revisions' ) !== false ) {
            $data['id']      = $this->next_id;
            $this->insert_id = $this->next_id;
            $this->revs[ $this->next_id ] = $data;
            $this->next_id++;
        } else {
            $this->docs[ $data['schoolyear'] ] = $data;
        }
    }
    public function update( $table, $data, $where ) {
        $key = $where['schoolyear'] ?? null;
        if ( $key ) {
            $this->docs[ $key ] = array_merge( $this->docs[ $key ] ?? array(), $data );
        }
    }
    public function query( $sql ) {
        if ( count( $this->revs ) > 50 ) {
            $keys = array_keys( $this->revs );
            $keep = array_slice( $keys, -50 );
            foreach ( $keys as $k ) {
                if ( ! in_array( $k, $keep, true ) ) {
                    unset( $this->revs[ $k ] );
                }
            }
        }
        return true;
    }
}
$GLOBALS['wpdb']      = new Gsh_Fake_Wpdb_Rev();
$GLOBALS['options']   = array();

/* ---------- Guard claims stub (simulates validated app-token) ---------- */
$GLOBALS['gsh_tp_curriculr_current_claims'] = null;
function gsh_tp_curriculr_guard_current_claims() {
    return $GLOBALS['gsh_tp_curriculr_current_claims'];
}
$GLOBALS['refreshed'] = array();
function get_option( $k, $d = false ) { return $GLOBALS['options'][ $k ] ?? $d; }
function update_option( $k, $v, $a = null ) { $GLOBALS['options'][ $k ] = $v; return true; }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $k ) ); }
function wp_generate_password( $l = 12, $s = true, $e = true ) { return substr( str_repeat( 'aB3xY9Qz', 8 ), 0, $l ); }
function current_time( $t ) { return '2026-06-05 02:00:00'; }
function get_current_user_id() { return 1; }
function wp_json_encode( $d ) { return json_encode( $d ); }
function current_user_can( $c ) { return true; }
function rest_url( $p ) { return 'https://wp.test/wp-json/' . $p; }
function gsh_tp_get_profiles() { return $GLOBALS['profiles'] ?? array(); }
function gsh_tp_active_profile_id() { return 'p1'; }
function gsh_tp_do_refresh( $pid ) { $GLOBALS['refreshed'][] = $pid; }
function add_action() {}
function add_filter() {}
function register_activation_hook() {}
function register_deactivation_hook() {}
function wp_next_scheduled() { return false; }
function wp_schedule_event() {}
class WP_REST_Response {
    public $data; public $status;
    public function __construct( $d, $s = 200 ) { $this->data = $d; $this->status = $s; }
}
class Gsh_Fake_Req implements ArrayAccess {
    public $params;
    public function __construct( $params ) { $this->params = $params; }
    public function get_method() { return 'GET'; }
    public function get_route() { return ''; }
    public function offsetExists( $o ): bool { return isset( $this->params[ $o ] ); }
    public function offsetGet( $o ): mixed { return $this->params[ $o ] ?? null; }
    public function offsetSet( $o, $v ): void { $this->params[ $o ] = $v; }
    public function offsetUnset( $o ): void { unset( $this->params[ $o ] ); }
}

require __DIR__ . '/../../plugin/curriculr-data-layer.php';

$doc = json_decode( file_get_contents( __DIR__ . '/fixtures/sample-doc.json' ), true );

/* ---------- 1. Revision-Snapshot wird bei repo_put geschrieben ---------- */
$r1 = gsh_tp_curriculr_repo_put( 'sj_2026_27', $doc, 0 );
gsh_assert_eq( $r1['status'], 'ok', 'repo_put erzeugt Version 1' );
gsh_assert_eq( count( $GLOBALS['wpdb']->revs ), 1, 'nach erstem PUT genau 1 Revision' );

$rev = reset( $GLOBALS['wpdb']->revs );
gsh_assert_eq( (int) $rev['version'], 1, 'Revision trägt Version 1' );
gsh_assert_eq( $rev['schoolyear'], 'sj_2026_27', 'Revision trägt schoolyear' );
gsh_assert_true( ! empty( $rev['json'] ), 'Revision enthält json' );

/* ---------- 2. Weiterer PUT → weitere Revision ---------- */
gsh_tp_curriculr_repo_put( 'sj_2026_27', $doc, 1 );
gsh_assert_eq( count( $GLOBALS['wpdb']->revs ), 2, 'nach zweitem PUT zwei Revisionen' );

/* ---------- 3. REST: Revisions-Liste ---------- */
$list_resp = gsh_tp_curriculr_rest_revisions_list( new Gsh_Fake_Req( array( 'sj' => 'sj_2026_27' ) ) );
gsh_assert_eq( $list_resp->status, 200, 'revisions_list -> 200' );
gsh_assert_true( is_array( $list_resp->data ) && count( $list_resp->data ) === 2, 'revisions_list enthält 2 Einträge' );

/* ---------- 4. REST: Einzelne Revision abrufen ---------- */
$first_id = (int) array_key_first( $GLOBALS['wpdb']->revs );
$get_resp  = gsh_tp_curriculr_rest_revisions_get( new Gsh_Fake_Req( array( 'sj' => 'sj_2026_27', 'id' => $first_id ) ) );
gsh_assert_eq( $get_resp->status, 200, 'revisions_get -> 200' );
gsh_assert_eq( $get_resp->data['version'], 1, 'revisions_get gibt Version 1 zurück' );
gsh_assert_true( is_array( $get_resp->data['json'] ), 'revisions_get dekodiert json zu Array' );

/* ---------- 5. REST: Nicht-existierende Revision → 404 ---------- */
$nf_resp = gsh_tp_curriculr_rest_revisions_get( new Gsh_Fake_Req( array( 'sj' => 'sj_2026_27', 'id' => 9999 ) ) );
gsh_assert_eq( $nf_resp->status, 404, 'revisions_get unbekannte id -> 404' );

/* ---------- 6. Revision-Konflikt: kein PUT → keine neue Revision ---------- */
$before = count( $GLOBALS['wpdb']->revs );
$conf   = gsh_tp_curriculr_repo_put( 'sj_2026_27', $doc, 0 );
gsh_assert_eq( $conf['status'], 'conflict', 'veraltete baseVersion -> conflict' );
gsh_assert_eq( count( $GLOBALS['wpdb']->revs ), $before, 'Konflikt erzeugt keine neue Revision' );

/* ---------- 7. Author attribution: guard claims written to revision ---------- */
$GLOBALS['wpdb'] = new Gsh_Fake_Wpdb_Rev();
$GLOBALS['gsh_tp_curriculr_current_claims'] = array( 'sub' => 'iserv-u99', 'name' => 'Bob' );
$r_attr = gsh_tp_curriculr_repo_put( 'sj_attr', $doc, 0 );
gsh_assert_eq( $r_attr['status'], 'ok', 'attribution PUT ok' );
$rev_attr = reset( $GLOBALS['wpdb']->revs );
gsh_assert_eq( $rev_attr['author_sub'], 'iserv-u99', 'revision trägt author_sub' );
gsh_assert_eq( $rev_attr['author_name'], 'Bob', 'revision trägt author_name' );

/* ---------- 8. Attribution empty when no guard ran ---------- */
$GLOBALS['wpdb'] = new Gsh_Fake_Wpdb_Rev();
$GLOBALS['gsh_tp_curriculr_current_claims'] = null;
gsh_tp_curriculr_repo_put( 'sj_noauth', $doc, 0 );
$rev_noauth = reset( $GLOBALS['wpdb']->revs );
gsh_assert_eq( $rev_noauth['author_sub'], '', 'revision author_sub leer wenn kein Guard' );
gsh_assert_eq( $rev_noauth['author_name'], '', 'revision author_name leer wenn kein Guard' );

/* ---------- 9. Revisions list includes author fields ---------- */
$GLOBALS['wpdb'] = new Gsh_Fake_Wpdb_Rev();
$GLOBALS['gsh_tp_curriculr_current_claims'] = array( 'sub' => 'iserv-u77', 'name' => 'Charlie' );
gsh_tp_curriculr_repo_put( 'sj_list2', $doc, 0 );
$list2 = gsh_tp_curriculr_rest_revisions_list( new Gsh_Fake_Req( array( 'sj' => 'sj_list2' ) ) );
gsh_assert_true( isset( $list2->data[0]['author_sub'] ), 'revisions_list enthält author_sub' );
gsh_assert_eq( $list2->data[0]['author_sub'], 'iserv-u77', 'revisions_list author_sub korrekt' );

gsh_test_done();
