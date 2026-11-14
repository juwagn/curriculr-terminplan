<?php
// Missing or invalid SPA configuration must never disable audience validation.
define( 'GSH_TP_CURRICULR_TEST', true );
define( 'CURRICULR_ISERV_BASE_URL', 'https://iserv.example.test' );
define( 'CURRICULR_ISERV_CLIENT_ID', 'synthetic-client' );
define( 'CURRICULR_ISERV_CLIENT_SECRET', 'synthetic-secret' );
define( 'CURRICULR_APP_TOKEN_KEY', 'synthetic-muster-gesamtschule-signing-key' );
$mode = $argv[1] ?? 'missing';
if ( $mode !== 'missing' ) { define( 'CURRICULR_SPA_URL', 'http://planner.example.test/' ); }
require __DIR__ . '/assert.php';
function rest_url( $path ) { return 'https://wp.example.test/wp-json/' . $path; }
class WP_Error {
    public $data;
    function __construct( $code, $message, $data ) { $this->data = $data; }
}
require __DIR__ . '/../../plugin/curriculr-auth.php';
require __DIR__ . '/../../plugin/curriculr-guard.php';
foreach ( array( 'https://other-app.example.test/', 'http://planner.example.test/', '' ) as $audience ) {
    $token = gsh_tp_curriculr_jwt_sign( array(
        'sub' => 'synthetic-user', 'aud' => $audience,
        'iss' => rest_url( 'curriculr/v1' ), 'exp' => time() + 300,
    ), CURRICULR_APP_TOKEN_KEY );
    $req = new class( $token ) {
        private $token;
        function __construct( $token ) { $this->token = $token; }
        function get_header( $name ) { return 'Bearer ' . $this->token; }
    };
    $GLOBALS['gsh_tp_curriculr_current_claims'] = array( 'sub' => 'stale' );
    $result = gsh_tp_curriculr_guard_perm( $req );
    gsh_assert_true( $result instanceof WP_Error, $mode . ': guard rejects signed token without valid configuration' );
    gsh_assert_eq( $result instanceof WP_Error ? $result->data['status'] : null, 401, 'guard preserves unauthorized response' );
    gsh_assert_eq( gsh_tp_curriculr_guard_current_claims(), null, 'configuration failure clears stale claims' );
}
gsh_test_done();
if ( $mode === 'missing' ) {
    passthru( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' invalid', $status );
    exit( $status );
}
