<?php
/**
 * Curriculr REST Guard
 *
 * Validates app-token Bearer tokens on protected curriculr/v1 REST routes.
 * Requires gsh_tp_curriculr_jwt_verify() and gsh_tp_curriculr_auth_config()
 * from curriculr-auth.php, which gsh-terminplan.php loads first.
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'GSH_TP_CURRICULR_TEST' ) ) {
    if ( PHP_SAPI !== 'cli' ) {
        exit;
    }
}

/**
 * Pure: extract Bearer token from Authorization header and verify it.
 *
 * @param string $auth_header   Raw Authorization header value (e.g. "Bearer eyJ...")
 * @param string $app_token_key Signing key
 * @param int    $now           Unix timestamp (injectable for tests)
 * @return array {valid: bool, claims: array|null, error: string|null}
 */
function gsh_tp_curriculr_guard_validate_bearer( $auth_header, $app_token_key, $now ) {
    if ( ! preg_match( '/^Bearer\s+(\S+)$/i', (string) $auth_header, $m ) ) {
        return array( 'valid' => false, 'error' => 'missing_bearer' );
    }
    return gsh_tp_curriculr_jwt_verify( $m[1], $app_token_key, $now );
}

/**
 * WP permission_callback: validates Bearer app-token; stores claims in global
 * so REST callbacks can read sub/name without re-validating.
 *
 * Returns true on success, WP_Error(403) on any failure.
 *
 * @param WP_REST_Request $req
 * @return true|WP_Error
 */
function gsh_tp_curriculr_guard_perm( $req ) {
    $config = gsh_tp_curriculr_auth_config();
    $auth   = (string) $req->get_header( 'authorization' );
    $result = gsh_tp_curriculr_guard_validate_bearer( $auth, $config['app_token_key'], time() );
    if ( ! $result['valid'] ) {
        unset( $GLOBALS['gsh_tp_curriculr_current_claims'] );
        return new WP_Error( 'forbidden', 'App-Token invalid', array( 'status' => 403 ) );
    }
    $GLOBALS['gsh_tp_curriculr_current_claims'] = $result['claims'];
    return true;
}

/**
 * Returns validated claims from the current request, or null if no guard ran.
 *
 * @return array|null
 */
function gsh_tp_curriculr_guard_current_claims() {
    return isset( $GLOBALS['gsh_tp_curriculr_current_claims'] )
        ? $GLOBALS['gsh_tp_curriculr_current_claims']
        : null;
}
