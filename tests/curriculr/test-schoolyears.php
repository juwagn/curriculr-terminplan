<?php
require __DIR__ . '/assert.php';

// Stub WP functions used by helpers
if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $key ) {
        $key = strtolower( $key );
        $key = preg_replace( '/\s+/', '-', $key );
        return preg_replace( '/[^a-z0-9_\-]/', '', $key );
    }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $str ) { return trim( strip_tags( $str ) ); }
}
if ( ! function_exists( 'absint' ) ) {
    function absint( $v ) { return abs( (int) $v ); }
}
if ( ! function_exists( 'sanitize_textarea_field' ) ) {
    function sanitize_textarea_field( $str ) { return trim( $str ); }
}
if ( ! function_exists( 'esc_url_raw' ) ) {
    function esc_url_raw( $url ) { return filter_var( trim($url), FILTER_SANITIZE_URL ) ?: ''; }
}
if ( ! function_exists( 'current_time' ) ) {
    function current_time( $fmt ) { return date( $fmt ); }
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
    function delete_transient( $key ) { return true; }
}

// In-memory option storage for tests
$GLOBALS['_wp_options'] = array();
function get_option( $key, $default = false ) { return $GLOBALS['_wp_options'][$key] ?? $default; }
function update_option( $key, $value ) { $GLOBALS['_wp_options'][$key] = $value; return true; }

// Additional stubs for curriculr sub-files loaded via require_once in gsh-terminplan.php
if ( ! defined( 'GSH_TP_CURRICULR_TEST' ) ) {
    define( 'GSH_TP_CURRICULR_TEST', true );
}

require __DIR__ . '/../../plugin/gsh-terminplan.php';

// ---- gsh_tp_calendar_id ----
gsh_assert_eq( gsh_tp_calendar_id( 'sj_2026_27', null ),          'sj_2026_27',              'null group → sj_key' );
gsh_assert_eq( gsh_tp_calendar_id( 'sj_2026_27', '' ),            'sj_2026_27',              'empty group → sj_key' );
gsh_assert_eq( gsh_tp_calendar_id( 'sj_2026_27', 'Schulleitung'), 'sj_2026_27__schulleitung','Schulleitung suffix' );
gsh_assert_eq( gsh_tp_calendar_id( 'sj_2026_27', 'Eltern' ),      'sj_2026_27__eltern',      'Eltern suffix' );
gsh_assert_eq( gsh_tp_calendar_id( 'sj_2026_27', 'Klassen 5-7'), 'sj_2026_27__klassen-5-7', 'spaces → hyphens via sanitize_key' );

// ---- gsh_tp_get_schoolyears / gsh_tp_save_schoolyears ----
gsh_assert_eq( gsh_tp_get_schoolyears(), array(), 'empty when option missing' );

$test_sy = array(
    array(
        'key'       => 'sj_2026_27',
        'label'     => '2026/27',
        'is_active' => true,
        'created'   => '2026-06-30',
        'shared'    => array( 'quartal_grenzen' => '', 'schuljahr_start' => '', 'cache_duration' => 3600 ),
        'calendars' => array(
            array( 'group' => null, 'label' => 'Alle Termine', 'ical_url' => 'https://x.de/feed.ics',
                   'is_draft' => false, 'managed' => true, 'orphaned' => false ),
            array( 'group' => 'Schulleitung', 'label' => 'Schulleitung', 'ical_url' => '',
                   'is_draft' => false, 'managed' => true, 'orphaned' => false ),
        ),
    ),
);
gsh_tp_save_schoolyears( $test_sy );
$loaded = gsh_tp_get_schoolyears();
gsh_assert_eq( count( $loaded ), 1, 'one schoolyear saved and loaded' );
gsh_assert_eq( $loaded[0]['key'], 'sj_2026_27', 'key preserved' );
gsh_assert_eq( count( $loaded[0]['calendars'] ), 2, 'two calendars preserved' );

// ---- Projection: gsh_tp_get_profiles() reads from schoolyears ----
$profiles = gsh_tp_get_profiles();
gsh_assert_eq( count( $profiles ), 2, 'projection yields 2 profiles' );

// Main calendar → is_active true
$main = null;
$sl   = null;
foreach ( $profiles as $p ) {
    if ( null === $p['group'] ) $main = $p;
    if ( 'Schulleitung' === $p['group'] ) $sl = $p;
}
gsh_assert_true( null !== $main, 'main calendar in projection' );
gsh_assert_true( null !== $sl, 'Schulleitung calendar in projection' );
gsh_assert_eq( $main['id'],        'sj_2026_27',               'main id = sj_key' );
gsh_assert_eq( $main['is_active'], true,                        'main calendar is_active for active schoolyear' );
gsh_assert_eq( $sl['id'],          'sj_2026_27__schulleitung',  'group id = sj__group' );
gsh_assert_eq( $sl['is_active'],   false,                       'group calendar never is_active' );
gsh_assert_eq( $sl['sj_key'],      'sj_2026_27',                'sj_key set on projected profile' );
gsh_assert_eq( $sl['group'],       'Schulleitung',              'group set on projected profile' );
gsh_assert_eq( $sl['cache_duration'], 3600,                     'shared cache_duration projected' );

// Fallback: when schoolyears empty, fall back to gsh_tp_profiles flat option
$GLOBALS['_wp_options'] = array(); // reset
update_option( 'gsh_tp_profiles', array(
    array( 'id' => 'old_id', 'label' => 'Old', 'ical_url' => '', 'cache_duration' => 3600,
           'quartal_grenzen' => '', 'schuljahr_start' => '', 'is_active' => true, 'is_draft' => false, 'created' => '2025-01-01' ),
) );
$profiles_fallback = gsh_tp_get_profiles();
gsh_assert_eq( count( $profiles_fallback ), 1, 'fallback to flat option when schoolyears missing' );
gsh_assert_eq( $profiles_fallback[0]['id'], 'old_id', 'old flat profile id preserved in fallback' );

gsh_test_done();
