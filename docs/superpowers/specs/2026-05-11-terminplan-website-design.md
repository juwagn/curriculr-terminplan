# Design Spec: Terminplan Website v2

**Datum:** 2026-05-11
**Status:** Genehmigt
**Scope:** 4 HTML-Seiten auf GitHub Pages, bestehende Designsprache erweitern

---

## Kontext

Das WordPress-Plugin und der Excel-Konverter sollen anderen Schulen im Schulbezirk zugänglich gemacht werden. Der Entwickler handelt als Privatperson, nicht als Unternehmen. Ziel: Andere Schulen können das Plugin kostenlos nutzen, übernehmen aber vollständige Eigenverantwortung.

---

## Zugangsmodell

- GitHub-Repo: **öffentlich** (GPL v2 sowieso kompatibel)
- Download: erst nach Click-through-Disclaimer aktiv
- Hosting: **GitHub Pages** (`website/` Ordner als Quelle)
- Kein Invite-Management, kein Login

---

## Dateistruktur

```
website/
  index.html        # Landing Page (erweitert)
  anleitung.html    # Schritt-für-Schritt Anleitung
  download.html     # Disclaimer + Download-Button
  impressum.html    # Impressum + Haftungsausschluss
  downloads/
    terminplan.zip  # Plugin-ZIP (manuell aktualisiert oder via GH Release)
```

---

## Design-System

Bestehende Tokens aus `website/index.html` — keine neuen einführen:

| Token | Wert |
|---|---|
| Font | `Plus Jakarta Sans` |
| Primary | `--teal-700: #0f766e` / `--teal-600: #0d9488` |
| Accent / CTA | `--amber-500: #f59e0b` |
| Background | `--slate-50: #f8fafc` |
| Surface | `#ffffff` |
| Text | `--slate-900: #0f172a` |
| Radius Cards | `--r-lg: 24px` |
| Shadow | `--shadow-md` (Teal-getönt) |

Absolut verboten: Glassmorphism, Gradienttexte (`background-clip: text`), identische Card-Grids ohne Variation, `border-left`-Akzente.

Warnboxen: Amber-Hintergrund (`--amber-50` + `--amber-500` Border), kein roter Alert-Stil.

---

## Seiten

### 1. `index.html` — Landing Page (Erweiterungen)

Bestehend bleibt unverändert: Nav, Hero, 3-Card Features, Footer.

**Neue Sektion: Workflow-Schaubild** (nach Features-Cards)

- Eyebrow: "So funktioniert es"
- 5 Schritte als horizontaler Flow, reines HTML/CSS, kein Canvas/SVG-Framework:
  1. Excel-Vorlage befüllen
  2. Konverter öffnen
  3. `.ics` Datei erzeugen
  4. WordPress Plugin konfigurieren
  5. Terminplan live
- Verbindungspfeile in `--teal-300`
- Aktiver Marker (Nummerierung) in `--teal-600` Circle
- Responsive: ab 768px horizontal, darunter vertikal mit gestrichelter Linie

**Neue Sektion: "Für andere Schulen"** (direkt vor Footer)

- Eyebrow: "Offen für alle Schulen im Bezirk"
- Headline: "Kostenlos. Open Source. Auf eigene Verantwortung."
- Kurztext (2 Sätze): Wofür, welche Zielgruppe
- Zwei Buttons nebeneinander:
  - Primär (Amber): "Plugin herunterladen" → `download.html`
  - Sekundär (Outline Teal): "Zur Anleitung" → `anleitung.html`
- Hinweiszeile klein darunter: "Privatprojekt — keine Garantie, keine kommerzielle Nutzung"

---

### 2. `anleitung.html` — Schritt-für-Schritt Anleitung

**Nav + Footer:** identisch mit `index.html` (shared HTML-Block, Copy-Paste da kein Build-System)

