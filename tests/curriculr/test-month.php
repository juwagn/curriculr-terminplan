<?php
require __DIR__ . '/assert.php';
require __DIR__ . '/../../plugin/curriculr-data-layer.php';

$doc = array(
    'categories' => array( array( 'id' => 'va', 'label' => 'Veranstaltung', 'color' => '#0E9F6E' ) ),
    'schoolyear' => array( 'holidays' => array( array( 'label' => 'Herbstferien', 'start' => '2026-10-12', 'end' => '2026-10-24' ) ) ),
    'events'     => array(
        array( 'id' => 'tdot', 'title' => 'Tag der offenen Tür', 'start' => '2026-11-28', 'end' => '2026-11-28', 'allDay' => false, 'startTime' => '10:00', 'endTime' => '14:00', 'categoryId' => 'va', 'location' => 'Aula', 'groups' => array( 'Eltern' ) ),
        array( 'id' => 'prak', 'title' => 'Praktikum', 'start' => '2026-11-12', 'end' => '2026-11-18', 'allDay' => true, 'categoryId' => 'va', 'groups' => array() ),
        array( 'id' => 'staff', 'title' => 'Konferenz', 'start' => '2026-11-10', 'end' => '2026-11-10', 'allDay' => false, 'startTime' => '15:00', 'categoryId' => 'va', 'groups' => array( 'Kollegium' ) ),
        array( 'id' => 'early', 'title' => 'Frühdienst', 'start' => '2026-11-10', 'end' => '2026-11-10', 'allDay' => false, 'startTime' => '07:30', 'categoryId' => 'va', 'groups' => array() ),
        array( 'id' => 'oct', 'title' => 'Oktober-Termin', 'start' => '2026-10-30', 'end' => '2026-11-02', 'allDay' => true, 'categoryId' => 'va', 'groups' => array() ),
    ),
);

$m = gsh_tp_curriculr_month( $doc, '2026-11', '2026-11-10', 'Eltern' );
gsh_assert_eq( $m['ym'], '2026-11', 'requested month' );
gsh_assert_eq( $m['label'], 'November 2026', 'German month label' );
gsh_assert_eq( array( $m['prev'], $m['next'] ), array( '2026-10', '2026-12' ), 'prev/next months' );
gsh_assert_eq( $m['weeks'][0][0]['date'], '2026-10-26', 'grid starts Monday before the 1st' );
gsh_assert_eq( end( $m['weeks'] )[6]['date'], '2026-12-06', 'grid ends Sunday after the last day' );
gsh_assert_eq( count( $m['weeks'] ), 6, 'November 2026 spans six grid rows' );

$cells = array();
foreach ( $m['weeks'] as $row ) {
    foreach ( $row as $c ) {
        $cells[ $c['date'] ] = $c;
    }
}
gsh_assert_eq( $cells['2026-10-31']['out'], true, 'October days are outside the month' );
gsh_assert_eq( $cells['2026-10-31']['items'], array(), 'outside days carry no items' );
gsh_assert_eq( $cells['2026-11-28']['weekend'], true, 'Saturday flagged weekend' );
gsh_assert_eq( $cells['2026-11-28']['items'][0]['title'], 'Tag der offenen Tür', 'Saturday parent event in grid' );
gsh_assert_eq( $cells['2026-11-28']['items'][0]['kind'], 'chip', 'single-day event is a chip' );
gsh_assert_eq( $cells['2026-11-28']['items'][0]['location'], 'Aula', 'location kept' );
gsh_assert_eq( $cells['2026-11-10']['today'], true, 'today flagged' );
gsh_assert_eq( $cells['2026-11-09']['past'], true, 'past flagged' );
gsh_assert_eq( array_column( $cells['2026-11-10']['items'], 'title' ), array( 'Frühdienst' ), 'group filter hides Kollegium event' );

// Mehrtägig: Chip am Start und am Wochenanfang, dazwischen Streifen.
gsh_assert_eq( $cells['2026-11-12']['items'][0]['kind'], 'chip', 'multi-day: chip on start day' );
gsh_assert_eq( $cells['2026-11-12']['items'][0]['until'], '2026-11-18', 'multi-day chip carries until' );
gsh_assert_eq( $cells['2026-11-13']['items'][0]['kind'], 'strip', 'multi-day: strip on following day' );
gsh_assert_eq( $cells['2026-11-16']['items'][0]['kind'], 'chip', 'multi-day: chip again on Monday' );
gsh_assert_eq( $cells['2026-11-16']['items'][0]['cont'], true, 'Monday chip marked as continuation' );
gsh_assert_eq( $cells['2026-11-01']['items'][0]['kind'], 'chip', 'event from previous month: chip on the 1st' );
gsh_assert_eq( $cells['2026-11-01']['items'][0]['cont'], true, 'first-of-month chip is a continuation' );

gsh_assert_eq( $cells['2026-11-12']['holiday'], '', 'no holiday in November' );
$okt = gsh_tp_curriculr_month( $doc, '2026-10', '2026-11-10', '' );
$found = '';
foreach ( $okt['weeks'] as $row ) {
    foreach ( $row as $c ) {
        if ( '2026-10-14' === $c['date'] ) {
            $found = $c['holiday'];
        }
    }
}
gsh_assert_eq( $found, 'Herbstferien', 'holiday label on cell' );
gsh_assert_eq( $m['categories'], array( array( 'label' => 'Veranstaltung', 'color' => '#0E9F6E' ) ), 'legend from month events' );

$fallback = gsh_tp_curriculr_month( $doc, 'kaputt', '2026-11-10', '' );
gsh_assert_eq( $fallback['ym'], '2026-11', 'invalid month falls back to current month' );
$dec = gsh_tp_curriculr_month( $doc, '2026-12', '2026-11-10', '' );
gsh_assert_eq( $dec['next'], '2027-01', 'year rollover' );

gsh_test_done();
