<?php
// Admin pages: every form field is labelled, IDs are unique, the active tab carries aria-current,
// the settings import asks for confirmation. Renders the real page functions with WP stubs.
require __DIR__ . '/assert.php';

define( 'ABSPATH', __DIR__ . '/' );
define( 'GSH_TP_CURRICULR_TEST', true );
define( 'ARRAY_A', 'ARRAY_A' );

function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_textarea( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_js( $s ) { return addslashes( (string) $s ); }
function esc_attr__( $s ) { return $s; }
function esc_html__( $s ) { return $s; }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) ); }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function wp_unslash( $s ) { return $s; }
function absint( $v ) { return abs( (int) $v ); }
function plugin_dir_path( $f ) { return rtrim( dirname( $f ), '/' ) . '/'; }
function plugin_dir_url( $f ) { return 'https://example.test/plugin/'; }
function plugin_basename( $f ) { return basename( dirname( $f ) ) . '/' . basename( $f ); }
foreach ( array( 'add_action', 'add_filter', 'add_shortcode', 'register_activation_hook', 'register_deactivation_hook', 'register_uninstall_hook', 'wp_enqueue_script', 'wp_enqueue_style', 'wp_clear_scheduled_hook', 'wp_unschedule_event', 'wp_schedule_event', 'settings_errors', 'wp_localize_script', 'wp_redirect' ) as $fn ) {
    eval( 'function ' . $fn . '() {}' );
}
function wp_next_scheduled() { return false; }
function get_transient() { return 1; }
function set_transient() { return true; }
function delete_transient() { return true; }
function wp_json_encode( $v ) { return json_encode( $v ); }
function wp_strip_all_tags( $s ) { return strip_tags( (string) $s ); }
function current_user_can() { return true; }
function current_datetime() { return new DateTimeImmutable( '2026-11-13 09:00:00' ); }
function current_time( $f ) { return date( $f ); }
function wp_timezone() { return new DateTimeZone( 'Europe/Berlin' ); }
function date_i18n( $f, $ts = null ) { return date( $f, $ts ?? time() ); }
function wp_date( $f, $ts = null ) { return date( $f, $ts ?? time() ); }
function shortcode_atts( $d, $a ) { return array_merge( $d, array_intersect_key( (array) $a, $d ) ); }
function add_query_arg( $k, $v = '' ) { return '/termine/?' . $k . '=' . $v; }
function remove_query_arg() { return '/termine/'; }
function wp_http_validate_url( $u ) { return false !== filter_var( $u, FILTER_VALIDATE_URL ); }
function wp_create_nonce() { return 'n'; }
function admin_url( $p = '' ) { return '/wp-admin/' . $p; }
function home_url() { return '/'; }
function get_bloginfo() { return 'Muster-Gesamtschule'; }
function wp_nonce_field( $a = -1, $n = '_wpnonce' ) { echo '<input type="hidden" name="' . $n . '" value="x">'; }
function wp_kses_post( $s ) { return $s; }
function selected( $a, $b = true, $echo = true ) { $r = ( (string) $a === (string) $b ) ? " selected='selected'" : ''; if ( $echo ) { echo $r; } return $r; }
function checked( $a, $b = true, $echo = true ) { $r = ( (string) $a === (string) $b ) ? " checked='checked'" : ''; if ( $echo ) { echo $r; } return $r; }
function disabled( $a, $b = true, $echo = true ) { return ''; }
function submit_button( $text = 'Speichern', $type = 'primary', $name = 'submit', $wrap = true ) {
    $b = '<input type="submit" name="' . $name . '" id="' . $name . '" class="button" value="' . esc_attr( $text ) . '">';
    echo $wrap ? '<p class="submit">' . $b . '</p>' : $b;
}
function wp_die( $m = '' ) { throw new Exception( 'wp_die ' . $m ); }
function get_current_user_id() { return 1; }
function get_post_meta() { return ''; }
function get_pages() { return array(); }
function get_permalink() { return 'https://example.test/kiosk/'; }
function site_url() { return 'https://example.test'; }
function get_site_url() { return 'https://example.test'; }
function rest_url( $p = '' ) { return 'https://example.test/wp-json/' . $p; }
function is_wp_error() { return false; }
function size_format( $b ) { return round( $b / 1024 ) . ' KB'; }
function number_format_i18n( $n, $d = 0 ) { return number_format( $n, $d, ',', '.' ); }
function wp_get_theme() { return new class { function get( $k ) { return 'Theme'; } }; }
function is_page() { return false; }

