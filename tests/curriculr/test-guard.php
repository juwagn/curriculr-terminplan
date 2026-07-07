<?php
/**
 * Tests for curriculr-guard.php — Bearer app-token validation.
 * Dependency-free, runs with plain `php`.
 */
define( 'GSH_TP_CURRICULR_TEST', true );
require __DIR__ . '/assert.php';

/* ---------- Config constants (needed by curriculr-auth.php config reader) ---------- */
define( 'CURRICULR_ISERV_BASE_URL',      'https://schule.iserv.de' );
define( 'CURRICULR_ISERV_CLIENT_ID',     'client-abc' );
define( 'CURRICULR_ISERV_CLIENT_SECRET', 'secret-xyz' );
define( 'CURRICULR_APP_TOKEN_KEY',       'k0123456789abcdef0123456789abcdef' );
define( 'CURRICULR_SPA_URL',             'https://juwagn.github.io/curriculr-planner/' );
define( 'CURRICULR_ALLOWED_GROUPS',      'Schulleitung' );

/* ---------- Minimal WP stubs ---------- */
$GLOBALS['transients']   = array();
$GLOBALS['remote_queue'] = array();
function rest_url( $p ) { return 'https://wp.test/wp-json/' . $p; }
function set_transient( $k, $v, $ttl ) { $GLOBALS['transients'][ $k ] = $v; }
function get_transient( $k ) { return $GLOBALS['transients'][ $k ] ?? false; }
function delete_transient( $k ) { unset( $GLOBALS['transients'][ $k ] ); }
function wp_generate_password( $l = 12, $s = true, $e = true ) { return substr( str_repeat( 'aB3xY9Qz', 16 ), 0, $l ); }
function wp_redirect( $u ) {}
function wp_remote_post( $u, $a = array() ) { return array_shift( $GLOBALS['remote_queue'] ); }
function wp_remote_get( $u, $a = array() ) { return array_shift( $GLOBALS['remote_queue'] ); }
function wp_remote_retrieve_body( $r ) { return is_array( $r ) ? ( $r['body'] ?? '' ) : ''; }
function is_wp_error( $t ) { return $t instanceof WP_Error; }
class WP_Error {
    public $code; public $message; public $data;
    public function __construct( $c = '', $m = '', $d = array() ) {
        $this->code = $c; $this->message = $m; $this->data = $d;
    }
}
class WP_REST_Response {
    public $data; public $status;
    public function __construct( $d, $s = 200 ) { $this->data = $d; $this->status = $s; }
}
function add_action() {}

class Gsh_Fake_Guard_Req {
    private $headers;
    public function __construct( $headers = array() ) { $this->headers = $headers; }
    public function get_header( $name ) { return $this->headers[ strtolower( $name ) ] ?? null; }
}

require __DIR__ . '/../../plugin/curriculr-auth.php';
require __DIR__ . '/../../plugin/curriculr-guard.php'; // <-- will fail until created

/* ---------- validate_bearer: pure function ---------- */
$key    = 'k0123456789abcdef0123456789abcdef';
$claims = array(
    'sub'    => 'iserv-sub-001',
    'name'   => 'Alice',
    'groups' => array( 'Schulleitung' ),
    'exp'    => 9999999999,
    'iat'    => 1000,
    'iss'    => 'https://wp.test/wp-json/curriculr/v1',
    'aud'    => 'https://juwagn.github.io/curriculr-planner/',
);
$jwt = gsh_tp_curriculr_jwt_sign( $claims, $key );

$r = gsh_tp_curriculr_guard_validate_bearer( 'Bearer ' . $jwt, $key, 1000 );
gsh_assert_true( $r['valid'], 'valid bearer accepted' );
gsh_assert_eq( $r['claims']['sub'], 'iserv-sub-001', 'claims sub correct' );

$r = gsh_tp_curriculr_guard_validate_bearer( '', $key, 1000 );
gsh_assert_eq( $r['valid'], false, 'empty header rejected' );
gsh_assert_eq( $r['error'], 'missing_bearer', 'empty header -> missing_bearer' );

$r = gsh_tp_curriculr_guard_validate_bearer( 'Basic dXNlcjpwYXNz', $key, 1000 );
gsh_assert_eq( $r['error'], 'missing_bearer', 'Basic scheme -> missing_bearer' );

