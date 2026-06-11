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

/* ---------- Pure: Authorize-URL (OIDC Schritt 2, Spec §4) ---------- */

function gsh_tp_curriculr_build_authorize_url( $config, $state, $nonce ) {
    $params = array(
        'response_type' => 'code',
        'client_id'     => $config['client_id'],
        'redirect_uri'  => $config['redirect_uri'],
        'scope'         => 'openid profile iserv:groups',
        'state'         => $state,
        'nonce'         => $nonce,
    );
    return $config['iserv_base'] . '/iserv/auth/auth?' . http_build_query( $params );
}

/* ---------- Pure: App-Token-Claims (minimal, Datenminimierung Spec §5/§8) ---------- */

function gsh_tp_curriculr_make_app_token_claims( $sub, $name, $groups, $now, $ttl, $iss, $aud ) {
    return array(
        'sub'    => (string) $sub,
        'name'   => (string) $name,
        'groups' => array_values( $groups ),
        'iat'    => (int) $now,
        'exp'    => (int) $now + (int) $ttl,
        'iss'    => (string) $iss,
        'aud'    => (string) $aud,
    );
}

/* ---------- Pure: iserv:groups normalisieren + Whitelist (Spec §4 Filter #2) ---------- */

function gsh_tp_curriculr_extract_groups( $claim ) {
    $out = array();
    if ( ! is_array( $claim ) ) {
        return $out;
    }
    foreach ( $claim as $g ) {
        if ( is_string( $g ) ) {
            $out[] = $g;
        } elseif ( is_array( $g ) ) {
            // Bevorzugung: maschinenlesbarer Account-Schlüssel, dann sprechende Namen.
            foreach ( array( 'act', 'id', 'name', 'displayName' ) as $k ) {
                if ( isset( $g[ $k ] ) && is_string( $g[ $k ] ) && $g[ $k ] !== '' ) {
                    $out[] = $g[ $k ];
                    break;
                }
            }
        }
    }
    return $out;
}

function gsh_tp_curriculr_group_check( $user_groups, $whitelist ) {
    if ( empty( $whitelist ) ) {
        return false; // Fail-closed: Fehlkonfiguration darf nie Zugang gewähren.
    }
    foreach ( (array) $user_groups as $g ) {
        if ( in_array( $g, $whitelist, true ) ) {
            return true;
        }
    }
    return false;
}

/* ---------- Pure: JWT-Payload lesen (id_token-Nonce; KEINE Sig-Prüfung) ---------- */
// Hinweis: Volle RS256/JWKS-Signaturprüfung des IServ-id_token ist Härtung für
// später (Spec §8). In M1 kommt das Token serverseitig über TLS direkt von
// IServ; die Nonce bindet es an unsere Session, Identität/Gruppen kommen aus
// dem userinfo-Endpunkt (ebenfalls TLS, direkt).

function gsh_tp_curriculr_jwt_payload( $jwt ) {
    $parts = explode( '.', (string) $jwt );
    if ( count( $parts ) < 2 ) {
        return null;
    }
    $payload = json_decode( gsh_tp_curriculr_b64url_decode( $parts[1] ), true );
    return is_array( $payload ) ? $payload : null;
}

/* ---------- WP-HTTP: code→token (serverseitig, MIT Secret) + userinfo ---------- */

function gsh_tp_curriculr_oidc_exchange_code( $config, $code ) {
    $resp = wp_remote_post(
        $config['iserv_base'] . '/iserv/auth/public/token',
        array(
            'timeout' => 15,
            'body'    => array(
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => $config['redirect_uri'],
                'client_id'     => $config['client_id'],
                'client_secret' => $config['client_secret'],
            ),
        )
    );
    if ( is_wp_error( $resp ) ) {
        return $resp;
    }
    $body = json_decode( wp_remote_retrieve_body( $resp ), true );
    return is_array( $body ) ? $body : new WP_Error( 'bad_token_response', 'invalid token response' );
}

