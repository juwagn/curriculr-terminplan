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
gsh_test_done();
