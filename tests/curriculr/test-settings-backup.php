<?php
define( 'GSH_TP_CURRICULR_TEST', true );
define( 'GSH_TP_VERSION', '4.21.0-test' );
define( 'GSH_TP_CACHE_VERSION', 3 );

require __DIR__ . '/assert.php';

/* ---------- WordPress stubs ---------- */
$GLOBALS['options'] = array();
function get_option( $k, $d = false ) { return $GLOBALS['options'][ $k ] ?? $d; }
function update_option( $k, $v, $a = null ) { $GLOBALS['options'][ $k ] = $v; return true; }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $k ) ); }
function gsh_tp_ck( $prefix, $pid ) { return $prefix . $pid . '_v' . GSH_TP_CACHE_VERSION; }

require __DIR__ . '/../../plugin/curriculr-data-layer.php';

/* ---------- gather_settings: returns all global config keys ---------- */
$GLOBALS['options'] = array(
    'gsh_tp_profiles'              => array( array( 'id' => 'p1' ), array( 'id' => 'p2' ) ),
    'gsh_tp_ical_url'              => 'https://example.com/feed.ics',
    'gsh_tp_cache_duration'        => 7200,
    'gsh_tp_schuljahr_start'       => '2026-08-24',
    'gsh_tp_quartal_grenzen'       => "2026-08-24|2026-10-30",
    'gsh_tp_kategorie_mapping'     => 'Konferenz|konferenz',
    'gsh_tp_categories'            => array( array( 'id' => 'c1', 'label' => 'Konferenz' ) ),
    'gsh_tp_kiosk_token'           => 'tok_live',
    'gsh_tp_draft_kiosk_token'     => 'tok_draft',
    'gsh_tp_iserv_domain'          => 'schule.iserv.de',
    'gsh_tp_curriculr_origin'      => 'https://juwagn.github.io/curriculr-planner/',
    'gsh_tp_curriculr_profile_map' => array( '2026-27' => 'p1' ),
    gsh_tp_ck( 'gsh_tp_ical_', 'p1' ) => 'https://wp.test/wp-json/curriculr/v1/feed/2026-27/token1.ics',
    gsh_tp_ck( 'gsh_tp_ical_', 'p2' ) => 'https://wp.test/wp-json/curriculr/v1/feed/2025-26/token2.ics',
);

$settings = gsh_tp_curriculr_gather_settings();

gsh_assert_eq( $settings['gsh_tp_ical_url'], 'https://example.com/feed.ics', 'gather: ical_url' );
gsh_assert_eq( $settings['gsh_tp_cache_duration'], 7200, 'gather: cache_duration' );
gsh_assert_eq( $settings['gsh_tp_schuljahr_start'], '2026-08-24', 'gather: schuljahr_start' );
gsh_assert_eq( $settings['gsh_tp_curriculr_origin'], 'https://juwagn.github.io/curriculr-planner/', 'gather: curriculr_origin' );
gsh_assert_eq( $settings['gsh_tp_curriculr_profile_map'], array( '2026-27' => 'p1' ), 'gather: profile_map' );
gsh_assert_true( is_array( $settings['gsh_tp_profiles'] ), 'gather: profiles is array' );
gsh_assert_eq( count( $settings['gsh_tp_profiles'] ), 2, 'gather: profiles count' );

$p1_key = gsh_tp_ck( 'gsh_tp_ical_', 'p1' );
$p2_key = gsh_tp_ck( 'gsh_tp_ical_', 'p2' );
gsh_assert_true( array_key_exists( $p1_key, $settings ), 'gather: per-profile key p1 present' );
gsh_assert_true( array_key_exists( $p2_key, $settings ), 'gather: per-profile key p2 present' );
gsh_assert_eq( $settings[ $p1_key ], 'https://wp.test/wp-json/curriculr/v1/feed/2026-27/token1.ics', 'gather: p1 ical value' );

/* ---------- apply_settings: round-trips through gather ---------- */
$GLOBALS['options'] = array();
gsh_tp_curriculr_apply_settings( $settings );

gsh_assert_eq( get_option( 'gsh_tp_ical_url' ), 'https://example.com/feed.ics', 'apply: ical_url written' );
gsh_assert_eq( get_option( 'gsh_tp_cache_duration' ), 7200, 'apply: cache_duration written' );
gsh_assert_eq( get_option( 'gsh_tp_curriculr_origin' ), 'https://juwagn.github.io/curriculr-planner/', 'apply: curriculr_origin written' );
gsh_assert_eq( get_option( $p1_key ), 'https://wp.test/wp-json/curriculr/v1/feed/2026-27/token1.ics', 'apply: p1 per-profile ical written' );
gsh_assert_eq( get_option( $p2_key ), 'https://wp.test/wp-json/curriculr/v1/feed/2025-26/token2.ics', 'apply: p2 per-profile ical written' );

/* ---------- apply_settings: ignores unknown keys ---------- */
$GLOBALS['options'] = array();
gsh_tp_curriculr_apply_settings( array(
    'gsh_tp_ical_url'   => 'https://safe.example.com/',
    'gsh_tp_evil_key'   => 'injected',
    '__proto__'         => 'poison',
) );
gsh_assert_eq( get_option( 'gsh_tp_ical_url' ), 'https://safe.example.com/', 'apply: allowlisted key written' );
gsh_assert_eq( get_option( 'gsh_tp_evil_key', null ), null, 'apply: unknown key ignored' );

/* ---------- apply_settings: no-op on non-array ---------- */
$GLOBALS['options'] = array( 'gsh_tp_ical_url' => 'original' );
gsh_tp_curriculr_apply_settings( 'not-an-array' );
gsh_assert_eq( get_option( 'gsh_tp_ical_url' ), 'original', 'apply: non-array input is no-op' );

gsh_test_done();