$exp_jwt = gsh_tp_curriculr_jwt_sign( array( 'sub' => 'u1', 'exp' => 100 ), $key );
$r = gsh_tp_curriculr_guard_validate_bearer( 'Bearer ' . $exp_jwt, $key, 1000 );
gsh_assert_eq( $r['error'], 'expired', 'expired token -> expired' );

$r = gsh_tp_curriculr_guard_validate_bearer( 'Bearer ' . $jwt . 'x', $key, 1000 );
gsh_assert_eq( $r['valid'], false, 'tampered token rejected' );

$r = gsh_tp_curriculr_guard_validate_bearer( 'Bearer ' . $jwt, '', 1000 );
gsh_assert_eq( $r['error'], 'no_key', 'empty key -> no_key' );

/* ---------- validate_bearer: iss/aud pass-through (SEC-MED-003) ---------- */
$r = gsh_tp_curriculr_guard_validate_bearer( 'Bearer ' . $jwt, $key, 1000, 'https://wp.test/wp-json/curriculr/v1', 'https://juwagn.github.io/curriculr-planner/' );
gsh_assert_true( $r['valid'], 'validate_bearer with matching expected iss/aud is valid' );

$r = gsh_tp_curriculr_guard_validate_bearer( 'Bearer ' . $jwt, $key, 1000, 'https://wrong.example/', '' );
gsh_assert_eq( $r['error'], 'bad_iss', 'validate_bearer rejects wrong expected iss' );

$r = gsh_tp_curriculr_guard_validate_bearer( 'Bearer ' . $jwt, $key, 1000, '', 'https://wrong.example/' );
gsh_assert_eq( $r['error'], 'bad_aud', 'validate_bearer rejects wrong expected aud' );

/* ---------- guard_perm + current_claims ---------- */
$req = new Gsh_Fake_Guard_Req( array( 'authorization' => 'Bearer ' . $jwt ) );
$res = gsh_tp_curriculr_guard_perm( $req );
gsh_assert_true( $res === true, 'guard_perm returns true for valid token' );
$c = gsh_tp_curriculr_guard_current_claims();
gsh_assert_eq( $c['sub'], 'iserv-sub-001', 'current_claims has correct sub after guard_perm' );

$req_empty = new Gsh_Fake_Guard_Req( array() );
$res_err   = gsh_tp_curriculr_guard_perm( $req_empty );
gsh_assert_true( $res_err instanceof WP_Error, 'guard_perm returns WP_Error on missing auth' );
$stale = gsh_tp_curriculr_guard_current_claims();
gsh_assert_true( $stale === null, 'current_claims null after failed guard_perm' );

/* ---------- guard_perm rejects tokens with wrong iss/aud (SEC-MED-003) ---------- */
$bad_aud_jwt = gsh_tp_curriculr_jwt_sign(
    array(
        'sub'    => 'iserv-sub-002',
        'exp'    => 9999999999,
        'iss'    => 'https://wp.test/wp-json/curriculr/v1',
        'aud'    => 'https://attacker.example/',
    ),
    $key
);
$req_bad_aud = new Gsh_Fake_Guard_Req( array( 'authorization' => 'Bearer ' . $bad_aud_jwt ) );
$res_bad_aud = gsh_tp_curriculr_guard_perm( $req_bad_aud );
gsh_assert_true( $res_bad_aud instanceof WP_Error, 'guard_perm rejects token with wrong aud' );

$bad_iss_jwt = gsh_tp_curriculr_jwt_sign(
    array(
        'sub' => 'iserv-sub-003',
        'exp' => 9999999999,
        'iss' => 'https://evil.example/wp-json/curriculr/v1',
        'aud' => 'https://juwagn.github.io/curriculr-planner/',
    ),
    $key
);
$req_bad_iss = new Gsh_Fake_Guard_Req( array( 'authorization' => 'Bearer ' . $bad_iss_jwt ) );
$res_bad_iss = gsh_tp_curriculr_guard_perm( $req_bad_iss );
gsh_assert_true( $res_bad_iss instanceof WP_Error, 'guard_perm rejects token with wrong iss' );

gsh_test_done();
