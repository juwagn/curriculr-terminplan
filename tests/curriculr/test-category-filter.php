<?php
require __DIR__ . '/assert.php';
require __DIR__ . '/../../plugin/curriculr-data-layer.php';

$doc = array(
    'categories' => array(
        array( 'id' => 'c-all', 'label' => 'Allgemein', 'slug' => 'allgemein', 'color' => '#0E9F6E' ),
        array( 'id' => 'c-el', 'label' => 'Elternarbeit', 'slug' => 'elternarbeit', 'color' => '#7C3AED' ),
        array( 'id' => 'c-kf', 'label' => 'Konferenzen', 'slug' => 'konferenzen', 'color' => '#0058A0' ),
    ),
    'events' => array(
        array( 'id' => 'a', 'title' => 'Schulfest', 'start' => '2026-09-21', 'end' => '2026-09-21', 'allDay' => true, 'categoryId' => 'c-all', 'groups' => array( 'Kollegium' ) ),
        array( 'id' => 'b', 'title' => 'Elternabend', 'start' => '2026-09-22', 'end' => '2026-09-22', 'allDay' => true, 'categoryId' => 'c-el', 'groups' => array( 'Eltern' ) ),
        array( 'id' => 'c', 'title' => 'Lehrerkonferenz', 'start' => '2026-09-23', 'end' => '2026-09-23', 'allDay' => true, 'categoryId' => 'c-kf', 'groups' => array( 'Kollegium' ) ),
        array( 'id' => 'd', 'title' => 'Info-Abend', 'start' => '2026-09-24', 'end' => '2026-09-24', 'allDay' => true, 'categoryId' => 'c-all', 'groups' => array( 'Eltern' ) ),
    ),
);

$titles = function( $ag ) {
    $out = array();
    foreach ( $ag['entries'] as $e ) {
        if ( 'day' === $e['type'] ) {
            foreach ( $e['events'] as $ev ) {
                $out[] = $ev['title'];
            }
        }
    }
    return $out;
};

gsh_assert_eq( $titles( gsh_tp_curriculr_agenda( $doc, '2026-09-21', 'Eltern' ) ), array( 'Elternabend', 'Info-Abend' ), 'group only: Eltern events, category Allgemein does not leak in' );
gsh_assert_eq(
    $titles( gsh_tp_curriculr_agenda( $doc, '2026-09-21', '' ) ),
    array( 'Schulfest', 'Elternabend', 'Lehrerkonferenz', 'Info-Abend' ),
    'no group: all events regardless of category'
);

$m = gsh_tp_curriculr_month( $doc, '2026-09', '2026-09-21', 'Eltern' );
$mt = array();
foreach ( $m['weeks'] as $row ) {
    foreach ( $row as $c ) {
        foreach ( $c['items'] as $it ) {
            $mt[] = $it['title'];
        }
    }
}
gsh_assert_eq( $mt, array( 'Elternabend', 'Info-Abend' ), 'month view filters by group only' );
gsh_assert_eq( array_column( $m['categories'], 'label' ), array( 'Elternarbeit', 'Allgemein' ), 'legend still lists categories of shown events' );

gsh_test_done();
