<?php
require __DIR__ . '/assert.php';
require __DIR__ . '/../../plugin/curriculr-data-layer.php';

// --- Escaping (Reihenfolge: \  ,  ;  \n) ---
$doc = array(
    'meta'       => array( 'name' => 'A,B;C\\D' ),
    'categories' => array(),
    'events'     => array( array(
        'id'     => 'e',
        'title'  => 'Komma, Semikolon; Backslash\\',
        'allDay' => true,
        'start'  => '2026-01-05',
        'end'    => '2026-01-05',
        'notes'  => "Zeile1\nZeile2",
        'groups' => array(),
        'location' => '',
    ) ),
);
$ics = gsh_tp_curriculr_build_ics( $doc );
gsh_assert_contains( $ics, 'SUMMARY:Komma\\, Semikolon\\; Backslash\\\\', 'summary escapes comma, semicolon, backslash' );
gsh_assert_contains( $ics, 'DESCRIPTION:Zeile1\\nZeile2', 'description escapes newline' );
gsh_assert_contains( $ics, 'X-WR-CALNAME:A\\,B\\;C\\\\D', 'calendar name escaped' );

// --- Fallback-Kalendername ---
$ics2 = gsh_tp_curriculr_build_ics( array( 'events' => array() ) );
gsh_assert_contains( $ics2, 'X-WR-CALNAME:Schulterminplan', 'fallback calendar name when meta.name missing' );

// --- Multibyte-sicheres Folding ---
$long = str_repeat( 'Schulnä', 20 ); // viele Umlaute, > 75 Oktette
$doc3 = array(
    'meta'       => array( 'name' => 'T' ),
    'categories' => array(),
    'events'     => array( array(
        'id'     => 'm',
        'title'  => $long,
        'allDay' => true,
        'start'  => '2026-02-02',
        'end'    => '2026-02-02',
        'notes'  => '',
        'groups' => array(),
        'location' => '',
    ) ),
);
$ics3 = gsh_tp_curriculr_build_ics( $doc3 );
gsh_assert_true( mb_check_encoding( $ics3, 'UTF-8' ), 'folded feed is valid UTF-8 (no split multibyte char)' );
$ok_len = true;
foreach ( explode( "\r\n", $ics3 ) as $physical ) {
    if ( strlen( $physical ) > 75 ) {
        $ok_len = false;
    }
}
gsh_assert_true( $ok_len, 'all folded physical lines are <= 75 octets' );
$unfolded = str_replace( "\r\n ", '', $ics3 );
gsh_assert_contains( $unfolded, 'SUMMARY:' . $long, 'unfolded summary reconstructs original umlaut value' );

gsh_test_done();
