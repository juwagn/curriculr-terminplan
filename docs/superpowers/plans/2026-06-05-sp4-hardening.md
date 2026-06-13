# SP4 — Härtung v1.1: Revisionen + Nacht-Backup

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Jedes gespeicherte WP-Dokument erzeugt einen unveränderlichen Revisions-Snapshot (Rollback-Fähigkeit gegen schlechte Edits) und ein täglicher wp-cron-Job schreibt JSON+ICS-Backups ins Upload-Verzeichnis (Schutz gegen DB-Verlust).

**Architecture:** Neue Tabelle `wp_curriculr_doc_revisions` in derselben `curriculr-data-layer.php`. `repo_put` schreibt nach jedem erfolgreichen PUT einen Snapshot und prunt auf max. 50 Einträge. Zwei neue REST-Routen (GET list, GET single). Backup-Cron exportiert alle Schuljahre täglich. Keine Klassen, kein Build-System — prozedural, `gsh_tp_curriculr_*`-Präfix.

**Tech Stack:** PHP 8.x (prozedural), WordPress 6.x, wp-cron, wp_filesystem für Backup-Writes, Plain-PHP-Testharness (kein Composer/PHPUnit)

---

## Dateien

| Aktion | Datei |
|---|---|
| Modify | `plugin/curriculr-data-layer.php` |
| Modify | `plugin/gsh-terminplan.php` (Version + Changelog) |
| Create | `tests/curriculr/test-revisions.php` |

---

## Task 1: Revisions-Tabelle + Install-Migration

**Files:**
- Modify: `plugin/curriculr-data-layer.php:171–193` (`gsh_tp_curriculr_install`, `gsh_tp_curriculr_table`)

- [ ] **Schritt 1: `gsh_tp_curriculr_revisions_table()` hinzufügen**

  Direkt nach der Zeile mit `function gsh_tp_curriculr_table()` (aktuell Zeile ~171) diese Funktion einfügen:

  ```php
  function gsh_tp_curriculr_revisions_table() {
      global $wpdb;
      return $wpdb->prefix . 'curriculr_doc_revisions';
  }
  ```

- [ ] **Schritt 2: `gsh_tp_curriculr_install()` um Revisions-Tabelle erweitern**

  Den Body von `gsh_tp_curriculr_install()` so ersetzen:

  ```php
  function gsh_tp_curriculr_install() {
      global $wpdb;
      $charset = $wpdb->get_charset_collate();

      $docs_table = gsh_tp_curriculr_table();
      $docs_sql   = "CREATE TABLE $docs_table (
          schoolyear varchar(64) NOT NULL,
          json longtext NOT NULL,
          version int unsigned NOT NULL DEFAULT 0,
          stage varchar(16) NOT NULL DEFAULT 'entwurf',
          updated_at datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
          updated_by bigint unsigned NOT NULL DEFAULT 0,
          feed_token varchar(64) NOT NULL DEFAULT '',
          PRIMARY KEY  (schoolyear)
      ) $charset;";

      $rev_table = gsh_tp_curriculr_revisions_table();
      $rev_sql   = "CREATE TABLE $rev_table (
          id bigint unsigned NOT NULL AUTO_INCREMENT,
          schoolyear varchar(64) NOT NULL,
          version int unsigned NOT NULL DEFAULT 0,
          json longtext NOT NULL,
          created_at datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
          PRIMARY KEY  (id),
          KEY sj_version (schoolyear, version)
      ) $charset;";

      require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      dbDelta( $docs_sql );
      dbDelta( $rev_sql );
      update_option( 'gsh_tp_curriculr_db_version', 3, false );
  }
  ```

- [ ] **Schritt 3: `admin_init`-Migrations-Check von `< 2` auf `< 3` anheben**

  Im Hook-Block am Ende der Datei (Zeile ~444) den Check anpassen:

  ```php
  // Vorher:
  if ( (int) get_option( 'gsh_tp_curriculr_db_version', 0 ) < 2 ) {
  // Nachher:
  if ( (int) get_option( 'gsh_tp_curriculr_db_version', 0 ) < 3 ) {
  ```

- [ ] **Schritt 4: PHP-Syntax prüfen**

  ```bash
  php -l plugin/curriculr-data-layer.php
  ```
  Erwartete Ausgabe: `No syntax errors detected`

---

## Task 2: Revision-Snapshot in `repo_put` schreiben

