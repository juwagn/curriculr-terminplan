---
name: Curricu:lr Terminplan
description: Schulkalender-Plugin für WordPress — datendichtes, strukturiertes Interface für Lehrer und Schulverwaltung
colors:
  primary-deep: "#00345C"
  primary: "#00467D"
  primary-mid: "#0058A0"
  primary-light: "#E6F4FF"
  gold: "#FFC857"
  gold-light: "#FFF8E6"
  success: "#0E9F6E"
  success-light: "#ECFDF5"
  error: "#E02424"
  error-light: "#FEF2F2"
  bg-body: "#F3F5F9"
  bg-surface: "#FFFFFF"
  bg-muted: "#F9FAFB"
  text-primary: "#111827"
  text-secondary: "#4B5563"
  text-on-dark: "#FFFFFF"
  border: "#D1D5DB"
  border-input: "#9CA3AF"
typography:
  body:
    fontFamily: "'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    fontSize: "15px"
    fontWeight: 400
    lineHeight: 1.6
    letterSpacing: "normal"
  label:
    fontFamily: "'Inter', system-ui, sans-serif"
    fontSize: "0.82rem"
    fontWeight: 500
    lineHeight: 1.4
    letterSpacing: "0.01em"
  mono:
    fontFamily: "'JetBrains Mono', 'Fira Code', monospace"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.5
    letterSpacing: "normal"
rounded:
  pill: "9999px"
  card: "14px"
  input: "8px"
  sm: "6px"
spacing:
  xs: "4px"
  sm: "8px"
  md: "16px"
  lg: "24px"
  xl: "32px"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.text-on-dark}"
    rounded: "{rounded.pill}"
    padding: "8px 24px"
  button-primary-hover:
    backgroundColor: "{colors.primary-mid}"
    textColor: "{colors.text-on-dark}"
  button-secondary:
    backgroundColor: "transparent"
    textColor: "{colors.primary}"
    rounded: "{rounded.pill}"
    padding: "8px 24px"
  button-ghost:
    backgroundColor: "transparent"
    textColor: "{colors.text-secondary}"
    rounded: "{rounded.pill}"
    padding: "8px 24px"
  tab-default:
    backgroundColor: "transparent"
    textColor: "rgba(255,255,255,0.75)"
    rounded: "{rounded.pill}"
    padding: "0.85rem 1.5rem"
  tab-active:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.text-on-dark}"
    rounded: "{rounded.pill}"
    padding: "0.85rem 1.5rem"
  filter-pill-default:
    backgroundColor: "{colors.bg-body}"
    textColor: "{colors.text-secondary}"
    rounded: "{rounded.pill}"
    padding: "4px 16px"
  filter-pill-active:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.text-on-dark}"
    rounded: "{rounded.pill}"
    padding: "4px 16px"
  badge:
    rounded: "{rounded.pill}"
    padding: "2px 10px"
---

# Design System: Curricu:lr Terminplan

## 1. Overview

**Creative North Star: "Die Präzisions-Agenda"**

Curricu:lr Terminplan ist ein Werkzeug, kein Schaustück. Lehrer und Schulverwaltung arbeiten damit täglich: sie brauchen keine Überraschungen, keine Ornamente, keine erklärenden Texte über das Interface selbst. Jeder Termin soll sofort auffindbar sein; jede Interaktion soll auf Anhieb sitzen. Das System informiert präzise und tritt dann in den Hintergrund.

Das visuelle Fundament ist eine Zweizone: dunkel (Navy-Balken für Navigation und Tabs) über hell (weißer und lichtgrauer Inhalt). Diese Trennung ist strukturell: Sie signalisiert dem Nutzer ohne Worte, wo gesteuert und wo gelesen wird. Die Palette ist restrained: Navy als einzige Akzentfarbe, Gold nur als Markensignatur (Curricu:lr-Logo und Heute-Dot), semantische Farben ausschließlich für Statusinformationen.

Dieses Dokument deckt den Plugin-Frontend-Register (product). Die Curricu:lr-Homepage nutzt dieselben Brand-Token in einem anderen Register (brand/marketing) mit eigenem Layout-Muster (Hero, 3-Card-Grid, CTA-Orange). Neue Designs für das Plugin-Frontend folgen diesem Dokument; neue Homepage-Abschnitte folgen dem Brand-Register.

