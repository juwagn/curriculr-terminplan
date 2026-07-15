<?php
define( 'GSH_TP_CURRICULR_TEST', true );
require __DIR__ . '/assert.php';
require __DIR__ . '/../../plugin/curriculr-data-layer.php';

$doc = array(
    'meta'       => array( 'name' => 'GruppenTest' ),
    'categories' => array(),
    'events'     => array(
        array( 'id' => 'g1', 'title' => 'Mit Gruppen', 'allDay' => true, 'start' => '2026-09-01', 'end' => '2026-09-01', 'groups' => array( 'Eltern', 'Sek I' ) ),
        array( 'id' => 'g2', 'title' => 'Ohne Gruppen', 'allDay' => true, 'start' => '2026-09-02', 'end' => '2026-09-02', 'groups' => array() ),
        array( 'id' => 'g3', 'title' => 'Escape', 'allDay' => true, 'start' => '2026-09-03', 'end' => '2026-09-03', 'groups' => array( 'A,B' ) ),
    ),
);

$ics = gsh_tp_curriculr_build_ics( $doc );

gsh_assert_contains( $ics, 'X-GSH-GROUPS:Eltern,Sek I', 'groups emitted comma-separated' );
gsh_assert_contains( $ics, 'X-GSH-GROUPS:A\\,B', 'comma inside group name escaped' );
// g2 (leer): kein X-GSH-GROUPS im g2-Block
preg_match( '/UID:g2@curriculr-planner.*?END:VEVENT/s', str_replace( "\r\n", "\n", $ics ), $m2 );
gsh_assert_true( isset( $m2[0] ) && strpos( $m2[0], 'X-GSH-GROUPS' ) === false, 'empty groups -> no X field' );

gsh_test_done();
