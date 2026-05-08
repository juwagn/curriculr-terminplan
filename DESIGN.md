# DESIGN.md

\# Design System & UI Spezifikation: [terminplan.geshorst.de](terminplan.geshorst.de) (V2)

## 1. Visuelle Identität & Core Styles Das Design verfolgt einen modernen "SaaS-Light" Ansatz: freundlich, aufgeräumt und professionell für den Bildungsbereich.  

### Farbschema (Tailwind CSS Äquivalente) - **Primary / Action:** `orange-500` (#F59E0B) – Für Haupt-CTAs. - **Secondary / Success:** `emerald-500` (#10B981) – Für Akzente und Bestätigungen. - **Background:** `slate-50` (#F8FAFC) oder ein sehr helles `mint-50` (#F0FDF4). - **Surface (Cards):** `white` (#FFFFFF) mit 70% Deckkraft für Glassmorphism-Effekte. - **Text (Primary):** `slate-900` (#0F172A). - **Text (Secondary):** `slate-600` (#475569).  

### Typografie - **Font-Family:** 'Inter', 'Outfit' oder 'Geist' (Sans-Serif). - **Headlines:** Semi-bold bis Bold, Zeilenabstand 1.2. - **Body:** Regular, Zeilenabstand 1.6 für optimale Lesbarkeit.  

### Dekorative Elemente - **Border Radius:** Großzügige Rundungen (24px für Cards, 12px für Buttons). - **Shadows:** Weiche, diffuse Schatten für Tiefe (Soft Elevation). - **Glassmorphism:** `backdrop-filter: blur(12px); background: rgba(255, 255, 255, 0.7); border: 1px solid rgba(255, 255, 255, 0.3);`  

\---  

## 2. Layout-Struktur (Desktop)  

### Header (Sticky) - **Links:** Logo (Terminplan). - **Mitte:** Navigation (Funktionen, Anleitung, WordPress-Plugin, FAQ). - **Rechts:** Button "Jetzt starten" (Orange).  

### Hero Section (Split-Layout) - **Spalte Links (60%):** Große Headline ("Schultermine einfach verwalten"), kurzer Infotext, zwei Buttons nebeneinander (Primär: Orange, Sekundär: Outline-Stil mit Icon). - **Spalte Rechts (40%):** Eine schwebende, leicht geneigte Browser-Fenster-Illustration, die ein Kalender-Dashboard zeigt (Glassmorphism-Effekt).  

### Produkt-Sektion (3-Card Grid) - Drei gleich große Karten: **ICS-Konverter**, **WordPress-Plugin**, **Excel-Vorlage**. - Jede Karte enthält: Ein farbiges Icon oben links, eine prägnante Headline, 3-4 Bulletpoints mit grünen Checkmarks und einen spezifischen Button ("Download", "Zum Tool" etc.) am unteren Rand.  

### Workflow (Horizontaler Prozess) - Eine visuelle Leiste mit 4 Schritten:    1. Excel-Liste füllen -> 2. In ICS umwandeln -> 3. In WP hochladen -> 4. Online-Kalender fertig. - Verbindungslinien zwischen den Schritten zur Verdeutlichung des Prozesses.  

### Footer (Klar & Simpel) - Zentrierte Links zu Impressum, Datenschutz und Kontakt. - Dezentes Copyright-Label.