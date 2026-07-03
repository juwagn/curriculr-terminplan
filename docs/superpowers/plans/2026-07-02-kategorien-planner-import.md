# Kategorien aus Planner übernehmen — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a WP-Admin add a manual, reviewable "Aus Planner übernehmen" action to the Kategorien-Tab that pulls `doc.categories` from a chosen Schuljahr's Planner document and merges it into the existing category editor table, without touching the persisted `gsh_tp_categories` option until the admin explicitly clicks Save.

**Architecture:** Single-file change in `plugin/gsh-terminplan.php`: a new read-only AJAX handler reads a Planner doc's categories out of `wp_curriculr_docs` (already stored locally, no cross-repo call), and existing client-side JS in the Kategorien-Tab merges the result into the DOM table (update-on-collision, append-if-new, never delete). Persistence stays on the existing `gsh_tp_save_categories()` path — this feature never writes `gsh_tp_categories` itself.

**Tech Stack:** PHP 7.4+ (WordPress plugin, no Composer/build step), vanilla JS (ES2020, `fetch`), dependency-free PHP test harness (`tests/curriculr/assert.php`).

## Global Constraints

- Spec: [docs/superpowers/specs/2026-07-02-kategorien-planner-import-design.md](../specs/2026-07-02-kategorien-planner-import-design.md)
- Nonce action name for the new AJAX call: reuse the existing `gsh_tp_save_categories_nonce` (same tab, same capability gate) — do not create a new nonce.
- New AJAX action name: `gsh_tp_import_categories_from_planner`.
- No new `curriculr/v1` REST route — this is an admin-only `wp_ajax_*` action.
- Merge policy (client-side): imported category ID already in table → overwrite `label` + `color` only, `keywords` field untouched. ID not in table → append new row with empty `keywords`. IDs only present in the WP table (not in the imported set) → never touched, never deleted.
- Nothing is persisted to `gsh_tp_categories` by the import itself — only the existing "Kategorien speichern" button (`gsh_tp_ajax_save_categories`) persists.
- Version target: **4.27.0** (NEU minor bump). `4.26.1` is already unstaged/in-flight on this branch (unrelated draft-toggle fix) — do not touch or conflict with that diff; build on top of it.
- `php -l plugin/gsh-terminplan.php` mandatory after every PHP edit in this plan (per repo `CLAUDE.md`).
- WordPress Coding Standards for PHP style (per repo `CLAUDE.md`).
- This admin tab already uses inline `style="..."` attributes throughout `gsh_tp_render_kategorien_tab()` (predates the "CSS only in assets/css" rule, which applies to shortcode/public-facing markup) — match the existing inline-style convention for any new markup in this function, don't introduce a new pattern.

---

### Task 1: Backend — read-only AJAX handler

**Files:**
- Modify: `plugin/gsh-terminplan.php:2554-2555` (action registration)
- Modify: `plugin/gsh-terminplan.php:2928` (new function, inserted directly after `gsh_tp_ajax_save_categories()`)
- Test: `tests/curriculr/test-categories-import.php` (new)

**Interfaces:**
- Consumes: `gsh_tp_curriculr_repo_get( string $sj ): array|null` (defined in `curriculr-data-layer.php`, returns ARRAY_A row with a `json` string column, or `null` if the schoolyear has no doc).
- Produces: `gsh_tp_ajax_import_categories_from_planner(): void` — WP AJAX handler, registered under action `gsh_tp_import_categories_from_planner`. On success sends `{ categories: array<{id,label,color,slug,keywords}> }` via `wp_send_json_success`. Task 2's JS calls this action by name and reads `result.data.categories`.

- [ ] **Step 1: Write the failing test**

Create `tests/curriculr/test-categories-import.php`:

```php
<?php
/**
 * Tests für den AJAX-Handler gsh_tp_ajax_import_categories_from_planner().
 * Dependency-free, läuft mit plain `php`.
 */
define( 'GSH_TP_CURRICULR_TEST', true );
define( 'ARRAY_A', 'ARRAY_A' );
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

require __DIR__ . '/assert.php';

/* ---------- WP-Stubs (Standard-Set zum Laden von gsh-terminplan.php) ---------- */

if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $key ) {
        $key = strtolower( $key );
        $key = preg_replace( '/\s+/', '-', $key );
        return preg_replace( '/[^a-z0-9_\-]/', '', $key );
    }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $str ) { return trim( strip_tags( $str ) ); }
}
if ( ! function_exists( 'absint' ) ) {
    function absint( $v ) { return abs( (int) $v ); }
}
if ( ! function_exists( 'sanitize_textarea_field' ) ) {
    function sanitize_textarea_field( $str ) { return trim( $str ); }
}
if ( ! function_exists( 'esc_url_raw' ) ) {
    function esc_url_raw( $url ) { return filter_var( trim( $url ), FILTER_SANITIZE_URL ) ?: ''; }
}
if ( ! function_exists( 'current_time' ) ) {
    function current_time( $fmt ) { return date( $fmt ); }
}
if ( ! function_exists( 'plugin_dir_path' ) ) {
    function plugin_dir_path( $file ) { return rtrim( dirname( $file ), '/' ) . '/'; }
}
if ( ! function_exists( 'plugin_basename' ) ) {
    function plugin_basename( $file ) { return basename( dirname( $file ) ) . '/' . basename( $file ); }
}
if ( ! function_exists( 'add_action' ) ) {
    function add_action() {}
}
if ( ! function_exists( 'add_filter' ) ) {
    function add_filter() {}
}
if ( ! function_exists( 'add_shortcode' ) ) {
    function add_shortcode() {}
}
if ( ! function_exists( 'register_deactivation_hook' ) ) {
    function register_deactivation_hook() {}
}
if ( ! function_exists( 'register_activation_hook' ) ) {
    function register_activation_hook() {}
}
if ( ! function_exists( 'register_uninstall_hook' ) ) {
    function register_uninstall_hook() {}
}
if ( ! function_exists( 'wp_next_scheduled' ) ) {
    function wp_next_scheduled() { return false; }
}
if ( ! function_exists( 'wp_schedule_event' ) ) {
    function wp_schedule_event() {}
}
if ( ! function_exists( 'wp_unschedule_event' ) ) {
    function wp_unschedule_event() {}
}
if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
    function wp_clear_scheduled_hook() {}
}
if ( ! function_exists( 'wp_http_validate_url' ) ) {
    function wp_http_validate_url( $url ) { return filter_var( $url, FILTER_VALIDATE_URL ) !== false; }
}
if ( ! function_exists( 'get_transient' ) ) {
    function get_transient( $key ) { return false; }
}
if ( ! function_exists( 'set_transient' ) ) {
    function set_transient( $key, $value, $expiration = 0 ) { return true; }
}
if ( ! function_exists( 'delete_transient' ) ) {
    function delete_transient( $key ) { return true; }
}

$GLOBALS['_wp_options'] = array();
if ( ! function_exists( 'get_option' ) ) {
    function get_option( $key, $default = false ) { return $GLOBALS['_wp_options'][ $key ] ?? $default; }
}
if ( ! function_exists( 'update_option' ) ) {
    function update_option( $key, $value ) { $GLOBALS['_wp_options'][ $key ] = $value; return true; }
}
if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $v ) { return is_string( $v ) ? stripslashes( $v ) : $v; }
}

/* ---------- AJAX-Stubs: Exceptions statt echtem exit() ---------- */

class Gsh_Test_Ajax_Exit extends Exception {
    public $kind;    // 'referer_fail' | 'success' | 'error'
    public $payload;
    public $status;
}

$GLOBALS['_test_valid_nonce']        = 'valid-nonce-123';
$GLOBALS['_test_can_manage_options'] = true;

if ( ! function_exists( 'check_ajax_referer' ) ) {
    function check_ajax_referer( $action, $query_arg = false, $die = true ) {
        $sent  = $_REQUEST[ $query_arg ] ?? '';
        $valid = ( $sent === $GLOBALS['_test_valid_nonce'] );
        if ( ! $valid && $die ) {
            $e = new Gsh_Test_Ajax_Exit( 'referer_fail' );
            $e->kind = 'referer_fail'; $e->payload = null; $e->status = -1;
            throw $e;
        }
        return $valid ? 1 : false;
    }
}
if ( ! function_exists( 'current_user_can' ) ) {
    function current_user_can( $cap ) { return (bool) $GLOBALS['_test_can_manage_options']; }
}
if ( ! function_exists( 'wp_send_json_success' ) ) {
    function wp_send_json_success( $data = null, $status_code = null ) {
        $e = new Gsh_Test_Ajax_Exit( 'success' );
        $e->kind = 'success'; $e->payload = $data; $e->status = $status_code ?: 200;
        throw $e;
    }
}
if ( ! function_exists( 'wp_send_json_error' ) ) {
    function wp_send_json_error( $data = null, $status_code = null ) {
        $e = new Gsh_Test_Ajax_Exit( 'error' );
        $e->kind = 'error'; $e->payload = $data; $e->status = $status_code ?: 400;
        throw $e;
    }
}

/* ---------- Fake $wpdb für gsh_tp_curriculr_repo_get() ---------- */

class Gsh_Fake_Wpdb_Repo {
    public $prefix = 'wp_';
    public $docs   = array(); // keyed by schoolyear, ARRAY_A row

    public function prepare( $query, ...$args ) {
        return array( 'sj' => $args[0] ?? '' );
    }
    public function get_row( $prepared, $out = null ) {
        $sj = is_array( $prepared ) ? ( $prepared['sj'] ?? '' ) : '';
        return $this->docs[ $sj ] ?? null;
    }
}

global $wpdb;
$wpdb = new Gsh_Fake_Wpdb_Repo();
$wpdb->docs['sj_2026_27'] = array(
    'schoolyear' => 'sj_2026_27',
    'json'       => json_encode( array(
        'categories' => array(
            array( 'id' => 'konferenz', 'label' => 'Konferenzen/DB', 'color' => '#0058A0', 'slug' => 'konferenz', 'keywords' => array() ),
            array( 'id' => 'pruefung',  'label' => 'Prüfung',        'color' => '#D9A23B', 'slug' => 'pruefung',  'keywords' => array() ),
        ),
    ) ),
    'version'    => 5,
    'stage'      => 'oeffentlich',
    'updated_at' => '2026-07-01 10:00:00',
);
$wpdb->docs['sj_leer'] = array(
    'schoolyear' => 'sj_leer',
    'json'       => json_encode( array( 'meta' => array( 'name' => 'Leeres Schuljahr' ) ) ), // kein categories-Key
    'version'    => 1,
    'stage'      => 'entwurf',
    'updated_at' => '2026-01-01 00:00:00',
);

require __DIR__ . '/../../plugin/gsh-terminplan.php';

function gsh_run_import_handler() {
    try {
        gsh_tp_ajax_import_categories_from_planner();
        return null; // sollte nie erreicht werden — Handler beendet immer via Exception
    } catch ( Gsh_Test_Ajax_Exit $e ) {
        return $e;
    }
}

/* ---------- Szenario 1: ungültige Nonce ---------- */

$_POST = $_REQUEST = array( 'nonce' => 'falsch', 'sj' => 'sj_2026_27' );
$exit = gsh_run_import_handler();
gsh_assert_true( $exit instanceof Gsh_Test_Ajax_Exit, 'Handler beendet bei ungültiger Nonce' );
gsh_assert_eq( $exit->kind, 'referer_fail', 'ungültige Nonce → referer_fail' );

/* ---------- Szenario 2: gültige Nonce, keine Berechtigung ---------- */

$GLOBALS['_test_can_manage_options'] = false;
$_POST = $_REQUEST = array( 'nonce' => 'valid-nonce-123', 'sj' => 'sj_2026_27' );
$exit = gsh_run_import_handler();
gsh_assert_eq( $exit->kind, 'error', 'fehlende Berechtigung → error' );
gsh_assert_eq( $exit->status, 403, 'fehlende Berechtigung → 403' );
gsh_assert_eq( $exit->payload['message'], 'Keine Berechtigung.', 'Fehlermeldung fehlende Berechtigung' );

/* ---------- Szenario 3: Schuljahr nicht gefunden ---------- */

$GLOBALS['_test_can_manage_options'] = true;
$_POST = $_REQUEST = array( 'nonce' => 'valid-nonce-123', 'sj' => 'sj_unbekannt' );
$exit = gsh_run_import_handler();
gsh_assert_eq( $exit->kind, 'error', 'unbekanntes Schuljahr → error' );
gsh_assert_eq( $exit->status, 404, 'unbekanntes Schuljahr → 404' );
gsh_assert_eq( $exit->payload['message'], 'Schuljahr nicht gefunden.', 'Fehlermeldung unbekanntes Schuljahr' );

/* ---------- Szenario 4: Happy Path — Kategorien vorhanden ---------- */

$_POST = $_REQUEST = array( 'nonce' => 'valid-nonce-123', 'sj' => 'sj_2026_27' );
$exit = gsh_run_import_handler();
gsh_assert_eq( $exit->kind, 'success', 'gültiges Schuljahr → success' );
gsh_assert_eq( count( $exit->payload['categories'] ), 2, 'zwei Kategorien geliefert' );
gsh_assert_eq( $exit->payload['categories'][0]['id'],    'konferenz',       'erste Kategorie-ID' );
gsh_assert_eq( $exit->payload['categories'][0]['label'], 'Konferenzen/DB', 'erste Kategorie-Label' );
gsh_assert_eq( $exit->payload['categories'][1]['id'],    'pruefung',        'zweite Kategorie-ID' );

/* ---------- Szenario 5: Dokument ohne categories-Key → leere Liste ---------- */

$_POST = $_REQUEST = array( 'nonce' => 'valid-nonce-123', 'sj' => 'sj_leer' );
$exit = gsh_run_import_handler();
gsh_assert_eq( $exit->kind, 'success', 'Dokument ohne categories → trotzdem success' );
gsh_assert_eq( $exit->payload['categories'], array(), 'leere Kategorien-Liste als Fallback' );

gsh_test_done();
echo "test-categories-import: OK\n";
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/curriculr/test-categories-import.php`
Expected: `PHP Fatal error:  Uncaught Error: Call to undefined function gsh_tp_ajax_import_categories_from_planner()` (function doesn't exist yet).

- [ ] **Step 3: Add the AJAX action registration**

Modify `plugin/gsh-terminplan.php` at the existing Kategorien-AJAX block (currently lines 2554-2555):

Old:
```php
// Kategorien-AJAX (nur eingeloggte Admins)
add_action( 'wp_ajax_gsh_tp_save_categories', 'gsh_tp_ajax_save_categories' );
```

New:
```php
// Kategorien-AJAX (nur eingeloggte Admins)
add_action( 'wp_ajax_gsh_tp_save_categories', 'gsh_tp_ajax_save_categories' );
add_action( 'wp_ajax_gsh_tp_import_categories_from_planner', 'gsh_tp_ajax_import_categories_from_planner' );
```

- [ ] **Step 4: Implement the handler**

Insert directly after `gsh_tp_ajax_save_categories()`'s closing `}` (currently line 2928 in `plugin/gsh-terminplan.php`):

```php

/**
 * AJAX-Handler: liefert die Kategorien eines Planner-Schuljahrs zum Übernehmen.
 *
 * Rein lesend — schreibt niemals gsh_tp_categories. Persistiert wird
 * ausschließlich über den bestehenden gsh_tp_ajax_save_categories()-Pfad,
 * nachdem der Admin das clientseitige Merge-Ergebnis geprüft hat.
 *
 * @since 4.27.0
 * @return void  Sendet JSON-Response und beendet die Ausführung.
 */
function gsh_tp_ajax_import_categories_from_planner(): void {
    check_ajax_referer( 'gsh_tp_save_categories_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Keine Berechtigung.' ), 403 );
        return;
    }

    $sj  = sanitize_key( wp_unslash( $_POST['sj'] ?? '' ) );
    $row = $sj ? gsh_tp_curriculr_repo_get( $sj ) : null;
    if ( ! $row ) {
        wp_send_json_error( array( 'message' => 'Schuljahr nicht gefunden.' ), 404 );
        return;
    }

    $doc  = json_decode( $row['json'], true );
    $cats = is_array( $doc['categories'] ?? null ) ? $doc['categories'] : array();

    wp_send_json_success( array( 'categories' => $cats ) );
}
```

- [ ] **Step 5: Syntax check**

Run: `php -l plugin/gsh-terminplan.php`
Expected: `No syntax errors detected in plugin/gsh-terminplan.php`

- [ ] **Step 6: Run test to verify it passes**

Run: `php tests/curriculr/test-categories-import.php`
Expected: last line `test-categories-import: OK`, exit code 0.

- [ ] **Step 7: Commit**

```bash
cd curriculr-terminplan
git add plugin/gsh-terminplan.php tests/curriculr/test-categories-import.php
git commit -m "feat: add read-only AJAX handler to fetch Planner categories for import"
```

---

### Task 2: Kategorien-Tab UI — Schuljahr-Dropdown, Import-Button, client-side Merge

**Files:**
- Modify: `plugin/gsh-terminplan.php:4195-4197` (new UI block in `gsh_tp_render_kategorien_tab()`)
- Modify: `plugin/gsh-terminplan.php:4266-4270` (new JS `const`/`var` refs)
- Modify: `plugin/gsh-terminplan.php:4454-4455` (new JS merge handler)

**Interfaces:**
- Consumes: AJAX action `gsh_tp_import_categories_from_planner` from Task 1 (POST params `action`, `nonce`, `sj`; response `{ success: bool, data: { categories?: array, message?: string } }`). Also consumes existing in-scope JS helpers already defined in this same `<script>` block: `tbody`, `nonce`, `updatePreview(row)`, `buildRowHtml(cat)` (all defined earlier in `gsh_tp_render_kategorien_tab()`, unchanged by this task).
- Produces: nothing new for later tasks — this is the UI-facing end of the feature. Manual verification only (no automated JS test harness in this plugin, per `CLAUDE.md`).

- [ ] **Step 1: Add the dropdown + button markup**

In `plugin/gsh-terminplan.php`, inside `gsh_tp_render_kategorien_tab()`, insert between the nonce hidden input and the `<div id="gsh-cat-editor">` open tag (currently lines 4195-4197):

Old:
```php
    <input type="hidden" id="gsh-cat-nonce" value="<?php echo esc_attr( $nonce_value ); ?>">

    <div id="gsh-cat-editor">
```

New:
```php
    <input type="hidden" id="gsh-cat-nonce" value="<?php echo esc_attr( $nonce_value ); ?>">

    <?php
    global $wpdb;
    $docs_table   = gsh_tp_curriculr_table();
    $planner_docs = $wpdb->get_results( "SELECT schoolyear, json FROM $docs_table ORDER BY updated_at DESC", ARRAY_A );
    $planner_docs = is_array( $planner_docs ) ? $planner_docs : array();

    $active_sj = '';
    foreach ( gsh_tp_get_schoolyears() as $sy ) {
        if ( ! empty( $sy['is_active'] ) ) { $active_sj = $sy['key']; break; }
    }
    ?>
    <?php if ( ! empty( $planner_docs ) ) : ?>
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;padding:10px 12px;background:#f6f7f7;border:1px solid #dcdcde;border-radius:4px;flex-wrap:wrap">
        <label for="gsh-cat-import-sj" style="font-weight:600">Aus Planner übernehmen:</label>
        <select id="gsh-cat-import-sj">
            <?php foreach ( $planner_docs as $d ) :
                $doc_arr = json_decode( $d['json'], true );
                $name    = ( is_array( $doc_arr ) && ! empty( $doc_arr['meta']['name'] ) ) ? $doc_arr['meta']['name'] : $d['schoolyear'];
            ?>
                <option value="<?php echo esc_attr( $d['schoolyear'] ); ?>" <?php selected( $active_sj, $d['schoolyear'] ); ?>>
                    <?php echo esc_html( $name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="button" id="gsh-cat-import-btn">Aus Planner übernehmen</button>
        <span id="gsh-cat-import-status" style="font-size:12px;color:#646970"></span>
    </div>
    <?php else : ?>
    <p class="description" style="margin-bottom:14px">Keine Planner-Schuljahre synchronisiert – „Aus Planner übernehmen" ist noch nicht verfügbar.</p>
    <?php endif; ?>

    <div id="gsh-cat-editor">
```

- [ ] **Step 2: Add JS element refs**

In the same function's `<script>` block, extend the existing ref declarations (currently lines 4266-4270):

Old:
```js
        var tbody    = document.getElementById('gsh-cat-tbody');
        var addBtn   = document.getElementById('gsh-cat-add');
        var saveBtn  = document.getElementById('gsh-cat-save');
        var statusEl = document.getElementById('gsh-cat-status');
        var nonce    = (document.getElementById('gsh-cat-nonce') || {}).value || '';
```

New:
```js
        var tbody         = document.getElementById('gsh-cat-tbody');
        var addBtn        = document.getElementById('gsh-cat-add');
        var saveBtn       = document.getElementById('gsh-cat-save');
        var statusEl      = document.getElementById('gsh-cat-status');
        var nonce         = (document.getElementById('gsh-cat-nonce') || {}).value || '';
        var importBtn     = document.getElementById('gsh-cat-import-btn');
        var importSelect  = document.getElementById('gsh-cat-import-sj');
        var importStatus  = document.getElementById('gsh-cat-import-status');
```

- [ ] **Step 3: Add the import + merge handler**

Insert after the "Speichern via AJAX" block's closing (currently lines 4454-4455, right before the IIFE's closing `})();`):

