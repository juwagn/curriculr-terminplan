# Plan: Mobile Redesign der Quartalsansicht (Kiosk, IServ-App)

Status: Entwurf, wartet auf Bestätigung. Es wurde noch kein Code geändert.

## Ausgangslage
- Dunkelmodus: Logo (immer `curriculr-logo-auf-hell.svg`), Wochenleiste, Heute-Karte und mehrere Schaltflächen sind unleserlich.
- Hellmodus: Kopf belegt zwei Drittel der ersten Bildschirmseite, Chips und Reiter werden angeschnitten, Emoji in der Wochenleiste, gesperrte Versalien, schwebender Heute-Knopf verdeckt Inhalt, Kategorien nur über Farbe.
- Betrifft Breite unter 768 px. Desktop bleibt unverändert.

## Zielbild
Mockup: `redesign.png` (hell und dunkel, 390 px). Kernpunkte:
1. Kopf in einer Zeile: Bildmarke, „Terminplan 26/27“, Stand, drei Symbolknöpfe (Suche, Filter mit Zähler, Hilfe), je 44 px.
2. Quartale als vier gleich breite Reiter, Unterstrich in Marine, kein Scrollen.
3. Wochenleiste: Zurück, „SW 10 – SW 11“, Textknopf „Heute“, Weiter. Kein Emoji, kein schwebender Knopf.
4. Agenda: Schulwochen-Kopf in Satzschreibung, links Tag und Datum, rechts Terminkarten mit 4-px-Streifen und Kategorie-Marke aus Text und Farbe. Heute: Marine-Rahmen.
5. Kalender abonnieren, PDF und Feedback hinter einer Zeile „Mehr“.
6. Alles über `--cl-*`-Tokens, Dunkelmodus durch die Token-Umkehrung.

## Schritte
0. Zweig `feat/mobile-redesign-quartal` anlegen.
1. Dunkelmodus-Audit: alle festen Farben und alten Variablen (`--primary-*`, `--accent-*`, Hex) in `gsh-terminplan.css` finden, die die Quartalsansicht betreffen. Liste in den Pull Request.
2. Kategorie-Farben als `--cl-product-*` (hell und dunkel) mit Kontrastprüfung (Text ≥ 4,5:1). Das Skript `design/check-contrast.mjs` liegt in diesem Repo nicht vor; ich prüfe mit einem eigenen Skript.
3. CSS für ≤ 767 px: Kopf, Reiter, Wochenleiste, Agenda, Mehr-Zeile. Versalien und Sperrung entfernen.
4. Markup (siehe offene Entscheidung 1): Symbolknöpfe, Mehr-Bereich, Kategorie-Text.
5. Logo: auf Mobil nur Bildmarke `curriculr-icon.svg`; auf Desktop zwei Wortmarken, per CSS nach Modus umgeschaltet.
6. Emoji „👉 Heute“ durch Text und Lucide-Symbol ersetzen.
7. Prüfen: Chromium-Aufnahmen bei 390 px hell und dunkel, Tastaturfokus, `php -l` auf alle vier Dateien, alle Tests unter `tests/curriculr/`, Drucken in Edge und Safari (nicht lokal prüfbar, siehe Risiko).
8. Version 4.43.0 an vier Stellen, Changelog mit `DESIGN`, `UX`, `FIX`; `CHANGELOG.md` unter `[Unreleased]`.
9. Anleitung unter `docs/anleitung/` ergänzen (Ordner fehlt bisher; Familienregel verlangt ihn).

## Offene Entscheidungen
1. `CLAUDE.md` verbietet Änderungen an Tabellen-Rendering, PDF-Export, Änderungsbenachrichtigung und der Grundstruktur von `gsh_tp_js()`/`gsh_tp_css()`. Die Kategorie-Marke auf der Karte braucht ein zusätzliches Textelement im Zeilen-Markup. Alternative: nur CSS, dann bleibt die Kategorie auf Karten Farbe plus Streifen.
2. Soll die IServ-Ansicht der Systemeinstellung folgen (Dunkelmodus) oder immer hell bleiben?

## Risiken
- Die Änderungen betreffen Darstellung, keine Daten, Rechte oder Exporte. Kein Sicherheitshinweis nötig, solange der Kiosk-Token-Ablauf unverändert bleibt.
- Safari-Druck und die Darstellung im IServ-iframe lassen sich nur auf dem Gerät vollständig prüfen.
