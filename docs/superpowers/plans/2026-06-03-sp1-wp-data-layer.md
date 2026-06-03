# SP1 — WP Data Layer Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a "Curriculr Data Layer" to the `curriculr-terminplan` WordPress plugin so the Planner can store its document via REST, WordPress publishes a token-protected ICS feed, and the existing quarterly/print renderer stays untouched (feed-reuse).

**Architecture:** A new procedural file `plugin/curriculr-data-layer.php` (functions prefixed `gsh_tp_curriculr_*`), loaded via one `require_once` from `gsh-terminplan.php`. Pure functions (ICS build, version decision, envelope validation) are TDD'd with a dependency-free plain-PHP assert harness. WP-bound functions (`$wpdb`, REST, CORS, feed-reuse wiring) are verified with `php -l` + `curl`. No Composer, no PHPUnit, no classes — matching plugin conventions (AGENTS.md).

**Tech Stack:** PHP 8.0+, WordPress REST API, `$wpdb`/`dbDelta`, WP Application Passwords, plain-PHP test scripts run with `php`.

**Reference spec:** `../curriculr-planner/docs/superpowers/specs/2026-06-03-curriculr-sync-architecture-design.md`

---

## File Structure

- **Create** `plugin/curriculr-data-layer.php` — all `gsh_tp_curriculr_*` functions + (final task) hook registrations.
- **Modify** `plugin/gsh-terminplan.php` — one `require_once` after the defines block (~line 512); version bump 4.4.0 → 4.5.0 in the four required places.
- **Create** `tests/curriculr/assert.php` — dependency-free assert helpers.
- **Create** `tests/curriculr/test-ics.php`, `test-version.php`, `test-envelope.php` — pure-function tests.
- **Create** `tests/curriculr/fixtures/sample-doc.json` — a Planner document fixture (tests + curl).

**Convention reminders (apply throughout):** identifiers English, comments German; CSS only in `gsh-terminplan.css` (this task adds none); after every edit to a PHP file run `php -l`; the feed need not be byte-identical to the Planner's `buildIcs`, only valid/parseable ICS.

---

## Task 1: Pure ICS builder (TDD)

**Files:**
- Create: `tests/curriculr/assert.php`
- Create: `tests/curriculr/fixtures/sample-doc.json`
- Create: `tests/curriculr/test-ics.php`
- Create: `plugin/curriculr-data-layer.php`

- [ ] **Step 1: Create the assert harness**

`tests/curriculr/assert.php`:
```php
<?php
// Dependency-free Test-Helfer (kein Composer/PHPUnit nötig).
$GLOBALS['gsh_test_fail'] = 0;

function gsh_assert_eq( $actual, $expected, $msg ) {
    if ( $actual !== $expected ) {
        $GLOBALS['gsh_test_fail']++;
        fwrite( STDERR, "FAIL: $msg\n  expected: " . var_export( $expected, true ) . "\n  actual:   " . var_export( $actual, true ) . "\n" );
    } else {
        echo "PASS: $msg\n";
    }
}

function gsh_assert_true( $cond, $msg ) {
    gsh_assert_eq( $cond === true, true, $msg );
}

function gsh_assert_contains( $haystack, $needle, $msg ) {
    gsh_assert_eq( strpos( $haystack, $needle ) !== false, true, $msg . " (needle: $needle)" );
}

function gsh_test_done() {
    if ( $GLOBALS['gsh_test_fail'] > 0 ) {
        fwrite( STDERR, $GLOBALS['gsh_test_fail'] . " failure(s)\n" );
        exit( 1 );
    }
    echo "ALL PASS\n";
}
```

- [ ] **Step 2: Create the document fixture**

`tests/curriculr/fixtures/sample-doc.json`:
```json
{
  "meta": { "name": "Terminplan 2026/27" },
  "categories": [
    { "id": "konferenz", "label": "Konferenz" },
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
    }
  ]
}
```

- [ ] **Step 3: Write the failing test**

