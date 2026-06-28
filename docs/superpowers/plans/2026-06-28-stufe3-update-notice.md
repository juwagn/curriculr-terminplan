# Stufe 3 — Update Notice & Release Notes Template Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a dismissible WP admin banner that appears after each plugin update, plus a git commit template and CLAUDE.md changelog tag docs.

**Architecture:** Version comparison on `admin_notices` (no `upgrader_process_complete` — plugin deploys via FTP/ZIP, not WP updater). Dismiss via `wp_ajax` + nonce → `update_option('gsh_tp_noticed_version', GSH_TP_VERSION, false)`. Docs live in `.gitmessage` + CLAUDE.md.

**Tech Stack:** PHP (WordPress plugin, no Composer), WP hooks API, jQuery (always present in WP admin).

## Global Constraints

- All CSS exclusively in `plugin/assets/css/gsh-terminplan.css` — never in PHP heredocs/echo strings. The notice uses only WP admin classes; no CSS additions needed.
- WordPress Coding Standards: spaces inside parens for control structures, `array()` not `[]`, snake_case.
- No `any`, no TypeScript — this is a PHP-only feature.
- `autoload=false` on all new `update_option` calls.
- Do NOT touch: `curriculr-auth.php`, `wp-stage.ts`, `wp_curriculr_doc_revisions` schema.
- Spec: `curriculr-terminplan/docs/superpowers/specs/2026-06-28-stufe3-update-notice-design.md`

---

### Task 1: Admin Update Notice

**Files:**
- Modify: `plugin/gsh-terminplan.php` — add two functions + two hook registrations

No new test file (spec explicitly excludes: notice logic is a one-liner WP option comparison + nonce check; verification is manual). Syntax-check replaces test run.

**Interfaces:**
- Produces: `gsh_tp_update_notice()` (registered on `admin_notices`), `gsh_tp_ajax_dismiss_notice()` (registered on `wp_ajax_gsh_tp_dismiss_notice`)
- Uses: `gsh_tp_changelog()` (already exists), `GSH_TP_VERSION` constant (already defined), WP functions `get_option`/`update_option`/`current_user_can`/`wp_create_nonce`/`wp_json_encode`/`check_ajax_referer`/`wp_die`

- [ ] **Step 1: Add `gsh_tp_update_notice()` function**

Add this function to `plugin/gsh-terminplan.php` just before line 2561 (before `function gsh_tp_ajax_save_categories()`). Insert at line ~2560 so it sits with the other AJAX/admin helpers:

```php
/**
 * Zeigt einen dismissiblen Admin-Hinweis nach einem Plugin-Update.
 * Vergleich GSH_TP_VERSION mit gespeicherter gsh_tp_noticed_version.
 *
 * @since 4.23.0
 */
function gsh_tp_update_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( GSH_TP_VERSION === get_option( 'gsh_tp_noticed_version', '' ) ) {
        return;
    }
    $cl      = gsh_tp_changelog();
    $entries = ! empty( $cl ) ? $cl[0]['entries'] : array();
    $nonce   = wp_create_nonce( 'gsh_tp_dismiss_notice' );
    ?>
    <div class="notice notice-info is-dismissible" id="gsh-tp-update-notice">
        <p>
            <strong>Curriculr <?php echo esc_html( GSH_TP_VERSION ); ?> installiert</strong>
            &mdash; <a href="#" onclick="if(typeof gshAdminChangelogOpen==='function'){gshAdminChangelogOpen();}return false;">Vollständiges Changelog</a>
        </p>
        <?php if ( ! empty( $entries ) ) : ?>
        <ul>
            <?php foreach ( $entries as $entry ) : ?>
            <li>
                <strong>[<?php echo esc_html( $entry['tag'] ); ?>]</strong>
                <?php echo esc_html( $entry['text'] ); ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <script>
    (function() {
        var el = document.getElementById('gsh-tp-update-notice');
        if ( ! el ) { return; }
        el.addEventListener('click', function(e) {
            if ( ! e.target.classList.contains('notice-dismiss') ) { return; }
            jQuery.post(ajaxurl, {
                action:      'gsh_tp_dismiss_notice',
                _ajax_nonce: <?php echo wp_json_encode( $nonce ); ?>
            });
        });
    }());
    </script>
    <?php
}
```

Note: the onclick guard `typeof gshAdminChangelogOpen==='function'` prevents JS errors on admin pages other than the plugin settings page, where the modal does not exist.

- [ ] **Step 2: Add `gsh_tp_ajax_dismiss_notice()` function**

Add immediately after `gsh_tp_update_notice()`:

```php
/**
 * AJAX-Handler: Notice dismisst → gsh_tp_noticed_version auf aktuelle Version setzen.
 *
 * @since 4.23.0
 */
function gsh_tp_ajax_dismiss_notice() {
    check_ajax_referer( 'gsh_tp_dismiss_notice' );
    if ( current_user_can( 'manage_options' ) ) {
        update_option( 'gsh_tp_noticed_version', GSH_TP_VERSION, false );
    }
    wp_die();
}
```

- [ ] **Step 3: Register hooks**