$GLOBALS['_wp_options'] = array(
    'gsh_tp_schoolyears' => array(
        array( 'key' => 'sj_2026_27', 'label' => '2026/27', 'is_active' => true, 'created' => '2026-06-01',
            'shared' => array( 'cache_duration' => 3600, 'schuljahr_start' => '2026-08-24', 'quartal_grenzen' => '' ),
            'calendars' => array(
                array( 'group' => null, 'label' => 'Gesamtkalender', 'ical_url' => 'https://example.test/a.ics', 'is_draft' => false, 'managed' => true, 'orphaned' => false, 'source' => 'planner' ),
                array( 'group' => 'Eltern', 'label' => 'Eltern', 'ical_url' => 'https://example.test/b.ics', 'is_draft' => true, 'managed' => true, 'orphaned' => false, 'source' => 'planner' ),
            ) ),
        array( 'key' => 'sj_2025_26', 'label' => '2025/26', 'is_active' => false, 'created' => '2025-06-01',
            'shared' => array( 'cache_duration' => 3600, 'schuljahr_start' => '2025-08-25', 'quartal_grenzen' => '' ),
            'calendars' => array( array( 'group' => null, 'label' => 'Gesamtkalender', 'ical_url' => '', 'is_draft' => false, 'managed' => false, 'orphaned' => false, 'source' => 'extern' ) ) ),
        // Keys with special characters; the last two sanitize to the same value.
        array( 'key' => 'sj_2027/28', 'label' => '2027/28', 'is_active' => false, 'created' => '2026-06-01', 'shared' => array(), 'calendars' => array() ),
        array( 'key' => 'sj_202728', 'label' => '2027/28 (Kopie)', 'is_active' => false, 'created' => '2026-06-01', 'shared' => array(), 'calendars' => array() ),
        array( 'key' => 'SJ 2028/29 ä', 'label' => '2028/29', 'is_active' => false, 'created' => '2026-06-01', 'shared' => array(), 'calendars' => array() ),
    ),
    'gsh_tp_school_name' => 'Muster-Gesamtschule',
    'gsh_tp_kiosk_token' => 'DEMO',
    'gsh_tp_iserv_domain' => 'https://schule.example',
);
function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['_wp_options'] ) ? $GLOBALS['_wp_options'][ $k ] : $d; }
function update_option( $k, $v ) { $GLOBALS['_wp_options'][ $k ] = $v; return true; }
$GLOBALS['wpdb'] = new class { public $prefix = 'wp_'; public $last_error = ''; function prepare( $q ) { return $q; } function get_row() { return null; } function get_var() { return ''; } function get_results() { return array(); } function esc_like( $s ) { return $s; } };

require __DIR__ . '/../../plugin/gsh-terminplan.php';

function gsh_render( $fn, $tab ) {
    $_GET = array( 'page' => 'gsh-terminplan', 'tab' => $tab );
    ob_start();
    $fn();
    return ob_get_clean();
}

function gsh_dom( $html ) {
    $d = new DOMDocument();
    libxml_use_internal_errors( true );
    $d->loadHTML( '<?xml encoding="utf-8"?><body>' . $html . '</body>' );
    libxml_clear_errors();
    return new DOMXPath( $d );
}

$pages = array(
    'Schuljahre'  => gsh_render( 'gsh_tp_settings_page', '_profile' ),
    'Kategorien'  => gsh_render( 'gsh_tp_settings_page', '_kategorien' ),
    'System'      => gsh_render( 'gsh_tp_settings_page', '_system' ),
    'Hilfe'       => gsh_render( 'gsh_tp_settings_page', '_hilfe' ),
    'Datensicherung' => gsh_render( 'gsh_tp_backup_page', '' ),
);

