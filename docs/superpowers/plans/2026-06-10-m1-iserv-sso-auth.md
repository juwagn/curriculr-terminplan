# M1 — IServ-SSO Auth-Endpunkte (WP-Plugin) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the WordPress-side OIDC auth layer so the Planner SPA can log a user in via IServ (confidential client) and receive a short-lived, WP-signed app-token — without ever exposing the IServ client-secret or the app-token in a URL.

**Architecture:** New procedural module `plugin/curriculr-auth.php` (sibling to `curriculr-data-layer.php`, same `gsh_tp_curriculr_*` prefix, same CLI-testable-pure-core convention). Secrets live in `wp-config.php` constants. Four REST routes under the existing `curriculr/v1` namespace: `GET /auth/login` (302→IServ), `GET /auth/callback` (state+nonce check, server-side `code→token` exchange with secret, userinfo, group whitelist, mint HS256 app-token, store under a one-time handoff secret, 302→SPA with the secret in the URL fragment), `POST /auth/token` (SPA exchanges the one-time secret for the app-token — token never touches a URL), `POST /auth/logout` (stateless ack). Pure functions (JWT, authorize-URL, group-check, claims, nonce) are unit-tested with the dependency-free `tests/curriculr/assert.php` harness; the redirect/`exit` handlers and live HTTP are `php -l`-checked + smoke-tested on a real install.

**Tech Stack:** PHP 7.4+ (no Composer/PHPUnit), WordPress REST API, WP HTTP API (`wp_remote_post`/`wp_remote_get`), WP transients for state/nonce + handoff, HS256 JWT via `hash_hmac` (no external lib).

---

## Why these design choices (read before starting)

- **Why HS256 (symmetric) for the app-token, not RS256:** WP both *mints* and *validates* its own app-token (M2 guard). A shared secret signed/verified by the same server is the simplest correct choice. No keypair, no JWKS for *our* token.
- **Why userinfo for identity+groups instead of verifying the IServ `id_token` signature in M1:** The `code→token` exchange already happens server-side over TLS directly against IServ, and the `access_token` is then sent (again over TLS, directly to IServ) to the `userinfo` endpoint. Identity/groups read this way are trustworthy in transit. We still bind the login to our session by checking `id_token.nonce`. Full RS256/JWKS signature verification of the `id_token` is a hardening item flagged for later (spec §8), not required to be secure in M1.
- **Why the one-time handoff secret (`/auth/token`):** Spec §5 forbids the app-token landing in the browser history/Referer. The callback puts only a single-use, 60-second handoff secret in the URL *fragment* (`#auth=...`, fragments are not sent in Referer and not logged server-side), which the SPA immediately POSTs to `/auth/token` to receive the actual app-token in a response body. The handoff transient is deleted on first use.
- **Why fail-closed group check:** An empty whitelist returns `false` (deny). A misconfiguration must never grant access.
- **Secrets:** `CURRICULR_ISERV_CLIENT_SECRET` and `CURRICULR_APP_TOKEN_KEY` are read from `wp-config.php` constants only — never an option, never echoed back in any response or admin screen.

---

## File Structure