function gsh_tp_curriculr_oidc_userinfo( $config, $access_token ) {
    $resp = wp_remote_get(
        $config['iserv_base'] . '/iserv/auth/userinfo',
        array(
            'timeout' => 15,
            'headers' => array( 'Authorization' => 'Bearer ' . $access_token ),
        )
    );
    if ( is_wp_error( $resp ) ) {
        return $resp;
    }
    $body = json_decode( wp_remote_retrieve_body( $resp ), true );
    return is_array( $body ) ? $body : new WP_Error( 'bad_userinfo', 'invalid userinfo response' );
}

/* ---------- WP: REST-Routen (unauth — der Nutzer hat noch kein App-Token) ---------- */

function gsh_tp_curriculr_register_auth_routes() {
    register_rest_route(
        'curriculr/v1',
        '/auth/login',
        array(
            'methods'             => 'GET',
            'callback'            => 'gsh_tp_curriculr_rest_auth_login',
            'permission_callback' => '__return_true',
        )
    );
    register_rest_route(
        'curriculr/v1',
        '/auth/callback',
        array(
            'methods'             => 'GET',
            'callback'            => 'gsh_tp_curriculr_rest_auth_callback',
            'permission_callback' => '__return_true',
        )
    );
    register_rest_route(
        'curriculr/v1',
        '/auth/token',
        array(
            'methods'             => 'POST',
            'callback'            => 'gsh_tp_curriculr_rest_auth_token',
            'permission_callback' => '__return_true',
        )
    );
    register_rest_route(
        'curriculr/v1',
        '/auth/logout',
        array(
            'methods'             => 'POST',
            'callback'            => 'gsh_tp_curriculr_rest_auth_logout',
            'permission_callback' => '__return_true',
        )
    );
}

/* ---------- WP: Redirect-Helfer (Token NIE in der URL — Spec §5) ---------- */

function gsh_tp_curriculr_spa_redirect_url( $spa_url, $fragment ) {
    return rtrim( $spa_url, '/' ) . '/' . $fragment;
}

function gsh_tp_curriculr_auth_fail( $config, $reason ) {
    wp_redirect( gsh_tp_curriculr_spa_redirect_url( $config['spa_url'], '#auth_error=' . rawurlencode( $reason ) ) );
    exit;
}

/* ---------- WP: /auth/login — 302 → IServ ---------- */

function gsh_tp_curriculr_rest_auth_login( $req ) {
    $config = gsh_tp_curriculr_auth_config();
    if ( ! gsh_tp_curriculr_auth_is_configured( $config ) ) {
        return new WP_REST_Response( array( 'error' => 'sso_not_configured' ), 503 );
    }
    $state = wp_generate_password( 40, false, false );
    $nonce = wp_generate_password( 40, false, false );
    // state→nonce, 10 Min gültig, Single-Use (im Callback gelöscht).
    set_transient( 'gsh_tp_cur_oauth_' . $state, array( 'nonce' => $nonce ), 600 );
    wp_redirect( gsh_tp_curriculr_build_authorize_url( $config, $state, $nonce ) );
    exit;
}

/* ---------- WP: /auth/callback — state+nonce, code→token, userinfo, Gruppen, App-Token ---------- */