`tests/curriculr/test-ics.php`:
```php
<?php
require __DIR__ . '/assert.php';
require __DIR__ . '/../../plugin/curriculr-data-layer.php';

$doc = json_decode( file_get_contents( __DIR__ . '/fixtures/sample-doc.json' ), true );
$ics = gsh_tp_curriculr_build_ics( $doc );

gsh_assert_contains( $ics, "BEGIN:VCALENDAR\r\n", 'calendar header present' );
gsh_assert_contains( $ics, "X-WR-CALNAME:Terminplan 2026/27", 'calendar name from meta.name' );
gsh_assert_contains( $ics, 'UID:ev1@curriculr-planner', 'event 1 UID' );
gsh_assert_contains( $ics, 'SUMMARY:Gesamtkonferenz', 'event 1 summary' );
gsh_assert_contains( $ics, 'DTSTART;VALUE=DATE:20260910', 'all-day DTSTART' );
gsh_assert_contains( $ics, 'DTEND;VALUE=DATE:20260911', 'all-day DTEND is end-exclusive (+1 day)' );
gsh_assert_contains( $ics, 'CATEGORIES:Konferenz', 'category label resolved from categoryId' );
gsh_assert_contains( $ics, 'DTSTART:20261112T160000', 'timed DTSTART uses startTime' );
gsh_assert_contains( $ics, 'DTEND:20261112T190000', 'timed DTEND uses endTime' );
gsh_assert_contains( $ics, "END:VCALENDAR\r\n", 'calendar footer present' );
gsh_test_done();
```

- [ ] **Step 4: Run the test, verify it fails**

Run: `php tests/curriculr/test-ics.php`
Expected: PHP fatal error `Call to undefined function gsh_tp_curriculr_build_ics()` (function not yet defined).

- [ ] **Step 5: Implement the ICS builder**

Create `plugin/curriculr-data-layer.php` with exactly this content:
```php
<?php
/**
 * Curriculr Data Layer
 *
 * Speichert das Planner-Dokument (REST), liefert einen Token-geschützten
 * ICS-Feed und verdrahtet ihn per Feed-Reuse mit dem bestehenden Renderer.
 * Prozedural, keine Klassen — passend zur Plugin-Konvention (AGENTS.md).
 *
 * Pure Funktionen (build_ics, version_decision, validate_envelope) sind ohne
 * WordPress lauffähig und werden mit tests/curriculr/*.php geprüft.
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'GSH_TP_CURRICULR_TEST' ) ) {
    // Direktaufruf im Browser verhindern, Tests (CLI) aber zulassen.
    if ( PHP_SAPI !== 'cli' ) {
        exit;
    }
}

/* ---------- Pure: ICS-Erzeugung (spiegelt ics-export.ts buildIcs) ---------- */

function gsh_tp_curriculr_ics_escape( $s ) {
    $s = str_replace( '\\', '\\\\', (string) $s );
    $s = str_replace( ',', '\\,', $s );
    $s = str_replace( ';', '\\;', $s );
    $s = str_replace( "\n", '\\n', $s );
    return $s;
}

function gsh_tp_curriculr_ics_fmt_date( $iso ) {
    // 'YYYY-MM-DD' -> 'YYYYMMDD'
    return str_replace( '-', '', (string) $iso );
}

function gsh_tp_curriculr_ics_fmt_datetime( $iso, $time ) {
    return gsh_tp_curriculr_ics_fmt_date( $iso ) . 'T' . str_replace( ':', '', (string) $time ) . '00';
}

function gsh_tp_curriculr_ics_fold( $line ) {
    // Zeilenfaltung nach RFC 5545 (Oktett-basiert; Folge-Zeilen mit Space-Prefix).
    if ( strlen( $line ) <= 75 ) {
        return $line;
    }
    $out = array();
    $len = strlen( $line );
    for ( $i = 0; $i < $len; $i += 73 ) {
        $out[] = ( $i === 0 ? '' : ' ' ) . substr( $line, $i, 73 );
    }
    return implode( "\r\n", $out );
}

function gsh_tp_curriculr_build_event( $e, $cats_by_id ) {
    $lines   = array( 'BEGIN:VEVENT' );
    $lines[] = 'UID:' . $e['id'] . '@curriculr-planner';
    $lines[] = 'DTSTAMP:' . gmdate( 'Ymd\THis\Z' );
    $lines[] = 'SUMMARY:' . gsh_tp_curriculr_ics_escape( $e['title'] );

    if ( ! empty( $e['allDay'] ) ) {
        $end_exclusive = date( 'Ymd', strtotime( $e['end'] . ' +1 day' ) );
        $lines[]       = 'DTSTART;VALUE=DATE:' . gsh_tp_curriculr_ics_fmt_date( $e['start'] );
        $lines[]       = 'DTEND;VALUE=DATE:' . $end_exclusive;
    } else {
        $st      = ( isset( $e['startTime'] ) && $e['startTime'] !== null ) ? $e['startTime'] : '00:00';
        $et      = ( isset( $e['endTime'] ) && $e['endTime'] !== null ) ? $e['endTime'] : '23:59';
        $lines[] = 'DTSTART:' . gsh_tp_curriculr_ics_fmt_datetime( $e['start'], $st );
        $lines[] = 'DTEND:' . gsh_tp_curriculr_ics_fmt_datetime( $e['end'], $et );
    }

    if ( ! empty( $e['location'] ) ) {
        $lines[] = 'LOCATION:' . gsh_tp_curriculr_ics_escape( $e['location'] );
    }

    $desc_parts = array();
    if ( ! empty( $e['notes'] ) ) {
        $desc_parts[] = $e['notes'];
    }
    if ( ! empty( $e['groups'] ) ) {
        $desc_parts[] = 'Gruppen: ' . implode( ', ', $e['groups'] );
    }
    if ( $desc_parts ) {
        $lines[] = 'DESCRIPTION:' . gsh_tp_curriculr_ics_escape( implode( "\n", $desc_parts ) );
    }

    if ( isset( $e['categoryId'] ) && isset( $cats_by_id[ $e['categoryId'] ] ) ) {
        $lines[] = 'CATEGORIES:' . gsh_tp_curriculr_ics_escape( $cats_by_id[ $e['categoryId'] ] );
    }

    $lines[] = 'END:VEVENT';
    return $lines;
}

function gsh_tp_curriculr_build_ics( $doc ) {
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
            $lines = array_merge( $lines, gsh_tp_curriculr_build_event( $e, $cats_by_id ) );
        }
    }
    $lines[]  = 'END:VCALENDAR';
    $folded   = array_map( 'gsh_tp_curriculr_ics_fold', $lines );
    return implode( "\r\n", $folded ) . "\r\n";
}
```