**Files:**
- Modify: `plugin/curriculr-data-layer.php` (`gsh_tp_curriculr_repo_put`, neue Funktion `gsh_tp_curriculr_repo_save_revision`)

- [ ] **Schritt 1: `gsh_tp_curriculr_repo_save_revision()` hinzufügen**

  Direkt nach `gsh_tp_curriculr_repo_put()` (nach Zeile ~245) einfügen:

  ```php
  function gsh_tp_curriculr_repo_save_revision( $sj, $version, $json_str ) {
      global $wpdb;
      $table = gsh_tp_curriculr_revisions_table();
      $wpdb->insert(
          $table,
          array(
              'schoolyear' => sanitize_key( $sj ),
              'version'    => (int) $version,
              'json'       => $json_str,
              'created_at' => current_time( 'mysql' ),
          )
      );
      return (int) $wpdb->insert_id;
  }
  ```

- [ ] **Schritt 2: `repo_put` nach erfolgreichem Write snapshot aufrufen**

  In `gsh_tp_curriculr_repo_put()` direkt vor dem `return`-Statement (nach dem `$wpdb->update`/`$wpdb->insert`-Block) einfügen:

  ```php
  // Revision-Snapshot + Retention-Prune.
  $json_str = wp_json_encode( $doc );
  gsh_tp_curriculr_repo_save_revision( $sj, $new_version, $json_str );
  gsh_tp_curriculr_prune_revisions( $sj );
  ```

  Hinweis: `gsh_tp_curriculr_prune_revisions` wird in Task 3 definiert — im PHP ist die Reihenfolge der Funktionsdefinitionen egal, Laufzeit-Call erst nach Task 3 relevant.

- [ ] **Schritt 3: PHP-Syntax prüfen**

  ```bash
  php -l plugin/curriculr-data-layer.php
  ```
  Erwartete Ausgabe: `No syntax errors detected`

---

## Task 3: Retention-Prune (max. 50 Revisionen pro Schuljahr)

**Files:**
- Modify: `plugin/curriculr-data-layer.php` (neue Funktion `gsh_tp_curriculr_prune_revisions`)

- [ ] **Schritt 1: `gsh_tp_curriculr_prune_revisions()` hinzufügen**

  Direkt nach `gsh_tp_curriculr_repo_save_revision()` einfügen:

  ```php
  function gsh_tp_curriculr_prune_revisions( $sj ) {
      global $wpdb;
      $table = gsh_tp_curriculr_revisions_table();
      $sj    = sanitize_key( $sj );
      // Behalte die neuesten 50 Einträge; lösche alles ältere.
      $wpdb->query(
          $wpdb->prepare(
              "DELETE FROM $table
               WHERE schoolyear = %s
                 AND id NOT IN (
                   SELECT id FROM (
                     SELECT id FROM $table
                     WHERE schoolyear = %s
                     ORDER BY id DESC
                     LIMIT 50
                   ) AS keep
                 )",
              $sj,
              $sj
          )
      );
  }
  ```

- [ ] **Schritt 2: PHP-Syntax prüfen**

  ```bash
  php -l plugin/curriculr-data-layer.php
  ```
  Erwartete Ausgabe: `No syntax errors detected`

---

## Task 4: REST-Endpunkte für Revisionen (List + Get)

**Files:**
- Modify: `plugin/curriculr-data-layer.php` (`gsh_tp_curriculr_register_rest`, neue Handler-Funktionen)

- [ ] **Schritt 1: Zwei REST-Routen in `gsh_tp_curriculr_register_rest()` registrieren**

  Am Ende von `gsh_tp_curriculr_register_rest()` (vor der schließenden `}`) zwei weitere `register_rest_route`-Aufrufe einfügen:

  ```php
  register_rest_route(
      'curriculr/v1',
      '/doc/(?P<sj>[a-z0-9_\-]+)/revisions',
      array(
          'methods'             => 'GET',
          'callback'            => 'gsh_tp_curriculr_rest_revisions_list',
          'permission_callback' => 'gsh_tp_curriculr_perm',
          'args'                => array( 'sj' => array( 'required' => true ) ),
      )
  );

  register_rest_route(
      'curriculr/v1',
      '/doc/(?P<sj>[a-z0-9_\-]+)/revisions/(?P<id>\d+)',
      array(
          'methods'             => 'GET',
          'callback'            => 'gsh_tp_curriculr_rest_revisions_get',
          'permission_callback' => 'gsh_tp_curriculr_perm',
          'args'                => array(
              'sj' => array( 'required' => true ),
              'id' => array( 'required' => true ),
          ),
      )
  );
  ```