function gsh_tp_curriculr_rest_auth_callback( $req ) {
    $config = gsh_tp_curriculr_auth_config();
    if ( ! gsh_tp_curriculr_auth_is_configured( $config ) ) {
        return new WP_REST_Response( array( 'error' => 'sso_not_configured' ), 503 );
    }
    $state = isset( $req['state'] ) ? (string) $req['state'] : '';
    $code  = isset( $req['code'] ) ? (string) $req['code'] : '';
    $key   = 'gsh_tp_cur_oauth_' . $state;
    $saved = $state ? get_transient( $key ) : false;
    if ( ! $saved || $code === '' ) {
        gsh_tp_curriculr_auth_fail( $config, 'state' );
    }
    delete_transient( $key ); // Single-Use gegen Replay.

    $tokens = gsh_tp_curriculr_oidc_exchange_code( $config, $code );
    if ( is_wp_error( $tokens ) || empty( $tokens['access_token'] ) ) {
        gsh_tp_curriculr_auth_fail( $config, 'token' );
    }

    // Nonce-Bindung: id_token.nonce muss zur gespeicherten Nonce passen.
    // Pflicht – kein id_token → Fehler (verhindert Bypass ohne PKCE).
    if ( empty( $tokens['id_token'] ) ) {
        gsh_tp_curriculr_auth_fail( $config, 'nonce' );
    }
    $idp = gsh_tp_curriculr_jwt_payload( $tokens['id_token'] );
    if ( ! $idp || ! isset( $idp['nonce'] ) || ! hash_equals( (string) $saved['nonce'], (string) $idp['nonce'] ) ) {
        gsh_tp_curriculr_auth_fail( $config, 'nonce' );
    }

    $info = gsh_tp_curriculr_oidc_userinfo( $config, $tokens['access_token'] );
    if ( is_wp_error( $info ) || empty( $info['sub'] ) ) {
        gsh_tp_curriculr_auth_fail( $config, 'userinfo' );
    }

    $groups = gsh_tp_curriculr_extract_groups( isset( $info['groups'] ) ? $info['groups'] : array() );
    if ( ! gsh_tp_curriculr_group_check( $groups, $config['allowed_groups'] ) ) {
        gsh_tp_curriculr_auth_fail( $config, 'forbidden' );
    }

    $name = '';
    foreach ( array( 'name', 'preferred_username', 'nickname' ) as $k ) {
        if ( ! empty( $info[ $k ] ) ) {
            $name = (string) $info[ $k ];
            break;
        }
    }
    if ( $name === '' ) {
        $name = (string) $info['sub'];
    }

    $claims    = gsh_tp_curriculr_make_app_token_claims(
        $info['sub'],
        $name,
        $groups,
        time(),
        $config['token_ttl'],
        rest_url( 'curriculr/v1' ),
        $config['spa_url']
    );
    $app_token = gsh_tp_curriculr_jwt_sign( $claims, $config['app_token_key'] );

    // Einmal-Handoff: 60 s, Single-Use. Nur DIESES Geheimnis steht im Fragment,
    // nie das App-Token (kein Referer/History-Leak).
    $handoff = wp_generate_password( 48, false, false );
    set_transient( 'gsh_tp_cur_handoff_' . $handoff, $app_token, 60 );
    wp_redirect( gsh_tp_curriculr_spa_redirect_url( $config['spa_url'], '#auth=' . rawurlencode( $handoff ) ) );
    exit;
}

/* ---------- WP: /auth/token — Einmal-Handoff → App-Token (Fetch, CORS) ---------- */

function gsh_tp_curriculr_rest_auth_token( $req ) {
    $body     = $req->get_json_params();
    $exchange = ( is_array( $body ) && isset( $body['exchange'] ) ) ? (string) $body['exchange'] : '';
    if ( $exchange === '' ) {
        return new WP_REST_Response( array( 'error' => 'missing_exchange' ), 400 );
    }
    $key       = 'gsh_tp_cur_handoff_' . $exchange;
    $app_token = get_transient( $key );
    if ( ! $app_token ) {
        return new WP_REST_Response( array( 'error' => 'invalid_or_expired' ), 401 );
    }
    delete_transient( $key ); // Single-Use.
    return new WP_REST_Response( array( 'token' => $app_token ), 200 );
}

/* ---------- WP: /auth/logout — App-Token lebt nur im SPA-RAM (stateless) ---------- */

function gsh_tp_curriculr_rest_auth_logout( $req ) {
    // Serverseitig nichts zu invalidieren: kurzlebiges Token, kein Server-State.
    // Optional später: IServ end_session_endpoint. M1 = ok-Bestätigung.
    return new WP_REST_Response( array( 'status' => 'ok' ), 200 );
}

/* ---------- WP: Hooks (nur unter WordPress aktiv) ---------- */

if ( function_exists( 'add_action' ) ) {
    add_action( 'rest_api_init', 'gsh_tp_curriculr_register_auth_routes' );
}
