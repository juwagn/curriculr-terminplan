# Settings-Schutz Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add settings export/import, a delete-warning modal, and fix the incomplete uninstall cleanup in the Curriculr WP plugin.

**Architecture:** New functions `gsh_tp_curriculr_gather_settings()` and `gsh_tp_curriculr_apply_settings()` live in `curriculr-data-layer.php`. A new "Datensicherung" submenu page and delete-warning JS live in `gsh-terminplan.php` and a new `assets/js/curriculr-delete-warn.js`. Export/import use plain `admin-post.php` form submissions (no AJAX) to keep the download flow simple and JS-free for the export itself.

**Tech Stack:** PHP 7.4+, WordPress hooks/options API, vanilla JS (ES5), no build step.

## Global Constraints

- `php -l` must pass on all modified PHP files before every commit.
- All CSS goes in `assets/css/gsh-terminplan.css` — never inline PHP heredocs.
- WordPress Coding Standards PHP style.
- No changes to `curriculr-auth.php`, `curriculr-guard.php`, `wp_curriculr_docs` table, or `wp_curriculr_doc_revisions` table.
- Version bump: `4.20.1` → `4.21.0` (4 places in `gsh-terminplan.php`, done in Task 6).
- Run all tests in `tests/curriculr/` with `php tests/curriculr/test-<name>.php` — exit code 0 = pass.

---

## File Map

| File | Change |
|------|--------|
| `plugin/gsh-terminplan.php` | Add submenu, `gsh_tp_backup_page()`, `plugin_action_links` hook, admin script enqueue, 3 options to uninstall, cron cleanup to uninstall, version bump |
| `plugin/curriculr-data-layer.php` | Add `gsh_tp_curriculr_gather_settings()`, `gsh_tp_curriculr_apply_settings()`, `gsh_tp_curriculr_handle_export()`, `gsh_tp_curriculr_handle_import()`, register hooks |
| `assets/js/curriculr-delete-warn.js` | New: delete-warning modal, scoped to `plugins.php` |
| `assets/css/gsh-terminplan.css` | New backup-page section styles |
| `tests/curriculr/test-settings-backup.php` | New: unit tests for gather/apply helpers |

---

## Task 1: Fix uninstall cleanup

**Files:**
- Modify: `plugin/gsh-terminplan.php:8410-8425` (uninstall foreach array)
- Modify: `plugin/gsh-terminplan.php:8454-8456` (end of uninstall, before closing brace)

**Interfaces:**
- Produces: nothing new — corrects existing `gsh_tp_uninstall()`

- [ ] **Step 1: Add the 3 missing curriculr options to the uninstall foreach**

In `gsh-terminplan.php`, find `gsh_tp_uninstall()`. The `foreach` array ends with `GSH_TP_BACKUP_KEY,`. Add three lines before the closing `) as $opt ) {`:

```php
        'gsh_tp_ical_url',
        'gsh_tp_cache_duration',
        'gsh_tp_schuljahr_start',
        'gsh_tp_quartal_grenzen',
        'gsh_tp_kategorie_mapping',
        'gsh_tp_categories',       // konfigurierbare Kategorien (seit 3.4.0)
        'gsh_tp_kiosk_token',
        'gsh_tp_draft_kiosk_token',
        'gsh_tp_iserv_domain',
        'gsh_tp_last_sync',
        GSH_TP_CACHE_KEY,          // permanente Daten-Option (seit 3.3.0)
        GSH_TP_BACKUP_KEY,
        'gsh_tp_curriculr_origin',
        'gsh_tp_curriculr_profile_map',
        'gsh_tp_curriculr_db_version',
```

- [ ] **Step 2: Add missing cron cleanup to uninstall**

At line 8456, the function currently has `wp_clear_scheduled_hook( 'gsh_tp_cron_refresh' );` as its last statement before `}`. Add the curriculr cron cleanup after it:

```php
    wp_clear_scheduled_hook( 'gsh_tp_cron_refresh' );
    wp_clear_scheduled_hook( 'gsh_tp_curriculr_daily_backup' );
}
```

