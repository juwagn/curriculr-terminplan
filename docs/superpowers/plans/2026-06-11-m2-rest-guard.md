# M2 WP REST Guard Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Lock all authenticated `curriculr/v1` REST routes behind app-token Bearer validation and add per-revision user attribution (`sub` + display name) to the revisions table.

**Architecture:** New `plugin/curriculr-guard.php` contains a pure `gsh_tp_curriculr_guard_validate_bearer()` function (testable without WP) and the WP `permission_callback` `gsh_tp_curriculr_guard_perm()`. The existing `gsh_tp_curriculr_perm()` in the data layer delegates to the guard. Validated JWT claims are stored in `$GLOBALS['gsh_tp_curriculr_current_claims']` so REST callbacks can read `sub`/`name` without re-validating. The revisions table gains `author_sub varchar(255)` and `author_name varchar(255)` columns (DB version 3 → 4); `gsh_tp_curriculr_repo_put()` reads current guard claims and writes them to each revision snapshot.

**Tech Stack:** PHP 7.4+, WordPress REST API (`WP_Error` with `status` data for 403), `hash_hmac`/`hash_equals`/`preg_match` (PHP stdlib), `dbDelta` (WP additive schema migration), dependency-free CLI test harness (`tests/curriculr/assert.php`).

---

## Codebase Context

This is a **procedural WordPress plugin** (`curriculr-terminplan`). All public functions use the `gsh_tp_curriculr_*` prefix. Secrets live only in `wp-config.php` constants, never in DB or REST responses.

**Key existing files:**
- `plugin/curriculr-auth.php` — IServ-SSO auth module (M1). Provides `gsh_tp_curriculr_jwt_verify($jwt, $key, $now)` and `gsh_tp_curriculr_auth_config()`. Loaded first.
- `plugin/curriculr-data-layer.php` — REST routes, repo functions, DB schema. `gsh_tp_curriculr_perm()` is the current `permission_callback` (calls `current_user_can('manage_options')`). `gsh_tp_curriculr_repo_save_revision($sj, $version, $json_str)` inserts revision snapshots.
- `plugin/gsh-terminplan.php` — Plugin entry point. Requires data-layer then auth (line 533–534). Version `4.10.0`.
- `tests/curriculr/assert.php` — `gsh_assert_eq`, `gsh_assert_true`, `gsh_assert_contains`, `gsh_test_done`.
- `tests/curriculr/test-revisions.php` — tests `repo_put`, revision REST endpoints using `Gsh_Fake_Wpdb_Rev` stub.

**Test run command:** `php tests/curriculr/<file>.php` from `curriculr-terminplan/`.

---

## File Map

| File | Change |
|------|--------|
| `plugin/curriculr-guard.php` | NEW — pure `validate_bearer`, WP `guard_perm`, `current_claims` accessor |
| `plugin/curriculr-data-layer.php` | MOD — delegate `perm`, add author columns to schema, attribution in `repo_put`, author fields in revisions list |
| `plugin/gsh-terminplan.php` | MOD — require guard, version 4.11.0 |
| `tests/curriculr/test-guard.php` | NEW — 9 assertions |
| `tests/curriculr/test-revisions.php` | MOD — guard claims stub + 6 attribution assertions |

---

### Task 1: Guard module + tests

**Files:**
- Create: `plugin/curriculr-guard.php`
- Create: `tests/curriculr/test-guard.php`

- [ ] **Step 1: Write the failing test**

Create `tests/curriculr/test-guard.php` with this exact content:

```php
<?php
/**
 * Tests for curriculr-guard.php — Bearer app-token validation.
 * Dependency-free, runs with plain `php`.
 */
define( 'GSH_TP_CURRICULR_TEST', true );
require __DIR__ . '/assert.php';

/* ---------- Config constants (needed by curriculr-auth.php config reader) ---------- */
define( 'CURRICULR_ISERV_BASE_URL',      'https://schule.iserv.de' );
define( 'CURRICULR_ISERV_CLIENT_ID',     'client-abc' );
define( 'CURRICULR_ISERV_CLIENT_SECRET', 'secret-xyz' );
define( 'CURRICULR_APP_TOKEN_KEY',       'k0123456789abcdef0123456789abcdef' );
define( 'CURRICULR_SPA_URL',             'https://juwagn.github.io/curriculr-planner/' );
define( 'CURRICULR_ALLOWED_GROUPS',      'Schulleitung' );

/* ---------- Minimal WP stubs ---------- */
$GLOBALS['transients']   = array();
$GLOBALS['remote_queue'] = array();
function rest_url( $p ) { return 'https://wp.test/wp-json/' . $p; }
function set_transient( $k, $v, $ttl ) { $GLOBALS['transients'][ $k ] = $v; }
function get_transient( $k ) { return $GLOBALS['transients'][ $k ] ?? false; }
function delete_transient( $k ) { unset( $GLOBALS['transients'][ $k ] ); }
function wp_generate_password( $l = 12, $s = true, $e = true ) { return substr( str_repeat( 'aB3xY9Qz', 16 ), 0, $l ); }
function wp_redirect( $u ) {}
function wp_remote_post( $u, $a = array() ) { return array_shift( $GLOBALS['remote_queue'] ); }
function wp_remote_get( $u, $a = array() ) { return array_shift( $GLOBALS['remote_queue'] ); }
function wp_remote_retrieve_body( $r ) { return is_array( $r ) ? ( $r['body'] ?? '' ) : ''; }
function is_wp_error( $t ) { return $t instanceof WP_Error; }
class WP_Error {
    public $code; public $message; public $data;
    public function __construct( $c = '', $m = '', $d = array() ) {
        $this->code = $c; $this->message = $m; $this->data = $d;
    }
}
class WP_REST_Response {
    public $data; public $status;
    public function __construct( $d, $s = 200 ) { $this->data = $d; $this->status = $s; }
}
function add_action() {}

class Gsh_Fake_Guard_Req {
    private $headers;
    public function __construct( $headers = array() ) { $this->headers = $headers; }
    public function get_header( $name ) { return $this->headers[ strtolower( $name ) ] ?? null; }
}

require __DIR__ . '/../../plugin/curriculr-auth.php';
require __DIR__ . '/../../plugin/curriculr-guard.php'; // <-- will fail until created

/* ---------- validate_bearer: pure function ---------- */
$key    = 'k0123456789abcdef0123456789abcdef';
$claims = array(
    'sub'    => 'iserv-sub-001',
    'name'   => 'Alice',
    'groups' => array( 'Schulleitung' ),
    'exp'    => 9999999999,
    'iat'    => 1000,
    'iss'    => 'https://wp.test/wp-json/curriculr/v1',
    'aud'    => 'https://juwagn.github.io/curriculr-planner/',
);
$jwt = gsh_tp_curriculr_jwt_sign( $claims, $key );

$r = gsh_tp_curriculr_guard_validate_bearer( 'Bearer ' . $jwt, $key, 1000 );
gsh_assert_true( $r['valid'], 'valid bearer accepted' );
gsh_assert_eq( $r['claims']['sub'], 'iserv-sub-001', 'claims sub correct' );

$r = gsh_tp_curriculr_guard_validate_bearer( '', $key, 1000 );
gsh_assert_eq( $r['valid'], false, 'empty header rejected' );
gsh_assert_eq( $r['error'], 'missing_bearer', 'empty header -> missing_bearer' );

$r = gsh_tp_curriculr_guard_validate_bearer( 'Basic dXNlcjpwYXNz', $key, 1000 );
gsh_assert_eq( $r['error'], 'missing_bearer', 'Basic scheme -> missing_bearer' );

$exp_jwt = gsh_tp_curriculr_jwt_sign( array( 'sub' => 'u1', 'exp' => 100 ), $key );
$r = gsh_tp_curriculr_guard_validate_bearer( 'Bearer ' . $exp_jwt, $key, 1000 );
gsh_assert_eq( $r['error'], 'expired', 'expired token -> expired' );

$r = gsh_tp_curriculr_guard_validate_bearer( 'Bearer ' . $jwt . 'x', $key, 1000 );
gsh_assert_eq( $r['valid'], false, 'tampered token rejected' );

$r = gsh_tp_curriculr_guard_validate_bearer( 'Bearer ' . $jwt, '', 1000 );
gsh_assert_eq( $r['error'], 'no_key', 'empty key -> no_key' );

/* ---------- guard_perm + current_claims ---------- */
$req = new Gsh_Fake_Guard_Req( array( 'authorization' => 'Bearer ' . $jwt ) );
$res = gsh_tp_curriculr_guard_perm( $req );
gsh_assert_true( $res === true, 'guard_perm returns true for valid token' );
$c = gsh_tp_curriculr_guard_current_claims();
gsh_assert_eq( $c['sub'], 'iserv-sub-001', 'current_claims has correct sub after guard_perm' );

$req_empty = new Gsh_Fake_Guard_Req( array() );
$res_err   = gsh_tp_curriculr_guard_perm( $req_empty );
gsh_assert_true( $res_err instanceof WP_Error, 'guard_perm returns WP_Error on missing auth' );

gsh_test_done();
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd /Users/julian.wagner/curriculr-planner/curriculr-terminplan
php tests/curriculr/test-guard.php
```