**Key Characteristics:**
- Zweizone: dunkle Navigation, heller Inhalt
- Restrained color strategy: ein Navy-Akzent, Gold nur als Marken-Signatur
- Pill-Radius für interaktive Elemente (Tabs, Filter, Buttons) — weich ohne verspielt zu wirken
- Strukturelle Elevation: Schatten nur wo echte Tiefe existiert
- Inter als einzige Schriftfamilie — kompakt, lesbar, professionell
- Dark-Mode-fähig via `data-gtp-theme` ohne separate Stylesheets

## 2. Colors: Die Digitale Dunkelblau-Skala

Restrained-Strategie: Navy trägt alle Interaktionssignale. Alles andere ist neutral. Gold ist Markensignatur, kein Akzent.

### Primary

- **Tintenschwarz-Blau** (`#00345C`): Navigationsbalken, Tabellen-Header, dunkelste Hintergründe. Nie als Text auf hellem Grund.
- **Digitales Dunkelblau** (`#00467D`): Primär-CTA, aktiver Tab, aktiver Filter-Pill, alle klickbaren Navy-Elemente. Das einzige Interaktionsblau.
- **Mittelblau** (`#0058A0`): Hover-State von Primary-Button. Niemals als Ruhezustand.
- **Blautinte-Pastell** (`#E6F4FF`): Row-Hover, aktiver Filter-Hintergrund, Schulwoche-Headers. Tonal, nicht akzentuiert.

### Secondary

- **Kronengold** (`#FFC857`): Curricu:lr-Logo-Akzent (der Doppelpunkt), Schuljahr-aktiver-Button, Heute-Dot im Tab. Sparsam: wenn Gold erscheint, hat es Bedeutung.
- **Goldstaub** (`#FFF8E6`): Hintergrund für Warning-Banner und Entwurfs-Meldungen.

### Tertiary

- **Smaragd** (`#0E9F6E`): Erfolgs-Statustexte und Bestätigungen. Nie für UI-Interaktionselemente.
- **Karminrot** (`#E02424`): Fehlerstatustexte. Nie für UI-Elemente außerhalb von Statusmeldungen.

### Neutral

- **Schiefergrau** (`#F3F5F9`): Seitengrundlage, leicht blaulich getönt Richtung Navy.
- **Papierweiss** (`#FFFFFF`): Card-Oberfläche, Tabellen-Zeilen, Hauptinhalts-Container.
- **Nebelgrau** (`#F9FAFB`): Alternating-Row-Hintergründe, abgedimmte Bereiche.
- **Schiefer-Text** (`#111827`): Alle Hauptinhalte, Tabellenzellen, Event-Titel.
- **Grau-Text** (`#4B5563`): Sekundäre Infos, Metadaten, Filterlabels, Datumsangaben.
- **Trennlinie** (`#D1D5DB`): Horizontale Trenner, Card-Rahmen, Tabellengrenzen.

### Named Rules

**Die Eine-Stimme-Regel.** Navy (`#00467D`) ist der einzige Interaktionsakzent. Orange, Lila, Türkis als Buttons oder Highlights sind im Plugin-Frontend verboten. Wenn ein Element klickbar ist, signalisiert es das ausschließlich durch Navy.

**Die Gold-Sparsamkeits-Regel.** `#FFC857` erscheint nur im Logo und als Heute-Marker. Kein anderes UI-Element nutzt Gold als Hintergrund oder Rahmen: sonst verliert die Signatur ihre Bedeutung.

## 3. Typography

**Body Font:** Inter (400/500/600/700, `system-ui`-Fallback)
**Mono Font:** JetBrains Mono / Fira Code (Timestamps, technische Metadaten)

**Character:** Inter ist kompakt und professionell. Es liest sich auf kleinen Bildschirmen schnell, ohne akademisch zu wirken. Die Schrift tritt hinter die Termine zurück.

### Hierarchy

