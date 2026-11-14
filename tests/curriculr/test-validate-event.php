<?php
/**
 * Tests fuer P4: Eingabevalidierung beim Speichern (PUT /doc/{sj}).
 *
 * Deckt is_iso_date (Kalendergueltigkeit, kein angehaengtes Newline),
 * validate_event (Typen, Zeitbereich, end >= start), die PUT-Ebene
 * (400 vor dem Schreiben) und DB-Fehler (kein 409) ab.
 * Dependency-free, plain `php`. Stub-Strategie wie test-revisions.php.
 */
define( 'GSH_TP_CURRICULR_TEST', true );
ini_set( 'error_log', '/dev/null' ); // erwartete DB-Fehler-Logs nicht ausgeben
define( 'GSH_TP_VERSION', '4.9.0-test' );
define( 'GSH_TP_CACHE_VERSION', 3 );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'FS_CHMOD_FILE', 0644 );

require __DIR__ . '/assert.php';

/* ---------- Erweiterte WordPress-Stubs ---------- */
class Gsh_Fake_Wpdb_Rev {
    public $last_error  = "";
    public $fail_update = false;
    public $fail_insert = false;
    public $fail_rev_insert = false;
    public $prefix    = 'wp_';
    public $docs      = array();
    public $revs      = array();
    public $next_id   = 1;
    public $insert_id = 0;

    public function get_charset_collate() { return ''; }
    public function prepare( $q, ...$args ) {
        // Conflict lookup: prepare(sql, sj_string, version_int) -> __rev__:sj:ver
        if ( count( $args ) === 2 && is_string( $args[0] ) && ( is_int( $args[1] ) || ctype_digit( (string) $args[1] ) ) ) {
            return '__rev__:' . (string) $args[0] . ':' . (int) $args[1];
        }
        return isset( $args[0] ) ? $args[0] : $q;
    }
    public function get_row( $key, $out = null ) {
        if ( is_string( $key ) && strncmp( $key, '__rev__:', 8 ) === 0 ) {
            $parts = explode( ':', $key, 3 );
            $sj    = $parts[1] ?? '';
            $ver   = isset( $parts[2] ) ? (int) $parts[2] : -1;
            foreach ( $this->revs as $rev ) {
                if ( $rev['schoolyear'] === $sj && (int) $rev['version'] === $ver ) {
                    return (object) $rev;
                }
            }
            return null;
        }
        if ( is_int( $key ) || ctype_digit( (string) $key ) ) {
            return $this->revs[ (int) $key ] ?? null;
        }
        return $this->docs[ $key ] ?? null;
    }
    public function get_results( $query, $out = null ) {
        return array_values( $this->revs );
    }
    public function insert( $table, $data ) {
        if ( strpos( $table, 'revisions' ) !== false ) {
            if ( $this->fail_rev_insert ) {
                $this->last_error = 'revision insert failed (secret db detail)';
                return false;
            }
            $data['id']      = $this->next_id;
            $this->insert_id = $this->next_id;
            $this->revs[ $this->next_id ] = $data;
            $this->next_id++;
            return 1;
        }
        if ( $this->fail_insert ) {
            $this->last_error = "Data too long for column 'schoolyear' (secret db detail)";
            return false;
        }
        // Simuliert PRIMARY KEY (schoolyear): Duplicate-Insert schlägt fehl (Race).
        if ( isset( $this->docs[ $data['schoolyear'] ] ) ) {
            return false;
        }
        $this->docs[ $data['schoolyear'] ] = $data;
        return 1;
    }
    public function update( $table, $data, $where ) {
        if ( $this->fail_update ) {
            $this->last_error = "Data too long for column 'schoolyear' (secret db detail)";
            return false;
        }
        $key = $where['schoolyear'] ?? null;
        if ( null === $key || ! isset( $this->docs[ $key ] ) ) {
            return false;
        }
        // Atomare Bedingung: WHERE version = <base> muss zur gespeicherten Version passen.
        if ( array_key_exists( 'version', $where ) && (int) $this->docs[ $key ]['version'] !== (int) $where['version'] ) {
            return 0;
        }
        $this->docs[ $key ] = array_merge( $this->docs[ $key ], $data );
        return 1;
    }
    public function query( $sql ) {
        if ( count( $this->revs ) > 50 ) {
            $keys = array_keys( $this->revs );
            $keep = array_slice( $keys, -50 );
            foreach ( $keys as $k ) {
                if ( ! in_array( $k, $keep, true ) ) {
                    unset( $this->revs[ $k ] );
                }
            }
        }
        return true;
    }
}
$GLOBALS['wpdb']      = new Gsh_Fake_Wpdb_Rev();
$GLOBALS['options']   = array();

