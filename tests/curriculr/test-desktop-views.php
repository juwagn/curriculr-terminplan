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
if ( ! function_exists( 'wp_strip_all_tags' ) ) {
    function wp_strip_all_tags( $s ) { return strip_tags( (string) $s ); }
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

// ---- Kette Planner-Dokument → ICS → Index → Ansichten (Demo-Daten) ----
$doc = array(
    'schoolyear'      => array( 'label' => '2026/27', 'firstSchoolDay' => '2026-08-24' ),
    'availableGroups' => array( 'Eltern' ),
    'categories'      => array(
        array( 'id' => 'c-el', 'slug' => 'eltern', 'label' => 'Eltern', 'color' => '#0891b2' ),
        array( 'id' => 'c-fe', 'slug' => 'feiertage', 'label' => 'Feiertage/Ferien', 'color' => '#d4a017' ),
    ),
    'events'          => array(
        array( 'id' => '1', 'title' => 'Elternabend 5', 'start' => '2026-11-03', 'end' => '2026-11-03', 'allDay' => false, 'startTime' => '18:00', 'endTime' => '20:00', 'categoryId' => 'c-el', 'groups' => array( 'Eltern' ) ),
        array( 'id' => '2', 'title' => '<script>alert(1)</script>', 'start' => '2026-11-04', 'end' => '2026-11-04', 'allDay' => true, 'categoryId' => 'c-el', 'groups' => array() ),
        array( 'id' => '3', 'title' => 'Tag der offenen Tür', 'start' => '2026-11-21', 'end' => '2026-11-21', 'allDay' => false, 'startTime' => '10:00', 'endTime' => '14:00', 'categoryId' => 'c-el', 'groups' => array() ),
        array( 'id' => '4', 'title' => 'Weihnachtsferien', 'start' => '2026-12-23', 'end' => '2027-01-03', 'allDay' => true, 'categoryId' => 'c-fe', 'groups' => array() ),
    ),
);
$ics    = gsh_tp_curriculr_build_ics( $doc, null );
gsh_assert_eq( substr_count( $ics, 'BEGIN:VEVENT' ), 4, 'ICS enthält alle vier Planner-Termine' );
$events = array_map( 'gsh_tp_assign_categories_to_event', gsh_tp_parse_events( $ics ) );
$index  = gsh_tp_build_date_index( $events );
$quart  = array(
    array( 'start' => '2026-08-24', 'end' => '2026-11-13' ),
    array( 'start' => '2026-11-16', 'end' => '2027-02-05' ),
    array( 'start' => '2027-02-08', 'end' => '2027-04-23' ),
    array( 'start' => '2027-04-26', 'end' => '2027-07-16' ),
);
$notes = gsh_tp_curriculr_display_notes( $doc );
gsh_assert_eq( $notes['2026-11-16'][0], 'Sa 21.11.: Tag der offenen Tür, 10.00–14.00 Uhr', 'Samstagstermin steht als Anmerkung in der Woche' );
gsh_assert_contains( gsh_tp_table( $index, $quart[1], '2026-08-24', $notes ), 'Tag der offenen Tür', 'Raster zeigt Samstagstermin in der Anmerkungen-Spalte' );

// ---- Jahreskalender ----
$year = gsh_tp_yearview( $index, $quart, 'x', '2026-08-24' );
gsh_assert_eq( substr_count( $year, 'class="gtp-cal-m"' ), 12, 'Jahreskalender hat zwölf Monatsspalten (Aug bis Jul)' );
gsh_assert_eq( substr_count( $year, 'class="gtp-yc-m"' ), 12, 'Handy-Kalender hat zwölf Monate' );
gsh_assert_true( false === strpos( $year, 'gtp-ym' ), 'Matrix (Heatmap) ist entfernt' );
gsh_assert_contains( $year, '<span class="gtp-cal-sw" title="Schulwoche 10">10</span>', 'Schulwoche steht am Montag (SW 10 = Woche ab 02.11.)' );
gsh_assert_true( substr_count( $year, 'class="gtp-cal-wd">Sa</span>' ) >= 50, 'Wochentag steht in jeder Zelle (mindestens 50 Samstage im Schuljahr)' );
gsh_assert_true( (bool) preg_match( '/data-d="2026-11-21"[^>]*><span class="gtp-cal-wd">Sa<\/span><span class="gtp-cal-n">21<\/span><span class="gtp-cal-es"><span class="gtp-cal-e"[^>]*>Tag der offenen/', $year ), 'Zelle zeigt Wochentag, Datum und Termintitel' );
gsh_assert_contains( $year, 'class="gtp-cal-x"', 'Tage, die es im Monat nicht gibt, bleiben leer' );
gsh_assert_contains( $year, 'data-d="2026-11-03"', 'Tag mit Termin ist ein Knopf' );
gsh_assert_true( (bool) preg_match( '/<button[^>]*gtp-cal-we[^>]*data-d="2026-11-21"/', $year ), 'Samstagstermin ist ein anklickbarer Wochenend-Tag' );
gsh_assert_true( (bool) preg_match( '/class="[^"]*gtp-cal-f[^"]*"[^>]*data-d="2026-12-24"/', $year ), 'Ferientag ist markiert' );
gsh_assert_true( (bool) preg_match( '/data-d="2026-11-04".*?<span class="gtp-cal-e"[^>]*>&lt;script&gt;alert\(1\)&lt;\/script&gt;<\/span>/s', $year ), 'Jeder Termin steht als eigene Zeile in der Zelle (maskiert)' );
gsh_assert_contains( $year, 'data-days="', 'Tagesdaten für Popover vorhanden' );
gsh_assert_true( false === strpos( $year, '<script>alert' ), 'Termintitel wird nicht als HTML ausgegeben' );
gsh_assert_contains( $year, '&lt;script&gt;', 'Termintitel steht nur maskiert in den Daten' );
gsh_assert_eq( gsh_tp_yearview( array(), $quart, 'x' ), '', 'ohne Termine keine Jahresansicht' );

// ---- Quartalsübersicht: Mo bis So, jeder Tag ein Knopf ----
$mini = gsh_tp_minimap( $index, $quart[1], '2026-08-24', 2 );
gsh_assert_contains( $mini, 'class="gtp-mini"', 'Quartalsübersicht rendert' );
gsh_assert_contains( $mini, 'gtpMiniGo(2,12)', 'Wochenknopf springt zur Schulwoche 12' );
$weeks = substr_count( $mini, 'class="gtp-mini-w"' );
gsh_assert_eq( substr_count( $mini, 'class="gtp-mini-c' ), $weeks * 7, 'sieben Tageszellen je Woche' );
gsh_assert_eq( substr_count( $mini, 'onclick="gtpMiniDay(this)"' ), $weeks * 7, 'jede Tageszelle ist wählbar' );
gsh_assert_true( (bool) preg_match( '/<button[^>]*data-d="2026-11-21"/', $mini ), 'Samstag ist wählbar' );
gsh_assert_contains( $mini, 'aria-label="Sa, 21.11., 1 Termin"', 'Tageszelle nennt Wochentag, Datum und Termine' );

// ---- Darstellung: CSS-Reset-Regeln (Regressionsschutz Raster und Quartal-Reiter) ----
$css = (string) file_get_contents( __DIR__ . '/../../plugin/assets/css/gsh-terminplan.css' );
gsh_assert_true( (bool) preg_match( '/#gtp \.gt \.ge-title\s*\{[^}]*white-space:\s*normal/', $css ), 'Raster: Termintitel bricht um statt überzulaufen' );
gsh_assert_true( (bool) preg_match( '/#gtp \.gtp-tab-now\s*\{[^}]*width:\s*auto[^}]*height:\s*auto/', $css ), 'Reiter: Altregel (6-px-Punkt) ist zurückgesetzt' );

// ---- Druck: Schuljahr kommt aus den Daten, Jahreskalender hat einen Druckweg ----
$main_src = (string) file_get_contents( __DIR__ . '/../../plugin/gsh-terminplan.php' );
gsh_assert_true( false === strpos( $main_src, 'Curriculr 2025-26' ), 'PDF-Dateititel enthalten kein festes Schuljahr' );
gsh_assert_true( false === strpos( $main_src, '2025\\u202f/\\u202f26' ), 'PDF-Kopf enthält kein festes Schuljahr' );
gsh_assert_contains( $main_src, 'function gtpPdfYear(', 'Jahreskalender-Druck vorhanden' );
gsh_assert_contains( $main_src, 'onclick="gtpPdfYear(this)"', 'Export-Menü bietet Jahreskalender drucken' );
gsh_assert_contains( $main_src, 'data-sj="', 'Schuljahr steht als Datenattribut am Container' );

gsh_test_done();