- **Title** (800, 1.3–1.6rem, -0.03em letter-spacing): Haupttitel im Header. Einzige expressive Größe. Nur einmal pro Ansicht.
- **Section Header** (700, 0.85rem, uppercase, 0.07em letter-spacing): Quartalüberschriften, Filtergruppen-Labels. Orientierung, kein Drama.
- **Body** (400, 15px / 0.875rem, 1.6 line-height): Tabellenzellen, Event-Text. Max. 65ch in Beschreibungsfeldern.
- **Label** (500–600, 0.82rem, 1.4): Tab-Beschriftungen, Filter-Pills, Button-Text. Kompakt, gut lesbar ab 12px Minimum.
- **Caption** (400, 0.72–0.78rem, tabular-nums): Sync-Zeitstempel, Metadaten, Versionsnummern. JetBrains Mono für zeitbasierte Werte.

### Named Rules

**Die Kompakt-Zuerst-Regel.** Schriftgrade unter 0.82rem nur für dekorative Metadaten. Alle lesbaren Inhalte beginnen bei 0.875rem. Schulverwaltung liest unter Zeitdruck: kein Text unter 15px in primären Inhaltsbereichen.

## 4. Elevation

Das System ist **strukturell**: Schatten erscheinen nur dort, wo echte Z-Ebenen-Abstände bestehen. Hover-Glow ohne Ebenenunterschied, permanente Card-Schatten als Dekoration und Blur-Glassmorphism als Default sind verboten.

### Shadow Vocabulary

- **Ambient-low** (`0 1px 3px rgba(0,0,0,0.08)`): Card-Raster auf Tablet-Breakpoint. Zeigt Trennung vom Seitengrund, nicht Erhabenheit.
- **Structural-card** (`0 18px 40px rgba(15,23,42,0.15)`): Modale, Event-Detail-Popup. Echter Ebenenabstand.
- **Functional-btn** (`0 2px 8px rgba(0,70,125,0.25)`): Primary-Button im Ruhezustand. Minimale Haptik, kein Dekorelement.
- **Focus-ring** (`0 0 0 3px rgba(0,70,125,0.25)`): Alle Focus-Visible-States. Accessibility-Pflicht.

### Named Rules

**Die Flach-im-Ruhezustand-Regel.** Tabellenzeilen, Filter-Pills, Tabs: kein Schatten. Schatten erscheinen nur bei Modal-Ebene oder als Hover-Feedback auf explizit interaktiven Surfaces. Die Tabelle ist keine Card-Sammlung.

## 5. Components

### Quarter Tabs (Signatur-Komponente)

Der dunkelste Balken des Interfaces. Navy-Hintergrund trägt Pill-Chips für Quartal-Navigation.

- **Leiste:** `background: #00345C`, `border-bottom: 2px solid rgba(255,255,255,0.1)`, `position: sticky; top: 0`
- **Chip Default:** transparent, `color: rgba(255,255,255,0.75)`, `border: 1px solid rgba(255,255,255,0.25)`, Pill-Radius, `padding: 0.85rem 1.5rem`
- **Chip Hover:** `background: rgba(255,255,255,0.12)`, `color: #FFFFFF`
- **Chip Active:** `background: #00467D`, `background-tint: #E6F4FF`, `color: #FFFFFF`, `font-weight: 700`, `border-bottom: 3px solid #00467D`
- **Heute-Dot:** 6px Gold-Kreis (`#FFC857`) inline im aktuellen Quartal-Chip

### Filter Pills

Kategorie-Filter auf hellem Hintergrund.

- **Default:** `background: #F3F5F9`, `border: 1px solid #D1D5DB`, `color: #4B5563`, `font-size: 0.82rem`, Pill-Radius
- **Hover:** `border-color: #00467D`, `color: #00467D`, `background: #E6F4FF`
- **Active:** `background: #00467D`, `color: #FFFFFF`
- **Inactive (ausgeblendet):** `opacity: 0.28`, `filter: grayscale(0.8)`, Text durchgestrichen

### Buttons

Pill-förmig. Kein Gradient, keine Textschatten.

- **Primary:** `background: #00467D`, `color: #FFFFFF`, `box-shadow: 0 2px 8px rgba(0,70,125,0.25)`, Pill, `padding: 8px 24px`
- **Primary Hover:** `background: #0058A0`, `transform: translateY(-1px)`, stärkerer Schatten
- **Secondary:** transparent, `border: 1.5px solid #00467D`, `color: #00467D`
- **Ghost:** `border: 1px solid #D1D5DB`, `color: #4B5563` — für Abbrechen, Schließen
- **Disabled:** `opacity: 0.45`, `cursor: not-allowed`, kein `transform`