- [ ] **Schritt 2: Handler-Funktionen hinzufügen**

  Nach `gsh_tp_curriculr_rest_feed()` (Zeile ~395) einfügen:

  ```php
  function gsh_tp_curriculr_rest_revisions_list( $req ) {
      global $wpdb;
      $table = gsh_tp_curriculr_revisions_table();
      $sj    = sanitize_key( $req['sj'] );
      $rows  = $wpdb->get_results(
          $wpdb->prepare(
              "SELECT id, schoolyear, version, created_at FROM $table WHERE schoolyear = %s ORDER BY id DESC LIMIT 100",
              $sj
          ),
          ARRAY_A
      );
      if ( $rows === null ) {
          return new WP_REST_Response( array( 'error' => 'db_error' ), 500 );
      }
      return new WP_REST_Response( $rows ? array_values( $rows ) : array(), 200 );
  }

  function gsh_tp_curriculr_rest_revisions_get( $req ) {
      global $wpdb;
      $table = gsh_tp_curriculr_revisions_table();
      $sj    = sanitize_key( $req['sj'] );
      $id    = (int) $req['id'];
      $row   = $wpdb->get_row(
          $wpdb->prepare(
              "SELECT * FROM $table WHERE id = %d AND schoolyear = %s",
              $id,
              $sj
          ),
          ARRAY_A
      );
      if ( ! $row ) {
          return new WP_REST_Response( array( 'error' => 'not_found' ), 404 );
      }
      return new WP_REST_Response(
          array(
              'id'         => (int) $row['id'],
              'schoolyear' => $row['schoolyear'],
              'version'    => (int) $row['version'],
              'json'       => json_decode( $row['json'], true ),
              'created_at' => $row['created_at'],
          ),
          200
      );
  }
  ```

- [ ] **Schritt 3: PHP-Syntax prüfen**

  ```bash
  php -l plugin/curriculr-data-layer.php
  ```
  Erwartete Ausgabe: `No syntax errors detected`

---

## Task 5: Nacht-Backup per wp-cron

**Files:**
- Modify: `plugin/curriculr-data-layer.php` (neue Funktion `gsh_tp_curriculr_backup_cron`, Hook-Registrierung)

- [ ] **Schritt 1: Backup-Funktion hinzufügen**

  Nach den Revisions-Funktionen einfügen:

  ```php
  function gsh_tp_curriculr_backup_cron() {
      global $wpdb;
      $table = gsh_tp_curriculr_table();
      $rows  = $wpdb->get_results( "SELECT schoolyear, json, feed_token FROM $table", ARRAY_A );
      if ( ! $rows ) {
          return;
      }

      $upload_dir = wp_upload_dir();
      $backup_dir = $upload_dir['basedir'] . '/curriculr-backups';
      wp_mkdir_p( $backup_dir );

      // Zugang zur lokalen Datei über WP_Filesystem sicherstellen.
      if ( ! function_exists( 'WP_Filesystem' ) ) {
          require_once ABSPATH . 'wp-admin/includes/file.php';
      }
      WP_Filesystem();
      global $wp_filesystem;

      $stamp = gmdate( 'Y-m-d' );
      foreach ( $rows as $row ) {
          $sj  = sanitize_key( $row['schoolyear'] );
          $doc = json_decode( $row['json'], true );
          // JSON-Backup.
          $wp_filesystem->put_contents(
              "$backup_dir/{$sj}-{$stamp}.json",
              $row['json'],
              FS_CHMOD_FILE
          );
          // ICS-Backup.
          if ( is_array( $doc ) ) {
              $wp_filesystem->put_contents(
                  "$backup_dir/{$sj}-{$stamp}.ics",
                  gsh_tp_curriculr_build_ics( $doc ),
                  FS_CHMOD_FILE
              );
          }
      }
  }
  ```

