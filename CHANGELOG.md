# Changelog

Alle nennenswerten Änderungen an **Curricu:lr Terminplan** stehen hier.
Das Format folgt [Keep a Changelog](https://keepachangelog.com/de/1.1.0/), die Versionierung folgt [Semantic Versioning](https://semver.org/lang/de/).
Frühere Änderungen stehen im Plugin unter **Einstellungen → Schul-Terminplan** (Versionsverlauf).

## [Unreleased]

## [4.44.0] – 2026-11-03

### Neu
- Desktop mit drei Darstellungen und Umschalter: **Liste** (Standard), **Raster** (Tabelle) und **Jahr**. Die Wahl bleibt im Browser gespeichert.
- Liste mit Seitenleiste: Quartalsübersicht mit Sprung zur Woche, Kategorien als Liste mit Anzahl, Gruppen.
- Jahresansicht neu: Monate × Tage mit Popover je Tag und „In der Liste öffnen“. Auf dem Handy ein Monatskalender mit Tagesliste (ersetzt die Heatmap).
- Mobile Ansicht als kompakte Liste: Kopf in einer Zeile, Suche als Symbol, Termine als Zeilen mit Kategorie als Punkt und Wort.

### Geändert
- Werkzeugleiste neu gegliedert: Quartal-Reiter mit Zeitspanne, Umschalter für die Darstellung, Menü **Exportieren**, Knopf **Heute**.
- Vergangene Wochen sind eingeklappt statt abgeblendet.
- Gestaltung an den Planner angeglichen: weiße Kopfzeile mit Wortmarke, Termine als getönte Blöcke, heller Tabellenkopf.
- Die Erscheinung folgt der Geräteeinstellung (hell oder dunkel). Der Theme-Schalter entfällt.
- Standardkategorie „Konferenzen“ ist graublau statt violett. Die Kategorien-Seite weist auf violette Kategorien hin.
- Auf dem Handy sind die PDF-Knöpfe ausgeblendet, weil das Drucken in der IServ-App nicht funktioniert.

### Behoben
- Vergangene Wochen waren kaum lesbar (Kontrast unter 2:1).
- Lange Termine wie Ferien fehlten in der Liste.
- Im Titel stand fest „Ihre Schule“, jetzt steht der Schulname.
- Quartalstitel passt zu den Quartalsgrenzen aus dem Planner.
- Dunkelmodus: Wochenleiste und Wochenköpfe sind lesbar, das Logo wechselt je Modus.
- Familienzeile „Teil der Curricu:lr-Familie“ im Fuß ergänzt.
