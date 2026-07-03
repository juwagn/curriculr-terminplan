<?php
/**
 * Tests für den AJAX-Handler gsh_tp_ajax_import_categories_from_planner().
 * Dependency-free, läuft mit plain `php`.
 */
define( 'GSH_TP_CURRICULR_TEST', true );
define( 'ARRAY_A', 'ARRAY_A' );
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

require __DIR__ . '/assert.php';

/* ---------- WP-Stubs (Standard-Set zum Laden von gsh-terminplan.php) ---------- */

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
    function esc_url_raw( $url ) { return filter_var( trim( $url ), FILTER_SANITIZE_URL ) ?: ''; }
}
if ( ! function_exists( 'current_time' ) ) {
    function current_time( $fmt ) { return date( $fmt ); }
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
if ( ! function_exists( 'register_uninstall_hook' ) ) {
    function register_uninstall_hook() {}
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

$GLOBALS['_wp_options'] = array();
if ( ! function_exists( 'get_option' ) ) {
    function get_option( $key, $default = false ) { return $GLOBALS['_wp_options'][ $key ] ?? $default; }
}
if ( ! function_exists( 'update_option' ) ) {
    function update_option( $key, $value ) { $GLOBALS['_wp_options'][ $key ] = $value; return true; }
}
if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $v ) { return is_string( $v ) ? stripslashes( $v ) : $v; }
}

/* ---------- AJAX-Stubs: Exceptions statt echtem exit() ---------- */

class Gsh_Test_Ajax_Exit extends Exception {
    public $kind;    // 'referer_fail' | 'success' | 'error'
    public $payload;
    public $status;
}

$GLOBALS['_test_valid_nonce']        = 'valid-nonce-123';
$GLOBALS['_test_can_manage_options'] = true;

if ( ! function_exists( 'check_ajax_referer' ) ) {
    function check_ajax_referer( $action, $query_arg = false, $die = true ) {
        $sent  = $_REQUEST[ $query_arg ] ?? '';
        $valid = ( $sent === $GLOBALS['_test_valid_nonce'] );
        if ( ! $valid && $die ) {
            $e = new Gsh_Test_Ajax_Exit( 'referer_fail' );
            $e->kind = 'referer_fail'; $e->payload = null; $e->status = -1;
            throw $e;
        }
        return $valid ? 1 : false;
    }
}
if ( ! function_exists( 'current_user_can' ) ) {
    function current_user_can( $cap ) { return (bool) $GLOBALS['_test_can_manage_options']; }
}
if ( ! function_exists( 'wp_send_json_success' ) ) {
    function wp_send_json_success( $data = null, $status_code = null ) {
        $e = new Gsh_Test_Ajax_Exit( 'success' );
        $e->kind = 'success'; $e->payload = $data; $e->status = $status_code ?: 200;
        throw $e;
    }
}
if ( ! function_exists( 'wp_send_json_error' ) ) {
    function wp_send_json_error( $data = null, $status_code = null ) {
        $e = new Gsh_Test_Ajax_Exit( 'error' );
        $e->kind = 'error'; $e->payload = $data; $e->status = $status_code ?: 400;
        throw $e;
    }
}

/* ---------- Fake $wpdb für gsh_tp_curriculr_repo_get() ---------- */

class Gsh_Fake_Wpdb_Repo {
    public $prefix = 'wp_';
    public $docs   = array(); // keyed by schoolyear, ARRAY_A row

    public function prepare( $query, ...$args ) {
        return array( 'sj' => $args[0] ?? '' );
    }
    public function get_row( $prepared, $out = null ) {
        $sj = is_array( $prepared ) ? ( $prepared['sj'] ?? '' ) : '';
        return $this->docs[ $sj ] ?? null;
    }
}

