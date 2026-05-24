# Curricu:lr Terminplan

**Digitaler Schulkalender für WordPress — aus IServ direkt auf die Schulwebsite.**

Version **4.3.4** · PHP 8.0+ · WordPress 6.0+ · GPL v2

[![Ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/X8X61ZEMZ7)

---

## Was ist das?

Curricu:lr Terminplan liest den IServ-Kalender eurer Schule als iCal-Feed und stellt ihn als interaktiven Terminplan auf der WordPress-Website bereit. Kein manuelles Einpflegen — Termine aus IServ erscheinen automatisch auf der Schulwebsite.

Das Projekt besteht aus vier Teilen:

| Teil | Was | Wo |
|---|---|---|
| **WordPress-Plugin** | Zeigt den Terminplan auf der Schulwebsite | `plugin/` |
| **Konverter-Tool** | Wandelt Excel-Terminpläne in iCal-Dateien um | `konverter/` |
| **Excel-Vorlage + Scripts** | Generiert die Stundenplanvorlage für Excel | `scripts/` + `website/downloads/` |
| **Website** | Projektwebsite mit Anleitung und Downloads | `website/` |

---

## Features

- **Quartalansicht** — Tabellenansicht auf Desktop, Agenda-Karten auf Mobile
- **Jahresansicht** — Kalenderraster Sep–Jul, ein Blick auf das gesamte Schuljahr
- **Kategorie-Filter** — farbige Badges, horizontal scrollbar, immer sichtbar
- **Volltextsuche** — Echtzeit-Filterung über alle Termintitel und -beschreibungen
- **Dark Mode** — manuell oder automatisch per System-Einstellung
- **PDF-Export** — einzelnes Quartal oder gesamtes Schuljahr, mit Lade-Feedback
- **Feedback-Funktion** — Termine direkt per E-Mail kommentieren, Log im Admin
- **Kiosk-Modus** — Einbettung in IServ ohne WordPress-Login, per Token-URL
- **Entwurf-Kiosk** — Vorschau-Link für das Schulleitungsteam, bevor der Plan freigegeben wird
- **Mehrere Schuljahre** — bis zu 5 Profile parallel, einfacher Schuljahreswechsel
- **Entwurf-Modus** — neues Schuljahr vorbereiten, erst nach Beschluss veröffentlichen
- **Hilfe-Overlay** — erklärt alle Bedienelemente, kein CDN nötig

---

## Teil 1: WordPress-Plugin

### Anforderungen

- WordPress 6.0 oder höher
- PHP 8.0 oder höher
- IServ mit iCal-Feed (HTTPS-URL des Schulkalenders)

### Installation

1. Den Ordner `plugin/` als `gsh-terminplan` nach `wp-content/plugins/` kopieren
2. Plugin in WordPress aktivieren
3. **Einstellungen → Schul-Terminplan** öffnen
4. Neues Schuljahr-Profil anlegen (Tab **Schuljahr anlegen**)
5. iCal-URL des IServ-Kalenders eintragen
6. **Cache aufbauen** klicken — Termine werden geladen
7. Shortcode auf einer Seite einfügen:

```
[gsh_terminplan]
```

### Shortcode-Parameter

| Parameter | Beispiel | Beschreibung |
|---|---|---|
| _(ohne)_ | `[gsh_terminplan]` | Aktives Schuljahr, aktuelles Quartal |
| `quartal` | `quartal="2"` | Bestimmtes Quartal (1–4) |
| `quartal` | `quartal="alle"` | Alle Quartale mit Tabs |
| `schuljahr` | `schuljahr="sj_2026_27"` | Bestimmtes Schuljahr-Profil |
| `schuljahr` | `schuljahr="entwurf"` | Entwurfs-Profil anzeigen (nur Admins) |

URL-Parameter `?sj=` überschreibt den Shortcode-Wert (Schuljahr-Umschalter im Frontend).

### Mehrere Schuljahre (Profile)

Das Plugin verwaltet bis zu 5 Schuljahr-Profile. Jedes Profil hat:
- eigene iCal-URL
- eigene Quartalsgrenzen
- Status **Beschlossen** oder **Entwurf**

Nur das als **aktiv** markierte, beschlossene Profil erscheint im Shortcode ohne Parameter.  
Entwurf-Profile sind nur für WordPress-Admins sichtbar — bis zur Freigabe über den Entwurf-Kiosk.

### Kategorien

Termine werden automatisch per Keyword-Matching kategorisiert. Standardkategorien:

| Kategorie | Typische Keywords |
|---|---|
| Jahrgang 5/6 | Jg. 5, Jg. 6, Klasse 5, Klasse 6 |
| Jahrgang 7/8 | Jg. 7, Jg. 8 |
| Jahrgang 9/10 | Jg. 9, Jg. 10 |
| Oberstufe | EF, Q1, Q2 |
| Inklusion | IFÖ, AL-SuS, Förderplan, AOSF |
| Feiertage | Feiertag, schulfrei, Ferientag |
| Konferenzen | Lehrerkonferenz, Zeugnisausgabe |

Labels, Farben und Keywords sind unter **Einstellungen → Kategorien** frei konfigurierbar.

---

## Teil 2: Kiosk-Modus (IServ-Einbettung)

Der Kiosk-Modus zeigt den Terminplan ohne WordPress-Navigation — ideal für IServ-Infoboards oder -Navigationseinträge.

### Einrichtung Kiosk

1. **Page-Template in Theme kopieren:** `plugin/page-terminplan-kiosk.php` → aktiver Theme-Ordner  
   _(z. B. `wp-content/themes/twentytwentyfour/`)_
2. **Neue WordPress-Seite** anlegen → Vorlage **„Terminplan Kiosk"** wählen → veröffentlichen
3. **Kiosk-Token generieren:** Admin → Kiosk & System → „Zufälligen Token erzeugen" → speichern
4. IServ-Domain eintragen (verhindert Einbettung von fremden Seiten)
5. Die angezeigte Kiosk-URL in IServ als Navigationslink oder iFrame hinterlegen

**Sicherheit:** Token-Vergleich ist timing-sicher (`hash_equals`). Rate-Limiting: max. 10 Fehlversuche pro IP pro Stunde.

---

## Teil 3: Entwurf-Kiosk (Vorschau für Schulleitungsteam)

> **Neu in v4.1.0**

Ermöglicht es dem Schulleitungsteam, einen Entwurfs-Terminplan **vor der offiziellen Freigabe** einzusehen — ohne WordPress-Login, per einfachem Link.

### Einrichtung Entwurf-Kiosk

**Schritt 1: Template ins Theme kopieren**

```
plugin/page-terminplan-entwurf.php
→ wp-content/themes/DEIN-THEME/page-terminplan-entwurf.php
```

**Schritt 2: WordPress-Seite anlegen**

WordPress-Backend → Seiten → Neu erstellen:
- Titel: z. B. „Terminplan Entwurf"
- Vorlage: **Terminplan Entwurf-Vorschau** auswählen
- Seite veröffentlichen (die Seite ist trotzdem nur mit Token erreichbar)

**Schritt 3: Entwurf-Token generieren**

Admin → **Einstellungen → Schul-Terminplan → Kiosk & System**:
- Abschnitt **Entwurf-Vorschau** → „Zufälligen Token erzeugen"
- Einstellungen speichern
- Die generierte URL wird automatisch angezeigt

**Schritt 4: URL teilen**

Die URL hat das Format:
```
https://eure-schule.de/terminplan-entwurf/?token=abc123...
```

Diese URL per E-Mail oder Chat an das Schulleitungsteam senden.

**Was sieht das Team?**

- Den vollständigen Entwurfs-Terminplan mit allen Funktionen (Filter, Suche, Dark Mode)
- Ein prominentes gelbes Banner: **„Entwurf — noch nicht beschlossen"**
- Kein WordPress-Login nötig

**Wann wird der Entwurf öffentlich?**

Erst wenn das Profil im Admin auf **Beschlossen** gesetzt und als aktives Schuljahr markiert wird. Bis dahin bleibt es für normale Besucher unsichtbar.

**Sicherheit:** Separater Token vom Live-Kiosk. Timing-sicherer Vergleich (`hash_equals`). Rate-Limiting: max. 10 Fehlversuche pro IP pro Stunde.

---

## Teil 4: Konverter-Tool

Das Konverter-Tool wandelt eine ausgefüllte Excel-Terminplan-Vorlage direkt im Browser in eine `.ics`-Datei um — die dann in IServ als Kalender importiert werden kann.

**Verwendung:**

1. `konverter/Terminplan_Konverter.html` lokal im Browser öffnen (Doppelklick genügt — kein Server nötig)
2. Fertig ausgefüllte Excel-Vorlage hochladen
3. Vorschau prüfen
4. ICS herunterladen und in IServ importieren

Das Tool läuft vollständig im Browser, keine Daten verlassen den Rechner.

---

## Teil 5: Excel-Vorlage und Scripts

Die Schulwochen-Vorlage für Excel wird per Python-Script generiert und enthält alle Schulwochen des Schuljahres als vorgefertigte Tabelle.

### Vorlage neu generieren

```bash
# Python 3 + openpyxl benötigt
pip install openpyxl

python scripts/build_excel_template.py
# Ergebnis: website/downloads/Terminplan_Schulwochen_Vorlage.xlsx
```

### Ferien und Eckdaten eintragen

In der generierten Vorlage den Tab **Ferien** öffnen:

| Zelle | Inhalt |
|---|---|
| B3–C3 | Herbstferien (erster / letzter Tag) |
| B4–C4 | Weihnachtsferien |
| B5–C5 | Osterferien |
| B6–C6 | Pfingstferien (optional) |
| B7–C7 | Sommerferien |
| B10 | Erster Schultag (SW 00) |
| B11 | Erster Unterrichtstag (SW 01) |
| B12 | Letzter Schultag |

### Verifikation

```bash
python scripts/recalc.py
```

Prüft: keine Blattsperren, echte Datumswerte, 42 Schulwochen-Header, Ferien-Skip korrekt.

---

## Jährlicher Workflow: Schuljahreswechsel

Kurzfassung — vollständige Anleitung in [`docs/schuljahreswechsel-anleitung.md`](docs/schuljahreswechsel-anleitung.md).

```
1. Excel-Vorlage generieren
   python scripts/build_excel_template.py

2. Ferien und Termine eintragen (Tab "Ferien", Tab "Terminplan")

3. Vorlage per Konverter in ICS umwandeln
   konverter/Terminplan_Konverter.html im Browser öffnen

4. ICS in IServ importieren (als neuen Schulkalender)

5. Im Plugin: neues Profil als Entwurf anlegen, iCal-URL des neuen Kalenders eintragen

6. Entwurf-Kiosk-Link an Schulleitung senden → Feedback einholen

7. Nach Beschluss: Profil auf "Beschlossen" setzen, als aktives Schuljahr markieren

8. Altes Schuljahr-Profil deaktivieren oder löschen
```

---

## Projektstruktur

```
plugin/
  gsh-terminplan.php              # WordPress-Plugin (~7000 Zeilen, alles in einer Datei)
  page-terminplan-entwurf.php     # Page-Template: Entwurf-Kiosk (ins Theme kopieren)
  assets/
    css/gsh-terminplan.css        # Gesamtes CSS (kein Inline-CSS im PHP)
    css/design-tokens.css         # Design-Token-System (Curricu:lr Farbpalette)

konverter/
  Terminplan_Konverter.html       # Standalone Excel→iCal-Konverter (Browser)

scripts/
  build_excel_template.py         # Hauptscript: generiert fertige Vorlage
  patch_rows.py                   # Grundstruktur (einmalig bei Strukturänderung)
  patch_xlsx.py                   # Datumsformeln setzen
  recalc.py                       # Verifikation der Formeln
  ferien_2026_27.json             # Feriendaten als JSON

website/
  index.html                      # Startseite (GitHub Pages)
  anleitung.html                  # Installationsanleitung
  download.html                   # Download-Seite
  downloads/
    Terminplan_Schulwochen_Vorlage.xlsx   # Aktuelle Vorlage
    Terminplan_Konverter.html             # Konverter-Tool (Mirror)
    gsh-terminplan.zip                    # Plugin-ZIP

docs/
  schuljahreswechsel-anleitung.md # Detaillierte Jahresanleitung
  sop-terminplan-schuljahr.md     # Betriebsanweisung

tasks/
  lessons.md                      # Gelernte Lektionen (für KI-Assistenz)
```

---

## Entwicklung

Kein Build-System. Änderungen direkt in den Quelldateien, dann in WordPress hochladen.

### PHP-Syntaxprüfung (nach jeder Änderung Pflicht)

```bash
php -l plugin/gsh-terminplan.php
```

Erwartete Ausgabe: `No syntax errors detected in plugin/gsh-terminplan.php`

### CSS-Regel

Alles CSS **ausschließlich** in `plugin/assets/css/gsh-terminplan.css`. Kein Inline-CSS in PHP-Strings oder Heredocs.

### Versionierung — alle vier Stellen synchron halten

Bei jedem Release müssen genau diese vier Stellen auf `X.Y.Z` gesetzt werden:

| # | Datei / Stelle | Beispiel |
|---|---|---|
| 1 | Plugin-Header: `Version:` | `Version: 4.3.0` |
| 2 | PHP-Konstante | `define('GSH_TP_VERSION', '4.3.0')` |
| 3 | `gsh_tp_changelog()` — neuer Eintrag oben | `'version' => '4.3.0'` |
| 4 | Plugin-Header-Kommentar `Changelog X.Y.Z:` | neuer Block oben |

**Bump-Regel:** Bugfix → Patch, neues Feature → Minor, Breaking Change → Major.

### Architektur-Übersicht

| Sektion | Bereich | Schlüsselfunktionen |
|---|---|---|
| 0 | Profil-Hilfsfunktionen | `gsh_tp_get_profiles`, `gsh_tp_active_profile_id` |
| 1 | Admin-UI + Tab-System | `gsh_tp_settings_page`, `gsh_tp_render_profile_tab`, `gsh_tp_render_system_tab` |
| 2 | iCal-Abruf / Cache | `gsh_tp_fetch_ical`, `gsh_tp_fetch_sync`, `gsh_tp_do_refresh` |
| 3 | Schulwochen / Quartale | `gsh_tp_quartale`, `gsh_tp_current_q` |
| 5 | Shortcode | `gsh_tp_shortcode` |
| 9 | Deinstallation | Profil-aware Cleanup |

**Nicht anfassen:** iCal-Parser, Date-Index, Tabellen-Rendering, PDF-Export, Change-Notification-System.

---

## Changelog

### 4.3.4
- [FIX] Entwurf-Vorschau + IServ-Kiosk: je eigenes Formular mit direktem POST-Handler — kein `options.php` mehr
- [UX] Kiosk & System Tab: zwei klar getrennte Sektionen mit eigenem Speichern-Button
- [FEATURE] `theme_page_templates`-Filter: Vorlage „Terminplan Entwurf-Vorschau" erscheint automatisch im WP-Seiten-Editor
- [CHORE] PDF-Strings: GSH → Curriculr (Logo-Mark, Footer, Dateinamen)

### 4.3.3
- [FIX] Entwurf-Token in `gsh_tp_options`-Gruppe — gleicher Form-Submit wie IServ-Kiosk

### 4.3.2
- [FIX] Entwurf-Token-Formular: direkter POST-Handler statt `options.php` — kein Redirect auf „Alle Einstellungen" mehr

### 4.3.1
- [FIX] Entwurf-Vorschau: fehlender `<form>`-Wrapper — Token wird jetzt gespeichert
- [FIX] Entwurf-Token in eigene Option-Gruppe — IServ-Kiosk-Daten bleiben beim Speichern erhalten
- [FEATURE] `template_include`-Filter: Plugin-Template ohne Theme-Copy nutzbar
- [FEATURE] Button „Vorschau-Seite automatisch erstellen" im Admin

### 4.3.0
- [UX] Header: einzeilig, Suche inline, keine redundante Überschrift
- [UX] Jahresansicht-Toggle in sticky Tab-Leiste integriert — Rückkehr zur Quartalansicht immer sichtbar
- [UX] Filter-Bar: horizontal scrollbar (kein Wrap), immer sichtbarer Toggle-Button
- [UX] Tab-Design: Unterstrich-Indikator statt Navy-Pill-Hintergrund
- [UX] Hilfe-Button: Pill mit „Hilfe"-Label statt reines Icon
- [UX] PDF-Buttons: Lade-Feedback mit `disabled`-State und reduzierter Deckkraft für 3,5 s
- [FIX] `border-left`-Antipattern: alle 8+ Instanzen durch Full-Border ersetzt
- [FIX] Schriftgrößen auf Event-Cards und Agenda-Karten erhöht (≥ 0,8 rem)
- [FIX] Kategorie-Fallback-Farbe von `#6c757d` auf `#94a3b8` (höherer Kontrast auf Pastel-Hintergrund)
- [FIX] Admin-Notices: `border-left` → `border` mit `border-radius`

### 4.2.0
- [FEATURE] Jahresansicht: Sep–Jul-Kalenderraster (`gsh_tp_yearview()`, `gtpViewToggle()`)
- [UX] `gtpApply()` erweitert: Jahresansicht wird bei Quartalswechsel korrekt ausgeblendet

### 4.1.0
- [FEATURE] Entwurf-Kiosk: Token-gesicherte Vorschau-Seite für das Schulleitungsteam
- [SECURITY] `gsh_tp_check_draft_kiosk_access()`: timing-sicherer Vergleich + Rate-Limiting (10/h/IP)
- [UX] Admin Kiosk & System Tab: neue Entwurf-Vorschau-Sektion mit Token-Generator und URL-Anzeige

### 4.0.0
- [DESIGN] Curricu:lr-Designsystem: Navy-Primärfarbe, Inter-Font, neue Markenidentität
- [DESIGN] Plugin-Frontend: vollständige CSS-Neuschrift mit Design-Tokens
- [INFRA] `design-tokens.css` als separate Datei mit Cache-Busting

### 3.17.0
- [UX] Header zweizeilig, Suche in voller Breite, Quartal-Dot-Indikator
- [UX] Filter-Bar mit Mobile-Toggle, Footer in Aktions- und Meta-Bereich
- [UX] Theme-Switcher und Feedback-Button mit SVG-Icons

### 3.16.0 – 3.16.1
- [FEATURE] Neue Standardkategorie „Inklusion"
- [FEATURE] Keywords pro Kategorie im Admin editierbar
- [FEATURE] Eigenes Hilfe-Overlay (kein CDN)

### 3.15.0
- [FEATURE] Kategorien-System mit `GSH_TP_DEFAULT_CATEGORIES` und Keyword-Matching

### 3.12.0
- [FEATURE] Feedback per AJAX + `wp_mail()` mit Admin-Log

### 3.9.0
- [FEATURE] Dark Mode mit Hell/Dunkel/System-Umschalter

### 3.5.0
- [FEATURE] Kiosk-Modus mit Token-Authentifizierung und Rate-Limiting

---

## Lizenz

GPL v2 or later — freie Nutzung, Weitergabe und Modifikation unter gleicher Lizenz.
