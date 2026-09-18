<?php
require __DIR__ . '/assert.php';
require __DIR__ . '/../../plugin/curriculr-data-layer.php';

$doc = array(
    'version'    => 6,
    'categories' => array(
        array( 'id' => 'cat-event', 'label' => 'Veranstaltung', 'color' => '#0E9F6E' ),
    ),
    'schoolyear' => array(
        'holidays' => array(
            array( 'id' => 'autumn', 'label' => 'Herbstferien', 'start' => '2026-09-24', 'end' => '2026-10-02' ),
            array( 'id' => 'old', 'label' => 'Sommerferien', 'start' => '2026-07-01', 'end' => '2026-08-10' ),
        ),
    ),
    'annotations' => array(
        array( 'id' => 'n1', 'weekStart' => '2026-09-21', 'text' => 'Elternsprechtag vorbereiten', 'order' => 0, 'updatedAt' => '' ),
    ),
    'events' => array(
        array( 'id' => 'tdot', 'title' => 'Tag der offenen Tür', 'start' => '2026-09-26', 'end' => '2026-09-26', 'allDay' => false, 'startTime' => '10:00', 'endTime' => '14:00', 'categoryId' => 'cat-event', 'location' => 'Aula', 'groups' => array( 'Eltern' ) ),
        array( 'id' => 'wknd', 'title' => 'Sportfest', 'start' => '2026-09-19', 'end' => '2026-09-20', 'allDay' => true, 'categoryId' => 'missing', 'groups' => array() ),
        array( 'id' => 'fri-sat', 'title' => 'Klassenfahrt', 'start' => '2026-09-18', 'end' => '2026-09-19', 'allDay' => true, 'categoryId' => 'cat-event', 'groups' => array() ),
        array( 'id' => 'past', 'title' => 'Vergangen', 'start' => '2026-09-10', 'end' => '2026-09-10', 'allDay' => true, 'categoryId' => 'cat-event', 'groups' => array() ),
        array( 'id' => 'running', 'title' => 'Praktikum', 'start' => '2026-09-14', 'end' => '2026-09-22', 'allDay' => true, 'categoryId' => 'cat-event', 'groups' => array( 'Eltern' ) ),
        array( 'id' => 'staff', 'title' => 'Lehrerkonferenz', 'start' => '2026-09-21', 'end' => '2026-09-21', 'allDay' => false, 'startTime' => '15:00', 'endTime' => '17:00', 'categoryId' => 'cat-event', 'groups' => array( 'Kollegium' ) ),
        array( 'id' => 'late', 'title' => 'Zu spät', 'start' => '2026-09-28', 'end' => '2026-09-28', 'allDay' => true, 'categoryId' => 'cat-event', 'groups' => array() ),
        array( 'id' => 'early', 'title' => 'Frühstück', 'start' => '2026-09-21', 'end' => '2026-09-21', 'allDay' => false, 'startTime' => '08:00', 'categoryId' => 'cat-event', 'groups' => array() ),
        array( 'id' => 'broken', 'title' => 'Kaputt', 'start' => 'nope', 'end' => 'nope', 'allDay' => true ),
        'not-an-array',
    ),
);

/* ---------- Wochenend-Hinweise (A+) ---------- */

$notes = gsh_tp_curriculr_weekend_notes( $doc );
gsh_assert_eq(
    $notes['2026-09-21'] ?? null,
    array( 'Sa 26.09.: Tag der offenen Tür, 10.00–14.00 Uhr' ),
    'Saturday event becomes a note on its Monday with time range'
);
gsh_assert_eq(
    $notes['2026-09-14'] ?? null,
    array( 'Sa 19.09.–So 20.09.: Sportfest' ),
    'all-day Sat–Sun event is one note with date range; Friday-start event is skipped'
);
gsh_assert_eq( count( $notes ), 2, 'only weekend-starting events produce notes' );
gsh_assert_eq( gsh_tp_curriculr_weekend_notes( array() ), array(), 'empty doc yields no notes' );

$display = gsh_tp_curriculr_display_notes( $doc );
gsh_assert_eq(
    $display['2026-09-21'],
    array( 'Elternsprechtag vorbereiten', 'Sa 26.09.: Tag der offenen Tür, 10.00–14.00 Uhr' ),
    'planner annotations come first, weekend notes are appended'
);
gsh_assert_eq( $display['2026-09-14'], array( 'Sa 19.09.–So 20.09.: Sportfest' ), 'weekend note creates its own week entry' );

/* ---------- Agenda für [gsh_termine] (B) ---------- */

// Freitag 18.09.: Fenster = Rest dieser Woche + nächste Woche (bis So 27.09.).
$ag = gsh_tp_curriculr_agenda( $doc, '2026-09-18', 'Eltern' );
gsh_assert_eq( $ag['from'], '2026-09-18', 'agenda starts today' );
gsh_assert_eq( $ag['to'], '2026-09-27', 'weekday: agenda ends Sunday of next week' );
gsh_assert_eq( count( $ag['strip'] ), 2, 'strip has two week rows' );
gsh_assert_eq( $ag['strip'][0][0]['date'], '2026-09-14', 'strip starts Monday of current week' );
gsh_assert_eq( $ag['strip'][0][0]['past'], true, 'Monday before today is past' );
gsh_assert_eq( $ag['strip'][0][4]['today'], true, 'Friday is today' );
gsh_assert_eq( $ag['strip'][0][5]['weekend'], true, 'Saturday flagged as weekend' );
gsh_assert_eq( $ag['strip'][1][5]['anchor'], true, 'Saturday 26.09. has events → jump link' );
gsh_assert_eq( $ag['strip'][1][5]['colors'], array( '#0E9F6E' ), 'strip cell carries category color dots' );
gsh_assert_eq( $ag['strip'][1][3]['holiday'], 'Herbstferien', 'holiday marked in strip' );
gsh_assert_eq( $ag['strip'][1][2]['anchor'], false, 'empty day is not a jump link' );

