# AGENTS.md

This file provides guidance to Codex (Codex.ai/code) when working with code in this repository.

---

## Core Principles

- **Simplicity First**: Make every change as simple as possible. Impact minimal code.
- **No Laziness**: Find root causes. No temporary fixes. Senior developer standards.
- **Minimal Impact**: Changes should only touch what's necessary. Avoid introducing bugs.

---

## Workflow Orchestration

### 1. Plan Node Default
- Enter plan mode for ANY non-trivial task (3+ steps or architectural decisions)
- If something goes sideways, STOP and re-plan immediately - don't keep pushing
- Use plan mode for verification steps, not just building
- Write detailed specs upfront to reduce ambiguity

### 2. Subagent Strategy
- Use subagents liberally to keep main context window clean
- Offload research, exploration, and parallel analysis to subagents
- For complex problems, throw more compute at it via subagents
- One tack per subagent for focused execution

### 3. Self-Improvement Loop
- After ANY correction from the user: update `tasks/lessons.md` with the pattern
- Write rules for yourself that prevent the same mistake
- Ruthlessly iterate on these lessons until mistake rate drops
- Review lessons at session start for relevant project

### 4. Verification Before Done
- Never mark a task complete without proving it works
- Diff behavior between main and your changes when relevant
- Ask yourself: "Would a staff engineer approve this?"
- Run tests, check logs, demonstrate correctness

### 5. Demand Elegance (Balanced)
- For non-trivial changes: pause and ask "is there a more elegant way?"
- If a fix feels hacky: "Knowing everything I know now, implement the elegant solution"
- Skip this for simple, obvious fixes - don't over-engineer
- Challenge your own work before presenting it

### 6. Autonomous Bug Fixing
- When given a bug report: just fix it. Don't ask for hand-holding
- Point at logs, errors, failing tests - then resolve them
- Zero context switching required from the user
- Go fix failing CI tests without being told how

---

## Task Management

1. **Plan First**: Write plan to `tasks/todo.md` with checkable items
2. **Verify Plan**: Check in before starting implementation
3. **Track Progress**: Mark items complete as you go
4. **Explain Changes**: High-level summary at each step
5. **Document Results**: Add review section to `tasks/todo.md`
6. **Capture Lessons**: Update `tasks/lessons.md` after corrections

---

## Projektstruktur

- `plugin/gsh-terminplan.php` – gesamtes Plugin (PHP, Inline-JS, ~7000 Zeilen)
- `plugin/assets/css/gsh-terminplan.css` – gesamtes CSS (nie Inline-CSS in PHP)
- `Backup/` – ältere Versionen (nicht anfassen)
- `konverter/` – standalone HTML-Tool (unabhängig vom Plugin)
- `prompts/` – Implementierungs-Prompts für Features
- `tasks/todo.md` – aktuelle Aufgabenliste (wird von Codex verwaltet)
- `tasks/lessons.md` – gelernte Lektionen (wird von Codex nach Korrekturen aktualisiert)

---

## Kein Build-System

Kein npm, kein Composer, keine Kompilierung. Änderungen direkt in den Quelldateien vornehmen und in WordPress hochladen.

## PHP-Syntaxprüfung

Nach jeder Änderung an `gsh-terminplan.php` ausführen:
```bash
php -l plugin/gsh-terminplan.php
```
Kein Deployment ohne sauberes `No syntax errors detected`.

---

## Versioning – alle vier Stellen müssen immer synchron sein

1. Plugin-Header: `Version: X.Y.Z`
2. `define('GSH_TP_VERSION', 'X.Y.Z')`
3. `gsh_tp_changelog()` – neuen Eintrag oben einfügen
4. Changelog im Plugin-Header-Kommentar

Bump-Regel: Bugfix → patch, neues Feature → minor, Breaking Change → major.

---

## CSS-Regel (strikt)

Alles CSS ausschließlich in `plugin/assets/css/gsh-terminplan.css`. Niemals CSS in PHP-Heredocs oder Inline-Strings.

---

## Architektur (`gsh-terminplan.php`)

| Sektion | Bereich | Schlüsselfunktionen |
|---------|---------|---------------------|
| 0 | Profil-Hilfsfunktionen | `gsh_tp_get_profiles`, `gsh_tp_active_profile_id`, `gsh_tp_maybe_migrate` |
| 1 | Admin-UI + Tab-System | `gsh_tp_settings_page`, `gsh_tp_render_profile_tab`, `gsh_tp_render_kategorien_tab`, `gsh_tp_render_system_tab` |
| 2 | iCal-Abruf/Cache | `gsh_tp_fetch_ical($pid)`, `gsh_tp_fetch_sync($pid)`, `gsh_tp_do_refresh($pid)` |
| 3 | Schulwochen/Quartale | `gsh_tp_quartale($pid)`, `gsh_tp_current_q($pid)` |
| 5 | Shortcode | `gsh_tp_shortcode` – Attribut `schuljahr=`, URL-Param `?sj=` |
| 9 | Deaktivierung/Deinstallation | Profil-aware Cleanup |

---

## Datenmodell

- Alle Profile in `gsh_tp_profiles` (serialisiertes Array, autoload=true, max 5)
- Pro Profil: `id`, `label`, `ical_url`, `cache_duration`, `quartal_grenzen`, `schuljahr_start`, `is_active`, `is_draft`
- Cache-Keys pro Profil: `gsh_tp_ical_{pid}`, `gsh_tp_backup_{pid}`, `gsh_tp_fresh_{pid}` (Transient), `gsh_tp_sync_{pid}`, `gsh_tp_snap_{pid}` (Transient), `gsh_tp_chg_{pid}` (Transient)

---

## Kategorien

- `GSH_TP_DEFAULT_CATEGORIES` – 6 Standardkategorien, jede mit `id`, `label`, `color`, `slug`, `keywords[]`
- `id` ist immutabel (Anker für Keyword-Matching)
- `slug` wird aus `label` generiert (Umlaut-Normalisierung: ä→ae etc.) – CSS-Klasse + Filter-Key
- Keyword-Matching: `gsh_tp_assign_categories_to_event()` liest `$cat['keywords']` – kein hardcodierter `match()`-Block

---

## Farb-Logik

- `gsh_tp_contrast_color($hex)` – WCAG-2.1-Luminanz (Schwellwert 0.179), gibt `#000000` oder `#ffffff` zurück
- `gsh_tp_color_derive($hex)` – erzeugt Pastel-`$bg` (12 % Original + 88 % Weiß), dann `$tx = gsh_tp_contrast_color($bg)`
- Reihenfolge im PHP: `gsh_tp_contrast_color` muss **vor** `gsh_tp_color_derive` stehen

---

## Icon-System

`gsh_tp_icon($name, $size)` – SVG-Pfade in `$paths`-Array. Gibt `''` zurück für unbekannte Namen → immer prüfen, ob ein neuer Icon-Name im Array registriert ist.

---

## Bereiche, die nicht verändert werden sollen

- iCal-Parser: `gsh_tp_parse_events`, `gsh_tp_parse_event`
- Date-Index: `gsh_tp_build_date_index`, `gsh_tp_day_events`
- Tabellen-Rendering
- PDF-Export
- Change-Notification-System
- Hauptfunktionen `gsh_tp_js()` und `gsh_tp_css()` (strukturell)

---

## Sprache

- Code-Bezeichner: Englisch
- Kommentare: Deutsch
- WordPress Coding Standards