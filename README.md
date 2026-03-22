# GSH Terminplan Dashboard

WordPress-Plugin der Gesamtschule Horst zur interaktiven Anzeige des Schulkalenders aus IServ-iCal-Feeds.

**Aktuelle Version: 3.17.0**

---

## Features

- **Quartalansicht** – Desktop-Tabelle und Mobile-Agenda-Ansicht
- **Kategorie-Filter** – farbige Badges pro Kategorie, filterbar per Klick
- **Volltextsuche** – Echtzeit-Filterung aller Termine
- **Dark Mode** – manuell oder automatisch per System-Einstellung
- **PDF-Export** – einzelnes Quartal oder gesamtes Schuljahr
- **Feedback-Funktion** – direkt per E-Mail mit Admin-Log
- **Kiosk-Modus** – für IServ-Einbettung ohne Navigationsleiste
- **Mehrere Schuljahre** – bis zu 5 Profile (Schuljahre) parallel verwaltbar
- **Entwurf-Modus** – neues Schuljahr vorbereiten, nur für Admins sichtbar
- **Hilfe-Overlay** – erklärt Bedienelemente auf Klick, kein externes CDN nötig

---

## Anforderungen

- WordPress 6.0 oder höher
- PHP 8.0 oder höher
- IServ-iCal-Feed (HTTPS)

---

## Installation

1. Den Ordner `plugin/` als `gsh-terminplan` nach `wp-content/plugins/` kopieren
2. Plugin in WordPress aktivieren
3. Einstellungen unter **Einstellungen → GSH Terminplan** konfigurieren
4. iCal-URL des IServ-Kalenders eintragen und Cache aufbauen
5. Shortcode auf einer Seite einfügen:

```
[gsh_terminplan]
```

---

## Shortcode-Parameter

| Parameter | Beispiel | Beschreibung |
|---|---|---|
| `schuljahr` | `schuljahr="sj_2026_27"` | Bestimmtes Schuljahr anzeigen |
| `schuljahr` | `schuljahr="entwurf"` | Entwurfs-Profil anzeigen (nur Admins) |

URL-Parameter `?sj=` überschreibt den Shortcode-Wert.

---

## Kategorien

Standardkategorien (anpassbar im Admin):

| Kategorie | Beschreibung |
|---|---|
| Jahrgang 5/6 | Veranstaltungen für Jg. 5 und 6 |
| Jahrgang 7/8 | Veranstaltungen für Jg. 7 und 8 |
| Jahrgang 9/10 | Veranstaltungen für Jg. 9 und 10 |
| Oberstufe | Veranstaltungen für EF, Q1, Q2 |
| Inklusion | IFÖ, AL-SuS, Förderpläne, AOSF etc. |
| Feiertage | Gesetzliche Feiertage und schulfreie Tage |
| Konferenzen | Lehrerkonferenzen, Zeugnisausgaben etc. |

Labels, Farben und Keywords sind im Admin unter **Kategorien** frei konfigurierbar.

---

## Projektstruktur

```
plugin/
  gsh-terminplan.php          # gesamtes Plugin (~7000 Zeilen)
  assets/css/
    gsh-terminplan.css        # gesamtes CSS

konverter/
  GSH_Terminplan_Konverter.html   # standalone Excel→iCal-Konverter
```

---

## Entwicklung

- Kein Build-System – Änderungen direkt in den Quelldateien
- PHP-Syntaxprüfung nach jeder Änderung: `php -l plugin/gsh-terminplan.php`
- CSS ausschließlich in `plugin/assets/css/gsh-terminplan.css` – kein Inline-CSS

### Versioning

Beim Release müssen vier Stellen synchron gehalten werden:
1. Plugin-Header: `Version: X.Y.Z`
2. `define('GSH_TP_VERSION', 'X.Y.Z')`
3. `gsh_tp_changelog()` – neuen Eintrag oben einfügen
4. Changelog im Plugin-Header-Kommentar

---

## Changelog (Auszug)

### 3.17.0
- [FEATURE] Frontend UI-Überarbeitung: zweizeiliger Header, Suche in voller Breite
- [FEATURE] Quartal-Tab mit Dot-Indikator für das aktuelle Quartal
- [FEATURE] Filter-Bar mit Mobile-Toggle (aufklappbar)
- [FEATURE] Footer in Aktions- und Meta-Bereich aufgeteilt
- [FEATURE] Theme-Switcher und Feedback-Button nutzen SVG-Icons statt Emojis
- [FEATURE] Entwurfs-Banner via CSS-Klasse statt Inline-Style

### 3.16.1
- [FEATURE] Neue Standardkategorie „Inklusion"

### 3.16.0
- [FEATURE] Slug wird beim Umbenennen einer Kategorie automatisch synchronisiert
- [FEATURE] Keywords pro Kategorie im Admin editierbar
- [FEATURE] Shepherd.js entfernt – neues schlichtes Hilfe-Overlay (kein CDN)

### 3.15.0
- [FEATURE] Kategorien-System neu aufgebaut mit `GSH_TP_DEFAULT_CATEGORIES`
- [FEATURE] Keyword-Matching auf Titel, Beschreibung, Ort und CATEGORIES-Feld
- [FEATURE] Admin-UI: Kategorie-Editor mit Farbwähler und Vorschau

### 3.12.0
- [FEATURE] Feedback per AJAX + `wp_mail()` mit Admin-Log

### 3.9.0
- [FEATURE] Dark Mode mit Hell/Dunkel/System-Umschalter

---

## Lizenz

Privates Schulprojekt – Gesamtschule Horst
