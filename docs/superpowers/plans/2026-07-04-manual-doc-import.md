# Manueller Planungsdokument-Import (SSO-Alternative) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a WP admin manually upload a JSON planning-document export from the SPA into a schoolyear's card in wp-admin, so schools without IServ/SSO can still publish their plan.

**Architecture:** Additive-only. A new pure decode/validate function and an additive `repo_put()` parameter live in `curriculr-data-layer.php`. Two new WP-glue handlers (inline POST for import, `admin-post.php` for the file-download export) write into the existing `wp_curriculr_docs` table via the existing `repo_put()`/`after_put()` pipeline. UI is a new section inside the existing per-schoolyear card in `gsh_tp_render_profile_tab_v2()`. No REST route, no `curriculr-auth.php`/`curriculr-guard.php` change, no DB schema change.

**Tech Stack:** PHP (WordPress plugin, procedural, `gsh_tp_curriculr_*` prefix), dependency-free test harness (`php tests/curriculr/test-*.php`).

## Global Constraints

- No changes to `curriculr-auth.php` / `curriculr-guard.php` / any `curriculr/v1` REST route.
- No DB schema change — same `wp_curriculr_docs` / `wp_curriculr_doc_revisions` tables, no `GSH_TP_DB_VERSION` bump.
- Every PHP file touched must pass `php -l` before commit (project convention, see `curriculr-terminplan/CLAUDE.md`).
- File upload size limit: 2 MB (larger than the existing 512 KB settings-backup limit — a full schoolyear doc can exceed that).
- Manual upload is always a forced overwrite (`baseVersion` = current version at request time) — no 409-conflict path exposed to the admin; the confirmation checkbox is the safeguard instead.
- Version bump: 4.27.2 → 4.28.0 (minor, tag `NEU`) in all 4 required places (see `curriculr-terminplan/CLAUDE.md` "Versioning").
- Language: PHP identifiers/comments English; WP admin UI strings German (existing convention).
- Spec: `docs/superpowers/specs/2026-07-04-manual-doc-import-design.md`

---

### Task 1: Pure upload-decode function

**Files:**
- Modify: `plugin/curriculr-data-layer.php` (insert after `gsh_tp_curriculr_validate_envelope()`, which ends at line 195, before the `/* ---------- Pure: Stufen-Normalisierung ... ---------- */` comment at line 197)
- Test: `tests/curriculr/test-doc-upload.php` (new)

**Interfaces:**
- Consumes: `gsh_tp_curriculr_validate_envelope( array $body ): array{valid: bool, errors: string[]}` (already exists, `curriculr-data-layer.php:181`)
- Produces: `gsh_tp_curriculr_decode_doc_upload( string $raw ): array{valid: bool, doc?: array, errors?: string[]}` — consumed by Task 3's `gsh_tp_curriculr_handle_doc_import()`

- [ ] **Step 1: Write the failing test**

Create `tests/curriculr/test-doc-upload.php`:

```php
<?php
require __DIR__ . '/assert.php';
require __DIR__ . '/../../plugin/curriculr-data-layer.php';

$sample = file_get_contents( __DIR__ . '/fixtures/sample-doc.json' );

$ok = gsh_tp_curriculr_decode_doc_upload( $sample );
gsh_assert_eq( $ok['valid'], true, 'valid sample doc passes' );
gsh_assert_true( is_array( $ok['doc'] ), 'decoded doc is array' );
gsh_assert_eq( $ok['doc']['meta']['name'], 'Terminplan 2026/27', 'decoded doc keeps meta.name' );

$bad_json = gsh_tp_curriculr_decode_doc_upload( '{not valid json' );
gsh_assert_eq( $bad_json['valid'], false, 'invalid JSON fails' );
gsh_assert_contains( implode( ',', $bad_json['errors'] ), 'invalid_json', 'reports invalid_json' );

$no_events = gsh_tp_curriculr_decode_doc_upload( json_encode( array( 'meta' => array( 'name' => 'x' ) ) ) );
gsh_assert_eq( $no_events['valid'], false, 'doc without events array fails' );
gsh_assert_contains( implode( ',', $no_events['errors'] ), 'doc_events_missing', 'reports doc_events_missing' );

$not_object = gsh_tp_curriculr_decode_doc_upload( '"just a string"' );
gsh_assert_eq( $not_object['valid'], false, 'JSON scalar (non-object) fails' );

gsh_test_done();
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/curriculr/test-doc-upload.php`
Expected: PHP fatal error — `Call to undefined function gsh_tp_curriculr_decode_doc_upload()`

