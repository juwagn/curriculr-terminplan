# Multi-Calendar (Stufe 2) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend the WP plugin and SPA so one school-year doc generates multiple group-filtered ICS feeds (one catch-all + one per group), and the SPA UI lets admins configure the group→profile mapping and push it to WP.

**Architecture:** Two repos touch: `curriculr-terminplan/plugin/` (PHP, Tasks 1-3) and `curriculr-planner/src/` (TypeScript, Tasks 4-5). PHP changes are backward-compatible (lazy migration, optional param). SPA changes are additive (new optional field, new UI section).

**Tech Stack:** WordPress REST API (PHP, no Composer), React + TypeScript + Zustand + shadcn/ui, Tailwind v4.

## Global Constraints

- `curriculr-auth.php` — do NOT touch, no refactor
- `wp-stage.ts` — do NOT touch, only import and use
- `wp_curriculr_doc_revisions` DB table — append-only, do NOT change schema
- `src/lib/ics-export.ts` — do NOT touch (SPA client-side export is separate)
- All CSS lives exclusively in `plugin/assets/css/gsh-terminplan.css` — never in PHP strings or heredocs
- Plugin version bump target: `4.22.0` (minor: new feature)
- TypeScript strict mode — no `any`
- German UI copy in WP admin and SPA (identifiers/comments English)
- WordPress Coding Standards for PHP style
- shadcn/ui primitives from `src/components/ui/` — no custom UI primitives
- Tailwind color tokens via `var(--color-*)` — never hardcode hex in components

---

### Task 1: ICS group filter in `gsh_tp_curriculr_build_ics`

**Files:**
- Modify: `curriculr-terminplan/plugin/curriculr-data-layer.php` (line 109: `gsh_tp_curriculr_build_ics`)
- Modify: `curriculr-terminplan/tests/curriculr/test-ics.php`

**Interfaces:**
- Produces: `gsh_tp_curriculr_build_ics( array $doc, string|null $target_group = null ): string` — used in Tasks 2 and 3.

---

- [ ] **Step 1: Extend the test fixture**

Add two more events to `tests/curriculr/fixtures/sample-doc.json` so we can test group filtering. Replace the whole file:

```json
{
  "meta": { "name": "Terminplan 2026/27" },
  "categories": [
    { "id": "konferenz",   "label": "Konferenz"   },
    { "id": "elternabend", "label": "Elternabend" }
  ],
  "events": [
    {
      "id": "ev1",
      "title": "Gesamtkonferenz",
      "allDay": true,
      "start": "2026-09-10",
      "end": "2026-09-10",
      "location": "Aula",
      "notes": "Tagesordnung folgt",
      "groups": ["Kollegium"],
      "categoryId": "konferenz"
    },
    {
      "id": "ev2",
      "title": "Elternsprechtag",
      "allDay": false,
      "start": "2026-11-12",
      "end": "2026-11-12",
      "startTime": "16:00",
      "endTime": "19:00",
      "location": "",
      "notes": "",
      "groups": [],
      "categoryId": "elternabend"
    },
    {
      "id": "ev3",
      "title": "SL-Runde",
      "allDay": true,
      "start": "2026-10-05",
      "end": "2026-10-05",
      "location": "",
      "notes": "",
      "groups": ["Schulleitung"],
      "categoryId": "konferenz"
    },
    {
      "id": "ev4",
      "title": "Elternabend Kl. 5",
      "allDay": true,
      "start": "2026-10-12",
      "end": "2026-10-12",
      "location": "",
      "notes": "",
      "groups": ["Eltern"],
      "categoryId": "elternabend"
    }
  ]
}
```

- [ ] **Step 2: Write the failing tests in `test-ics.php`**

Append these assertions at the end of `tests/curriculr/test-ics.php` (before `gsh_test_done()`):

```php
// ---------- Group filter tests ----------
$ics_all = gsh_tp_curriculr_build_ics( $doc, null );
gsh_assert_contains( $ics_all, 'UID:ev1@curriculr-planner', 'null filter: ev1 (Kollegium) present' );
gsh_assert_contains( $ics_all, 'UID:ev2@curriculr-planner', 'null filter: ev2 (no group) present' );
gsh_assert_contains( $ics_all, 'UID:ev3@curriculr-planner', 'null filter: ev3 (Schulleitung) present' );
gsh_assert_contains( $ics_all, 'UID:ev4@curriculr-planner', 'null filter: ev4 (Eltern) present' );

$ics_sl = gsh_tp_curriculr_build_ics( $doc, 'Schulleitung' );
gsh_assert_contains( $ics_sl, 'UID:ev2@curriculr-planner', 'Schulleitung filter: ev2 (no group) included' );
gsh_assert_contains( $ics_sl, 'UID:ev3@curriculr-planner', 'Schulleitung filter: ev3 (Schulleitung) included' );
// ev1 has groups=['Kollegium'], ev4 has groups=['Eltern'] — both must be absent
$has_ev1 = strpos( $ics_sl, 'UID:ev1@curriculr-planner' ) !== false;
$has_ev4 = strpos( $ics_sl, 'UID:ev4@curriculr-planner' ) !== false;
gsh_assert_true( ! $has_ev1, 'Schulleitung filter: ev1 (Kollegium) excluded' );
gsh_assert_true( ! $has_ev4, 'Schulleitung filter: ev4 (Eltern) excluded' );
```

- [ ] **Step 3: Run tests — verify they FAIL**

```bash
php tests/curriculr/test-ics.php
```

Expected: `FAIL: Schulleitung filter: ev3 (Schulleitung) included` (and others) because `build_ics` ignores the second param.

- [ ] **Step 4: Implement the group filter in `gsh_tp_curriculr_build_ics`**

In `plugin/curriculr-data-layer.php`, change the function signature and add filtering. Replace the entire `gsh_tp_curriculr_build_ics` function (currently lines 109–161) with:

```php
function gsh_tp_curriculr_build_ics( $doc, $target_group = null ) {
    $name        = isset( $doc['meta']['name'] ) ? $doc['meta']['name'] : 'Schulterminplan';
    $cats_by_id  = array();
    if ( ! empty( $doc['categories'] ) ) {
        foreach ( $doc['categories'] as $c ) {
            $cats_by_id[ $c['id'] ] = $c['label'];
        }
    }
    $lines = array(
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Curriculr Planner//DE',
        'CALSCALE:GREGORIAN',
        'X-WR-CALNAME:' . gsh_tp_curriculr_ics_escape( $name ),
        'X-WR-TIMEZONE:Europe/Berlin',
    );
    if ( ! empty( $doc['events'] ) ) {
        foreach ( $doc['events'] as $e ) {
            if ( null !== $target_group ) {
                $groups = ( isset( $e['groups'] ) && is_array( $e['groups'] ) ) ? $e['groups'] : array();
                if ( ! empty( $groups ) && ! in_array( $target_group, $groups, true ) ) {
                    continue;
                }
            }
            $lines = array_merge( $lines, gsh_tp_curriculr_build_event( $e, $cats_by_id ) );
        }
    }

    // Schulferien und gesetzliche Feiertage aus dem Schuljahr als VEVENT-Einträge.
    // Ohne diese Einträge fehlen Ferienzeiträume im ICS-Feed und werden in der
    // WP-Anzeige nicht als graue Ferien-Zeilen dargestellt.
    // Holidays have no groups — always included regardless of target_group.
    if ( ! empty( $doc['schoolyear']['holidays'] ) && is_array( $doc['schoolyear']['holidays'] ) ) {
        foreach ( $doc['schoolyear']['holidays'] as $h ) {
            if ( empty( $h['start'] ) || empty( $h['end'] ) || empty( $h['label'] ) ) {
                continue;
            }
            if ( ! gsh_tp_curriculr_is_iso_date( $h['start'] ) || ! gsh_tp_curriculr_is_iso_date( $h['end'] ) ) {
                continue;
            }
            $uid   = 'holiday-' . ( isset( $h['id'] ) ? $h['id'] : md5( $h['start'] . $h['label'] ) ) . '@curriculr-planner';
            // Holiday.end ist inklusiv (wie PlanEvent.end) → DTEND exklusiv = +1 Tag
            $dtend = ( new DateTime( $h['end'] ) )->modify( '+1 day' )->format( 'Ymd' );
            $lines = array_merge( $lines, array(
                'BEGIN:VEVENT',
                'UID:' . gsh_tp_curriculr_ics_escape( $uid ),
                'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ),
                'SUMMARY:' . gsh_tp_curriculr_ics_escape( $h['label'] ),
                'DTSTART;VALUE=DATE:' . gsh_tp_curriculr_ics_fmt_date( $h['start'] ),
                'DTEND;VALUE=DATE:' . $dtend,
                'CATEGORIES:feiertage',
                'END:VEVENT',
            ) );
        }
    }

    $lines[]  = 'END:VCALENDAR';
    $folded   = array_map( 'gsh_tp_curriculr_ics_fold', $lines );
    return implode( "\r\n", $folded ) . "\r\n";
}
```

**Filter logic:** `$target_group === null` → include all events. Otherwise include event when its `groups` array is empty (no group → universal) OR `$target_group` is in `groups`. Holidays have no groups and are always included.

- [ ] **Step 5: Run tests — verify they PASS**

```bash
php tests/curriculr/test-ics.php
```

Expected: `ALL PASS`

- [ ] **Step 6: Syntax check**

```bash
php -l plugin/curriculr-data-layer.php
```

Expected: `No syntax errors detected in plugin/curriculr-data-layer.php`

- [ ] **Step 7: Verify existing ICS edge-case tests still pass**

```bash
php tests/curriculr/test-ics-edgecases.php
```

Expected: `ALL PASS`

- [ ] **Step 8: Commit**

```bash
git add plugin/curriculr-data-layer.php tests/curriculr/test-ics.php tests/curriculr/fixtures/sample-doc.json
git commit -m "feat: add group filter to gsh_tp_curriculr_build_ics"
```

---

### Task 2: Lazy migration + n:m `after_put` + group feed route

**Files:**
- Modify: `curriculr-terminplan/plugin/curriculr-data-layer.php`
  - Add `gsh_tp_curriculr_feed_url_group()` helper near line 418
  - Replace `gsh_tp_curriculr_profile_for()` (line 682)
  - Replace `gsh_tp_curriculr_after_put()` (line 690)
  - Add new route + handler in `gsh_tp_curriculr_register_rest()` (line 448)
- Create: `curriculr-terminplan/tests/curriculr/test-multi-calendar.php`
- Modify: `curriculr-terminplan/tests/curriculr/test-integration-stubbed.php` (append n:m blocks)

**Interfaces:**
- Consumes: `gsh_tp_curriculr_build_ics( $doc, $target_group )` from Task 1
- Produces:
  - `gsh_tp_curriculr_profile_for( string $sj ): array<array{profileId: string, group: string|null}>` — used by `after_put`
  - `gsh_tp_curriculr_feed_url_group( string $sj, string $token, string $group ): string` — used by `after_put`
  - `GET /feed/{sj}/{token}/{group}.ics` — new REST route (public, token-auth)

---

- [ ] **Step 1: Write failing tests for lazy migration in new file**

Create `tests/curriculr/test-multi-calendar.php`:

```php
<?php
define( 'GSH_TP_CURRICULR_TEST', true );
define( 'GSH_TP_VERSION', '4.22.0-test' );
define( 'GSH_TP_CACHE_VERSION', 3 );

require __DIR__ . '/assert.php';

/* ---------- WordPress stubs ---------- */
$GLOBALS['options'] = array();
function get_option( $k, $d = false ) { return $GLOBALS['options'][ $k ] ?? $d; }
function update_option( $k, $v, $a = null ) { $GLOBALS['options'][ $k ] = $v; return true; }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $k ) ); }
function gsh_tp_ck( $prefix, $pid ) { return $prefix . $pid . '_v' . GSH_TP_CACHE_VERSION; }

require __DIR__ . '/../../plugin/curriculr-data-layer.php';

/* ---------- profile_for: no mapping → empty array ---------- */
$GLOBALS['options']['gsh_tp_curriculr_profile_map'] = array();
$result = gsh_tp_curriculr_profile_for( 'sj_2026_27' );
gsh_assert_eq( $result, array(), 'profile_for: no mapping returns empty array' );

/* ---------- profile_for: new array format returned as-is ---------- */
$GLOBALS['options']['gsh_tp_curriculr_profile_map'] = array(
    'sj_2026_27' => array(
        array( 'profileId' => 'curriculr_test', 'group' => null ),
        array( 'profileId' => 'curriculr_sl',   'group' => 'Schulleitung' ),
    ),
);
$result = gsh_tp_curriculr_profile_for( 'sj_2026_27' );
gsh_assert_eq( count( $result ), 2, 'profile_for: new format returns 2 mappings' );
gsh_assert_eq( $result[0]['profileId'], 'curriculr_test', 'profile_for: new format mapping 0 profileId' );
gsh_assert_eq( $result[0]['group'], null, 'profile_for: new format mapping 0 group null' );
gsh_assert_eq( $result[1]['profileId'], 'curriculr_sl', 'profile_for: new format mapping 1 profileId' );
gsh_assert_eq( $result[1]['group'], 'Schulleitung', 'profile_for: new format mapping 1 group' );
// No update_option call on already-normalised data (option unchanged).
gsh_assert_eq(
    $GLOBALS['options']['gsh_tp_curriculr_profile_map']['sj_2026_27'],
    array(
        array( 'profileId' => 'curriculr_test', 'group' => null ),
        array( 'profileId' => 'curriculr_sl',   'group' => 'Schulleitung' ),
    ),
    'profile_for: new format not re-saved'
);

/* ---------- profile_for: old string format → lazy migration ---------- */
$GLOBALS['options']['gsh_tp_curriculr_profile_map'] = array( 'sj_2026_27' => 'p_old' );
$result = gsh_tp_curriculr_profile_for( 'sj_2026_27' );
gsh_assert_eq( $result, array( array( 'profileId' => 'p_old', 'group' => null ) ), 'profile_for: old string migrated to array' );
// update_option must have persisted the new format.
$persisted = $GLOBALS['options']['gsh_tp_curriculr_profile_map']['sj_2026_27'];
gsh_assert_eq( $persisted, array( array( 'profileId' => 'p_old', 'group' => null ) ), 'profile_for: migration persisted via update_option' );

/* ---------- profile_for: invalid value → empty array ---------- */
$GLOBALS['options']['gsh_tp_curriculr_profile_map'] = array( 'sj_2026_27' => 123 );
$result = gsh_tp_curriculr_profile_for( 'sj_2026_27' );
gsh_assert_eq( $result, array(), 'profile_for: non-string non-array value returns empty array' );

gsh_test_done();
```

- [ ] **Step 2: Run — verify FAIL**

```bash
php tests/curriculr/test-multi-calendar.php
```

Expected: failures because `profile_for` still returns a string.

- [ ] **Step 3: Implement `gsh_tp_curriculr_feed_url_group` helper**

In `plugin/curriculr-data-layer.php`, add this function immediately after `gsh_tp_curriculr_feed_url` (after line ~420):

```php
function gsh_tp_curriculr_feed_url_group( $sj, $token, $group ) {
    return rest_url( 'curriculr/v1/feed/' . rawurlencode( $sj ) . '/' . $token . '/' . rawurlencode( $group ) . '.ics' );
}
```

- [ ] **Step 4: Replace `gsh_tp_curriculr_profile_for`**

In `plugin/curriculr-data-layer.php`, replace the function at line 682 with:

```php
function gsh_tp_curriculr_profile_for( $sj ) {
    // Nur EXPLIZITE Zuordnung. Kein Rückfall auf das aktive Profil
    // (Nicht-Disruption: das Live-Profil darf nie versehentlich umgebogen werden).
    $map = get_option( 'gsh_tp_curriculr_profile_map', array() );
    $sj  = sanitize_key( $sj );
    if ( ! is_array( $map ) || ! isset( $map[ $sj ] ) ) {
        return array();
    }
    $val = $map[ $sj ];
    // Lazy migration: old format stored a plain profile-ID string.
    if ( is_string( $val ) && '' !== $val ) {
        $normalised = array( array( 'profileId' => $val, 'group' => null ) );
        $map[ $sj ] = $normalised;
        update_option( 'gsh_tp_curriculr_profile_map', $map );
        return $normalised;
    }
    return is_array( $val ) ? $val : array();
}
```

- [ ] **Step 5: Replace `gsh_tp_curriculr_after_put` for n:m iteration**

In `plugin/curriculr-data-layer.php`, replace the function at line 690 with:

```php
function gsh_tp_curriculr_after_put( $sj, $token ) {
    $mappings = gsh_tp_curriculr_profile_for( $sj );
    if ( empty( $mappings ) ) {
        return;
    }

    $row = gsh_tp_curriculr_repo_get( $sj );
    $doc = $row ? json_decode( $row['json'], true ) : null;

    $grenzen  = is_array( $doc ) ? gsh_tp_curriculr_quartal_grenzen_from_doc( $doc ) : '';
    $sj_start = '';
    if ( '' !== $grenzen && isset( $doc['schoolyear']['firstSchoolDay'] ) && gsh_tp_curriculr_is_iso_date( $doc['schoolyear']['firstSchoolDay'] ) ) {
        $sj_start = gsh_tp_curriculr_monday_of_week( $doc['schoolyear']['firstSchoolDay'] );
    }

    $profiles = gsh_tp_get_profiles();
    $changed  = false;

    foreach ( $mappings as $mapping ) {
        if ( empty( $mapping['profileId'] ) ) {
            continue;
        }
        $pid      = $mapping['profileId'];
        $group    = isset( $mapping['group'] ) && is_string( $mapping['group'] ) ? $mapping['group'] : null;
        $feed_url = ( null === $group )
            ? gsh_tp_curriculr_feed_url( $sj, $token )
            : gsh_tp_curriculr_feed_url_group( $sj, $token, $group );

        foreach ( $profiles as &$p ) {
            if ( ! isset( $p['id'] ) || $p['id'] !== $pid ) {
                continue;
            }
            if ( ! isset( $p['ical_url'] ) || $p['ical_url'] !== $feed_url ) {
                $p['ical_url'] = $feed_url;
                $changed       = true;
            }
            if ( '' !== $grenzen && ( ! isset( $p['quartal_grenzen'] ) || $p['quartal_grenzen'] !== $grenzen ) ) {
                $p['quartal_grenzen'] = $grenzen;
                $changed              = true;
            }
            if ( '' !== $sj_start && ( ! isset( $p['schuljahr_start'] ) || $p['schuljahr_start'] !== $sj_start ) ) {
                $p['schuljahr_start'] = $sj_start;
                $changed              = true;
            }
        }
        unset( $p );

        // Write group-filtered ICS directly to the profile cache.
        if ( $row && function_exists( 'gsh_tp_ck' ) && is_array( $doc ) ) {
            $pid_key = sanitize_key( $pid );
            update_option( gsh_tp_ck( 'gsh_tp_ical_', $pid_key ), gsh_tp_curriculr_build_ics( $doc, $group ), false );
            delete_transient( gsh_tp_ck( 'gsh_tp_fresh_', $pid_key ) );
        }
    }

    if ( $changed ) {
        update_option( 'gsh_tp_profiles', $profiles, true );
    }
}
```

