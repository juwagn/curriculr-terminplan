# Category Matching Fix – Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix Excel import so events categorised as "Allgemein", "Prüfung", "Konferenz" etc. are correctly matched, by (1) reading the Excel's own Kategorien sheet, (2) adding prefix-matching fallback, and (3) improving warning messages.

**Architecture:** All changes are in `konverter/Terminplan_Konverter.html`, a self-contained single-file tool (no build system). Changes touch three JS sections: `handleFile()` (file reader), `matchCategory()`, and a new helper `mergeExcelCategories()`. The live `categories` array is mutated before import so both SW-format and classic-format paths benefit automatically.

**Tech Stack:** Vanilla JS · SheetJS (`XLSX`) · no build step

---

## File Map

| File | Change |
|------|--------|
| `konverter/Terminplan_Konverter.html` | Task 1: add `mergeExcelCategories()` helper + call in `handleFile()`<br>Task 2: extend `matchCategory()` with prefix/startsWith fallback<br>Task 3: improve warning message in `importSWFormat()` |

---

## Task 1: Read Kategorien sheet and merge into live categories

**Files:**
- Modify: `konverter/Terminplan_Konverter.html:1596-1619` (handleFile reader block)
- Modify: `konverter/Terminplan_Konverter.html:~1729` (add helper before matchCategory)

### What this does
After SheetJS parses the workbook, read `wb.Sheets['Kategorien']`. Column A = category label, Column B = optional hex colour. For each label not already in `categories`, push a new entry and call `rebuildCategoryLookups()`. Classic import path benefits automatically because the merge happens before the format-detection branch.

- [ ] **Step 1: Add `mergeExcelCategories()` helper before `matchCategory()`**

Locate the line `function matchCategory(val) {` (~line 1730). Insert the following block **directly above** it:

```javascript
function mergeExcelCategories(katRows) {
  const PALETTE = [
    { bg: '#e3eef7', text: '#1a3a5c' },
    { bg: '#f3e8f7', text: '#4a1a7c' },
    { bg: '#fef3cd', text: '#7c5a00' },
    { bg: '#d1f5ea', text: '#0e5c3a' },
    { bg: '#fce4d6', text: '#7c2e0e' },
    { bg: '#e4f7d1', text: '#2e5c0e' },
  ];
  let palIdx = categories.length % PALETTE.length;
  // Row 0 is the header; data starts at row 1
  for (let i = 1; i < katRows.length; i++) {
    const row = katRows[i] || [];
    const label = String(row[0] || '').trim();
    if (!label) continue;
    // Skip if already known (exact, case-insensitive)
    if (categories.some(c => c.label.toLowerCase() === label.toLowerCase())) continue;
    const rawColor = String(row[1] || '').trim();
    const hasHex = /^#[0-9a-fA-F]{6}$/.test(rawColor);
    const pair = PALETTE[palIdx++ % PALETTE.length];
    const bg   = hasHex ? rawColor : pair.bg;
    // Simple WCAG luminance for text contrast
    const r = parseInt(bg.slice(1, 3), 16);
    const g = parseInt(bg.slice(3, 5), 16);
    const b = parseInt(bg.slice(5, 7), 16);
    const lum = 0.2126 * (r / 255) ** 2.2 + 0.7152 * (g / 255) ** 2.2 + 0.0722 * (b / 255) ** 2.2;
    const text = lum > 0.179 ? '#333333' : '#ffffff';
    categories.push({ label, bg, text, icsValue: label, keywords: '' });
  }
  rebuildCategoryLookups();
}
```

- [ ] **Step 2: Call `mergeExcelCategories()` in `handleFile()` before format detection**

Locate this exact block (~line 1599–1605):

```javascript
      const wb = XLSX.read(e.target.result, { type: 'array', cellDates: true });
      // "Terminplan"-Sheet by name suchen, Fallback auf erstes Sheet
      const sheetName = wb.SheetNames.find(n => n.toLowerCase() === 'terminplan') ?? wb.SheetNames[0];
      const ws = wb.Sheets[sheetName];
      const rows = XLSX.utils.sheet_to_json(ws, { header: 1, raw: false, dateNF: 'yyyy-mm-dd' });

      // Schulwochen-Format automatisch erkennen und separat importieren
      if (detectSWFormat(rows)) {
```

Insert the following **after** the `rows` assignment and **before** the `if (detectSWFormat(rows))` line:

```javascript
      // Kategorien-Sheet mergen (falls vorhanden) – vor Format-Erkennung, damit beide Pfade profitieren
      const katWs = wb.Sheets['Kategorien'];
      if (katWs) {
        mergeExcelCategories(XLSX.utils.sheet_to_json(katWs, { header: 1, raw: false }));
      }

```

- [ ] **Step 3: Manual verification**

Open `konverter/Terminplan_Konverter.html` in browser. Import the problematic Excel (with `Kategorien` sheet containing "Allgemein", "Prüfung", "Konferenz"). In the Import-Summary modal, the categories "Allgemein", "Prüfung", "Konferenz" must appear in the category counts — **not** "Ohne Kategorie".

- [ ] **Step 4: Commit**