global $wpdb;
$wpdb = new Gsh_Fake_Wpdb_Repo();
$wpdb->docs['sj_2026_27'] = array(
    'schoolyear' => 'sj_2026_27',
    'json'       => json_encode( array(
        'categories' => array(
            array( 'id' => 'konferenz', 'label' => 'Konferenzen/DB', 'color' => '#0058A0', 'slug' => 'konferenz', 'keywords' => array() ),
            array( 'id' => 'pruefung',  'label' => 'Prüfung',        'color' => '#D9A23B', 'slug' => 'pruefung',  'keywords' => array() ),
        ),
    ) ),
    'version'    => 5,
    'stage'      => 'oeffentlich',
    'updated_at' => '2026-07-01 10:00:00',
);
$wpdb->docs['sj_leer'] = array(
    'schoolyear' => 'sj_leer',
    'json'       => json_encode( array( 'meta' => array( 'name' => 'Leeres Schuljahr' ) ) ), // kein categories-Key
    'version'    => 1,
    'stage'      => 'entwurf',
    'updated_at' => '2026-01-01 00:00:00',
);

require __DIR__ . '/../../plugin/gsh-terminplan.php';

function gsh_run_import_handler() {
    try {
        gsh_tp_ajax_import_categories_from_planner();
        return null; // sollte nie erreicht werden — Handler beendet immer via Exception
    } catch ( Gsh_Test_Ajax_Exit $e ) {
        return $e;
    }
}

/* ---------- Szenario 1: ungültige Nonce ---------- */

$_POST = $_REQUEST = array( 'nonce' => 'falsch', 'sj' => 'sj_2026_27' );
$exit = gsh_run_import_handler();
gsh_assert_true( $exit instanceof Gsh_Test_Ajax_Exit, 'Handler beendet bei ungültiger Nonce' );
gsh_assert_eq( $exit->kind, 'referer_fail', 'ungültige Nonce → referer_fail' );

/* ---------- Szenario 2: gültige Nonce, keine Berechtigung ---------- */

$GLOBALS['_test_can_manage_options'] = false;
$_POST = $_REQUEST = array( 'nonce' => 'valid-nonce-123', 'sj' => 'sj_2026_27' );
$exit = gsh_run_import_handler();
gsh_assert_eq( $exit->kind, 'error', 'fehlende Berechtigung → error' );
gsh_assert_eq( $exit->status, 403, 'fehlende Berechtigung → 403' );
gsh_assert_eq( $exit->payload['message'], 'Keine Berechtigung.', 'Fehlermeldung fehlende Berechtigung' );

/* ---------- Szenario 3: Schuljahr nicht gefunden ---------- */

$GLOBALS['_test_can_manage_options'] = true;
$_POST = $_REQUEST = array( 'nonce' => 'valid-nonce-123', 'sj' => 'sj_unbekannt' );
$exit = gsh_run_import_handler();
gsh_assert_eq( $exit->kind, 'error', 'unbekanntes Schuljahr → error' );
gsh_assert_eq( $exit->status, 404, 'unbekanntes Schuljahr → 404' );
gsh_assert_eq( $exit->payload['message'], 'Schuljahr nicht gefunden.', 'Fehlermeldung unbekanntes Schuljahr' );

/* ---------- Szenario 4: Happy Path — Kategorien vorhanden ---------- */

$_POST = $_REQUEST = array( 'nonce' => 'valid-nonce-123', 'sj' => 'sj_2026_27' );
$exit = gsh_run_import_handler();
gsh_assert_eq( $exit->kind, 'success', 'gültiges Schuljahr → success' );
gsh_assert_eq( count( $exit->payload['categories'] ), 2, 'zwei Kategorien geliefert' );
gsh_assert_eq( $exit->payload['categories'][0]['id'],    'konferenz',       'erste Kategorie-ID' );
gsh_assert_eq( $exit->payload['categories'][0]['label'], 'Konferenzen/DB', 'erste Kategorie-Label' );
gsh_assert_eq( $exit->payload['categories'][1]['id'],    'pruefung',        'zweite Kategorie-ID' );

/* ---------- Szenario 5: Dokument ohne categories-Key → leere Liste ---------- */

$_POST = $_REQUEST = array( 'nonce' => 'valid-nonce-123', 'sj' => 'sj_leer' );
$exit = gsh_run_import_handler();
gsh_assert_eq( $exit->kind, 'success', 'Dokument ohne categories → trotzdem success' );
gsh_assert_eq( $exit->payload['categories'], array(), 'leere Kategorien-Liste als Fallback' );

gsh_test_done();
echo "test-categories-import: OK\n";