- [ ] **Step 6: Add group feed route and handler**

In `gsh_tp_curriculr_register_rest()`, add a new `register_rest_route` call immediately after the existing `/feed/...` route (after line ~491):

```php
    register_rest_route(
        'curriculr/v1',
        '/feed/(?P<sj>[a-z0-9_\-]+)/(?P<token>[A-Za-z0-9]+)/(?P<group>[^/\.]+)\.ics',
        array(
            'methods'             => 'GET',
            'callback'            => 'gsh_tp_curriculr_rest_feed_group',
            'permission_callback' => '__return_true',
        )
    );
```

Add the handler function immediately after `gsh_tp_curriculr_rest_feed` (after line ~594):

```php
function gsh_tp_curriculr_rest_feed_group( $req ) {
    $row = gsh_tp_curriculr_repo_get( $req['sj'] );
    if ( ! $row || ! hash_equals( (string) $row['feed_token'], (string) $req['token'] ) ) {
        return new WP_REST_Response( array( 'error' => 'not_found' ), 404 );
    }
    $group = sanitize_text_field( urldecode( $req['group'] ) );
    $doc   = json_decode( $row['json'], true );
    $ics   = gsh_tp_curriculr_build_ics( $doc, $group );

    if ( ! headers_sent() ) {
        header( 'Content-Type: text/calendar; charset=utf-8' );
        header( 'Content-Disposition: inline; filename="' . sanitize_key( $req['sj'] ) . '-' . sanitize_key( $group ) . '.ics"' );
        header( 'Cache-Control: max-age=300' );
    }
    // phpcs:ignore -- raw ICS output, bewusst kein wp_die.
    echo $ics;
    exit;
}
```

- [ ] **Step 7: Run lazy-migration tests — verify PASS**

```bash
php tests/curriculr/test-multi-calendar.php
```

Expected: `ALL PASS`

- [ ] **Step 8: Write n:m `after_put` assertions**

Append the following block to the end of `tests/curriculr/test-integration-stubbed.php` (before `gsh_test_done()`):

```php
/* ---------- after_put: n:m mappings write per-profile filtered ICS cache ---------- */
$GLOBALS['options']['gsh_tp_curriculr_profile_map'] = array(
    'sj_2026_27' => array(
        array( 'profileId' => 'p_all',  'group' => null          ),
        array( 'profileId' => 'p_sl',   'group' => 'Schulleitung' ),
    ),
);
// Insert a fresh doc so repo_get returns something with events.
$doc_nm = array(
    'meta'       => array( 'name' => 'Test NM' ),
    'categories' => array(),
    'events'     => array(
        array( 'id' => 'nm1', 'title' => 'Alle', 'allDay' => true, 'start' => '2026-09-01', 'end' => '2026-09-01', 'groups' => array(), 'categoryId' => '' ),
        array( 'id' => 'nm2', 'title' => 'SL',   'allDay' => true, 'start' => '2026-09-02', 'end' => '2026-09-02', 'groups' => array( 'Schulleitung' ), 'categoryId' => '' ),
        array( 'id' => 'nm3', 'title' => 'Kol',  'allDay' => true, 'start' => '2026-09-03', 'end' => '2026-09-03', 'groups' => array( 'Kollegium' ),   'categoryId' => '' ),
    ),
    'schoolyear' => array( 'id' => 'sj_nm_test', 'firstSchoolDay' => '2026-09-01', 'holidays' => array() ),
);
$GLOBALS['wpdb']->rows['sj_nm_test'] = array(
    'schoolyear'  => 'sj_nm_test',
    'json'        => json_encode( $doc_nm ),
    'version'     => 1,
    'stage'       => 'oeffentlich',
    'feed_token'  => 'nmtoken',
    'updated_at'  => '2026-09-01 00:00:00',
);
unset( $GLOBALS['options'][ gsh_tp_ck( 'gsh_tp_ical_', 'p_all' ) ] );
unset( $GLOBALS['options'][ gsh_tp_ck( 'gsh_tp_ical_', 'p_sl' ) ] );
gsh_tp_curriculr_after_put( 'sj_nm_test', 'nmtoken' );

$ics_p_all = $GLOBALS['options'][ gsh_tp_ck( 'gsh_tp_ical_', 'p_all' ) ] ?? '';
$ics_p_sl  = $GLOBALS['options'][ gsh_tp_ck( 'gsh_tp_ical_', 'p_sl'  ) ] ?? '';

// Catch-all profile: all 3 events present.
gsh_assert_contains( $ics_p_all, 'UID:nm1@curriculr-planner', 'n:m catch-all: nm1 (no group) present' );
gsh_assert_contains( $ics_p_all, 'UID:nm2@curriculr-planner', 'n:m catch-all: nm2 (Schulleitung) present' );
gsh_assert_contains( $ics_p_all, 'UID:nm3@curriculr-planner', 'n:m catch-all: nm3 (Kollegium) present' );

// Schulleitung profile: nm1 (no group) + nm2 (Schulleitung) but not nm3 (Kollegium).
gsh_assert_contains( $ics_p_sl, 'UID:nm1@curriculr-planner', 'n:m SL filter: nm1 (no group) present' );
gsh_assert_contains( $ics_p_sl, 'UID:nm2@curriculr-planner', 'n:m SL filter: nm2 (Schulleitung) present' );
gsh_assert_true( strpos( $ics_p_sl, 'UID:nm3@curriculr-planner' ) === false, 'n:m SL filter: nm3 (Kollegium) excluded' );
```

- [ ] **Step 9: Run integration tests — verify PASS**

```bash
php tests/curriculr/test-integration-stubbed.php
```

Expected: `ALL PASS`

