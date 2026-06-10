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

gsh_test_done();