- [ ] **Step 3: Write minimal implementation**

In `plugin/curriculr-data-layer.php`, insert this new function immediately after the closing `}` of `gsh_tp_curriculr_validate_envelope()` (line 195), before the `/* ---------- Pure: Stufen-Normalisierung ... ---------- */` comment:

```php
/* ---------- Pure: Datei-Upload-Dekodierung (manueller Import, Spec 2026-07-04) ---------- */

function gsh_tp_curriculr_decode_doc_upload( $raw ) {
    $decoded = json_decode( (string) $raw, true );
    if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
        return array( 'valid' => false, 'errors' => array( 'invalid_json' ) );
    }
    $envelope = gsh_tp_curriculr_validate_envelope( array( 'doc' => $decoded, 'baseVersion' => 0 ) );
    if ( ! $envelope['valid'] ) {
        return array( 'valid' => false, 'errors' => $envelope['errors'] );
    }
    return array( 'valid' => true, 'doc' => $decoded );
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php tests/curriculr/test-doc-upload.php`
Expected: `ALL PASS`

- [ ] **Step 5: Lint and commit**

Run: `php -l plugin/curriculr-data-layer.php`
Expected: `No syntax errors detected`

```bash
git add plugin/curriculr-data-layer.php tests/curriculr/test-doc-upload.php
git commit -m "feat: add pure decode/validate function for manual doc-upload"
```

---

### Task 2: `repo_put()` author override

**Files:**
- Modify: `plugin/curriculr-data-layer.php:324` (function signature) and `:367-373` (author lookup block)
- Test: `tests/curriculr/test-revisions.php` (extend, insert before `gsh_test_done();` at line 187)

**Interfaces:**
- Consumes: nothing new
- Produces: `gsh_tp_curriculr_repo_put( string $sj, array $doc, int $base_version, string $stage = 'entwurf', ?array $author_override = null ): array` — `$author_override`, when non-null, is `['sub' => string, 'name' => string]`. Consumed by Task 3's `gsh_tp_curriculr_handle_doc_import()`.

- [ ] **Step 1: Write the failing test**

Open `tests/curriculr/test-revisions.php`. Insert the following immediately before the final `gsh_test_done();` (currently line 187):

```php
/* ---------- 10. Author override takes precedence over guard claims ---------- */
$GLOBALS['wpdb'] = new Gsh_Fake_Wpdb_Rev();
$GLOBALS['gsh_tp_curriculr_current_claims'] = array( 'sub' => 'iserv-should-not-be-used', 'name' => 'Guard Name' );
gsh_tp_curriculr_repo_put( 'sj_override', $doc, 0, 'entwurf', array( 'sub' => 'manual:1', 'name' => 'Admin Manuell' ) );
$rev_override = reset( $GLOBALS['wpdb']->revs );
gsh_assert_eq( $rev_override['author_sub'], 'manual:1', 'author_override wins over guard claims (sub)' );
gsh_assert_eq( $rev_override['author_name'], 'Admin Manuell', 'author_override wins over guard claims (name)' );

/* ---------- 11. Omitting author_override preserves existing guard-claims behavior ---------- */
$GLOBALS['wpdb'] = new Gsh_Fake_Wpdb_Rev();
$GLOBALS['gsh_tp_curriculr_current_claims'] = array( 'sub' => 'iserv-u55', 'name' => 'Dana' );
gsh_tp_curriculr_repo_put( 'sj_no_override', $doc, 0 );
$rev_default = reset( $GLOBALS['wpdb']->revs );
gsh_assert_eq( $rev_default['author_sub'], 'iserv-u55', 'no override -> falls back to guard claims (sub)' );
gsh_assert_eq( $rev_default['author_name'], 'Dana', 'no override -> falls back to guard claims (name)' );
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/curriculr/test-revisions.php`
Expected: `FAIL: author_override wins over guard claims (sub)` (actual will be `'iserv-should-not-be-used'` — the 5th argument is silently ignored by the current signature)