- [ ] **Step 10: Syntax check**

```bash
php -l plugin/curriculr-data-layer.php
```

Expected: `No syntax errors detected in plugin/curriculr-data-layer.php`

- [ ] **Step 11: Commit**

```bash
git add plugin/curriculr-data-layer.php \
        tests/curriculr/test-multi-calendar.php \
        tests/curriculr/test-integration-stubbed.php
git commit -m "feat: lazy migration + n:m after_put + group-filtered ICS feed route"
```

---

### Task 3: `POST /curriculr/v1/profile-map` REST endpoint + version bump

**Files:**
- Modify: `curriculr-terminplan/plugin/curriculr-data-layer.php` (register + handler)
- Create: `curriculr-terminplan/tests/curriculr/test-profile-map-endpoint.php`
- Modify: `curriculr-terminplan/plugin/gsh-terminplan.php` (4-place version bump)
- Modify: `curriculr-terminplan/CLAUDE.md` (update version in ZIP command comment if present)

**Interfaces:**
- Produces: `POST /curriculr/v1/profile-map` — accepts `{sj, mappings}` JSON, updates `gsh_tp_curriculr_profile_map` option, returns `{updated: true}` or 400.

---

- [ ] **Step 1: Write failing tests**

Create `tests/curriculr/test-profile-map-endpoint.php`:

```php
<?php
define( 'GSH_TP_CURRICULR_TEST', true );
define( 'GSH_TP_VERSION', '4.22.0-test' );
define( 'GSH_TP_CACHE_VERSION', 3 );
define( 'ARRAY_A', 'ARRAY_A' );

require __DIR__ . '/assert.php';

/* ---------- WordPress stubs ---------- */
$GLOBALS['options'] = array();
function get_option( $k, $d = false ) { return $GLOBALS['options'][ $k ] ?? $d; }
function update_option( $k, $v, $a = null ) { $GLOBALS['options'][ $k ] = $v; return true; }
function sanitize_key( $k )          { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $k ) ); }
function sanitize_text_field( $s )   { return trim( (string) $s ); }
function gsh_tp_ck( $prefix, $pid )  { return $prefix . $pid . '_v' . GSH_TP_CACHE_VERSION; }
function rest_url( $p )              { return 'https://wp.test/wp-json/' . $p; }
function gsh_tp_curriculr_guard_perm( $req ) { return true; }

class WP_REST_Response {
    public $data;
    public $status;
    public function __construct( $d, $s = 200 ) { $this->data = $d; $this->status = $s; }
}

class Gsh_Fake_Req implements ArrayAccess {
    public function __construct( private array $body = array(), private array $params = array() ) {}
    public function get_json_params() { return $this->body; }
    public function offsetExists( $k ): bool  { return isset( $this->params[ $k ] ); }
    public function offsetGet( $k ): mixed     { return $this->params[ $k ] ?? null; }
    public function offsetSet( $k, $v ): void  {}
    public function offsetUnset( $k ): void    {}
}

/* Minimal stubs so the data-layer loads without errors */
function gsh_tp_get_profiles()           { return array(); }
function gsh_tp_active_profile_id()      { return ''; }
function gsh_tp_curriculr_guard_current_claims() { return null; }
class Gsh_Fake_Wpdb {
    public $prefix = 'wp_';
    public function get_charset_collate() { return ''; }
    public function prepare( $q, ...$a )  { return $q; }
    public function get_row( $k, $o = null ) { return null; }
    public function get_results( $q, $o = null ) { return array(); }
    public function insert( $t, $d ) {}
    public function update( $t, $d, $w ) {}
    public function query( $q ) { return true; }
}
$GLOBALS['wpdb'] = new Gsh_Fake_Wpdb();
$GLOBALS['gsh_tp_curriculr_current_claims'] = null;

require __DIR__ . '/../../plugin/curriculr-data-layer.php';

/* ---------- POST /profile-map: missing sj → 400 ---------- */
$r = gsh_tp_curriculr_rest_profile_map_put(
    new Gsh_Fake_Req( array( 'mappings' => array( array( 'profileId' => 'p1', 'group' => null ) ) ) )
);
gsh_assert_eq( $r->status, 400, 'profile-map: missing sj → 400' );
gsh_assert_eq( $r->data['code'], 'invalid_input', 'profile-map: missing sj error code' );

/* ---------- POST /profile-map: empty sj → 400 ---------- */
$r = gsh_tp_curriculr_rest_profile_map_put(
    new Gsh_Fake_Req( array( 'sj' => '', 'mappings' => array( array( 'profileId' => 'p1', 'group' => null ) ) ) )
);
gsh_assert_eq( $r->status, 400, 'profile-map: empty sj → 400' );

/* ---------- POST /profile-map: missing mappings → 400 ---------- */
$r = gsh_tp_curriculr_rest_profile_map_put(
    new Gsh_Fake_Req( array( 'sj' => 'sj_2026_27' ) )
);
gsh_assert_eq( $r->status, 400, 'profile-map: missing mappings → 400' );

/* ---------- POST /profile-map: empty mappings array → 400 ---------- */
$r = gsh_tp_curriculr_rest_profile_map_put(
    new Gsh_Fake_Req( array( 'sj' => 'sj_2026_27', 'mappings' => array() ) )
);
gsh_assert_eq( $r->status, 400, 'profile-map: empty mappings → 400' );

/* ---------- POST /profile-map: empty profileId → 400 ---------- */
$r = gsh_tp_curriculr_rest_profile_map_put(
    new Gsh_Fake_Req( array( 'sj' => 'sj_2026_27', 'mappings' => array( array( 'profileId' => '', 'group' => null ) ) ) )
);
gsh_assert_eq( $r->status, 400, 'profile-map: empty profileId → 400' );

/* ---------- POST /profile-map: valid body → 200, option updated ---------- */
$GLOBALS['options'] = array();
$r = gsh_tp_curriculr_rest_profile_map_put(
    new Gsh_Fake_Req( array(
        'sj'       => 'sj_2026_27',
        'mappings' => array(
            array( 'profileId' => 'curriculr_test', 'group' => null           ),
            array( 'profileId' => 'curriculr_sl',   'group' => 'Schulleitung' ),
        ),
    ) )
);
gsh_assert_eq( $r->status, 200, 'profile-map: valid body → 200' );
gsh_assert_eq( $r->data['updated'], true, 'profile-map: response has updated=true' );

$saved = get_option( 'gsh_tp_curriculr_profile_map', array() );
gsh_assert_true( is_array( $saved ), 'profile-map: option is array' );
gsh_assert_eq( count( $saved['sj_2026_27'] ), 2, 'profile-map: 2 mappings saved' );
gsh_assert_eq( $saved['sj_2026_27'][0]['profileId'], 'curriculr_test', 'profile-map: mapping 0 profileId' );
gsh_assert_eq( $saved['sj_2026_27'][0]['group'],     null,             'profile-map: mapping 0 group null' );
gsh_assert_eq( $saved['sj_2026_27'][1]['profileId'], 'curriculr_sl',   'profile-map: mapping 1 profileId' );
gsh_assert_eq( $saved['sj_2026_27'][1]['group'],     'Schulleitung',   'profile-map: mapping 1 group' );

/* ---------- POST /profile-map: merges with existing other sj entries ---------- */
$GLOBALS['options']['gsh_tp_curriculr_profile_map'] = array(
    'sj_2025_26' => array( array( 'profileId' => 'old_profile', 'group' => null ) ),
);
$r = gsh_tp_curriculr_rest_profile_map_put(
    new Gsh_Fake_Req( array(
        'sj'       => 'sj_2026_27',
        'mappings' => array( array( 'profileId' => 'new_profile', 'group' => null ) ),
    ) )
);
gsh_assert_eq( $r->status, 200, 'profile-map merge: 200' );
$saved = get_option( 'gsh_tp_curriculr_profile_map', array() );
gsh_assert_true( isset( $saved['sj_2025_26'] ), 'profile-map merge: existing sj preserved' );
gsh_assert_true( isset( $saved['sj_2026_27'] ), 'profile-map merge: new sj written' );

gsh_test_done();
```