/* ---------- Guard claims stub (simulates validated app-token) ---------- */
$GLOBALS['gsh_tp_curriculr_current_claims'] = null;
function gsh_tp_curriculr_guard_current_claims() {
    return $GLOBALS['gsh_tp_curriculr_current_claims'];
}
$GLOBALS['refreshed'] = array();
function get_option( $k, $d = false ) { return $GLOBALS['options'][ $k ] ?? $d; }
function update_option( $k, $v, $a = null ) { $GLOBALS['options'][ $k ] = $v; return true; }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $k ) ); }
function wp_generate_password( $l = 12, $s = true, $e = true ) { return substr( str_repeat( 'aB3xY9Qz', 8 ), 0, $l ); }
function current_time( $t ) { return '2026-06-05 02:00:00'; }
function get_current_user_id() { return 1; }
function wp_json_encode( $d ) { return ! empty( $GLOBALS['gsh_force_json_fail'] ) ? false : json_encode( $d ); }
function current_user_can( $c ) { return true; }
function rest_url( $p ) { return 'https://wp.test/wp-json/' . $p; }
function gsh_tp_ck( $prefix, $pid ) { return $prefix . $pid . '_v' . GSH_TP_CACHE_VERSION; }
function gsh_tp_get_profiles() { return $GLOBALS['profiles'] ?? array(); }
function gsh_tp_active_profile_id() { return 'p1'; }
function gsh_tp_do_refresh( $pid ) { $GLOBALS['refreshed'][] = $pid; }
$GLOBALS['schoolyears'] = array();
function gsh_tp_get_schoolyears() { return $GLOBALS['schoolyears']; }
function gsh_tp_save_schoolyears( $schoolyears ) { $GLOBALS['schoolyears'] = $schoolyears; }
function gsh_tp_calendar_id( $sj_key, $group ) {
    $base = sanitize_key( $sj_key );
    return ( null === $group || '' === $group ) ? $base : $base . '__' . sanitize_key( $group );
}
function sanitize_text_field( $str ) { return trim( strip_tags( (string) $str ) ); }
function gsh_tp_cal_is_extern( $cal ) { return 'extern' === ( $cal['source'] ?? 'planner' ); }
function delete_transient( $k ) { unset( $GLOBALS['options'][ $k ] ); }
function set_transient( $k, $v, $ttl = 0 ) { $GLOBALS['options'][ $k ] = $v; }
function wp_unslash( $v ) { return $v; }
function wp_verify_nonce( $n, $a ) { return true; }
function esc_html( $s ) { return htmlspecialchars( (string) $s ); }
function wp_get_current_user() { return (object) array( 'display_name' => 'Muster' ); }
function add_action() {}
function add_filter() {}
function register_activation_hook() {}
function register_deactivation_hook() {}
function wp_next_scheduled() { return false; }
function wp_schedule_event() {}
class WP_REST_Response {
    public $data; public $status;
    public function __construct( $d, $s = 200 ) { $this->data = $d; $this->status = $s; }
}
class Gsh_Fake_Req_Put implements ArrayAccess {
    public $body; public $params;
    public function __construct( $body, $params ) { $this->body = $body; $this->params = $params; }
    public function get_json_params() { return $this->body; }
    public function get_method() { return 'PUT'; }
    public function get_route() { return '/curriculr/v1/doc/sj_2026_27'; }
    public function offsetExists( $o ): bool { return isset( $this->params[ $o ] ); }
    public function offsetGet( $o ): mixed { return $this->params[ $o ] ?? null; }
    public function offsetSet( $o, $v ): void { $this->params[ $o ] = $v; }
    public function offsetUnset( $o ): void { unset( $this->params[ $o ] ); }
}

