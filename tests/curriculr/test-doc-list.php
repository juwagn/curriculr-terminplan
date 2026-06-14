<?php
/**
 * Tests für den Plan-Listen-Endpoint GET /curriculr/v1/docs.
 * Dependency-free, läuft mit plain `php`.
 */
define( 'GSH_TP_CURRICULR_TEST', true );
define( 'GSH_TP_VERSION', '4.17.0-test' );
define( 'ARRAY_A', 'ARRAY_A' );

require __DIR__ . '/assert.php';

/* ---------- WP-Stubs ---------- */
class Gsh_Fake_Wpdb_List {
    public $prefix = 'wp_';
    public $docs   = array();  // ARRAY_A rows keyed by schoolyear
    public $revs   = array();  // list of array(schoolyear, version, author_name)

    public function get_results( $query, $out = null ) {
        return array_values( $this->docs );
    }
    public function prepare( $q, ...$args ) {
        // rev author lookup: prepare(sql, sj, version) -> marker
        if ( count( $args ) === 2 ) {
            return '__rev__:' . (string) $args[0] . ':' . (int) $args[1];
        }
        return $q;
    }
    public function get_row( $key, $out = null ) {
        if ( is_string( $key ) && strncmp( $key, '__rev__:', 8 ) === 0 ) {
            $p   = explode( ':', $key, 3 );
            $sj  = $p[1] ?? '';
            $ver = isset( $p[2] ) ? (int) $p[2] : -1;
            foreach ( $this->revs as $r ) {
                if ( $r['schoolyear'] === $sj && (int) $r['version'] === $ver ) {
                    return (object) $r;
                }
            }
        }
        return null;
    }
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
    class WP_REST_Response {
        public $data; public $status;
        public function __construct( $data, $status = 200 ) { $this->data = $data; $this->status = $status; }
    }
}
if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $k ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $k ) ); }
}
if ( ! function_exists( 'add_action' ) ) {
    function add_action() {}
}
if ( ! function_exists( 'add_filter' ) ) {
    function add_filter() {}
}
if ( ! function_exists( 'register_activation_hook' ) ) {
    function register_activation_hook() {}
}
if ( ! function_exists( 'register_deactivation_hook' ) ) {
    function register_deactivation_hook() {}
}
if ( ! function_exists( 'wp_next_scheduled' ) ) {
    function wp_next_scheduled() { return false; }
}
if ( ! function_exists( 'wp_schedule_event' ) ) {
    function wp_schedule_event() {}
}
if ( ! function_exists( 'get_option' ) ) {
    function get_option( $k, $d = false ) { return $d; }
}
if ( ! function_exists( 'update_option' ) ) {
    function update_option( $k, $v, $a = null ) { return true; }
}

global $wpdb;
$wpdb = new Gsh_Fake_Wpdb_List();
$wpdb->docs = array(
    'sj_2026_27' => array(
        'schoolyear' => 'sj_2026_27',
        'json'       => json_encode( array( 'meta' => array( 'name' => 'Schuljahr 2026/27' ) ) ),
        'version'    => 12,
        'stage'      => 'genehmigt',
        'updated_at' => '2026-06-12 09:00:00',
    ),
    'sj_2025_26' => array(
        'schoolyear' => 'sj_2025_26',
        'json'       => json_encode( array( 'meta' => array( 'name' => 'Schuljahr 2025/26' ) ) ),
        'version'    => 40,
        'stage'      => 'oeffentlich',
        'updated_at' => '2026-06-03 10:00:00',
    ),
);
$wpdb->revs = array(
    array( 'schoolyear' => 'sj_2026_27', 'version' => 12, 'author_name' => 'M. Weber' ),
    array( 'schoolyear' => 'sj_2025_26', 'version' => 40, 'author_name' => 'A. Klein' ),
);

require __DIR__ . '/../../plugin/curriculr-data-layer.php';

$res  = gsh_tp_curriculr_rest_doc_list();
$data = $res->data;

gsh_assert_eq( $res->status, 200, 'status 200' );
gsh_assert_eq( count( $data ), 2, 'zwei Pläne gelistet' );

$byKey = array();
foreach ( $data as $row ) { $byKey[ $row['sj'] ] = $row; }

gsh_assert_eq( $byKey['sj_2026_27']['name'], 'Schuljahr 2026/27', 'name aus doc.meta.name' );
gsh_assert_eq( $byKey['sj_2026_27']['stage'], 'genehmigt', 'stage übernommen' );
gsh_assert_eq( $byKey['sj_2026_27']['version'], 12, 'version übernommen' );
gsh_assert_eq( $byKey['sj_2026_27']['authorName'], 'M. Weber', 'authorName aus neuester Revision' );
gsh_assert_eq( $byKey['sj_2026_27']['updatedAt'], '2026-06-12 09:00:00', 'updatedAt übernommen' );
gsh_assert_eq( isset( $byKey['sj_2026_27']['json'] ), false, 'kein json im Listing' );

gsh_assert_eq( $byKey['sj_2025_26']['authorName'], 'A. Klein', 'authorName zweiter Plan' );

/* ---------- Issue 5: name-fallback wenn meta.name fehlt ---------- */

$wpdb->docs['sj_2027_28'] = array(
    'schoolyear' => 'sj_2027_28',
    'json'       => json_encode( array( 'meta' => array() ) ),  // meta.name fehlt
    'version'    => 1,
    'stage'      => 'entwurf',
    'updated_at' => '2027-01-01 00:00:00',
);

$res2  = gsh_tp_curriculr_rest_doc_list();
$data2 = $res2->data;
gsh_assert_eq( $res2->status, 200, 'status 200 (drei Einträge)' );
gsh_assert_eq( count( $data2 ), 3, 'drei Pläne gelistet' );
$byKey2 = array();
foreach ( $data2 as $row ) { $byKey2[ $row['sj'] ] = $row; }
gsh_assert_eq( $byKey2['sj_2027_28']['name'], 'sj_2027_28', 'name-fallback auf sj wenn meta.name fehlt' );

/* ---------- Issue 6: DB-Fehler → 500 ---------- */

class Gsh_Fake_Wpdb_Null {
    public $prefix = 'wp_';
    public function get_results( $query, $out = null ) { return null; }
    public function prepare( $q, ...$args ) { return $q; }
    public function get_row( $key, $out = null ) { return null; }
}

$wpdb = new Gsh_Fake_Wpdb_Null();
$res3 = gsh_tp_curriculr_rest_doc_list();
gsh_assert_eq( $res3->status, 500, 'DB-Fehler → 500' );
gsh_assert_eq( $res3->data['error'], 'db_error', 'DB-Fehler-Body enthält error=db_error' );

gsh_test_done();
echo "test-doc-list: OK\n";