- [ ] **Step 3: Write minimal implementation**

In `plugin/curriculr-data-layer.php`, change the function signature (line 324) from:

```php
function gsh_tp_curriculr_repo_put( $sj, $doc, $base_version, $stage = 'entwurf' ) {
```

to:

```php
function gsh_tp_curriculr_repo_put( $sj, $doc, $base_version, $stage = 'entwurf', $author_override = null ) {
```

Then change the author-lookup block (lines 367-373) from:

```php
    // Revision-Snapshot + Retention-Prune.
    $json_str    = wp_json_encode( $doc );
    $guard       = function_exists( 'gsh_tp_curriculr_guard_current_claims' )
        ? gsh_tp_curriculr_guard_current_claims()
        : null;
    $author_sub  = $guard ? (string) ( $guard['sub'] ?? '' ) : '';
    $author_name = $guard ? (string) ( $guard['name'] ?? '' ) : '';
```

to:

```php
    // Revision-Snapshot + Retention-Prune.
    $json_str    = wp_json_encode( $doc );
    $guard       = null !== $author_override
        ? $author_override
        : ( function_exists( 'gsh_tp_curriculr_guard_current_claims' ) ? gsh_tp_curriculr_guard_current_claims() : null );
    $author_sub  = $guard ? (string) ( $guard['sub'] ?? '' ) : '';
    $author_name = $guard ? (string) ( $guard['name'] ?? '' ) : '';
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php tests/curriculr/test-revisions.php`
Expected: `ALL PASS`

Also re-run Task 1's test to confirm no regression: `php tests/curriculr/test-doc-upload.php` → `ALL PASS`

- [ ] **Step 5: Lint and commit**

Run: `php -l plugin/curriculr-data-layer.php`
Expected: `No syntax errors detected`

```bash
git add plugin/curriculr-data-layer.php tests/curriculr/test-revisions.php
git commit -m "feat: add optional author_override param to repo_put for manual imports"
```

---

### Task 3: Import/export WP-glue handlers

**Files:**
- Modify: `plugin/curriculr-data-layer.php` (insert new functions after `gsh_tp_curriculr_handle_import()`, which ends at line 1226, before the `/* ---------- WP: Hooks ... ---------- */` comment at line 1228; add one line to the hooks block at lines 1247-1249)

**Interfaces:**
- Consumes: Task 1's `gsh_tp_curriculr_decode_doc_upload()`, Task 2's `gsh_tp_curriculr_repo_put( ..., $author_override )`, existing `gsh_tp_curriculr_repo_get()`, `gsh_tp_curriculr_normalize_stage()`, `gsh_tp_curriculr_after_put()`, and `gsh_tp_get_schoolyears()` (defined in `gsh-terminplan.php`, already loaded first per the require order in `gsh-terminplan.php:610-612`).
- Produces: `gsh_tp_curriculr_handle_doc_import(): void` (echoes a notice `<div>`, no return value) — consumed by Task 4's dispatch line in `gsh_tp_settings_page()`. `gsh_tp_curriculr_handle_doc_export(): void` (never returns — calls `exit`) — registered on the `admin_post_gsh_tp_curriculr_doc_export` action, consumed by Task 4's "Sichern ↓" link.

These two functions are WP-glue (nonce, `$_FILES`, `current_user_can`, `header()`/`exit`) and are **not** unit-tested, matching the existing convention for `gsh_tp_curriculr_handle_export()` / `gsh_tp_curriculr_handle_import()` (settings backup) — see the spec's Testing section. Verification here is `php -l` plus the end-to-end manual check in Task 4.

- [ ] **Step 1: Add the two handler functions**

In `plugin/curriculr-data-layer.php`, insert immediately after the closing `}` of `gsh_tp_curriculr_handle_import()` (line 1226), before the `/* ---------- WP: Hooks (nur unter WordPress aktiv) ---------- */` comment (line 1228):

