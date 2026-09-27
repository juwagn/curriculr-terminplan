<?php
/**
 * Tests für den Login mehrerer Apps (ab 4.42.0): App-Registry, CORS-Origin,
 * Claims je App, Login mit unbekannter App, Guard je App. Dependency-free.
 */
define( 'GSH_TP_CURRICULR_TEST', true );
require __DIR__ . '/assert.php';

define( 'CURRICULR_ISERV_BASE_URL',      'https://schule.iserv.de' );
define( 'CURRICULR_ISERV_CLIENT_ID',     'client-abc' );
define( 'CURRICULR_ISERV_CLIENT_SECRET', 'secret-xyz' );
define( 'CURRICULR_APP_TOKEN_KEY',       'k0123456789abcdef0123456789abcdef' );
define( 'CURRICULR_SPA_URL',             'https://juwagn.github.io/curriculr-planner/' );
define( 'CURRICULR_ALLOWED_GROUPS',      'Schulleitung' );

/* ---------- minimale WordPress-Stubs ---------- */
$GLOBALS['transients']   = array();
$GLOBALS['redirects']    = array();
$GLOBALS['remote_queue'] = array();
$GLOBALS['app_filter']   = null; // callable|null — simuliert add_filter('curriculr_apps', …)
function rest_url( $p ) { return 'https://wp.test/wp-json/' . $p; }
function set_transient( $k, $v, $ttl ) { $GLOBALS['transients'][ $k ] = $v; return true; }
function get_transient( $k ) { return $GLOBALS['transients'][ $k ] ?? false; }
function delete_transient( $k ) { unset( $GLOBALS['transients'][ $k ] ); return true; }
function wp_generate_password( $l = 12, $s = true, $e = true ) { return substr( str_repeat( 'aB3xY9Qz', 16 ), 0, $l ); }
function wp_redirect( $u ) { $GLOBALS['redirects'][] = $u; }
function wp_remote_post( $u, $a = array() ) { return array_shift( $GLOBALS['remote_queue'] ); }
function wp_remote_get( $u, $a = array() ) { return array_shift( $GLOBALS['remote_queue'] ); }
function wp_remote_retrieve_body( $r ) { return is_array( $r ) ? ( $r['body'] ?? '' ) : ''; }
function is_wp_error( $t ) { return $t instanceof WP_Error; }
function apply_filters( $hook, $value ) {
    if ( $hook === 'curriculr_apps' && is_callable( $GLOBALS['app_filter'] ) ) {
        return call_user_func( $GLOBALS['app_filter'], $value );
    }
    return $value;
}
class WP_Error {
    public $code; public $message; public $data;
    public function __construct( $c = '', $m = '', $d = array() ) { $this->code = $c; $this->message = $m; $this->data = $d; }
}
class WP_REST_Response { public $data; public $status; public function __construct( $d, $s = 200 ) { $this->data = $d; $this->status = $s; } }
function add_action() {}

require __DIR__ . '/../../plugin/curriculr-auth.php';

$KP_URL = 'https://klausurplan.schule.de/';
$with_klausurplan = function ( $apps ) use ( $KP_URL ) {
    $apps['klausurplan'] = array( 'url' => $KP_URL, 'groups' => 'Oberstufenleitung, Schulleitung' );
    return $apps;
};

/* ---------- split_groups ---------- */
gsh_assert_eq( gsh_tp_curriculr_split_groups( ' A, ,B ,A' ), array( 'A', 'B' ), 'split_groups: Komma-String, trimmt, entfernt Leere und Doppelte' );
gsh_assert_eq( gsh_tp_curriculr_split_groups( array( 'A', '', 3, ' B ' ) ), array( 'A', 'B' ), 'split_groups: Array, nur nicht-leere Strings' );
gsh_assert_eq( gsh_tp_curriculr_split_groups( null ), array(), 'split_groups: Unsinn → leer' );

