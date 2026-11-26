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


// Oberstufe steht vor Konferenzen und hat kurze Stichwörter wie „ef“ und „q2“.
$GLOBALS['_wp_options']['gsh_tp_categories'] = array(
    array( 'id' => 'oberstufe', 'label' => 'Oberstufe', 'color' => '#2980b9', 'slug' => 'oberstufe', 'keywords' => array( 'ef', 'q2' ) ),
    array( 'id' => 'konferenzen', 'label' => 'Konferenzen/DB', 'color' => '#475467', 'slug' => 'konferenzen', 'keywords' => array( 'konferenz', 'db' ) ),
);

require __DIR__ . '/../../plugin/gsh-terminplan.php';

$ev = function ( $summary, $categories ) {
    return array( 'summary' => $summary, 'description' => 'Gruppen: Kollegium, Schülerinnen und Schüler', 'location' => '', 'categories' => $categories );
};

$r = gsh_tp_assign_categories_to_event( $ev( 'TBS-Vorbereitung, Lern- und Beratungstag EF', 'Konferenzen/DB' ) );
gsh_assert_eq( $r['categories'], array( 'konferenzen' ), 'Kategorie aus dem Planer gilt allein, Stichwort „EF“ im Titel ändert sie nicht' );

$r = gsh_tp_assign_categories_to_event( $ev( 'Beratungstag Q2', '  konferenzen/db ' ) );
gsh_assert_eq( $r['categories'], array( 'konferenzen' ), 'Vergleich mit dem Kategorienamen ignoriert Groß-/Kleinschreibung und Leerzeichen' );

$r = gsh_tp_assign_categories_to_event( $ev( 'Abiturvorbereitung EF', 'Termine' ) );
gsh_assert_eq( $r['categories'], array( 'oberstufe' ), 'Unbekannte Kategorie (z. B. aus IServ): Stichwortsuche bleibt Ersatz' );

$r = gsh_tp_assign_categories_to_event( $ev( 'Ausflug', '' ) );
gsh_assert_eq( $r['categories'], array(), 'Ohne Kategorie und ohne Treffer bleibt der Termin ungefärbt' );

$r = gsh_tp_assign_categories_to_event( array( 'summary' => 'X', 'categories' => array( 'oberstufe' ) ) );
gsh_assert_eq( $r['categories'], array( 'oberstufe' ), 'Bereits zugewiesene Kategorien bleiben unverändert' );

gsh_test_done();