```php
/**
 * POST-Handler: Planungsdokument (JSON) manuell hochladen — SSO-Alternative.
 *
 * Inline-Handler (kein admin-post.php/exit) — läuft mitten in gsh_tp_settings_page()
 * und gibt ein <div class="notice"> zurück, analog zu gsh_tp_handle_new_schoolyear().
 * Erzwingt Überschreiben (baseVersion = aktuelle Version), da der Admin bereits über
 * die Bestätigungs-Checkbox zugestimmt hat — keine 409-Konflikt-UI im manuellen Pfad.
 *
 * @since 4.28.0
 * @return void
 */
function gsh_tp_curriculr_handle_doc_import() {
    $sy_key = sanitize_key( wp_unslash( $_POST['gsh_tp_di_sy'] ?? '' ) );
    $pid    = sanitize_key( $sy_key );
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ 'gsh_tp_di_n_' . $pid ] ?? '' ) ), 'gsh_tp_doc_import_' . $pid ) ) {
        echo '<div class="notice notice-error"><p>Sicherheitsprüfung fehlgeschlagen.</p></div>';
        return;
    }

    $known = false;
    foreach ( gsh_tp_get_schoolyears() as $sy ) {
        if ( $sy['key'] === $sy_key ) { $known = true; break; }
    }
    if ( ! $known ) {
        echo '<div class="notice notice-error"><p>Unbekanntes Schuljahr.</p></div>';
        return;
    }

    $current_row = gsh_tp_curriculr_repo_get( $sy_key );
    if ( $current_row && empty( $_POST['gsh_tp_di_confirm'] ) ) {
        echo '<div class="notice notice-error"><p>Bitte bestätige, dass der aktuelle Stand überschrieben werden soll.</p></div>';
        return;
    }

    $upload_error = $_FILES['gsh_tp_di_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ( empty( $_FILES['gsh_tp_di_file']['tmp_name'] ) || UPLOAD_ERR_OK !== $upload_error ) {
        echo '<div class="notice notice-error"><p>Keine Datei ausgewählt.</p></div>';
        return;
    }
    if ( (int) ( $_FILES['gsh_tp_di_file']['size'] ?? 0 ) > 2 * 1024 * 1024 ) {
        echo '<div class="notice notice-error"><p>Datei zu groß (max. 2 MB).</p></div>';
        return;
    }

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $raw    = file_get_contents( $_FILES['gsh_tp_di_file']['tmp_name'] );
    $parsed = gsh_tp_curriculr_decode_doc_upload( $raw );
    if ( ! $parsed['valid'] ) {
        echo '<div class="notice notice-error"><p>Ungültiges Dokumentformat. Bitte eine Curriculr-JSON-Backup-Datei wählen.</p></div>';
        return;
    }

    $stage        = gsh_tp_curriculr_normalize_stage( wp_unslash( $_POST['gsh_tp_di_stage'] ?? 'entwurf' ) );
    $base_version = $current_row ? (int) $current_row['version'] : 0;
    $user         = wp_get_current_user();
    $author       = array( 'sub' => 'manual:' . get_current_user_id(), 'name' => (string) $user->display_name );

    $res = gsh_tp_curriculr_repo_put( $sy_key, $parsed['doc'], $base_version, $stage, $author );
    gsh_tp_curriculr_after_put( $sy_key, $res['feed_token'] );

    echo '<div class="notice notice-success"><p>Planungsdokument für <strong>' . esc_html( $sy_key ) . '</strong> hochgeladen (Version ' . (int) $res['version'] . ').</p></div>';
}

/**
 * admin-post.php-Handler: Aktuellen Planungsdokument-Stand als JSON herunterladen.
 *
 * Braucht header()+exit für den Datei-Download — läuft deshalb über die separate
 * admin-post.php-Request statt inline in gsh_tp_settings_page() (dort ist zum
 * Zeitpunkt des Seiten-Callbacks bereits HTML gesendet, header() würde fehlschlagen).
 *
 * @since 4.28.0
 * @return void
 */
function gsh_tp_curriculr_handle_doc_export() {
    $sj = sanitize_key( wp_unslash( $_GET['sj'] ?? '' ) );
    check_admin_referer( 'gsh_tp_curriculr_doc_export_' . $sj );
    if ( ! current_user_can( 'manage_options' ) ) {
        status_header( 403 );
        exit;
    }
    $row = gsh_tp_curriculr_repo_get( $sj );
    if ( ! $row ) {
        status_header( 404 );
        exit;
    }
    $payload = $row['json'];
    header( 'Content-Type: application/json; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $sj ) . '-' . gmdate( 'Y-m-d' ) . '.json"' );
    header( 'Content-Length: ' . strlen( $payload ) );
    header( 'Cache-Control: no-cache, no-store' );
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rohe JSON-Ausgabe, bewusst kein wp_die.
    echo $payload;
    exit;
}
```