/* ---------- normalize_app_url ---------- */
gsh_assert_eq( gsh_tp_curriculr_normalize_app_url( 'https://klausurplan.schule.de' ), 'https://klausurplan.schule.de/', 'URL bekommt abschließenden Slash' );
gsh_assert_eq( gsh_tp_curriculr_normalize_app_url( 'https://schule.de/app/' ), 'https://schule.de/app/', 'URL mit Pfad bleibt' );
gsh_assert_eq( gsh_tp_curriculr_normalize_app_url( 'https://schule.de:8443/app' ), 'https://schule.de:8443/app/', 'https mit Port erlaubt' );
gsh_assert_eq( gsh_tp_curriculr_normalize_app_url( 'http://localhost:5174' ), 'http://localhost:5174/', 'localhost über http erlaubt' );
gsh_assert_eq( gsh_tp_curriculr_normalize_app_url( 'http://klausurplan.schule.de/' ), '', 'http ohne localhost abgelehnt' );
gsh_assert_eq( gsh_tp_curriculr_normalize_app_url( 'javascript:alert(1)' ), '', 'javascript: abgelehnt' );
gsh_assert_eq( gsh_tp_curriculr_normalize_app_url( 'https://schule.de/app/?x=1' ), '', 'Query abgelehnt' );
gsh_assert_eq( gsh_tp_curriculr_normalize_app_url( 'https://schule.de/app/#x' ), '', 'Fragment abgelehnt' );
gsh_assert_eq( gsh_tp_curriculr_normalize_app_url( 42 ), '', 'kein String abgelehnt' );
gsh_assert_eq( gsh_tp_curriculr_normalize_app_url( 'https://trusted.example@evil.example/' ), '', 'Userinfo (kein Port) abgelehnt' );
gsh_assert_eq( gsh_tp_curriculr_normalize_app_url( 'https://user:pass@evil.example/' ), '', 'Userinfo mit Passwort abgelehnt' );
gsh_assert_eq( gsh_tp_curriculr_normalize_app_url( 'https://[::1]:8443/' ), '', 'IPv6-Literal abgelehnt' );
gsh_assert_eq( gsh_tp_curriculr_normalize_app_url( 'HTTPS://Example.COM/App' ), 'https://example.com/App/', 'Schema und Host klein, Pfad-Case bleibt' );
gsh_assert_eq( gsh_tp_curriculr_normalize_app_url( 'http://LOCALHOST:5174' ), 'http://localhost:5174/', 'localhost-Host klein' );

/* ---------- apps(): ohne Filter wie 4.41.0 ---------- */
$GLOBALS['app_filter'] = null;
$apps = gsh_tp_curriculr_apps();
gsh_assert_eq( array_keys( $apps ), array( 'terminplan' ), 'ohne Filter nur terminplan' );
gsh_assert_eq( $apps['terminplan']['url'], 'https://juwagn.github.io/curriculr-planner/', 'terminplan-URL exakt CURRICULR_SPA_URL' );
gsh_assert_eq( $apps['terminplan']['groups'], array( 'Schulleitung' ), 'terminplan-Gruppen aus CURRICULR_ALLOWED_GROUPS' );

/* ---------- apps(): mit Filter ---------- */
$GLOBALS['app_filter'] = $with_klausurplan;
$apps = gsh_tp_curriculr_apps();
gsh_assert_eq( array_keys( $apps ), array( 'terminplan', 'klausurplan' ), 'Filter fügt klausurplan hinzu, terminplan zuerst' );
gsh_assert_eq( $apps['klausurplan'], array( 'url' => $KP_URL, 'groups' => array( 'Oberstufenleitung', 'Schulleitung' ) ), 'klausurplan normalisiert' );

/* ---------- apps(): Filter liefert Unsinn ---------- */
$GLOBALS['app_filter'] = function ( $apps ) {
    $apps['terminplan'] = array( 'url' => 'https://evil.example/', 'groups' => array( 'X' ) );
    $apps['Bad Key']    = array( 'url' => 'https://a.example/', 'groups' => array( 'X' ) );
    $apps['no-groups']  = array( 'url' => 'https://b.example/', 'groups' => '' );
    $apps['bad-url']    = array( 'url' => 'ftp://c.example/', 'groups' => array( 'X' ) );
    $apps['not-array']  = 'https://d.example/';
    $apps['ok']         = array( 'url' => 'https://e.example', 'groups' => array( 'X' ) );
    return $apps;
};
$apps = gsh_tp_curriculr_apps();
gsh_assert_eq( array_keys( $apps ), array( 'terminplan', 'ok' ), 'ungültige Einträge verworfen' );
gsh_assert_eq( $apps['terminplan']['url'], 'https://juwagn.github.io/curriculr-planner/', 'terminplan per Filter nicht überschreibbar' );
$GLOBALS['app_filter'] = function ( $apps ) { return 'kaputt'; };
gsh_assert_eq( array_keys( gsh_tp_curriculr_apps() ), array( 'terminplan' ), 'Filter liefert kein Array → nur terminplan' );

/* ---------- app_origin ---------- */
gsh_assert_eq( gsh_tp_curriculr_app_origin( 'https://Klausurplan.Schule.de/app/' ), 'https://klausurplan.schule.de', 'Origin kleingeschrieben ohne Pfad' );
gsh_assert_eq( gsh_tp_curriculr_app_origin( 'http://localhost:5174/' ), 'http://localhost:5174', 'Origin mit Port' );
gsh_assert_eq( gsh_tp_curriculr_app_origin( 'kein url' ), '', 'ungültig → leer' );

