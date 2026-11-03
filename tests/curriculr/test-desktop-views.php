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

foreach ( array( 'esc_attr', 'esc_html', 'esc_url' ) as $fn ) {
    if ( ! function_exists( $fn ) ) {
        eval( 'function ' . $fn . '( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, "UTF-8" ); }' );
    }
}
if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $v ) { return json_encode( $v ); }
}
if ( ! function_exists( 'esc_attr__' ) ) {
    function esc_attr__( $s ) { return $s; }
}

require __DIR__ . '/../../plugin/gsh-terminplan.php';

// ---- Zeitspanne eines Quartals ----
gsh_assert_eq( gsh_tp_fmt_range( array( 'start' => '2026-08-24', 'end' => '2026-11-13' ) ), '24.08.–13.11.', 'Quartalsspanne' );
gsh_assert_eq( gsh_tp_fmt_range( array( 'start' => 'kaputt', 'end' => '' ) ), '', 'unlesbare Spanne ergibt leer' );

// ---- Violett-Erkennung (Familienregel: kein Violett) ----
gsh_assert_true( gsh_tp_color_is_violet( '#8e44ad' ), 'altes Standard-Violett wird erkannt' );
gsh_assert_true( gsh_tp_color_is_violet( '#7C3AED' ), 'Planner-Violett wird erkannt' );
gsh_assert_true( ! gsh_tp_color_is_violet( '#475467' ), 'Graublau ist nicht violett' );
gsh_assert_true( ! gsh_tp_color_is_violet( '#2980b9' ), 'Blau ist nicht violett' );
gsh_assert_true( ! gsh_tp_color_is_violet( '#e74c3c' ), 'Rot ist nicht violett' );
gsh_assert_true( ! gsh_tp_color_is_violet( 'rot' ), 'ungültiger Wert ist nicht violett' );
$violet_defaults = 0;
foreach ( GSH_TP_DEFAULT_CATEGORIES as $dc ) {
    if ( gsh_tp_color_is_violet( $dc['color'] ) ) {
        $violet_defaults++;
    }
}
gsh_assert_eq( $violet_defaults, 0, 'keine Standardkategorie ist violett' );

// ---- Jahresansicht und Quartalsübersicht mit echten Funktionen ----
$ics = "BEGIN:VCALENDAR\r\n"
     . "BEGIN:VEVENT\r\nUID:a\r\nSUMMARY:Elternabend 5\r\nDTSTART;VALUE=DATE:20261103\r\nDTEND;VALUE=DATE:20261104\r\nEND:VEVENT\r\n"
     . "BEGIN:VEVENT\r\nUID:b\r\nSUMMARY:<script>alert(1)</script>\r\nDTSTART;VALUE=DATE:20261104\r\nDTEND;VALUE=DATE:20261105\r\nEND:VEVENT\r\n"
     . "END:VCALENDAR\r\n";
$events = array_map( 'gsh_tp_assign_categories_to_event', gsh_tp_parse_events( $ics ) );
$index  = gsh_tp_build_date_index( $events );
$quart  = array(
    array( 'start' => '2026-08-24', 'end' => '2026-11-13' ),
    array( 'start' => '2026-11-16', 'end' => '2027-02-05' ),
    array( 'start' => '2027-02-08', 'end' => '2027-04-23' ),
    array( 'start' => '2027-04-26', 'end' => '2027-07-16' ),
);
$year = gsh_tp_yearview( $index, $quart, 'x' );
gsh_assert_eq( substr_count( $year, 'class="gtp-ym-mo"' ), 12, 'Jahresmatrix hat zwölf Monatszeilen (Aug bis Jul)' );
gsh_assert_eq( substr_count( $year, 'class="gtp-yc-m"' ), 12, 'Handy-Kalender hat zwölf Monate' );
gsh_assert_contains( $year, 'data-d="2026-11-03"', 'Tag mit Termin ist ein Knopf' );
gsh_assert_contains( $year, 'data-days="', 'Tagesdaten für Popover vorhanden' );
gsh_assert_true( false === strpos( $year, '<script>alert' ), 'Termintitel wird nicht als HTML ausgegeben' );
gsh_assert_true( false !== strpos( $year, '&lt;script&gt;' ) || false !== strpos( $year, '<script' ) || false !== strpos( $year, '&quot;' ), 'Termintitel steht nur maskiert in den Daten' );
gsh_assert_eq( gsh_tp_yearview( array(), $quart, 'x' ), '', 'ohne Termine keine Jahresansicht' );

$mini = gsh_tp_minimap( $index, $quart[0], '2026-08-24', 1 );
gsh_assert_contains( $mini, 'class="gtp-mini"', 'Quartalsübersicht rendert' );
gsh_assert_contains( $mini, 'gtpMiniGo(1,10)', 'Wochenknopf springt zur Schulwoche 10' );
gsh_assert_eq( substr_count( $mini, 'class="gtp-mini-w"' ), 12, 'Quartal 1 hat zwölf Schulwochen (00 bis 11)' );

gsh_test_done();
