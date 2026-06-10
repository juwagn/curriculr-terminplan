<?php
/**
 * Unit tests for the IServ-SSO auth module. Dependency-free (kein Composer/
 * PHPUnit) — exercises the pure helpers (config, base64url/JWT, authorize-URL,
 * group extract/check, nonce) und den testbaren POST /auth/token-Handler mit
 * Array-Transients und überschreibbaren wp_remote_*-Stubs. Redirect/exit-Handler
 * (login/callback) und Live-HTTP bleiben einem echten WP-Install vorbehalten.
 */
define( 'GSH_TP_CURRICULR_TEST', true );

require __DIR__ . '/assert.php';

/* ---------- Config-Konstanten für den Reader ---------- */
define( 'CURRICULR_ISERV_BASE_URL',      'https://schule.iserv.de' );
define( 'CURRICULR_ISERV_CLIENT_ID',     'client-abc' );
define( 'CURRICULR_ISERV_CLIENT_SECRET', 'secret-xyz' );
define( 'CURRICULR_APP_TOKEN_KEY',       'k0123456789abcdef0123456789abcdef' );
define( 'CURRICULR_SPA_URL',             'https://juwagn.github.io/curriculr-planner/' );
define( 'CURRICULR_ALLOWED_GROUPS',      'Schulleitung, Verwaltung' );

/* ---------- minimale WordPress-Stubs ---------- */
$GLOBALS['transients']   = array();
$GLOBALS['redirects']    = array();
$GLOBALS['remote_queue'] = array(); // FIFO: nächste wp_remote_*-Antwort
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
class WP_Error { public $msg; public function __construct( $c = '', $m = '' ) { $this->msg = $m; } }
class WP_REST_Response { public $data; public $status; public function __construct( $d, $s = 200 ) { $this->data = $d; $this->status = $s; } }
function add_action() {}

require __DIR__ . '/../../plugin/curriculr-auth.php';

/* ---------- Config-Reader ---------- */
$cfg = gsh_tp_curriculr_auth_config();
gsh_assert_eq( $cfg['iserv_base'], 'https://schule.iserv.de', 'config reads iserv_base' );
gsh_assert_eq( $cfg['client_id'], 'client-abc', 'config reads client_id' );
gsh_assert_eq( $cfg['client_secret'], 'secret-xyz', 'config reads client_secret' );
gsh_assert_eq( $cfg['redirect_uri'], 'https://wp.test/wp-json/curriculr/v1/auth/callback', 'config derives redirect_uri from rest_url' );
gsh_assert_eq( $cfg['allowed_groups'], array( 'Schulleitung', 'Verwaltung' ), 'config splits + trims allowed_groups' );
gsh_assert_eq( $cfg['token_ttl'], 1800, 'config defaults token_ttl to 1800' );
gsh_assert_true( gsh_tp_curriculr_auth_is_configured( $cfg ), 'is_configured true when all secrets present' );
gsh_assert_eq( gsh_tp_curriculr_auth_is_configured( array( 'iserv_base' => '', 'client_id' => '', 'client_secret' => '', 'app_token_key' => '' ) ), false, 'is_configured false when empty' );

/* ---------- base64url + HS256 JWT ---------- */
gsh_assert_eq( gsh_tp_curriculr_b64url_encode( 'A?B>C' ), 'QT9CPkM', 'b64url_encode strips padding + uses -_ alphabet' );
gsh_assert_eq( gsh_tp_curriculr_b64url_decode( 'QT9CPkM' ), 'A?B>C', 'b64url_decode round-trips' );

$key   = 'k0123456789abcdef0123456789abcdef';
$jwt   = gsh_tp_curriculr_jwt_sign( array( 'sub' => 'u1', 'exp' => 9999999999 ), $key );
gsh_assert_eq( substr_count( $jwt, '.' ), 2, 'jwt has three dot-separated parts' );

$ok = gsh_tp_curriculr_jwt_verify( $jwt, $key, 1000 );
gsh_assert_true( $ok['valid'], 'jwt verifies with correct key before exp' );
gsh_assert_eq( $ok['claims']['sub'], 'u1', 'jwt verify returns claims' );

$bad_key = gsh_tp_curriculr_jwt_verify( $jwt, 'wrong-key', 1000 );
gsh_assert_eq( $bad_key['valid'], false, 'jwt rejects wrong key' );
gsh_assert_eq( $bad_key['error'], 'bad_signature', 'jwt wrong key -> bad_signature' );

$expired = gsh_tp_curriculr_jwt_verify( gsh_tp_curriculr_jwt_sign( array( 'exp' => 500 ), $key ), $key, 1000 );
gsh_assert_eq( $expired['error'], 'expired', 'jwt past exp -> expired' );

$tampered = gsh_tp_curriculr_jwt_verify( $jwt . 'x', $key, 1000 );
gsh_assert_eq( $tampered['valid'], false, 'tampered signature rejected' );