require __DIR__ . '/../../plugin/curriculr-data-layer.php';

$fresh_wpdb = function () { $GLOBALS['wpdb'] = new Gsh_Fake_Wpdb_Rev(); return $GLOBALS['wpdb']; };

/* ---------- PHP-01: is_iso_date ---------- */
foreach ( array( '2026-01-01', '2026-12-31', '2028-02-29' ) as $d ) {
    gsh_assert_eq( gsh_tp_curriculr_is_iso_date( $d ), true, "is_iso_date akzeptiert $d" );
}
foreach ( array( '2026-13-45', '2026-02-30', '2027-02-29', '2026-00-10', "2026-01-01\n", "2026-01-01\r\n", '2026-1-1', '', null, 20260101 ) as $d ) {
    gsh_assert_eq( gsh_tp_curriculr_is_iso_date( $d ), false, 'is_iso_date lehnt ab: ' . var_export( $d, true ) );
}

/* ---------- PHP-02: validate_event ---------- */
$base = array( 'id' => 'e1', 'title' => 'Muster-Termin', 'allDay' => true, 'start' => '2026-09-01', 'end' => '2026-09-01' );
$with = function ( array $over, array $drop = array() ) use ( $base ) {
    $e = array_merge( $base, $over );
    foreach ( $drop as $k ) { unset( $e[ $k ] ); }
    return $e;
};
$timed = array( 'allDay' => false, 'startTime' => '09:00', 'endTime' => '10:30' );

$accept = array(
    'ganztaegig'                      => $base,
    'mit Zeiten'                      => $with( $timed ),
    'Endzeit vor Startzeit (Zod erlaubt es)' => $with( array( 'allDay' => false, 'startTime' => '10:00', 'endTime' => '09:00' ) ),
    'Endzeit == Startzeit'            => $with( array( 'allDay' => false, 'startTime' => '09:00', 'endTime' => '09:00' ) ),
    '00:00 und 23:59'                 => $with( array( 'allDay' => false, 'startTime' => '00:00', 'endTime' => '23:59' ) ),
    'mehrtaegig (Zeiten uebergreifend)' => $with( array( 'end' => '2026-09-03', 'allDay' => false, 'startTime' => '14:00', 'endTime' => '08:00' ) ),
    'mehrtaegig ganztaegig'           => $with( array( 'end' => '2026-09-03' ) ),
    'ohne groups'                     => $base,
    'groups []'                       => $with( array( 'groups' => array() ) ),
    'groups Strings'                  => $with( array( 'groups' => array( 'Kollegium', '5a' ) ) ),
    'categoryId null'                 => $with( array( 'categoryId' => null ) ),
    'categoryId String'               => $with( array( 'categoryId' => 'konferenz' ) ),
    'notes/location Strings'          => $with( array( 'notes' => 'n', 'location' => 'Aula' ) ),
    'notes/location leer'             => $with( array( 'notes' => '', 'location' => '' ) ),
    'Zeiten null bei allDay'          => $with( array( 'startTime' => null, 'endTime' => null ) ),
    'nur startTime'                   => $with( array( 'allDay' => false, 'startTime' => '09:00' ) ),
);
foreach ( $accept as $name => $e ) {
    gsh_assert_eq( gsh_tp_curriculr_validate_event( $e ), true, "validate_event akzeptiert: $name" );
}