- [ ] **Step 2: Register the export admin-post hook**

In `plugin/curriculr-data-layer.php`, in the existing hooks block, change:

```php
    add_action( 'gsh_tp_curriculr_daily_backup', 'gsh_tp_curriculr_backup_cron' );
    add_action( 'admin_post_gsh_tp_curriculr_export', 'gsh_tp_curriculr_handle_export' );
    add_action( 'admin_post_gsh_tp_import_settings',  'gsh_tp_curriculr_handle_import' );
```

to:

```php
    add_action( 'gsh_tp_curriculr_daily_backup', 'gsh_tp_curriculr_backup_cron' );
    add_action( 'admin_post_gsh_tp_curriculr_export', 'gsh_tp_curriculr_handle_export' );
    add_action( 'admin_post_gsh_tp_import_settings',  'gsh_tp_curriculr_handle_import' );
    add_action( 'admin_post_gsh_tp_curriculr_doc_export', 'gsh_tp_curriculr_handle_doc_export' );
```

- [ ] **Step 3: Lint**

Run: `php -l plugin/curriculr-data-layer.php`
Expected: `No syntax errors detected`

- [ ] **Step 4: Run existing test suite to confirm no regression**

Run each of:
```bash
php tests/curriculr/test-doc-upload.php
php tests/curriculr/test-revisions.php
php tests/curriculr/test-envelope.php
php tests/curriculr/test-settings-backup.php
php tests/curriculr/test-integration-stubbed.php
```
Expected: `ALL PASS` for every file (these two new functions are not called by any existing test, so this only confirms no syntax/definition collisions).

- [ ] **Step 5: Commit**

```bash
git add plugin/curriculr-data-layer.php
git commit -m "feat: add doc-import/export WP-glue handlers for manual SSO-alternative upload"
```

---

### Task 4: wp-admin wiring — dispatch, doc-status version, UI, version bump

**Files:**
- Modify: `plugin/gsh-terminplan.php`:
  - `:6` (header `Version:`), `:10-11` (header changelog block), `:602` (`GSH_TP_VERSION` define), `:844-851` (`gsh_tp_changelog()` array)
  - `:3790-3797` (POST dispatch block in `gsh_tp_settings_page()`)
  - `:1556-1574` (`gsh_tp_get_doc_status()`)
  - `:4683-4719` (per-schoolyear card render in `gsh_tp_render_profile_tab_v2()`)

**Interfaces:**
- Consumes: Task 3's `gsh_tp_curriculr_handle_doc_import()` (called from dispatch), `admin_post_gsh_tp_curriculr_doc_export` action (linked from UI)
- Produces: `gsh_tp_get_doc_status( string $sj_key ): ?array{stage: string, last_sent: string, version: int}` — return shape gains `version` (additive; only caller is this same render function, no test references it)

- [ ] **Step 1: Add `version` to `gsh_tp_get_doc_status()`**

In `plugin/gsh-terminplan.php`, change:

```php
function gsh_tp_get_doc_status( $sj_key ) {
    global $wpdb;
    $table = $wpdb->prefix . 'curriculr_docs';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT stage, updated_at FROM `{$table}` WHERE schoolyear = %s LIMIT 1",
            $sj_key
        ),
        ARRAY_A
    );
    if ( ! $row ) {
        return null;
    }
    return array(
        'stage'     => (string) ( $row['stage']      ?? 'entwurf' ),
        'last_sent' => (string) ( $row['updated_at'] ?? '' ),
    );
}
```

to:

```php
function gsh_tp_get_doc_status( $sj_key ) {
    global $wpdb;
    $table = $wpdb->prefix . 'curriculr_docs';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT stage, updated_at, version FROM `{$table}` WHERE schoolyear = %s LIMIT 1",
            $sj_key
        ),
        ARRAY_A
    );
    if ( ! $row ) {
        return null;
    }
    return array(
        'stage'     => (string) ( $row['stage']      ?? 'entwurf' ),
        'last_sent' => (string) ( $row['updated_at'] ?? '' ),
        'version'   => (int) ( $row['version'] ?? 0 ),
    );
}
```

