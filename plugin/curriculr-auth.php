<?php
/**
 * Curriculr Auth (IServ-SSO)
 *
 * OIDC-Anmeldung über IServ (Confidential Client) und Ausstellung eines
 * kurzlebigen, WP-signierten App-Tokens. Das IServ-Client-Secret und der
 * App-Token-Schlüssel liegen NUR in wp-config.php-Konstanten — nie als Option,
 * nie in einer Antwort. Prozedural, gsh_tp_curriculr_*-Präfix (AGENTS.md).
 *
 * Pure Funktionen (Config, base64url/JWT, Authorize-URL, Gruppen, Nonce) laufen
 * ohne WordPress und werden mit tests/curriculr/test-auth.php geprüft.
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'GSH_TP_CURRICULR_TEST' ) ) {
    if ( PHP_SAPI !== 'cli' ) {
        exit;
    }
}

/* ---------- Config (nur lesen; Secrets aus wp-config.php) ---------- */

function gsh_tp_curriculr_auth_config() {
    $groups = array();
    if ( defined( 'CURRICULR_ALLOWED_GROUPS' ) && CURRICULR_ALLOWED_GROUPS !== '' ) {
        foreach ( explode( ',', CURRICULR_ALLOWED_GROUPS ) as $g ) {
            $g = trim( $g );
            if ( $g !== '' ) {
                $groups[] = $g;
            }
        }
    }
    return array(
        'iserv_base'     => defined( 'CURRICULR_ISERV_BASE_URL' ) ? rtrim( CURRICULR_ISERV_BASE_URL, '/' ) : '',
        'client_id'      => defined( 'CURRICULR_ISERV_CLIENT_ID' ) ? CURRICULR_ISERV_CLIENT_ID : '',
        'client_secret'  => defined( 'CURRICULR_ISERV_CLIENT_SECRET' ) ? CURRICULR_ISERV_CLIENT_SECRET : '',
        'app_token_key'  => defined( 'CURRICULR_APP_TOKEN_KEY' ) ? CURRICULR_APP_TOKEN_KEY : '',
        'spa_url'        => defined( 'CURRICULR_SPA_URL' ) ? CURRICULR_SPA_URL : 'https://juwagn.github.io/curriculr-planner/',
        'redirect_uri'   => rest_url( 'curriculr/v1/auth/callback' ),
        'allowed_groups' => $groups,
        'token_ttl'      => defined( 'CURRICULR_APP_TOKEN_TTL' ) ? (int) CURRICULR_APP_TOKEN_TTL : 1800,
    );
}

function gsh_tp_curriculr_auth_is_configured( $config ) {
    return ! empty( $config['iserv_base'] )
        && ! empty( $config['client_id'] )
        && ! empty( $config['client_secret'] )
        && ! empty( $config['app_token_key'] );
}

/* ---------- Pure: base64url + HS256 JWT (eigenes App-Token) ---------- */

function gsh_tp_curriculr_b64url_encode( $data ) {
    return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
}

function gsh_tp_curriculr_b64url_decode( $data ) {
    $pad = strlen( $data ) % 4;
    if ( $pad ) {
        $data .= str_repeat( '=', 4 - $pad );
    }
    return base64_decode( strtr( $data, '-_', '+/' ) );
}

function gsh_tp_curriculr_jwt_sign( $claims, $key ) {
    $header = gsh_tp_curriculr_b64url_encode( gsh_tp_curriculr_json_encode( array( 'alg' => 'HS256', 'typ' => 'JWT' ) ) );
    $body   = gsh_tp_curriculr_b64url_encode( gsh_tp_curriculr_json_encode( $claims ) );
    $input  = $header . '.' . $body;
    $sig    = hash_hmac( 'sha256', $input, $key, true );
    return $input . '.' . gsh_tp_curriculr_b64url_encode( $sig );
}

function gsh_tp_curriculr_jwt_verify( $jwt, $key, $now ) {
    if ( $key === '' ) {
        return array( 'valid' => false, 'error' => 'no_key' );
    }
    $parts = explode( '.', (string) $jwt );
    if ( count( $parts ) !== 3 ) {
        return array( 'valid' => false, 'error' => 'malformed' );
    }
    list( $header, $body, $sig ) = $parts;
    $expected = gsh_tp_curriculr_b64url_encode( hash_hmac( 'sha256', $header . '.' . $body, $key, true ) );
    if ( ! hash_equals( $expected, $sig ) ) {
        return array( 'valid' => false, 'error' => 'bad_signature' );
    }
    $claims = json_decode( gsh_tp_curriculr_b64url_decode( $body ), true );
    if ( ! is_array( $claims ) ) {
        return array( 'valid' => false, 'error' => 'bad_payload' );
    }
    if ( ! isset( $claims['exp'] ) || (int) $claims['exp'] < (int) $now ) {
        return array( 'valid' => false, 'error' => 'expired' );
    }
    return array( 'valid' => true, 'claims' => $claims );
}

// wp_json_encode existiert nur unter WP; im CLI-Test auf json_encode zurückfallen.
// Eigener Präfix (nicht wp_*) — vermeidet Redeclare-Kollision mit WP-Core.
function gsh_tp_curriculr_json_encode( $d ) {
    return function_exists( 'wp_json_encode' ) ? wp_json_encode( $d ) : json_encode( $d );
}