- [ ] **Step 3: Syntax check**

```bash
php -l plugin/gsh-terminplan.php
```

Expected: `No syntax errors detected in plugin/gsh-terminplan.php`

- [ ] **Step 4: Commit**

```bash
git add plugin/gsh-terminplan.php
git commit -m "fix: add missing curriculr options and cron to uninstall cleanup"
```

---

## Task 2: Implement `gsh_tp_curriculr_gather_settings()` and `gsh_tp_curriculr_apply_settings()` (TDD)

**Files:**
- Create: `tests/curriculr/test-settings-backup.php`
- Modify: `plugin/curriculr-data-layer.php` (insert after line 787, before `/* --- WP: Hooks --- */`)

**Interfaces:**
- `gsh_tp_curriculr_gather_settings(): array` — returns all config keys as key→value map
- `gsh_tp_curriculr_apply_settings(array $settings): void` — writes known keys to WP options

- [ ] **Step 1: Write the failing test**

Create `tests/curriculr/test-settings-backup.php`:

```php
<?php
define( 'GSH_TP_CURRICULR_TEST', true );
define( 'GSH_TP_VERSION', '4.21.0-test' );
define( 'GSH_TP_CACHE_VERSION', 3 );

require __DIR__ . '/assert.php';

/* ---------- WordPress stubs ---------- */
$GLOBALS['options'] = array();
function get_option( $k, $d = false ) { return $GLOBALS['options'][ $k ] ?? $d; }
function update_option( $k, $v, $a = null ) { $GLOBALS['options'][ $k ] = $v; return true; }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $k ) ); }
function gsh_tp_ck( $prefix, $pid ) { return $prefix . $pid . '_v' . GSH_TP_CACHE_VERSION; }

require __DIR__ . '/../../plugin/curriculr-data-layer.php';

/* ---------- gather_settings: returns all global config keys ---------- */
$GLOBALS['options'] = array(
    'gsh_tp_profiles'              => array( array( 'id' => 'p1' ), array( 'id' => 'p2' ) ),
    'gsh_tp_ical_url'              => 'https://example.com/feed.ics',
    'gsh_tp_cache_duration'        => 7200,
    'gsh_tp_schuljahr_start'       => '2026-08-24',
    'gsh_tp_quartal_grenzen'       => "2026-08-24|2026-10-30",
    'gsh_tp_kategorie_mapping'     => 'Konferenz|konferenz',
    'gsh_tp_categories'            => array( array( 'id' => 'c1', 'label' => 'Konferenz' ) ),
    'gsh_tp_kiosk_token'           => 'tok_live',
    'gsh_tp_draft_kiosk_token'     => 'tok_draft',
    'gsh_tp_iserv_domain'          => 'schule.iserv.de',
    'gsh_tp_curriculr_origin'      => 'https://juwagn.github.io/curriculr-planner/',
    'gsh_tp_curriculr_profile_map' => array( '2026-27' => 'p1' ),
    gsh_tp_ck( 'gsh_tp_ical_', 'p1' ) => 'https://wp.test/wp-json/curriculr/v1/feed/2026-27/token1.ics',
    gsh_tp_ck( 'gsh_tp_ical_', 'p2' ) => 'https://wp.test/wp-json/curriculr/v1/feed/2025-26/token2.ics',
);

$settings = gsh_tp_curriculr_gather_settings();

gsh_assert_eq( $settings['gsh_tp_ical_url'], 'https://example.com/feed.ics', 'gather: ical_url' );
gsh_assert_eq( $settings['gsh_tp_cache_duration'], 7200, 'gather: cache_duration' );
gsh_assert_eq( $settings['gsh_tp_schuljahr_start'], '2026-08-24', 'gather: schuljahr_start' );
gsh_assert_eq( $settings['gsh_tp_curriculr_origin'], 'https://juwagn.github.io/curriculr-planner/', 'gather: curriculr_origin' );
gsh_assert_eq( $settings['gsh_tp_curriculr_profile_map'], array( '2026-27' => 'p1' ), 'gather: profile_map' );
gsh_assert_true( is_array( $settings['gsh_tp_profiles'] ), 'gather: profiles is array' );
gsh_assert_eq( count( $settings['gsh_tp_profiles'] ), 2, 'gather: profiles count' );

$p1_key = gsh_tp_ck( 'gsh_tp_ical_', 'p1' );
$p2_key = gsh_tp_ck( 'gsh_tp_ical_', 'p2' );
gsh_assert_true( array_key_exists( $p1_key, $settings ), 'gather: per-profile key p1 present' );
gsh_assert_true( array_key_exists( $p2_key, $settings ), 'gather: per-profile key p2 present' );
gsh_assert_eq( $settings[ $p1_key ], 'https://wp.test/wp-json/curriculr/v1/feed/2026-27/token1.ics', 'gather: p1 ical value' );

/* ---------- apply_settings: round-trips through gather ---------- */
$GLOBALS['options'] = array();
gsh_tp_curriculr_apply_settings( $settings );

gsh_assert_eq( get_option( 'gsh_tp_ical_url' ), 'https://example.com/feed.ics', 'apply: ical_url written' );
gsh_assert_eq( get_option( 'gsh_tp_cache_duration' ), 7200, 'apply: cache_duration written' );
gsh_assert_eq( get_option( 'gsh_tp_curriculr_origin' ), 'https://juwagn.github.io/curriculr-planner/', 'apply: curriculr_origin written' );
gsh_assert_eq( get_option( $p1_key ), 'https://wp.test/wp-json/curriculr/v1/feed/2026-27/token1.ics', 'apply: p1 per-profile ical written' );
gsh_assert_eq( get_option( $p2_key ), 'https://wp.test/wp-json/curriculr/v1/feed/2025-26/token2.ics', 'apply: p2 per-profile ical written' );

/* ---------- apply_settings: ignores unknown keys ---------- */
$GLOBALS['options'] = array();
gsh_tp_curriculr_apply_settings( array(
    'gsh_tp_ical_url'   => 'https://safe.example.com/',
    'gsh_tp_evil_key'   => 'injected',
    '__proto__'         => 'poison',
) );
gsh_assert_eq( get_option( 'gsh_tp_ical_url' ), 'https://safe.example.com/', 'apply: allowlisted key written' );
gsh_assert_eq( get_option( 'gsh_tp_evil_key', null ), null, 'apply: unknown key ignored' );

/* ---------- apply_settings: no-op on non-array ---------- */
$GLOBALS['options'] = array( 'gsh_tp_ical_url' => 'original' );
gsh_tp_curriculr_apply_settings( 'not-an-array' );
gsh_assert_eq( get_option( 'gsh_tp_ical_url' ), 'original', 'apply: non-array input is no-op' );

gsh_test_done();
```