$reject = array(
    'start 2026-13-45'      => $with( array( 'start' => '2026-13-45' ) ),
    'end 2026-02-30'        => $with( array( 'end' => '2026-02-30' ) ),
    'start mit Newline'     => $with( array( 'start' => "2026-01-01\n" ) ),
    'groups String'         => $with( array( 'groups' => 'x' ) ),
    'groups null'           => $with( array( 'groups' => null ) ),
    'groups [1,2]'          => $with( array( 'groups' => array( 1, 2 ) ) ),
    'groups verschachtelt'  => $with( array( 'groups' => array( array( 'x' ) ) ) ),
    'categoryId Array'      => $with( array( 'categoryId' => array( 'x' ) ) ),
    'categoryId Zahl'       => $with( array( 'categoryId' => 5 ) ),
    'notes Array'           => $with( array( 'notes' => array( 'x' ) ) ),
    'notes null'            => $with( array( 'notes' => null ) ),
    'location Array'        => $with( array( 'location' => array( 'x' ) ) ),
    'Zeit 25:99'            => $with( array( 'allDay' => false, 'startTime' => '25:99', 'endTime' => '26:00' ) ),
    'endTime 24:00'         => $with( array( 'allDay' => false, 'startTime' => '09:00', 'endTime' => '24:00' ) ),
    'startTime 10:60'       => $with( array( 'allDay' => false, 'startTime' => '10:60', 'endTime' => '11:00' ) ),
    'Zeit mit Newline'      => $with( array( 'allDay' => false, 'startTime' => "10:00\n", 'endTime' => '11:00' ) ),
    'endTime mit Newline'   => $with( array( 'allDay' => false, 'startTime' => '10:00', 'endTime' => "11:00\n" ) ),
    'end vor start'         => $with( array( 'start' => '2026-09-02', 'end' => '2026-09-01' ) ),
);
foreach ( $reject as $name => $e ) {
    gsh_assert_eq( gsh_tp_curriculr_validate_event( $e ), false, "validate_event lehnt ab: $name" );
}

/* Lesen: Altdokumente mit kaputten Terminen duerfen den Feed nicht brechen. */
$legacy = array(
    'meta'   => array( 'name' => 'Alt' ),
    'events' => array(
        array( 'id' => 'x1', 'title' => 'Kaputt', 'allDay' => true, 'start' => '2026-13-45', 'end' => '2026-13-45' ),
        array( 'id' => 'x2', 'title' => 'Newline', 'allDay' => true, 'start' => "2026-01-01\n", 'end' => '2026-01-01' ),
        array( 'id' => 'x3', 'title' => 'Gut', 'allDay' => true, 'start' => '2026-09-01', 'end' => '2026-09-01', 'groups' => 'x', 'notes' => array( 'y' ) ),
    ),
);
$ics = '';
try { $ics = gsh_tp_curriculr_build_ics( $legacy ); } catch ( \Throwable $t ) { $ics = 'EXC:' . $t->getMessage(); }
gsh_assert_contains( $ics, 'BEGIN:VCALENDAR', 'build_ics mit Altdaten wirft nicht' );
gsh_assert_contains( $ics, 'SUMMARY:Gut', 'build_ics mit Altdaten behaelt den gueltigen Termin' );
gsh_assert_eq( strpos( $ics, 'Array' ) === false, true, 'build_ics mit Altdaten: "Array" kommt nirgends vor' );
gsh_assert_eq( strpos( $ics, 'SUMMARY:Kaputt' ) === false && strpos( $ics, 'SUMMARY:Newline' ) === false, true, 'build_ics ueberspringt Termine mit ungueltigem Datum' );