### Inputs / Search

Input-Radius (8px), nicht Pill: visuell von Action-Buttons unterschieden.

- **Default:** `border: 1.5px solid #9CA3AF`, `border-radius: 8px`, `background: #FFFFFF`, `font-size: 0.84rem`
- **Focus:** `border-color: #00467D`, `box-shadow: 0 0 0 3px rgba(0,70,125,0.25)`
- **Search-Layout:** Wrap `position: relative`, Icon absolut bei `left: 14px`, Input `padding-left: 42px`

### Cards / Modale

Nur für modale Ebene (Event-Detail, Changelog, Help) und Kalender-Grid-Ansicht (Tablet+). Nie als Tabellen-Row-Ersatz auf Desktop.

- **Radius:** 16px (Modal), 14px (Calendar-Card)
- **Shadow:** `0 18px 40px rgba(15,23,42,0.15)`
- **Padding:** `1.6rem 1.75rem` (Modal), `24px` (Calendar-Card)
- **Glass-Variante** (Calendar-Grid): `backdrop-filter: blur(16px)`, `background: rgba(255,255,255,0.85)` — nur auf Tablet+

### Category Badges

Inline-Pills in Kategoriefarbe (dynamisch via PHP-generiertem CSS). Immer Label + Farbe.

- **Radius:** Pill (`9999px`)
- **Padding:** `2px 10px`
- **Font:** `0.75rem`, weight 600, `0.02em` letter-spacing

## 6. Do's and Don'ts

### Do:

- **Do** Navy (`#00467D`) für alle primären interaktiven Elemente: Tabs, Filter, CTAs, Links.
- **Do** Pill-Radius (`9999px`) für alle klickbaren Interaktionselemente: Tabs, Filter, Buttons, Badges.
- **Do** Input-Radius (8px) für Eingabefelder: visuell von Action-Pills unterschieden.
- **Do** Strukturelle Schatten: `box-shadow` nur bei echtem Ebenenabstand (Modal, Hover-Card). Tabellen-Rows flach lassen.
- **Do** `prefers-reduced-motion` respektieren: alle Transitions auf `0.01ms` reduzieren.
- **Do** Kategorien immer mit Label + Farbe zeigen. Nie Farbe allein als Information.
- **Do** Inter für alle Textebenen; JetBrains Mono nur für Zeitstempel und technische Metadaten.
- **Do** WCAG 2.1 AA: alle Text-Hintergrund-Kombinationen auf mindestens 4.5:1 Kontrast prüfen.
- **Do** Dark-Mode via `data-gtp-theme="dark"` als Token-Override umsetzen, nicht als separates Stylesheet.

### Don't:

- **Don't** buntes Grundschuldesign: keine PlaySchool-Ästhetik, keine farbigen Flächen als Basispalette außer Navy.
- **Don't** generisches WordPress-Theme: kein TwentyTwenty-Look, keine `#0073aa` WP-Admin-Buttons, keine grauen WP-Standard-Tabellen.
- **Don't** SaaS-Creme: kein Dashboard in Weiß + abgerundetes Hellblau + Schatten-auf-allem. Strukturell, nicht dekorativ.
- **Don't** Terminal-Dark als Default: Hintergrund bleibt hell (`#F3F5F9`/`#FFFFFF`). Dark-Mode ist opt-in.
- **Don't** Orange als primäre Interaktionsfarbe im Plugin-Frontend. Orange gehört zum Homepage-Brand-Register.
- **Don't** `border-left > 1px` als farbigen Stripe. Draft-Banner ist die einzige Ausnahme — dort mit vollem Border + Background-Tint.
- **Don't** Gradient-Text (`background-clip: text + linear-gradient`). Navy ist solid, Gold ist solid.
- **Don't** Glassmorphism als Default-Stil. `backdrop-filter: blur()` nur im Kalender-Grid-Modus auf Tablet — nicht an Standardkomponenten.
- **Don't** Kategoriefarben als Hintergrund ganzer Tabellenzeilen. Nur als Pill/Badge. Ganze farbige Rows zerstören die Lesbarkeit.
- **Don't** `#000` oder `#fff` direkt setzen. Immer Token: `--text-main` (`#111827`) und `--bg-surface` (`#FFFFFF`).