$types = array_map( function( $e ) { return $e['type'] . ':' . $e['date'] . ':' . $e['week']; }, $ag['entries'] );
gsh_assert_eq(
    $types,
    array( 'day:2026-09-18:0', 'day:2026-09-19:0', 'day:2026-09-21:1', 'holiday:2026-09-24:1', 'day:2026-09-26:1' ),
    'entries are chronological with week index; holiday band at its start date'
);

$today_events = $ag['entries'][0]['events'];
gsh_assert_eq( array_column( $today_events, 'title' ), array( 'Praktikum', 'Klassenfahrt' ), 'running events first, then all-day' );
gsh_assert_eq( $today_events[0]['since'], '2026-09-14', 'running event has since' );
gsh_assert_eq( $today_events[0]['until'], '2026-09-22', 'running event has until' );
gsh_assert_eq( $today_events[1]['since'], '', 'event starting today has no since' );
gsh_assert_eq( $today_events[1]['until'], '2026-09-19', 'multi-day event starting today has until' );
gsh_assert_eq( $today_events[0]['category'], 'Veranstaltung', 'category label resolved' );

gsh_assert_eq( array_column( $ag['entries'][2]['events'], 'title' ), array( 'Frühstück' ), 'group filter hides Kollegium-only event' );
$tdot = $ag['entries'][4]['events'][0];
gsh_assert_eq( array( $tdot['title'], $tdot['startTime'], $tdot['endTime'], $tdot['location'] ), array( 'Tag der offenen Tür', '10:00', '14:00', 'Aula' ), 'Saturday parent event with time and location' );
gsh_assert_eq( $ag['entries'][1]['events'][0]['color'], '#94A3B8', 'unknown category falls back to neutral color' );
gsh_assert_eq(
    $ag['categories'],
    array( array( 'label' => 'Veranstaltung', 'color' => '#0E9F6E' ) ),
    'legend lists categories of shown events once'
);
gsh_assert_eq( $ag['entries'][3]['label'], 'Herbstferien', 'holiday entry label' );
gsh_assert_eq( $ag['entries'][3]['end'], '2026-10-02', 'holiday entry keeps real end' );
gsh_assert_eq( $ag['next'], null, 'no next-hint needed when window has events' );

// Samstag: Wochenende → zusätzlich die übernächste Woche, heutige Termine bleiben sichtbar.
$sat = gsh_tp_curriculr_agenda( $doc, '2026-09-26', 'Eltern' );
gsh_assert_eq( $sat['to'], '2026-10-11', 'weekend: agenda extends by one week' );
gsh_assert_eq( count( $sat['strip'] ), 3, 'weekend: strip has three rows' );
gsh_assert_eq( $sat['entries'][0]['type'] . ':' . $sat['entries'][0]['date'], 'holiday:2026-09-26', 'ongoing holiday shown at window start' );
gsh_assert_eq( $sat['entries'][1]['events'][0]['title'], 'Tag der offenen Tür', "today's Saturday event stays visible" );

// Kollegium-Filter: Lehrerkonferenz sichtbar, nach Uhrzeit sortiert.
$staff = gsh_tp_curriculr_agenda( $doc, '2026-09-18', 'Kollegium' );
$mon   = array_values( array_filter( $staff['entries'], function( $e ) { return '2026-09-21' === $e['date'] && 'day' === $e['type']; } ) );
gsh_assert_eq( array_column( $mon[0]['events'], 'title' ), array( 'Frühstück', 'Lehrerkonferenz' ), 'timed events sorted by start time' );

// Leeres Fenster → Hinweis auf nächsten Termin.
$empty = gsh_tp_curriculr_agenda(
    array( 'events' => array(
        array( 'id' => 'x', 'title' => 'Tag der offenen Tür', 'start' => '2026-11-28', 'end' => '2026-11-28', 'allDay' => true, 'groups' => array( 'Eltern' ) ),
        array( 'id' => 'y', 'title' => 'Nur Kollegium', 'start' => '2026-11-01', 'end' => '2026-11-01', 'allDay' => true, 'groups' => array( 'Kollegium' ) ),
    ) ),
    '2026-09-18', 'Eltern'
);
gsh_assert_eq( $empty['entries'], array(), 'no entries in empty window' );
gsh_assert_eq( $empty['next'], array( 'title' => 'Tag der offenen Tür', 'start' => '2026-11-28' ), 'next matching event is offered' );

$invalid = gsh_tp_curriculr_agenda( $doc, 'bad', '' );
gsh_assert_eq( $invalid['entries'], array(), 'invalid today yields no entries' );

gsh_test_done();