Expected: fatal on `require .../curriculr-guard.php` — file does not exist yet.

- [ ] **Step 3: Create `plugin/curriculr-guard.php`**

```php
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
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php tests/curriculr/test-guard.php
```

Expected: `ALL PASS`

- [ ] **Step 5: Commit**

```bash
git add plugin/curriculr-guard.php tests/curriculr/test-guard.php
git commit -m "feat(guard): Bearer app-token validation + permission_callback (M2 Task 1)"
```

---

### Task 2: Wire guard into data-layer + version bump

**Files:**
- Modify: `plugin/curriculr-data-layer.php` (line 314–318)
- Modify: `plugin/gsh-terminplan.php` (line 6, 10–12, 525, 534)

- [ ] **Step 1: Confirm baseline green**

```bash
php tests/curriculr/test-revisions.php && php tests/curriculr/test-auth.php
```

Expected: both `ALL PASS`.

- [ ] **Step 2: Replace `gsh_tp_curriculr_perm()` body in `plugin/curriculr-data-layer.php`**

Find (line 314–318):

```php
function gsh_tp_curriculr_perm() {
    // Application Passwords authentifizieren den Request als WP-User;
    // danach greift current_user_can wie bei einer normalen Session.
    return current_user_can( 'manage_options' );
}
```

Replace with:

```php
function gsh_tp_curriculr_perm( $req ) {
    return gsh_tp_curriculr_guard_perm( $req );
}
```

- [ ] **Step 3: Add guard require in `plugin/gsh-terminplan.php`**

Find (line 534):

```php
require_once plugin_dir_path( __FILE__ ) . 'curriculr-auth.php';
```

Add immediately after:

```php
require_once plugin_dir_path( __FILE__ ) . 'curriculr-guard.php';
```

- [ ] **Step 4: Bump version to 4.11.0 in `plugin/gsh-terminplan.php`**

Change line 6:
```
 * Version:     4.10.0
```
to:
```
 * Version:     4.11.0
```

Add after `* Changelog 4.10.0:` block (after line 11, before the blank line):

```
 * Changelog 4.11.0:
 * - [SECURITY] M2 REST Guard: App-Token Bearer-Validierung auf allen geschützten curriculr/v1-Routen; 403 bei fehlendem/abgelaufenem/ungültigem Token
 * - [FEATURE] Revisions-Attribution: author_sub und author_name in wp_curriculr_doc_revisions (DB-Version 4)
 *
```

Change line 525:

```php
define( 'GSH_TP_VERSION',       '4.10.0' );
```

to:

```php
define( 'GSH_TP_VERSION',       '4.11.0' );
```

- [ ] **Step 5: Run tests to confirm no regressions**

```bash
php tests/curriculr/test-revisions.php && php tests/curriculr/test-auth.php && php tests/curriculr/test-guard.php
```

Expected: all three `ALL PASS`.

(`gsh_tp_curriculr_perm` is never invoked in these test files — only defined — so the missing `gsh_tp_curriculr_guard_perm` reference causes no problem at PHP parse time.)

- [ ] **Step 6: Commit**

```bash
git add plugin/curriculr-data-layer.php plugin/gsh-terminplan.php
git commit -m "feat(guard): wire guard perm into data-layer, version 4.11.0 (M2 Task 2)"
```

---

### Task 3: DB migration — revisions author columns

**Files:**
- Modify: `plugin/curriculr-data-layer.php` (lines 197–211, 610–612)

`dbDelta` is additive: if the CREATE TABLE SQL includes new columns that don't exist yet, it adds them. Bumping the DB version from 3 to 4 causes the `admin_init` check to re-run `gsh_tp_curriculr_install()` on first page load after plugin update.