/* Lesen: ungueltige Zeiten in Altdaten -> Termin ganztaegig, kein ungueltiges DTSTART, kein rohes LF. */
foreach ( array( '25:99', "10:00\n", array( '10:00' ), 1000, '9:00' ) as $bad_t ) {
    $lt  = array( 'id' => 'lt', 'title' => 'AltZeit', 'allDay' => false, 'start' => '2026-09-01', 'end' => '2026-09-01', 'startTime' => $bad_t, 'endTime' => '11:00' );
    $ics = gsh_tp_curriculr_build_ics( array( 'meta' => array( 'name' => 'x' ), 'events' => array( $lt ) ) );
    $lbl = 'Altdaten startTime ' . json_encode( $bad_t );
    gsh_assert_contains( $ics, 'SUMMARY:AltZeit', "$lbl: Termin bleibt erhalten" );
    gsh_assert_contains( $ics, 'DTSTART;VALUE=DATE:20260901', "$lbl: als ganztaegig ausgegeben" );
    gsh_assert_contains( $ics, 'DTEND;VALUE=DATE:20260902', "$lbl: ganztaegiges DTEND" );
    gsh_assert_eq( substr_count( $ics, "\n" ) === substr_count( $ics, "\r\n" ), true, "$lbl: kein rohes LF in der ICS-Ausgabe" );
    gsh_assert_eq( strpos( $ics, 'DTSTART:' ) === false, true, "$lbl: kein DTSTART mit Uhrzeit" );
}
$lt = array( 'id' => 'lt2', 'title' => 'AltEnd', 'allDay' => false, 'start' => '2026-09-01', 'end' => '2026-09-01', 'startTime' => '09:00', 'endTime' => "11:00\n" );
$ics = gsh_tp_curriculr_build_ics( array( 'meta' => array( 'name' => 'x' ), 'events' => array( $lt ) ) );
gsh_assert_contains( $ics, 'DTSTART;VALUE=DATE:20260901', 'Altdaten endTime mit Newline: ganztaegig' );

/* Endzeit vor Startzeit am selben Tag: gueltige ICS-Ausgabe, DTEND nie vor DTSTART. */
$rev = array( 'id' => 'r1', 'title' => 'Rueck', 'allDay' => false, 'start' => '2026-09-01', 'end' => '2026-09-01', 'startTime' => '10:00', 'endTime' => '09:00' );
$ics = gsh_tp_curriculr_build_ics( array( 'meta' => array( 'name' => 'x' ), 'events' => array( $rev ) ) );
gsh_assert_contains( $ics, 'DTSTART:20260901T100000', 'Endzeit<Startzeit: DTSTART unveraendert' );
gsh_assert_contains( $ics, 'DTEND:20260901T100000', 'Endzeit<Startzeit: DTEND auf DTSTART begrenzt' );
$rev['start'] = '2026-09-01'; $rev['end'] = '2026-09-02';
$ics = gsh_tp_curriculr_build_ics( array( 'meta' => array( 'name' => 'x' ), 'events' => array( $rev ) ) );
gsh_assert_contains( $ics, 'DTEND:20260902T090000', 'mehrtaegig: Endzeit bleibt unveraendert' );

/* ---------- Nachzuegler: leere Gruppen nicht in DESCRIPTION ---------- */
$ge  = array( 'id' => 'g1', 'title' => 'T', 'allDay' => true, 'start' => '2026-09-01', 'end' => '2026-09-01', 'groups' => array( '', ' ' ) );
$ics = gsh_tp_curriculr_build_ics( array( 'meta' => array( 'name' => 'x' ), 'events' => array( $ge ) ) );
gsh_assert_eq( strpos( $ics, 'Gruppen:' ) === false, true, 'leere Gruppen erzeugen keine "Gruppen:"-Zeile' );
$ge['groups'] = array( ' 5a ', '', 'Kollegium' );
$ics = gsh_tp_curriculr_build_ics( array( 'meta' => array( 'name' => 'x' ), 'events' => array( $ge ) ) );
gsh_assert_contains( $ics, 'DESCRIPTION:Gruppen: 5a\, Kollegium', 'DESCRIPTION nutzt bereinigte Gruppenliste' );

