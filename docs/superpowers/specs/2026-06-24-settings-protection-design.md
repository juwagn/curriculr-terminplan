# Stufe 1: Settings-Schutz — Design-Spec

**Date:** 2026-06-24  
**Plugin version target:** 4.21.0 (minor bump — new feature, no REST/DB shape change)  
**Scope:** WP-admin only. No SPA changes. No auth-flow changes.

---

## Problem

`gsh_tp_uninstall()` deletes all `gsh_tp_*` WP options on plugin deletion. Three curriculr-specific options (`gsh_tp_curriculr_origin`, `gsh_tp_curriculr_profile_map`, `gsh_tp_curriculr_db_version`) are missing from that list — an inconsistency that leaves orphaned rows. More critically: there is no export/import path and no warning before deletion, so admins lose all plugin configuration silently.

Planungsdaten (`wp_curriculr_docs`, `wp_curriculr_doc_revisions`) are unaffected — they survive uninstall already.

---

## Decisions

| # | Decision | Choice |
|---|----------|--------|
| 1 | Export delivery | JSON download (browser) only |
| 2 | Uninstall behavior for curriculr options | Add to cleanup — consistent clean slate |
| 3 | Delete warning | JS intercept modal + passive `plugin_action_links` hint |
| 4 | UI architecture | Dedicated "Datensicherung" submenu page |
| 5 | Import conflict | Silent overwrite — recovery tool, not merge tool |

---

## Architecture

### New files
- `assets/js/curriculr-delete-warn.js` — enqueued on `plugins.php` only

### Files modified
- `gsh-terminplan.php` — submenu registration, warning JS enqueue, `plugin_action_links` hook, uninstall additions
- `curriculr-data-layer.php` — export AJAX handler, import POST handler, `gsh_tp_curriculr_gather_settings()` helper, `gsh_tp_curriculr_apply_settings()` helper
- `assets/css/gsh-terminplan.css` — backup page styles

---

## Export Flow

1. Admin clicks "Einstellungen exportieren" on the Datensicherung page.
2. JS POSTs to `admin-ajax.php?action=gsh_tp_curriculr_export` with nonce.
3. Handler calls `gsh_tp_curriculr_gather_settings()`, wraps result:
   ```json
   {
     "version": "4.21.0",
     "exported_at": "2026-06-24T10:00:00+02:00",
     "settings": { ... }
   }
   ```
4. Response headers: `Content-Type: application/json`, `Content-Disposition: attachment; filename="curriculr-settings-2026-06-24.json"`.
5. JS triggers download via Blob URL (`URL.createObjectURL`).

### Keys exported by `gsh_tp_curriculr_gather_settings()`

**Global config:**
- `gsh_tp_profiles`
- `gsh_tp_ical_url`
- `gsh_tp_cache_duration`
- `gsh_tp_schuljahr_start`
- `gsh_tp_quartal_grenzen`
- `gsh_tp_kategorie_mapping`
- `gsh_tp_categories`
- `gsh_tp_kiosk_token`
- `gsh_tp_draft_kiosk_token`
- `gsh_tp_iserv_domain`

**Curriculr integration:**
- `gsh_tp_curriculr_origin`
- `gsh_tp_curriculr_profile_map`

**Per-profile** (derived from `gsh_tp_profiles` list at export time):
- `gsh_tp_ical_{pid}` via `gsh_tp_ck('gsh_tp_ical_', $pid)` — versioned key format, same helper used on import to write back to the current version slot

**Not exported** (operational/cache — no value restoring these):
- `GSH_TP_CACHE_KEY`, `GSH_TP_BACKUP_KEY` (cached iCal data)
- `gsh_tp_sync_logs_{pid}`, `gsh_tp_backup_{pid}`, `gsh_tp_sync_{pid}`
- `gsh_tp_cache_ver`, `gsh_tp_last_sync`
- `gsh_tp_curriculr_db_version` (reset fresh by `gsh_tp_curriculr_install()` on reinstall)

---

## Import Flow

1. Admin uploads `.json` file via `<form enctype="multipart/form-data" method="post">` on the Datensicherung page.
2. POST handler validates:
   - Nonce: `gsh_tp_import_settings`
   - Capability: `manage_options`
   - File size: max 512 KB
   - MIME: `application/json` or `text/plain` (browsers vary)
   - `json_decode` succeeds and `$data['settings']` is an array
   - `$data['version']` field present (format sanity)
3. Calls `gsh_tp_curriculr_apply_settings($data['settings'])`:
   - Iterates known-key allowlist only (unknown keys silently ignored)
   - Calls `update_option()` for each present key
   - Per-profile `gsh_tp_ical_{pid}` keys rebuilt from `gsh_tp_profiles` list in the import
4. Redirect: `wp_redirect(add_query_arg('imported', '1', $page_url))`. Success admin notice shown on redirect.
5. No rollback on partial failure — import is best-effort (settings are not transactional in WP).

---

## Delete Warning

### `plugin_action_links` (passive, works without JS)
Hook adds "Einstellungen sichern →" link before "Löschen" in the plugin list row. Links to the Datensicherung page.

### JS intercept (`curriculr-delete-warn.js`)
Enqueued only on `plugins.php` (`$pagenow === 'plugins.php'`).

Behavior:
1. Intercepts click on `tr[data-slug="gsh-terminplan"] .delete a`.
2. Prevents default navigation.
3. Renders modal overlay:
   - Heading: "Plugin löschen"
   - Body: "Vor dem Löschen Einstellungen exportieren. Curriculr-Dokumente bleiben erhalten."
   - Button 1: "Einstellungen exportieren" → opens Datensicherung page in new tab
   - Button 2: "Trotzdem löschen" → follows original delete href
   - Button 3: "Abbrechen" → closes modal
4. Modal uses native WP admin colors (no custom palette needed).

---

## Uninstall Cleanup

Three options added to `gsh_tp_uninstall()` foreach array:
```php
'gsh_tp_curriculr_origin',
'gsh_tp_curriculr_profile_map',
'gsh_tp_curriculr_db_version',
```

Cron cleanup added to `gsh_tp_uninstall()`:
```php
wp_clear_scheduled_hook( 'gsh_tp_curriculr_daily_backup' );
```
(Currently only cleared in the deactivation hook in `curriculr-data-layer.php` — if someone deletes without deactivating, this cron orphans.)

---

## Security

| Threat | Mitigation |
|--------|------------|
| Unauthorized export | `manage_options` cap check + nonce |
| Unauthorized import | `manage_options` cap check + nonce `gsh_tp_import_settings` |
| Malicious JSON payload | Known-key allowlist in `gsh_tp_curriculr_apply_settings()` — only `update_option()` calls, no eval |
| Oversized upload | 512 KB hard limit before `json_decode` |
| MIME spoofing | Accept `application/json` and `text/plain`; validate structure not MIME alone |

---

## Out of Scope

- Auth-flow (`curriculr-auth.php`) — not touched
- `wp_curriculr_docs` / `wp_curriculr_doc_revisions` tables — survive uninstall already, not part of settings backup
- SPA (`curriculr-planner/`) — no changes
- Stufe 2 (Mehrkalender) — separate spec