- [ ] **Step 2: Run tests — verify FAIL**

```bash
php tests/curriculr/test-profile-map-endpoint.php
```

Expected: PHP fatal error (`Call to undefined function gsh_tp_curriculr_rest_profile_map_put`).

- [ ] **Step 3: Register the route and add the handler**

In `plugin/curriculr-data-layer.php`, add to `gsh_tp_curriculr_register_rest()` (after the last existing `register_rest_route` call, before the closing `}`):

```php
    register_rest_route(
        'curriculr/v1',
        '/profile-map',
        array(
            'methods'             => 'POST',
            'callback'            => 'gsh_tp_curriculr_rest_profile_map_put',
            'permission_callback' => 'gsh_tp_curriculr_perm',
        )
    );
```

Add the handler function immediately after `gsh_tp_curriculr_rest_feed_group` (added in Task 2):

```php
/**
 * POST /curriculr/v1/profile-map
 *
 * Body: { sj: string, mappings: [{profileId: string, group: string|null}] }
 * Saves the group→profile mapping for one school year into the WP option.
 *
 * @since 4.22.0
 */
function gsh_tp_curriculr_rest_profile_map_put( $req ) {
    $body     = $req->get_json_params();
    $sj       = isset( $body['sj'] ) ? sanitize_key( $body['sj'] ) : '';
    $mappings = isset( $body['mappings'] ) ? $body['mappings'] : null;

    if ( '' === $sj ) {
        return new WP_REST_Response( array( 'code' => 'invalid_input', 'message' => 'sj required' ), 400 );
    }
    if ( ! is_array( $mappings ) || empty( $mappings ) ) {
        return new WP_REST_Response( array( 'code' => 'invalid_input', 'message' => 'mappings required, must be non-empty array' ), 400 );
    }

    $normalised = array();
    foreach ( $mappings as $m ) {
        if ( ! is_array( $m ) ) {
            return new WP_REST_Response( array( 'code' => 'invalid_input', 'message' => 'each mapping must be an object' ), 400 );
        }
        $pid = sanitize_key( $m['profileId'] ?? '' );
        if ( '' === $pid ) {
            return new WP_REST_Response( array( 'code' => 'invalid_input', 'message' => 'profileId required and must be non-empty' ), 400 );
        }
        $group        = ( isset( $m['group'] ) && is_string( $m['group'] ) && '' !== $m['group'] )
            ? sanitize_text_field( $m['group'] ) : null;
        $normalised[] = array( 'profileId' => $pid, 'group' => $group );
    }

    $map        = get_option( 'gsh_tp_curriculr_profile_map', array() );
    $map        = is_array( $map ) ? $map : array();
    $map[ $sj ] = $normalised;
    update_option( 'gsh_tp_curriculr_profile_map', $map );

    return new WP_REST_Response( array( 'updated' => true ), 200 );
}
```

- [ ] **Step 4: Run endpoint tests — verify PASS**

```bash
php tests/curriculr/test-profile-map-endpoint.php
```

Expected: `ALL PASS`

- [ ] **Step 5: Syntax check**

```bash
php -l plugin/curriculr-data-layer.php
```

Expected: `No syntax errors detected in plugin/curriculr-data-layer.php`

- [ ] **Step 6: Run all PHP tests to confirm no regressions**

```bash
php tests/curriculr/test-ics.php && \
php tests/curriculr/test-ics-edgecases.php && \
php tests/curriculr/test-multi-calendar.php && \
php tests/curriculr/test-profile-map-endpoint.php && \
php tests/curriculr/test-settings-backup.php && \
php tests/curriculr/test-integration-stubbed.php && \
php tests/curriculr/test-envelope.php && \
php tests/curriculr/test-stage.php
```

Expected: `ALL PASS` for each file.

- [ ] **Step 7: Bump plugin version to 4.22.0 in `gsh-terminplan.php` (4 places)**

**Place 1** — plugin header comment (line 6):
```php
 * Version:     4.22.0
```

**Place 2** — prepend changelog entry in header (line 10, before the existing 4.21.0 block):
```php
 * Changelog 4.22.0:
 * - [NEU] Mehrfach-Kalender: ein Schuljahr kann mehrere Gruppen-ICS-Feeds bedienen (n:m Profil-Mapping)
 * - [NEU] REST POST /curriculr/v1/profile-map — SPA kann Gruppen→Profil-Mapping direkt speichern
 * - [NEU] Lazy Migration: altes Einzel-Profil-Format wird automatisch zum neuen Array-Format migriert
```