- [ ] **Step 2: Run test — confirm it fails**

```bash
php tests/curriculr/test-settings-backup.php
```

Expected: error like `Call to undefined function gsh_tp_curriculr_gather_settings()`

- [ ] **Step 3: Implement both functions in `curriculr-data-layer.php`**

Insert after `gsh_tp_curriculr_backup_cron()` (ends at the `}` before `/* --- WP: Hooks --- */`, currently line 787), before the `/* ---------- WP: Hooks ---------- */` comment:

```php
/* ---------- Settings Backup: Export / Import ---------- */

function gsh_tp_curriculr_gather_settings() {
    $profiles = get_option( 'gsh_tp_profiles', array() );
    $data     = array(
        'gsh_tp_profiles'              => $profiles,
        'gsh_tp_ical_url'              => get_option( 'gsh_tp_ical_url', '' ),
        'gsh_tp_cache_duration'        => get_option( 'gsh_tp_cache_duration', 3600 ),
        'gsh_tp_schuljahr_start'       => get_option( 'gsh_tp_schuljahr_start', '' ),
        'gsh_tp_quartal_grenzen'       => get_option( 'gsh_tp_quartal_grenzen', '' ),
        'gsh_tp_kategorie_mapping'     => get_option( 'gsh_tp_kategorie_mapping', '' ),
        'gsh_tp_categories'            => get_option( 'gsh_tp_categories', array() ),
        'gsh_tp_kiosk_token'           => get_option( 'gsh_tp_kiosk_token', '' ),
        'gsh_tp_draft_kiosk_token'     => get_option( 'gsh_tp_draft_kiosk_token', '' ),
        'gsh_tp_iserv_domain'          => get_option( 'gsh_tp_iserv_domain', '' ),
        'gsh_tp_curriculr_origin'      => get_option( 'gsh_tp_curriculr_origin', '' ),
        'gsh_tp_curriculr_profile_map' => get_option( 'gsh_tp_curriculr_profile_map', array() ),
    );
    if ( is_array( $profiles ) ) {
        foreach ( $profiles as $p ) {
            $pid = sanitize_key( $p['id'] ?? '' );
            if ( $pid ) {
                $ck          = gsh_tp_ck( 'gsh_tp_ical_', $pid );
                $data[ $ck ] = get_option( $ck, '' );
            }
        }
    }
    return $data;
}

function gsh_tp_curriculr_apply_settings( $settings ) {
    if ( ! is_array( $settings ) ) {
        return;
    }
    $allowlist = array(
        'gsh_tp_profiles',
        'gsh_tp_ical_url',
        'gsh_tp_cache_duration',
        'gsh_tp_schuljahr_start',
        'gsh_tp_quartal_grenzen',
        'gsh_tp_kategorie_mapping',
        'gsh_tp_categories',
        'gsh_tp_kiosk_token',
        'gsh_tp_draft_kiosk_token',
        'gsh_tp_iserv_domain',
        'gsh_tp_curriculr_origin',
        'gsh_tp_curriculr_profile_map',
    );
    foreach ( $allowlist as $key ) {
        if ( array_key_exists( $key, $settings ) ) {
            update_option( $key, $settings[ $key ] );
        }
    }
    $profiles = $settings['gsh_tp_profiles'] ?? array();
    if ( is_array( $profiles ) ) {
        foreach ( $profiles as $p ) {
            $pid = sanitize_key( $p['id'] ?? '' );
            if ( ! $pid ) {
                continue;
            }
            $ck = gsh_tp_ck( 'gsh_tp_ical_', $pid );
            if ( array_key_exists( $ck, $settings ) ) {
                update_option( $ck, $settings[ $ck ] );
            }
        }
    }
}
```

