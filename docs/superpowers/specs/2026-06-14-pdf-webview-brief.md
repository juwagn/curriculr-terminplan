# Brief: PDF-Export, der in der eingebetteten IServ-App-Webview funktioniert

> Handoff-Prompt. In eine frische Session geben (oder hier weiterarbeiten), um die
> Option-A-Lösung (client-seitige PDF-Datei) umzusetzen. Enthält allen Kontext,
> damit nichts neu hergeleitet werden muss.

## Kontext / Repo
- WordPress-Plugin "curriculr-terminplan" (Schul-Terminplan-Anzeige).
- Workspace-Root: `/Users/julian.wagner/curriculr-planner`
- Plugin-Repo (git): `curriculr-terminplan/` (eigenes git, branch main)
- Hauptdatei: `plugin/gsh-terminplan.php` (groß, ~380KB)
- Repo-Konventionen siehe `curriculr-terminplan/CLAUDE.md`: kein Build-System, kein npm,
  kein Composer. PHP nach jeder Änderung: `php -l plugin/gsh-terminplan.php`.
  Tests: `php tests/curriculr/test-*.php`. Version in 4 Stellen synchron halten +
  Changelog (`gsh_tp_changelog()` + Header-Kommentar). ZIP-Build: 4 PHP-Dateien an
  Root + `assets/` (Rezept in CLAUDE.md), Ablage als `curriculr-terminplan-<ver>.zip`
  im Workspace-Root.

## Problem
Auf iPad/Handy wird der Terminplaner ALS EINGEBETTETE SEITE (iframe) in der IServ-App
angezeigt (Navigations-Ziel "Eingebettet" — soll so bleiben, native Optik). Die
App-Webview (WKWebView im iframe) BLOCKT `window.print()` UND `window.open()`/Popups.
=> Der bestehende PDF-Export (Funktion `gtpPrint()` im JS) funktioniert dort nicht:
- Desktop: versteckter iframe + `iframe.print()` (funktioniert, bleibt)
- 4.16.0 Touch-Zweig: `window.open()` neuer Tab (in IServ-App blockiert!)

Offizielle IServ-Doku bestätigt: kein dokumentierter Weg, aus der App-Webview in
Safari auszubrechen (`iserv://` / `open.iserv.app` gehen nur IN die App). Quellen:
- https://doku.iserv.de/development/links/
- https://doku.iserv.de/manage/system/menuitems/ (Ziel: Eingebettet/Neue Seite/Aktuelle Seite)

## Lösung (Option A — client-seitige PDF-Datei)
Eine Webview kann zuverlässig DATEIEN HERUNTERLADEN. Also: PDF im Frontend per
JS erzeugen (Blob) und über einen `<a download>`-Link / programmatischen Klick
anbieten — kein Druckdialog, kein Popup, läuft im iframe.
- Empfohlene Lib: `jsPDF` + `jspdf-autotable`, als statische Dateien in
  `plugin/assets/js/` vendoren und per `wp_enqueue_script` laden (analog zum
  vorhandenen CSS-Enqueue; verstößt NICHT gegen "kein Build-System", da nur
  statische Assets).
- Tabelle als Vektor-PDF via autoTable (scharf, klein), A4 quer, mehrseitig für
  Quartal-Einzeln UND "alle Quartale".
- VORHER VALIDIEREN (Gerätetest durch Nutzer): funktioniert ein normaler
  Datei-Download im eingebetteten Terminplaner in der IServ-App überhaupt? Falls ja,
  ist Option A sicher. (Wenn nein: Rückfrage, ggf. Server-PDF.)

## Relevante Code-Stellen (Namen, nicht Zeilennummern — die driften)
- `gtpPrint(mode,pdfTitle)`: JS im PHP-String von `gsh_tp_js()`. Baut das A4-Quer-HTML
  (Variablen `CSS` + `body`, `docTitle`/`safeTitle`), Desktop=`iframe.print()`,
  Touch=`window.open`.
- Aufrufer der PDF-Buttons: `gtpPrint("single",title)` und `gtpPrint("all",...)` — im
  Footer-Markup ("📄 Quartal als PDF" / "📄 Alle als PDF").
- Tabellendaten: `gsh_tp_table($index,$qd,$sjs)` rendert `<table class="gt">` mit Spalten
  SW | Mo | Di | Mi | Do | Fr | Hinweise. Kategorien stehen in `#gtp` dataset
  `data-categories` (JSON: slug,label,border-Farbe) — schon im JS genutzt.
- Assets: `plugin/assets/css/`, `plugin/assets/img/`. Neu: `plugin/assets/js/`.

## Designfragen für die Brainstorm-Phase (vor Code klären)
1. Touch/Webview: bestehende gtpPrint-Buttons im Touch-Fall durch echten PDF-Download
   ersetzen, ODER separater Button "PDF herunterladen"? Desktop-iframe-Druck unangetastet?
2. Erkennung "Webview/Touch" wie in 4.16.0 (`pointer:coarse` / `innerWidth<=1024`) oder
   feiner? Soll der Download-Weg auch auf Desktop optional sein?
3. jsPDF-autoTable: woher die Zeilen/Spalten? Aus dem schon gerenderten DOM (`.gt`
   auslesen) — bevorzugt, hält EINE Datenquelle — oder aus data-Attributen?
4. Mehrseitigkeit, Kopf/Fußzeile, Kategorie-Legende, Farben wie im jetzigen Druck-CSS
   nachbilden.
5. Dateiname (Browser-Vorschlag) wie bisher (z.B. "Terminplan Curriculr 2025-26 - Q2").

## Arbeitsweise
Superpowers nutzen: erst `brainstorming` (Design + obige Fragen klären, Visual optional),
dann `writing-plans`, dann `subagent-driven-development`. Feature-Branch (nicht direkt
main). Version: minor-Bump. Tests + `php -l` + ZIP-Build am Ende. iPad-Verifikation ist
headless nicht möglich -> als Human-Schritt ausweisen.

## Akzeptanzkriterien
- In der eingebetteten IServ-App-Webview (iPad) erzeugt ein Button eine PDF-DATEI, die
  der Nutzer speichern/teilen kann — ohne Druckdialog, ohne Popup.
- Quartal-Einzeln und "alle Quartale" funktionieren.
- Desktop-Verhalten unverändert oder verbessert, nicht verschlechtert.
- PHP-Syntax ok, alle `tests/curriculr/*.php` grün, ZIP gebaut.

## Bezug
Folgt auf v4.16.0 (iPad volle Tabelle + Touch-PDF via window.open). Der window.open-Weg
greift im eingebetteten App-Webview nicht — dieser Brief löst genau diese Lücke.
Siehe `2026-06-14-feedback-tablet-pdf-design.md` (Spec v4.16.0).
