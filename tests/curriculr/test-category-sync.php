<?php
define( 'GSH_TP_CURRICULR_TEST', true );
require __DIR__ . '/assert.php';

// Stubs: Kategorien-Speicher wie gsh-terminplan.php, minimal
$GLOBALS['wp_categories'] = array(
    array( 'id' => 'c1', 'label' => 'Alt-Label', 'color' => '#111111', 'slug' => 'ferien', 'keywords' => array( 'iserv-kw' ) ),
    array( 'id' => 'wp_only', 'label' => 'WP-Eigen', 'color' => '#222222', 'slug' => 'wp-eigen', 'keywords' => array() ),
    array( 'id' => 'legacy_ferien', 'label' => 'Alt', 'color' => '#333333', 'slug' => 'herbstferien', 'keywords' => array( 'legacy-kw' ) ),
    array( 'id' => 'c3', 'label' => 'Bleibt unveraendert', 'color' => '#444444', 'slug' => 'unveraendert', 'keywords' => array( 'kw3' ) ),
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
    array( 'id' => 'c2', 'label' => 'Herbstferien NEU', 'color' => '#654321', 'slug' => 'herbstferien', 'keywords' => array() ),
    array( 'id' => 'c3', 'label' => 'Kaputte Farbe', 'color' => 'gruen', 'slug' => 'unveraendert', 'keywords' => array() ),
) );

gsh_assert_true( gsh_tp_curriculr_sync_categories( $doc ), 'sync returns true' );

$cats = $GLOBALS['wp_categories'];
$by_id = array();
foreach ( $cats as $c ) { $by_id[ $c['id'] ] = $c; }

gsh_assert_eq( $by_id['c1']['label'], 'Ferien NEU', 'label updated from planner' );
gsh_assert_eq( $by_id['c1']['color'], '#ABCDEF', 'color updated from planner' );
gsh_assert_eq( $by_id['c1']['keywords'][0], 'iserv-kw', 'WP keywords preserved for matched category' );
gsh_assert_true( ! isset( $by_id['wp_only'] ), 'category without Planner counterpart is removed — Planner is now sole source' );
gsh_assert_true( isset( $by_id['c9'] ), 'new planner category created' );
gsh_assert_eq( $by_id['c9']['keywords'], array(), 'new planner category has empty keywords' );
gsh_assert_true( ! isset( $by_id['bad'] ), 'invalid color with no existing match -> not created' );

// c3 hat eine passende Planner-Kategorie mit ungueltiger Farbe -> bestehender
// Eintrag bleibt unveraendert erhalten (nicht geloescht, nicht mit kaputten Daten ueberschrieben).
gsh_assert_true( isset( $by_id['c3'] ), 'matched category with invalid planner color is kept, not deleted' );
gsh_assert_eq( $by_id['c3']['label'], 'Bleibt unveraendert', 'matched-but-invalid planner entry does not overwrite existing label' );
gsh_assert_eq( $by_id['c3']['color'], '#444444', 'matched-but-invalid planner entry does not overwrite existing color' );
gsh_assert_eq( $by_id['c3']['keywords'], array( 'kw3' ), 'matched-but-invalid planner entry keeps existing keywords' );

// Slug-Fallback: Planner-Kategorie 'c2' hat keine id-Übereinstimmung, aber slug
// 'herbstferien' matched die bestehende WP-Kategorie 'legacy_ferien'.
$by_slug = array();
foreach ( $cats as $c ) {
    if ( ! empty( $c['slug'] ) ) {
        $by_slug[ $c['slug'] ][] = $c;
    }
}
gsh_assert_true( isset( $by_slug['herbstferien'] ) && count( $by_slug['herbstferien'] ) === 1, 'slug match updates in place, no duplicate' );
$legacy = $by_slug['herbstferien'][0];
gsh_assert_eq( $legacy['id'], 'legacy_ferien', 'slug-matched category keeps its original id' );
gsh_assert_eq( $legacy['label'], 'Herbstferien NEU', 'slug-matched category label updated from planner' );
gsh_assert_eq( $legacy['color'], '#654321', 'slug-matched category color updated from planner' );
gsh_assert_eq( $legacy['keywords'], array( 'legacy-kw' ), 'slug-matched category keeps its WP keywords' );

gsh_assert_eq( count( $cats ), 4, 'exactly the 4 planner-matched categories remain (c1, c9, c2/legacy_ferien, c3) — wp_only and bad are gone' );

gsh_assert_true( gsh_tp_curriculr_sync_categories( array( 'meta' => array() ) ) === false, 'doc without categories -> no-op false' );

gsh_test_done();