/* ---------- PUT-Ebene: ungueltiges Doc -> 400, DB unveraendert ---------- */
$wpdb = $fresh_wpdb();
$good = json_decode( file_get_contents( __DIR__ . '/fixtures/sample-doc.json' ), true );
$r0   = gsh_tp_curriculr_repo_put( 'sj_2026_27', $good, 0 );
gsh_assert_eq( $r0['status'], 'ok', 'Setup: Version 1 gespeichert' );
$docs_before = $wpdb->docs;
$revs_before = count( $wpdb->revs );

$bad_doc = $good;
$bad_doc['events'][] = array( 'id' => 'bad', 'title' => 'Kaputt', 'allDay' => true, 'start' => '2026-13-45', 'end' => '2026-13-45' );
$put = function ( $doc, $base ) {
    return gsh_tp_curriculr_rest_put( new Gsh_Fake_Req_Put( array( 'doc' => $doc, 'baseVersion' => $base ), array( 'sj' => 'sj_2026_27' ) ) );
};
foreach ( array(
    'ungueltiges Datum'      => array( 'start' => '2026-13-45', 'end' => '2026-13-45' ),
    'Datum mit Newline'      => array( 'start' => "2026-01-01\n", 'end' => '2026-01-01' ),
    'groups String'          => array( 'groups' => 'x' ),
    'categoryId Array'       => array( 'categoryId' => array( 'x' ) ),
    'notes Array'            => array( 'notes' => array( 'x' ) ),
    'Zeit 25:99'             => array( 'allDay' => false, 'startTime' => '25:99', 'endTime' => '26:00' ),
) as $name => $over ) {
    $d = $good;
    $d['events'][] = array_merge( array( 'id' => 'bad', 'title' => 'Kaputt', 'allDay' => true, 'start' => '2026-09-01', 'end' => '2026-09-01' ), $over );
    $resp = $put( $d, 1 );
    gsh_assert_eq( $resp->status, 400, "PUT mit $name -> 400" );
    gsh_assert_eq( $wpdb->docs, $docs_before, "PUT mit $name: Version/JSON in DB unveraendert" );
    gsh_assert_eq( count( $wpdb->revs ), $revs_before, "PUT mit $name: keine Revision geschrieben" );
}
$ok = $put( $good, 1 );
gsh_assert_eq( $ok->status, 200, 'PUT mit gueltigem Doc -> 200' );
gsh_assert_eq( $ok->data['version'], 2, 'gueltiger PUT zaehlt Version hoch' );

/* ---------- PHP-06: DB-Fehler ist kein 409 ---------- */
$wpdb = $fresh_wpdb();
gsh_tp_curriculr_repo_put( 'sj_2026_27', $good, 0 );
$docs_pre = $wpdb->docs;
$wpdb->fail_update = true;
$resp = $put( $good, 1 );
gsh_assert_eq( $wpdb->docs, $docs_pre, 'DB-Fehler UPDATE: docs-Zeile (Version, json) unveraendert' );
gsh_assert_eq( $resp->status, 500, 'DB-Fehler beim UPDATE -> 500 (nicht 409)' );
gsh_assert_eq( $resp->data['error'], 'server_error', 'DB-Fehler: generischer Fehlercode' );
gsh_assert_eq( strpos( json_encode( $resp->data ), 'secret db detail' ) === false, true, 'DB-Fehler: keine DB-Details im Body' );
gsh_assert_eq( count( $wpdb->revs ), 1, 'DB-Fehler: keine zusaetzliche Revision' );

$wpdb = $fresh_wpdb();
$wpdb->fail_insert = true;
$resp = $put( $good, 0 );
gsh_assert_eq( $resp->status, 500, 'DB-Fehler beim INSERT (kein Doc vorhanden) -> 500 (nicht 409)' );
gsh_assert_eq( strpos( json_encode( $resp->data ), 'secret db detail' ) === false, true, 'INSERT-Fehler: keine DB-Details im Body' );

