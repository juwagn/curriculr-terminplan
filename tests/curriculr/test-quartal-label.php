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

// Quartalstitel kommt aus den echten Grenzen (Planner/Einstellung), nicht mehr fest aus dem Code.
gsh_assert_eq( gsh_tp_quartal_label( 1, array( 'start' => '2026-08-17', 'end' => '2026-11-13' ), 'x' ), 'Quartal 1 &ndash; August bis November', 'Q1 endet im November' );
gsh_assert_eq( gsh_tp_quartal_label( 2, array( 'start' => '2026-11-16', 'end' => '2027-02-05' ), 'x' ), 'Quartal 2 &ndash; November bis Februar', 'Q2 über den Jahreswechsel' );
gsh_assert_eq( gsh_tp_quartal_label( 3, array( 'start' => '2027-03-01', 'end' => '2027-03-26' ), 'x' ), 'Quartal 3 &ndash; März', 'Start und Ende im selben Monat' );
gsh_assert_eq( gsh_tp_quartal_label( 4, array( 'start' => 'kaputt', 'end' => '' ), 'Fallback' ), 'Fallback', 'unlesbare Daten ergeben den Fallback' );
gsh_assert_eq( gsh_tp_quartal_label( 4, array(), 'Fallback' ), 'Fallback', 'fehlende Daten ergeben den Fallback' );

gsh_test_done();