Old:
```js
                } finally {
                    saveBtn.disabled    = false;
                    saveBtn.textContent = origTxt;
                }
            });
        }
    })();
    </script>
```

New:
```js
                } finally {
                    saveBtn.disabled    = false;
                    saveBtn.textContent = origTxt;
                }
            });
        }

        // Aus Planner übernehmen: fetch + clientseitiger Merge in die Tabelle
        if (importBtn) {
            importBtn.addEventListener('click', async function () {
                var sj = importSelect ? importSelect.value : '';
                if (!sj) return;
                importBtn.disabled    = true;
                var origImportTxt     = importBtn.textContent;
                importBtn.textContent = 'Wird geladen…';
                importStatus.textContent = '';

                try {
                    var response = await fetch(
                        (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php'),
                        {
                            method:  'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body:    new URLSearchParams({
                                action: 'gsh_tp_import_categories_from_planner',
                                nonce:  nonce,
                                sj:     sj,
                            }).toString()
                        }
                    );
                    var result = await response.json();

                    if (result.success && result.data && Array.isArray(result.data.categories)) {
                        var updated = 0, added = 0;
                        result.data.categories.forEach(function (cat) {
                            var rows  = tbody.querySelectorAll('.gsh-cat-row');
                            var match = null;
                            rows.forEach(function (row) {
                                var idVal = (row.querySelector('.gsh-cat-id') || {}).value || '';
                                if (idVal === cat.id) { match = row; }
                            });
                            if (match) {
                                var labelInput = match.querySelector('.gsh-cat-label');
                                var colorInput = match.querySelector('.gsh-cat-color');
                                if (labelInput) labelInput.value = cat.label || '';
                                if (colorInput) colorInput.value = cat.color || '#94a3b8';
                                updatePreview(match);
                                updated++;
                            } else {
                                var newRow = document.createElement('tr');
                                newRow.className = 'gsh-cat-row';
                                newRow.innerHTML = buildRowHtml({ id: cat.id, slug: cat.slug, label: cat.label, color: cat.color, keywords: [] });
                                tbody.appendChild(newRow);
                                added++;
                            }
                        });
                        importStatus.textContent = added + ' übernommen, ' + updated + ' aktualisiert – bitte prüfen und speichern.';
                    } else {
                        var msg = result.data && result.data.message ? result.data.message : 'Fehler beim Laden.';
                        importStatus.textContent = '✗ ' + msg;
                    }
                } catch (err) {
                    importStatus.textContent = '✗ Netzwerkfehler: ' + err.message;
                } finally {
                    importBtn.disabled    = false;
                    importBtn.textContent = origImportTxt;
                }
            });
        }
    })();
    </script>
```