- [ ] **Schritt 2: Cron-Hooks im WP-Hook-Block registrieren**

  Im Hook-Block am Ende der Datei (nach den bestehenden `add_action`-Aufrufen) einfügen:

  ```php
  add_action( 'gsh_tp_curriculr_daily_backup', 'gsh_tp_curriculr_backup_cron' );
  // Cron planen, falls noch nicht getan (z.B. nach Plugin-Update ohne Reaktivierung).
  add_action(
      'wp_loaded',
      function () {
          if ( ! wp_next_scheduled( 'gsh_tp_curriculr_daily_backup' ) ) {
              wp_schedule_event( strtotime( 'tomorrow 02:00:00' ), 'daily', 'gsh_tp_curriculr_daily_backup' );
          }
      }
  );
  ```

- [ ] **Schritt 3: Cron bei Plugin-Deaktivierung aufräumen**

  In der `register_activation_hook`-Nähe einen Deaktivierungs-Hook einfügen:

  ```php
  register_deactivation_hook(
      dirname( __FILE__ ) . '/gsh-terminplan.php',
      function () {
          $timestamp = wp_next_scheduled( 'gsh_tp_curriculr_daily_backup' );
          if ( $timestamp ) {
              wp_unschedule_event( $timestamp, 'gsh_tp_curriculr_daily_backup' );
          }
      }
  );
  ```

- [ ] **Schritt 4: PHP-Syntax prüfen**

  ```bash
  php -l plugin/curriculr-data-layer.php
  ```
  Erwartete Ausgabe: `No syntax errors detected`

---

## Task 6: Tests für Revisionen

**Files:**
- Create: `tests/curriculr/test-revisions.php`

