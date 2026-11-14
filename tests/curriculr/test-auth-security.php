<?php
// Regression coverage for missing SPA configuration and public-route limits.
define( 'GSH_TP_CURRICULR_TEST', true );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'CURRICULR_ISERV_BASE_URL', 'https://iserv.example.test' );
define( 'CURRICULR_ISERV_CLIENT_ID', 'synthetic-client' );
define( 'CURRICULR_ISERV_CLIENT_SECRET', 'synthetic-secret' );
define( 'CURRICULR_APP_TOKEN_KEY', 'synthetic-key-for-muster-gesamtschule' );
define( 'CURRICULR_ALLOWED_GROUPS', 'Schulleitung' );
$mode = $argv[1] ?? 'missing';
if ( $mode !== 'missing' ) {
    define( 'CURRICULR_SPA_URL', 'https://planner.example.test/plan/' );
}
require __DIR__ . '/assert.php';
$GLOBALS['transients'] = array();
$GLOBALS['options'] = array();
$GLOBALS['writes'] = 0;
function rest_url( $path ) { return 'https://wp.example.test/wp-json/' . $path; }
function get_option( $key, $default = false ) { return $GLOBALS['options'][ $key ] ?? $default; }
function get_transient( $key ) { return $GLOBALS['transients'][ $key ] ?? false; }
function set_transient( $key, $value, $ttl ) { $GLOBALS['transients'][ $key ] = $value; $GLOBALS['writes']++; return true; }
function delete_transient( $key ) { unset( $GLOBALS['transients'][ $key ] ); }
function wp_salt( $scheme = 'auth' ) { return 'synthetic-wordpress-salt'; }
function wp_unslash( $s ) { return $s; }
function sanitize_key( $s ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $s ) ); }
function sanitize_file_name( $s ) { return $s; }
function sanitize_text_field( $s ) { return trim( strip_tags( $s ) ); }
function esc_url_raw( $s ) { return $s; }
function wp_generate_password( $n ) { return str_repeat( 'a', $n ); }
class SecurityRedirect extends Exception {}
function wp_redirect( $url ) { throw new SecurityRedirect( $url ); }
class WP_REST_Response {
    public $data; public $status;
    function __construct( $data, $status = 200 ) { $this->data = $data; $this->status = $status; }
}
class SecurityRequest {
    function get_json_params() { return array( 'exchange' => 'synthetic-handoff' ); }
}
class SecurityWpdb {
    public $prefix = 'wp_';
    function prepare( $sql, $sj ) { return $sj; }
    function get_row( $sj, $format ) {
        return $sj === 'missing' ? null : array( 'feed_token' => 'synthetic-feed-token', 'json' => json_encode( array( 'events' => array(), 'categories' => array() ) ) );
    }
}
$GLOBALS['wpdb'] = new SecurityWpdb();
require __DIR__ . '/../../plugin/curriculr-auth.php';
require __DIR__ . '/../../plugin/curriculr-data-layer.php';

if ( in_array( $mode, array( 'feed', 'feed_group' ), true ) ) {
    // Valid calendars still pass a saturated error bucket without a write.
    for ( $i = 0; $i < CURRICULR_TP_FEED_ERROR_LIMIT; $i++ ) { curriculr_tp_rate_limit( 'feed', CURRICULR_TP_FEED_ERROR_LIMIT, CURRICULR_TP_RATE_WINDOW ); }
    $writes = $GLOBALS['writes'];
    register_shutdown_function( function() use ( $writes ) {
        if ( $GLOBALS['writes'] !== $writes ) { fwrite( STDERR, 'FAIL: valid feed counted' ); exit( 1 ); }
        $output = ob_get_clean();
        if ( strpos( $output, 'BEGIN:VCALENDAR' ) === false ) { fwrite( STDERR, 'FAIL: valid feed blocked' ); exit( 1 ); }
        echo "PASS: valid feed bypasses saturated bucket without counting\n";
    } );
    ob_start();
    call_user_func( 'gsh_tp_curriculr_rest_' . $mode, array( 'sj' => 'sj_2026_27', 'token' => 'synthetic-feed-token', 'group' => 'Eltern' ) );
}

if ( $mode === 'missing' ) {
    $config = gsh_tp_curriculr_auth_config();
    gsh_assert_eq( $config['spa_url'], '', 'missing SPA has no developer fallback' );
    gsh_assert_eq( gsh_tp_curriculr_auth_is_configured( $config ), false, 'missing SPA disables authentication' );
    $GLOBALS['options']['gsh_tp_curriculr_origin'] = 'https://old.example.test';
    gsh_assert_eq( gsh_tp_curriculr_allowed_origin(), '', 'missing configuration suppresses even stored CORS origin' );
    foreach ( array( 'login', 'callback', 'token' ) as $route ) {
        $GLOBALS['transients']['gsh_tp_cur_handoff_synthetic-handoff'] = 'synthetic-app-token';
        try {
            $response = call_user_func( 'gsh_tp_curriculr_rest_auth_' . $route, $route === 'token' ? new SecurityRequest() : array() );
            gsh_assert_eq( $response->status, 503, $route . ': missing configuration returns 503' );
            gsh_assert_eq( $response->data, array( 'error' => 'sso_not_configured' ), $route . ': no token returned' );
        } catch ( SecurityRedirect $e ) {
            gsh_assert_true( false, $route . ': must not redirect without SPA config' );
        }
    }
    gsh_assert_eq( $GLOBALS['writes'], 0, 'no login state created without configuration' );
    gsh_test_done();
    $status = 0;
    passthru( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' configured', $status );
    exit( $status );
}