- [ ] **Step 6: Run the test, verify it passes**

Run: `php tests/curriculr/test-ics.php`
Expected: ten `PASS:` lines then `ALL PASS`.

- [ ] **Step 7: Lint the new file**

Run: `php -l plugin/curriculr-data-layer.php`
Expected: `No syntax errors detected in plugin/curriculr-data-layer.php`.

- [ ] **Step 8: Commit**

```bash
git add tests/curriculr/assert.php tests/curriculr/fixtures/sample-doc.json tests/curriculr/test-ics.php plugin/curriculr-data-layer.php
git commit -m "feat(curriculr): pure ICS feed builder + plain-PHP test harness"
```

---

## Task 2: Version decision (TDD)

**Files:**
- Create: `tests/curriculr/test-version.php`
- Modify: `plugin/curriculr-data-layer.php` (append function)

- [ ] **Step 1: Write the failing test**

`tests/curriculr/test-version.php`:
```php
<?php
require __DIR__ . '/assert.php';
require __DIR__ . '/../../plugin/curriculr-data-layer.php';

gsh_assert_eq( gsh_tp_curriculr_version_decision( 0, 0 ), 'ok', 'first write: base 0 vs current 0 -> ok' );
gsh_assert_eq( gsh_tp_curriculr_version_decision( 5, 5 ), 'ok', 'matching versions -> ok' );
gsh_assert_eq( gsh_tp_curriculr_version_decision( 6, 5 ), 'conflict', 'server newer -> conflict' );
gsh_assert_eq( gsh_tp_curriculr_version_decision( 5, 7 ), 'conflict', 'client ahead (impossible) -> conflict' );
gsh_test_done();
```

- [ ] **Step 2: Run the test, verify it fails**

Run: `php tests/curriculr/test-version.php`
Expected: fatal `Call to undefined function gsh_tp_curriculr_version_decision()`.

- [ ] **Step 3: Append the implementation**

Add to the end of `plugin/curriculr-data-layer.php`:
```php
/* ---------- Pure: Versions-Entscheidung (Konflikt-Schutz, Spec §5) ---------- */

function gsh_tp_curriculr_version_decision( $current, $base ) {
    // $current = gespeicherte Server-Version (0 = noch keine Zeile).
    // $base    = baseVersion des Clients. Nur exakte Übereinstimmung darf schreiben.
    return ( (int) $base === (int) $current ) ? 'ok' : 'conflict';
}
```

- [ ] **Step 4: Run the test, verify it passes**

Run: `php tests/curriculr/test-version.php`
Expected: four `PASS:` lines then `ALL PASS`.

- [ ] **Step 5: Lint + commit**

```bash
php -l plugin/curriculr-data-layer.php
git add tests/curriculr/test-version.php plugin/curriculr-data-layer.php
git commit -m "feat(curriculr): version decision for optimistic concurrency"
```

---

## Task 3: Envelope validation (TDD)

