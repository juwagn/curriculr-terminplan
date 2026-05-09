# Design Spec: Kategorie-Matching-Verbesserung im Konverter

**Datum:** 2026-05-09  
**Branch:** `redesign/iserv-look`  
**Status:** Approved

---

## Problem

Wenn ein Schuladmin eine von ChatGPT befüllte Excel-Datei importiert, trägt ChatGPT generische deutsche Kategorie-Namen ein ("Allgemein", "Prüfung", "Konferenz"). Diese stimmen nicht mit den `DEFAULT_CATEGORIES` des Konverters überein. Ergebnis: 41 von 46 Terminen importiert ohne Kategorie.

**Root Cause:** `matchCategory()` prüft nur exact-match auf Label und Keywords. Kein Prefix- oder Substring-Fallback.

---

## Lösung: 3 Ebenen

### Ebene 1 – Kategorien-Sheet aus Excel lesen

**Wo:** `konverter/Terminplan_Konverter.html`, FileReader-Block (~Zeile 1606)

**Was:** Nach dem Einlesen des `Terminplan`-Sheets auch das `Kategorien`-Sheet auslesen. Die dort definierten Kategorien (Spalte A: Name, Spalte B: Farbe optional) werden in das laufende `categories`-Array gemischt. Matching-Logik:

1. Existiert der Label bereits → Kategorie überspringen (eigene Konfiguration hat Vorrang)
2. Sonst → neue Kategorie mit dem Excel-Label anlegen, Farbe aus Spalte B oder Fallback-Farbe

**Ziel:** Wenn das Excel `Kategorien!A2 = "Allgemein"` enthält und der Konverter kein "Allgemein" kennt, wird "Allgemein" als ad-hoc-Kategorie angelegt, sodass alle 41 Termine korrekt zugeordnet werden.

**Datenstruktur einer Konverter-Kategorie:**
```js
{ id: 'allgemein', label: 'Allgemein', color: '#6B7280', slug: 'allgemein', keywords: [] }
```

### Ebene 2 – matchCategory() Prefix/Substring-Fallback

**Wo:** `matchCategory()` ~Zeile 1730

**Was:** Nach dem bestehenden Keyword-Match einen dritten Schritt einführen:
- Normalisierung: `input.trim().toLowerCase()`
- Prüfe für jede Kategorie, ob `cat.label.toLowerCase().startsWith(input)` oder `input.startsWith(cat.label.toLowerCase())`
- Falls Treffer: diese Kategorie zurückgeben

**Beispiele:**
- `"Konferenz"` → trifft `"Konferenzen/DB"` via startsWith  
- `"Prüfungen"` → trifft `"Prüfung"` via input.startsWith(cat.label)

**Reihenfolge:**  
`exactLabel → keywords → prefix/substring → ''`

### Ebene 3 – Excel-Dropdown-Validierung

**Wo:** Vorlage-Generierung. Da kein Python-Generator im Repo liegt (Vorlage wird manuell gepflegt), wird diese Ebene als Hinweis in die Konverter-Ausgabe eingebaut:

- Im Konverter-UI: Beim Export einer Vorlage (falls vorhanden) oder in der Anleitung im Changelog/Tooltip einen Hinweis anzeigen: "Kategorie-Spalte mit den Namen aus dem Kategorien-Sheet befüllen"
- **Keine Code-Änderung** an der Vorlage selbst in diesem Scope (kein Python-Generator vorhanden)

Ebene 3 reduziert sich damit auf: Fehlermeldung im Import-Summary verbessern – statt "Unbekannte Kategorie" → "Nicht zugeordnet – bitte Kategorien-Sheet prüfen oder matchCategory erweitern"

---

## Nicht im Scope

- Fuzzy-Matching (Levenshtein) – zu fehleranfällig für Kategorie-Namen
- Änderungen am WordPress-Plugin selbst
- Python-Excel-Generator (existiert nicht)
- Neue Kategorien persistent in Konverter-Config speichern (nur für aktuellen Import)

---

## Akzeptanzkriterien

1. Excel mit `Kategorien!A = ["Allgemein", "Prüfung", "Konferenz"]` importiert → 0 "Nicht zugeordnet"-Warnungen
2. "Konferenz" matched auf "Konferenzen/DB" via Ebene 2 (Prefix)
3. Kein Regression bei bestehendem Exact-Match
4. Import-Summary zeigt korrekte Anzahl pro Kategorie