- [ ] **Step 2: Add the dispatch line**

In `gsh_tp_settings_page()`, change:

```php
    // POST: new schoolyear admin actions (4.24.0)
    if ( isset( $_POST['gsh_tp_new_schoolyear'] ) )      { gsh_tp_handle_new_schoolyear(); }
    if ( isset( $_POST['gsh_tp_save_schoolyear'] ) )     { gsh_tp_handle_save_schoolyear(); }
    if ( isset( $_POST['gsh_tp_save_shared'] ) )         { gsh_tp_handle_save_shared(); }
    if ( isset( $_POST['gsh_tp_activate_schoolyear'] ) ) { gsh_tp_handle_activate_schoolyear(); }
    if ( isset( $_POST['gsh_tp_del_cal'] ) )             { gsh_tp_handle_delete_calendar(); }
    if ( isset( $_POST['gsh_tp_toggle_draft'] ) )        { gsh_tp_handle_toggle_draft(); }
    if ( isset( $_POST['gsh_tp_del_sy'] ) )              { gsh_tp_handle_delete_schoolyear(); }
```

to:

```php
    // POST: new schoolyear admin actions (4.24.0)
    if ( isset( $_POST['gsh_tp_new_schoolyear'] ) )      { gsh_tp_handle_new_schoolyear(); }
    if ( isset( $_POST['gsh_tp_save_schoolyear'] ) )     { gsh_tp_handle_save_schoolyear(); }
    if ( isset( $_POST['gsh_tp_save_shared'] ) )         { gsh_tp_handle_save_shared(); }
    if ( isset( $_POST['gsh_tp_activate_schoolyear'] ) ) { gsh_tp_handle_activate_schoolyear(); }
    if ( isset( $_POST['gsh_tp_del_cal'] ) )             { gsh_tp_handle_delete_calendar(); }
    if ( isset( $_POST['gsh_tp_toggle_draft'] ) )        { gsh_tp_handle_toggle_draft(); }
    if ( isset( $_POST['gsh_tp_del_sy'] ) )              { gsh_tp_handle_delete_schoolyear(); }
    if ( isset( $_POST['gsh_tp_doc_import'] ) )          { gsh_tp_curriculr_handle_doc_import(); }
```

- [ ] **Step 3: Add the UI section**

In `gsh_tp_render_profile_tab_v2()`, change:

```php
        </div>
        <?php endif; ?>

        <!-- Shared Settings (Quartal etc.) -->
        <div style="padding:12px 16px;border-bottom:1px solid #c3c4c7">
```

to:

```php
        </div>
        <?php endif; ?>

        <!-- Planungsdokument: manueller Upload (SSO-Alternative, 4.28.0) -->
        <div style="padding:12px 16px;border-bottom:1px solid #c3c4c7;background:#fafafa">
            <strong style="display:block;margin-bottom:6px">Planungsdokument (manueller Upload)</strong>
            <p class="description" style="margin:0 0 8px">
                Für Schulen ohne IServ-SSO: Plan im Planer exportieren (Export ↓ → „JSON-Backup") und hier hochladen.
            </p>
            <?php if ( $doc_status ) :
                $export_nonce = wp_create_nonce( 'gsh_tp_curriculr_doc_export_' . $pid );
                $export_url   = admin_url( 'admin-post.php?action=gsh_tp_curriculr_doc_export&sj=' . rawurlencode( $sy_key ) . '&_wpnonce=' . $export_nonce );
            ?>
            <p style="margin:0 0 8px">
                Aktueller Stand: Version <?php echo (int) $doc_status['version']; ?><?php echo $s_time ? ', ' . esc_html( $s_time ) : ''; ?>
                — <a href="<?php echo esc_url( $export_url ); ?>">Sichern ↓</a>
            </p>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <?php wp_nonce_field( 'gsh_tp_doc_import_' . $pid, 'gsh_tp_di_n_' . $pid ); ?>
                <input type="hidden" name="gsh_tp_di_sy" value="<?php echo esc_attr( $sy_key ); ?>" />
                <input type="file" name="gsh_tp_di_file" accept=".json" required />
                <select name="gsh_tp_di_stage">
                    <option value="entwurf">Entwurf</option>
                    <option value="genehmigt">Intern</option>
                    <option value="oeffentlich">Öffentlich</option>
                </select>
                <?php if ( $doc_status ) : ?>
                <label style="font-size:12px">
                    <input type="checkbox" name="gsh_tp_di_confirm" value="1" /> aktuellen Stand überschreiben
                </label>
                <?php endif; ?>
                <button type="submit" name="gsh_tp_doc_import" value="1" class="button">
                    <?php echo $doc_status ? 'Dokument aktualisieren' : 'Dokument hochladen'; ?>
                </button>
            </form>
        </div>

        <!-- Shared Settings (Quartal etc.) -->
        <div style="padding:12px 16px;border-bottom:1px solid #c3c4c7">
```