- [ ] **Step 4: Run test — confirm it passes**

```bash
php tests/curriculr/test-settings-backup.php
```

Expected: `ALL PASS`

- [ ] **Step 5: Syntax check**

```bash
php -l plugin/curriculr-data-layer.php
```

Expected: `No syntax errors detected`

- [ ] **Step 6: Commit**

```bash
git add plugin/curriculr-data-layer.php tests/curriculr/test-settings-backup.php
git commit -m "feat: add gather_settings and apply_settings helpers with tests"
```

---

## Task 3: Export and import POST handlers

**Files:**
- Modify: `plugin/curriculr-data-layer.php` (add two functions + two hook registrations)

**Interfaces:**
- Consumes: `gsh_tp_curriculr_gather_settings()` from Task 2, `gsh_tp_curriculr_apply_settings()` from Task 2
- Produces:
  - `gsh_tp_curriculr_handle_export()` — triggered by `admin_post_gsh_tp_curriculr_export`
  - `gsh_tp_curriculr_handle_import()` — triggered by `admin_post_gsh_tp_import_settings`

- [ ] **Step 1: Add handler functions to `curriculr-data-layer.php`**

Insert after `gsh_tp_curriculr_apply_settings()` (end of the Settings Backup section added in Task 2), before `/* --- WP: Hooks --- */`:

```php
function gsh_tp_curriculr_handle_export() {
    check_admin_referer( 'gsh_tp_curriculr_export_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Keine Berechtigung.', 403 );
    }
    $payload = wp_json_encode(
        array(
            'version'     => GSH_TP_VERSION,
            'exported_at' => gmdate( 'c' ),
            'settings'    => gsh_tp_curriculr_gather_settings(),
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );
    $date = gmdate( 'Y-m-d' );
    header( 'Content-Type: application/json; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="curriculr-settings-' . $date . '.json"' );
    header( 'Content-Length: ' . strlen( $payload ) );
    header( 'Cache-Control: no-cache, no-store' );
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $payload;
    exit;
}

function gsh_tp_curriculr_handle_import() {
    check_admin_referer( 'gsh_tp_import_settings' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Keine Berechtigung.', 403 );
    }
    $page_url = admin_url( 'options-general.php?page=gsh-terminplan-backup' );

    if ( empty( $_FILES['settings_file']['tmp_name'] ) ) {
        wp_safe_redirect( add_query_arg( 'import_error', '1', $page_url ) );
        exit;
    }
    if ( (int) ( $_FILES['settings_file']['size'] ?? 0 ) > 512 * 1024 ) {
        wp_safe_redirect( add_query_arg( 'import_error', '2', $page_url ) );
        exit;
    }
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $raw  = file_get_contents( $_FILES['settings_file']['tmp_name'] );
    $data = json_decode( $raw, true );
    if ( JSON_ERROR_NONE !== json_last_error()
        || ! is_array( $data )
        || ! isset( $data['settings'] )
        || ! is_array( $data['settings'] )
        || ! isset( $data['version'] )
    ) {
        wp_safe_redirect( add_query_arg( 'import_error', '3', $page_url ) );
        exit;
    }
    gsh_tp_curriculr_apply_settings( $data['settings'] );
    wp_safe_redirect( add_query_arg( 'imported', '1', $page_url ) );
    exit;
}
```

- [ ] **Step 2: Register hooks inside the `if ( function_exists( 'add_action' ) )` block**

Find the `if ( function_exists( 'add_action' ) ) {` block (currently around line 791). Add two lines after the last existing `add_action` in that block (after `add_action( 'gsh_tp_curriculr_daily_backup', ... )`):

```php
    add_action( 'admin_post_gsh_tp_curriculr_export', 'gsh_tp_curriculr_handle_export' );
    add_action( 'admin_post_gsh_tp_import_settings',  'gsh_tp_curriculr_handle_import' );
```

- [ ] **Step 3: Syntax check**

