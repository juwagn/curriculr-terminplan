<?php
// CORS: the stored planner address is enforced as a bare browser origin, and the
// configured planner is always allowed. The filter must run after WordPress core.
define( 'GSH_TP_CURRICULR_TEST', true );
define( 'CURRICULR_ISERV_BASE_URL', 'https://iserv.example.test' );
define( 'CURRICULR_ISERV_CLIENT_ID', 'synthetic-client' );
define( 'CURRICULR_ISERV_CLIENT_SECRET', 'synthetic-secret' );
define( 'CURRICULR_APP_TOKEN_KEY', 'synthetic-key-for-muster-gesamtschule' );
define( 'CURRICULR_SPA_URL', 'https://Planner.example.test:443/plan/' );
require __DIR__ . '/assert.php';
$GLOBALS['options'] = array();
$GLOBALS['filters'] = array();
function rest_url( $path ) { return 'https://wp.example.test/wp-json/' . $path; }
function get_option( $key, $default = false ) { return $GLOBALS['options'][ $key ] ?? $default; }
function add_action() {}
function register_activation_hook() {}
function register_deactivation_hook() {}
function add_filter( $hook, $callback, $priority = 10 ) { $GLOBALS['filters'][ $hook ][ $callback ] = $priority; }
require __DIR__ . '/../../plugin/curriculr-auth.php';
require __DIR__ . '/../../plugin/curriculr-data-layer.php';

// Core registers rest_send_cors_headers at priority 10 and reflects any origin.
gsh_assert_true( $GLOBALS['filters']['rest_pre_serve_request']['gsh_tp_curriculr_cors_filter'] > 10, 'CORS filter runs after WordPress core' );

$planner = 'https://planner.example.test';
gsh_assert_eq( gsh_tp_curriculr_allowed_origin(), $planner, 'derived origin omits the default port' );
$cases = array(
    'https://juwagn.github.io/'                    => 'https://juwagn.github.io',
    'https://juwagn.github.io/curriculr-planner/'  => 'https://juwagn.github.io',
    'HTTPS://Alt.Example.test:443'                 => 'https://alt.example.test',
    'http://localhost:80/'                         => 'http://localhost',
    ' https://alt.example.test:8443/x '            => 'https://alt.example.test:8443',
    'kein url'                                     => $planner,
);
foreach ( $cases as $stored => $expected ) {
    $GLOBALS['options']['gsh_tp_curriculr_origin'] = $stored;
    gsh_assert_eq( gsh_tp_curriculr_allowed_origin(), $expected, 'stored value becomes a browser origin: ' . $stored );
}

$GLOBALS['options']['gsh_tp_curriculr_origin'] = 'http://localhost:5173';
$apps = gsh_tp_curriculr_apps();
gsh_assert_eq( gsh_tp_curriculr_cors_origin( $planner, gsh_tp_curriculr_allowed_origin(), $apps ), $planner, 'configured planner stays allowed beside a stale stored origin' );
gsh_assert_eq( gsh_tp_curriculr_cors_origin( 'http://localhost:5173', gsh_tp_curriculr_allowed_origin(), $apps ), 'http://localhost:5173', 'stored origin stays allowed' );
gsh_assert_eq( gsh_tp_curriculr_cors_origin( 'https://evil.example', gsh_tp_curriculr_allowed_origin(), $apps ), 'http://localhost:5173', 'foreign origin is not reflected' );
gsh_test_done();