Note: `$pid` and `$sy_key` are already in scope from the enclosing `foreach ( $schoolyears as $sy ) : $sy_key = $sy['key']; $pid = sanitize_key( $sy_key );` loop header (existing code, ~line 4645-4648). `$s_time` is already in scope from the doc-status block immediately above (existing code, computed inside `if ( $doc_status ) :` at line 4696-4701).

- [ ] **Step 4: Version bump — plugin header**

In `plugin/gsh-terminplan.php`, change the header comment (lines 6-11) from:

```php
 * Version:     4.27.2
 * Author:      Open Source Community
 * License:     GPL v2 or later
 * Text Domain: gsh-terminplan
 * v4.27.2
 * - [SECURITY] Entwurf-Kiosk-Template (page-terminplan-entwurf.php): CSP frame-ancestors + X-Frame-Options SAMEORIGIN ergänzt — fehlten bisher komplett, wodurch Server-/Host-Default das IServ-iframe-Embedding blockierte (Live-Kiosk-Template war bereits korrekt)
```

to:

```php
 * Version:     4.28.0
 * Author:      Open Source Community
 * License:     GPL v2 or later
 * Text Domain: gsh-terminplan
 * v4.28.0
 * - [NEU] Schuljahr-Karte: manueller Planungsdokument-Upload (JSON) als Alternative zu IServ-SSO — Admin exportiert im Planer "JSON-Backup" und lädt es hier hoch; inkl. "Sichern ↓"-Download des aktuellen Stands vor dem Überschreiben
 * Changelog 4.27.2:
 * - [SECURITY] Entwurf-Kiosk-Template (page-terminplan-entwurf.php): CSP frame-ancestors + X-Frame-Options SAMEORIGIN ergänzt — fehlten bisher komplett, wodurch Server-/Host-Default das IServ-iframe-Embedding blockierte (Live-Kiosk-Template war bereits korrekt)
```

- [ ] **Step 5: Version bump — `GSH_TP_VERSION` define**

In `plugin/gsh-terminplan.php`, change:

```php
define( 'GSH_TP_VERSION',       '4.27.2' );
```

to:

```php
define( 'GSH_TP_VERSION',       '4.28.0' );
```

- [ ] **Step 6: Version bump — `gsh_tp_changelog()` array**

In `plugin/gsh-terminplan.php`, change:

```php
function gsh_tp_changelog() {
    return array(
        array(
            'version' => '4.27.2',
            'entries' => array(
                array( 'tag' => 'SECURITY', 'text' => 'Entwurf-Kiosk-Template: CSP frame-ancestors + X-Frame-Options SAMEORIGIN ergänzt — fehlten bisher komplett, wodurch Server-/Host-Default das IServ-iframe-Embedding blockierte' ),
            ),
        ),
```

to:

```php
function gsh_tp_changelog() {
    return array(
        array(
            'version' => '4.28.0',
            'entries' => array(
                array( 'tag' => 'NEU', 'text' => 'Schuljahr-Karte: manueller Planungsdokument-Upload (JSON) als Alternative zu IServ-SSO — inkl. "Sichern ↓"-Download des aktuellen Stands vor dem Überschreiben' ),
            ),
        ),
        array(
            'version' => '4.27.2',
            'entries' => array(
                array( 'tag' => 'SECURITY', 'text' => 'Entwurf-Kiosk-Template: CSP frame-ancestors + X-Frame-Options SAMEORIGIN ergänzt — fehlten bisher komplett, wodurch Server-/Host-Default das IServ-iframe-Embedding blockierte' ),
            ),
        ),
```