**Place 3** — define constant (line 578):
```php
define( 'GSH_TP_VERSION',       '4.22.0' );
```

**Place 4** — prepend entry in `gsh_tp_changelog()` array (line 821, before the '4.21.0' block):
```php
        array(
            'version'  => '4.22.0',
            'entries'  => array(
                array( 'tag' => 'NEU', 'text' => 'Mehrfach-Kalender: ein Schuljahr kann mehrere Gruppen-ICS-Feeds bedienen (n:m Profil-Mapping)' ),
                array( 'tag' => 'NEU', 'text' => 'REST POST /curriculr/v1/profile-map — SPA kann Gruppen→Profil-Mapping direkt speichern' ),
                array( 'tag' => 'NEU', 'text' => 'Lazy Migration: altes Einzel-Profil-Format wird automatisch zum neuen Array-Format migriert' ),
            ),
        ),
```

- [ ] **Step 8: Syntax check gsh-terminplan.php**

```bash
php -l plugin/gsh-terminplan.php
```

Expected: `No syntax errors detected in plugin/gsh-terminplan.php`

- [ ] **Step 9: Run version test**

```bash
php tests/curriculr/test-version.php
```

Expected: `ALL PASS`

- [ ] **Step 10: Commit**

```bash
git add plugin/curriculr-data-layer.php \
        plugin/gsh-terminplan.php \
        tests/curriculr/test-profile-map-endpoint.php
git commit -m "feat: v4.22.0 — POST /profile-map endpoint; n:m multi-calendar complete"
```

---

### Task 4: SPA — `CalendarMapping` type + `postProfileMap` REST function

**Files:**
- Modify: `curriculr-planner/src/lib/wp-sync-config.ts`
- Modify: `curriculr-planner/src/lib/wp-sync.ts`

**Interfaces:**
- Produces:
  - `CalendarMapping = { group: string | null; profileId: string }` (exported from `wp-sync-config.ts`)
  - `postProfileMap(cfg, token, sj, mappings, fetchImpl?): Promise<'ok'|'error'>` (exported from `wp-sync.ts`)

---

- [ ] **Step 1: Add `CalendarMapping` type and extend `WpPlanLink` in `wp-sync-config.ts`**

Replace the current `WpPlanLink` interface and update `parseLink` in `src/lib/wp-sync-config.ts`.

Full updated file content:

```typescript
import type { UUID } from '@/types';
import type { WpStage } from './wp-stage';

const KEY = 'curriculr-planner:wp-sync';

const VALID_STAGES = new Set<string>(['entwurf', 'genehmigt', 'oeffentlich']);

export interface CalendarMapping {
  group: string | null;
  profileId: string;
}

export interface WpPlanLink {
  schoolyearKey: string;
  wpProfileId: string;
  stage: WpStage;
  knownVersion: number;
  feedUrl?: string;
  calendarMappings?: CalendarMapping[];
}

export interface WpSyncConfig {
  enabled: boolean;
  baseUrl: string;
  links: Record<UUID, WpPlanLink>;
}

export const EMPTY_CONFIG: WpSyncConfig = {
  enabled: false, baseUrl: '', links: {},
};

function parseCalendarMappings(raw: unknown): CalendarMapping[] | undefined {
  if (!Array.isArray(raw)) return undefined;
  const parsed = raw
    .filter((m): m is Record<string, unknown> => m !== null && typeof m === 'object')
    .map((m): CalendarMapping | null => {
      const pid = typeof m.profileId === 'string' ? m.profileId : '';
      if (!pid) return null;
      const group = typeof m.group === 'string' ? m.group : null;
      return { profileId: pid, group };
    })
    .filter((m): m is CalendarMapping => m !== null);
  return parsed.length > 0 ? parsed : undefined;
}

function parseLink(v: unknown): WpPlanLink | null {
  if (!v || typeof v !== 'object') return null;
  const l = v as Record<string, unknown>;
  const stage = typeof l.stage === 'string' && VALID_STAGES.has(l.stage) ? (l.stage as WpStage) : 'entwurf';
  const feedUrl = typeof l.feedUrl === 'string' && /^https:\/\//i.test(l.feedUrl) ? l.feedUrl : undefined;
  const calendarMappings = parseCalendarMappings(l.calendarMappings);
  return {
    schoolyearKey: typeof l.schoolyearKey === 'string' ? l.schoolyearKey : '',
    wpProfileId:   typeof l.wpProfileId   === 'string' ? l.wpProfileId   : '',
    stage,
    knownVersion:  typeof l.knownVersion  === 'number' ? l.knownVersion  : 0,
    ...(feedUrl          ? { feedUrl }          : {}),
    ...(calendarMappings ? { calendarMappings } : {}),
  };
}

export function loadWpConfig(): WpSyncConfig {
  try {
    const raw = localStorage.getItem(KEY);
    if (!raw) return structuredClone(EMPTY_CONFIG);
    const p = JSON.parse(raw);
    const rawLinks = p.links && typeof p.links === 'object' ? p.links : {};
    const links: Record<UUID, WpPlanLink> = {};
    for (const [k, v] of Object.entries(rawLinks)) {
      const parsed = parseLink(v);
      if (parsed) links[k as UUID] = parsed;
    }
    return {
      enabled: !!p.enabled,
      baseUrl: typeof p.baseUrl === 'string' ? p.baseUrl : '',
      links,
    };
  } catch {
    return structuredClone(EMPTY_CONFIG);
  }
}

export function saveWpConfig(cfg: WpSyncConfig): void {
  localStorage.setItem(KEY, JSON.stringify(cfg));
}
```

- [ ] **Step 2: Add `postProfileMap` to `wp-sync.ts`**

Add the following import addition and new function to `src/lib/wp-sync.ts`.

At the top of the file, extend the existing import from `wp-sync-config`:
```typescript
import type { WpSyncConfig, CalendarMapping } from './wp-sync-config';
```

(Replace the existing `import type { WpSyncConfig } from './wp-sync-config';`)

Append the new function at the end of the file:

```typescript
export async function postProfileMap(
  cfg: WpSyncConfig,
  token: string,
  sj: string,
  mappings: CalendarMapping[],
  fetchImpl: FetchLike = fetch,
): Promise<'ok' | 'error'> {
  try {
    const res = await fetchImpl(`${base(cfg)}/profile-map`, {
      method: 'POST',
      headers: { Authorization: bearerHeader(token), 'Content-Type': 'application/json' },
      body: JSON.stringify({ sj, mappings }),
    });
    return res.ok ? 'ok' : 'error';
  } catch {
    return 'error';
  }
}
```