In `plugin/gsh-terminplan.php`, find the block at line ~2285–2288:

```php
add_action( 'wp_ajax_gsh_tp_feedback',        'gsh_tp_ajax_feedback' );
add_action( 'wp_ajax_nopriv_gsh_tp_feedback', 'gsh_tp_ajax_feedback' );
// Kategorien-AJAX (nur eingeloggte Admins)
add_action( 'wp_ajax_gsh_tp_save_categories', 'gsh_tp_ajax_save_categories' );
```

Add two lines immediately after `gsh_tp_save_categories`:

```php
// Update-Notice (erscheint nach Plugin-Update bis dismissed)
add_action( 'admin_notices',                 'gsh_tp_update_notice' );
add_action( 'wp_ajax_gsh_tp_dismiss_notice', 'gsh_tp_ajax_dismiss_notice' );
```

- [ ] **Step 4: Syntax-check**

```bash
cd curriculr-terminplan
php -l plugin/gsh-terminplan.php
```

Expected: `No syntax errors detected in plugin/gsh-terminplan.php`

- [ ] **Step 5: Manual verification checklist**

(Document in commit message that this was verified, or skip if no WP instance available.)

1. Upload ZIP to WP admin → notice appears with current version + changelog entries
2. Click × → notice disappears; `get_option('gsh_tp_noticed_version')` equals `GSH_TP_VERSION`
3. Reload → no notice
4. On a different admin page (e.g. Dashboard): notice also appears; "Vollständiges Changelog" link does not throw JS error

- [ ] **Step 6: Bump version to 4.23.0**

In `plugin/gsh-terminplan.php`, bump version in all 4 places:

1. Plugin header comment: `* Version: 4.23.0`
2. `define( 'GSH_TP_VERSION', '4.23.0' )`
3. Prepend to `gsh_tp_changelog()`:
```php
array(
    'version' => '4.23.0',
    'entries' => array(
        array( 'tag' => 'NEU', 'text' => 'Update-Hinweis im WP-Admin nach Plugin-Update (dismissibel, per-Version)' ),
    ),
),
```
4. Changelog block in the header comment — prepend:
```
 * - [NEU] Update-Hinweis im WP-Admin nach Plugin-Update (dismissibel, per-Version)
```

- [ ] **Step 7: Syntax-check after version bump**

```bash
php -l plugin/gsh-terminplan.php
```

Expected: `No syntax errors detected in plugin/gsh-terminplan.php`

- [ ] **Step 8: Commit**

```bash
git add plugin/gsh-terminplan.php
git commit -m "feat: v4.23.0 — dismissible admin update notice after plugin install"
```

---

### Task 2: `.gitmessage` + CLAUDE.md Docs

**Files:**
- Create: `curriculr-terminplan/.gitmessage`
- Modify: `curriculr-terminplan/CLAUDE.md`

No test cycle — documentation only.

**Interfaces:**
- Produces: a commit template activated with `git config commit.template .gitmessage`

- [ ] **Step 1: Create `.gitmessage`**

Create `curriculr-terminplan/.gitmessage` with this exact content:

```
# Curriculr release vX.Y.Z
# Vor dem Commit: Version in gsh-terminplan.php an 4 Stellen bumpen.
#
# Changelog-Eintrag in gsh_tp_changelog() als ERSTEN Block einfügen:
#
#   array(
#       'version' => 'X.Y.Z',
#       'entries' => array(
#           array( 'tag' => 'NEU',      'text' => '...' ),
#           array( 'tag' => 'FIX',      'text' => '...' ),
#           array( 'tag' => 'SECURITY', 'text' => '...' ),
#           array( 'tag' => 'UX',       'text' => '...' ),
#           array( 'tag' => 'INFRA',    'text' => '...' ),
#       ),
#   ),
#
# Nur benötigte Tags einfügen — leere Einträge weglassen.
```

- [ ] **Step 2: Update CLAUDE.md — Commands section**

In `curriculr-terminplan/CLAUDE.md`, find the Commands code block. Add the git config command at the end of the block, before the closing ` ``` `:

```bash
# Activate commit template (run once per clone)
git config commit.template .gitmessage
```

- [ ] **Step 3: Update CLAUDE.md — Versioning section**

In `curriculr-terminplan/CLAUDE.md`, find the Versioning section. After the bump rule line (`Bump rule: bugfix → patch, new feature → minor, breaking REST/DB change → major.`), add:

```markdown
### Changelog Tags

| Tag | Wann verwenden |
|-----|----------------|
| `NEU` | Neues user-sichtbares Feature |
| `FIX` | Bugfix |
| `SECURITY` | Sicherheitsrelevante Änderung |
| `UX` | UX/Layout-Verbesserung ohne neues Feature |
| `DESIGN` | Rein visuelle Änderung (Farben, Spacing) |
| `INFRA` | Intern/Tooling, kein user-sichtbarer Effekt |

Only insert tags that apply to the release — omit unused ones.
```

- [ ] **Step 4: Commit**

```bash
git add .gitmessage CLAUDE.md
git commit -m "docs: add .gitmessage commit template and changelog tag reference"
```