**Files:**
- Create: `tests/curriculr/test-envelope.php`
- Modify: `plugin/curriculr-data-layer.php` (append function)

- [ ] **Step 1: Write the failing test**

`tests/curriculr/test-envelope.php`:
```php
<?php
require __DIR__ . '/assert.php';
require __DIR__ . '/../../plugin/curriculr-data-layer.php';

$ok = gsh_tp_curriculr_validate_envelope( array(
    'doc'         => array( 'events' => array() ),
    'baseVersion' => 0,
) );
gsh_assert_eq( $ok['valid'], true, 'valid envelope passes' );

$no_base = gsh_tp_curriculr_validate_envelope( array( 'doc' => array( 'events' => array() ) ) );
gsh_assert_eq( $no_base['valid'], false, 'missing baseVersion fails' );
gsh_assert_contains( implode( ',', $no_base['errors'] ), 'baseVersion_missing', 'reports baseVersion_missing' );

$no_doc = gsh_tp_curriculr_validate_envelope( array( 'baseVersion' => 1 ) );
gsh_assert_eq( $no_doc['valid'], false, 'missing doc fails' );

$no_events = gsh_tp_curriculr_validate_envelope( array( 'doc' => array( 'meta' => array() ), 'baseVersion' => 1 ) );
gsh_assert_eq( $no_events['valid'], false, 'doc without events array fails' );

$not_object = gsh_tp_curriculr_validate_envelope( 'nope' );
gsh_assert_eq( $not_object['valid'], false, 'non-array body fails' );
gsh_test_done();
```

- [ ] **Step 2: Run the test, verify it fails**

Run: `php tests/curriculr/test-envelope.php`
Expected: fatal `Call to undefined function gsh_tp_curriculr_validate_envelope()`.

- [ ] **Step 3: Append the implementation**

Add to the end of `plugin/curriculr-data-layer.php`:
```php
/* ---------- Pure: Envelope-Validierung des PUT-Bodys (Spec §6) ---------- */

function gsh_tp_curriculr_validate_envelope( $body ) {
    if ( ! is_array( $body ) ) {
        return array( 'valid' => false, 'errors' => array( 'body_not_object' ) );
    }
    $errors = array();
    if ( ! isset( $body['doc'] ) || ! is_array( $body['doc'] ) ) {
        $errors[] = 'doc_missing';
    } elseif ( ! isset( $body['doc']['events'] ) || ! is_array( $body['doc']['events'] ) ) {
        $errors[] = 'doc_events_missing';
    }
    if ( ! array_key_exists( 'baseVersion', $body ) || ! is_int( $body['baseVersion'] ) ) {
        $errors[] = 'baseVersion_missing';
    }
    return array( 'valid' => empty( $errors ), 'errors' => $errors );
}
```

- [ ] **Step 4: Run the test, verify it passes**

Run: `php tests/curriculr/test-envelope.php`
Expected: six `PASS:` lines then `ALL PASS`.

- [ ] **Step 5: Lint + commit**

```bash
php -l plugin/curriculr-data-layer.php
git add tests/curriculr/test-envelope.php plugin/curriculr-data-layer.php
git commit -m "feat(curriculr): PUT envelope validation"
```

---

## Task 4: Database table + repository (WP-bound)

WP-bound functions cannot run under plain PHP; verify by `php -l` here and by `curl` in Task 8. Define them now.

**Files:**
- Modify: `plugin/curriculr-data-layer.php` (append functions)

- [ ] **Step 1: Append table name + install + repository functions**

