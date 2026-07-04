# Manueller Planungsdokument-Import (SSO-Alternative) — Design-Spec

**Date:** 2026-07-04
**Plugin version target:** 4.28.0 (minor bump — new feature, no REST/DB shape change)
**Scope:** WP-admin only. No REST route changes. No auth-flow (`curriculr-auth.php` / `curriculr-guard.php`) changes. No mandatory SPA changes.

---

## Problem

The only way to get planning data from the SPA into the WP plugin is `PUT /curriculr/v1/doc/{sj}`, which requires a Bearer app-token issued exclusively via IServ OIDC login (`curriculr-auth.php`). Schools without IServ/SSO have no way to publish their plan — there is no fallback (no application password, no static token, no file upload).

Two things already exist that make a minimal fix possible:
- The SPA's "JSON-Backup" export (`ExportDropdown.tsx` → `storage.exportJson(doc)`) produces exactly the raw `PlannerDocument` shape the REST `doc` field expects — no new SPA export code needed.
- WP already lets admins fully manage schoolyears and calendars by hand, independent of SSO/REST (`gsh_tp_render_profile_tab_v2()`, `gsh_tp_handle_new_schoolyear()` etc.). The only missing piece is getting the **document** (events/schoolyear JSON) itself into `wp_curriculr_docs`.

A separate "Datensicherung" page already exports/imports plugin **settings** — explicitly excluding Planungsdokumente (see its own copy: *"Curriculr-Planungsdokumente sind nicht enthalten"*). This feature is a distinct, additive capability, not a change to that existing settings backup.

---

## Decisions