- [ ] **Step 7: Lint**

Run: `php -l plugin/gsh-terminplan.php`
Expected: `No syntax errors detected`

- [ ] **Step 8: Run full test suite**

Run every file in `tests/curriculr/`:
```bash
for f in tests/curriculr/test-*.php; do echo "== $f =="; php "$f" || break; done
```
Expected: `ALL PASS` for every file, loop does not break early.

- [ ] **Step 9: Manual smoke test**

1. On a local/staging WP with the plugin active, go to Einstellungen → Schul-Terminplan → Schuljahre.
2. In an existing schoolyear's card, confirm the new "Planungsdokument (manueller Upload)" section renders below "Veröffentlichung".
3. Export a "JSON-Backup" from the SPA for a matching plan (or reuse `tests/curriculr/fixtures/sample-doc.json` renamed to `.json`), upload it via the new form, submit.
4. Confirm the success notice shows, "Veröffentlichung" status block now shows updated version/timestamp, and (if a calendar is provisioned) the ICS feed URL returns the new events.
5. Click "Sichern ↓" — confirm a `.json` file downloads containing the doc just uploaded.
6. Re-upload without checking "aktuellen Stand überschreiben" — confirm the error notice appears and no version bump occurs.

- [ ] **Step 10: Commit**

```bash
git add plugin/gsh-terminplan.php
git commit -m "feat: wire up manual doc-import UI, dispatch and version bump to 4.28.0"
```

---

### Task 5 (optional): SPA copy hint

**Files:**
- Modify: `curriculr-planner/src/components/settings/PublishTab.tsx:293-309` (the existing "Export" section)

**Interfaces:**
- Consumes: nothing (copy-only)
- Produces: nothing (no exported symbols change)

This task is copy-only, does not touch the sync protocol, and does not require a `package.json` version bump per the workspace `CLAUDE.md` cross-repo rule (no REST-shape/sync-client-behavior change).

- [ ] **Step 1: Add the hint**

In `curriculr-planner/src/components/settings/PublishTab.tsx`, change:

```tsx
      {/* Export */}
      <section className="space-y-3 border-t pt-4">
        <h3 className="text-[12px] font-semibold text-[var(--color-ink-500)] uppercase tracking-[0.05em]">
          Export
        </h3>
        <p className="text-[13px] text-[var(--color-ink-500)]">
          Plan als Datei exportieren. Das Export-Menü oben rechts bietet dieselben Optionen.
        </p>
        <div className="flex gap-2 flex-wrap">
          <Button variant="outline" onClick={exportIcs} disabled={!doc}>
            ICS-Datei (.ics)
          </Button>
          <Button variant="outline" onClick={exportExcel} disabled={!doc}>
            Excel-Konverter-Format (.xlsx)
          </Button>
        </div>
      </section>
```

to:

```tsx
      {/* Export */}
      <section className="space-y-3 border-t pt-4">
        <h3 className="text-[12px] font-semibold text-[var(--color-ink-500)] uppercase tracking-[0.05em]">
          Export
        </h3>
        <p className="text-[13px] text-[var(--color-ink-500)]">
          Plan als Datei exportieren. Das Export-Menü oben rechts bietet dieselben Optionen.
        </p>
        <div className="flex gap-2 flex-wrap">
          <Button variant="outline" onClick={exportIcs} disabled={!doc}>
            ICS-Datei (.ics)
          </Button>
          <Button variant="outline" onClick={exportExcel} disabled={!doc}>
            Excel-Konverter-Format (.xlsx)
          </Button>
        </div>
        <p className="text-[12px] text-[var(--color-ink-500)]">
          Keine IServ-SSO-Anbindung? Lade das „JSON-Backup" (Export-Menü oben rechts) herunter und
          trage es im WordPress-Backend unter Einstellungen → Schul-Terminplan → Schuljahre in der
          Karte des jeweiligen Schuljahres hoch.
        </p>
      </section>
```

- [ ] **Step 2: Verify**

Run: `npm run typecheck && npm run lint`
Expected: both exit 0, no new errors.

- [ ] **Step 3: Commit**

```bash
git add src/components/settings/PublishTab.tsx
git commit -m "docs: hint at manual wp-admin doc upload for schools without SSO"
```