Add to the end of `plugin/curriculr-data-layer.php`:
```php
/* ---------- WP: Tabelle + Repository ---------- */

function gsh_tp_curriculr_table() {
    global $wpdb;
    return $wpdb->prefix . 'curriculr_docs';
}

function gsh_tp_curriculr_install() {
    global $wpdb;
    $table   = gsh_tp_curriculr_table();
    $charset = $wpdb->get_charset_collate();
    $sql     = "CREATE TABLE $table (
        schoolyear varchar(64) NOT NULL,
        json longtext NOT NULL,
        version int unsigned NOT NULL DEFAULT 0,
        updated_at datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        updated_by bigint unsigned NOT NULL DEFAULT 0,
        feed_token varchar(64) NOT NULL DEFAULT '',
        PRIMARY KEY  (schoolyear)
    ) $charset;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
    update_option( 'gsh_tp_curriculr_db_version', 1, false );
}

function gsh_tp_curriculr_repo_get( $sj ) {
    global $wpdb;
    $table = gsh_tp_curriculr_table();
    $sj    = sanitize_key( $sj );
    $row   = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM $table WHERE schoolyear = %s", $sj ),
        ARRAY_A
    );
    return $row ? $row : null;
}

function gsh_tp_curriculr_repo_put( $sj, $doc, $base_version ) {
    global $wpdb;
    $table    = gsh_tp_curriculr_table();
    $sj       = sanitize_key( $sj );
    $existing = gsh_tp_curriculr_repo_get( $sj );
    $current  = $existing ? (int) $existing['version'] : 0;

    if ( gsh_tp_curriculr_version_decision( $current, $base_version ) === 'conflict' ) {
        return array( 'status' => 'conflict', 'current' => $existing );
    }

    $new_version = $current + 1;
    $token       = ( $existing && ! empty( $existing['feed_token'] ) )
        ? $existing['feed_token']
        : wp_generate_password( 32, false, false );

    $data = array(
        'schoolyear' => $sj,
        'json'       => wp_json_encode( $doc ),
        'version'    => $new_version,
        'updated_at' => current_time( 'mysql' ),
        'updated_by' => get_current_user_id(),
        'feed_token' => $token,
    );

    if ( $existing ) {
        $wpdb->update( $table, $data, array( 'schoolyear' => $sj ) );
    } else {
        $wpdb->insert( $table, $data );
    }

    return array(
        'status'     => 'ok',
        'version'    => $new_version,
        'feed_token' => $token,
        'updated_at' => $data['updated_at'],
    );
}
```

- [ ] **Step 2: Verify the pure tests still pass (file still requires cleanly)**

Run: `php tests/curriculr/test-ics.php && php tests/curriculr/test-version.php && php tests/curriculr/test-envelope.php`
Expected: each ends `ALL PASS` (appended WP-bound functions are only *defined*, never called at load, so plain-PHP require stays safe).

- [ ] **Step 3: Lint + commit**

```bash
php -l plugin/curriculr-data-layer.php
git add plugin/curriculr-data-layer.php
git commit -m "feat(curriculr): docs table, install, repository get/put"
```

---

## Task 5: REST routes (GET / PUT / health) + auth + CORS (WP-bound)

**Files:**
- Modify: `plugin/curriculr-data-layer.php` (append functions)

- [ ] **Step 1: Append the feed-URL helper, permission callback, CORS, and route handlers**

