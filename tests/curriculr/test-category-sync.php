<?php
define( 'GSH_TP_CURRICULR_TEST', true );
require __DIR__ . '/assert.php';

// Stubs: Kategorien-Speicher wie gsh-terminplan.php, minimal
$GLOBALS['wp_categories'] = array(
    array( 'id' => 'c1', 'label' => 'Alt-Label', 'color' => '#111111', 'slug' => 'ferien', 'keywords' => array( 'iserv-kw' ) ),
    array( 'id' => 'wp_only', 'label' => 'WP-Eigen', 'color' => '#222222', 'slug' => 'wp-eigen', 'keywords' => array() ),
);
function gsh_tp_get_categories() { return $GLOBALS['wp_categories']; }
function gsh_tp_save_categories( array $cats ) { $GLOBALS['wp_categories'] = $cats; return $cats; }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $k ) ); }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }

require __DIR__ . '/../../plugin/curriculr-data-layer.php';

$doc = array( 'categories' => array(
    array( 'id' => 'c1', 'label' => 'Ferien NEU', 'color' => '#ABCDEF', 'slug' => 'ferien', 'keywords' => array() ),
    array( 'id' => 'c9', 'label' => 'Neu vom Planner', 'color' => '#123456', 'slug' => 'neu', 'keywords' => array() ),
    array( 'id' => 'bad', 'label' => 'Kaputt', 'color' => 'rot', 'slug' => 'bad', 'keywords' => array() ),
) );

gsh_assert_true( gsh_tp_curriculr_sync_categories( $doc ), 'sync returns true' );

$cats = $GLOBALS['wp_categories'];
$by_id = array();
foreach ( $cats as $c ) { $by_id[ $c['id'] ] = $c; }

gsh_assert_eq( $by_id['c1']['label'], 'Ferien NEU', 'label updated from planner' );
gsh_assert_eq( $by_id['c1']['color'], '#ABCDEF', 'color updated from planner' );
gsh_assert_eq( $by_id['c1']['keywords'][0], 'iserv-kw', 'WP keywords preserved' );
gsh_assert_true( isset( $by_id['wp_only'] ), 'WP-only category never deleted' );
gsh_assert_true( isset( $by_id['c9'] ), 'new planner category created' );
gsh_assert_true( ! isset( $by_id['bad'] ), 'invalid color skipped' );
gsh_assert_true( gsh_tp_curriculr_sync_categories( array( 'meta' => array() ) ) === false, 'doc without categories -> no-op false' );

gsh_test_done();
