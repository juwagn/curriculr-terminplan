# WP Plugin Redesign (Ansatz B) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reduce WP admin from 4 tabs to 3 by integrating the Kiosk tab into System. Rename "Schuljahr-Profile" → "Schuljahre". Hide technical IDs behind `<details>`. Add a Status column to the Schuljahr card. Improve "Als aktiv setzen" UX with explanation text.

**Architecture:** All changes are in a single PHP file (`gsh-terminplan.php`). No REST API shape changes. No DB schema changes. No `curriculr-auth.php`, `curriculr-guard.php`, or `curriculr-data-layer.php` changes. After every edit: `php -l`, then test in WP admin.

**Tech Stack:** PHP 8 (procedural), WordPress admin UI, no Composer, no build system

**Spec:** `../../curriculr-planner/docs/superpowers/specs/2026-06-30-system-audit-neugestaltung-design.md`

## Global Constraints

- Run `php -l plugin/gsh-terminplan.php` after every change — must have no syntax errors
- All CSS in `plugin/assets/css/gsh-terminplan.css` only — never add new inline CSS in PHP (existing code violates this; do NOT introduce more)
- WordPress Coding Standards for PHP style
- Never echo unsanitized output — use `esc_html()`, `esc_attr()`, `esc_url()`
- Do NOT touch: `curriculr-auth.php`, `curriculr-guard.php`, `curriculr-data-layer.php`, ICS generation, shortcode renderer, `gsh_tp_parse_events`, table rendering, `gsh_tp_js()`, `gsh_tp_css()`
- Version bump: 4 places must stay in sync (plugin header comment, `GSH_TP_VERSION` constant, `gsh_tp_changelog()`, changelog block in header comment)
- Bump rule: UX improvements → minor version (4.25.0 → 4.26.0)

---

### Task 1: Rename Tab and Headings

**Files:**
- Modify: `plugin/gsh-terminplan.php`

**Interfaces:**
- Produces: `$tabs['_profile'] === 'Schuljahre'`; h2 inside profile tab reads "Schuljahre"

- [ ] **Step 1: Update `$tabs` array in `gsh_tp_settings_page()` (~line 3661)**

```php
// Before:
$tabs = array(
    '_profile'    => 'Schuljahr-Profile',
    '_kategorien' => 'Kategorien',
    '_kiosk'      => 'Kiosk',
    '_system'     => 'System &amp; Logs',
);

// After:
$tabs = array(
    '_profile'    => 'Schuljahre',
    '_kategorien' => 'Kategorien',
    '_system'     => 'System &amp; Logs',
);
```

(`_kiosk` removed — redirect added in Task 2.)

- [ ] **Step 2: Update h2 heading in `gsh_tp_render_profile_tab_v2()` (~line 4377)**

```php
// Before:
<h2 style="margin:0">Schuljahr-Profile</h2>

// After:
<h2 style="margin:0">Schuljahre</h2>
```

- [ ] **Step 3: Syntax check**

```bash
cd curriculr-terminplan && php -l plugin/gsh-terminplan.php
```

Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add plugin/gsh-terminplan.php
git commit -m "refactor: rename 'Schuljahr-Profile' to 'Schuljahre' in WP admin tabs"
```

---

### Task 2: Move Kiosk Tab into System Tab

**Files:**
- Modify: `plugin/gsh-terminplan.php`

**Interfaces:**
- Produces: `?tab=_kiosk` redirects to `?tab=_system`; Kiosk content appears as first section of System tab; `_kiosk` no longer in nav

- [ ] **Step 1: Add redirect for legacy `?tab=_kiosk` bookmarks**

In `gsh_tp_settings_page()`, locate the whitelist check (~line 3670):

```php
$active_tab = sanitize_key( $_GET['tab'] ?? '' );
if ( ! array_key_exists( $active_tab, $tabs ) ) {
    $active_tab = '_profile';
}
```

Replace with:

```php
$active_tab = sanitize_key( $_GET['tab'] ?? '' );
// Redirect legacy _kiosk bookmarks — Kiosk settings are now in System tab.
if ( '_kiosk' === $active_tab ) {
    wp_redirect( admin_url( 'options-general.php?page=gsh-terminplan&tab=_system' ) );
    exit;
}
if ( ! array_key_exists( $active_tab, $tabs ) ) {
    $active_tab = '_profile';
}
```

- [ ] **Step 2: Remove `_kiosk` branch from tab dispatch (~line 3779)**

```php
// Before:
if ( '_kategorien' === $active_tab ) {
    gsh_tp_render_kategorien_tab();
} elseif ( '_kiosk' === $active_tab ) {
    gsh_tp_render_kiosk_tab();
} elseif ( '_system' === $active_tab ) {
    gsh_tp_render_system_tab();
} else { // _profile
    gsh_tp_render_profile_tab_v2();
}

