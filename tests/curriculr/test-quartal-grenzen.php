<?php
require __DIR__ . '/assert.php';
require __DIR__ . '/../../plugin/curriculr-data-layer.php';

/* ---------- snap_friday ---------- */

// 2026-08-19 ist ein Mittwoch → Freitag derselben Woche: 2026-08-21
gsh_assert_eq( gsh_tp_curriculr_snap_friday( '2026-08-19' ), '2026-08-21', 'Mittwoch snappt vor auf Freitag' );
// 2026-10-23 ist bereits Freitag
gsh_assert_eq( gsh_tp_curriculr_snap_friday( '2026-10-23' ), '2026-10-23', 'Freitag bleibt Freitag' );
// 2026-10-25 ist ein Sonntag → zurück auf Freitag 2026-10-23
gsh_assert_eq( gsh_tp_curriculr_snap_friday( '2026-10-25' ), '2026-10-23', 'Sonntag snappt zurueck auf Freitag' );
// Monatsgrenze: 2026-11-01 (Sonntag) → 2026-10-30
gsh_assert_eq( gsh_tp_curriculr_snap_friday( '2026-11-01' ), '2026-10-30', 'Snap ueber Monatsgrenze' );

/* ---------- monday_of_week ---------- */

gsh_assert_eq( gsh_tp_curriculr_monday_of_week( '2026-08-19' ), '2026-08-17', 'Mittwoch -> Montag der Woche' );
gsh_assert_eq( gsh_tp_curriculr_monday_of_week( '2026-08-17' ), '2026-08-17', 'Montag bleibt Montag' );

/* ---------- quartal_grenzen_from_doc: Happy Path ---------- */

$doc = array(
    'schoolyear' => array(
        'firstSchoolDay'    => '2026-08-19', // Mittwoch (Lehrer-Vorbereitungswoche)
        'lastSchoolDay'     => '2027-07-16', // Freitag
        // Grenzen: Q1-Ende, Q2-Ende, Q3-Ende (werden auf Freitag gesnappt)
        'quarterBoundaries' => array( '2026-10-23', '2027-01-29', '2027-04-23' ),
    ),
    'events'     => array(),
);

$expected = "2026-08-19|2026-10-23\n"
          . "2026-10-26|2027-01-29\n"
          . "2027-02-01|2027-04-23\n"
          . "2027-04-26|2027-07-16";
gsh_assert_eq( gsh_tp_curriculr_quartal_grenzen_from_doc( $doc ), $expected, '4 Quartale, Folge-Q startet Montag nach Grenz-Freitag' );

/* ---------- quartal_grenzen_from_doc: Snap mitten in der Woche ---------- */

$doc_midweek = $doc;
$doc_midweek['schoolyear']['quarterBoundaries'] = array( '2026-10-21', '2027-01-27', '2027-04-21' ); // Mittwochs
gsh_assert_eq(
    gsh_tp_curriculr_quartal_grenzen_from_doc( $doc_midweek ),
    $expected,
    'Mittwoch-Grenzen snappen auf dieselben Freitage'
);

/* ---------- quartal_grenzen_from_doc: Fehlerfaelle -> '' ---------- */

gsh_assert_eq( gsh_tp_curriculr_quartal_grenzen_from_doc( null ), '', 'null -> leer' );
gsh_assert_eq( gsh_tp_curriculr_quartal_grenzen_from_doc( array() ), '', 'kein schoolyear -> leer' );

$bad = $doc;
$bad['schoolyear']['quarterBoundaries'] = array( '2026-10-23', '2027-01-29' );
gsh_assert_eq( gsh_tp_curriculr_quartal_grenzen_from_doc( $bad ), '', 'nur 2 Grenzen -> leer' );

$bad = $doc;
$bad['schoolyear']['quarterBoundaries'] = array( '2026-10-23', '2027-01-29', 'kaputt' );
gsh_assert_eq( gsh_tp_curriculr_quartal_grenzen_from_doc( $bad ), '', 'kaputtes Datum -> leer' );

$bad = $doc;
unset( $bad['schoolyear']['firstSchoolDay'] );
gsh_assert_eq( gsh_tp_curriculr_quartal_grenzen_from_doc( $bad ), '', 'firstSchoolDay fehlt -> leer' );

$bad = $doc;
$bad['schoolyear']['lastSchoolDay'] = '16.07.2027';
gsh_assert_eq( gsh_tp_curriculr_quartal_grenzen_from_doc( $bad ), '', 'falsches Datumsformat -> leer' );

gsh_test_done();