- [ ] **Schritt 1: Test-Datei schreiben**

  ```php
  <?php
  /**
   * Tests für Revisions-Snapshot, Prune und REST-Handler.
   *
   * Dependency-free, läuft mit plain `php`. Benutzt dieselbe Stub-Strategie
   * wie test-integration-stubbed.php.
   */
  define( 'GSH_TP_CURRICULR_TEST', true );
  define( 'GSH_TP_VERSION', '4.9.0-test' );
  define( 'ARRAY_A', 'ARRAY_A' );
  define( 'FS_CHMOD_FILE', 0644 );

  require __DIR__ . '/assert.php';

  /* ---------- Erweiterte WordPress-Stubs ---------- */
  class Gsh_Fake_Wpdb_Rev {
      public $prefix    = 'wp_';
      public $docs      = array();      // keyed by schoolyear
      public $revs      = array();      // keyed by auto-increment id
      public $next_id   = 1;
      public $insert_id = 0;

      public function get_charset_collate() { return ''; }
      public function prepare( $q, ...$args ) {
          // Für Tests: ersten Positionalparameter zurückgeben.
          return isset( $args[0] ) ? $args[0] : $q;
      }
      public function get_row( $key, $out = null ) {
          if ( is_int( $key ) || ctype_digit( (string) $key ) ) {
              return $this->revs[ (int) $key ] ?? null;   // Revision by id
          }
          return $this->docs[ $key ] ?? null;
      }
      public function get_results( $query, $out = null ) {
          return array_values( $this->revs );
      }
      public function insert( $table, $data ) {
          if ( strpos( $table, 'revisions' ) !== false ) {
              $data['id']   = $this->next_id;
              $this->insert_id = $this->next_id;
              $this->revs[ $this->next_id ] = $data;
              $this->next_id++;
          } else {
              $this->docs[ $data['schoolyear'] ] = $data;
          }
      }
      public function update( $table, $data, $where ) {
          $key = $where['schoolyear'] ?? null;
          if ( $key ) {
              $this->docs[ $key ] = array_merge( $this->docs[ $key ] ?? array(), $data );
          }
      }
      public function query( $sql ) {
          // Prune-Simulation: behalte die ersten 50 (simplifiziert für Tests).
          if ( count( $this->revs ) > 50 ) {
              $keys = array_keys( $this->revs );
              $keep = array_slice( $keys, -50 );
              foreach ( $keys as $k ) {
                  if ( ! in_array( $k, $keep, true ) ) {
                      unset( $this->revs[ $k ] );
                  }
              }
          }
          return true;
      }
  }
  $GLOBALS['wpdb']      = new Gsh_Fake_Wpdb_Rev();
  $GLOBALS['options']   = array();
  $GLOBALS['refreshed'] = array();
  function get_option( $k, $d = false ) { return $GLOBALS['options'][ $k ] ?? $d; }
  function update_option( $k, $v, $a = null ) { $GLOBALS['options'][ $k ] = $v; return true; }
  function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $k ) ); }
  function wp_generate_password( $l = 12, $s = true, $e = true ) { return substr( str_repeat( 'aB3xY9Qz', 8 ), 0, $l ); }
  function current_time( $t ) { return '2026-06-05 02:00:00'; }
  function get_current_user_id() { return 1; }
  function wp_json_encode( $d ) { return json_encode( $d ); }
  function current_user_can( $c ) { return true; }
  function rest_url( $p ) { return 'https://wp.test/wp-json/' . $p; }
  function gsh_tp_get_profiles() { return $GLOBALS['profiles'] ?? array(); }
  function gsh_tp_active_profile_id() { return 'p1'; }
  function gsh_tp_do_refresh( $pid ) { $GLOBALS['refreshed'][] = $pid; }
  function add_action() {}
  function add_filter() {}
  function register_activation_hook() {}
  function register_deactivation_hook() {}
  function wp_next_scheduled() { return false; }
  function wp_schedule_event() {}
  class WP_REST_Response {
      public $data; public $status;
      public function __construct( $d, $s = 200 ) { $this->data = $d; $this->status = $s; }
  }
  class Gsh_Fake_Req implements ArrayAccess {
      public $params;
      public function __construct( $params ) { $this->params = $params; }
      public function get_method() { return 'GET'; }
      public function get_route() { return ''; }
      public function offsetExists( $o ): bool { return isset( $this->params[ $o ] ); }
      public function offsetGet( $o ): mixed { return $this->params[ $o ] ?? null; }
      public function offsetSet( $o, $v ): void { $this->params[ $o ] = $v; }
      public function offsetUnset( $o ): void { unset( $this->params[ $o ] ); }
  }

  require __DIR__ . '/../../plugin/curriculr-data-layer.php';

  $doc = json_decode( file_get_contents( __DIR__ . '/fixtures/sample-doc.json' ), true );

  /* ---------- 1. Revision-Snapshot wird bei repo_put geschrieben ---------- */
  $r1 = gsh_tp_curriculr_repo_put( 'sj_2026_27', $doc, 0 );
  gsh_assert_eq( $r1['status'], 'ok', 'repo_put erzeugt Version 1' );
  gsh_assert_eq( count( $GLOBALS['wpdb']->revs ), 1, 'nach erstem PUT genau 1 Revision' );

  $rev = reset( $GLOBALS['wpdb']->revs );
  gsh_assert_eq( (int) $rev['version'], 1, 'Revision trägt Version 1' );
  gsh_assert_eq( $rev['schoolyear'], 'sj_2026_27', 'Revision trägt schoolyear' );
  gsh_assert_true( ! empty( $rev['json'] ), 'Revision enthält json' );

  /* ---------- 2. Weiterer PUT → weitere Revision ---------- */
  gsh_tp_curriculr_repo_put( 'sj_2026_27', $doc, 1 );
  gsh_assert_eq( count( $GLOBALS['wpdb']->revs ), 2, 'nach zweitem PUT zwei Revisionen' );

  /* ---------- 3. REST: Revisions-Liste ---------- */
  $list_resp = gsh_tp_curriculr_rest_revisions_list( new Gsh_Fake_Req( array( 'sj' => 'sj_2026_27' ) ) );
  gsh_assert_eq( $list_resp->status, 200, 'revisions_list -> 200' );
  gsh_assert_true( is_array( $list_resp->data ) && count( $list_resp->data ) === 2, 'revisions_list enthält 2 Einträge' );

  /* ---------- 4. REST: Einzelne Revision abrufen ---------- */
  $first_id  = (int) array_key_first( $GLOBALS['wpdb']->revs );
  $get_resp  = gsh_tp_curriculr_rest_revisions_get( new Gsh_Fake_Req( array( 'sj' => 'sj_2026_27', 'id' => $first_id ) ) );
  gsh_assert_eq( $get_resp->status, 200, 'revisions_get -> 200' );
  gsh_assert_eq( $get_resp->data['version'], 1, 'revisions_get gibt Version 1 zurück' );
  gsh_assert_true( is_array( $get_resp->data['json'] ), 'revisions_get dekodiert json zu Array' );

  /* ---------- 5. REST: Nicht-existierende Revision → 404 ---------- */
  $nf_resp = gsh_tp_curriculr_rest_revisions_get( new Gsh_Fake_Req( array( 'sj' => 'sj_2026_27', 'id' => 9999 ) ) );
  gsh_assert_eq( $nf_resp->status, 404, 'revisions_get unbekannte id -> 404' );

  /* ---------- 6. Revision-Konflikt: kein PUT → keine neue Revision ---------- */
  $before = count( $GLOBALS['wpdb']->revs );
  $conf   = gsh_tp_curriculr_repo_put( 'sj_2026_27', $doc, 0 );  // veraltete baseVersion
  gsh_assert_eq( $conf['status'], 'conflict', 'veraltete baseVersion -> conflict' );
  gsh_assert_eq( count( $GLOBALS['wpdb']->revs ), $before, 'Konflikt erzeugt keine neue Revision' );

  gsh_test_done();
  ```