- [ ] **Step 4: Syntax check**

Run: `php -l plugin/gsh-terminplan.php`
Expected: `No syntax errors detected in plugin/gsh-terminplan.php`

- [ ] **Step 5: Run the full existing test suite (regression check)**

Run:
```bash
cd curriculr-terminplan
for f in tests/curriculr/test-*.php; do php "$f" || echo "FAILED: $f"; done
```
Expected: every file prints its own `OK` line, no `FAILED:` lines.

- [ ] **Step 6: Manual smoke test**

Use the `/verify` skill (or manual WP-Admin walkthrough) against a real or staging site with at least one Planner-synced schoolyear in `wp_curriculr_docs`:
1. WP-Admin → Terminplan → Einstellungen → Kategorien-Tab.
2. Confirm the new "Aus Planner übernehmen" block appears above the category table, dropdown pre-selects the active schoolyear.
3. Click "Aus Planner übernehmen" — confirm rows update/append per the merge policy, status line shows the "X übernommen, Y aktualisiert" message, and nothing is persisted until "Kategorien speichern" is clicked.
4. Verify a pre-existing category's `keywords` field is untouched after import overwrites its label/color.

- [ ] **Step 7: Commit**

```bash
cd curriculr-terminplan
git add plugin/gsh-terminplan.php
git commit -m "feat: add Planner-category import UI to Kategorien-Tab"
```

