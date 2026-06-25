<?php
define( 'GSH_TP_CURRICULR_TEST', true );
define( 'GSH_TP_VERSION', '4.22.0-test' );
define( 'GSH_TP_CACHE_VERSION', 3 );
define( 'ARRAY_A', 'ARRAY_A' );

require __DIR__ . '/assert.php';

/* ---------- WordPress stubs ---------- */
$GLOBALS['options'] = array();
function get_option( $k, $d = false ) { return $GLOBALS['options'][ $k ] ?? $d; }
function update_option( $k, $v, $a = null ) { $GLOBALS['options'][ $k ] = $v; return true; }
function sanitize_key( $k )          { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $k ) ); }
function sanitize_text_field( $s )   { return trim( (string) $s ); }
function gsh_tp_ck( $prefix, $pid )  { return $prefix . $pid . '_v' . GSH_TP_CACHE_VERSION; }
function rest_url( $p )              { return 'https://wp.test/wp-json/' . $p; }
function gsh_tp_curriculr_guard_perm( $req ) { return true; }

class WP_REST_Response {
    public $data;
    public $status;
    public function __construct( $d, $s = 200 ) { $this->data = $d; $this->status = $s; }
}

class Gsh_Fake_Req implements ArrayAccess {
    public function __construct( private array $body = array(), private array $params = array() ) {}
    public function get_json_params() { return $this->body; }
    public function offsetExists( $k ): bool  { return isset( $this->params[ $k ] ); }
    public function offsetGet( $k ): mixed     { return $this->params[ $k ] ?? null; }
    public function offsetSet( $k, $v ): void  {}
    public function offsetUnset( $k ): void    {}
}

/* Minimal stubs so the data-layer loads without errors */
function gsh_tp_get_profiles()           { return array(); }
function gsh_tp_active_profile_id()      { return ''; }
function gsh_tp_curriculr_guard_current_claims() { return null; }
class Gsh_Fake_Wpdb {
    public $prefix = 'wp_';
    public function get_charset_collate() { return ''; }
    public function prepare( $q, ...$a )  { return $q; }
    public function get_row( $k, $o = null ) { return null; }
    public function get_results( $q, $o = null ) { return array(); }
    public function insert( $t, $d ) {}
    public function update( $t, $d, $w ) {}
    public function query( $q ) { return true; }
}
$GLOBALS['wpdb'] = new Gsh_Fake_Wpdb();
$GLOBALS['gsh_tp_curriculr_current_claims'] = null;

require __DIR__ . '/../../plugin/curriculr-data-layer.php';

/* ---------- POST /profile-map: missing sj → 400 ---------- */
$r = gsh_tp_curriculr_rest_profile_map_put(
    new Gsh_Fake_Req( array( 'mappings' => array( array( 'profileId' => 'p1', 'group' => null ) ) ) )
);
gsh_assert_eq( $r->status, 400, 'profile-map: missing sj → 400' );
gsh_assert_eq( $r->data['code'], 'invalid_input', 'profile-map: missing sj error code' );

/* ---------- POST /profile-map: empty sj → 400 ---------- */
$r = gsh_tp_curriculr_rest_profile_map_put(
    new Gsh_Fake_Req( array( 'sj' => '', 'mappings' => array( array( 'profileId' => 'p1', 'group' => null ) ) ) )
);
gsh_assert_eq( $r->status, 400, 'profile-map: empty sj → 400' );

/* ---------- POST /profile-map: missing mappings → 400 ---------- */
$r = gsh_tp_curriculr_rest_profile_map_put(
    new Gsh_Fake_Req( array( 'sj' => 'sj_2026_27' ) )
);
gsh_assert_eq( $r->status, 400, 'profile-map: missing mappings → 400' );

/* ---------- POST /profile-map: empty mappings array → 400 ---------- */
$r = gsh_tp_curriculr_rest_profile_map_put(
    new Gsh_Fake_Req( array( 'sj' => 'sj_2026_27', 'mappings' => array() ) )
);
gsh_assert_eq( $r->status, 400, 'profile-map: empty mappings → 400' );

/* ---------- POST /profile-map: empty profileId → 400 ---------- */
$r = gsh_tp_curriculr_rest_profile_map_put(
    new Gsh_Fake_Req( array( 'sj' => 'sj_2026_27', 'mappings' => array( array( 'profileId' => '', 'group' => null ) ) ) )
);
gsh_assert_eq( $r->status, 400, 'profile-map: empty profileId → 400' );

/* ---------- POST /profile-map: valid body → 200, option updated ---------- */
$GLOBALS['options'] = array();
$r = gsh_tp_curriculr_rest_profile_map_put(
    new Gsh_Fake_Req( array(
        'sj'       => 'sj_2026_27',
        'mappings' => array(
            array( 'profileId' => 'curriculr_test', 'group' => null           ),
            array( 'profileId' => 'curriculr_sl',   'group' => 'Schulleitung' ),
        ),
    ) )
);
gsh_assert_eq( $r->status, 200, 'profile-map: valid body → 200' );
gsh_assert_eq( $r->data['updated'], true, 'profile-map: response has updated=true' );

$saved = get_option( 'gsh_tp_curriculr_profile_map', array() );
gsh_assert_true( is_array( $saved ), 'profile-map: option is array' );
gsh_assert_eq( count( $saved['sj_2026_27'] ), 2, 'profile-map: 2 mappings saved' );
gsh_assert_eq( $saved['sj_2026_27'][0]['profileId'], 'curriculr_test', 'profile-map: mapping 0 profileId' );
gsh_assert_eq( $saved['sj_2026_27'][0]['group'],     null,             'profile-map: mapping 0 group null' );
gsh_assert_eq( $saved['sj_2026_27'][1]['profileId'], 'curriculr_sl',   'profile-map: mapping 1 profileId' );
gsh_assert_eq( $saved['sj_2026_27'][1]['group'],     'Schulleitung',   'profile-map: mapping 1 group' );

/* ---------- POST /profile-map: merges with existing other sj entries ---------- */
$GLOBALS['options']['gsh_tp_curriculr_profile_map'] = array(
    'sj_2025_26' => array( array( 'profileId' => 'old_profile', 'group' => null ) ),
);
$r = gsh_tp_curriculr_rest_profile_map_put(
    new Gsh_Fake_Req( array(
        'sj'       => 'sj_2026_27',
        'mappings' => array( array( 'profileId' => 'new_profile', 'group' => null ) ),
    ) )
);
gsh_assert_eq( $r->status, 200, 'profile-map merge: 200' );
$saved = get_option( 'gsh_tp_curriculr_profile_map', array() );
gsh_assert_true( isset( $saved['sj_2025_26'] ), 'profile-map merge: existing sj preserved' );
gsh_assert_true( isset( $saved['sj_2026_27'] ), 'profile-map merge: new sj written' );

gsh_test_done();
