
# Design: Kategorien aus Planner übernehmen

**Datum:** 2026-07-02
**Status:** Approved
**Scope:** Manuelle, admin-getriggerte Übernahme der Kategorien eines Planner-Dokuments (`doc.categories`) in die WP-seitigen `gsh_tp_categories`. Kein automatischer Sync, keine neue REST-Route.

## Problem

Zwei unabhängige Kategorie-Speicher existieren parallel:

- **Planner:** `doc.categories`, pro Schuljahr im JSON-Dokument, editierbar im `CategoriesTab` (SPA), gepusht via `PUT /curriculr/v1/doc/{sj}`.
- **WP-Plugin:** `gsh_tp_categories`, eine **globale** Option, editierbar im Kategorien-Tab (`gsh_tp_render_kategorien_tab()`), verwaltet über `gsh_tp_get_categories()` / `gsh_tp_save_categories()`.

Für das aktuell laufende Hauptschuljahr zieht die `[gsh_terminplan]`-Anzeige ihre Termine aus einem **echten externen IServ-Kalenderfeed** (`.../iserv/public/calendar/ics/feed/full/<token>/calendar.ics`), nicht aus dem Planner-Dokument. Farbe/Kategorie dieser Termine bestimmt ausschließlich `gsh_tp_assign_categories_to_event()` per Keyword-Matching (Titel/Beschreibung/Ort/CATEGORIES-Text) gegen `gsh_tp_categories`.

Ein automatischer Mirror-on-Push (`doc.categories` → `gsh_tp_categories` bei jedem Planner-PUT) würde diese Keyword-Klassifikation für den IServ-Feed unbemerkt überschreiben und die Live-Anzeige beschädigen. Beide Datenmodelle sind aber strukturell identisch (`{id, label, color, slug, keywords[]}`), eine manuelle Übernahme ist naheliegend.

## Lösung

### 1. UI: neuer Block im Kategorien-Tab

Oberhalb der bestehenden Kategorien-Tabelle in `gsh_tp_render_kategorien_tab()`:

```
[Schuljahr: ▾ 2026/27 (aktiv)]  [Aus Planner übernehmen]   <Statuszeile>
```

- Dropdown listet alle Schuljahre aus `wp_curriculr_docs` (Label + sj-Key), vorselektiert auf das aktuell zugeordnete/aktive Schuljahr (Fallback: erster Eintrag). Keine Planner-Dokumente vorhanden → Block zeigt Hinweistext statt Dropdown, Button deaktiviert.
- Klick auf „Aus Planner übernehmen" ruft den neuen AJAX-Handler auf und merged das Ergebnis **client-seitig in die bestehende Tabelle** (identisches JS-Zeilenmodell wie beim manuellen Bearbeiten).
- **Nichts wird automatisch gespeichert.** Der Admin muss weiterhin den bestehenden Button „Kategorien speichern" klicken. Statuszeile zeigt z. B. „3 übernommen, 2 aktualisiert – bitte prüfen und speichern".

### 2. Merge-Logik (client-seitig, JS)

Für jede importierte Kategorie:

- **ID existiert bereits in der Tabelle** → nur `label` und `color` der Zeile überschreiben (inkl. Live-Vorschau-Update); `keywords` bleiben unangetastet (sie sind auf den IServ-Rohtext abgestimmt).
- **ID existiert noch nicht** → neue Zeile anhängen mit `id`/`slug`/`label`/`color` aus dem Planner, `keywords` leer.
- Kategorien, die nur in `gsh_tp_categories` existieren (keine Entsprechung im Planner-Set), werden **nie berührt oder gelöscht** — der Merge iteriert ausschließlich über die importierten IDs.

### 3. Backend: neuer AJAX-Handler (read-only)

```php
add_action( 'wp_ajax_gsh_tp_import_categories_from_planner', 'gsh_tp_ajax_import_categories_from_planner' );

function gsh_tp_ajax_import_categories_from_planner(): void {
    check_ajax_referer( 'gsh_tp_save_categories_nonce', 'nonce' ); // bestehende Nonce, gleicher Tab/Capability
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

Rührt `gsh_tp_categories` **nicht an** — reine Datenlieferung. Persistiert wird ausschließlich über den bereits bestehenden `gsh_tp_ajax_save_categories()`-Pfad (Nonce, Capability-Check, Validierung, Dedup — alles unverändert).

Dropdown-Datenquelle: gleiche Abfrage wie `gsh_tp_curriculr_rest_doc_list()` (schoolyear + name aus `wp_curriculr_docs`), aber serverseitig direkt in den Tab gerendert — keine neue REST-Route, rein Admin-AJAX.

### 4. Fehlerbehandlung

- Kein Dokument für gewähltes Schuljahr → Button deaktiviert + Inline-Hinweis, kein stiller No-Op.
- `doc.categories` fehlt/ungültig → als leere Liste behandelt (nichts zu übernehmen), gleiche Notice-Optik wie andere AJAX-Fehler im Tab.

## Nicht im Scope

- Automatischer Sync bei jedem Planner-PUT (bewusst abgelehnt — würde die IServ-Keyword-Klassifikation stillschweigend überschreiben).
- Löschen von WP-only-Kategorien beim Import.
- Rückrichtung (WP-Kategorien → Planner).
- Neue `curriculr/v1`-REST-Route — reiner Admin-AJAX-Mechanismus.

## Versionierung

`NEU`-Feature → Minor-Bump. Da `4.26.1` (unabhängiger Draft-Toggle-Fix) bereits unstaged in Arbeit ist, landet dieses Feature als **`4.27.0`** — 4 Stellen gemäß `CLAUDE.md` (Header-Kommentar, `GSH_TP_VERSION`, Changelog-Array, Changelog-Block im Header), `php -l` nach Änderung, ZIP-Rebuild laut Deploy-Anleitung.

## Tests

- `tests/curriculr/`: neuer dependency-freier Test für `gsh_tp_ajax_import_categories_from_planner` (Nonce/Capability/404/Happy-Path), analog zu bestehenden AJAX-Tests.
- Client-seitige Merge-Logik: kein JS-Testharness im Plugin vorhanden → manueller Smoke-Test gegen ein echtes Planner-synchronisiertes Schuljahr (via `/verify`).

## Betroffene Dateien

- `plugin/gsh-terminplan.php`:
  - `gsh_tp_render_kategorien_tab()` — neuer Übernahme-Block (Dropdown + Button + Statuszeile)
  - neue Funktion `gsh_tp_ajax_import_categories_from_planner()` + `add_action( 'wp_ajax_gsh_tp_import_categories_from_planner', ... )`
  - JS im Kategorien-Tab (~Zeile 4261 ff.) — Merge-Funktion ergänzt
  - Versionsstellen (Header, `GSH_TP_VERSION`, Changelog ×2) → `4.27.0`
- `tests/curriculr/` — neuer Testfall für den AJAX-Handler