---

### Task 3: Release — version bump + changelog

**Files:**
- Modify: `plugin/gsh-terminplan.php:6-17` (header comment)
- Modify: `plugin/gsh-terminplan.php:598` (`GSH_TP_VERSION` define)
- Modify: `plugin/gsh-terminplan.php:840-847` (`gsh_tp_changelog()` array)

**Interfaces:**
- Consumes: nothing (pure metadata/versioning task, runs after Tasks 1-2 are committed).
- Produces: nothing consumed elsewhere — terminal task of this plan.

- [ ] **Step 1: Bump the header comment**

Old (`plugin/gsh-terminplan.php:6-12`):
```php
 * Version:     4.26.1
 * Author:      Open Source Community
 * License:     GPL v2 or later
 * Text Domain: gsh-terminplan
 * v4.26.1
 * - [FIX] Schuljahre-Tab (v2): Kalender-Status (Entwurf/Beschlossen) war seit 4.24.0 nur Text-Anzeige — Umschalten fehlte, Entwurf-Kiosk dadurch für neue/synchronisierte Schuljahre unerreichbar
 * Changelog 4.26.0:
```

New:
```php
 * Version:     4.27.0
 * Author:      Open Source Community
 * License:     GPL v2 or later
 * Text Domain: gsh-terminplan
 * v4.27.0
 * - [NEU] Kategorien-Tab: Kategorien aus einem Planner-Schuljahr übernehmen (Label/Farbe je Kategorie werden übernommen, WP-seitige Stichwörter für das IServ-Keyword-Matching bleiben unverändert)
 * Changelog 4.26.1:
 * - [FIX] Schuljahre-Tab (v2): Kalender-Status (Entwurf/Beschlossen) war seit 4.24.0 nur Text-Anzeige — Umschalten fehlte, Entwurf-Kiosk dadurch für neue/synchronisierte Schuljahre unerreichbar
 * Changelog 4.26.0:
```

