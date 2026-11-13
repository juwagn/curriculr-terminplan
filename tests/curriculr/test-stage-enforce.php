<?php
/**
 * Stage enforcement: only docs with stage "oeffentlich" reach public readers
 * (token feed, group feed, ICS cache via after_put, [gsh_termine], [gsh_monat],
 * [gsh_terminplan]). Authenticated GET /doc keeps returning drafts.
 */
require __DIR__ . '/assert.php';

define( 'ABSPATH', __DIR__ . '/' );
define( 'GSH_TP_CURRICULR_TEST', true );
define( 'ARRAY_A', 'ARRAY_A' );

foreach ( array( 'esc_attr', 'esc_html', 'esc_url', 'esc_html__', 'esc_attr__' ) as $fn ) {
    eval( 'function ' . $fn . '( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, "UTF-8" ); }' );
}
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) ); }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_file_name( $s ) { return preg_replace( '/[^A-Za-z0-9_.\-]/', '', (string) $s ); }
function wp_unslash( $s ) { return $s; }
function absint( $v ) { return abs( (int) $v ); }
function plugin_dir_path( $f ) { return rtrim( dirname( $f ), '/' ) . '/'; }
function plugin_dir_url( $f ) { return 'https://example.test/wp-content/plugins/gsh-terminplan/'; }
function plugin_basename( $f ) { return basename( dirname( $f ) ) . '/' . basename( $f ); }
foreach ( array( 'add_action', 'add_filter', 'add_shortcode', 'register_activation_hook', 'register_deactivation_hook', 'register_uninstall_hook', 'wp_enqueue_script', 'wp_clear_scheduled_hook', 'wp_unschedule_event', 'wp_schedule_event', 'wp_schedule_single_event' ) as $fn ) {
    eval( 'function ' . $fn . '() {}' );
}
function wp_next_scheduled() { return false; }
function get_transient( $k ) { return $GLOBALS['_wp_options'][ 'tr_' . $k ] ?? false; }
function set_transient( $k, $v, $t = 0 ) { $GLOBALS['_wp_options'][ 'tr_' . $k ] = $v; return true; }
function delete_transient( $k ) { unset( $GLOBALS['_wp_options'][ 'tr_' . $k ] ); return true; }
function wp_json_encode( $v ) { return json_encode( $v ); }
function wp_strip_all_tags( $s ) { return strip_tags( (string) $s ); }
function current_user_can() { return false; }
function current_datetime() { return new DateTimeImmutable( '2026-09-18 09:00:00' ); }
function current_time( $t ) { return '2026-09-18 09:00:00'; }
function get_current_user_id() { return 7; }
function wp_generate_password( $l = 12, $s = true, $e = true ) { return substr( str_repeat( 'aB3xY9Qz', 8 ), 0, $l ); }
function rest_url( $p ) { return 'https://example.test/wp-json/' . $p; }
function shortcode_atts( $defaults, $atts ) { return array_merge( $defaults, array_intersect_key( (array) $atts, $defaults ) ); }
function add_query_arg( $k, $v = '' ) { return '/termine/?' . $k . '=' . $v; }
function remove_query_arg() { return '/termine/'; }
function wp_http_validate_url( $u ) { return false !== filter_var( $u, FILTER_VALIDATE_URL ); }
// Background refresh must not hit the network in tests: report "unreachable".
function wp_remote_get() { return array( 'response' => array( 'code' => 404 ), 'body' => '' ); }
function wp_remote_retrieve_response_code( $r ) { return $r['response']['code']; }
function wp_remote_retrieve_body( $r ) { return $r['body']; }
function is_wp_error( $x ) { return false; }

$GLOBALS['_wp_options'] = array();
function get_option( $k, $d = false ) { return $GLOBALS['_wp_options'][ $k ] ?? $d; }
function update_option( $k, $v, $a = null ) { $GLOBALS['_wp_options'][ $k ] = $v; return true; }
function delete_option( $k ) { unset( $GLOBALS['_wp_options'][ $k ] ); return true; }

class WP_REST_Response { public $data; public $status; public function __construct( $d, $s = 200 ) { $this->data = $d; $this->status = $s; } }

// In-memory docs table (keyed by schoolyear); revisions are accepted and ignored.
$GLOBALS['wpdb'] = new class {
    public $prefix    = 'wp_';
    public $rows      = array();
    public $insert_id = 0;
    public $last_error = '';
    public function get_charset_collate() { return ''; }
    public function prepare( $q, ...$args ) { return isset( $args[0] ) && ! isset( $args[1] ) ? $args[0] : '__none__'; }
    public function get_row( $key, $out = null ) { return $this->rows[ $key ] ?? null; }
    public function get_var( $q ) { return null; }
    public function get_results( $q, $out = null ) { return array(); }
    public function query( $q ) { return true; }
    public function insert( $t, $data ) {
        if ( strpos( (string) $t, "revisions" ) !== false ) { $this->insert_id++; return 1; }
        $this->rows[ $data['schoolyear'] ] = $data;
        return 1;
    }
    public function update( $t, $data, $where ) {
        $key = $where['schoolyear'];
        if ( ! isset( $this->rows[ $key ] ) || (int) $this->rows[ $key ]['version'] !== (int) $where['version'] ) { return 0; }
        $this->rows[ $key ] = array_merge( $this->rows[ $key ], $data );
        return 1;
    }
};