/* ---------- cors_origin ---------- */
$GLOBALS['app_filter'] = $with_klausurplan;
$apps    = gsh_tp_curriculr_apps();
$default = 'https://juwagn.github.io';
gsh_assert_eq( gsh_tp_curriculr_cors_origin( 'https://klausurplan.schule.de', $default, $apps ), 'https://klausurplan.schule.de', 'registrierte App-Origin wird gespiegelt' );
gsh_assert_eq( gsh_tp_curriculr_cors_origin( 'HTTPS://Klausurplan.Schule.de/', $default, $apps ), 'https://klausurplan.schule.de', 'Groß-/Kleinschreibung und Slash egal, Ausgabe kanonisch' );
gsh_assert_eq( gsh_tp_curriculr_cors_origin( 'https://evil.example', $default, $apps ), $default, 'fremde Origin → Standard' );
gsh_assert_eq( gsh_tp_curriculr_cors_origin( 'https://klausurplan.schule.de.evil.example', $default, $apps ), $default, 'Suffix-Trick → Standard' );
gsh_assert_eq( gsh_tp_curriculr_cors_origin( '', $default, $apps ), $default, 'keine Origin → Standard' );
gsh_assert_eq( gsh_tp_curriculr_cors_origin( null, $default, $apps ), $default, 'null → Standard' );

/* ---------- resolve_app_key ---------- */
$GLOBALS['app_filter'] = $with_klausurplan;
$apps = gsh_tp_curriculr_apps();
gsh_assert_eq( gsh_tp_curriculr_resolve_app_key( '', $apps ), 'terminplan', 'ohne app → terminplan' );
gsh_assert_eq( gsh_tp_curriculr_resolve_app_key( null, $apps ), 'terminplan', 'null → terminplan' );
gsh_assert_eq( gsh_tp_curriculr_resolve_app_key( 'klausurplan', $apps ), 'klausurplan', 'bekannte App' );
gsh_assert_eq( gsh_tp_curriculr_resolve_app_key( 'gibtsnicht', $apps ), null, 'unbekannte App → null' );
gsh_assert_eq( gsh_tp_curriculr_resolve_app_key( array( 'x' ), $apps ), null, 'Array → null' );

/* ---------- app_for_state ---------- */
gsh_assert_eq( gsh_tp_curriculr_app_for_state( array( 'nonce' => 'n' ), $apps ), 'terminplan', 'alter Transient ohne app → terminplan' );
gsh_assert_eq( gsh_tp_curriculr_app_for_state( array( 'nonce' => 'n', 'app' => 'klausurplan' ), $apps ), 'klausurplan', 'app aus Transient' );
gsh_assert_eq( gsh_tp_curriculr_app_for_state( array( 'nonce' => 'n', 'app' => 'weg' ), $apps ), 'terminplan', 'inzwischen entfernte App → terminplan' );

/* ---------- claims_for_app ---------- */
$iss = 'https://wp.test/wp-json/curriculr/v1';
$c = gsh_tp_curriculr_claims_for_app( 'sub-1', 'Frau Muster', array( 'Oberstufenleitung', 'Lehrer', 'Schulleitung' ), $apps['klausurplan'], 1000, 1800, $iss );
gsh_assert_eq( $c['aud'], $KP_URL, 'Token-aud = App-URL' );
gsh_assert_eq( $c['groups'], array( 'Oberstufenleitung', 'Schulleitung' ), 'nur Gruppen dieser App im Token' );
gsh_assert_eq( $c['exp'], 2800, 'exp = now + ttl' );
gsh_assert_eq( gsh_tp_curriculr_claims_for_app( 'sub-2', 'Herr X', array( 'Lehrer' ), $apps['klausurplan'], 1000, 1800, $iss ), null, 'ohne App-Gruppe → null (forbidden)' );
$t = gsh_tp_curriculr_claims_for_app( 'sub-1', 'Frau Muster', array( 'Oberstufenleitung', 'Schulleitung' ), $apps['terminplan'], 1000, 1800, $iss );
gsh_assert_eq( $t['groups'], array( 'Schulleitung' ), 'Terminplan-Token enthält keine Klausurplan-Gruppen' );
gsh_assert_eq( $t['aud'], 'https://juwagn.github.io/curriculr-planner/', 'Terminplan-aud unverändert' );

/* ---------- /auth/login mit unbekannter App ---------- */
class Gsh_Fake_Login_Req implements ArrayAccess {
    private $p;
    public function __construct( $p ) { $this->p = $p; }
    public function offsetExists( $k ): bool { return isset( $this->p[ $k ] ); }
    public function offsetGet( $k ): mixed { return $this->p[ $k ] ?? null; }
    public function offsetSet( $k, $v ): void {}
    public function offsetUnset( $k ): void {}
}
$GLOBALS['transients'] = array();
$GLOBALS['redirects']  = array();
$res = gsh_tp_curriculr_rest_auth_login( new Gsh_Fake_Login_Req( array( 'app' => 'gibtsnicht' ) ) );
gsh_assert_eq( $res->status, 400, 'unbekannte App → 400' );
gsh_assert_eq( $res->data, array( 'error' => 'unknown_app' ), 'Fehlercode unknown_app' );
gsh_assert_eq( $GLOBALS['redirects'], array(), 'kein Redirect bei unbekannter App' );
gsh_assert_eq( $GLOBALS['transients'], array(), 'kein State-Transient bei unbekannter App' );

gsh_test_done();