- [ ] **Step 2: Bump `GSH_TP_VERSION`**

Old (`plugin/gsh-terminplan.php:598`):
```php
define( 'GSH_TP_VERSION',       '4.26.1' );
```

New:
```php
define( 'GSH_TP_VERSION',       '4.27.0' );
```

- [ ] **Step 3: Prepend the changelog array entry**

Old (`plugin/gsh-terminplan.php:840-847`, start of `gsh_tp_changelog()`):
```php
function gsh_tp_changelog() {
    return array(
        array(
            'version' => '4.26.1',
            'entries' => array(
                array( 'tag' => 'FIX', 'text' => 'Schuljahre-Tab (v2): Kalender-Status (Entwurf/Beschlossen) war seit 4.24.0 nur Text-Anzeige — Umschalten fehlte, Entwurf-Kiosk dadurch für neue/synchronisierte Schuljahre unerreichbar' ),
            ),
        ),
```

New:
```php
function gsh_tp_changelog() {
    return array(
        array(
            'version' => '4.27.0',
            'entries' => array(
                array( 'tag' => 'NEU', 'text' => 'Kategorien-Tab: Kategorien aus einem Planner-Schuljahr übernehmen (Label/Farbe je Kategorie werden übernommen, WP-seitige Stichwörter für das IServ-Keyword-Matching bleiben unverändert)' ),
            ),
        ),
        array(
            'version' => '4.26.1',
            'entries' => array(
                array( 'tag' => 'FIX', 'text' => 'Schuljahre-Tab (v2): Kalender-Status (Entwurf/Beschlossen) war seit 4.24.0 nur Text-Anzeige — Umschalten fehlte, Entwurf-Kiosk dadurch für neue/synchronisierte Schuljahre unerreichbar' ),
            ),
        ),
```