require __DIR__ . '/../../plugin/gsh-terminplan.php';

class Gsh_Stage_Req implements ArrayAccess {
    public $body; public $params;
    public function __construct( $body, $params ) { $this->body = $body; $this->params = $params; }
    public function get_json_params() { return $this->body; }
    public function offsetExists( $o ): bool { return isset( $this->params[ $o ] ); }
    public function offsetGet( $o ): mixed { return $this->params[ $o ] ?? null; }
    public function offsetSet( $o, $v ): void { $this->params[ $o ] = $v; }
    public function offsetUnset( $o ): void { unset( $this->params[ $o ] ); }
}

$sj  = 'sj_muster';
$doc = array(
    'meta'            => array( 'name' => 'Muster-Gesamtschule 2026/27' ),
    'categories'      => array( array( 'id' => 'c1', 'label' => 'Veranstaltung', 'color' => '#0E9F6E' ) ),
    'availableGroups' => array( 'Eltern' ),
    'schoolyear'      => array( 'id' => 'x', 'label' => '2026/27', 'firstSchoolDay' => '2026-08-24', 'holidays' => array() ),
    'events'          => array(
        array( 'id' => 'e1', 'title' => 'Elternabend Muster-Gesamtschule', 'start' => '2026-09-21', 'end' => '2026-09-21', 'allDay' => false, 'startTime' => '18:00', 'endTime' => '20:00', 'categoryId' => 'c1', 'groups' => array( 'Eltern' ) ),
    ),
);

// Live schoolyear: main calendar + Eltern calendar (public) and a draft preview calendar.
update_option( 'gsh_tp_schoolyears', array(
    array(
        'key'       => $sj,
        'label'     => '2026/27',
        'is_active' => true,
        'shared'    => array( 'cache_duration' => 3600, 'quartal_grenzen' => '', 'schuljahr_start' => '' ),
        'calendars' => array(
            array( 'group' => null,     'label' => 'Alle',   'ical_url' => '', 'is_draft' => false, 'managed' => true, 'orphaned' => false, 'source' => 'planner' ),
            array( 'group' => 'Eltern', 'label' => 'Eltern', 'ical_url' => '', 'is_draft' => false, 'managed' => true, 'orphaned' => false, 'source' => 'planner' ),
        ),
    ),
) );
$main_ck   = gsh_tp_ck( 'gsh_tp_ical_', sanitize_key( gsh_tp_calendar_id( $sj, null ) ) );
$eltern_ck = gsh_tp_ck( 'gsh_tp_ical_', sanitize_key( gsh_tp_calendar_id( $sj, 'Eltern' ) ) );

function gsh_stage_put( $sj, $doc, $stage ) {
    $row  = $GLOBALS['wpdb']->rows[ $sj ] ?? null;
    $base = $row ? (int) $row['version'] : 0;
    return gsh_tp_curriculr_rest_put( new Gsh_Stage_Req( array( 'doc' => $doc, 'baseVersion' => $base, 'stage' => $stage ), array( 'sj' => $sj ) ) );
}
function gsh_stage_token( $sj ) { return $GLOBALS['wpdb']->rows[ $sj ]['feed_token']; }
function gsh_stage_main_ical_url( $sj ) {
    foreach ( gsh_tp_get_schoolyears() as $sy ) {
        if ( $sy['key'] === $sj ) { return $sy['calendars'][0]['ical_url']; }
    }
    return null;
}

/* ---------- Unknown schoolyear is never public ---------- */
gsh_assert_eq( gsh_tp_curriculr_doc_is_public( 'sj_gibt_es_nicht' ), false, 'unknown schoolyear is not public' );

