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

gsh_assert_eq( gsh_tp_curriculr_parse_list( ' Allgemein, elternarbeit ,,' ), array( 'allgemein', 'elternarbeit' ), 'list parsing trims, lowercases, drops empties' );

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

gsh_assert_eq( $titles( gsh_tp_curriculr_agenda( $doc, '2026-09-21', 'Eltern' ) ), array( 'Elternabend', 'Info-Abend' ), 'group only: Eltern events' );
gsh_assert_eq(
    $titles( gsh_tp_curriculr_agenda( $doc, '2026-09-21', 'Eltern', 2, array( 'also' => array( 'allgemein' ) ) ) ),
    array( 'Schulfest', 'Elternabend', 'Info-Abend' ),
    'auch_kategorien adds Allgemein events outside the group'
);
gsh_assert_eq(
    $titles( gsh_tp_curriculr_agenda( $doc, '2026-09-21', 'Eltern', 2, array( 'only' => array( 'elternarbeit' ) ) ) ),
    array( 'Elternabend' ),
    'kategorien restricts to listed categories'
);
gsh_assert_eq(
    $titles( gsh_tp_curriculr_agenda( $doc, '2026-09-21', '', 2, array( 'only' => array( 'konferenzen' ) ) ) ),
    array( 'Lehrerkonferenz' ),
    'kategorien by slug without group'
);
gsh_assert_eq(
    $titles( gsh_tp_curriculr_agenda( $doc, '2026-09-21', 'Eltern', 2, array( 'only' => array( 'allgemein' ), 'also' => array( 'allgemein' ) ) ) ),
    array( 'Schulfest', 'Info-Abend' ),
    'only + also combined'
);

$m = gsh_tp_curriculr_month( $doc, '2026-09', '2026-09-21', 'Eltern', array( 'also' => array( 'Allgemein' ) ) );
$mt = array();
foreach ( $m['weeks'] as $row ) {
    foreach ( $row as $c ) {
        foreach ( $c['items'] as $it ) {
            $mt[] = $it['title'];
        }
    }
}
gsh_assert_eq( $mt, array( 'Schulfest', 'Elternabend', 'Info-Abend' ), 'month view honours category filter (case-insensitive)' );

gsh_test_done();