- [ ] **Step 4: Syntax check**

Run: `php -l plugin/gsh-terminplan.php`
Expected: `No syntax errors detected in plugin/gsh-terminplan.php`

- [ ] **Step 5: Run the full test suite one more time**

Run:
```bash
cd curriculr-terminplan
for f in tests/curriculr/test-*.php; do php "$f" || echo "FAILED: $f"; done
```
Expected: every file prints its own `OK` line, no `FAILED:` lines.

- [ ] **Step 6: Commit**

```bash
cd curriculr-terminplan
git add plugin/gsh-terminplan.php
git commit -m "release: bump WP plugin to v4.27.0 — Kategorien aus Planner übernehmen"
```

- [ ] **Step 7: Note the manual deploy step (not part of this plan's automation)**

This repo has no CI/CD — deployment is a manual ZIP build + WP-admin upload, per the workspace `CLAUDE.md`:
```bash
cd curriculr-terminplan/plugin
VER=$(grep "define.*GSH_TP_VERSION" gsh-terminplan.php | grep -oE "[0-9]+\.[0-9]+\.[0-9]+" | head -1)
zip ../../curriculr-terminplan-$VER.zip gsh-terminplan.php curriculr-data-layer.php curriculr-auth.php curriculr-guard.php page-terminplan-entwurf.php
zip -r ../../curriculr-terminplan-$VER.zip assets/
```
Flag to the user that this ZIP build + upload is a manual step to run when they're ready to deploy — do not run it automatically as part of plan execution.