Add to the end of `plugin/curriculr-data-layer.php`:
```php
/* ---------- WP: REST-Routen, Auth, CORS ---------- */

function gsh_tp_curriculr_feed_url( $sj, $token ) {
    return rest_url( 'curriculr/v1/feed/' . rawurlencode( $sj ) . '/' . $token . '.ics' );
}

function gsh_tp_curriculr_perm() {
    // Application Passwords authentifizieren den Request als WP-User;
    // danach greift current_user_can wie bei einer normalen Session.
    return current_user_can( 'manage_options' );
}

function gsh_tp_curriculr_allowed_origin() {
    return get_option( 'gsh_tp_curriculr_origin', 'https://juwagn.github.io' );
}

function gsh_tp_curriculr_send_cors() {
    header( 'Access-Control-Allow-Origin: ' . gsh_tp_curriculr_allowed_origin() );
    header( 'Access-Control-Allow-Methods: GET, PUT, OPTIONS' );
    header( 'Access-Control-Allow-Headers: Authorization, Content-Type' );
    header( 'Vary: Origin' );
}

function gsh_tp_curriculr_cors_filter( $served, $result, $request, $server ) {
    if ( strpos( $request->get_route(), '/curriculr/v1' ) === 0 ) {
        gsh_tp_curriculr_send_cors();
        if ( $request->get_method() === 'OPTIONS' ) {
            status_header( 200 );
            return true; // Preflight kurzschließen.
        }
    }
    return $served;
}

function gsh_tp_curriculr_register_rest() {
    register_rest_route(
        'curriculr/v1',
        '/doc/(?P<sj>[a-z0-9_\-]+)',
        array(
            array(
                'methods'             => 'GET',
                'callback'            => 'gsh_tp_curriculr_rest_get',
                'permission_callback' => 'gsh_tp_curriculr_perm',
            ),
            array(
                'methods'             => 'PUT',
                'callback'            => 'gsh_tp_curriculr_rest_put',
                'permission_callback' => 'gsh_tp_curriculr_perm',
            ),
        )
    );
    register_rest_route(
        'curriculr/v1',
        '/health',
        array(
            'methods'             => 'GET',
            'callback'            => 'gsh_tp_curriculr_rest_health',
            'permission_callback' => 'gsh_tp_curriculr_perm',
        )
    );
    register_rest_route(
        'curriculr/v1',
        '/feed/(?P<sj>[a-z0-9_\-]+)/(?P<token>[A-Za-z0-9]+)\.ics',
        array(
            'methods'             => 'GET',
            'callback'            => 'gsh_tp_curriculr_rest_feed',
            'permission_callback' => '__return_true',
        )
    );
}

function gsh_tp_curriculr_rest_health() {
    return new WP_REST_Response( array( 'ok' => true, 'plugin' => GSH_TP_VERSION ), 200 );
}

function gsh_tp_curriculr_rest_get( $req ) {
    $row = gsh_tp_curriculr_repo_get( $req['sj'] );
    if ( ! $row ) {
        return new WP_REST_Response( array( 'exists' => false ), 404 );
    }
    return new WP_REST_Response(
        array(
            'exists'    => true,
            'doc'       => json_decode( $row['json'], true ),
            'version'   => (int) $row['version'],
            'updatedAt' => $row['updated_at'],
            'feedUrl'   => gsh_tp_curriculr_feed_url( $req['sj'], $row['feed_token'] ),
        ),
        200
    );
}

function gsh_tp_curriculr_rest_put( $req ) {
    $body = $req->get_json_params();
    $v    = gsh_tp_curriculr_validate_envelope( $body );
    if ( ! $v['valid'] ) {
        return new WP_REST_Response( array( 'error' => 'invalid', 'details' => $v['errors'] ), 400 );
    }

    $res = gsh_tp_curriculr_repo_put( $req['sj'], $body['doc'], (int) $body['baseVersion'] );

    if ( $res['status'] === 'conflict' ) {
        return new WP_REST_Response(
            array(
                'error'         => 'conflict',
                'serverVersion' => (int) $res['current']['version'],
                'doc'           => json_decode( $res['current']['json'], true ),
            ),
            409
        );
    }

    gsh_tp_curriculr_after_put( $req['sj'], $res['feed_token'] );

    return new WP_REST_Response(
        array(
            'status'    => 'ok',
            'version'   => $res['version'],
            'updatedAt' => $res['updated_at'],
            'feedUrl'   => gsh_tp_curriculr_feed_url( $req['sj'], $res['feed_token'] ),
        ),
        200
    );
}
```

> Note: `gsh_tp_curriculr_after_put` and `gsh_tp_curriculr_rest_feed` are defined in Tasks 6 and 7. PHP resolves function names at call time, so the file lints and the pure tests still pass before those tasks land.

- [ ] **Step 2: Verify pure tests still pass + lint**

Run: `php tests/curriculr/test-ics.php && php -l plugin/curriculr-data-layer.php`
Expected: `ALL PASS` then `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add plugin/curriculr-data-layer.php
git commit -m "feat(curriculr): REST doc GET/PUT + health, App-Password auth, CORS"
```

---

## Task 6: Public token feed route (WP-bound)

**Files:**
- Modify: `plugin/curriculr-data-layer.php` (append function)

- [ ] **Step 1: Append the feed handler**

Add to the end of `plugin/curriculr-data-layer.php`:
```php
/* ---------- WP: Öffentlicher Token-Feed (IServ + WP-Anzeige) ---------- */

function gsh_tp_curriculr_rest_feed( $req ) {
    $row = gsh_tp_curriculr_repo_get( $req['sj'] );
    if ( ! $row || ! hash_equals( (string) $row['feed_token'], (string) $req['token'] ) ) {
        return new WP_REST_Response( array( 'error' => 'not_found' ), 404 );
    }
    $doc = json_decode( $row['json'], true );
    $ics = gsh_tp_curriculr_build_ics( $doc );

    if ( ! headers_sent() ) {
        header( 'Content-Type: text/calendar; charset=utf-8' );
        header( 'Content-Disposition: inline; filename="' . sanitize_key( $req['sj'] ) . '.ics"' );
        header( 'Cache-Control: max-age=300' );
    }
    echo $ics; // phpcs:ignore -- rohe ICS-Ausgabe, bewusst kein wp_die.
    exit;
}
```

`hash_equals` prevents token timing attacks. The `.ics` suffix is part of the route regex (Task 5), so subscription URLs end in `.ics` for IServ.

- [ ] **Step 2: Lint + commit**

```bash
php -l plugin/curriculr-data-layer.php
git add plugin/curriculr-data-layer.php
git commit -m "feat(curriculr): public token-protected ICS feed endpoint"
```

---

