<?php
require __DIR__ . '/assert.php';

// WP stubs
if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $key ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) ); }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $str ) { return trim( strip_tags( $str ) ); }
}
if ( ! function_exists( 'absint' ) ) { function absint( $v ) { return abs( (int) $v ); } }
if ( ! function_exists( 'sanitize_textarea_field' ) ) {
    function sanitize_textarea_field( $s ) { return trim( $s ); }
}
if ( ! function_exists( 'esc_url_raw' ) ) {
    function esc_url_raw( $url ) { return filter_var(trim($url), FILTER_SANITIZE_URL) ?: ''; }
}
if ( ! function_exists( 'current_time' ) ) { function current_time( $f ) { return date($f); } }
if ( ! function_exists( 'rest_url' ) ) {
    function rest_url( $path ) { return 'https://example.com/wp-json/' . $path; }
}

// Additional WP stubs needed to load gsh-terminplan.php
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}
if ( ! function_exists( 'plugin_dir_path' ) ) {
    function plugin_dir_path( $file ) { return rtrim( dirname( $file ), '/' ) . '/'; }
}
if ( ! function_exists( 'plugin_basename' ) ) {
    function plugin_basename( $file ) { return basename( dirname( $file ) ) . '/' . basename( $file ); }
}
if ( ! function_exists( 'add_action' ) ) {
    function add_action() {}
}
if ( ! function_exists( 'add_filter' ) ) {
    function add_filter() {}
}
if ( ! function_exists( 'add_shortcode' ) ) {
    function add_shortcode() {}
}
if ( ! function_exists( 'register_deactivation_hook' ) ) {
    function register_deactivation_hook() {}
}
if ( ! function_exists( 'register_activation_hook' ) ) {
    function register_activation_hook() {}
}
if ( ! function_exists( 'wp_next_scheduled' ) ) {
    function wp_next_scheduled() { return false; }
}
if ( ! function_exists( 'wp_schedule_event' ) ) {
    function wp_schedule_event() {}
}
if ( ! function_exists( 'wp_unschedule_event' ) ) {
    function wp_unschedule_event() {}
}
if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
    function wp_clear_scheduled_hook() {}
}
if ( ! function_exists( 'register_uninstall_hook' ) ) {
    function register_uninstall_hook() {}
}
if ( ! function_exists( 'wp_http_validate_url' ) ) {
    function wp_http_validate_url( $url ) { return filter_var( $url, FILTER_VALIDATE_URL ) !== false; }
}
if ( ! function_exists( 'get_transient' ) ) {
    function get_transient( $key ) { return false; }
}
if ( ! function_exists( 'set_transient' ) ) {
    function set_transient( $key, $value, $expiration = 0 ) { return true; }
}
if ( ! function_exists( 'delete_transient' ) ) {
    function delete_transient( $k ) { return true; }
}
if ( ! function_exists( 'wp_cache_flush' ) ) {
    function wp_cache_flush() {}
}

if ( ! defined( 'GSH_TP_CURRICULR_TEST' ) ) {
    define( 'GSH_TP_CURRICULR_TEST', true );
}

$GLOBALS['_wp_options'] = array();
function get_option( $k, $d = false ) { return $GLOBALS['_wp_options'][$k] ?? $d; }
function update_option( $k, $v, $autoload = true ) { $GLOBALS['_wp_options'][$k] = $v; return true; }

// Minimal WP_REST_Response stub
class WP_REST_Response {
    public $data; public $status;
    public function __construct( $data, $status = 200 ) { $this->data = $data; $this->status = $status; }
}

require __DIR__ . '/../../plugin/gsh-terminplan.php';

// ---- gsh_tp_curriculr_provision_schoolyear ----

// Create new schoolyear with groups
$resp = gsh_tp_curriculr_provision_schoolyear( 'sj_2026_27', '2026/27', array( 'Schulleitung', 'Eltern' ) );
gsh_assert_eq( $resp->status, 200, 'provision returns 200' );
gsh_assert_eq( $resp->data['updated'], true, 'updated true' );