- [ ] **Step 1: Update `$rev_sql` in `gsh_tp_curriculr_install()`**

Find in `plugin/curriculr-data-layer.php` (lines 198–206):

```php
    $rev_sql   = "CREATE TABLE $rev_table (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        schoolyear varchar(64) NOT NULL,
        version int unsigned NOT NULL DEFAULT 0,
        json longtext NOT NULL,
        created_at datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        PRIMARY KEY  (id),
        KEY sj_version (schoolyear, version)
    ) $charset;";
```

Replace with (add `author_sub` + `author_name` before `PRIMARY KEY`):

```php
    $rev_sql   = "CREATE TABLE $rev_table (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        schoolyear varchar(64) NOT NULL,
        version int unsigned NOT NULL DEFAULT 0,
        json longtext NOT NULL,
        created_at datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        author_sub varchar(255) NOT NULL DEFAULT '',
        author_name varchar(255) NOT NULL DEFAULT '',
        PRIMARY KEY  (id),
        KEY sj_version (schoolyear, version)
    ) $charset;";
```

- [ ] **Step 2: Bump DB version to 4**

In `gsh_tp_curriculr_install()`, find (line 211):

```php
    update_option( 'gsh_tp_curriculr_db_version', 3, false );
```

Change to:

```php
    update_option( 'gsh_tp_curriculr_db_version', 4, false );
```

- [ ] **Step 3: Update `admin_init` version guard**

Find (line 610):

```php
            if ( (int) get_option( 'gsh_tp_curriculr_db_version', 0 ) < 3 ) {
```

Change to:

```php
            if ( (int) get_option( 'gsh_tp_curriculr_db_version', 0 ) < 4 ) {
```

- [ ] **Step 4: Run tests — still pass (fake wpdb ignores SQL)**

```bash
php tests/curriculr/test-revisions.php
```

Expected: `ALL PASS`. (`Gsh_Fake_Wpdb_Rev` stubs the DB entirely; `gsh_tp_curriculr_install()` is never called in tests.)

- [ ] **Step 5: Commit**

```bash
git add plugin/curriculr-data-layer.php
git commit -m "feat(guard): DB v4 - author_sub/author_name columns in revisions (M2 Task 3)"
```

---

### Task 4: Revisions attribution — repo_put + test coverage

**Files:**
- Modify: `plugin/curriculr-data-layer.php` (lines 271–284, 257–260, 487)
- Modify: `tests/curriculr/test-revisions.php`

- [ ] **Step 1: Write failing test assertions in `tests/curriculr/test-revisions.php`**

After line 65 (where `$GLOBALS['wpdb']` is assigned), add the guard claims stub:

```php
/* ---------- Guard claims stub (simulates validated app-token) ---------- */
$GLOBALS['gsh_tp_curriculr_current_claims'] = null;
function gsh_tp_curriculr_guard_current_claims() {
    return $GLOBALS['gsh_tp_curriculr_current_claims'];
}
```

At the end of the file, immediately before the existing `gsh_test_done()` call, add:

```php
/* ---------- 7. Author attribution: guard claims written to revision ---------- */
$GLOBALS['wpdb'] = new Gsh_Fake_Wpdb_Rev();
$GLOBALS['gsh_tp_curriculr_current_claims'] = array( 'sub' => 'iserv-u99', 'name' => 'Bob' );
$r_attr = gsh_tp_curriculr_repo_put( 'sj_attr', $doc, 0 );
gsh_assert_eq( $r_attr['status'], 'ok', 'attribution PUT ok' );
$rev_attr = reset( $GLOBALS['wpdb']->revs );
gsh_assert_eq( $rev_attr['author_sub'], 'iserv-u99', 'revision trägt author_sub' );
gsh_assert_eq( $rev_attr['author_name'], 'Bob', 'revision trägt author_name' );

/* ---------- 8. Attribution empty when no guard ran ---------- */
$GLOBALS['wpdb'] = new Gsh_Fake_Wpdb_Rev();
$GLOBALS['gsh_tp_curriculr_current_claims'] = null;
gsh_tp_curriculr_repo_put( 'sj_noauth', $doc, 0 );
$rev_noauth = reset( $GLOBALS['wpdb']->revs );
gsh_assert_eq( $rev_noauth['author_sub'], '', 'revision author_sub leer wenn kein Guard' );
gsh_assert_eq( $rev_noauth['author_name'], '', 'revision author_name leer wenn kein Guard' );

/* ---------- 9. Revisions list includes author fields ---------- */
$GLOBALS['wpdb'] = new Gsh_Fake_Wpdb_Rev();
$GLOBALS['gsh_tp_curriculr_current_claims'] = array( 'sub' => 'iserv-u77', 'name' => 'Charlie' );
gsh_tp_curriculr_repo_put( 'sj_list2', $doc, 0 );
$list2 = gsh_tp_curriculr_rest_revisions_list( new Gsh_Fake_Req( array( 'sj' => 'sj_list2' ) ) );
gsh_assert_true( isset( $list2->data[0]['author_sub'] ), 'revisions_list enthält author_sub' );
gsh_assert_eq( $list2->data[0]['author_sub'], 'iserv-u77', 'revisions_list author_sub korrekt' );
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php tests/curriculr/test-revisions.php
```