## Task 7: Feed-reuse wiring (WP-bound)

On save, point the mapped profile's `ical_url` at our feed and trigger the existing refresh so the quartely/print renderer updates immediately — without any renderer change.

**Files:**
- Modify: `plugin/curriculr-data-layer.php` (append functions)

- [ ] **Step 1: Append the profile mapping + after_put wiring**

Add to the end of `plugin/curriculr-data-layer.php`:
```php
/* ---------- WP: Feed-Reuse-Verdrahtung (Spec §3/§7) ---------- */

function gsh_tp_curriculr_profile_for( $sj ) {
    // Schuljahr -> Profil-ID. Map-Option erlaubt explizite Zuordnung;
    // Fallback = aktives Profil.
    $map = get_option( 'gsh_tp_curriculr_profile_map', array() );
    $sj  = sanitize_key( $sj );
    if ( is_array( $map ) && isset( $map[ $sj ] ) ) {
        return $map[ $sj ];
    }
    return function_exists( 'gsh_tp_active_profile_id' ) ? gsh_tp_active_profile_id() : '';
}

function gsh_tp_curriculr_after_put( $sj, $token ) {
    $pid = gsh_tp_curriculr_profile_for( $sj );
    if ( ! $pid ) {
        return;
    }
    $feed_url = gsh_tp_curriculr_feed_url( $sj, $token );
    $profiles = gsh_tp_get_profiles();
    $changed  = false;
    foreach ( $profiles as &$p ) {
        if ( isset( $p['id'] ) && $p['id'] === $pid && ( ! isset( $p['ical_url'] ) || $p['ical_url'] !== $feed_url ) ) {
            $p['ical_url'] = $feed_url;
            $changed       = true;
        }
    }
    unset( $p );
    if ( $changed ) {
        update_option( 'gsh_tp_profiles', $profiles, true );
    }
    // Bestehenden Refresh anstoßen → Anzeige-Cache sofort aktuell.
    if ( function_exists( 'gsh_tp_do_refresh' ) ) {
        gsh_tp_do_refresh( $pid );
    }
}
```

> Loopback risk: `gsh_tp_do_refresh` fetches the feed URL via `wp_remote_get` (loopback to the same site). If a host blocks loopback HTTP, the display won't refresh on PUT (the feed + IServ still work). Fallback documented in Task 8 verification; if loopback is unavailable, schedule the refresh via `wp_schedule_single_event` instead — out of scope for v1.

- [ ] **Step 2: Lint + commit**

```bash
php -l plugin/curriculr-data-layer.php
git add plugin/curriculr-data-layer.php
git commit -m "feat(curriculr): feed-reuse wiring — set profile ical_url + do_refresh on PUT"
```

---

## Task 8: Load the module, register hooks, bump version, end-to-end verify

**Files:**
- Modify: `plugin/curriculr-data-layer.php` (append guarded hook block)
- Modify: `plugin/gsh-terminplan.php` (require_once + version bump in 4 places)

- [ ] **Step 1: Append the guarded hook registrations**

Add to the end of `plugin/curriculr-data-layer.php`:
```php
/* ---------- WP: Hooks (nur unter WordPress aktiv) ---------- */

if ( function_exists( 'add_action' ) ) {
    add_action( 'rest_api_init', 'gsh_tp_curriculr_register_rest' );
    add_filter( 'rest_pre_serve_request', 'gsh_tp_curriculr_cors_filter', 10, 4 );

    // Tabelle bei Aktivierung anlegen ...
    register_activation_hook( dirname( __FILE__ ) . '/gsh-terminplan.php', 'gsh_tp_curriculr_install' );

    // ... und defensiv, falls das Plugin per Update statt Reaktivierung kam.
    add_action(
        'admin_init',
        function () {
            if ( (int) get_option( 'gsh_tp_curriculr_db_version', 0 ) < 1 ) {
                gsh_tp_curriculr_install();
            }
        }
    );
}
```

- [ ] **Step 2: Add the require_once in the main plugin file**

In `plugin/gsh-terminplan.php`, immediately after the defines block (after the `GSH_TP_FRESH_KEY` define near line 512), add:
```php
require_once plugin_dir_path( __FILE__ ) . 'curriculr-data-layer.php';
```

- [ ] **Step 3: Bump the version in all four required places**

Per AGENTS.md, set version `4.4.0` → `4.5.0` in:
1. Plugin header comment `Version: 4.5.0`
2. `define( 'GSH_TP_VERSION', '4.5.0' );` (line ~507)
3. New top entry in `gsh_tp_changelog()`: `4.5.0 — Curriculr Data Layer: REST-Speicherung des Planner-Dokuments + Token-ICS-Feed (Feed-Reuse).`
4. The changelog block in the plugin header comment.

