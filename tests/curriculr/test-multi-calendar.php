<?php
define( 'GSH_TP_CURRICULR_TEST', true );
define( 'GSH_TP_VERSION', '4.22.0-test' );
define( 'GSH_TP_CACHE_VERSION', 3 );

require __DIR__ . '/assert.php';

/* ---------- WordPress stubs ---------- */
$GLOBALS['options'] = array();
function get_option( $k, $d = false ) { return $GLOBALS['options'][ $k ] ?? $d; }
function update_option( $k, $v, $a = null ) { $GLOBALS['options'][ $k ] = $v; return true; }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $k ) ); }
function gsh_tp_ck( $prefix, $pid ) { return $prefix . $pid . '_v' . GSH_TP_CACHE_VERSION; }

require __DIR__ . '/../../plugin/curriculr-data-layer.php';

/* ---------- profile_for: no mapping → empty array ---------- */
$GLOBALS['options']['gsh_tp_curriculr_profile_map'] = array();
$result = gsh_tp_curriculr_profile_for( 'sj_2026_27' );
gsh_assert_eq( $result, array(), 'profile_for: no mapping returns empty array' );

/* ---------- profile_for: new array format returned as-is ---------- */
$GLOBALS['options']['gsh_tp_curriculr_profile_map'] = array(
    'sj_2026_27' => array(
        array( 'profileId' => 'curriculr_test', 'group' => null ),
        array( 'profileId' => 'curriculr_sl',   'group' => 'Schulleitung' ),
    ),
);
$result = gsh_tp_curriculr_profile_for( 'sj_2026_27' );
gsh_assert_eq( count( $result ), 2, 'profile_for: new format returns 2 mappings' );
gsh_assert_eq( $result[0]['profileId'], 'curriculr_test', 'profile_for: new format mapping 0 profileId' );
gsh_assert_eq( $result[0]['group'], null, 'profile_for: new format mapping 0 group null' );
gsh_assert_eq( $result[1]['profileId'], 'curriculr_sl', 'profile_for: new format mapping 1 profileId' );
gsh_assert_eq( $result[1]['group'], 'Schulleitung', 'profile_for: new format mapping 1 group' );
// No update_option call on already-normalised data (option unchanged).
gsh_assert_eq(
    $GLOBALS['options']['gsh_tp_curriculr_profile_map']['sj_2026_27'],
    array(
        array( 'profileId' => 'curriculr_test', 'group' => null ),
        array( 'profileId' => 'curriculr_sl',   'group' => 'Schulleitung' ),
    ),
    'profile_for: new format not re-saved'
);

/* ---------- profile_for: old string format → lazy migration ---------- */
$GLOBALS['options']['gsh_tp_curriculr_profile_map'] = array( 'sj_2026_27' => 'p_old' );
$result = gsh_tp_curriculr_profile_for( 'sj_2026_27' );
gsh_assert_eq( $result, array( array( 'profileId' => 'p_old', 'group' => null ) ), 'profile_for: old string migrated to array' );
// update_option must have persisted the new format.
$persisted = $GLOBALS['options']['gsh_tp_curriculr_profile_map']['sj_2026_27'];
gsh_assert_eq( $persisted, array( array( 'profileId' => 'p_old', 'group' => null ) ), 'profile_for: migration persisted via update_option' );

/* ---------- profile_for: invalid value → empty array ---------- */
$GLOBALS['options']['gsh_tp_curriculr_profile_map'] = array( 'sj_2026_27' => 123 );
$result = gsh_tp_curriculr_profile_for( 'sj_2026_27' );
gsh_assert_eq( $result, array(), 'profile_for: non-string non-array value returns empty array' );

gsh_test_done();