```bash
php -l plugin/curriculr-data-layer.php
```

Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add plugin/curriculr-data-layer.php
git commit -m "feat: add export and import admin-post handlers"
```

---

## Task 4: Datensicherung submenu page

**Files:**
- Modify: `plugin/gsh-terminplan.php` (add submenu to `gsh_tp_admin_menu()`, add `gsh_tp_backup_page()` function after it)

**Interfaces:**
- Consumes: `admin_post_gsh_tp_curriculr_export` and `admin_post_gsh_tp_import_settings` from Task 3
- Produces: WP admin page at `options-general.php?page=gsh-terminplan-backup`

- [ ] **Step 1: Add submenu entry to `gsh_tp_admin_menu()`**

In `gsh_tp_admin_menu()` (around line 2025), after the existing `add_options_page(...)` call:

```php
function gsh_tp_admin_menu() {
    add_options_page(
        'Schul-Terminplan',
        'Schul-Terminplan',
        'manage_options',
        GSH_TP_SLUG,
        'gsh_tp_settings_page'
    );
    add_options_page(
        'Schul-Terminplan – Datensicherung',
        'Datensicherung',
        'manage_options',
        'gsh-terminplan-backup',
        'gsh_tp_backup_page'
    );
}
```

- [ ] **Step 2: Add `gsh_tp_backup_page()` render function**

Insert immediately after `gsh_tp_admin_menu()` closes:

```php
function gsh_tp_backup_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $imported   = ! empty( $_GET['imported'] );
    $import_err = (int) ( $_GET['import_error'] ?? 0 );
    $err_msgs   = array(
        1 => 'Keine Datei ausgewählt.',
        2 => 'Datei zu groß (max. 512 KB).',
        3 => 'Ungültiges Dateiformat. Bitte eine Curriculr-Export-Datei wählen.',
    );
    ?>
    <div class="wrap gsh-backup-wrap">
        <h1>Schul-Terminplan – Datensicherung</h1>

        <?php if ( $imported ) : ?>
        <div class="notice notice-success is-dismissible"><p>Einstellungen erfolgreich importiert.</p></div>
        <?php endif; ?>
        <?php if ( $import_err && isset( $err_msgs[ $import_err ] ) ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $err_msgs[ $import_err ] ); ?></p></div>
        <?php endif; ?>

        <div class="gsh-backup-section">
            <h2>Einstellungen exportieren</h2>
            <p>Lädt alle Plugin-Einstellungen als JSON-Datei herunter. Curriculr-Planungsdokumente sind nicht enthalten — diese verbleiben in der Datenbank.</p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'gsh_tp_curriculr_export_nonce' ); ?>
                <input type="hidden" name="action" value="gsh_tp_curriculr_export">
                <button type="submit" class="button button-primary">Einstellungen exportieren</button>
            </form>
        </div>

        <div class="gsh-backup-section">
            <h2>Einstellungen importieren</h2>
            <p>Stellt alle Plugin-Einstellungen aus einer Export-Datei wieder her. Vorhandene Einstellungen werden überschrieben.</p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field( 'gsh_tp_import_settings' ); ?>
                <input type="hidden" name="action" value="gsh_tp_import_settings">
                <input type="file" name="settings_file" accept=".json" required class="gsh-backup-file-input">
                <?php submit_button( 'Einstellungen importieren', 'secondary', 'submit', false ); ?>
            </form>
        </div>
    </div>
    <?php
}
```

- [ ] **Step 3: Syntax check**

```bash
php -l plugin/gsh-terminplan.php
```

Expected: `No syntax errors detected`

- [ ] **Step 4: Manual smoke test**

Activate the plugin on a WP install. Go to **Settings → Datensicherung**. Verify:
- Page renders without PHP errors.
- "Einstellungen exportieren" downloads a `.json` file.
- "Einstellungen importieren" accepts the downloaded file and redirects with success notice.

- [ ] **Step 5: Commit**

```bash
git add plugin/gsh-terminplan.php
git commit -m "feat: add Datensicherung admin page with export and import forms"
```

---

## Task 5: Delete warning JS and `plugin_action_links`

**Files:**
- Create: `assets/js/curriculr-delete-warn.js`
- Modify: `plugin/gsh-terminplan.php` (add enqueue hook, add `plugin_action_links` filter)

**Interfaces:**
- Consumes: backup page URL (passed via `wp_localize_script`)
- Produces: modal overlay on `plugins.php` when user clicks "Löschen" for this plugin

- [ ] **Step 1: Create `assets/js/curriculr-delete-warn.js`**

```js
/* global gshDeleteWarn */
(function () {
    var config = window.gshDeleteWarn || {};
    var backupUrl = config.backupUrl || '';
    var pluginSlug = 'gsh-terminplan';

    function findDeleteLink() {
        var row = document.querySelector('tr[data-slug="' + pluginSlug + '"]');
        if (!row) { return null; }
        var links = row.querySelectorAll('.delete a, .deactivate + span a, td.plugin-title + td a');
        for (var i = 0; i < row.querySelectorAll('a').length; i++) {
            var a = row.querySelectorAll('a')[i];
            if (a.href && a.href.indexOf('action=delete-plugin') !== -1) {
                return a;
            }
        }
        return null;
    }

    function buildModal(deleteHref) {
        var overlay = document.createElement('div');
        overlay.id = 'gsh-delete-warn-overlay';
        overlay.innerHTML =
            '<div id="gsh-delete-warn-box">' +
            '<h2>Plugin löschen</h2>' +
            '<p>Vor dem Löschen Einstellungen exportieren.<br>' +
            'Curriculr-Planungsdokumente bleiben erhalten.</p>' +
            '<div class="gsh-delete-warn-actions">' +
            (backupUrl ? '<a href="' + backupUrl + '" target="_blank" class="button button-primary">Einstellungen exportieren</a> ' : '') +
            '<a href="' + deleteHref + '" class="button gsh-delete-warn-confirm">Trotzdem löschen</a> ' +
            '<button type="button" class="button gsh-delete-warn-cancel">Abbrechen</button>' +
            '</div>' +
            '</div>';
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:100000;display:flex;align-items:center;justify-content:center';
        var box = overlay.querySelector('#gsh-delete-warn-box');
        box.style.cssText = 'background:#fff;padding:24px 28px;border-radius:4px;max-width:420px;width:90%;box-shadow:0 4px 24px rgba(0,0,0,.25)';
        overlay.querySelector('.gsh-delete-warn-cancel').addEventListener('click', function () {
            document.body.removeChild(overlay);
        });
        return overlay;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var deleteLink = findDeleteLink();
        if (!deleteLink) { return; }
        deleteLink.addEventListener('click', function (e) {
            e.preventDefault();
            document.body.appendChild(buildModal(deleteLink.href));
        });
    });
}());
```

- [ ] **Step 2: Register the script enqueue, backup-page CSS, and `plugin_action_links` in `gsh-terminplan.php`**

Add after the existing `add_action( 'wp_enqueue_scripts', 'gsh_tp_enqueue_frontend_styles' );` line (around line 2129).

Note: `gsh-terminplan.css` is only enqueued on the frontend (`wp_enqueue_scripts`). The backup page is an admin page and needs the CSS enqueued via `admin_enqueue_scripts`. Both the delete-warn script and the backup-page CSS are handled in one hook:

```php
add_action( 'admin_enqueue_scripts', 'gsh_tp_enqueue_admin_delete_warn' );
function gsh_tp_enqueue_admin_delete_warn( $hook ) {
    // Enqueue backup-page CSS on both plugins.php and the backup page itself.
    if ( in_array( $hook, array( 'plugins.php', 'settings_page_gsh-terminplan-backup' ), true ) ) {
        wp_enqueue_style(
            'gsh-terminplan',
            plugin_dir_url( __FILE__ ) . 'assets/css/gsh-terminplan.css',
            array(),
            GSH_TP_VERSION
        );
    }
    if ( 'plugins.php' !== $hook ) {
        return;
    }
    wp_enqueue_script(
        'gsh-curriculr-delete-warn',
        plugin_dir_url( __FILE__ ) . 'assets/js/curriculr-delete-warn.js',
        array(),
        GSH_TP_VERSION,
        true
    );
    wp_localize_script(
        'gsh-curriculr-delete-warn',
        'gshDeleteWarn',
        array( 'backupUrl' => admin_url( 'options-general.php?page=gsh-terminplan-backup' ) )
    );
}

