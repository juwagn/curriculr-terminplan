<?php
// P1: the group filter works on explicit groups only (trim + case-insensitive).
require __DIR__ . '/assert.php';
require __DIR__ . '/../../plugin/curriculr-data-layer.php';

$cats = array(
    array( 'id' => 'c-all', 'label' => 'Allgemein', 'slug' => 'allgemein', 'color' => '#0E9F6E' ),
    array( 'id' => 'c-el', 'label' => 'Elternarbeit', 'slug' => 'elternarbeit', 'color' => '#7C3AED' ),
);
$ev = function( $id, $extra, $cat = 'c-el' ) {
    return array_merge( array( 'id' => $id, 'title' => $id, 'start' => '2026-09-22', 'end' => '2026-09-22', 'allDay' => true, 'categoryId' => $cat ), $extra );
};
$doc = array(
    'categories' => $cats,
    'events'     => array(
        $ev( 'g-exact', array( 'groups' => array( 'Eltern' ) ) ),
        $ev( 'g-lower', array( 'groups' => array( 'eltern ' ) ) ),
        $ev( 'g-multi', array( 'groups' => array( 'Kollegium', 'Eltern' ) ) ),
        $ev( 'n-empty', array( 'groups' => array() ) ),
        $ev( 'n-missing', array() ),
        $ev( 'n-null', array( 'groups' => null ) ),
        $ev( 'n-string', array( 'groups' => 'Eltern' ) ),
        $ev( 'n-allg', array( 'groups' => array( 'Allgemein' ) ) ),
        $ev( 'n-koll', array( 'groups' => array( 'Kollegium' ) ) ),
        $ev( 'n-nonstr', array( 'groups' => array( 5, array( 'Eltern' ) ) ) ),
        $ev( 'n-cat-allg-empty', array( 'groups' => array() ), 'c-all' ),
        $ev( 'n-cat-allg-koll', array( 'groups' => array( 'Kollegium' ) ), 'c-all' ),
    ),
);
$yes = array( 'g-exact', 'g-lower', 'g-multi' );

$agenda_ids = function( $ag ) {
    $out = array();
    foreach ( $ag['entries'] as $e ) {
        if ( 'day' === $e['type'] ) {
            foreach ( $e['events'] as $x ) {
                $out[] = $x['title'];
            }
        }
    }
    sort( $out );
    return $out;
};
$month_ids = function( $m ) {
    $out = array();
    foreach ( $m['weeks'] as $row ) {
        foreach ( $row as $c ) {
            foreach ( $c['items'] as $it ) {
                if ( 'chip' === $it['kind'] ) {
                    $out[] = $it['title'];
                }
            }
        }
    }
    $out = array_values( array_unique( $out ) );
    sort( $out );
    return $out;
};
$exp = $yes;
sort( $exp );

foreach ( array( 'Eltern', ' eltern', 'ELTERN ' ) as $g ) {
    gsh_assert_eq( $agenda_ids( gsh_tp_curriculr_agenda( $doc, '2026-09-21', $g ) ), $exp, "agenda gruppe='$g' shows only explicit group events" );
    gsh_assert_eq( $month_ids( gsh_tp_curriculr_month( $doc, '2026-09', '2026-09-21', $g ) ), $exp, "month gruppe='$g' shows only explicit group events" );
}

// Legacy kategorien=/auch_kategorien= (5th arg in old API) must not change the result.
$legacy = array( 'only' => array( 'x' ), 'also' => array( 'allgemein' ) );
gsh_assert_eq( $agenda_ids( gsh_tp_curriculr_agenda( $doc, '2026-09-21', 'Eltern', 2, $legacy ) ), $exp, 'agenda: legacy category args are ignored' );
gsh_assert_eq( $month_ids( gsh_tp_curriculr_month( $doc, '2026-09', '2026-09-21', 'Eltern', $legacy ) ), $exp, 'month: legacy category args are ignored' );

// Empty group = everything (no restriction).
gsh_assert_eq( count( $agenda_ids( gsh_tp_curriculr_agenda( $doc, '2026-09-21', '' ) ) ), count( $doc['events'] ), "agenda gruppe='' shows all events" );
gsh_assert_eq( count( $month_ids( gsh_tp_curriculr_month( $doc, '2026-09', '2026-09-21', '' ) ) ), count( $doc['events'] ), "month gruppe='' shows all events" );

// ICS group feed follows the same rule; full feed unchanged; no fatal for string groups.
$ics_ids = function( $ics ) {
    preg_match_all( '/^UID:(.+)$/m', $ics, $m );
    $out = array_map( function( $u ) { return trim( $u ); }, $m[1] );
    return $out;
};
$full = gsh_tp_curriculr_build_ics( $doc );
foreach ( $doc['events'] as $e ) {
    gsh_assert_true( false !== strpos( $full, $e['id'] ), 'full feed contains ' . $e['id'] );
}
$g_ics = gsh_tp_curriculr_build_ics( $doc, ' eltern ' );
foreach ( $doc['events'] as $e ) {
    $has = false !== strpos( $g_ics, $e['id'] );
    gsh_assert_eq( $has, in_array( $e['id'], $yes, true ), 'group ICS ' . $e['id'] . ( $has ? ' present' : ' absent' ) );
}

gsh_test_done();