$malformed = gsh_tp_curriculr_jwt_verify( 'not-a-jwt', $key, 1000 );
gsh_assert_eq( $malformed['error'], 'malformed', 'non-jwt -> malformed' );

$no_key = gsh_tp_curriculr_jwt_verify( $jwt, '', 1000 );
gsh_assert_eq( $no_key['error'], 'no_key', 'jwt empty key -> no_key' );

/* ---------- App-Token-Claims ---------- */
$claims = gsh_tp_curriculr_make_app_token_claims( 'iserv-uuid-1', 'Frau Beispiel', array( 'Schulleitung' ), 1000, 1800, 'https://wp.test/wp-json/curriculr/v1', 'https://juwagn.github.io/curriculr-planner/' );
gsh_assert_eq( $claims['sub'], 'iserv-uuid-1', 'claims carry sub' );
gsh_assert_eq( $claims['name'], 'Frau Beispiel', 'claims carry display name' );
gsh_assert_eq( $claims['groups'], array( 'Schulleitung' ), 'claims carry groups' );
gsh_assert_eq( $claims['iat'], 1000, 'claims iat = now' );
gsh_assert_eq( $claims['exp'], 2800, 'claims exp = now + ttl' );
gsh_assert_eq( $claims['iss'], 'https://wp.test/wp-json/curriculr/v1', 'claims carry iss' );
gsh_assert_eq( $claims['aud'], 'https://juwagn.github.io/curriculr-planner/', 'claims carry aud' );

/* ---------- Authorize-URL ---------- */
$au = gsh_tp_curriculr_build_authorize_url( $cfg, 'STATE123', 'NONCE456' );
gsh_assert_contains( $au, 'https://schule.iserv.de/iserv/auth/auth?', 'authorize url hits IServ /iserv/auth/auth' );
gsh_assert_contains( $au, 'response_type=code', 'authorize url asks for code' );
gsh_assert_contains( $au, 'client_id=client-abc', 'authorize url carries client_id' );
gsh_assert_contains( $au, 'scope=openid+profile+iserv%3Agroups', 'authorize url requests openid profile iserv:groups' );
gsh_assert_contains( $au, 'state=STATE123', 'authorize url carries state' );
gsh_assert_contains( $au, 'nonce=NONCE456', 'authorize url carries nonce' );
gsh_assert_contains( $au, 'redirect_uri=https%3A%2F%2Fwp.test%2Fwp-json%2Fcurriculr%2Fv1%2Fauth%2Fcallback', 'authorize url carries exact redirect_uri' );

/* ---------- Gruppen-Extraktion (offener Punkt Spec §13: Claim-Form) ---------- */
gsh_assert_eq( gsh_tp_curriculr_extract_groups( array( 'Schulleitung', 'Lehrer' ) ), array( 'Schulleitung', 'Lehrer' ), 'extract_groups handles string list' );
gsh_assert_eq( gsh_tp_curriculr_extract_groups( array( array( 'act' => 'schulleitung', 'name' => 'Schulleitung' ) ) ), array( 'schulleitung' ), 'extract_groups prefers act on object form' );
gsh_assert_eq( gsh_tp_curriculr_extract_groups( array( array( 'name' => 'Schulleitung' ) ) ), array( 'Schulleitung' ), 'extract_groups falls back to name' );
gsh_assert_eq( gsh_tp_curriculr_extract_groups( 'nope' ), array(), 'extract_groups of non-array is empty' );

/* ---------- Whitelist-Prüfung (Gruppenfilter #2, fail-closed) ---------- */
gsh_assert_true( gsh_tp_curriculr_group_check( array( 'Schulleitung' ), array( 'Schulleitung', 'Verwaltung' ), ), 'member of whitelist passes' );
gsh_assert_eq( gsh_tp_curriculr_group_check( array( 'Lehrer' ), array( 'Schulleitung' ) ), false, 'non-member rejected' );
gsh_assert_eq( gsh_tp_curriculr_group_check( array( 'Schulleitung' ), array() ), false, 'empty whitelist fails closed' );
gsh_assert_eq( gsh_tp_curriculr_group_check( array(), array( 'Schulleitung' ) ), false, 'no user groups rejected' );

/* ---------- id_token-Payload (nur Lesen; Nonce-Bindung Spec §4 Schritt 5) ---------- */
$fake_id_token = gsh_tp_curriculr_b64url_encode( '{"alg":"RS256"}' )
    . '.' . gsh_tp_curriculr_b64url_encode( '{"sub":"u1","nonce":"NONCE456"}' )
    . '.sig';
$payload = gsh_tp_curriculr_jwt_payload( $fake_id_token );
gsh_assert_eq( $payload['nonce'], 'NONCE456', 'jwt_payload reads id_token nonce without verifying signature' );
gsh_assert_eq( gsh_tp_curriculr_jwt_payload( 'garbage' ), null, 'jwt_payload of malformed token is null' );

gsh_test_done();