add_filter( 'plugin_action_links_gsh-terminplan/gsh-terminplan.php', 'gsh_tp_plugin_action_links' );
function gsh_tp_plugin_action_links( $links ) {
    $backup_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=gsh-terminplan-backup' ) ) . '" style="color:#b91c1c;font-weight:600">Einstellungen sichern ↗</a>';
    array_unshift( $links, $backup_link );
    return $links;
}
```

> **Note:** The `plugin_action_links` filter name must match the plugin's actual file path relative to the plugins directory. The filename in the filter is `gsh-terminplan/gsh-terminplan.php`. Verify this matches by checking `plugin_basename(__FILE__)` in the plugin — or use the dynamic form:

```php
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'gsh_tp_plugin_action_links' );
```

Use the dynamic `plugin_basename(__FILE__)` form to avoid hardcoding the path.

- [ ] **Step 3: Syntax check**

```bash
php -l plugin/gsh-terminplan.php
```

Expected: `No syntax errors detected`

- [ ] **Step 4: Manual smoke test**

Go to **Plugins** page. Verify:
- A red "Einstellungen sichern ↗" link appears in the row for this plugin.
- Clicking the original "Löschen" link shows the modal with "Einstellungen exportieren" and "Trotzdem löschen" buttons.
- "Abbrechen" closes the modal without navigating.
- "Einstellungen exportieren" opens the backup page in a new tab.
- "Trotzdem löschen" follows through to the WP delete confirmation.

- [ ] **Step 5: Commit**

```bash
git add assets/js/curriculr-delete-warn.js plugin/gsh-terminplan.php
git commit -m "feat: add delete-warning modal and plugin action links for backup"
```

---

## Task 6: CSS and version bump

**Files:**
- Modify: `assets/css/gsh-terminplan.css` (add backup page styles)
- Modify: `plugin/gsh-terminplan.php` (version bump in 4 places + changelog)

**Interfaces:**
- Produces: polished backup page layout; version `4.21.0`

- [ ] **Step 1: Add backup page styles to `assets/css/gsh-terminplan.css`**

Append at the end of the file:

```css
/* ---------- Datensicherung-Seite (WP Admin) ---------- */
.gsh-backup-wrap h1 { margin-bottom: 24px; }
.gsh-backup-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px 24px;
    max-width: 640px;
    margin-bottom: 20px;
}
.gsh-backup-section h2 { margin-top: 0; font-size: 1rem; }
.gsh-backup-section p  { margin-top: 0; color: #50575e; }
.gsh-backup-file-input { display: block; margin-bottom: 12px; }
```

- [ ] **Step 2: Bump version in `gsh-terminplan.php` (4 places)**

**Place 1** — plugin header comment (near top of file, look for `* Version:`):
```
 * Version: 4.21.0
```

**Place 2** — constant (line ~573):
```php
define( 'GSH_TP_VERSION', '4.21.0' );
```

**Place 3** — `gsh_tp_changelog()` — prepend a new entry at the top of the array:
```php
array(
    'version' => '4.21.0',
    'date'    => '2026-06-24',
    'changes' => array(
        array( 'tag' => 'NEU',  'text' => 'Datensicherung-Seite: Einstellungen als JSON exportieren und importieren' ),
        array( 'tag' => 'NEU',  'text' => 'Warnhinweis beim Plugin-Löschen mit Link zur Datensicherung' ),
        array( 'tag' => 'FIX',  'text' => 'Uninstall-Hook löscht jetzt alle curriculr_origin / curriculr_profile_map / curriculr_db_version Optionen' ),
        array( 'tag' => 'FIX',  'text' => 'Uninstall-Hook bereinigt jetzt auch den gsh_tp_curriculr_daily_backup Cron-Job' ),
    ),
),
```

**Place 4** — changelog block in the file header comment (the `@since` / `@version` block at the very top):  
Add a line `* - [NEU] v4.21.0: Datensicherung (Export/Import) + Uninstall-Fix`.

- [ ] **Step 3: Syntax check all changed PHP files**

```bash
php -l plugin/gsh-terminplan.php && php -l plugin/curriculr-data-layer.php
```

Expected: both `No syntax errors detected`

- [ ] **Step 4: Run all tests**

```bash
php tests/curriculr/test-ics.php && \
php tests/curriculr/test-stage.php && \
php tests/curriculr/test-version.php && \
php tests/curriculr/test-envelope.php && \
php tests/curriculr/test-revisions.php && \
php tests/curriculr/test-integration-stubbed.php && \
php tests/curriculr/test-settings-backup.php
```

Expected: each prints `ALL PASS`

- [ ] **Step 5: Commit**

```bash
git add assets/css/gsh-terminplan.css plugin/gsh-terminplan.php
git commit -m "feat: v4.21.0 — settings backup, delete warning, uninstall fix"
```