$config = gsh_tp_curriculr_auth_config();
gsh_assert_true( gsh_tp_curriculr_auth_is_configured( $config ), 'explicit HTTPS config works' );
foreach ( array( '', 'http://planner.example.test/', 'javascript:alert(1)', 'https://', 'https://user:pass@example.test/', 'https://example.test/#fragment', 'https://example.test/?query=1', 'https://bad host.test/', 'https://example.test:99999/', 'https://-bad.test/' ) as $url ) {
    $invalid = array_merge( $config, array( 'spa_url' => $url ) );
    gsh_assert_eq( gsh_tp_curriculr_auth_is_configured( $invalid ), false, 'invalid SPA URL rejected: ' . $url );
}
gsh_assert_eq( gsh_tp_curriculr_allowed_origin(), 'https://planner.example.test', 'CORS default derives from configured SPA' );
$GLOBALS['options']['gsh_tp_curriculr_origin'] = '';
gsh_assert_eq( gsh_tp_curriculr_allowed_origin(), 'https://planner.example.test', 'blank saved CORS origin derives from configured SPA' );
$GLOBALS['options']['gsh_tp_curriculr_origin'] = 'http://localhost:5173';
gsh_assert_eq( gsh_tp_curriculr_allowed_origin(), 'http://localhost:5173', 'explicit development CORS origin stays supported' );

if ( ! function_exists( 'curriculr_tp_rate_limit' ) ) {
    gsh_assert_true( false, 'shared rate limiter exists' );
    gsh_test_done();
}
$_SERVER['REMOTE_ADDR'] = '192.0.2.15';
$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.10';
for ( $i = 0; $i < 3; $i++ ) {
    gsh_assert_true( curriculr_tp_rate_limit( 'test', 3, 60, 1000 ), 'limit accepts first N attempts' );
}
gsh_assert_eq( curriculr_tp_rate_limit( 'test', 3, 60, 1001 ), false, 'attempt N+1 rejected' );
$stored = json_encode( $GLOBALS['transients'] );
gsh_assert_eq( strpos( $stored, '192.0.2.' ), false, 'no plaintext address persisted' );
$_SERVER['REMOTE_ADDR'] = '192.0.2.200';
gsh_assert_eq( curriculr_tp_rate_limit( 'test', 3, 60, 1001 ), false, 'IPv4 /24 shares bucket' );
gsh_assert_eq( curriculr_tp_rate_limit( 'test', 3, 60, 1060 ), true, 'fixed window expires without sliding extension' );
$_SERVER['REMOTE_ADDR'] = '2001:db8:abcd:12::1';
gsh_assert_true( curriculr_tp_rate_limit( 'ipv6', 1, 60, 1000 ), 'IPv6 accepted' );
$_SERVER['REMOTE_ADDR'] = '2001:db8:abcd:12::abcd';
gsh_assert_eq( curriculr_tp_rate_limit( 'ipv6', 1, 60, 1001 ), false, 'IPv6 /64 shares bucket' );
$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.5';
gsh_assert_eq( curriculr_tp_rate_limit( 'ipv6', 1, 60, 1001 ), false, 'untrusted forwarded address cannot bypass limit' );

$GLOBALS['transients'] = array();
$_SERVER['REMOTE_ADDR'] = '192.0.2.15';
for ( $i = 0; $i < CURRICULR_TP_AUTH_LOGIN_LIMIT; $i++ ) {
    try { gsh_tp_curriculr_rest_auth_login( array() ); } catch ( SecurityRedirect $e ) {
        if ( $i === 0 ) { gsh_assert_true( strpos( $e->getMessage(), 'https://iserv.example.test/' ) === 0, 'configured login redirects to IServ' ); }
    }
}
gsh_assert_eq( gsh_tp_curriculr_rest_auth_login( array() )->status, 429, 'login limit returns 429' );
$GLOBALS['transients'] = array();
$GLOBALS['transients']['gsh_tp_cur_handoff_synthetic-handoff'] = 'synthetic-app-token';
gsh_assert_eq( gsh_tp_curriculr_rest_auth_token( new SecurityRequest() )->data, array( 'token' => 'synthetic-app-token' ), 'valid handoff remains unchanged' );
for ( $i = 1; $i < CURRICULR_TP_AUTH_TOKEN_LIMIT; $i++ ) { gsh_tp_curriculr_rest_auth_token( new SecurityRequest() ); }
gsh_assert_eq( gsh_tp_curriculr_rest_auth_token( new SecurityRequest() )->status, 429, 'token limit returns 429' );

$GLOBALS['transients'] = array();
for ( $i = 0; $i < CURRICULR_TP_FEED_ERROR_LIMIT; $i++ ) {
    $r = gsh_tp_curriculr_rest_feed( array( 'sj' => 'sj_2026_27', 'token' => 'wrong' ) );
}
gsh_assert_eq( $r->status, 404, 'first N feed failures keep existing response' );
gsh_assert_eq( gsh_tp_curriculr_rest_feed_group( array( 'sj' => 'missing', 'token' => 'wrong', 'group' => 'Eltern' ) )->status, 429, 'group and full feed share error limit' );
foreach ( $GLOBALS['transients'] as &$entry ) { $entry['expires'] = time() - 1; } unset( $entry );
gsh_assert_eq( gsh_tp_curriculr_rest_feed( array( 'sj' => 'missing', 'token' => 'wrong' ) )->status, 404, 'feed attempts resume after window' );
gsh_test_done();
foreach ( array( 'feed', 'feed_group' ) as $route ) {
    if ( $mode === 'configured' ) {
        passthru( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' ' . escapeshellarg( $route ), $status );
        if ( $status !== 0 ) { exit( $status ); }
    }
}
