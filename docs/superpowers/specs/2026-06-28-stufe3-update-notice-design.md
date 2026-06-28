# Stufe 3 — Update Notice & Release Notes Template

**Date:** 2026-06-28
**Feature:** Dismissible WP admin notice after plugin update + git commit template + changelog tag docs

---

## Goal

After each manual ZIP upload/update of the plugin, a dismissible banner appears in WP admin showing what changed in the new version. Additionally, a git commit template and CLAUDE.md tag table reduce the friction of writing consistent changelog entries.

---

## Section 1 — Admin Update Notice

### Trigger

On every `admin_notices` action:

1. `current_user_can('manage_options')` — abort if not admin
2. Compare `GSH_TP_VERSION` with `get_option('gsh_tp_noticed_version', '')` — if equal, do nothing
3. If different: render notice

### Why version comparison, not `upgrader_process_complete`

The plugin is deployed via manual FTP/ZIP upload to shared hosting (w3w.de). WordPress's `upgrader_process_complete` fires only through the WP admin updater, not on manual uploads. Version comparison on every page load is the correct approach for this deployment model.

### Notice HTML

Standard WP admin markup — no custom CSS needed:

```php
<div class="notice notice-info is-dismissible" id="gsh-tp-update-notice">
    <p>
        <strong>Curriculr <?php echo esc_html( GSH_TP_VERSION ); ?> installiert</strong>
        — <a href="#" onclick="gshAdminChangelogOpen(); return false;">Vollständiges Changelog</a>
    </p>
    <ul>
        <?php foreach ( gsh_tp_changelog()[0]['entries'] as $entry ) : ?>
        <li>
            <strong>[<?php echo esc_html( $entry['tag'] ); ?>]</strong>
            <?php echo esc_html( $entry['text'] ); ?>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
```

The "Vollständiges Changelog" link opens the existing `gshAdminChangelog` modal (already present on `gsh_tp_admin_page()`). On other admin pages where the modal doesn't exist, the link is a no-op — acceptable.

### Dismiss mechanism

WP's `.is-dismissible` class renders a close button and fires a standard `click` event. We intercept it with a small inline script on the notice div:

```php
<script>
document.getElementById('gsh-tp-update-notice')
    .addEventListener('click', function(e) {
        if ( ! e.target.classList.contains('notice-dismiss') ) return;
        jQuery.post(ajaxurl, {
            action:      'gsh_tp_dismiss_notice',
            _ajax_nonce: <?php echo wp_json_encode( wp_create_nonce('gsh_tp_dismiss_notice') ); ?>
        });
    });
</script>
```

AJAX handler (registered via `add_action('wp_ajax_gsh_tp_dismiss_notice', ...)`):

```php
function gsh_tp_ajax_dismiss_notice() {
    check_ajax_referer( 'gsh_tp_dismiss_notice' );
    if ( current_user_can( 'manage_options' ) ) {
        update_option( 'gsh_tp_noticed_version', GSH_TP_VERSION, false );
    }
    wp_die();
}
```

### Option

`gsh_tp_noticed_version` — string, stores the last-dismissed version. `autoload=false` (notice check only runs on `admin_notices`, not every request). Not included in `gather_settings`/`apply_settings` (it's UI state, not config).

### Scope

- Registered in `gsh-terminplan.php` alongside the existing admin hooks
- No new files
- No CSS additions — WP admin classes handle styling

---

## Section 2 — Git Commit Template

**File:** `curriculr-terminplan/.gitmessage`

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

**Activation:** `git config commit.template .gitmessage` — must be run once locally. Add to CLAUDE.md under Commands so it's not forgotten.

---

## Section 3 — CLAUDE.md Changelog Tag Reference

Add to `curriculr-terminplan/CLAUDE.md` under **Versioning**:

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

---

## Constraints

- All CSS stays in `plugin/assets/css/gsh-terminplan.css` — the notice uses only WP admin classes, no additions needed
- No CSS in PHP heredocs or echo strings
- The small inline `<script>` on the notice div is necessary for dismiss interception; it is scoped to that single element and does not violate the CSS rule (JS ≠ CSS)
- `gsh_tp_noticed_version` option: `autoload=false`, not in `gather_settings`/`apply_settings`
- `curriculr-auth.php`, `wp-stage.ts`, `wp_curriculr_doc_revisions` schema — untouched

---

## Files Changed

| File | Change |
|------|--------|
| `plugin/gsh-terminplan.php` | Add `gsh_tp_update_notice()` + `gsh_tp_ajax_dismiss_notice()`, register both hooks |
| `CLAUDE.md` | Add changelog tag table + `.gitmessage` activation command under Commands |
| `.gitmessage` | New file |

No new test files — the notice logic is a one-liner WP option comparison; the AJAX handler is a nonce check + option write, both covered by existing WP patterns. Manual verification: install plugin, confirm notice appears; click ×, confirm it disappears and `gsh_tp_noticed_version` is set.