Expected: FAIL on `revision trägt author_sub` (the current `save_revision` doesn't insert that field, so `$rev_attr['author_sub']` is undefined).

- [ ] **Step 3: Update `gsh_tp_curriculr_repo_save_revision()` signature and insert**

Find (lines 271–284):

```php
function gsh_tp_curriculr_repo_save_revision( $sj, $version, $json_str ) {
    global $wpdb;
    $table = gsh_tp_curriculr_revisions_table();
    $wpdb->insert(
        $table,
        array(
            'schoolyear' => sanitize_key( $sj ),
            'version'    => (int) $version,
            'json'       => $json_str,
            'created_at' => current_time( 'mysql' ),
        )
    );
    return (int) $wpdb->insert_id;
}
```

Replace with:

```php
function gsh_tp_curriculr_repo_save_revision( $sj, $version, $json_str, $author_sub = '', $author_name = '' ) {
    global $wpdb;
    $table = gsh_tp_curriculr_revisions_table();
    $wpdb->insert(
        $table,
        array(
            'schoolyear'  => sanitize_key( $sj ),
            'version'     => (int) $version,
            'json'        => $json_str,
            'created_at'  => current_time( 'mysql' ),
            'author_sub'  => (string) $author_sub,
            'author_name' => (string) $author_name,
        )
    );
    return (int) $wpdb->insert_id;
}
```

- [ ] **Step 4: Update `gsh_tp_curriculr_repo_put()` to pass guard claims to save_revision**

Find (line 257–260):

```php
    // Revision-Snapshot + Retention-Prune.
    $json_str = wp_json_encode( $doc );
    gsh_tp_curriculr_repo_save_revision( $sj, $new_version, $json_str );
    gsh_tp_curriculr_prune_revisions( $sj );
```

Replace with:

```php
    // Revision-Snapshot + Retention-Prune.
    $json_str    = wp_json_encode( $doc );
    $guard       = function_exists( 'gsh_tp_curriculr_guard_current_claims' )
        ? gsh_tp_curriculr_guard_current_claims()
        : null;
    $author_sub  = $guard ? (string) ( $guard['sub'] ?? '' ) : '';
    $author_name = $guard ? (string) ( $guard['name'] ?? '' ) : '';
    gsh_tp_curriculr_repo_save_revision( $sj, $new_version, $json_str, $author_sub, $author_name );
    gsh_tp_curriculr_prune_revisions( $sj );
```

- [ ] **Step 5: Update `gsh_tp_curriculr_rest_revisions_list()` SELECT to include author fields**

Find (line 487):

```php
            "SELECT id, schoolyear, version, created_at FROM $table WHERE schoolyear = %s ORDER BY id DESC LIMIT 100",
```

Replace with:

```php
            "SELECT id, schoolyear, version, created_at, author_sub, author_name FROM $table WHERE schoolyear = %s ORDER BY id DESC LIMIT 100",
```

- [ ] **Step 6: Run all tests**

```bash
php tests/curriculr/test-revisions.php && php tests/curriculr/test-auth.php && php tests/curriculr/test-guard.php
```

Expected: all three `ALL PASS`.

- [ ] **Step 7: Commit**

```bash
git add plugin/curriculr-data-layer.php tests/curriculr/test-revisions.php
git commit -m "feat(guard): revisions attribution sub/name from guard claims (M2 Task 4)"
```

---

### Task 5: Full verification + rebuild ZIP

**Files:**
- No code changes — verification only, then ZIP rebuild.

- [ ] **Step 1: Run full test suite**

```bash
cd /Users/julian.wagner/curriculr-planner/curriculr-terminplan
php tests/curriculr/test-guard.php
php tests/curriculr/test-auth.php
php tests/curriculr/test-revisions.php
php tests/curriculr/test-integration-stubbed.php
php tests/curriculr/test-envelope.php
php tests/curriculr/test-version.php
php tests/curriculr/test-stage.php
php tests/curriculr/test-ics.php
php tests/curriculr/test-ics-edgecases.php
```

All must output `ALL PASS`. Fix any failures before continuing.

- [ ] **Step 2: Verify git log looks correct**

```bash
git log --oneline -6
```

Expected to see (most recent first):
```
feat(guard): revisions attribution sub/name from guard claims (M2 Task 4)
feat(guard): DB v4 - author_sub/author_name columns in revisions (M2 Task 3)
feat(guard): wire guard perm into data-layer, version 4.11.0 (M2 Task 2)
feat(guard): Bearer app-token validation + permission_callback (M2 Task 1)
fix(auth): nonce mandatory + wp_kses_post on admin SSO panel
feat(auth): Admin-Anleitung IServ-SSO einrichten (M1 Task 12)
```

- [ ] **Step 3: Rebuild plugin ZIP**

The ZIP must be **flat** (no subdirectory wrapper) and contain exactly three PHP files:

```bash
cd /Users/julian.wagner/curriculr-planner/curriculr-terminplan/plugin
zip -j /Users/julian.wagner/curriculr-planner/curriculr-terminplan-4.11.0.zip \
    gsh-terminplan.php \
    curriculr-data-layer.php \
    curriculr-auth.php \
    curriculr-guard.php
```

Verify ZIP contents:

```bash
unzip -l /Users/julian.wagner/curriculr-planner/curriculr-terminplan-4.11.0.zip
```

Expected: 4 entries (`gsh-terminplan.php`, `curriculr-data-layer.php`, `curriculr-auth.php`, `curriculr-guard.php`) with no directory prefix.

- [ ] **Step 4: Commit ZIP**

```bash
git add /Users/julian.wagner/curriculr-planner/curriculr-terminplan-4.11.0.zip
git commit -m "chore: plugin ZIP v4.11.0 (M2 REST Guard)"
```

---

## Self-Review

### Spec coverage (§ references from `docs/superpowers/specs/2026-06-10-multiuser-iserv-sso-design.md`)

| Spec requirement | Task |
|-----------------|------|
| §3 REST-Guard: validiert App-Token + Gruppen-Whitelist → sonst 403 | Task 1 (`guard_perm` returns WP_Error 403 on any invalid token) |
| §4 Step 8: WP REST-Guard validiert App-Token + Gruppen → liest/schreibt Doc | Task 1 (guard validates token; groups already in claims from M1 — not re-checked here, deferred to M3/spec note below) |
| §7 `wp_curriculr_doc_revisions` bekommt User-Attribution (`sub` + Anzeigename) | Tasks 3–4 |
| §10 M2 = WP-REST-Guard: App-Token validieren + Gruppen-Whitelist + 403; Revisions-Attribution | All tasks |

**Groups re-check in guard:** The spec §4 Step 8 says "validiert App-Token + Gruppen". The app-token already encodes `groups` in its claims (set at login time, verified by IServ). `guard_perm` validates the token signature + expiry — the group check was done at token issuance (M1 callback). Re-checking groups on every request would require comparing token claims against a server-side list, which the spec doesn't mandate for M2. This is consistent with the spec's "defense in depth" positioning of the whitelist at auth time. **No gap.**

### Placeholder scan

No TBD/TODO/placeholder patterns found.

### Type consistency

- `gsh_tp_curriculr_guard_validate_bearer` used in Task 1 test and implementation — matches.
- `gsh_tp_curriculr_guard_perm($req)` matches permission_callback signature WordPress expects.
- `gsh_tp_curriculr_guard_current_claims()` → returns `array|null` — matches how `repo_put` reads it (`$guard ? $guard['sub'] : ''`).
- `gsh_tp_curriculr_repo_save_revision($sj, $version, $json_str, $author_sub, $author_name)` — all 5 params used in Task 4 Step 4 call.
