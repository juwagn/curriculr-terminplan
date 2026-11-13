<?php
// Embedded blocks ([gsh_termine], [gsh_monat]): design attribute -> data-gtp-theme.
require __DIR__ . '/assert.php';

define( 'ABSPATH', __DIR__ . '/' );
define( 'GSH_TP_CURRICULR_TEST', true );
define( 'ARRAY_A', 'ARRAY_A' );

foreach ( array( 'esc_attr', 'esc_html', 'esc_url' ) as $fn ) {
    eval( 'function ' . $fn . '( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, "UTF-8" ); }' );
}
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) ); }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function wp_unslash( $s ) { return $s; }
function absint( $v ) { return abs( (int) $v ); }
function plugin_dir_path( $f ) { return rtrim( dirname( $f ), '/' ) . '/'; }
function plugin_dir_url( $f ) { return 'https://example.test/wp-content/plugins/gsh-terminplan/'; }
function plugin_basename( $f ) { return basename( dirname( $f ) ) . '/' . basename( $f ); }
foreach ( array( 'add_action', 'add_filter', 'add_shortcode', 'register_activation_hook', 'register_deactivation_hook', 'register_uninstall_hook', 'wp_enqueue_script', 'wp_clear_scheduled_hook', 'wp_unschedule_event', 'wp_schedule_event' ) as $fn ) {
    eval( 'function ' . $fn . '() {}' );
}
function wp_next_scheduled() { return false; }
function get_transient() { return false; }
function set_transient() { return true; }
function delete_transient() { return true; }
function wp_json_encode( $v ) { return json_encode( $v ); }
function wp_strip_all_tags( $s ) { return strip_tags( (string) $s ); }
function current_user_can() { return false; }
function current_datetime() { return new DateTimeImmutable( '2026-09-18 09:00:00' ); }
function shortcode_atts( $defaults, $atts ) { return array_merge( $defaults, array_intersect_key( (array) $atts, $defaults ) ); }
function add_query_arg( $k, $v = '' ) { return '/termine/?' . $k . '=' . $v; }
function remove_query_arg() { return '/termine/'; }
function wp_http_validate_url( $u ) { return false !== filter_var( $u, FILTER_VALIDATE_URL ); }

$GLOBALS['_wp_options'] = array(
    'gsh_tp_profiles'              => array( array( 'id' => 'sj_2026_27', 'is_active' => true, 'is_draft' => false, 'sj_key' => 'sj_2026_27' ) ),
    'gsh_tp_curriculr_profile_map' => array( 'sj_2026_27' => 'sj_2026_27' ),
);
function get_option( $k, $d = false ) { return $GLOBALS['_wp_options'][ $k ] ?? $d; }
function update_option( $k, $v ) { $GLOBALS['_wp_options'][ $k ] = $v; return true; }

// Minimal $wpdb: one stored doc (Muster-Gesamtschule).
$muster_doc = array(
    'categories' => array( array( 'id' => 'c1', 'label' => 'Veranstaltung', 'color' => '#0E9F6E' ) ),
    'schoolyear' => array( 'holidays' => array() ),
    'events'     => array(
        array( 'id' => 'e1', 'title' => 'Elternabend Muster-Gesamtschule', 'start' => '2026-09-21', 'end' => '2026-09-21', 'allDay' => false, 'startTime' => '18:00', 'endTime' => '20:00', 'categoryId' => 'c1', 'groups' => array( 'Eltern' ) ),
    ),
);
$GLOBALS['wpdb'] = new class( $muster_doc ) {
    public $prefix = 'wp_';
    private $doc;
    public function __construct( $doc ) { $this->doc = $doc; }
    public function prepare( $q ) { return $q; }
    public function get_row() { return array( 'json' => json_encode( $this->doc ), 'feed_token' => '', 'stage' => 'oeffentlich' ); }
};

require __DIR__ . '/../../plugin/gsh-terminplan.php';

// ---- Value mapping ----
$cases = array(
    'hell'       => 'light',
    'Hell'       => 'light',
    ''           => 'light',
    'quatsch'    => 'light',
    'auto'       => 'auto',
    ' AUTO '     => 'auto',
    'dunkel'     => 'dark',
    ' DUNKEL '   => 'dark',
    'Dunkel'     => 'dark',
    'dark'       => 'light', // English value is not documented, falls back to the default
);
foreach ( $cases as $in => $out ) {
    gsh_assert_eq( gsh_tp_embed_theme( $in ), $out, 'embed theme for "' . $in . '"' );
}
gsh_assert_eq( gsh_tp_embed_theme( null ), 'light', 'embed theme for null' );
gsh_assert_eq( gsh_tp_embed_theme( array( 'dunkel' ) ), 'light', 'embed theme for array' );

// ---- Shortcode markup ----
$default = gsh_tp_upcoming_shortcode( array() );
gsh_assert_contains( $default, '<section class="gtp-up" id="gtp-up-1" data-gtp-theme="light"', '[gsh_termine] default is light' );
gsh_assert_contains( gsh_tp_upcoming_shortcode( array( 'design' => 'dunkel' ) ), 'data-gtp-theme="dark"', '[gsh_termine] design=dunkel' );
gsh_assert_contains( gsh_tp_upcoming_shortcode( array( 'design' => 'auto' ) ), 'data-gtp-theme="auto"', '[gsh_termine] design=auto' );
gsh_assert_contains( gsh_tp_upcoming_shortcode( array( 'design' => 'quatsch' ) ), 'data-gtp-theme="light"', '[gsh_termine] design=quatsch' );

$m = gsh_tp_month_shortcode( array() );
gsh_assert_contains( $m, 'class="gtp-up gtp-mo"', '[gsh_monat] renders' );
gsh_assert_contains( $m, 'data-gtp-theme="light"', '[gsh_monat] default is light' );
gsh_assert_contains( gsh_tp_month_shortcode( array( 'design' => 'dunkel' ) ), 'data-gtp-theme="dark"', '[gsh_monat] design=dunkel' );
gsh_assert_contains( gsh_tp_month_shortcode( array( 'design' => ' Auto ' ) ), 'data-gtp-theme="auto"', '[gsh_monat] design=" Auto "' );

gsh_test_done();