**Hero-Strip** (kompakter als Landing-Hero, kein Vollbild):
- Teal-Hintergrund `--teal-700`, Höhe ~160px
- Breadcrumb: "Startseite → Anleitung"
- Titel: "Installationsanleitung"
- Kein CTA-Button

**Voraussetzungen** (Badges-Row):
- 3 Badges: `WordPress 6.0+`, `PHP 8.0+`, `IServ iCal-Feed (HTTPS)`
- Badge-Stil: `--teal-100` Hintergrund, `--teal-700` Text, `--r-sm` Radius

**Schritt-für-Schritt** (nummerierte Steps):

| Schritt | Titel | Inhalt |
|---|---|---|
| 1 | Plugin herunterladen | Link zu `download.html`, ZIP-Inhalt erklären |
| 2 | Plugin installieren | WP-Admin → Plugins → Hochladen, Code-Snippet: Pfad |
| 3 | Plugin aktivieren | Screenshot-Platzhalter, Admin-Menüpunkt |
| 4 | iCal-URL einrichten | IServ-Kalender exportieren, URL in Einstellungen eintragen |
| 5 | Excel-Vorlage erstellen | Link zum Konverter-Tool, Schulwochen-Vorlage generieren |
| 6 | Termine importieren | Konverter-Workflow: Import → Kategorien → Export |
| 7 | Shortcode einbinden | `[gsh_terminplan]` auf Seite, Schuljahr-Parameter |

Step-Stil: Nummer in `--teal-600` Circle (32px), Titel H3, Erklärtext, optionaler Code-Block.
Kein Card-Grid — Steps sind Listenpositionen mit vertikaler Linie als Verbinder.

**Ablauf-Schaubild** (nach Steps):
- Titel: "Gesamtablauf"
- Vertikaler Flow: Excel → Konverter → ICS-Datei → WordPress → Schulwebsite
- Jede Station: Icon + Label + kurze Beschreibung

**FAQ-Accordion** (5 Fragen):
1. Funktioniert das auch ohne IServ?
2. Wie oft muss die iCal-URL aktualisiert werden?
3. Kann ich eigene Kategorien anlegen?
4. Was passiert bei WordPress-Updates?
5. Wo melde ich Fehler?

Accordion: reines CSS + `<details>`/`<summary>`, kein JS nötig.

---

### 3. `download.html` — Disclaimer + Download

**Aufbau:**
- Schmaler Teal-Hero-Strip: "Plugin herunterladen"
- Hauptbereich (max-width 640px, zentriert):

**Haftungshinweis-Box** (Amber-Tinted, Pflichtlektüre):

```
Wichtige Hinweise vor dem Download

Dieses Plugin ist ein privates Hobbyprojekt und wurde ohne
professionelle Qualitätssicherung entwickelt ("Vibe Coding").

Sicherheitslücken können nicht ausgeschlossen werden.
Eine Prüfung auf Datenschutzkonformität (DSGVO) hat nicht
stattgefunden. Vor dem Einsatz mit personenbezogenen Daten
ist eine eigene rechtliche und technische Prüfung Pflicht.

Das Plugin wird ohne jede Gewährleistung bereitgestellt —
weder ausdrücklich noch stillschweigend. Der Entwickler
übernimmt keine Haftung für Schäden jeglicher Art.
```

Box-Stil: `--amber-50` Hintergrund, `2px solid --amber-400` Border, `--r-md` Radius, Warn-Icon oben links (SVG, kein Emoji).

**Checkbox (Pflicht):**
```
☐ Ich habe die obigen Hinweise gelesen und verstanden.
  Ich nutze das Plugin auf eigenes Risiko und in eigener
  Verantwortung meiner Schule.
```

**Download-Button:**
- Initial: `disabled`, `opacity: 0.4`, `cursor: not-allowed`
- Nach Checkbox: aktiv, Amber-Stil, "Plugin herunterladen (.zip)"
- `href` zeigt auf aktuellsten GitHub Release ZIP (direktlink) oder `downloads/terminplan.zip`
- Kleiner Text darunter: Versionsnummer + "GPL v2 or later"