| # | Decision | Choice |
|---|----------|--------|
| 1 | Sync scope | One-way file upload only — no pull, no live "Verbindung testen", no 409-conflict UI. Admin exports from the SPA, uploads in wp-admin. |
| 2 | Auth model | None new. Gated entirely by existing WP admin login + `manage_options` capability + nonce — same trust boundary as the rest of wp-admin. No Bearer token, no rate-limiting needed (this isn't a public network-facing secret like the kiosk token). |
| 3 | UI placement | Inline in the existing per-schoolyear card (`gsh_tp_render_profile_tab_v2`), directly under the already-rendered "Veröffentlichung" status block. Not a new page/menu — sj_key and current doc status are already in view there. |
| 4 | Overwrite protection | Confirmation checkbox (only shown/required if a doc already exists for that schoolyear) + a "Sichern ↓" one-click download of the current server doc, shown right next to the checkbox. |
| 5 | Version conflict handling | Manual upload is a forced overwrite: `baseVersion` is set to the current stored version at request time, so `gsh_tp_curriculr_version_decision()` always resolves `'ok'`. No conflict path exposed to the admin (the confirmation checkbox is the safeguard instead). |
| 6 | SSO toggle | None. Schoolyear/calendar management is already SSO-independent; the upload is just an additional write path into the same table. No per-schoolyear "manual mode" switch. |
| 7 | SPA changes | None required. Optional copy-only hint in `PublishTab.tsx` pointing school admins without SSO at the JSON-Backup export + wp-admin upload. No protocol/behavior change → no `package.json` version bump. |

---

## Architecture

### Files modified

- **`curriculr-data-layer.php`**
  - New pure function `gsh_tp_curriculr_decode_doc_upload( $raw )` — JSON-decodes the upload and reuses the existing `gsh_tp_curriculr_validate_envelope()` shape check (wraps as `{doc, baseVersion: 0}`). No WordPress calls — directly unit-testable like `test-envelope.php`.
  - `gsh_tp_curriculr_repo_put()` signature gains an additive 5th param: `$author_override = null`. When provided (`['sub' => ..., 'name' => ...]`), used instead of `gsh_tp_curriculr_guard_current_claims()` for the revision's author fields. Default `null` preserves all existing behavior/tests untouched.
  - New `gsh_tp_curriculr_handle_doc_import()` — **inline** POST handler (same pattern as `gsh_tp_handle_new_schoolyear()`), called from `gsh_tp_settings_page()`'s existing dispatch block. Renders `<div class="notice">` and returns — no `exit`, no `header()` calls (the admin page has already started rendering by the time this runs, so headers cannot be sent here).
  - New `gsh_tp_curriculr_handle_doc_export()` — **admin-post.php** action (`admin_post_gsh_tp_curriculr_doc_export`), registered like the existing settings export. Needs `header()` + `exit` for the file download, which requires the separate `admin-post.php` request cycle (unlike the import handler, this cannot run inline mid-page).

- **`gsh-terminplan.php`**
  - `gsh_tp_settings_page()`: add `if ( isset( $_POST['gsh_tp_doc_import'] ) ) { gsh_tp_curriculr_handle_doc_import(); }` to the existing schoolyear-actions dispatch block.
  - `gsh_tp_render_profile_tab_v2()`: new section per schoolyear card (see UI below).
  - Version bump: header comment `Version:`, `GSH_TP_VERSION` define, `gsh_tp_changelog()` new entry (tag `NEU`), changelog block in header comment. 4.27.2 → 4.28.0.

- **`src/components/settings/PublishTab.tsx`** (SPA, optional copy-only)
  - One line under the existing "Import"/"Export" sections: hint that schools without IServ-SSO can use the JSON-Backup export + upload it in wp-admin under the schoolyear's card.

### Files added

- `tests/curriculr/test-doc-upload.php` — pure tests for `gsh_tp_curriculr_decode_doc_upload()`.

---

## UI (per schoolyear card, `gsh_tp_render_profile_tab_v2`)

Rendered directly below the existing "Veröffentlichung: Entwurf/Intern/Öffentlich · Zuletzt gesendet: …" status block (which already reads `gsh_tp_get_doc_status( $sy_key )` — reused, no new query):

```
Planungsdokument (manueller Upload)
Für Schulen ohne IServ-SSO: Plan im Planer exportieren (Export ↓ → „JSON-Backup") und hier hochladen.

[nur wenn Doc existiert:]
Aktueller Stand: Version 3, 03.07.2026 — Sichern ↓

[Datei wählen]  Stufe: [Entwurf ▾]  ☐ aktuellen Stand überschreiben (nur wenn Doc existiert)
[Dokument hochladen / aktualisieren]
```

Form: `<form method="post" enctype="multipart/form-data">`, nonce `gsh_tp_doc_import_{sy_key}` (field name `gsh_tp_di_n_{pid}`, mirroring the existing `gsh_tp_ssy_n_{pid}` convention), hidden `gsh_tp_di_sy = {sy_key}`, submit button `name="gsh_tp_doc_import" value="1"`.

"Sichern ↓" link: `admin_url('admin-post.php?action=gsh_tp_curriculr_doc_export&sj={sy_key}&_wpnonce=...')`, only rendered when `gsh_tp_get_doc_status($sy_key)` returns non-null.

---

## Import Flow

1. Admin exports "JSON-Backup" from the SPA (existing feature, unchanged) — file is the raw `PlannerDocument`.
2. Admin opens the schoolyear's card in wp-admin, selects the file, picks a Veröffentlichungsstufe, checks the overwrite confirmation (if a doc already exists), submits.
3. `gsh_tp_curriculr_handle_doc_import()` validates, in order:
   - Nonce (`gsh_tp_doc_import_{sy_key}`)
   - `sy_key` exists in `gsh_tp_get_schoolyears()` (rejects arbitrary/unknown keys)
   - If a doc already exists for this `sy_key`: `gsh_tp_di_confirm` checkbox must be checked
   - File present, `UPLOAD_ERR_OK`, size ≤ 2 MB (larger than the 512 KB settings-backup limit — a full schoolyear with many events/notes can reasonably exceed that)
   - `gsh_tp_curriculr_decode_doc_upload()` succeeds (valid JSON, `doc.events` is an array) — same laxity as the REST PUT endpoint already accepts; no new validation surface
4. On success: `$base_version` = current stored version (0 if none) → `gsh_tp_curriculr_repo_put( $sy_key, $doc, $base_version, $stage, $author_override )` where `$author_override` = `['sub' => 'manual:' . get_current_user_id(), 'name' => wp_get_current_user()->display_name]`. Decision is always `'ok'` by construction.
5. `gsh_tp_curriculr_after_put( $sy_key, $res['feed_token'] )` — same cache/feed-URL refresh the REST PUT path triggers, so ICS feeds and IServ subscriptions pick up the new data immediately.
6. Success/error rendered inline as a `<div class="notice">`, page continues rendering normally (no redirect) — consistent with every other schoolyear-card form action.

## Export Flow ("Sichern ↓")

1. Admin clicks "Sichern ↓" next to the current-version line.
2. `admin-post.php` → `gsh_tp_curriculr_handle_doc_export()`: `check_admin_referer('gsh_tp_curriculr_doc_export_' . $sj)`, `current_user_can('manage_options')`, `gsh_tp_curriculr_repo_get($sj)`.
3. Streams the stored `json` column verbatim (already a JSON string of the raw doc — no re-encoding) as `{sj}-{date}.json`, `Content-Disposition: attachment`.
4. This file is the same shape as the SPA's JSON-Backup export, so it doubles as a safety copy before overwriting and stays forward-compatible with the SPA's existing (currently unused in UI) `storage.importJson()`.

---

## Security

| Threat | Mitigation |
|--------|------------|
| Unauthorized import/export | `manage_options` capability check + per-schoolyear nonce, identical trust boundary to every other schoolyear-card action already in this tab |
| CSRF | Nonce scoped per `sy_key` (`gsh_tp_doc_import_{sy_key}` / `gsh_tp_curriculr_doc_export_{sy_key}`), matching the existing `gsh_tp_save_schoolyear_{pid}` pattern |
| Unknown/arbitrary schoolyear key injection | `sy_key` must already exist in `gsh_tp_get_schoolyears()` — the upload cannot create a new schoolyear, only write a doc into one the admin already created through the existing UI |
| Oversized upload | 2 MB hard limit before `json_decode` |
| Malformed/malicious JSON | `json_decode` + `doc.events` array shape check (reuses `validate_envelope`); on failure, rejected with a generic notice — no partial write |
| Accidental overwrite of a newer server-side edit | Confirmation checkbox (required whenever a doc already exists) + adjacent one-click backup download of the current stored version |
| Weakening the OIDC/Bearer fail-closed guarantee | Not applicable — this path never touches `curriculr-auth.php` / `curriculr-guard.php` / the app-token guard. It's a second, entirely separate write path gated by native WP admin auth, same as the existing settings import already is. |

No rate-limiting is introduced (unlike the kiosk token's `gsh_tp_check_kiosk_access()` brute-force guard) because this endpoint is never reachable without an authenticated WP admin session — it is not a guessable public secret, so the kiosk threat model doesn't apply here.

---

## Testing

- `tests/curriculr/test-doc-upload.php` (new): `gsh_tp_curriculr_decode_doc_upload()` — valid doc, invalid JSON, missing `events`, non-object body. Mirrors `test-envelope.php` style (no WP stubs needed).
- `tests/curriculr/test-revisions.php` (extended): `gsh_tp_curriculr_repo_put()` with `$author_override` set — asserts the revision row's `author_sub`/`author_name` come from the override rather than guard claims, and that omitting it (existing calls) is unaffected.
- `gsh_tp_curriculr_handle_doc_import()` / `handle_doc_export()` themselves are thin WP glue (nonce/`$_FILES`/`current_user_can`) and are **not** unit-tested, consistent with the existing `gsh_tp_curriculr_handle_export()` / `handle_import()` (settings backup), which follow the same convention.
- Manual verification: `php -l` on all four touched PHP files; upload a real SPA JSON-Backup export into a local/staging WP; confirm ICS feed reflects the new data and the revision appears with the WP-admin's display name as author.

---

## Out of Scope

- Two-way sync (pull/test-connection/409-conflict UI) without SSO — considered and explicitly rejected in favor of the simpler one-way upload (decision #1).
- A static per-site API key / second Bearer-auth mechanism in `curriculr-guard.php` — considered as an alternative approach; rejected for now due to the added security surface (persistent secret, needs rate-limiting, revocation UI, SPA auth-flow changes) relative to the actual need.
- `curriculr-auth.php`, `curriculr-guard.php`, any `curriculr/v1` REST route — untouched.
- SPA JSON-import UI (`storage.importJson()` is currently dead code, unused by any component) — not wired up; out of scope for this feature.
- DB schema / `GSH_TP_DB_VERSION` — unchanged, same tables.