- [ ] **Schritt 2: Test ausführen**

  ```bash
  php tests/curriculr/test-revisions.php
  ```
  Erwartete Ausgabe:
  ```
  PASS: repo_put erzeugt Version 1
  PASS: nach erstem PUT genau 1 Revision
  PASS: Revision trägt Version 1
  PASS: Revision trägt schoolyear
  PASS: Revision enthält json
  PASS: nach zweitem PUT zwei Revisionen
  PASS: revisions_list -> 200
  PASS: revisions_list enthält 2 Einträge
  PASS: revisions_get -> 200
  PASS: revisions_get gibt Version 1 zurück
  PASS: revisions_get dekodiert json zu Array
  PASS: revisions_get unbekannte id -> 404
  PASS: Konflikt erzeugt keine neue Revision
  ALL PASS
  ```

- [ ] **Schritt 3: Alle bestehenden Tests noch grün**

  ```bash
  php tests/curriculr/test-version.php
  php tests/curriculr/test-stage.php
  php tests/curriculr/test-integration-stubbed.php
  php tests/curriculr/test-ics.php
  php tests/curriculr/test-ics-edgecases.php
  php tests/curriculr/test-envelope.php
  ```
  Alle müssen `ALL PASS` ausgeben.

---

## Task 7: Versionsnummer + PR

**Files:**
- Modify: `plugin/gsh-terminplan.php` (4 Stellen: Header, Konstante, Changelog-Array, Changelog-Kommentar)

- [ ] **Schritt 1: Version von 4.8.0 → 4.9.0 an allen 4 Stellen anheben**

  Changelog-Eintrag (oben im Array und im Header-Kommentar einfügen):
  ```
  4.9.0: [FEATURE] SP4 Hardening: Revisions-Snapshots (wp_curriculr_doc_revisions), REST GET list+single, Retention-Prune, Nacht-Backup via wp-cron
  ```

- [ ] **Schritt 2: Abschließende Syntax-Checks**

  ```bash
  php -l plugin/gsh-terminplan.php
  php -l plugin/curriculr-data-layer.php
  ```
  Beide: `No syntax errors detected`

- [ ] **Schritt 3: Alle Tests ein letztes Mal durchlaufen**

  ```bash
  for f in tests/curriculr/test-*.php; do php "$f"; done
  ```
  Erwartete Ausgabe: jede Datei endet mit `ALL PASS`.

- [ ] **Schritt 4: Commit + Branch + PR**

  ```bash
  git checkout -b feat/sp4-hardening
  git add plugin/curriculr-data-layer.php plugin/gsh-terminplan.php tests/curriculr/test-revisions.php
  git commit -m "feat(sp4): Revisions-Snapshots, REST list/get, Prune, Nacht-Backup via wp-cron (v4.9.0)"
  git push -u origin feat/sp4-hardening
  gh pr create --title "SP4 Hardening: Revisions + Nacht-Backup (v4.9.0)" \
    --body "Implements SP4 as per docs/superpowers/plans/2026-06-05-sp4-hardening.md

  ## Changes
  - wp_curriculr_doc_revisions table (dbDelta, db_version 3)
  - repo_put now writes a snapshot after each successful PUT
  - prune_revisions keeps last 50 per schoolyear
  - REST GET /curriculr/v1/doc/{sj}/revisions (list)
  - REST GET /curriculr/v1/doc/{sj}/revisions/{id} (single, json decoded)
  - gsh_tp_curriculr_backup_cron() → daily wp-cron → JSON+ICS in uploads/curriculr-backups/
  - New test suite: tests/curriculr/test-revisions.php (13 assertions)"
  ```