**JS-Logik** (minimal, inline):
```javascript
document.getElementById('disclaimer-cb').addEventListener('change', function() {
  document.getElementById('dl-btn').disabled = !this.checked;
});
```

---

### 4. `impressum.html` — Rechtliches

**Abschnitte:**

#### Impressum (§5 TMG)

```
Angaben gemäß § 5 TMG:

[VORNAME NACHNAME]
[STRAßE NR]
[PLZ ORT]

E-Mail: [EMAIL]
```

Hinweis: Diese Seite ist ein privates, nicht-kommerzielles Projekt.

#### Haftungsausschluss

**Haftung für Inhalte (§ 7 Abs.1 TMG):**
Standardtext: Inhalte nach besten Wissen, keine Gewähr für Aktualität/Richtigkeit/Vollständigkeit.

**Haftung für Links:**
Standardtext: Externe Links geprüft zum Zeitpunkt der Verlinkung, keine laufende Kontrolle.

**Software-Haftungsausschluss:**

```
Das auf dieser Website angebotene WordPress-Plugin wird als
privates Hobbyprojekt kostenlos zur Verfügung gestellt.

Es wird OHNE JEDE GEWÄHRLEISTUNG bereitgestellt, weder
ausdrücklich noch stillschweigend, einschließlich, aber
nicht beschränkt auf die stillschweigende Gewährleistung
der Marktgängigkeit oder Eignung für einen bestimmten Zweck.

Der Entwickler übernimmt keine Haftung für:
- Datenverlust oder Datenschutzverletzungen
- Sicherheitsvorfälle oder unbefugte Zugriffe
- Schäden durch fehlerhafte Funktion oder Inkompatibilitäten
- Folgeschäden jeglicher Art

Die Nutzung erfolgt ausschließlich auf eigenes Risiko.
Vor dem produktiven Einsatz ist eine eigene technische
und rechtliche Prüfung durch die nutzende Institution
erforderlich.
```

#### Urheberrecht

Plugin steht unter GPL v2 or later. Website-Inhalte (Texte, Gestaltung) © [VORNAME NACHNAME]. Weiterverwendung nur mit Genehmigung, ausgenommen die per GPL lizenzierten Code-Bestandteile.

#### Datenschutz

Diese Website wird auf GitHub Pages gehostet. GitHub erhebt ggf. Server-Logfiles (IP-Adresse, Zeitstempel). Weitere personenbezogene Daten werden von dieser Website nicht erhoben. Keine Cookies, kein Tracking, keine Analyse-Tools.

Verantwortlich für die Datenverarbeitung durch GitHub: GitHub Inc., 88 Colin P Kelly Jr St, San Francisco, CA 94107, USA. Datenschutzerklärung: [github.com/site/privacy](https://github.com/site/privacy)

---

## Navigation (alle Seiten)

Gleiches Nav wie `index.html`. Ergänzung: Link "Anleitung" zeigt auf `anleitung.html`.

Nav-Links:
- Funktionen (→ `index.html#features`)
- Anleitung (→ `anleitung.html`)
- Download (→ `download.html`)
- Impressum (→ `impressum.html`)

CTA-Button bleibt: "Plugin herunterladen" → `download.html`

---

## GitHub Pages Setup

1. Repo Settings → Pages → Source: `Deploy from branch`
2. Branch: `main`, Folder: `/website`
3. Resulting URL: `https://[USERNAME].github.io/[REPO]/`
4. `index.html` muss im `website/`-Root liegen

---

## Out of Scope

- Kontaktformular (kein Backend auf GitHub Pages)
- Kommentare / Issues-Integration
- Analytics / Tracking
- Mehrsprachigkeit
- Automatisches ZIP-Update bei Release (kann manuell gepflegt werden)
- DSGVO-Cookie-Banner (keine Cookies, nicht nötig)