- [ ] **Step 4: Lint both files**

Run: `php -l plugin/curriculr-data-layer.php && php -l plugin/gsh-terminplan.php`
Expected: `No syntax errors detected` for both.

- [ ] **Step 5: Re-run the full pure-test suite**

Run: `php tests/curriculr/test-ics.php && php tests/curriculr/test-version.php && php tests/curriculr/test-envelope.php`
Expected: each ends `ALL PASS`.

- [ ] **Step 6: End-to-end verification against a WordPress install (manual)**

On a WordPress site with this plugin active (local or staging), with an Application Password created for an admin user:

```bash
SITE="https://your-wp.example"
AUTH="admin:xxxx xxxx xxxx xxxx xxxx xxxx"   # WP user : application password
SJ="sj_2026_27"

# health
curl -s -u "$AUTH" "$SITE/wp-json/curriculr/v1/health"
# expected: {"ok":true,"plugin":"4.5.0"}

# first PUT (baseVersion 0 -> creates row, returns version 1 + feedUrl)
curl -s -u "$AUTH" -X PUT "$SITE/wp-json/curriculr/v1/doc/$SJ" \
  -H 'Content-Type: application/json' \
  -d "{\"baseVersion\":0,\"doc\":$(cat tests/curriculr/fixtures/sample-doc.json)}"
# expected: {"status":"ok","version":1,"updatedAt":...,"feedUrl":".../feed/sj_2026_27/<token>.ics"}

# stale PUT (baseVersion 0 again -> 409 conflict)
curl -s -o /dev/null -w "%{http_code}\n" -u "$AUTH" -X PUT "$SITE/wp-json/curriculr/v1/doc/$SJ" \
  -H 'Content-Type: application/json' \
  -d "{\"baseVersion\":0,\"doc\":{\"events\":[]}}"
# expected: 409

# GET
curl -s -u "$AUTH" "$SITE/wp-json/curriculr/v1/doc/$SJ" | head -c 200
# expected: {"exists":true,"doc":{...},"version":1,...}

# public feed (no auth) — copy the feedUrl from the PUT response
curl -s "$SITE/wp-json/curriculr/v1/feed/$SJ/<token>.ics" | head -5
# expected: BEGIN:VCALENDAR ... (text/calendar)

# CORS preflight
curl -s -o /dev/null -w "%{http_code}\n" -X OPTIONS "$SITE/wp-json/curriculr/v1/doc/$SJ" \
  -H "Origin: https://juwagn.github.io" -H "Access-Control-Request-Method: PUT"
# expected: 200, response carries Access-Control-Allow-Origin
```

Then confirm feed-reuse: open the WordPress quarterly view for the mapped profile; the two fixture events (Gesamtkonferenz, Elternsprechtag) should appear. If they don't and the feed URL itself returns valid ICS, the host likely blocks loopback `wp_remote_get` (see Task 7 note).

- [ ] **Step 7: Commit**

```bash
git add plugin/curriculr-data-layer.php plugin/gsh-terminplan.php
git commit -m "feat(curriculr): load data layer, register hooks, bump to 4.5.0"
```

---

## Self-Review Notes (author)

- **Spec coverage:** §3 feed-reuse → Task 7; §6 API (GET/PUT/health/feed, auth, CORS, 409) → Tasks 5/6; §7 tables + doc↔profile map → Tasks 4/7; §9 procedural file + conventions → all tasks + Task 8 version bump; §8 schema-decoupling → feed/validation read only needed fields (Tasks 1/3). Out of v1 scope by design: revisions ⑦ + nightly backup ⑧ (SP4).
- **Not covered here (correct):** Planner-side sync client (SP2), IServ subscription + migration (SP3). Separate plans.
- **Type consistency:** `feed_token` (DB) ↔ `feed_token`/`token` (route param) ↔ `gsh_tp_curriculr_feed_url($sj,$token)`; PUT returns `version`/`feedUrl`/`updatedAt`, GET mirrors them; `gsh_tp_curriculr_repo_put` returns `status`/`version`/`feed_token`/`updated_at`, consumed exactly in `rest_put`.
- **Open items for SP3 (not blockers):** confirm IServ accepts the `.ics` token URL + its pull interval; decide explicit `gsh_tp_curriculr_profile_map` entries vs active-profile fallback; loopback-refresh fallback if the host blocks it.
