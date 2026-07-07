<?php
require __DIR__ . '/assert.php';
require __DIR__ . '/../../plugin/curriculr-data-layer.php';

$ok = gsh_tp_curriculr_validate_envelope( array(
    'doc'         => array( 'events' => array() ),
    'baseVersion' => 0,
) );
gsh_assert_eq( $ok['valid'], true, 'valid envelope passes' );

$no_base = gsh_tp_curriculr_validate_envelope( array( 'doc' => array( 'events' => array() ) ) );
gsh_assert_eq( $no_base['valid'], false, 'missing baseVersion fails' );
gsh_assert_contains( implode( ',', $no_base['errors'] ), 'baseVersion_missing', 'reports baseVersion_missing' );

$no_doc = gsh_tp_curriculr_validate_envelope( array( 'baseVersion' => 1 ) );
gsh_assert_eq( $no_doc['valid'], false, 'missing doc fails' );

$no_events = gsh_tp_curriculr_validate_envelope( array( 'doc' => array( 'meta' => array() ), 'baseVersion' => 1 ) );
gsh_assert_eq( $no_events['valid'], false, 'doc without events array fails' );

$not_object = gsh_tp_curriculr_validate_envelope( 'nope' );
gsh_assert_eq( $not_object['valid'], false, 'non-array body fails' );

/* ---------- Event-Tiefenvalidierung (SEC-MED-002) ---------- */
$valid_event = array( 'id' => 'e1', 'title' => 'Test', 'allDay' => true, 'start' => '2026-09-01', 'end' => '2026-09-01' );

$ok_events = gsh_tp_curriculr_validate_envelope( array(
    'doc'         => array( 'events' => array( $valid_event ) ),
    'baseVersion' => 0,
) );
gsh_assert_eq( $ok_events['valid'], true, 'valid event passes' );

$broken_end = $valid_event;
$broken_end['end'] = 'not-a-date';
$r1 = gsh_tp_curriculr_validate_envelope( array(
    'doc'         => array( 'events' => array( $broken_end ) ),
    'baseVersion' => 0,
) );
gsh_assert_eq( $r1['valid'], false, 'broken end date fails' );
gsh_assert_contains( implode( ',', $r1['errors'] ), 'invalid_event_0', 'reports invalid_event_0 for broken end' );

$crlf_start = $valid_event;
$crlf_start['start'] = "2026-09-01\r\nBEGIN:VEVENT";
$r2 = gsh_tp_curriculr_validate_envelope( array(
    'doc'         => array( 'events' => array( $crlf_start ) ),
    'baseVersion' => 0,
) );
gsh_assert_eq( $r2['valid'], false, 'CRLF in start fails' );
gsh_assert_contains( implode( ',', $r2['errors'] ), 'invalid_event_0', 'reports invalid_event_0 for CRLF start' );

$bad_time = $valid_event;
$bad_time['allDay']    = false;
$bad_time['startTime'] = '9:00';
$r3 = gsh_tp_curriculr_validate_envelope( array(
    'doc'         => array( 'events' => array( $bad_time ) ),
    'baseVersion' => 0,
) );
gsh_assert_eq( $r3['valid'], false, 'malformed startTime "9:00" fails' );
gsh_assert_contains( implode( ',', $r3['errors'] ), 'invalid_event_0', 'reports invalid_event_0 for bad startTime' );

$missing_id = $valid_event;
unset( $missing_id['id'] );
$r4 = gsh_tp_curriculr_validate_envelope( array(
    'doc'         => array( 'events' => array( $missing_id ) ),
    'baseVersion' => 0,
) );
gsh_assert_eq( $r4['valid'], false, 'missing id fails' );

$bad_allday = $valid_event;
$bad_allday['allDay'] = 'yes';
$r5 = gsh_tp_curriculr_validate_envelope( array(
    'doc'         => array( 'events' => array( $bad_allday ) ),
    'baseVersion' => 0,
) );
gsh_assert_eq( $r5['valid'], false, 'non-bool allDay fails' );

$multi = gsh_tp_curriculr_validate_envelope( array(
    'doc'         => array( 'events' => array( $valid_event, $broken_end ) ),
    'baseVersion' => 0,
) );
gsh_assert_eq( $multi['valid'], false, 'second broken event in list fails whole envelope' );
gsh_assert_contains( implode( ',', $multi['errors'] ), 'invalid_event_1', 'reports invalid_event_1 (index of the broken one)' );

gsh_test_done();
