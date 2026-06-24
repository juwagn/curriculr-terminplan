# Multi-Calendar (Stufe 2) Design Spec

**Date:** 2026-06-24  
**Feature:** n:m group→calendar mapping — one school-year doc generates multiple group-filtered ICS feeds

---

## Goal

Extend the single ICS feed per school-year to support multiple feeds, each filtered to a group (e.g. Schulleitung, Eltern). One catch-all feed (all events) plus N group-specific feeds, each served via its own WP profile.

---

## Section 1 — PHP Data Model

### `gsh_tp_curriculr_profile_map` option shape

Old (1:1): `{ "sj_2026_27": "curriculr_test" }`  
New (n:m): `{ "sj_2026_27": [{ "profileId": "curriculr_test", "group": null }, { "profileId": "curriculr_sl", "group": "Schulleitung" }] }`

`group: null` → catch-all (all events).  
`group: string` → filtered feed.

### Lazy migration in `gsh_tp_curriculr_profile_for($sj)`

- If stored value for `$sj` is a string → wrap to `[['profileId' => $val, 'group' => null]]`, call `update_option` to persist, return normalised array.
- If already an array → return as-is.
- Callers always receive `array<array{profileId: string, group: string|null}>`.

### ICS filter — `gsh_tp_curriculr_build_ics($doc, $target_group = null)`

- `$target_group === null` → include ALL events (catch-all).
- `$target_group` is string → include event if `$event['groups'] === []` OR `$target_group` is in `$event['groups']`.

This matches the agreed filter semantics: events without a group appear in every feed; events with groups appear only in matching feeds (plus the catch-all).

### `gsh_tp_curriculr_after_put($sj, $doc, $token)`

1. Call `gsh_tp_curriculr_profile_for($sj)` → array of mappings.
2. For each mapping: `$ics = gsh_tp_curriculr_build_ics($doc, $mapping['group'])`.
3. Cache under `gsh_tp_ck('gsh_tp_curriculr_ics_' . $mapping['profileId'], $pid)`.

The feed REST endpoint (`GET /feed/{sj}/{token}.ics`) already serves from the profile-specific cache key — no change needed there.

### New REST endpoint — `POST /curriculr/v1/profile-map`

**Request body:**
```json
{ "sj": "sj_2026_27", "mappings": [{ "profileId": "curriculr_test", "group": null }, { "profileId": "curriculr_sl", "group": "Schulleitung" }] }
```

**Validation:**
- `sj` required, non-empty string, `sanitize_key`.
- `mappings` required, non-empty array.
- Each entry: `profileId` required, non-empty string after `sanitize_key`; `group` string or null.

**On success:** Merge into `gsh_tp_curriculr_profile_map` option for the given `sj`, return `200 {"updated": true}`.  
**On error:** Return `400 {"code":"invalid_input","message":"..."}`.

**Auth:** `permission_callback => 'gsh_tp_curriculr_guard_perm'` — same as all other `curriculr/v1` routes.

---

## Section 2 — SPA Types + Store

### `src/lib/wp-sync-config.ts`

```ts
export type CalendarMapping = { group: string | null; profileId: string };

export type WpPlanLink = {
  schoolyearKey: string;
  wpProfileId: string;               // catch-all profile; kept for backward compat
  stage: WpStage;
  knownVersion: number;
  feedUrl?: string;
  calendarMappings?: CalendarMapping[]; // additional group-filtered feeds
};
```

`wpProfileId` is the null-group (catch-all) profile. `calendarMappings` holds extras. On push: merge both into one array: `[{profileId: link.wpProfileId, group: null}, ...link.calendarMappings]`.

### `stores/wpSync.ts` — new action

```ts
pushProfileMap(docId: string, token: string): Promise<'ok' | 'error'>
```

Builds merged mappings array, calls `postProfileMap` from `wp-sync.ts`, returns result. Sets inline state (`profileMapStatus: 'idle' | 'sending' | 'ok' | 'error'`).

### `src/lib/wp-sync.ts` — new function

```ts
export async function postProfileMap(
  config: WpSyncConfig,
  token: string,
  sj: string,
  mappings: CalendarMapping[]
): Promise<'ok' | 'error'>
```

Bearer-authed POST to `${config.baseUrl}/wp-json/curriculr/v1/profile-map`. Returns `'ok'` on 2xx, `'error'` otherwise.

---

## Section 3 — SPA UI (WordpressTab)

New section added **below** the existing "Verknüpfung" block, separated by `border-t pt-4`.

**Explanation box** (info card with muted background):

> Der Haupt-Feed (oben) enthält alle Termine. Zusätzlich kannst du separate Feeds je Gruppe einrichten — z.B. einen Feed nur für Schulleitung-Termine. Termine ohne Gruppe erscheinen in allen Gruppen-Feeds.

**Mapping table:** One row per `CalendarMapping`. Each row:
- `<select>` populated from `doc.availableGroups`
- `<Input>` for profileId
- Remove button

**"+ Gruppe hinzufügen"** button appends empty row.

**"Konfiguration senden"** button: calls `pushProfileMap`, shows inline status.

Section only renders when `doc` is present and `config.enabled`.

---

## Section 4 — Tests

### PHP

Extend `tests/curriculr/test-ics.php`:
- `gsh_tp_curriculr_build_ics` with `$target_group = null` → all events present
- `$target_group = 'Schulleitung'` → `groups=[]` events included, `groups=['Schulleitung']` included, `groups=['Eltern']` excluded

New file `tests/curriculr/test-profile-map.php`:
- `gsh_tp_curriculr_profile_for` lazy migration: string → normalised array, option updated
- POST handler: missing `sj` → 400, empty `profileId` → 400, valid body → option updated

### SPA

No new test files. `CalendarMapping` / `WpPlanLink` are pure types (no runtime logic to test). `postProfileMap` is a thin fetch wrapper — integration tested manually.

---

## Data Flow Summary

```
Admin in SPA:
  defines calendarMappings (group → WP profile ID)
  → clicks "Konfiguration senden"
  → POST /curriculr/v1/profile-map  (saves profile_map option in WP)

Later, SPA PUTs doc:
  PUT /curriculr/v1/doc/{sj}
  → after_put: reads profile_map, iterates mappings
  → builds group-filtered ICS per mapping
  → caches each under profile-specific key

IServ subscribes:
  GET /feed/{sj}/{token}.ics  (one URL per profile)
  → served from cache
```

---

## Constraints (not to touch)

- `curriculr-auth.php` — no changes
- `wp-stage.ts` — no changes
- `wp_curriculr_doc_revisions` schema — no changes
- `src/lib/ics-export.ts` — no changes (SPA export is separate)
