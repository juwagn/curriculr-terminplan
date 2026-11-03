# Changelog

Alle nennenswerten Änderungen an **Curricu:lr Terminplan** stehen hier.
Das Format folgt [Keep a Changelog](https://keepachangelog.com/de/1.1.0/), die Versionierung folgt [Semantic Versioning](https://semver.org/lang/de/).
Frühere Änderungen stehen im Plugin unter **Einstellungen → Schul-Terminplan** (Versionsverlauf).

## [Unreleased]

## [4.45.1] – 2026-11-03

### Behoben
- In der Jahresansicht war der Juli abgeschnitten. Der Wandplaner passt jetzt immer in die Seitenbreite. Unter 1024 px erscheint der Monatskalender.
- Der Jahreskalender lief beim Drucken auf mehrere Seiten. Er wird jetzt zuverlässig auf eine Seite A4 quer eingepasst.

## [4.45.0] – 2026-11-03

### Neu
- **Jahr** zeigt einen Wandplaner: zwölf Monate als Spalten, die Tage 1 bis 31 als Zeilen. Jede Zelle nennt Wochentag, Datum und alle Termine des Tages, farbig nach Kategorie. Die Schulwoche steht am Montag. Ein Klick öffnet die Termine des Tages.
- **Exportieren → Jahreskalender drucken** druckt das Schuljahr mit allen Terminen auf eine Seite A4 quer. Im Druckdialog lässt es sich auf A3 skalieren.
- Die Quartalsübersicht in der Seitenleiste zeigt Montag bis Sonntag. Jeder Tag ist wählbar und springt zum Tag in Liste oder Raster. Samstag und Sonntag springen zur Anmerkung der Woche.

### Behoben
- Im Raster ragten Termintitel aus der Kachel. Sie brechen jetzt um.
- Im Quartal-Reiter lag „aktuell“ auf einem verzerrten blauen Oval. Es erscheint jetzt als Pille.
- PDF-Kopf und Dateiname nannten fest das Schuljahr 2025/26. Das Schuljahr kommt jetzt aus den Daten, die Quelle heißt „Curricu:lr Planer“.

### Geändert
- Die bisherige Matrix „Monate × Tage“ in der Jahresansicht entfällt.

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
