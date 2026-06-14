# Design: Feedback v4.16.0 — Entwurf-Zugang, iPad-Tabelle, Mobile-PDF

**Date:** 2026-06-14
**Repo:** `curriculr-terminplan` (WordPress plugin)
**Target version:** 4.16.0 (minor — UX features)

## Context

Three feedback items from a school adopting the plugin. Investigated against the
current codebase: item 1 turned out to be a misunderstanding (feature already
exists), items 2 and 3 are real fixes sharing the same area (mobile/tablet
rendering of the `[gsh_terminplan]` shortcode output).

## Item 1 — Entwurf-Ansicht an Admin-Account gekoppelt → NO CODE

**Feedback:** Draft view rigidly tied to the WP admin account; the people doing
scheduling differ from the WP admins. They would accept a freely link-accessible
draft or a simple password.

**Finding:** The token-URL Entwurf-Kiosk already does exactly this (since v4.1.0):

- `page-terminplan-entwurf.php` validates a token from the URL via
  `gsh_tp_check_draft_kiosk_access()` (timing-safe `hash_equals`, 10/h/IP
  rate-limit). It is **passwordless** — whoever has the link sees the draft, no
  login, no password. A "Entwurf" banner is shown.
- The shortcode gates draft visibility on
  `current_user_can('manage_options') || gsh_tp_draft_kiosk_context()`
  (`gsh-terminplan.php:4669`, `:4700`).

The only real friction: **generating** the token requires WP admin
(`manage_options`, System/Kiosk tab). The link itself is freely shareable and
persistent once generated.

**Decision (user):** No code change. Reply to the feedback explaining that the
draft page is already passwordless and link-shareable; an admin generates the
token once and shares the URL.

## Item 3 — iPad zeigt Mobile-Ansicht statt voller Tabelle

**Feedback:** On iPad (in IServ) the Terminplaner renders as the mobile site;
the full table would be better.

**Root cause:** Two competing CSS sources.

- `gsh_tp_css()` (`gsh-terminplan.php:5729`) emits an inline `<style>` block that
  is the de-facto source of truth (loaded after the enqueued stylesheet, wins on
  equal-specificity conflicts). It already contains:
  - `@media(max-width:767px)` → `.gt{display:none!important}` + `.gtp-mob{display:block}` (phones get the agenda)
  - `@media(min-width:768px) and (max-width:1024px)` → `.gt{display:block;overflow-x:auto}` (tablets get the scrollable table)
- BUT the enqueued `assets/css/gsh-terminplan.css` has
  `@media (min-width:769px) and (max-width:1200px)` that turns the table into
  stacked cards: `.gt thead{display:none}`, `.gt tr{display:flex;flex-direction:column}`.
  The inline tablet rule never resets `thead`/`tr`, so on iPad (768–1024) the
  result is a hybrid: block+scroll table with hidden header and vertically
  stacked rows — i.e. the "mobile-looking" view the feedback complains about.

**Decision (user):** Full table from ≥768px with horizontal scroll; phones keep
the agenda.

**Fix:**

1. Remove the `@media (min-width:769px) and (max-width:1200px)` stacked-card
   block from `assets/css/gsh-terminplan.css`. After removal:
   - 768–1024px (iPad): inline rule governs → real `.gt` table, `overflow-x:auto`.
   - 1025–1200px: no special rule → default desktop table (wide enough).
   - ≤767px: unchanged → table hidden, agenda shown.
2. In the inline tablet rule (`gsh_tp_css()`, the `@media(768–1024px)` block),
   add a `min-width` to `.gt` so columns don't squish — the table keeps its
   natural width and scrolls horizontally instead. (Value to confirm during
   implementation against the real column count, e.g. `min-width:760px`.)

No phone/tablet boundary change needed: ≤767 phone, ≥768 tablet is already
correct; iPad portrait (768 CSS-px) lands in the tablet bucket.

## Item 2 — PDF am Handy und iPad nicht übersichtlich

**Feedback:** PDF output not clearly usable on phone and iPad.

**Root cause:** `gtpPrint()` (`gsh-terminplan.php:7087`) builds a self-contained
HTML document (its own A4-landscape CSS) and writes it into a **hidden, off-screen
0×0 iframe**, then calls `frame.contentWindow.print()` (`:7228`–`:7264`). On
desktop this produces a clean PDF. On iOS Safari it fails badly: Safari ignores
`@page{size:A4 landscape}` and the hidden-iframe print preview squeezes the wide
landscape table into a portrait page → unreadable. This is a mobile-browser
print-mechanism limitation, not a missing print stylesheet (the iframe already
carries full CSS).

**Decision:** Standalone print page on touch/mobile (chosen over bundling a
client-side PDF lib, which would violate the no-build/no-npm constraint).

**Fix:** In `gtpPrint()`, branch on device:

- **Touch/mobile** (`window.matchMedia('(pointer:coarse)').matches`, or viewport
  ≤1024px as fallback): open the **same generated HTML** in a real new tab via
  `window.open()`, write document, `close()`. Do **not** auto-call `print()` —
  let the user pinch-zoom the clean A4-landscape document and use the browser's
  native "Als PDF sichern" / share sheet. A full top-level document honors the
  layout far better than a hidden iframe.
- **Desktop** (default): keep the current hidden-iframe + auto-`print()` path
  unchanged.

Reuse the existing `CSS` and `body` strings; only the delivery target changes.
Keep the existing `window.open` fallback for the desktop iframe-print failure
case.

## Cross-cutting

- **Version bump** 4.15.0 → 4.16.0 in all 4 places (header comment `Version:`,
  `define('GSH_TP_VERSION', ...)`, `gsh_tp_changelog()` prepended entry,
  changelog block in header comment).
- **Validation:** `php -l plugin/gsh-terminplan.php` after PHP edits. Manual
  check on a real iPad and phone for both items (no automated browser tests in
  this repo).
- **Build** ZIP per `CLAUDE.md` (`curriculr-terminplan-4.16.0.zip` at workspace
  root), manual upload to WP admin.

## Constraints / flags

- **Dual-CSS tension:** `CLAUDE.md` states "All CSS lives exclusively in
  `assets/css/gsh-terminplan.css`", but `gsh_tp_css()` already holds live inline
  CSS that overrides the file. This spec edits where behavior actually lives
  (inline tablet rule + removal of the conflicting `.css` block). A full
  reconciliation of the dual-CSS situation is **out of scope** — noted as
  pre-existing tech debt.

## Scope

Two files:

- `plugin/assets/css/gsh-terminplan.css` — remove one `@media` block.
- `plugin/gsh-terminplan.php` — inline tablet `min-width`, `gtpPrint()` mobile
  branch, 4× version strings, changelog entry.

## Out of scope

- Item 1 code changes (non-admin token generation, password gate).
- Refactoring the inline-vs-file CSS duplication.
- True client-side/server-side PDF file generation.