- **Create** `plugin/curriculr-auth.php` — the whole auth module. Pure helpers (config, base64url, JWT, authorize-URL, group extract/check, nonce) + thin HTTP wrappers + 4 REST handlers + route registration. One responsibility: authentication.
- **Create** `tests/curriculr/test-auth.php` — dependency-free unit tests for every pure function + the testable `POST /auth/token` handler, with array-backed transient + overridable `wp_remote_*` stubs.
- **Create** `docs/anleitung-iserv-sso.md` — admin setup guide (fills spec §12).
- **Modify** `plugin/gsh-terminplan.php` — `require_once` the new module; bump `GSH_TP_VERSION` `4.9.0`→`4.10.0` (+ header + changelog); add a read-only "IServ-SSO" status panel and the Datenschutz/Vibecoding section (§9, plugin half) to the System tab.
- **Modify** `plugin/curriculr-data-layer.php:326` — add `POST` to the CORS `Access-Control-Allow-Methods` list (the SPA's `/auth/token` is a cross-origin POST).
- **Modify (other repo)** `../curriculr-planner/docs/superpowers/specs/2026-06-10-multiuser-iserv-sso-design.md` §12 — replace the placeholder with the finalized steps.

---

## Configuration contract (wp-config.php)

The admin sets these in `wp-config.php` (Task 11 surfaces their status in the UI):

```php
define( 'CURRICULR_ISERV_BASE_URL',     'https://<schule>.iserv.de' );      // ohne /iserv-Suffix
define( 'CURRICULR_ISERV_CLIENT_ID',    'xxxxxxxx' );
define( 'CURRICULR_ISERV_CLIENT_SECRET','yyyyyyyy' );                        // GEHEIM
define( 'CURRICULR_APP_TOKEN_KEY',      '<32+ random bytes>' );             // GEHEIM, signiert App-Token
define( 'CURRICULR_SPA_URL',            'https://juwagn.github.io/curriculr-planner/' );
define( 'CURRICULR_ALLOWED_GROUPS',     'Schulleitung' );                    // Komma-Liste
// optional:
define( 'CURRICULR_APP_TOKEN_TTL',      1800 );                              // Sekunden, Default 1800
```

---

### Task 1: Auth-Modul-Gerüst + Config-Reader + Test-Bootstrap

**Files:**
- Create: `plugin/curriculr-auth.php`
- Test: `tests/curriculr/test-auth.php`

- [ ] **Step 1: Write the failing test (bootstrap + config reader)**

Create `tests/curriculr/test-auth.php`:

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/curriculr/test-auth.php`
Expected: FAIL — fatal error, `gsh_tp_curriculr_auth_config()` undefined (file not yet created).

- [ ] **Step 3: Write minimal implementation**

Create `plugin/curriculr-auth.php`:

```php
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
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php tests/curriculr/test-auth.php`
Expected: PASS — all `config*` + `is_configured*` assertions PASS, `ALL PASS`.

- [ ] **Step 5: Commit**

```bash
git add plugin/curriculr-auth.php tests/curriculr/test-auth.php
git commit -m "feat(auth): IServ-SSO Auth-Modul-Gerüst + Config-Reader (M1 Task 1)"
```

---

### Task 2: base64url + HS256 JWT sign/verify

**Files:**
- Modify: `plugin/curriculr-auth.php`
- Test: `tests/curriculr/test-auth.php`

- [ ] **Step 1: Write the failing test**

Append before `gsh_test_done();` in `tests/curriculr/test-auth.php`:

```php
/* ---------- base64url + HS256 JWT ---------- */
gsh_assert_eq( gsh_tp_curriculr_b64url_encode( 'A?B>C' ), 'QT9CPkM', 'b64url_encode strips padding + uses -_ alphabet' );
gsh_assert_eq( gsh_tp_curriculr_b64url_decode( 'QT9CPkM' ), 'A?B>C', 'b64url_decode round-trips' );

$key   = 'k0123456789abcdef0123456789abcdef';
$jwt   = gsh_tp_curriculr_jwt_sign( array( 'sub' => 'u1', 'exp' => 9999999999 ), $key );
gsh_assert_eq( substr_count( $jwt, '.' ), 2, 'jwt has three dot-separated parts' );

$ok = gsh_tp_curriculr_jwt_verify( $jwt, $key, 1000 );
gsh_assert_true( $ok['valid'], 'jwt verifies with correct key before exp' );
gsh_assert_eq( $ok['claims']['sub'], 'u1', 'jwt verify returns claims' );

$bad_key = gsh_tp_curriculr_jwt_verify( $jwt, 'wrong-key', 1000 );
gsh_assert_eq( $bad_key['valid'], false, 'jwt rejects wrong key' );
gsh_assert_eq( $bad_key['error'], 'bad_signature', 'jwt wrong key -> bad_signature' );

$expired = gsh_tp_curriculr_jwt_verify( gsh_tp_curriculr_jwt_sign( array( 'exp' => 500 ), $key ), $key, 1000 );
gsh_assert_eq( $expired['error'], 'expired', 'jwt past exp -> expired' );

$tampered = gsh_tp_curriculr_jwt_verify( $jwt . 'x', $key, 1000 );
gsh_assert_eq( $tampered['valid'], false, 'tampered signature rejected' );

$malformed = gsh_tp_curriculr_jwt_verify( 'not-a-jwt', $key, 1000 );
gsh_assert_eq( $malformed['error'], 'malformed', 'non-jwt -> malformed' );
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/curriculr/test-auth.php`
Expected: FAIL — `gsh_tp_curriculr_b64url_encode()` undefined.

- [ ] **Step 3: Write minimal implementation**

Append to `plugin/curriculr-auth.php`:

```php
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
    $header = gsh_tp_curriculr_b64url_encode( wp_json_encode_compat( array( 'alg' => 'HS256', 'typ' => 'JWT' ) ) );
    $body   = gsh_tp_curriculr_b64url_encode( wp_json_encode_compat( $claims ) );
    $input  = $header . '.' . $body;
    $sig    = hash_hmac( 'sha256', $input, $key, true );
    return $input . '.' . gsh_tp_curriculr_b64url_encode( $sig );
}

