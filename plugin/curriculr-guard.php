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
 * @param string $expected_iss  Expected iss claim; empty = skip check (backward compat)
 * @param string $expected_aud  Expected aud claim; empty = skip check (backward compat)
 * @return array {valid: bool, claims: array|null, error: string|null}
 */
function gsh_tp_curriculr_guard_validate_bearer( $auth_header, $app_token_key, $now, $expected_iss = '', $expected_aud = '' ) {
    if ( ! preg_match( '/^Bearer\s+(\S+)$/i', (string) $auth_header, $m ) ) {
        return array( 'valid' => false, 'error' => 'missing_bearer' );
    }
    return gsh_tp_curriculr_jwt_verify( $m[1], $app_token_key, $now, $expected_iss, $expected_aud );
}

/**
 * WP permission_callback: validates Bearer app-token; stores claims in global
 * so REST callbacks can read sub/name without re-validating.
 *
 * Returns true on success, WP_Error(401) on any failure.
 *
 * @param WP_REST_Request $req
 * @return true|WP_Error
 */
function gsh_tp_curriculr_guard_perm( $req ) {
    $config       = gsh_tp_curriculr_auth_config();
    $auth         = (string) $req->get_header( 'authorization' );
    $expected_iss = function_exists( 'rest_url' ) ? rest_url( 'curriculr/v1' ) : '';
    $expected_aud = isset( $config['spa_url'] ) ? $config['spa_url'] : '';
    $result       = gsh_tp_curriculr_guard_validate_bearer( $auth, $config['app_token_key'], time(), $expected_iss, $expected_aud );
    if ( ! $result['valid'] ) {
        unset( $GLOBALS['gsh_tp_curriculr_current_claims'] );
        return new WP_Error( 'unauthorized', 'App-Token invalid', array( 'status' => 401 ) );
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

/**
 * Pure: validiert ein Bearer-App-Token für eine bestimmte App (ab 4.42.0).
 * aud muss exakt der App-URL entsprechen (401), mindestens eine Token-Gruppe
 * muss zu den Gruppen der App gehören (403).
 *
 * @param string     $auth_header   Roher Authorization-Header
 * @param string     $app_token_key Signaturschlüssel
 * @param int        $now           Unix-Zeit (injizierbar für Tests)
 * @param string     $expected_iss  Erwarteter iss-Claim
 * @param array|null $app           Registry-Eintrag {url, groups} oder null
 * @return array {valid: bool, claims?: array, error?: string, status: int}
 */
function gsh_tp_curriculr_guard_validate_for_app( $auth_header, $app_token_key, $now, $expected_iss, $app ) {
    if ( ! is_array( $app ) || empty( $app['url'] ) || empty( $app['groups'] ) ) {
        return array( 'valid' => false, 'error' => 'unknown_app', 'status' => 401 );
    }
    $result = gsh_tp_curriculr_guard_validate_bearer( $auth_header, $app_token_key, $now, $expected_iss, $app['url'] );
    if ( ! $result['valid'] ) {
        $result['status'] = 401;
        return $result;
    }
    $groups = ( isset( $result['claims']['groups'] ) && is_array( $result['claims']['groups'] ) ) ? $result['claims']['groups'] : array();
    if ( ! gsh_tp_curriculr_group_check( $groups, $app['groups'] ) ) {
        return array( 'valid' => false, 'error' => 'forbidden', 'status' => 403 );
    }
    $result['status'] = 200;
    return $result;
}

/**
 * WP permission_callback für Routen einer registrierten App, z. B. im
 * Begleit-Plugin: 'permission_callback' => fn( $r ) => gsh_tp_curriculr_guard_for_app( $r, 'klausurplan' ).
 *
 * @param WP_REST_Request $req
 * @param string          $app_key Registry-Schlüssel
 * @return true|WP_Error
 */
function gsh_tp_curriculr_guard_for_app( $req, $app_key ) {
    $config       = gsh_tp_curriculr_auth_config();
    $apps         = gsh_tp_curriculr_apps();
    $app          = ( is_string( $app_key ) && isset( $apps[ $app_key ] ) ) ? $apps[ $app_key ] : null;
    $expected_iss = function_exists( 'rest_url' ) ? rest_url( 'curriculr/v1' ) : '';
    $result       = gsh_tp_curriculr_guard_validate_for_app(
        (string) $req->get_header( 'authorization' ),
        $config['app_token_key'],
        time(),
        $expected_iss,
        $app
    );
    if ( ! $result['valid'] ) {
        unset( $GLOBALS['gsh_tp_curriculr_current_claims'] );
        if ( $result['status'] === 403 ) {
            return new WP_Error( 'forbidden', 'Keine Berechtigung für diese App', array( 'status' => 403 ) );
        }
        return new WP_Error( 'unauthorized', 'App-Token invalid', array( 'status' => 401 ) );
    }
    $GLOBALS['gsh_tp_curriculr_current_claims'] = $result['claims'];
    return true;
}
