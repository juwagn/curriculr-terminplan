<?php
/**
 * Integration test for the WP-bound Data Layer with stubbed WordPress.
 *
 * Exercises the glue that `php -l` alone cannot reach: repository put/get,
 * optimistic-concurrency conflict, the REST PUT paths (400/409/200), the
 * feed URL shape, the after_put → direct cache write, and the feed
 * token check — all without a real WordPress/DB. Dependency-free, runs with
 * plain `php`. Real-WP specifics (dbDelta, route registration, loopback
 * refresh, CORS preflight) still require a live install.
 */
define( 'GSH_TP_CURRICULR_TEST', true );
define( 'GSH_TP_VERSION', '4.5.0-test' );
define( 'GSH_TP_CACHE_VERSION', 3 );
define( 'ARRAY_A', 'ARRAY_A' );

require __DIR__ . '/assert.php';

/* ---------- minimale WordPress-Stubs ---------- */
class Gsh_Fake_Wpdb {
    public $prefix    = 'wp_';
    public $rows      = array();
    public $revs      = array();
    public $next_id   = 1;
    public $insert_id = 0;
    public function get_charset_collate() { return ''; }
    public function prepare( $q, ...$args ) {
        // Conflict lookup: prepare(sql, sj_string, version_int) -> __rev__:sj:ver
        if ( count( $args ) === 2 && is_string( $args[0] ) && ( is_int( $args[1] ) || ctype_digit( (string) $args[1] ) ) ) {
            return '__rev__:' . (string) $args[0] . ':' . (int) $args[1];
        }
        return isset( $args[0] ) ? $args[0] : $q;
    }
    public function get_row( $key, $out = null ) {
        if ( is_string( $key ) && strncmp( $key, '__rev__:', 8 ) === 0 ) {
            $parts = explode( ':', $key, 3 );
            $sj    = $parts[1] ?? '';
            $ver   = isset( $parts[2] ) ? (int) $parts[2] : -1;
            foreach ( $this->revs as $rev ) {
                if ( $rev['schoolyear'] === $sj && (int) $rev['version'] === $ver ) {
                    return (object) $rev;
                }
            }
            return null;
        }
        return $this->rows[ $key ] ?? null;
    }
    public function get_results( $q, $out = null ) { return array_values( $this->revs ); }
    public function insert( $t, $data ) {
        if ( strpos( (string) $t, 'revisions' ) !== false ) {
            $data['id']      = $this->next_id;
            $this->insert_id = $this->next_id;
            $this->revs[ $this->next_id ] = $data;
            $this->next_id++;
            return 1;
        }
        // Simuliert PRIMARY KEY (schoolyear): Duplicate-Insert schlägt fehl (Race).
        if ( isset( $this->rows[ $data['schoolyear'] ] ) ) {
            return false;
        }
        $this->rows[ $data['schoolyear'] ] = $data;
        return 1;
    }
    public function update( $t, $data, $where ) {
        $key = $where['schoolyear'] ?? null;
        if ( null === $key || ! isset( $this->rows[ $key ] ) ) {
            return false;
        }
        // Atomare Bedingung: WHERE version = <base> muss zur gespeicherten Version passen.
        if ( array_key_exists( 'version', $where ) && (int) $this->rows[ $key ]['version'] !== (int) $where['version'] ) {
            return 0;
        }
        $this->rows[ $key ] = array_merge( $this->rows[ $key ], $data );
        return 1;
    }
    public function query( $sql ) { return true; }
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
// Mutable schoolyears store: seit Auto-Provision (4.31.0) legt after_put fehlende
// Schuljahre selbst an — der Stub muss Schreiben+Lesen können.
$GLOBALS['schoolyears'] = array();
function gsh_tp_get_schoolyears() { return $GLOBALS['schoolyears'] ?? array(); }
function gsh_tp_save_schoolyears( $schoolyears ) { $GLOBALS['schoolyears'] = $schoolyears; }
function gsh_tp_calendar_id( $sj_key, $group ) {
    $base = sanitize_key( $sj_key );
    return ( null === $group || '' === $group ) ? $base : $base . '__' . sanitize_key( $group );
}
function sanitize_text_field( $str ) { return trim( strip_tags( (string) $str ) ); }
function gsh_tp_active_profile_id() { return 'p1'; }
function gsh_tp_ck( $prefix, $pid ) { return $prefix . $pid . '_v' . GSH_TP_CACHE_VERSION; }
function delete_transient( $k ) { unset( $GLOBALS['options'][ $k ] ); }
function set_transient( $k, $v, $ttl = 0 ) { $GLOBALS['options'][ $k ] = $v; }
$GLOBALS['gsh_tp_curriculr_current_claims'] = null;
function gsh_tp_curriculr_guard_current_claims() {
    return $GLOBALS['gsh_tp_curriculr_current_claims'];
}
function add_action() {}
function add_filter() {}
function register_activation_hook() {}
function register_deactivation_hook() {}
function wp_next_scheduled() { return false; }
function wp_schedule_event() {}
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

/* ---------- Feed-Token: rest_feed 404 bei falschem Token ---------- */
$nf = gsh_tp_curriculr_rest_feed( new Gsh_Fake_Req( array(), array( 'sj' => 'sj_2026_27', 'token' => 'WRONGTOKEN' ) ) );
gsh_assert_true( $nf instanceof WP_REST_Response && $nf->status === 404, 'rest_feed wrong token -> 404' );

/* ---------- Stage + Nicht-Disruption + Auto-Provision ---------- */
// Standard-Stufe ist entwurf; ohne Mapping provisioniert after_put das Schuljahr
// automatisch (inaktiv) — bestehende Profile bleiben unberührt (Live-Schutz).
$draft = gsh_tp_curriculr_rest_put( new Gsh_Fake_Req( array( 'doc' => $doc, 'baseVersion' => 3 ), array( 'sj' => 'sj_2026_27' ) ) );
gsh_assert_eq( $draft->data['stage'], 'entwurf', 'PUT without stage defaults to entwurf' );
gsh_assert_true( empty( $GLOBALS['options'][ gsh_tp_ck( 'gsh_tp_ical_', 'p1' ) ] ), 'entwurf PUT without mapping does not write profile cache' );

// Auto-Provision: Schuljahr angelegt (inaktiv), Haupt-Kalender vorhanden, ICS-Cache gefüllt.
$auto_sy = null;
foreach ( gsh_tp_get_schoolyears() as $sy_check ) {
    if ( $sy_check['key'] === 'sj_2026_27' ) { $auto_sy = $sy_check; break; }
}
gsh_assert_true( null !== $auto_sy, 'unmapped PUT auto-provisions the schoolyear' );
gsh_assert_true( empty( $auto_sy['is_active'] ), 'auto-provisioned schoolyear stays inactive (Live-Schutz)' );
$auto_ics = $GLOBALS['options'][ gsh_tp_ck( 'gsh_tp_ical_', sanitize_key( gsh_tp_calendar_id( 'sj_2026_27', null ) ) ) ] ?? '';
gsh_assert_contains( $auto_ics, 'BEGIN:VCALENDAR', 'auto-provisioned main calendar gets ICS cache' );

// Ohne explizites Profil-Mapping bleibt das aktive Profil auch für oeffentlich unberührt.
$pub = gsh_tp_curriculr_rest_put( new Gsh_Fake_Req( array( 'doc' => $doc, 'baseVersion' => 4, 'stage' => 'oeffentlich' ), array( 'sj' => 'sj_2026_27' ) ) );
gsh_assert_eq( $pub->data['stage'], 'oeffentlich', 'stage carried through to oeffentlich' );
gsh_assert_true( empty( $GLOBALS['options'][ gsh_tp_ck( 'gsh_tp_ical_', 'p1' ) ] ), 'oeffentlich PUT without profile mapping leaves active profile untouched' );

// Mit explizitem Mapping schreibt after_put das ICS direkt in den Profil-Cache (alle Stages).
// Schoolyears-Store leeren, damit der Legacy-Pfad (profile_map) exercised wird.
$GLOBALS['schoolyears'] = array();
$GLOBALS['options']['gsh_tp_curriculr_profile_map'] = array( 'sj_2026_27' => 'p1' );
unset( $GLOBALS['options'][ gsh_tp_ck( 'gsh_tp_ical_', 'p1' ) ] );
$pub2 = gsh_tp_curriculr_rest_put( new Gsh_Fake_Req( array( 'doc' => $doc, 'baseVersion' => 5, 'stage' => 'oeffentlich' ), array( 'sj' => 'sj_2026_27' ) ) );
$ical_cache = $GLOBALS['options'][ gsh_tp_ck( 'gsh_tp_ical_', 'p1' ) ] ?? '';
gsh_assert_true( strpos( $ical_cache, 'BEGIN:VCALENDAR' ) !== false, 'mapped oeffentlich PUT writes ICS to profile cache directly' );

// Entwurf-PUT mit Mapping schreibt ebenfalls in den Cache (Entwurf-Vorschau soll aktuelle Daten zeigen).
unset( $GLOBALS['options'][ gsh_tp_ck( 'gsh_tp_ical_', 'p1' ) ] );
$entwurf2 = gsh_tp_curriculr_rest_put( new Gsh_Fake_Req( array( 'doc' => $doc, 'baseVersion' => 6, 'stage' => 'entwurf' ), array( 'sj' => 'sj_2026_27' ) ) );
$ical_entwurf = $GLOBALS['options'][ gsh_tp_ck( 'gsh_tp_ical_', 'p1' ) ] ?? '';
gsh_assert_true( strpos( $ical_entwurf, 'BEGIN:VCALENDAR' ) !== false, 'mapped entwurf PUT writes ICS to profile cache directly' );

/* ---------- 409 + Author-Attribution ---------- */
$GLOBALS['gsh_tp_curriculr_current_claims'] = array( 'sub' => 'u1', 'name' => 'Max Mustermann' );
$pa1 = gsh_tp_curriculr_rest_put( new Gsh_Fake_Req(
    array( 'doc' => $doc, 'baseVersion' => 0 ),
    array( 'sj' => 'sj_author_test' )
) );
gsh_assert_eq( $pa1->status, 200, 'author test: first PUT ok' );

$pa2 = gsh_tp_curriculr_rest_put( new Gsh_Fake_Req(
    array( 'doc' => $doc, 'baseVersion' => 0 ),
    array( 'sj' => 'sj_author_test' )
) );
gsh_assert_eq( $pa2->status, 409, 'author test: stale PUT yields 409' );
gsh_assert_eq( $pa2->data['authorName'], 'Max Mustermann', '409 includes authorName of last saver' );
gsh_assert_true( strlen( $pa2->data['savedAt'] ) > 0, '409 includes non-empty savedAt' );
$GLOBALS['gsh_tp_curriculr_current_claims'] = null;

/* ---------- after_put: n:m mappings write per-profile filtered ICS cache ---------- */
$GLOBALS['options']['gsh_tp_curriculr_profile_map'] = array(
    'sj_nm_test' => array(
        array( 'profileId' => 'p_all',  'group' => null          ),
        array( 'profileId' => 'p_sl',   'group' => 'Schulleitung' ),
    ),
);
// Insert a fresh doc so repo_get returns something with events.
$doc_nm = array(
    'meta'       => array( 'name' => 'Test NM' ),
    'categories' => array(),
    'events'     => array(
        array( 'id' => 'nm1', 'title' => 'Alle', 'allDay' => true, 'start' => '2026-09-01', 'end' => '2026-09-01', 'groups' => array(), 'categoryId' => '' ),
        array( 'id' => 'nm2', 'title' => 'SL',   'allDay' => true, 'start' => '2026-09-02', 'end' => '2026-09-02', 'groups' => array( 'Schulleitung' ), 'categoryId' => '' ),
        array( 'id' => 'nm3', 'title' => 'Kol',  'allDay' => true, 'start' => '2026-09-03', 'end' => '2026-09-03', 'groups' => array( 'Kollegium' ),   'categoryId' => '' ),
    ),
    'schoolyear' => array( 'id' => 'sj_nm_test', 'firstSchoolDay' => '2026-09-01', 'holidays' => array() ),
);
$GLOBALS['wpdb']->rows['sj_nm_test'] = array(
    'schoolyear'  => 'sj_nm_test',
    'json'        => json_encode( $doc_nm ),
    'version'     => 1,
    'stage'       => 'oeffentlich',
    'feed_token'  => 'nmtoken',
    'updated_at'  => '2026-09-01 00:00:00',
);
unset( $GLOBALS['options'][ gsh_tp_ck( 'gsh_tp_ical_', 'p_all' ) ] );
unset( $GLOBALS['options'][ gsh_tp_ck( 'gsh_tp_ical_', 'p_sl' ) ] );
gsh_tp_curriculr_after_put( 'sj_nm_test', 'nmtoken' );

$ics_p_all = $GLOBALS['options'][ gsh_tp_ck( 'gsh_tp_ical_', 'p_all' ) ] ?? '';
$ics_p_sl  = $GLOBALS['options'][ gsh_tp_ck( 'gsh_tp_ical_', 'p_sl'  ) ] ?? '';

// Catch-all profile: all 3 events present.
gsh_assert_contains( $ics_p_all, 'UID:nm1@curriculr-planner', 'n:m catch-all: nm1 (no group) present' );
gsh_assert_contains( $ics_p_all, 'UID:nm2@curriculr-planner', 'n:m catch-all: nm2 (Schulleitung) present' );
gsh_assert_contains( $ics_p_all, 'UID:nm3@curriculr-planner', 'n:m catch-all: nm3 (Kollegium) present' );

// Schulleitung profile: nm1 (no group) + nm2 (Schulleitung) but not nm3 (Kollegium).
gsh_assert_contains( $ics_p_sl, 'UID:nm1@curriculr-planner', 'n:m SL filter: nm1 (no group) present' );
gsh_assert_contains( $ics_p_sl, 'UID:nm2@curriculr-planner', 'n:m SL filter: nm2 (Schulleitung) present' );
gsh_assert_true( strpos( $ics_p_sl, 'UID:nm3@curriculr-planner' ) === false, 'n:m SL filter: nm3 (Kollegium) excluded' );

/* ---------- Lost-Update-Race: atomarer UPDATE fängt ab, was der Schnellpfad
   allein nicht sieht (CODE-MED-002) ---------- */
class Gsh_Fake_Wpdb_Race extends Gsh_Fake_Wpdb {
    public $race_armed = false;
    public function get_row( $key, $out = null ) {
        $row = parent::get_row( $key, $out );
        if ( $this->race_armed && is_array( $row ) && ( $row['schoolyear'] ?? '' ) === 'sj_race' ) {
            $this->race_armed = false; // einmalig auslösen
            // Simuliert: zwischen diesem SELECT (repo_get in repo_put) und dem
            // folgenden atomaren UPDATE committet ein paralleler Request bereits
            // Version 2. Der Schnellpfad hatte hier noch Version 1 gesehen.
            $this->rows['sj_race']['version'] = 2;
        }
        return $row;
    }
}
$saved_wpdb    = $GLOBALS['wpdb'];
$race_wpdb     = new Gsh_Fake_Wpdb_Race();
$GLOBALS['wpdb'] = $race_wpdb;

$r_race1 = gsh_tp_curriculr_repo_put( 'sj_race', $doc, 0 );
gsh_assert_eq( $r_race1['status'], 'ok', 'race setup: first PUT ok' );
gsh_assert_eq( $r_race1['version'], 1, 'race setup: version 1' );

$race_wpdb->race_armed = true;
$r_race2 = gsh_tp_curriculr_repo_put( 'sj_race', $doc, 1 );
gsh_assert_eq( $r_race2['status'], 'conflict', 'atomic UPDATE catches lost-update race even though quick-path decision said ok' );
gsh_assert_eq( $r_race2['current']['version'], 2, 'race conflict reports the version that actually won the race' );

$GLOBALS['wpdb'] = $saved_wpdb;

gsh_test_done();