$sys = gsh_tp_get_schoolyears();
gsh_assert_eq( count( $sys ), 1, 'one schoolyear created' );
gsh_assert_eq( $sys[0]['key'], 'sj_2026_27', 'key correct' );
gsh_assert_eq( count( $sys[0]['calendars'] ), 3, 'Haupt + 2 groups = 3 calendars' );

$groups_in_cals = array_filter( array_column( $sys[0]['calendars'], 'group' ) );
gsh_assert_true( in_array( 'Schulleitung', $groups_in_cals, true ), 'Schulleitung calendar created' );
gsh_assert_true( in_array( 'Eltern', $groups_in_cals, true ), 'Eltern calendar created' );

// Main calendar always present
$main_cal = null;
foreach ( $sys[0]['calendars'] as $c ) { if ( null === $c['group'] ) $main_cal = $c; }
gsh_assert_true( null !== $main_cal, 'main calendar present' );
gsh_assert_eq( $main_cal['managed'], true, 'main calendar managed' );

// Idempotent: re-send same groups
$resp2 = gsh_tp_curriculr_provision_schoolyear( 'sj_2026_27', '2026/27', array( 'Schulleitung', 'Eltern' ) );
gsh_assert_eq( $resp2->status, 200, 'idempotent re-provision returns 200' );
$sys2 = gsh_tp_get_schoolyears();
gsh_assert_eq( count( $sys2[0]['calendars'] ), 3, 'no duplicate calendars on re-provision' );

// Orphan: remove Eltern from groups
$resp3 = gsh_tp_curriculr_provision_schoolyear( 'sj_2026_27', '2026/27', array( 'Schulleitung' ) );
gsh_assert_eq( $resp3->status, 200, 'orphan marking returns 200' );
$sys3 = gsh_tp_get_schoolyears();
gsh_assert_eq( count( $sys3[0]['calendars'] ), 3, 'orphaned calendar NOT deleted' );
$eltern_cal = null;
foreach ( $sys3[0]['calendars'] as $c ) { if ( 'Eltern' === $c['group'] ) $eltern_cal = $c; }
gsh_assert_eq( $eltern_cal['orphaned'], true, 'Eltern marked orphaned' );
// Main never orphaned
$main_cal_after = null;
foreach ( $sys3[0]['calendars'] as $c ) { if ( null === $c['group'] ) $main_cal_after = $c; }
gsh_assert_eq( $main_cal_after['orphaned'], false, 'main calendar not orphaned' );

// Un-orphan: add Eltern back
$resp4 = gsh_tp_curriculr_provision_schoolyear( 'sj_2026_27', '2026/27', array( 'Schulleitung', 'Eltern' ) );
$sys4 = gsh_tp_get_schoolyears();
foreach ( $sys4[0]['calendars'] as $c ) {
    if ( 'Eltern' === $c['group'] ) {
        gsh_assert_eq( $c['orphaned'], false, 'Eltern un-orphaned on re-add' );
    }
}

// Limit: max 8 calendars (1 main + 7 groups)
$too_many = array( 'G1','G2','G3','G4','G5','G6','G7','G8' ); // 8 groups + 1 main = 9
$GLOBALS['_wp_options'] = array(); // fresh
$resp_limit = gsh_tp_curriculr_provision_schoolyear( 'sj_test', 'Test', $too_many );
gsh_assert_eq( $resp_limit->status, 400, '9 calendars → 400 limit error' );

// Response includes calendars array
$GLOBALS['_wp_options'] = array();
$resp5 = gsh_tp_curriculr_provision_schoolyear( 'sj_2026_27', '2026/27', array( 'Schulleitung' ) );
gsh_assert_true( isset( $resp5->data['calendars'] ), 'response includes calendars array' );
gsh_assert_eq( count( $resp5->data['calendars'] ), 2, 'response has 2 calendars (main + Schulleitung)' );

gsh_test_done();