/* ---------- entwurf + genehmigt: nothing public changes ---------- */
foreach ( array( 'entwurf', 'genehmigt' ) as $stage ) {
    update_option( $main_ck, 'OLD-MAIN' );
    update_option( $eltern_ck, 'OLD-ELTERN' );
    $res = gsh_stage_put( $sj, $doc, $stage );
    gsh_assert_eq( $res->status, 200, "$stage: PUT accepted" );
    gsh_assert_eq( $res->data['stage'], $stage, "$stage: response carries stage" );
    gsh_assert_eq( gsh_tp_curriculr_doc_is_public( $sj ), false, "$stage: doc is not public" );

    $token = gsh_stage_token( $sj );
    $feed  = gsh_tp_curriculr_rest_feed( new Gsh_Stage_Req( array(), array( 'sj' => $sj, 'token' => $token ) ) );
    gsh_assert_true( $feed instanceof WP_REST_Response && 404 === $feed->status && array( 'error' => 'not_found' ) === $feed->data, "$stage: feed 404 like a wrong token" );
    $gfeed = gsh_tp_curriculr_rest_feed_group( new Gsh_Stage_Req( array(), array( 'sj' => $sj, 'token' => $token, 'group' => 'Eltern' ) ) );
    gsh_assert_true( $gfeed instanceof WP_REST_Response && 404 === $gfeed->status && array( 'error' => 'not_found' ) === $gfeed->data, "$stage: group feed 404 like a wrong token" );

    gsh_assert_eq( get_option( $main_ck ), 'OLD-MAIN', "$stage: main ICS cache untouched" );
    gsh_assert_eq( get_option( $eltern_ck ), 'OLD-ELTERN', "$stage: group ICS cache untouched" );
    gsh_assert_eq( gsh_stage_main_ical_url( $sj ), '', "$stage: ical_url not set" );

    gsh_assert_eq( gsh_tp_upcoming_shortcode( array() ), '', "$stage: [gsh_termine] empty for visitors" );
    gsh_assert_eq( gsh_tp_month_shortcode( array() ), '', "$stage: [gsh_monat] empty for visitors" );
    gsh_assert_contains( gsh_tp_shortcode( array() ), 'noch nicht freigegeben', "$stage: [gsh_terminplan] shows not-released state" );

    // Authenticated read keeps returning the draft.
    $get = gsh_tp_curriculr_rest_get( new Gsh_Stage_Req( array(), array( 'sj' => $sj ) ) );
    gsh_assert_eq( $get->status, 200, "$stage: authenticated GET /doc still 200" );
    gsh_assert_eq( $get->data['stage'], $stage, "$stage: authenticated GET /doc returns stage" );
    gsh_assert_eq( $get->data['doc']['events'][0]['id'], 'e1', "$stage: authenticated GET /doc returns the draft doc" );
}

/* ---------- Preview calendar (is_draft) keeps receiving drafts ---------- */
$sys = get_option( 'gsh_tp_schoolyears' );
$sys[0]['calendars'][0]['is_draft'] = true;
update_option( 'gsh_tp_schoolyears', $sys );
update_option( $main_ck, 'OLD-MAIN' );
update_option( $eltern_ck, 'OLD-ELTERN' );
gsh_stage_put( $sj, $doc, 'genehmigt' );
gsh_assert_contains( (string) get_option( $main_ck ), 'BEGIN:VCALENDAR', 'genehmigt: draft preview calendar cache refreshed' );
gsh_assert_eq( get_option( $eltern_ck ), 'OLD-ELTERN', 'genehmigt: non-draft calendar cache still untouched' );
$sys[0]['calendars'][0]['is_draft'] = false;
update_option( 'gsh_tp_schoolyears', $sys );

/* ---------- oeffentlich: everything public ---------- */
update_option( $main_ck, 'OLD-MAIN' );
update_option( $eltern_ck, 'OLD-ELTERN' );
$res = gsh_stage_put( $sj, $doc, 'oeffentlich' );
gsh_assert_eq( $res->status, 200, 'oeffentlich: PUT accepted' );
gsh_assert_eq( gsh_tp_curriculr_doc_is_public( $sj ), true, 'oeffentlich: doc is public' );
gsh_assert_contains( (string) get_option( $main_ck ), 'UID:e1@curriculr-planner', 'oeffentlich: main ICS cache written' );
gsh_assert_contains( (string) get_option( $eltern_ck ), 'UID:e1@curriculr-planner', 'oeffentlich: group ICS cache written' );
gsh_assert_contains( (string) gsh_stage_main_ical_url( $sj ), '/feed/' . $sj . '/', 'oeffentlich: ical_url set to feed' );
gsh_assert_contains( gsh_tp_upcoming_shortcode( array() ), 'Elternabend Muster-Gesamtschule', 'oeffentlich: [gsh_termine] shows event' );
gsh_assert_contains( gsh_tp_month_shortcode( array( 'monat' => '2026-09' ) ), 'Elternabend Muster-Gesamtschule', 'oeffentlich: [gsh_monat] shows event' );
$gfeed = gsh_tp_curriculr_rest_feed_group( new Gsh_Stage_Req( array(), array( 'sj' => $sj, 'token' => 'WRONG', 'group' => 'Eltern' ) ) );
gsh_assert_eq( $gfeed->status, 404, 'oeffentlich: wrong token still 404' );

/* ---------- oeffentlich: feed delivers (rest_feed exits, so assert on shutdown) ---------- */
ob_start();
register_shutdown_function( function () {
    $out = ob_get_clean();
    gsh_assert_contains( (string) $out, 'UID:e1@curriculr-planner', 'oeffentlich: feed delivers ICS' );
    gsh_test_done();
} );
gsh_tp_curriculr_rest_feed( new Gsh_Stage_Req( array(), array( 'sj' => $sj, 'token' => gsh_stage_token( $sj ) ) ) );
fwrite( STDERR, "FAIL: oeffentlich feed returned instead of streaming\n" );
exit( 1 );