foreach ( $pages as $name => $html ) {
    $x = gsh_dom( $html );
    $unlabeled = array();
    foreach ( $x->query( '//input|//select|//textarea' ) as $el ) {
        $type = strtolower( $el->getAttribute( 'type' ) );
        if ( in_array( $type, array( 'hidden', 'submit', 'button', 'image', 'reset' ), true ) ) { continue; }
        $id = $el->getAttribute( 'id' );
        $ok = $el->getAttribute( 'aria-label' ) !== '' || $el->getAttribute( 'aria-labelledby' ) !== ''
            || ( $id !== '' && $x->query( '//label[@for="' . $id . '"]' )->length > 0 )
            || $x->query( 'ancestor::label', $el )->length > 0;
        if ( ! $ok ) { $unlabeled[] = $el->nodeName . '[name=' . $el->getAttribute( 'name' ) . ']'; }
    }
    gsh_assert_eq( $unlabeled, array(), "$name: every form field has a label or aria-label" );

    $seen = array(); $dups = array();
    foreach ( $x->query( '//*[@id]' ) as $el ) {
        $id = $el->getAttribute( 'id' );
        if ( 'submit' === $id ) { continue; } // WP submit_button() default id, not a label target
        if ( isset( $seen[ $id ] ) ) { $dups[] = $id; }
        $seen[ $id ] = true;
    }
    gsh_assert_eq( $dups, array(), "$name: element IDs are unique" );
}

$x = gsh_dom( $pages['Kategorien'] );
gsh_assert_eq( $x->query( '//nav[@aria-label="Plugin-Einstellungen"]' )->length, 1, 'Tab navigation has an aria-label' );
gsh_assert_eq( $x->query( '//nav//a[@aria-current="page"]' )->length, 1, 'Exactly one tab carries aria-current=page' );
gsh_assert_eq( trim( $x->query( '//nav//a[@aria-current="page"]' )->item( 0 )->textContent ), 'Kategorien', 'The active tab is the one that is shown' );

$x = gsh_dom( $pages['Schuljahre'] );
gsh_assert_eq( trim( $x->query( '//a[@aria-current="page"]' )->item( 0 )->textContent ), 'Schuljahre', 'Default tab is Schuljahre and marked current' );
$bad_ids = array();
foreach ( $x->query( '//*[starts-with(@id,"gsh-sy-")]' ) as $el ) {
    if ( ! preg_match( '/^[a-z][a-z0-9_\-]*$/', $el->getAttribute( 'id' ) ) ) { $bad_ids[] = $el->getAttribute( 'id' ); }
}
gsh_assert_eq( $bad_ids, array(), 'School-year field IDs stay valid for keys with special characters' );
gsh_assert_eq( $x->query( '//*[starts-with(@id,"gsh-sy-label-")]' )->length, 5, 'Each of the five school-year cards has its own label field ID' );
gsh_assert_eq( $x->query( '//button[contains(@class,"button-small")]' )->length, 0, 'No button-small (11 px, 24 px target) on the school-year page' );
gsh_assert_contains( $pages['Schuljahre'], '>Aktiv</span>', 'Active badge is written in sentence case' );
gsh_assert_eq( strpos( $pages['Schuljahre'], 'AKTIV' ), false, 'No capitals-only badge' );

gsh_assert_contains( $pages['Datensicherung'], "onsubmit=\"return confirm('Die aktuellen Einstellungen werden überschrieben. Import starten?')\"", 'Import asks for confirmation before it runs' );

// The origin field shows what is stored, so saving the form never erases or pins it.
$origin_field = '//input[@id="gsh_tp_curriculr_origin_sys"]';
gsh_assert_eq( gsh_dom( $pages['System'] )->query( $origin_field )->item( 0 )->getAttribute( 'value' ), '', 'Unset origin leaves the field empty' );
gsh_assert_contains( $pages['System'], 'keine (Anmeldekonfiguration unvollständig)', 'Missing planner configuration is named in plain German' );
$GLOBALS['_wp_options']['gsh_tp_curriculr_origin'] = 'https://alt.example.test';
$x = gsh_dom( gsh_render( 'gsh_tp_settings_page', '_system' ) );
gsh_assert_eq( $x->query( $origin_field )->item( 0 )->getAttribute( 'value' ), 'https://alt.example.test', 'Stored origin survives an invalid planner configuration' );

gsh_test_done();