/* Echter Konflikt bleibt 409, auch ohne vorhandenes Doc (current null-sicher). */
$wpdb = $fresh_wpdb();
$resp = $put( $good, 5 );
gsh_assert_eq( $resp->status, 409, 'baseVersion>0 ohne Doc -> 409' );
gsh_assert_eq( $resp->data['serverVersion'], 0, 'Konflikt ohne Doc: serverVersion 0, keine Warnung' );

/* wp_json_encode schlaegt fehl -> nichts schreiben. */
$wpdb = $fresh_wpdb();
$GLOBALS['gsh_force_json_fail'] = true;
$res = gsh_tp_curriculr_repo_put( 'sj_2026_27', $good, 0 );
$GLOBALS['gsh_force_json_fail'] = false;
gsh_assert_eq( $res['status'], 'error', 'json-Encode-Fehler -> status error' );
gsh_assert_eq( $wpdb->docs, array(), 'json-Encode-Fehler: nichts geschrieben' );
gsh_assert_eq( count( $wpdb->revs ), 0, 'json-Encode-Fehler: keine Revision' );

/* Revisions-INSERT schlaegt nach erfolgreichem Doc-UPDATE fehl: Erfolg, Version erhoeht, loggen. */
$wpdb = $fresh_wpdb();
gsh_tp_curriculr_repo_put( 'sj_2026_27', $good, 0 );
$wpdb->fail_rev_insert = true;
$resp = $put( $good, 1 );
gsh_assert_eq( $resp->status, 200, 'Revisions-Fehler: PUT meldet Erfolg (Doc ist gespeichert)' );
gsh_assert_eq( $resp->data['version'], 2, 'Revisions-Fehler: Version erhoeht' );
gsh_assert_eq( (int) $wpdb->docs['sj_2026_27']['version'], 2, 'Revisions-Fehler: Version in DB erhoeht' );
gsh_assert_eq( count( $wpdb->revs ), 1, 'Revisions-Fehler: keine Revision fuer v2' );
$stale = $put( $good, 1 );
gsh_assert_eq( $stale->status, 409, 'Konflikt nach Revisions-Fehler -> 409' );
gsh_assert_eq( $stale->data['authorName'], '', 'Konflikt ohne Revision: leerer authorName' );
gsh_assert_eq( $stale->data['serverVersion'], 2, 'Konflikt ohne Revision: serverVersion 2' );
// insert_id darf nicht als Erfolg gelten: bei Fehler bleibt die alte id stehen.
gsh_assert_eq( gsh_tp_curriculr_repo_save_revision( 'sj_2026_27', 3, '{}' ), false, 'save_revision liefert false bei fehlgeschlagenem INSERT' );

/* Datei-Import: status=error -> generische Meldung, kein Konflikt-Text. */
$wpdb = $fresh_wpdb();
gsh_tp_curriculr_repo_put( 'sj_2026_27', $good, 0 );
$GLOBALS['schoolyears'] = array( array( 'key' => 'sj_2026_27' ) );
$tmp = tempnam( sys_get_temp_dir(), 'gshdoc' );
file_put_contents( $tmp, json_encode( $good ) );
$_POST  = array( 'gsh_tp_di_sy' => 'sj_2026_27', 'gsh_tp_di_confirm' => '1' );
$_FILES = array( 'gsh_tp_di_file' => array( 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => filesize( $tmp ) ) );
$wpdb->fail_update = true;
ob_start();
gsh_tp_curriculr_handle_doc_import();
$out = ob_get_clean();
gsh_assert_contains( $out, 'notice-error', 'Import bei DB-Fehler: Fehlermeldung' );
gsh_assert_contains( $out, 'Bitte versuchen Sie es später erneut', 'Import bei DB-Fehler: generische Meldung (Sie)' );
gsh_assert_eq( strpos( $out, 'zwischenzeitlich' ) === false, true, 'Import bei DB-Fehler: kein Konflikt-Text' );
gsh_assert_eq( strpos( $out, 'secret db detail' ) === false, true, 'Import bei DB-Fehler: keine DB-Details' );
unlink( $tmp );

gsh_test_done();