- [ ] **Step 3: TypeScript type-check and lint**

```bash
cd curriculr-planner && npm run typecheck && npm run lint
```

Expected: no errors, no warnings.

- [ ] **Step 4: Commit**

```bash
cd curriculr-planner && git add src/lib/wp-sync-config.ts src/lib/wp-sync.ts
git commit -m "feat: add CalendarMapping type and postProfileMap REST function"
```

---

### Task 5: SPA — `WordpressTab` Gruppen-Kalender UI section

**Files:**
- Modify: `curriculr-planner/src/components/settings/WordpressTab.tsx`

**Interfaces:**
- Consumes:
  - `CalendarMapping` from `@/lib/wp-sync-config` (Task 4)
  - `postProfileMap` from `@/lib/wp-sync` (Task 4)
  - `doc.availableGroups: string[]` from `@/types` (already present)
  - `link.wpProfileId`, `link.calendarMappings`, `link.schoolyearKey` from `WpPlanLink` (Task 4)
  - `token` from `useAuthStore` (already used in component)
  - `config` from `useWpSyncStore` (already used in component)

---

- [ ] **Step 1: Add imports to `WordpressTab.tsx`**

Add to the existing import block at the top of `src/components/settings/WordpressTab.tsx`:

```typescript
import { postProfileMap } from '@/lib/wp-sync';
import type { CalendarMapping } from '@/lib/wp-sync-config';
```

- [ ] **Step 2: Add local state and helper functions inside `WordpressTab`**

Inside the `WordpressTab` function, after the existing state declarations (after line 22 `const [testState, setTestState]...`), add:

```typescript
const [pmStatus, setPmStatus] = useState<'idle' | 'sending' | 'ok' | 'error'>('idle');

const calMappings: CalendarMapping[] = link?.calendarMappings ?? [];

function addCalMapping() {
  patchLink({ calendarMappings: [...calMappings, { group: null, profileId: '' }] });
}

function removeCalMapping(i: number) {
  patchLink({ calendarMappings: calMappings.filter((_, idx) => idx !== i) });
}

function updateCalMapping(i: number, patch: Partial<CalendarMapping>) {
  patchLink({ calendarMappings: calMappings.map((m, idx) => idx === i ? { ...m, ...patch } : m) });
}

async function onSendProfileMap() {
  if (!token || !docId || !link) return;
  const mappings = [
    { group: null, profileId: link.wpProfileId },
    ...calMappings,
  ].filter(m => Boolean(m.profileId));
  setPmStatus('sending');
  const result = await postProfileMap(config, token, link.schoolyearKey, mappings);
  setPmStatus(result);
}
```

- [ ] **Step 3: Add the UI section to the JSX**

Inside the `return (...)`, immediately after the closing `</div>` of the `{doc && (` "Verknüpfung" block (after line 152), add:

```tsx
      {doc && link && config.enabled && (
        <div className="space-y-3 border-t pt-4">
          <p className="text-[13px] font-semibold">Gruppen-Kalender</p>
          <div className="rounded-md bg-[var(--color-marine-50,#f0f5fa)] border border-[var(--color-marine-200,#c8d8e8)] p-3 space-y-1">
            <p className="text-[12px] text-[var(--color-ink-600)]">
              Der Haupt-Feed (oben) enthält alle Termine. Zusätzlich kannst du separate Feeds je Gruppe einrichten — z.B. einen Feed nur für Schulleitung-Termine.
            </p>
            <p className="text-[12px] text-[var(--color-ink-600)]">
              Termine <strong>ohne Gruppe</strong> erscheinen in allen Gruppen-Feeds.
            </p>
          </div>
          {calMappings.map((m, i) => (
            <div key={i} className="flex gap-2 items-center">
              <select
                className="border border-input rounded-md px-2 py-1 text-[13px] bg-background"
                value={m.group ?? ''}
                onChange={(e) => updateCalMapping(i, { group: e.target.value || null })}
              >
                <option value="">Gruppe wählen…</option>
                {doc.availableGroups.map((g) => (
                  <option key={g} value={g}>{g}</option>
                ))}
              </select>
              <Input
                className="flex-1"
                value={m.profileId}
                placeholder="WP-Profil-ID"
                onChange={(e) => updateCalMapping(i, { profileId: e.target.value })}
              />
              <Button variant="ghost" size="sm" onClick={() => removeCalMapping(i)}>×</Button>
            </div>
          ))}
          <Button variant="outline" size="sm" onClick={addCalMapping}>
            + Gruppe hinzufügen
          </Button>
          <div className="flex items-center gap-3 pt-1">
            <Button onClick={onSendProfileMap} disabled={pmStatus === 'sending'}>
              {pmStatus === 'sending' ? 'Sende…' : 'Konfiguration senden'}
            </Button>
            {pmStatus === 'ok'    && <p className="text-[12px] text-green-700">✓ Gespeichert</p>}
            {pmStatus === 'error' && <p className="text-[12px] text-red-600">Fehler beim Senden</p>}
          </div>
        </div>
      )}
```

- [ ] **Step 4: TypeScript type-check and lint**

```bash
cd curriculr-planner && npm run typecheck && npm run lint
```

Expected: no errors, no warnings.

- [ ] **Step 5: Commit**

```bash
cd curriculr-planner && git add src/components/settings/WordpressTab.tsx
git commit -m "feat: add Gruppen-Kalender UI section to WordpressTab"
```

---

## Final Checklist

After all 5 tasks complete, verify:

- [ ] All PHP tests pass: `php tests/curriculr/test-ics.php && php tests/curriculr/test-multi-calendar.php && php tests/curriculr/test-profile-map-endpoint.php && php tests/curriculr/test-integration-stubbed.php`
- [ ] All PHP files lint clean: `php -l plugin/curriculr-data-layer.php && php -l plugin/gsh-terminplan.php`
- [ ] SPA type-checks and lints: `cd curriculr-planner && npm run typecheck && npm run lint`
- [ ] Plugin version is `4.22.0` in all 4 places in `gsh-terminplan.php`
- [ ] Spec constraints respected: auth, stage, revisions table, ICS export untouched
