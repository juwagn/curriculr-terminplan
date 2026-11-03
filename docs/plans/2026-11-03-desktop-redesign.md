# Plan: Desktop-Redesign mit Liste, Raster und Jahr

Status: umgesetzt in Version 4.44.0. Grundlage waren die freigegebenen Mockups.

## Entscheidungen
- **Darstellungen:** Liste (Standard), Raster (Tabelle), Jahr. Umschalter in der Werkzeugleiste, Wahl im Browser gespeichert.
- **Werkzeugleiste:** links die Zeit (Quartal-Reiter mit Zeitspanne), rechts Darstellung, Menü Exportieren und die eine Marine-Hauptaktion Heute. In der Jahresansicht entfallen die Quartal-Reiter.
- **Seitenleiste** nur in der Liste: Quartalsübersicht, Kategorien mit Anzahl, Gruppen. In Raster und Jahr wird sie zur Filterleiste.
- **Gestaltung** nach dem Planner (weißer Kopf, helle Tabellenköpfe, getönte Termin-Blöcke mit Streifen und Kategorie-Wort, gelbe Hinweisbox). Familienregeln haben Vorrang: kein Marine-Kopf, keine Versalien mit Sperrung, kein Violett.
- **Vergangene Wochen:** eingeklappt statt abgeblendet (Kontrast).
- **Theme-Schalter** entfällt, die Erscheinung folgt dem Gerät.
- **Violett:** Standardkategorie „Konferenzen“ graublau; Hinweis im Admin; bestehende Schulen werden nicht still umgefärbt.
- **PDF:** unverändert, nur das Kategorie-Wort und der Wochenzeitraum werden aus der Tabelle für das PDF entfernt. Mobil sind die PDF-Knöpfe ausgeblendet.

## Umsetzung
- PHP: Werkzeugleiste, Seitenleiste, Wochenattribute, neue Jahresansicht, Quartalsübersicht, Hilfsfunktionen.
- JS: `gtpSetView`, Jahres-Popover, Wochen-Sprung, Heute, vergangene Wochen.
- CSS: gemeinsamer Teil, Desktop ab 768 px, schmaler Desktop, mobil.
- Tests: `test-desktop-views.php`, `test-quartal-label.php`.

## Risiko
Keine Änderung an Login, Rechten, Datenhaltung oder Exporten. Die Jahresdaten für das Popover stehen als maskiertes JSON im Attribut; Texte werden im Browser nur als Text eingesetzt.