```bash
git add konverter/Terminplan_Konverter.html
git commit -m "feat(konverter): merge Kategorien sheet into live categories on import"
```

---

## Task 2: Prefix/startsWith fallback in `matchCategory()`

**Files:**
- Modify: `konverter/Terminplan_Konverter.html` — function `matchCategory()` (~line 1730)

### What this does
After the existing keyword match, check whether any category label starts with the input string, or the input string starts with any category label. Catches "Konferenz" → "Konferenzen/DB".

- [ ] **Step 1: Extend `matchCategory()` with prefix fallback**

Locate the current function body (after Task 1 insertion, line numbers shift slightly):

```javascript
function matchCategory(val) {
  if (!val) return '';
  const lower = val.toLowerCase();
  // Exact label match
  for (const c of categories) {
    if (c.label.toLowerCase() === lower) return c.label;
  }
  // Keyword match (uses configured keywords)
  for (const entry of catKeywords) {
    if (lower.includes(entry.kw)) return categories[entry.idx].label;
  }
  return '';
}
```

Replace with:

```javascript
function matchCategory(val) {
  if (!val) return '';
  const lower = val.trim().toLowerCase();
  // Exact label match
  for (const c of categories) {
    if (c.label.toLowerCase() === lower) return c.label;
  }
  // Keyword match (uses configured keywords)
  for (const entry of catKeywords) {
    if (lower.includes(entry.kw)) return categories[entry.idx].label;
  }
  // Prefix/substring fallback: "Konferenz" → "Konferenzen/DB"
  for (const c of categories) {
    const catLower = c.label.toLowerCase();
    if (catLower.startsWith(lower) || lower.startsWith(catLower)) return c.label;
  }
  return '';
}
```

- [ ] **Step 2: Manual verification — prefix match**

In browser, create a test row in the table with category "Konferenz" typed manually. Alternatively, import an Excel with `Terminplan!I = "Konferenz"` and no Kategorien sheet. Verify the event is assigned to "Konferenzen/DB" (not "Ohne Kategorie").

- [ ] **Step 3: Manual verification — no regression**

Import the same Excel as before. Verify that events already matching via exact-label or keyword still resolve to the same category.

- [ ] **Step 4: Commit**

```bash
git add konverter/Terminplan_Konverter.html
git commit -m "feat(konverter): add prefix/startsWith fallback to matchCategory"
```

---

## Task 3: Improve "Unbekannte Kategorie" warning message

**Files:**
- Modify: `konverter/Terminplan_Konverter.html` — `importSWFormat()` (~line 1473) and classic-format import block (~line 1669)

### What this does
Replace the vague "Unbekannte Kategorie" warning with an actionable message pointing the user to the Kategorien sheet.

- [ ] **Step 1: Update warning in `importSWFormat()` (SW format path)**

Locate line ~1473:

```javascript
      if (catRaw && !cat) warnings.push(`Unbekannte Kategorie "${catRaw}" bei "${title}". Bitte Kategorie pruefen.`);
```

Replace with:

```javascript
      if (catRaw && !cat) warnings.push(`Kategorie "${catRaw}" bei "${title}" nicht zugeordnet – prüfen: Kategorien-Sheet befüllen oder Schreibweise angleichen.`);
```

- [ ] **Step 2: Find and update warning in classic import path**

Search for similar `catRaw && !cat` or `Unbekannte Kategorie` warning in the classic-format loop (~line 1656–1710). If present, apply the same updated message. If absent, add it after the `matchCategory` call analogous to Step 1.

Grep in the file for `Unbekannte Kategorie` to confirm all occurrences are updated.

- [ ] **Step 3: Manual verification**

Import an Excel with a category value that truly has no match even after prefix fallback (e.g. "XYZ"). The Import-Summary warning must read the new message, not the old one.

- [ ] **Step 4: Commit**

```bash
git add konverter/Terminplan_Konverter.html
git commit -m "fix(konverter): improve unmatched category warning with actionable hint"
```

---

## Task 4: Bump Konverter version to v2.4

**Files:**
- Modify: `konverter/Terminplan_Konverter.html` — version badge and changelog

### What this does
Three category-matching improvements = new minor version for the Konverter.

- [ ] **Step 1: Find all version references**

Search for `v2.3` in the file. Expect to find:
- `<title>` tag
- version badge in header
- changelog entry header

- [ ] **Step 2: Update version to v2.4 in all three locations**

Replace each `v2.3` with `v2.4`. Then add a changelog entry in the `modal-changelog` section:

```html
<h4>v2.4 – 2026-05-09</h4>
<ul>
  <li>Kategorien-Sheet aus Excel wird beim Import automatisch gelesen und gemergt</li>
  <li>Prefix-Matching in Kategorie-Zuordnung (z.B. "Konferenz" → "Konferenzen/DB")</li>
  <li>Verbesserter Hinweis bei nicht zugeordneten Kategorien</li>
</ul>
```

- [ ] **Step 3: Manual verification**

Open in browser. Check version badge shows `v2.4`. Open changelog modal — new entry appears at top.

- [ ] **Step 4: Commit and push**

```bash
git add konverter/Terminplan_Konverter.html
git commit -m "chore(konverter): bump version to v2.4"
git push
```
