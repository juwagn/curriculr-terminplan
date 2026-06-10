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
