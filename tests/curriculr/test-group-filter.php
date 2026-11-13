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


// ---------- Fix round 1 ----------
// Umlaut groups: groups ['Ärzte'] vs gruppe=' ärzte' (agenda, month, ICS).
$doc_u = array(
    'categories' => $cats,
    'events'     => array(
        $ev( 'u-yes', array( 'groups' => array( 'Ärzte' ) ) ),
        $ev( 'u-no', array( 'groups' => array( 'Aerzte' ) ) ),
    ),
);
gsh_assert_eq( $agenda_ids( gsh_tp_curriculr_agenda( $doc_u, '2026-09-21', ' ärzte' ) ), array( 'u-yes' ), 'agenda: umlaut group matches case-insensitively' );
gsh_assert_eq( $month_ids( gsh_tp_curriculr_month( $doc_u, '2026-09', '2026-09-21', ' ärzte' ) ), array( 'u-yes' ), 'month: umlaut group matches case-insensitively' );
$u_ics = gsh_tp_curriculr_build_ics( $doc_u, ' ärzte' );
gsh_assert_true( false !== strpos( $u_ics, 'UID:u-yes@' ) && false === strpos( $u_ics, 'UID:u-no@' ), 'ICS: umlaut group matches case-insensitively' );

// Feed path: URL built by feed_url_group, decoded like the router, must find the event.
function rest_url( $p ) { return 'https://wp.test/wp-json/' . $p; }
function sanitize_text_field( $str ) { return trim( strip_tags( (string) $str ) ); }
$url = gsh_tp_curriculr_feed_url_group( 'sj_2026_27', 'tok', 'Ärzte Team' );
$seg = substr( basename( $url ), 0, -4 );
$grp = gsh_tp_curriculr_feed_group_name( rawurldecode( $seg ) );
gsh_assert_eq( $grp, 'Ärzte Team', 'feed URL round trip keeps umlaut and space' );
$doc_t = array( 'categories' => $cats, 'events' => array( $ev( 't-yes', array( 'groups' => array( 'ärzte team' ) ) ) ) );
gsh_assert_true( false !== strpos( gsh_tp_curriculr_build_ics( $doc_t, $grp ), 'UID:t-yes@' ), 'feed group from URL finds event' );
gsh_assert_eq( gsh_tp_curriculr_feed_group_name( '%20' ), '', 'blank feed group name is empty' );
gsh_assert_eq( gsh_tp_curriculr_feed_group_name( '   ' ), '', 'whitespace feed group name is empty' );

// DESCRIPTION: no empty "Gruppen: " line when no usable group entry remains.
$d_ics = gsh_tp_curriculr_build_ics( array( 'categories' => $cats, 'events' => array( $ev( 'd1', array( 'groups' => array( array( 'x' ), null ) ) ) ) ) );
gsh_assert_true( false === strpos( $d_ics, 'Gruppen:' ), 'no empty Gruppen line for non-scalar group entries' );
$d_ics2 = gsh_tp_curriculr_build_ics( array( 'categories' => $cats, 'events' => array( $ev( 'd2', array( 'groups' => array( 'Eltern', array( 'x' ) ) ) ) ) ) );
gsh_assert_contains( $d_ics2, 'Gruppen: Eltern', 'Gruppen line lists scalar entries only' );

gsh_test_done();
