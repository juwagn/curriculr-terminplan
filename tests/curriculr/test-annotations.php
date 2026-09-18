<?php
require __DIR__ . '/assert.php';
require __DIR__ . '/../../plugin/curriculr-data-layer.php';

$schoolyear = array(
    'firstSchoolDay' => '2026-08-24',
    'lastSchoolDay'  => '2027-07-16',
    'holidays'       => array(
        array( 'id' => 'autumn', 'label' => 'Herbstferien', 'start' => '2026-10-19', 'end' => '2026-10-30', 'type' => 'ferien' ),
    ),
);

gsh_assert_eq(
    gsh_tp_curriculr_legacy_annotation_week_start( $schoolyear, 11 ),
    '2026-11-23',
    'legacy SW 11 skips the two complete autumn holiday weeks'
);
gsh_assert_true(
    gsh_tp_curriculr_legacy_annotation_week_start( $schoolyear, 11 ) !== '2026-11-09',
    'legacy SW 11 is not mapped two weeks early'
);

$partial = array(
    'firstSchoolDay' => '2026-08-24',
    'lastSchoolDay' => '2026-09-18',
    'holidays' => array(
        array( 'start' => '2026-08-24', 'end' => '2026-08-28' ),
        array( 'start' => '2026-09-09', 'end' => '2026-09-11' ),
    ),
);
gsh_assert_eq( gsh_tp_curriculr_legacy_annotation_week_start( $partial, 0 ), '2026-08-24', 'SW 00 remains valid during full first-week summer holiday' );
gsh_assert_eq( gsh_tp_curriculr_legacy_annotation_week_start( $partial, 2 ), '2026-09-07', 'partial holiday week remains a school week' );

$v6 = array(
    'version' => 6,
    'schoolyear' => $schoolyear,
    'annotations' => array(
        array( 'id' => 'late', 'weekStart' => '2026-11-23', 'text' => 'Zweite Notiz', 'order' => 1, 'updatedAt' => '' ),
        array( 'id' => 'first', 'weekStart' => '2026-11-23', 'text' => 'Erste Notiz', 'order' => 0, 'updatedAt' => '' ),
    ),
);
$map = gsh_tp_curriculr_annotation_map( $v6 );
gsh_assert_eq( $map['2026-11-23'], array( 'Erste Notiz', 'Zweite Notiz' ), 'v6 notes are grouped by Monday and sorted by order' );

$legacy = array(
    'version' => 5,
    'schoolyear' => $schoolyear,
    'annotations' => array( array( 'schoolweek' => 11, 'text' => 'TaTü', 'updatedAt' => '' ) ),
);
gsh_assert_eq( gsh_tp_curriculr_annotation_map( $legacy ), array( '2026-11-23' => array( 'TaTü' ) ), 'legacy document is mapped with Planner holiday semantics' );

gsh_assert_true( gsh_tp_curriculr_validate_annotation( array( 'id' => 'a', 'weekStart' => '2026-11-23', 'text' => 'Note', 'order' => 0, 'updatedAt' => '' ) ), 'v6 annotation is accepted by upload validation' );
gsh_assert_true( gsh_tp_curriculr_validate_annotation( array( 'schoolweek' => 11, 'text' => 'Legacy', 'updatedAt' => '' ) ), 'legacy annotation remains accepted by upload validation' );
gsh_assert_true( ! gsh_tp_curriculr_validate_annotation( array( 'id' => 'a', 'weekStart' => 'not-a-date', 'text' => 'Note', 'order' => 0, 'updatedAt' => '' ) ), 'invalid v6 annotation is rejected by upload validation' );

gsh_test_done();