// After:
if ( '_kategorien' === $active_tab ) {
    gsh_tp_render_kategorien_tab();
} elseif ( '_system' === $active_tab ) {
    gsh_tp_render_system_tab();
} else { // _profile
    gsh_tp_render_profile_tab_v2();
}
```

- [ ] **Step 3: Call Kiosk content from within `gsh_tp_render_system_tab()` (~line 4797)**

At the very start of `gsh_tp_render_system_tab()`, before any existing output, add:

```php
function gsh_tp_render_system_tab() {
    // Kiosk + Entwurf-Vorschau — moved from former standalone Kiosk tab.
    gsh_tp_render_kiosk_tab();
    echo '<hr style="margin:24px 0" />';

    // ... existing function body continues unchanged below ...
```

- [ ] **Step 4: Syntax check**

```bash
cd curriculr-terminplan && php -l plugin/gsh-terminplan.php
```

Expected: `No syntax errors detected`

- [ ] **Step 5: Commit**

```bash
git add plugin/gsh-terminplan.php
git commit -m "feat: integrate Kiosk tab into System tab; redirect legacy ?tab=_kiosk"
```

---

### Task 3: Improve "Als aktiv setzen" UX

**Files:**
- Modify: `plugin/gsh-terminplan.php`

**Interfaces:**
- Produces: button text "Als aktives Schuljahr setzen"; one-line explanation below button

- [ ] **Step 1: Update button label + add explanation in `gsh_tp_render_profile_tab_v2()` (~line 4413)**

```php
// Before:
<button type="submit" name="gsh_tp_activate_schoolyear" value="1"
        class="button button-small" style="color:#1e8449;border-color:#1e8449">
    Als aktiv setzen
</button>

// After:
<button type="submit" name="gsh_tp_activate_schoolyear" value="1"
        class="button button-small" style="color:#1e8449;border-color:#1e8449">
    Als aktives Schuljahr setzen
</button>
<p class="description" style="margin:4px 0 0;font-size:12px">
    Dieses Schuljahr wird dann auf der Schulwebsite angezeigt.
</p>
```

- [ ] **Step 2: Syntax check**

```bash
cd curriculr-terminplan && php -l plugin/gsh-terminplan.php
```

Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add plugin/gsh-terminplan.php
git commit -m "ux: rename 'Als aktiv setzen' to 'Als aktives Schuljahr setzen' + explanation"
```

---

### Task 4: Hide Technical IDs Behind `<details>`

**Files:**
- Modify: `plugin/gsh-terminplan.php`

**Interfaces:**
- Produces: `ID: sj_2026_27` hidden by default; `<details>` wraps it; new schoolyear key field also behind `<details>`

- [ ] **Step 1: Wrap ID display in schoolyear card header (~line 4421)**

```php
// Before:
<span style="color:#888;font-size:12px">ID: <code><?php echo esc_html( $sy_key ); ?></code></span>

// After:
<details style="display:inline-block">
    <summary style="color:#aaa;font-size:11px;cursor:pointer;list-style:none">Erweitert</summary>
    <span style="color:#888;font-size:12px;display:block;margin-top:4px">
        ID: <code><?php echo esc_html( $sy_key ); ?></code>
    </span>
</details>
```

- [ ] **Step 2: Hide Schlüssel field in "+ Neues Schuljahr" form (~line 4379)**

```php
// Before:
<form method="post" style="margin:0">
    <?php wp_nonce_field( 'gsh_tp_new_schoolyear', 'gsh_tp_nsy_n' ); ?>
    <input type="text" name="gsh_tp_new_sy_key"   placeholder="sj_2027_28" class="regular-text" style="width:130px" required />
    <input type="text" name="gsh_tp_new_sy_label" placeholder="2027/28"    class="regular-text" style="width:100px" required />
    <button type="submit" name="gsh_tp_new_schoolyear" value="1" class="button" style="color:#27ae60;border-color:#27ae60">
        + Neues Schuljahr
    </button>
</form>

// After:
<form method="post" style="margin:0;display:flex;align-items:center;gap:6px;flex-wrap:wrap">
    <?php wp_nonce_field( 'gsh_tp_new_schoolyear', 'gsh_tp_nsy_n' ); ?>
    <input type="text" name="gsh_tp_new_sy_label" placeholder="2027/28"
           class="regular-text" style="width:110px" required />
    <button type="submit" name="gsh_tp_new_schoolyear" value="1"
            class="button" style="color:#27ae60;border-color:#27ae60">
        + Schuljahr anlegen
    </button>
    <details style="display:inline-block">
        <summary style="color:#aaa;font-size:11px;cursor:pointer;list-style:none">
            Schlüssel (Erweitert)
        </summary>
        <div style="margin-top:4px">
            <input type="text" name="gsh_tp_new_sy_key" placeholder="sj_2027_28"
                   class="regular-text" style="width:130px" />
            <p class="description" style="font-size:11px;margin-top:2px">
                Wird automatisch aus dem Label vorgeschlagen. Nur ändern wenn nötig.
            </p>
        </div>
    </details>
</form>
```

- [ ] **Step 3: Verify `gsh_tp_handle_new_schoolyear()` handles empty key**

```bash
grep -n "gsh_tp_handle_new_schoolyear\|new_sy_key" plugin/gsh-terminplan.php | head -25
```

If the handler requires `$_POST['gsh_tp_new_sy_key']` without a fallback, add auto-generation. Find the handler and update the key extraction to:

```php
$key = sanitize_key( $_POST['gsh_tp_new_sy_key'] ?? '' );
if ( empty( $key ) ) {
    $label_raw = sanitize_text_field( $_POST['gsh_tp_new_sy_label'] ?? '' );
    // e.g. "2027/28" → "sj_2027_28"
    $key = 'sj_' . sanitize_key( str_replace( '/', '_', $label_raw ) );
}
```

- [ ] **Step 4: Syntax check**

```bash
cd curriculr-terminplan && php -l plugin/gsh-terminplan.php
```

Expected: `No syntax errors detected`

- [ ] **Step 5: Commit**

```bash
git add plugin/gsh-terminplan.php
git commit -m "ux: hide schoolyear ID and key fields behind <details> Erweitert"
```

---

### Task 5: Add `gsh_tp_get_doc_status()` and Status Badge

**Files:**
- Modify: `plugin/gsh-terminplan.php`

**Interfaces:**
- Produces: `gsh_tp_get_doc_status( string $sj_key ): array{ stage: string, last_sent: string }|null`
- Stage labels: `entwurf` → "Entwurf", `genehmigt` → "Intern", `oeffentlich` → "Öffentlich" (matches SPA terminology from spec)
- DB table: `wp_curriculr_docs`, columns `sj` (VARCHAR), `stage` (VARCHAR), `updated_at` (DATETIME)

- [ ] **Step 1: Add helper function near `gsh_tp_get_schoolyears()` (~line 1505)**

```php
/**
 * Reads stage + last-sent timestamp for a schoolyear from wp_curriculr_docs.
 *
 * @param  string $sj_key  Schoolyear key, e.g. 'sj_2026_27'.
 * @return array{ stage: string, last_sent: string }|null  Null if no doc exists yet.
 */
function gsh_tp_get_doc_status( $sj_key ) {
    global $wpdb;
    $table = $wpdb->prefix . 'curriculr_docs';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT stage, updated_at FROM `{$table}` WHERE sj = %s LIMIT 1",
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

- [ ] **Step 2: Add status badge in schoolyear card in `gsh_tp_render_profile_tab_v2()`**

Inside the `foreach ( $schoolyears as $sy )` loop, after the card header div (the one with AKTIV badge, ~line 4400–4422) and before the Shared Settings section (~line 4424), insert:

```php
<?php
// Status badge — shows Veröffentlichungs-Stufe from wp_curriculr_docs.
$doc_status   = gsh_tp_get_doc_status( $sy_key );
$stage_labels = array(
    'entwurf'     => 'Entwurf',
    'genehmigt'   => 'Intern',
    'oeffentlich' => 'Öffentlich',
);
$stage_colors = array(
    'entwurf'     => '#d97706',
    'genehmigt'   => '#00467D',
    'oeffentlich' => '#16a34a',
);
if ( $doc_status ) :
    $s_label = $stage_labels[ $doc_status['stage'] ] ?? esc_html( $doc_status['stage'] );
    $s_color = $stage_colors[ $doc_status['stage'] ] ?? '#888';
    $s_time  = $doc_status['last_sent']
        ? date_i18n( 'd.m.Y, H:i', strtotime( $doc_status['last_sent'] ) ) . ' Uhr'
        : '';
?>
<div style="padding:6px 16px;background:#f0f0f1;border-bottom:1px solid #c3c4c7;font-size:12px;color:#3c434a">
    Veröffentlichung:
    <span style="display:inline-block;font-weight:600;padding:1px 8px;border-radius:10px;margin-left:4px;
                 background:<?php echo esc_attr( $s_color ); ?>22;
                 color:<?php echo esc_attr( $s_color ); ?>;
                 border:1px solid <?php echo esc_attr( $s_color ); ?>44">
        <?php echo esc_html( $s_label ); ?>
    </span>
    <?php if ( $s_time ) : ?>
        <span style="color:#888;margin-left:8px">
            Zuletzt gesendet: <?php echo esc_html( $s_time ); ?>
        </span>
    <?php endif; ?>
</div>
<?php endif; ?>
```

- [ ] **Step 3: Syntax check**

```bash
cd curriculr-terminplan && php -l plugin/gsh-terminplan.php
```

Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add plugin/gsh-terminplan.php
git commit -m "feat: add gsh_tp_get_doc_status() + status badge in Schuljahr card"
```

---

### Task 6: Version Bump to 4.26.0

**Files:**
- Modify: `plugin/gsh-terminplan.php` (4 places)

- [ ] **Step 1: Update plugin header comment**

Find `* Version: 4.25.0` → `* Version: 4.26.0`

- [ ] **Step 2: Update `GSH_TP_VERSION` constant**

Find `define('GSH_TP_VERSION', '4.25.0')` → `define('GSH_TP_VERSION', '4.26.0')`

- [ ] **Step 3: Prepend entry to `gsh_tp_changelog()`**

At the top of the return array inside `gsh_tp_changelog()`, add:

```php
array(
    'version' => '4.26.0',
    'entries' => array(
        array( 'tag' => 'UX',  'text' => 'Tab „Schuljahr-Profile" umbenannt in „Schuljahre"' ),
        array( 'tag' => 'UX',  'text' => 'Kiosk-Tab in System-Tab integriert; ?tab=_kiosk leitet auf ?tab=_system weiter' ),
        array( 'tag' => 'UX',  'text' => 'Schaltfläche „Als aktiv setzen" → „Als aktives Schuljahr setzen" mit Erklärungstext' ),
        array( 'tag' => 'UX',  'text' => 'Schuljahr-ID und Schlüssel-Feld hinter „Erweitert" versteckt' ),
        array( 'tag' => 'NEU', 'text' => 'Status-Anzeige in Schuljahr-Karte: Veröffentlichungs-Stufe (Entwurf/Intern/Öffentlich) und letzter Sync-Zeitstempel' ),
    ),
),
```

- [ ] **Step 4: Add 4.26.0 block in header changelog comment**

In the multiline `/* ... */` comment at the top of the file, find the `4.25.0` block and prepend above it:

```
 * v4.26.0
 * - [UX] Tab „Schuljahr-Profile" umbenannt in „Schuljahre"
 * - [UX] Kiosk-Tab in System-Tab integriert; ?tab=_kiosk leitet auf ?tab=_system weiter
 * - [UX] „Als aktiv setzen" → „Als aktives Schuljahr setzen" mit Erklärungstext
 * - [UX] Schuljahr-ID und Schlüssel-Feld hinter „Erweitert" versteckt
 * - [NEU] Status-Anzeige in Schuljahr-Karte (Stufe + Zeitstempel)
```

- [ ] **Step 5: Syntax check + tests**

```bash
cd curriculr-terminplan && php -l plugin/gsh-terminplan.php
php tests/curriculr/test-auth.php
php tests/curriculr/test-guard.php
php tests/curriculr/test-ics.php
php tests/curriculr/test-stage.php
```

Expected: no syntax errors; all tests exit 0.

- [ ] **Step 6: Commit**

```bash
git add plugin/gsh-terminplan.php
git commit -m "release: bump WP plugin to v4.26.0 — Schuljahre UX + Status column"
```

---

### Task 7: Build and Deploy ZIP

- [ ] **Step 1: Build ZIP**

```bash
cd curriculr-terminplan/plugin
VER=$(grep "define.*GSH_TP_VERSION" gsh-terminplan.php | grep -oE "[0-9]+\.[0-9]+\.[0-9]+" | head -1)
echo "Building v$VER"
zip ../../curriculr-terminplan-$VER.zip \
    gsh-terminplan.php \
    curriculr-data-layer.php \
    curriculr-auth.php \
    curriculr-guard.php \
    page-terminplan-entwurf.php \
    page-terminplan-kiosk.php
zip -r ../../curriculr-terminplan-$VER.zip assets/
ls -lh ../../curriculr-terminplan-$VER.zip
```

Expected: `curriculr-terminplan-4.26.0.zip` created.

- [ ] **Step 2: Deploy**

Upload `curriculr-terminplan-4.26.0.zip` via WP Admin → Plugins → Plugin hochladen. Activate.

- [ ] **Step 3: Verify in WP Admin**

- [ ] Tab shows "Schuljahre" (not "Schuljahr-Profile")
- [ ] No "Kiosk" tab in nav
- [ ] Kiosk settings appear as first section of System tab
- [ ] Schoolyear card shows Stage badge ("Intern" or "Öffentlich" if a doc exists)
- [ ] `?tab=_kiosk` redirects to `?tab=_system`
- [ ] ID hidden — click "Erweitert" to reveal
- [ ] "+ Schuljahr anlegen" form shows only Label field by default

---

## Self-Review

**Spec coverage:**

| Problem | Resolution |
|---------|------------|
| P-06 "Schuljahr-Profile" confusing | → "Schuljahre" ✓ |
| P-08 "Als aktiv setzen" no explanation | → "Als aktives Schuljahr setzen" + text ✓ |
| P-12 Calendar ID visible and confusing | → behind `<details>` ✓ |

**Open risk — Task 2 Kiosk integration:** `gsh_tp_render_kiosk_tab()` currently opens with `<h2>Entwurf-Vorschau...</h2>`. When embedded in System tab, there will be two h2 headings (Kiosk h2 + System h2). This is acceptable. If it looks wrong, wrap the kiosk call in `<section>` with its own heading style.

**Open risk — Task 4 key auto-generation:** Only needed if `gsh_tp_handle_new_schoolyear()` uses `$_POST['gsh_tp_new_sy_key']` as required. Step 3 verifies this. If the key was already optional (falls back to label-derived value), no change needed.

**No data migration.** `gsh_tp_schoolyears` option, `wp_curriculr_docs` table, ICS feeds, and REST endpoints are untouched. Existing IServ calendar subscriptions continue working.