function gsh_tp_curriculr_jwt_verify( $jwt, $key, $now ) {
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
function wp_json_encode_compat( $d ) {
    return function_exists( 'wp_json_encode' ) ? wp_json_encode( $d ) : json_encode( $d );
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php tests/curriculr/test-auth.php`
Expected: PASS — all JWT assertions PASS.

- [ ] **Step 5: Commit**

```bash
git add plugin/curriculr-auth.php tests/curriculr/test-auth.php
git commit -m "feat(auth): base64url + HS256 JWT sign/verify (M1 Task 2)"
```

---

### Task 3: App-Token-Claims-Builder

**Files:**
- Modify: `plugin/curriculr-auth.php`
- Test: `tests/curriculr/test-auth.php`

- [ ] **Step 1: Write the failing test**

Append before `gsh_test_done();`:

```php
/* ---------- App-Token-Claims ---------- */
$claims = gsh_tp_curriculr_make_app_token_claims( 'iserv-uuid-1', 'Frau Beispiel', array( 'Schulleitung' ), 1000, 1800, 'https://wp.test/wp-json/curriculr/v1', 'https://juwagn.github.io/curriculr-planner/' );
gsh_assert_eq( $claims['sub'], 'iserv-uuid-1', 'claims carry sub' );
gsh_assert_eq( $claims['name'], 'Frau Beispiel', 'claims carry display name' );
gsh_assert_eq( $claims['groups'], array( 'Schulleitung' ), 'claims carry groups' );
gsh_assert_eq( $claims['iat'], 1000, 'claims iat = now' );
gsh_assert_eq( $claims['exp'], 2800, 'claims exp = now + ttl' );
gsh_assert_eq( $claims['iss'], 'https://wp.test/wp-json/curriculr/v1', 'claims carry iss' );
gsh_assert_eq( $claims['aud'], 'https://juwagn.github.io/curriculr-planner/', 'claims carry aud' );
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/curriculr/test-auth.php`
Expected: FAIL — `gsh_tp_curriculr_make_app_token_claims()` undefined.

- [ ] **Step 3: Write minimal implementation**

Append to `plugin/curriculr-auth.php`:

```php
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
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php tests/curriculr/test-auth.php`
Expected: PASS — all `claims` assertions PASS.

- [ ] **Step 5: Commit**

```bash
git add plugin/curriculr-auth.php tests/curriculr/test-auth.php
git commit -m "feat(auth): App-Token-Claims-Builder (M1 Task 3)"
```

---

### Task 4: Authorize-URL-Builder

**Files:**
- Modify: `plugin/curriculr-auth.php`
- Test: `tests/curriculr/test-auth.php`

- [ ] **Step 1: Write the failing test**

Append before `gsh_test_done();`:

```php
/* ---------- Authorize-URL ---------- */
$au = gsh_tp_curriculr_build_authorize_url( $cfg, 'STATE123', 'NONCE456' );
gsh_assert_contains( $au, 'https://schule.iserv.de/iserv/auth/auth?', 'authorize url hits IServ /iserv/auth/auth' );
gsh_assert_contains( $au, 'response_type=code', 'authorize url asks for code' );
gsh_assert_contains( $au, 'client_id=client-abc', 'authorize url carries client_id' );
gsh_assert_contains( $au, 'scope=openid+profile+iserv%3Agroups', 'authorize url requests openid profile iserv:groups' );
gsh_assert_contains( $au, 'state=STATE123', 'authorize url carries state' );
gsh_assert_contains( $au, 'nonce=NONCE456', 'authorize url carries nonce' );
gsh_assert_contains( $au, 'redirect_uri=https%3A%2F%2Fwp.test%2Fwp-json%2Fcurriculr%2Fv1%2Fauth%2Fcallback', 'authorize url carries exact redirect_uri' );
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/curriculr/test-auth.php`
Expected: FAIL — `gsh_tp_curriculr_build_authorize_url()` undefined.

- [ ] **Step 3: Write minimal implementation**

Append to `plugin/curriculr-auth.php`:

```php
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
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php tests/curriculr/test-auth.php`
Expected: PASS — all `authorize url` assertions PASS.

- [ ] **Step 5: Commit**

```bash
git add plugin/curriculr-auth.php tests/curriculr/test-auth.php
git commit -m "feat(auth): Authorize-URL-Builder (M1 Task 4)"
```

---

### Task 5: Gruppen-Extraktion + Whitelist-Prüfung

**Files:**
- Modify: `plugin/curriculr-auth.php`
- Test: `tests/curriculr/test-auth.php`

- [ ] **Step 1: Write the failing test**

Append before `gsh_test_done();`:

```php
/* ---------- Gruppen-Extraktion (offener Punkt Spec §13: Claim-Form) ---------- */
gsh_assert_eq( gsh_tp_curriculr_extract_groups( array( 'Schulleitung', 'Lehrer' ) ), array( 'Schulleitung', 'Lehrer' ), 'extract_groups handles string list' );
gsh_assert_eq( gsh_tp_curriculr_extract_groups( array( array( 'act' => 'schulleitung', 'name' => 'Schulleitung' ) ) ), array( 'schulleitung' ), 'extract_groups prefers act on object form' );
gsh_assert_eq( gsh_tp_curriculr_extract_groups( array( array( 'name' => 'Schulleitung' ) ) ), array( 'Schulleitung' ), 'extract_groups falls back to name' );
gsh_assert_eq( gsh_tp_curriculr_extract_groups( 'nope' ), array(), 'extract_groups of non-array is empty' );

/* ---------- Whitelist-Prüfung (Gruppenfilter #2, fail-closed) ---------- */
gsh_assert_true( gsh_tp_curriculr_group_check( array( 'Schulleitung' ), array( 'Schulleitung', 'Verwaltung' ), ), 'member of whitelist passes' );
gsh_assert_eq( gsh_tp_curriculr_group_check( array( 'Lehrer' ), array( 'Schulleitung' ) ), false, 'non-member rejected' );
gsh_assert_eq( gsh_tp_curriculr_group_check( array( 'Schulleitung' ), array() ), false, 'empty whitelist fails closed' );
gsh_assert_eq( gsh_tp_curriculr_group_check( array(), array( 'Schulleitung' ) ), false, 'no user groups rejected' );
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/curriculr/test-auth.php`
Expected: FAIL — `gsh_tp_curriculr_extract_groups()` undefined.

- [ ] **Step 3: Write minimal implementation**

Append to `plugin/curriculr-auth.php`:

```php
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
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php tests/curriculr/test-auth.php`
Expected: PASS — all `extract_groups` + `group_check` assertions PASS.

> Note for M1 smoke test (Task 13): the exact `iserv:groups` claim shape is an open point (spec §13). `extract_groups` covers string-list and object-list; verify against a real token and tighten the preferred key if needed.

- [ ] **Step 5: Commit**

```bash
git add plugin/curriculr-auth.php tests/curriculr/test-auth.php
git commit -m "feat(auth): iserv:groups-Extraktion + Whitelist (fail-closed) (M1 Task 5)"
```

---

### Task 6: id_token-Payload-Decode + Nonce-Helfer

**Files:**
- Modify: `plugin/curriculr-auth.php`
- Test: `tests/curriculr/test-auth.php`

- [ ] **Step 1: Write the failing test**

Append before `gsh_test_done();`:

```php
/* ---------- id_token-Payload (nur Lesen; Nonce-Bindung Spec §4 Schritt 5) ---------- */
$fake_id_token = gsh_tp_curriculr_b64url_encode( '{"alg":"RS256"}' )
    . '.' . gsh_tp_curriculr_b64url_encode( '{"sub":"u1","nonce":"NONCE456"}' )
    . '.sig';
$payload = gsh_tp_curriculr_jwt_payload( $fake_id_token );
gsh_assert_eq( $payload['nonce'], 'NONCE456', 'jwt_payload reads id_token nonce without verifying signature' );
gsh_assert_eq( gsh_tp_curriculr_jwt_payload( 'garbage' ), null, 'jwt_payload of malformed token is null' );
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/curriculr/test-auth.php`
Expected: FAIL — `gsh_tp_curriculr_jwt_payload()` undefined.

- [ ] **Step 3: Write minimal implementation**

Append to `plugin/curriculr-auth.php`:

```php
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
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php tests/curriculr/test-auth.php`
Expected: PASS — both `jwt_payload` assertions PASS.

- [ ] **Step 5: Commit**

```bash
git add plugin/curriculr-auth.php tests/curriculr/test-auth.php
git commit -m "feat(auth): id_token-Payload-Decode für Nonce-Bindung (M1 Task 6)"
```

---

### Task 7: OIDC-HTTP-Wrapper (code→token, userinfo)

**Files:**
- Modify: `plugin/curriculr-auth.php`
- Test: `tests/curriculr/test-auth.php`

- [ ] **Step 1: Write the failing test**

Append before `gsh_test_done();`:

```php
/* ---------- OIDC-HTTP-Wrapper (mit gestubbten wp_remote_*) ---------- */
$GLOBALS['remote_queue'] = array(
    array( 'body' => json_encode( array( 'access_token' => 'AT', 'id_token' => 'IT' ) ) ),
);
$tok = gsh_tp_curriculr_oidc_exchange_code( $cfg, 'CODE' );
gsh_assert_eq( $tok['access_token'], 'AT', 'exchange_code returns parsed token body' );

$GLOBALS['remote_queue'] = array( new WP_Error( 'http', 'down' ) );
gsh_assert_true( is_wp_error( gsh_tp_curriculr_oidc_exchange_code( $cfg, 'CODE' ) ), 'exchange_code propagates wp_error' );

$GLOBALS['remote_queue'] = array(
    array( 'body' => json_encode( array( 'sub' => 'u1', 'name' => 'Frau B', 'groups' => array( 'Schulleitung' ) ) ) ),
);
$ui = gsh_tp_curriculr_oidc_userinfo( $cfg, 'AT' );
gsh_assert_eq( $ui['sub'], 'u1', 'userinfo returns parsed body' );

$GLOBALS['remote_queue'] = array( array( 'body' => 'not json' ) );
gsh_assert_true( is_wp_error( gsh_tp_curriculr_oidc_userinfo( $cfg, 'AT' ) ), 'userinfo non-json -> wp_error' );
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/curriculr/test-auth.php`
Expected: FAIL — `gsh_tp_curriculr_oidc_exchange_code()` undefined.

- [ ] **Step 3: Write minimal implementation**

Append to `plugin/curriculr-auth.php`:

```php
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
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php tests/curriculr/test-auth.php`
Expected: PASS — all OIDC-HTTP assertions PASS.

- [ ] **Step 5: Commit**

```bash
git add plugin/curriculr-auth.php tests/curriculr/test-auth.php
git commit -m "feat(auth): OIDC-HTTP-Wrapper code→token + userinfo (M1 Task 7)"
```

---

### Task 8: REST-Registrierung + login/callback-Handler (Live-WP)

**Files:**
- Modify: `plugin/curriculr-auth.php`

> These two handlers end in `wp_redirect()` + `exit` (full-page browser navigations, not `fetch`). They are not unit-tested for the redirect path (matching the existing "live-WP specifics" carve-out in `test-integration-stubbed.php`). They are `php -l`-checked in Task 13 and smoke-tested on a real install. The helpers they call (`build_authorize_url`, `oidc_*`, `extract_groups`, `group_check`, `jwt_payload`, `make_app_token_claims`, `jwt_sign`) are all already unit-tested.

- [ ] **Step 1: Write implementation (route registration)**

Append to `plugin/curriculr-auth.php`:

```php
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
```

- [ ] **Step 2: Write implementation (login + callback + helpers)**

Append to `plugin/curriculr-auth.php`:

```php
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
    if ( ! empty( $tokens['id_token'] ) ) {
        $idp = gsh_tp_curriculr_jwt_payload( $tokens['id_token'] );
        if ( ! $idp || ! isset( $idp['nonce'] ) || ! hash_equals( (string) $saved['nonce'], (string) $idp['nonce'] ) ) {
            gsh_tp_curriculr_auth_fail( $config, 'nonce' );
        }
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
```

- [ ] **Step 3: Verify syntax**

Run: `php -l plugin/curriculr-auth.php`
Expected: `No syntax errors detected in plugin/curriculr-auth.php`

- [ ] **Step 4: Confirm existing unit tests still pass**

Run: `php tests/curriculr/test-auth.php`
Expected: PASS — `ALL PASS` (no new pure functions broke).

- [ ] **Step 5: Commit**

```bash
git add plugin/curriculr-auth.php
git commit -m "feat(auth): REST-Routen + /auth/login + /auth/callback (M1 Task 8)"
```

---

### Task 9: /auth/token (Handoff-Tausch) + /auth/logout

**Files:**
- Modify: `plugin/curriculr-auth.php`
- Test: `tests/curriculr/test-auth.php`

- [ ] **Step 1: Write the failing test**

Append before `gsh_test_done();` in `tests/curriculr/test-auth.php`:

```php
/* ---------- /auth/token: Einmal-Handoff gegen App-Token tauschen ---------- */
class Gsh_Fake_Auth_Req {
    public $body;
    public function __construct( $body ) { $this->body = $body; }
    public function get_json_params() { return $this->body; }
}

$GLOBALS['transients']['gsh_tp_cur_handoff_HX'] = 'THE.APP.TOKEN';
$ok = gsh_tp_curriculr_rest_auth_token( new Gsh_Fake_Auth_Req( array( 'exchange' => 'HX' ) ) );
gsh_assert_eq( $ok->status, 200, 'auth/token valid handoff -> 200' );
gsh_assert_eq( $ok->data['token'], 'THE.APP.TOKEN', 'auth/token returns the app-token' );

// Single-Use: zweiter Tausch desselben Handoffs schlägt fehl.
$again = gsh_tp_curriculr_rest_auth_token( new Gsh_Fake_Auth_Req( array( 'exchange' => 'HX' ) ) );
gsh_assert_eq( $again->status, 401, 'auth/token reused handoff -> 401' );

$missing = gsh_tp_curriculr_rest_auth_token( new Gsh_Fake_Auth_Req( array() ) );
gsh_assert_eq( $missing->status, 400, 'auth/token missing exchange -> 400' );

$unknown = gsh_tp_curriculr_rest_auth_token( new Gsh_Fake_Auth_Req( array( 'exchange' => 'NOPE' ) ) );
gsh_assert_eq( $unknown->status, 401, 'auth/token unknown handoff -> 401' );

/* ---------- /auth/logout: stateless-Bestätigung ---------- */
$lo = gsh_tp_curriculr_rest_auth_logout( new Gsh_Fake_Auth_Req( array() ) );
gsh_assert_eq( $lo->status, 200, 'auth/logout -> 200 ok' );
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/curriculr/test-auth.php`
Expected: FAIL — `gsh_tp_curriculr_rest_auth_token()` undefined.

- [ ] **Step 3: Write minimal implementation**

Append to `plugin/curriculr-auth.php`:

```php
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
```

> Note: `add_action` is stubbed as a no-op in the test bootstrap (Task 1), so this `if` block is inert under CLI and harmless.

- [ ] **Step 4: Run test to verify it passes**

Run: `php tests/curriculr/test-auth.php`
Expected: PASS — all `auth/token` + `auth/logout` assertions PASS, `ALL PASS`.

- [ ] **Step 5: Commit**

```bash
git add plugin/curriculr-auth.php tests/curriculr/test-auth.php
git commit -m "feat(auth): /auth/token Handoff-Tausch + /auth/logout + Hook (M1 Task 9)"
```

---

### Task 10: Modul einbinden + CORS POST + Versions-Bump 4.10.0

**Files:**
- Modify: `plugin/gsh-terminplan.php:530` (require), `:522` (version), header docblock + changelog
- Modify: `plugin/curriculr-data-layer.php:326` (CORS methods)

- [ ] **Step 1: Add POST to CORS allowed methods**

In `plugin/curriculr-data-layer.php`, change line 326:

```php
    header( 'Access-Control-Allow-Methods: GET, PUT, OPTIONS' );
```

to:

```php
    header( 'Access-Control-Allow-Methods: GET, PUT, POST, OPTIONS' );
```

- [ ] **Step 2: Require the auth module**

In `plugin/gsh-terminplan.php`, after line 530:

```php
require_once plugin_dir_path( __FILE__ ) . 'curriculr-data-layer.php';
```

add:

```php
require_once plugin_dir_path( __FILE__ ) . 'curriculr-auth.php';
```

- [ ] **Step 3: Bump version constant**

In `plugin/gsh-terminplan.php` line 522, change:

```php
define( 'GSH_TP_VERSION',       '4.9.0' );
```

to:

```php
define( 'GSH_TP_VERSION',       '4.10.0' );
```

- [ ] **Step 4: Bump header version + add changelog entry**

In the header docblock: change `* Version:     4.9.0` to `* Version:     4.10.0`, and insert directly above the `Changelog 4.9.0:` line:

```php
 * Changelog 4.10.0:
 * - [FEATURE] M1 IServ-SSO: Auth-Endpunkte (/auth/login, /auth/callback, /auth/token, /auth/logout), Confidential-Client code→token serverseitig, kurzlebiges HS256-App-Token, Einmal-Handoff (kein Token in URL), Gruppen-Whitelist (fail-closed)
 *
```

- [ ] **Step 5: Verify syntax of both files**

Run: `php -l plugin/gsh-terminplan.php && php -l plugin/curriculr-data-layer.php`
Expected: `No syntax errors detected` for both.

- [ ] **Step 6: Commit**

```bash
git add plugin/gsh-terminplan.php plugin/curriculr-data-layer.php
git commit -m "feat(auth): Auth-Modul einbinden, CORS POST, Version 4.10.0 (M1 Task 10)"
```

---

### Task 11: System-Tab — SSO-Status-Panel + Datenschutz/Vibecoding (§9, Plugin-Hälfte)

**Files:**
- Modify: `plugin/gsh-terminplan.php` (System-Tab-Rendering, bei der bestehenden Curriculr-Sync-Sektion)

> Read-only panel: shows whether each `wp-config.php` constant is defined (never the value), the exact `redirect_uri` to register in IServ, and the Datenschutz + Vibecoding text from spec §9. No secret entry here — secrets stay in `wp-config.php`.

- [ ] **Step 1: Locate the Curriculr-Sync admin section**

Run: `grep -n "gsh_tp_save_curriculr\|Curriculr-Sync\|gsh_tp_curriculr_origin" plugin/gsh-terminplan.php`
Expected: finds the System-tab block that renders the Curriculr origin/profile-map form (around the POST handler at line ~2683 and its matching render markup). Identify the closing of that rendered section — the new panel goes immediately after it.

- [ ] **Step 2: Add the status panel + Datenschutz/Vibecoding markup**

Immediately after the rendered Curriculr-Sync section's closing markup, insert:

```php
            <?php
            $cur_cfg = gsh_tp_curriculr_auth_config();
            $cur_defs = array(
                'CURRICULR_ISERV_BASE_URL'      => ! empty( $cur_cfg['iserv_base'] ),
                'CURRICULR_ISERV_CLIENT_ID'     => ! empty( $cur_cfg['client_id'] ),
                'CURRICULR_ISERV_CLIENT_SECRET' => ! empty( $cur_cfg['client_secret'] ),
                'CURRICULR_APP_TOKEN_KEY'       => ! empty( $cur_cfg['app_token_key'] ),
            );
            $cur_ready = gsh_tp_curriculr_auth_is_configured( $cur_cfg );
            ?>
            <h2><?php echo esc_html__( 'IServ-SSO (Mehrbenutzer-Anmeldung)', 'gsh-terminplan' ); ?></h2>
            <p>
                <?php echo $cur_ready
                    ? gsh_tp_icon( 'check' ) . ' <strong>Konfiguriert.</strong> Die Anmeldung über IServ ist aktiv.'
                    : gsh_tp_icon( 'alert' ) . ' <strong>Noch nicht vollständig konfiguriert.</strong> Bitte die fehlenden Konstanten in <code>wp-config.php</code> ergänzen.'; ?>
            </p>
            <table class="widefat" style="max-width:640px">
                <thead><tr><th>Konstante (in wp-config.php)</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ( $cur_defs as $const => $set ) : ?>
                    <tr>
                        <td><code><?php echo esc_html( $const ); ?></code></td>
                        <td><?php echo $set
                            ? gsh_tp_icon( 'check' ) . ' gesetzt'
                            : gsh_tp_icon( 'alert' ) . ' fehlt'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <strong>Redirect-URI für die IServ-Client-Registrierung:</strong><br>
                <code><?php echo esc_html( $cur_cfg['redirect_uri'] ); ?></code>
            </p>
            <p>
                <strong>Erlaubte Gruppen (Whitelist):</strong>
                <code><?php echo esc_html( $cur_cfg['allowed_groups'] ? implode( ', ', $cur_cfg['allowed_groups'] ) : '— keine — (Anmeldung gesperrt)' ); ?></code>
            </p>

            <h2><?php echo esc_html__( 'Datenschutz & Transparenz', 'gsh-terminplan' ); ?></h2>
            <p>
                Verarbeitete Daten bei aktivierter IServ-Anmeldung: IServ-Kennung
                (<code>sub</code>), Anzeigename und freigegebene Gruppen — sowie die
                Plandaten des Schuljahres. Speicherung der Plandaten auf dem
                WordPress-Server (Hoster w3w.de, DE/EU). IServ-Tokens werden nicht
                dauerhaft gespeichert.
            </p>
            <p>
                Hinweis: Die Planner-Oberfläche wird von GitHub Pages
                (GitHub/Microsoft, USA) geladen; dabei wird die IP-Adresse in ein
                Drittland übertragen. Dort werden <em>keine</em> Plandaten verarbeitet
                (nur statisches JavaScript/CSS). Zweck: gemeinsame Terminplanung.
                Rechtsgrundlage und Ansprechpartner: siehe schulisches
                Datenschutzkonzept.
            </p>
            <p style="padding:12px;border-left:4px solid var(--gsh-marine,#00345C);background:#f1f5f9">
                <strong>Hinweis (&bdquo;Vibecoding&ldquo;):</strong> Diese Werkzeuge
                (Planner und WordPress-Plugin) wurden im Wege des &bdquo;Vibecodings&ldquo;
                &mdash; also KI-gestützter Softwareentwicklung &mdash; erstellt. Vor dem
                produktiven Einsatz mit personenbezogenen Daten sind die übliche
                Sorgfalt, Tests und eine datenschutzrechtliche Bewertung anzuwenden.
            </p>
```

> If `gsh_tp_icon()` has no `'alert'` glyph, use an existing one — run `grep -n "function gsh_tp_icon" plugin/gsh-terminplan.php` and check the available keys, then substitute (e.g. `'warning'` or `'info'`). The panel text must not depend on a missing icon key.

- [ ] **Step 3: Verify syntax**

Run: `php -l plugin/gsh-terminplan.php`
Expected: `No syntax errors detected in plugin/gsh-terminplan.php`

- [ ] **Step 4: Commit**

```bash
git add plugin/gsh-terminplan.php
git commit -m "feat(auth): System-Tab SSO-Status + Datenschutz/Vibecoding-Hinweis (M1 Task 11)"
```

---

### Task 12: Admin-SSO-Anleitung + Spec §12 finalisieren

**Files:**
- Create: `docs/anleitung-iserv-sso.md`
- Modify: `../curriculr-planner/docs/superpowers/specs/2026-06-10-multiuser-iserv-sso-design.md` §12

- [ ] **Step 1: Write the admin setup guide**

Create `docs/anleitung-iserv-sso.md`:

```markdown
# IServ-SSO einrichten (Administrator)

Diese Anleitung verbindet den Curriculr-Planner über IServ-Single-Sign-On mit
dem WordPress-Plugin. Voraussetzung: Admin-Zugang zu IServ und zur
`wp-config.php` des WordPress-Servers.

## 1. IServ-Client anlegen

1. In IServ: **Verwaltung → System → Single-Sign-On → Hinzufügen**.
2. **Name:** z. B. `Curriculr-Planner`.
3. **Gruppen-/Rollenrechte:** nur die berechtigte(n) Gruppe(n) freigeben, z. B.
   `Schulleitung` (Gruppenfilter #1 am IServ).
4. **Scopes:** `openid`, `profile`, `iserv:groups`.
5. **Grant-Type:** Authorization Code.
6. **Redirect-URI:** exakt den Wert eintragen, den das Plugin im System-Tab unter
   „IServ-SSO" anzeigt (Form: `https://<wp-host>/wp-json/curriculr/v1/auth/callback`).
7. **Client-ID** und **Client-Secret** notieren.

## 2. wp-config.php befüllen

```php
define( 'CURRICULR_ISERV_BASE_URL',     'https://<schule>.iserv.de' ); // ohne /iserv
define( 'CURRICULR_ISERV_CLIENT_ID',    '<client-id>' );
define( 'CURRICULR_ISERV_CLIENT_SECRET','<client-secret>' );
define( 'CURRICULR_APP_TOKEN_KEY',      '<32+ zufällige Zeichen>' );
define( 'CURRICULR_SPA_URL',            'https://juwagn.github.io/curriculr-planner/' );
define( 'CURRICULR_ALLOWED_GROUPS',     'Schulleitung' ); // Komma-Liste
// optional: define( 'CURRICULR_APP_TOKEN_TTL', 1800 );
```

> `CURRICULR_APP_TOKEN_KEY` per `wp_generate_password()` o. ä. erzeugen; geheim halten.

## 3. Prüfen

1. WP-Admin → Plugin-Einstellungen → **System-Tab → IServ-SSO**: alle vier
   Konstanten müssen „gesetzt" zeigen, die Redirect-URI muss mit der in IServ
   registrierten übereinstimmen.
2. Test-Login mit einem Konto der freigegebenen Gruppe (ab M3 in der SPA verfügbar).
3. Konto **außerhalb** der Gruppe testen → muss abgewiesen werden (Gruppenfilter #2).

## Sicherheitshinweise

- Client-Secret und App-Token-Schlüssel nur in `wp-config.php`, nie in der DB,
  nie im Repository.
- Redirect-URI exakt registrieren (keine Wildcards).
- App-Token ist kurzlebig (Default 30 Min) und lebt im Browser nur im RAM.

Referenzen:
- IServ SSO (Verwaltung): https://doku.iserv.de/manage/system/sso/
- IServ OAuth/OpenID (Entwickler): https://doku.iserv.de/development/oauth/
```

- [ ] **Step 2: Finalize spec §12 in the planner repo**

In `../curriculr-planner/docs/superpowers/specs/2026-06-10-multiuser-iserv-sso-design.md`, replace the `## 12. SSO-Einrichtungs-Anleitung (für den Administrator) — Platzhalter` blockquote body with a short confirmation that the guide is now concrete, pointing to it:

```markdown
## 12. SSO-Einrichtungs-Anleitung (für den Administrator)

Bei M1 umgesetzt. Vollständige Schritt-für-Schritt-Anleitung:
`curriculr-terminplan/docs/anleitung-iserv-sso.md`. Kurzfassung:

1. IServ-Client anlegen (Gruppenrechte, Scopes `openid profile iserv:groups`,
   Redirect-URI = WP `/wp-json/curriculr/v1/auth/callback`).
2. Client-ID + Secret notieren.
3. In `wp-config.php`: `CURRICULR_ISERV_BASE_URL`, `CURRICULR_ISERV_CLIENT_ID`,
   `CURRICULR_ISERV_CLIENT_SECRET`, `CURRICULR_APP_TOKEN_KEY`, `CURRICULR_SPA_URL`,
   `CURRICULR_ALLOWED_GROUPS`.
4. WP-Admin → System-Tab → IServ-SSO: Status „konfiguriert" prüfen, Redirect-URI
   abgleichen.
5. Test-Login mit Schulleitungskonto; Gruppenfilter mit Fremdkonto verifizieren.

Referenzen:
- IServ Single-Sign-On (Verwaltung): https://doku.iserv.de/manage/system/sso/
- IServ OAuth/OpenID (Entwickler): https://doku.iserv.de/development/oauth/
```

- [ ] **Step 3: Commit (plugin repo)**

```bash
git add docs/anleitung-iserv-sso.md
git commit -m "docs(auth): Admin-Anleitung IServ-SSO einrichten (M1 Task 12)"
```

> The planner-repo spec edit is committed separately in that repo:
> ```bash
> cd ../curriculr-planner && git add docs/superpowers/specs/2026-06-10-multiuser-iserv-sso-design.md && git commit -m "docs(spec): SSO-Setup §12 finalisiert (M1)" && cd ../curriculr-terminplan
> ```

---

### Task 13: Gesamt-Verifikation + Plugin-ZIP

**Files:** none (verification)

- [ ] **Step 1: Run the full auth test suite + lint all touched PHP**

Run:
```bash
php tests/curriculr/test-auth.php \
  && php tests/curriculr/test-integration-stubbed.php \
  && php -l plugin/curriculr-auth.php \
  && php -l plugin/curriculr-data-layer.php \
  && php -l plugin/gsh-terminplan.php
```
Expected: `ALL PASS` for both test files, `No syntax errors detected` for all three.

- [ ] **Step 2: Confirm version bump landed**

Run: `grep -n "GSH_TP_VERSION\b" plugin/gsh-terminplan.php | head -1`
Expected: shows `'4.10.0'`.

- [ ] **Step 3: Smoke-test plan (manual, real WP — record results, do not block the commit)**

Document for the live install (not runnable in CI):
1. Set the six constants in `wp-config.php`, reactivate plugin.
2. System-Tab → IServ-SSO shows all four constants „gesetzt".
3. Browse to `https://<wp-host>/wp-json/curriculr/v1/auth/login` → 302 to IServ.
4. Log in as a Schulleitung member → lands on SPA URL with `#auth=<handoff>`.
5. `POST /wp-json/curriculr/v1/auth/token` with `{"exchange":"<handoff>"}` → `{ "token": "<jwt>" }`; second call → 401.
6. **Verify the real `iserv:groups` claim shape** (spec §13 open point) — if it differs from string/object-with-`act`, adjust `gsh_tp_curriculr_extract_groups`.
7. Log in as a non-group member → redirected with `#auth_error=forbidden`.

- [ ] **Step 4: Rebuild the plugin ZIP (matches go-live convention)**

Run from the repo parent (`/Users/julian.wagner/curriculr-planner`):
```bash
rm -rf curriculr-terminplan-4.10.0 && mkdir -p curriculr-terminplan-4.10.0 \
  && cp curriculr-terminplan/plugin/gsh-terminplan.php \
        curriculr-terminplan/plugin/curriculr-data-layer.php \
        curriculr-terminplan/plugin/curriculr-auth.php \
        curriculr-terminplan/plugin/page-terminplan-entwurf.php \
        curriculr-terminplan-4.10.0/ \
  && zip -r curriculr-terminplan-4.10.0.zip curriculr-terminplan-4.10.0
```
Expected: `curriculr-terminplan-4.10.0.zip` created (now includes `curriculr-auth.php`).

> Verify which files belong in the ZIP first — `unzip -l curriculr-terminplan-4.9.0.zip` shows the prior contents; match that set plus the new `curriculr-auth.php`.

- [ ] **Step 5: Final commit (if any uncommitted bookkeeping remains)**

```bash
git add -A
git commit -m "chore(auth): M1 verification — Tests grün, ZIP 4.10.0" || echo "nothing to commit"
```

---

## Self-Review

**Spec coverage (against §10 M1 row + §3/§4/§5/§9/§12):**
- IServ Confidential-Client anlegen → admin guide (Task 12) + status panel surfaces redirect-URI (Task 11). ✓
- WP-Auth-Endpunkte `/auth/login` `/auth/callback` `/auth/logout` → Tasks 8–9. ✓ (plus `/auth/token` handoff, required by §5).
- Secret-Storage → `wp-config.php` constants, config reader (Task 1), never echoed (Task 11). ✓
- App-Token-Ausstellung → HS256 JWT (Task 2), claims (Task 3), minted in callback (Task 8). ✓
- §4 flow: state+nonce (Tasks 8,6), code→token server-side with secret (Task 7,8), group filter #2 (Task 5,8). ✓
- §5 token handling: app-token never in URL → fragment handoff + `/auth/token` (Tasks 8,9). ✓
- §9 in-app hints (plugin half): Datenschutz + Vibecoding in System tab (Task 11). SPA half = M3 (noted). ✓
- §12 admin guide filled (Task 12). ✓
- §8 CORS: POST added (Task 10); strict CORS/CSP tightening = M3 (noted). ✓

**Deferred-and-noted (not M1):** full RS256/JWKS id_token signature verification (§8 hardening); strict CORS origin/CSP (M3); rate-limiting (M2/hardening); SPA-side login UI + §9 SPA half (M3). All explicitly flagged inline so nothing is silently dropped.

**Placeholder scan:** No TBD/TODO. Every code step shows complete code. The two non-unit-tested handlers (login/callback) are explicitly justified (redirect+exit, live-WP) and rely only on already-tested helpers.

**Type/name consistency:** Function names verified consistent across tasks — `gsh_tp_curriculr_auth_config`, `gsh_tp_curriculr_auth_is_configured`, `gsh_tp_curriculr_b64url_encode/decode`, `gsh_tp_curriculr_jwt_sign/verify/payload`, `gsh_tp_curriculr_make_app_token_claims`, `gsh_tp_curriculr_build_authorize_url`, `gsh_tp_curriculr_extract_groups`, `gsh_tp_curriculr_group_check`, `gsh_tp_curriculr_oidc_exchange_code/userinfo`, `gsh_tp_curriculr_register_auth_routes`, `gsh_tp_curriculr_rest_auth_login/callback/token/logout`, `gsh_tp_curriculr_spa_redirect_url`, `gsh_tp_curriculr_auth_fail`. Transient keys consistent: `gsh_tp_cur_oauth_<state>`, `gsh_tp_cur_handoff_<exchange>`.
