<?php
require __DIR__ . '/assert.php';
require __DIR__ . '/../../plugin/curriculr-data-layer.php';

$doc = json_decode( file_get_contents( __DIR__ . '/fixtures/sample-doc.json' ), true );
$ics = gsh_tp_curriculr_build_ics( $doc );

gsh_assert_contains( $ics, "BEGIN:VCALENDAR\r\n", 'calendar header present' );
gsh_assert_contains( $ics, "X-WR-CALNAME:Terminplan 2026/27", 'calendar name from meta.name' );
gsh_assert_contains( $ics, 'UID:ev1@curriculr-planner', 'event 1 UID' );
gsh_assert_contains( $ics, 'SUMMARY:Gesamtkonferenz', 'event 1 summary' );
gsh_assert_contains( $ics, 'DTSTART;VALUE=DATE:20260910', 'all-day DTSTART' );
gsh_assert_contains( $ics, 'DTEND;VALUE=DATE:20260911', 'all-day DTEND is end-exclusive (+1 day)' );
gsh_assert_contains( $ics, 'CATEGORIES:Konferenz', 'category label resolved from categoryId' );
gsh_assert_contains( $ics, 'DTSTART:20261112T160000', 'timed DTSTART uses startTime' );
gsh_assert_contains( $ics, 'DTEND:20261112T190000', 'timed DTEND uses endTime' );
gsh_assert_contains( $ics, "END:VCALENDAR\r\n", 'calendar footer present' );

// ---------- Group filter tests ----------
$ics_all = gsh_tp_curriculr_build_ics( $doc, null );
gsh_assert_contains( $ics_all, 'UID:ev1@curriculr-planner', 'null filter: ev1 (Kollegium) present' );
gsh_assert_contains( $ics_all, 'UID:ev2@curriculr-planner', 'null filter: ev2 (no group) present' );
gsh_assert_contains( $ics_all, 'UID:ev3@curriculr-planner', 'null filter: ev3 (Schulleitung) present' );
gsh_assert_contains( $ics_all, 'UID:ev4@curriculr-planner', 'null filter: ev4 (Eltern) present' );

$ics_sl = gsh_tp_curriculr_build_ics( $doc, 'Schulleitung' );
gsh_assert_contains( $ics_sl, 'UID:ev2@curriculr-planner', 'Schulleitung filter: ev2 (no group) included' );
gsh_assert_contains( $ics_sl, 'UID:ev3@curriculr-planner', 'Schulleitung filter: ev3 (Schulleitung) included' );
// ev1 has groups=['Kollegium'], ev4 has groups=['Eltern'] — both must be absent
$has_ev1 = strpos( $ics_sl, 'UID:ev1@curriculr-planner' ) !== false;
$has_ev4 = strpos( $ics_sl, 'UID:ev4@curriculr-planner' ) !== false;
gsh_assert_true( ! $has_ev1, 'Schulleitung filter: ev1 (Kollegium) excluded' );
gsh_assert_true( ! $has_ev4, 'Schulleitung filter: ev4 (Eltern) excluded' );

gsh_test_done();
