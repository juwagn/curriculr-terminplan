# Terminplan Website v2 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend the existing GitHub Pages site with 3 new pages (anleitung, download, impressum) and update index.html nav/footer/CTA, giving other schools a professional, legally protected entry point for the WordPress plugin.

**Architecture:** Pure static HTML, no build system. Design tokens and CSS classes copied verbatim from `website/index.html`. Nav and footer HTML duplicated across pages (no templating). JS is minimal and inline. GitHub Pages serves `website/` folder from `main` branch.

**Tech Stack:** Vanilla HTML/CSS/JS, Plus Jakarta Sans (Google Fonts), SVG icons, GitHub Pages, GitHub Releases for download link.

**Repo:** https://github.com/juwagn/gsh-terminplan — GitHub Pages URL will be `https://juwagn.github.io/gsh-terminplan/`

---

## File Map

| File | Action | Responsibility |
|---|---|---|
| `website/index.html` | Modify | Add "Für andere Schulen" section + update nav + footer Impressum link |
| `website/anleitung.html` | Create | WordPress plugin installation guide (7 steps) |
| `website/download.html` | Create | Disclaimer click-through + download button |
| `website/impressum.html` | Create | TMG Impressum + Haftungsausschluss + DSGVO note |

---

## Shared reference: Design tokens (copy from `website/index.html` `<style>` block)

Every new page needs the full `:root` CSS variable block from `index.html` lines 11–75, the `body`, `.container`, `.eyebrow`, `h2.section-title`, `.section-sub` rules, and the nav + footer CSS. Copy verbatim — do not abbreviate.

---

## Task 1: Update `index.html` — Nav links + Footer Impressum

**Files:**
- Modify: `website/index.html`

### Nav change

The current nav has anchor links (`#download`, `#anleitung`). Update to page links and add Impressum to footer.

- [ ] **Step 1: Replace nav-links in index.html**

Find (around line 920):
```html
    <ul class="nav-links" role="list">
      <li><a href="#download">Downloads</a></li>
      <li><a href="#anleitung">Anleitung</a></li>
```

Replace with:
```html
    <ul class="nav-links" role="list">
      <li><a href="#download">Downloads</a></li>
      <li><a href="anleitung.html">Anleitung</a></li>
```

(Keep remaining nav-links items unchanged. Keep CTA button pointing to `download.html` — see Step 2.)

- [ ] **Step 2: Update nav CTA href**

Find (around line 927):
```html
    <a class="nav-cta" href="#download">
```

Replace with:
```html
    <a class="nav-cta" href="download.html">
```

- [ ] **Step 3: Add Impressum link to footer**

Find the footer `<div class="footer-inner">` block (around line 1572). After the existing `footer-versions` div, add:

```html
    <div class="footer-legal">
      <a href="impressum.html">Impressum &amp; Datenschutz</a>
      <span aria-hidden="true">·</span>
      <a href="https://github.com/juwagn/gsh-terminplan" target="_blank" rel="noopener">GitHub</a>
    </div>
```

Then add this CSS rule in the `<style>` block (after the existing footer CSS):

```css
.footer-legal {
  display: flex; gap: 16px; flex-wrap: wrap;
  font-size: .78rem;
  margin-top: 12px;
}
.footer-legal a {
  color: var(--teal-300);
  text-decoration: none;
  transition: color .15s;
}
.footer-legal a:hover { color: #fff; }
.footer-legal span { color: rgba(255,255,255,.3); }
```

- [ ] **Step 4: Verify in browser**

Open `website/index.html` locally. Check:
- "Anleitung" nav link shows correct href `anleitung.html`
- "Herunterladen" CTA button href = `download.html`
- Footer shows "Impressum & Datenschutz" link

- [ ] **Step 5: Commit**

```bash
git add website/index.html
git commit -m "feat(website): update nav links to pages, add Impressum footer link"
```

---

## Task 2: Add "Für andere Schulen" section to `index.html`

**Files:**
- Modify: `website/index.html`

- [ ] **Step 1: Add CSS for the new section**

In the `<style>` block, add after the existing FAQ styles:

```css
/* ── FÜR ANDERE SCHULEN ──────────────────────────────────── */
.fuer-schulen {
  background: var(--teal-700);
  padding: var(--gap) 0;
}
.fuer-schulen .eyebrow { color: var(--teal-300); }
.fuer-schulen .eyebrow::before { background: var(--teal-400); }
.fuer-schulen h2.section-title { color: #fff; }
.fuer-schulen .section-sub { color: rgba(255,255,255,.75); max-width: 520px; }

.fuer-schulen-actions {
  display: flex; align-items: center; gap: 14px;
  flex-wrap: wrap;
  margin-top: 36px;
}
.btn-amber {
  display: inline-flex; align-items: center; gap: 8px;
  background: var(--amber-500);
  color: #fff;
  font-size: .9rem; font-weight: 700;
  padding: 13px 26px;
  border-radius: var(--r-sm);
  text-decoration: none;
  transition: opacity .15s, transform .1s;
  box-shadow: 0 2px 12px rgba(245,158,11,.4);
  cursor: pointer;
}
.btn-amber:hover { opacity: .9; transform: translateY(-1px); }

.btn-outline-white {
  display: inline-flex; align-items: center; gap: 8px;
  background: transparent;
  color: rgba(255,255,255,.9);
  font-size: .9rem; font-weight: 600;
  padding: 12px 24px;
  border-radius: var(--r-sm);
  border: 1.5px solid rgba(255,255,255,.35);
  text-decoration: none;
  transition: border-color .15s, background .15s;
  cursor: pointer;
}
.btn-outline-white:hover {
  border-color: rgba(255,255,255,.7);
  background: rgba(255,255,255,.08);
}
.fuer-schulen-note {
  margin-top: 16px;
  font-size: .78rem;
  color: rgba(255,255,255,.45);
}
```

- [ ] **Step 2: Insert section HTML before footer**

Find the comment `<!-- ── FOOTER` (around line 1571). Insert before it:

```html
<!-- ── FÜR ANDERE SCHULEN ────────────────────────────────── -->
<section class="fuer-schulen" aria-labelledby="schulen-title">
  <div class="container">
    <span class="eyebrow">Für andere Schulen im Bezirk</span>
    <h2 class="section-title" id="schulen-title">Kostenlos. Open Source.<br>Auf eigene Verantwortung.</h2>
    <p class="section-sub">Das Plugin und der Konverter stehen anderen Schulen im Schulbezirk kostenlos zur Verfügung. Es ist ein privates Hobbyprojekt — ohne Garantien, ohne Support-Pflicht, ohne kommerzielle Absichten.</p>
    <div class="fuer-schulen-actions">
      <a href="download.html" class="btn-amber">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Plugin herunterladen
      </a>
      <a href="anleitung.html" class="btn-outline-white">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        Installationsanleitung
      </a>
    </div>
    <p class="fuer-schulen-note">GPL v2 or later · Privatprojekt von Julian Wagner · Keine Haftung</p>
  </div>
</section>

```

- [ ] **Step 3: Verify in browser**

Open `website/index.html`. Scroll to bottom. Check:
- Dark teal section appears before footer
- Both buttons render with correct icons
- Responsive at 375px: buttons stack vertically (flex-wrap handles this)

- [ ] **Step 4: Commit**

```bash
git add website/index.html
git commit -m "feat(website): add 'Für andere Schulen' CTA section to landing page"
```

---

## Task 3: Create `website/impressum.html`

**Files:**
- Create: `website/impressum.html`

- [ ] **Step 1: Create the file**

Create `website/impressum.html` with the following complete content:

```html
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Impressum & Datenschutz – Terminplan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ── DESIGN TOKENS ───────────────────────────────────────── */
:root {
  --teal-700: #0f766e; --teal-600: #0d9488; --teal-500: #14b8a6;
  --teal-400: #2dd4bf; --teal-300: #5eead4; --teal-200: #99f6e4;
  --teal-100: #ccfbf1; --teal-50: #f0fdfa;
  --amber-500: #f59e0b; --amber-400: #fbbf24;
  --slate-900: #0f172a; --slate-800: #1e293b; --slate-700: #334155;
  --slate-600: #475569; --slate-500: #64748b; --slate-400: #94a3b8;
  --slate-300: #cbd5e1; --slate-200: #e2e8f0; --slate-100: #f1f5f9;
  --slate-50: #f8fafc;
  --ink: var(--slate-900); --ink-2: var(--slate-700); --ink-3: var(--slate-500);
  --line: var(--slate-200); --bg: var(--slate-50); --surface: #ffffff;
  --r-sm: 10px; --r-md: 16px; --r-lg: 24px;
  --shadow-md: 0 4px 16px rgba(15,118,110,.08), 0 1px 4px rgba(15,118,110,.05);
  --font-display: 'Plus Jakarta Sans', system-ui, sans-serif;
  --font-body: 'Plus Jakarta Sans', system-ui, sans-serif;
  --max-w: 1100px; --gap: 96px;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body { font-family: var(--font-body); background: var(--bg); color: var(--ink); line-height: 1.6; -webkit-font-smoothing: antialiased; }
.container { max-width: var(--max-w); margin: 0 auto; padding: 0 32px; }
@media (max-width: 640px) { .container { padding: 0 18px; } }

/* ── NAVIGATION ──────────────────────────────────────────── */
.nav { position: sticky; top: 0; z-index: 100; background: var(--teal-700); box-shadow: 0 2px 16px rgba(15,118,110,.3); }
.nav-inner { max-width: var(--max-w); margin: 0 auto; padding: 0 32px; height: 62px; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
.nav-logo { display: flex; align-items: center; gap: 11px; text-decoration: none; }
.nav-logo-mark { width: 34px; height: 34px; background: rgba(255,255,255,.15); border-radius: 10px; display: flex; align-items: center; justify-content: center; border: 1.5px solid rgba(255,255,255,.25); }
.nav-logo-mark svg { width: 18px; height: 18px; }
.nav-logo-text { font-weight: 700; font-size: .95rem; color: #fff; letter-spacing: -.01em; }
.nav-logo-text span { color: rgba(255,255,255,.6); font-weight: 500; }
.nav-links { display: flex; gap: 28px; list-style: none; }
.nav-links a { font-size: .85rem; font-weight: 500; color: rgba(255,255,255,.75); text-decoration: none; transition: color .15s; }
.nav-links a:hover, .nav-links a[aria-current="page"] { color: #fff; }
.nav-cta { background: var(--amber-500); color: #fff; font-size: .82rem; font-weight: 700; padding: 9px 20px; border-radius: var(--r-sm); text-decoration: none; transition: opacity .15s, transform .1s; display: flex; align-items: center; gap: 7px; box-shadow: 0 2px 8px rgba(245,158,11,.35); }
.nav-cta:hover { opacity: .9; transform: translateY(-1px); }
@media (max-width: 640px) { .nav-links { display: none; } .nav-inner { padding: 0 18px; } }

/* ── HERO STRIP ──────────────────────────────────────────── */
.page-hero { background: linear-gradient(160deg, var(--teal-700) 0%, #065f57 100%); padding: 48px 0 44px; }
.breadcrumb { font-size: .78rem; color: rgba(255,255,255,.55); margin-bottom: 12px; }
.breadcrumb a { color: rgba(255,255,255,.55); text-decoration: none; }
.breadcrumb a:hover { color: rgba(255,255,255,.85); }
.breadcrumb span { margin: 0 6px; }
.page-hero h1 { font-family: var(--font-display); font-size: clamp(1.8rem, 4vw, 2.8rem); font-weight: 800; letter-spacing: -.04em; color: #fff; line-height: 1.1; }

/* ── CONTENT ─────────────────────────────────────────────── */
.legal-content { max-width: 720px; padding: 64px 0 96px; }
.legal-content h2 { font-size: 1.3rem; font-weight: 700; letter-spacing: -.02em; color: var(--ink); margin: 48px 0 14px; padding-top: 48px; border-top: 1px solid var(--line); }
.legal-content h2:first-child { margin-top: 0; padding-top: 0; border-top: none; }
.legal-content h3 { font-size: 1rem; font-weight: 700; color: var(--ink-2); margin: 24px 0 8px; }
.legal-content p { font-size: .95rem; color: var(--ink-2); line-height: 1.75; margin-bottom: 14px; }
.legal-content address { font-style: normal; font-size: .95rem; color: var(--ink-2); line-height: 1.9; }
.legal-content a { color: var(--teal-600); text-decoration: none; }
.legal-content a:hover { text-decoration: underline; }
.legal-content ul { margin: 0 0 14px 20px; }
.legal-content ul li { font-size: .95rem; color: var(--ink-2); line-height: 1.75; margin-bottom: 4px; }
.disclaimer-box { background: #fffbeb; border: 2px solid #fbbf24; border-radius: var(--r-md); padding: 20px 24px; margin: 24px 0; }
.disclaimer-box p { color: #78350f; margin: 0; font-size: .9rem; }

/* ── FOOTER ──────────────────────────────────────────────── */
footer { background: var(--teal-700); }
.footer-inner { max-width: var(--max-w); margin: 0 auto; padding: 40px 32px; display: flex; align-items: flex-start; justify-content: space-between; gap: 32px; flex-wrap: wrap; }
.footer-logo { font-weight: 800; font-size: 1.1rem; color: #fff; letter-spacing: -.02em; margin-bottom: 6px; }
.footer-sub { font-size: .8rem; color: rgba(255,255,255,.5); line-height: 1.6; }
.footer-legal { display: flex; gap: 16px; flex-wrap: wrap; font-size: .78rem; margin-top: 12px; }
.footer-legal a { color: var(--teal-300); text-decoration: none; transition: color .15s; }
.footer-legal a:hover { color: #fff; }
.footer-legal span { color: rgba(255,255,255,.3); }
</style>
</head>
<body>

<!-- ── NAVIGATION ─────────────────────────────────────────── -->
<nav class="nav" role="banner">
  <div class="nav-inner">
    <a class="nav-logo" href="index.html" aria-label="Terminplan Startseite">
      <div class="nav-logo-mark">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      </div>
      <div class="nav-logo-text">Terminplan <span>/ Deine Schule</span></div>
    </a>
    <ul class="nav-links" role="list">
      <li><a href="index.html#download">Downloads</a></li>
      <li><a href="anleitung.html">Anleitung</a></li>
      <li><a href="download.html">Plugin</a></li>
      <li><a href="impressum.html" aria-current="page">Impressum</a></li>
    </ul>
    <a class="nav-cta" href="download.html">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Herunterladen
    </a>
  </div>
</nav>

<!-- ── HERO STRIP ────────────────────────────────────────── -->
<div class="page-hero">
  <div class="container">
    <div class="breadcrumb"><a href="index.html">Startseite</a><span aria-hidden="true">›</span> Impressum</div>
    <h1>Impressum &amp; Datenschutz</h1>
  </div>
</div>

<!-- ── CONTENT ───────────────────────────────────────────── -->
<main>
  <div class="container">
    <div class="legal-content">

      <h2>Impressum</h2>
      <h3>Angaben gemäß § 5 TMG</h3>
      <address>
        Julian Wagner<br>
        c/o Gesamtschule Horst<br>
        Devenstraße 15<br>
        45899 Gelsenkirchen<br><br>
        E-Mail: <a href="mailto:julian.wagner@ges-horst.de">julian.wagner@ges-horst.de</a>
      </address>
      <p style="margin-top:14px; font-size:.85rem; color: var(--ink-3);">
        Hinweis: Diese Website ist ein privates, nicht-kommerzielles Hobbyprojekt.
        Die Schuladresse wird ausschließlich als Kontaktanschrift genutzt.
        Das Projekt steht in keiner offiziellen Verbindung zur Gesamtschule Horst oder ihrem Schulträger.
      </p>

      <h2>Haftungsausschluss</h2>
      <h3>Haftung für Inhalte (§ 7 Abs. 1 TMG)</h3>
      <p>Die Inhalte dieser Website wurden nach bestem Wissen erstellt. Eine Gewähr für Aktualität, Richtigkeit oder Vollständigkeit wird nicht übernommen. Als privater Diensteanbieter bin ich gemäß § 7 Abs. 1 TMG für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich. Nach §§ 8 bis 10 TMG bin ich jedoch nicht verpflichtet, übermittelte oder gespeicherte fremde Informationen zu überwachen.</p>
      <h3>Haftung für Links</h3>
      <p>Diese Website enthält Links zu externen Websites Dritter. Auf deren Inhalte habe ich keinen Einfluss. Für die Inhalte verlinkter Seiten ist stets der jeweilige Anbieter verantwortlich. Zum Zeitpunkt der Verlinkung wurden die Seiten auf mögliche Rechtsverstöße geprüft. Eine dauerhafte inhaltliche Kontrolle ist ohne konkreten Anhaltspunkt nicht zumutbar.</p>

      <h2>Software-Haftungsausschluss</h2>
      <div class="disclaimer-box">
        <p><strong>Wichtig:</strong> Das auf dieser Website angebotene WordPress-Plugin ist ein privates Hobbyprojekt und wurde ohne professionelle Qualitätssicherung entwickelt. Sicherheitslücken können nicht ausgeschlossen werden.</p>
      </div>
      <p>Das Plugin wird <strong>ohne jede Gewährleistung</strong> bereitgestellt — weder ausdrücklich noch stillschweigend, einschließlich, aber nicht beschränkt auf die stillschweigende Gewährleistung der Marktgängigkeit oder Eignung für einen bestimmten Zweck.</p>
      <p>Der Entwickler übernimmt keine Haftung für:</p>
      <ul>
        <li>Datenverlust oder Datenschutzverletzungen durch den Einsatz des Plugins</li>
        <li>Sicherheitsvorfälle oder unbefugte Zugriffe auf WordPress-Instanzen</li>
        <li>Schäden durch fehlerhafte Funktion, Inkompatibilitäten oder Fehlkonfiguration</li>
        <li>Folgeschäden jeglicher Art</li>
      </ul>
      <p>Die Nutzung erfolgt <strong>ausschließlich auf eigenes Risiko</strong>. Vor dem produktiven Einsatz ist eine eigene technische und rechtliche Prüfung durch die nutzende Institution erforderlich — insbesondere hinsichtlich der DSGVO-Konformität beim Verarbeiten personenbezogener Daten.</p>

      <h2>Urheberrecht</h2>
      <p>Das WordPress-Plugin steht unter der <a href="https://www.gnu.org/licenses/old-licenses/gpl-2.0.html" target="_blank" rel="noopener">GPL v2 or later</a>. Website-Inhalte (Texte, Gestaltung) © Julian Wagner. Eine Weiterverwendung der Website-Inhalte ist nur mit ausdrücklicher Genehmigung gestattet, ausgenommen die per GPL lizenzierten Code-Bestandteile.</p>

      <h2>Datenschutz</h2>
      <h3>Verantwortlicher</h3>
      <p>Julian Wagner, c/o Gesamtschule Horst, Devenstraße 15, 45899 Gelsenkirchen<br>
      E-Mail: <a href="mailto:julian.wagner@ges-horst.de">julian.wagner@ges-horst.de</a></p>
      <h3>Hosting: GitHub Pages</h3>
      <p>Diese Website wird über GitHub Pages gehostet (GitHub Inc., 88 Colin P Kelly Jr St, San Francisco, CA 94107, USA). Beim Abruf der Website erhebt GitHub ggf. Server-Logfiles, die IP-Adresse, Zeitstempel und aufgerufene URLs enthalten. Ich habe auf diese Datenverarbeitung keinen Einfluss. Weitere Informationen: <a href="https://docs.github.com/site-policy/privacy-policies/github-general-privacy-statement" target="_blank" rel="noopener">GitHub Datenschutzerklärung</a>.</p>
      <h3>Keine eigene Datenerhebung</h3>
      <p>Diese Website verwendet keine Cookies, kein Tracking, keine Analyse-Tools und keine Kontaktformulare. Es werden keine personenbezogenen Daten durch den Betreiber dieser Website erhoben oder gespeichert.</p>

    </div>
  </div>
</main>

<!-- ── FOOTER ─────────────────────────────────────────────── -->
<footer>
  <div class="footer-inner">
    <div>
      <div class="footer-logo">Terminplan</div>
      <div class="footer-sub">
        Digitaler Schulkalender · Open Source Schul-Projekt<br>
        Entwickelt von Julian Wagner · Stand Mai 2026
      </div>
      <div class="footer-legal">
        <a href="impressum.html" aria-current="page">Impressum &amp; Datenschutz</a>
        <span aria-hidden="true">·</span>
        <a href="https://github.com/juwagn/gsh-terminplan" target="_blank" rel="noopener">GitHub</a>
      </div>
    </div>
  </div>
</footer>

</body>
</html>
```

- [ ] **Step 2: Verify in browser**

Open `website/impressum.html`. Check:
- Nav renders, "Impressum" nav item has white color (aria-current)
- All address data correct
- Disclaimer box (amber border) visible
- Footer Impressum link present
- Responsive at 375px: readable, no horizontal scroll

- [ ] **Step 3: Commit**

```bash
git add website/impressum.html
git commit -m "feat(website): add impressum.html with TMG, Haftungsausschluss, DSGVO"
```

---

## Task 4: Create `website/download.html`

**Files:**
- Create: `website/download.html`

- [ ] **Step 1: Create the file**

```html
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Plugin herunterladen – Terminplan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
  --teal-700: #0f766e; --teal-600: #0d9488; --teal-500: #14b8a6;
  --teal-400: #2dd4bf; --teal-300: #5eead4; --teal-200: #99f6e4;
  --teal-100: #ccfbf1; --teal-50: #f0fdfa;
  --amber-500: #f59e0b; --amber-400: #fbbf24; --amber-100: #fef3c7; --amber-50: #fffbeb;
  --slate-900: #0f172a; --slate-800: #1e293b; --slate-700: #334155;
  --slate-600: #475569; --slate-500: #64748b; --slate-400: #94a3b8;
  --slate-300: #cbd5e1; --slate-200: #e2e8f0; --slate-100: #f1f5f9; --slate-50: #f8fafc;
  --ink: var(--slate-900); --ink-2: var(--slate-700); --ink-3: var(--slate-500);
  --line: var(--slate-200); --bg: var(--slate-50); --surface: #ffffff;
  --r-sm: 10px; --r-md: 16px; --r-lg: 24px;
  --font-display: 'Plus Jakarta Sans', system-ui, sans-serif;
  --font-body: 'Plus Jakarta Sans', system-ui, sans-serif;
  --max-w: 1100px;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body { font-family: var(--font-body); background: var(--bg); color: var(--ink); line-height: 1.6; -webkit-font-smoothing: antialiased; }
.container { max-width: var(--max-w); margin: 0 auto; padding: 0 32px; }
@media (max-width: 640px) { .container { padding: 0 18px; } }

/* nav — identical to other pages */
.nav { position: sticky; top: 0; z-index: 100; background: var(--teal-700); box-shadow: 0 2px 16px rgba(15,118,110,.3); }
.nav-inner { max-width: var(--max-w); margin: 0 auto; padding: 0 32px; height: 62px; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
.nav-logo { display: flex; align-items: center; gap: 11px; text-decoration: none; }
.nav-logo-mark { width: 34px; height: 34px; background: rgba(255,255,255,.15); border-radius: 10px; display: flex; align-items: center; justify-content: center; border: 1.5px solid rgba(255,255,255,.25); }
.nav-logo-mark svg { width: 18px; height: 18px; }
.nav-logo-text { font-weight: 700; font-size: .95rem; color: #fff; letter-spacing: -.01em; }
.nav-logo-text span { color: rgba(255,255,255,.6); font-weight: 500; }
.nav-links { display: flex; gap: 28px; list-style: none; }
.nav-links a { font-size: .85rem; font-weight: 500; color: rgba(255,255,255,.75); text-decoration: none; transition: color .15s; }
.nav-links a:hover, .nav-links a[aria-current="page"] { color: #fff; }
.nav-cta { background: var(--amber-500); color: #fff; font-size: .82rem; font-weight: 700; padding: 9px 20px; border-radius: var(--r-sm); text-decoration: none; transition: opacity .15s, transform .1s; display: flex; align-items: center; gap: 7px; box-shadow: 0 2px 8px rgba(245,158,11,.35); cursor: pointer; }
.nav-cta:hover { opacity: .9; transform: translateY(-1px); }
@media (max-width: 640px) { .nav-links { display: none; } .nav-inner { padding: 0 18px; } }

/* hero strip */
.page-hero { background: linear-gradient(160deg, var(--teal-700) 0%, #065f57 100%); padding: 48px 0 44px; }
.breadcrumb { font-size: .78rem; color: rgba(255,255,255,.55); margin-bottom: 12px; }
.breadcrumb a { color: rgba(255,255,255,.55); text-decoration: none; }
.breadcrumb a:hover { color: rgba(255,255,255,.85); }
.breadcrumb span { margin: 0 6px; }
.page-hero h1 { font-family: var(--font-display); font-size: clamp(1.8rem, 4vw, 2.8rem); font-weight: 800; letter-spacing: -.04em; color: #fff; line-height: 1.1; }
.page-hero p { color: rgba(255,255,255,.7); font-size: 1rem; margin-top: 10px; }

/* download layout */
.dl-wrap { max-width: 640px; margin: 0 auto; padding: 64px 0 96px; }

/* warning box */
.warn-box {
  background: var(--amber-50);
  border: 2px solid var(--amber-400);
  border-radius: var(--r-md);
  padding: 24px 28px;
  margin-bottom: 32px;
}
.warn-box-header {
  display: flex; align-items: center; gap: 10px;
  margin-bottom: 14px;
}
.warn-box-header svg { width: 20px; height: 20px; color: #d97706; flex-shrink: 0; }
.warn-box-header strong { font-size: 1rem; font-weight: 700; color: #78350f; }
.warn-box p { font-size: .9rem; color: #78350f; line-height: 1.7; margin-bottom: 10px; }
.warn-box p:last-child { margin-bottom: 0; }
.warn-box ul { margin: 0 0 10px 18px; }
.warn-box ul li { font-size: .88rem; color: #92400e; line-height: 1.65; margin-bottom: 3px; }

/* checkbox */
.cb-wrap {
  background: var(--surface);
  border: 1.5px solid var(--line);
  border-radius: var(--r-md);
  padding: 20px 24px;
  margin-bottom: 28px;
  transition: border-color .2s;
}
.cb-wrap:has(input:checked) { border-color: var(--teal-400); background: var(--teal-50); }
.cb-label {
  display: flex; align-items: flex-start; gap: 14px;
  cursor: pointer;
}
.cb-label input[type="checkbox"] {
  width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px;
  accent-color: var(--teal-600); cursor: pointer;
}
.cb-label span { font-size: .9rem; color: var(--ink-2); line-height: 1.65; }

/* download button */
.dl-btn {
  display: flex; align-items: center; justify-content: center; gap: 10px;
  width: 100%;
  background: var(--amber-500);
  color: #fff;
  font-family: var(--font-display);
  font-size: 1rem; font-weight: 700;
  padding: 16px 28px;
  border-radius: var(--r-md);
  border: none;
  cursor: not-allowed;
  opacity: 0.38;
  transition: opacity .2s, transform .1s, box-shadow .2s;
  text-decoration: none;
  box-shadow: none;
}
.dl-btn.active {
  cursor: pointer;
  opacity: 1;
  box-shadow: 0 4px 16px rgba(245,158,11,.4);
}
.dl-btn.active:hover { opacity: .9; transform: translateY(-1px); }

.dl-meta { text-align: center; margin-top: 12px; font-size: .78rem; color: var(--ink-3); }
.dl-meta a { color: var(--teal-600); text-decoration: none; }
.dl-meta a:hover { text-decoration: underline; }

/* footer */
footer { background: var(--teal-700); }
.footer-inner { max-width: var(--max-w); margin: 0 auto; padding: 40px 32px; display: flex; align-items: flex-start; justify-content: space-between; gap: 32px; flex-wrap: wrap; }
.footer-logo { font-weight: 800; font-size: 1.1rem; color: #fff; letter-spacing: -.02em; margin-bottom: 6px; }
.footer-sub { font-size: .8rem; color: rgba(255,255,255,.5); line-height: 1.6; }
.footer-legal { display: flex; gap: 16px; flex-wrap: wrap; font-size: .78rem; margin-top: 12px; }
.footer-legal a { color: var(--teal-300); text-decoration: none; transition: color .15s; }
.footer-legal a:hover { color: #fff; }
.footer-legal span { color: rgba(255,255,255,.3); }
</style>
</head>
<body>

<nav class="nav" role="banner">
  <div class="nav-inner">
    <a class="nav-logo" href="index.html" aria-label="Terminplan Startseite">
      <div class="nav-logo-mark">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      </div>
      <div class="nav-logo-text">Terminplan <span>/ Deine Schule</span></div>
    </a>
    <ul class="nav-links" role="list">
      <li><a href="index.html#download">Downloads</a></li>
      <li><a href="anleitung.html">Anleitung</a></li>
      <li><a href="download.html" aria-current="page">Plugin</a></li>
      <li><a href="impressum.html">Impressum</a></li>
    </ul>
    <a class="nav-cta" href="download.html" aria-current="page">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Herunterladen
    </a>
  </div>
</nav>

<div class="page-hero">
  <div class="container">
    <div class="breadcrumb"><a href="index.html">Startseite</a><span aria-hidden="true">›</span> Plugin herunterladen</div>
    <h1>Plugin herunterladen</h1>
    <p>Bitte lies die folgenden Hinweise sorgfältig, bevor du das Plugin herunterlädst.</p>
  </div>
</div>

<main>
  <div class="container">
    <div class="dl-wrap">

      <div class="warn-box" role="region" aria-label="Wichtige Hinweise">
        <div class="warn-box-header">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          <strong>Wichtige Hinweise vor dem Download</strong>
        </div>
        <p>Dieses WordPress-Plugin ist ein <strong>privates Hobbyprojekt</strong> und wurde ohne professionelle Qualitätssicherung entwickelt. Sicherheitslücken können nicht ausgeschlossen werden.</p>
        <p>Vor dem produktiven Einsatz muss die nutzende Schule folgendes selbst prüfen und verantworten:</p>
        <ul>
          <li>Technische Eignung für die vorhandene WordPress- und PHP-Version</li>
          <li>DSGVO-Konformität beim Verarbeiten von Termindaten</li>
          <li>Sicherheitsrisiken beim Einsatz in der Schulinfrastruktur</li>
          <li>Kompatibilität mit installierten Themes und Plugins</li>
        </ul>
        <p>Das Plugin wird <strong>ohne jede Gewährleistung</strong> bereitgestellt. Der Entwickler übernimmt keine Haftung für Schäden jeglicher Art. Weitere Details im <a href="impressum.html" style="color:#b45309;font-weight:600;">Impressum &amp; Haftungsausschluss</a>.</p>
      </div>

      <div class="cb-wrap">
        <label class="cb-label" for="disclaimer-cb">
          <input type="checkbox" id="disclaimer-cb" aria-describedby="cb-desc">
          <span id="cb-desc">Ich habe die obigen Hinweise gelesen und verstanden. Ich lade das Plugin auf eigenes Risiko herunter und stelle sicher, dass meine Schule die Nutzung eigenverantwortlich prüft.</span>
        </label>
      </div>

      <a id="dl-btn" class="dl-btn" href="#" aria-disabled="true" role="button"
         tabindex="0" aria-label="Plugin herunterladen (erst nach Zustimmung verfügbar)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Plugin herunterladen (.zip)
      </a>
      <p class="dl-meta">
        Version 3.17.0 · GPL v2 or later ·
        <a href="https://github.com/juwagn/gsh-terminplan/releases" target="_blank" rel="noopener">Alle Versionen auf GitHub</a>
      </p>

    </div>
  </div>
</main>

<footer>
  <div class="footer-inner">
    <div>
      <div class="footer-logo">Terminplan</div>
      <div class="footer-sub">Digitaler Schulkalender · Open Source Schul-Projekt<br>Entwickelt von Julian Wagner · Stand Mai 2026</div>
      <div class="footer-legal">
        <a href="impressum.html">Impressum &amp; Datenschutz</a>
        <span aria-hidden="true">·</span>
        <a href="https://github.com/juwagn/gsh-terminplan" target="_blank" rel="noopener">GitHub</a>
      </div>
    </div>
  </div>
</footer>

<script>
const RELEASE_URL = 'https://github.com/juwagn/gsh-terminplan/releases/latest/download/gsh-terminplan.zip';
const cb  = document.getElementById('disclaimer-cb');
const btn = document.getElementById('dl-btn');

cb.addEventListener('change', function () {
  if (this.checked) {
    btn.href = RELEASE_URL;
    btn.removeAttribute('aria-disabled');
    btn.setAttribute('aria-label', 'Plugin herunterladen');
    btn.classList.add('active');
  } else {
    btn.href = '#';
    btn.setAttribute('aria-disabled', 'true');
    btn.setAttribute('aria-label', 'Plugin herunterladen (erst nach Zustimmung verfügbar)');
    btn.classList.remove('active');
  }
});

btn.addEventListener('click', function (e) {
  if (btn.getAttribute('aria-disabled') === 'true') e.preventDefault();
});
</script>
</body>
</html>
```

**Note on RELEASE_URL:** The download points to `https://github.com/juwagn/gsh-terminplan/releases/latest/download/gsh-terminplan.zip`. This works automatically when GitHub Releases contain a file named `gsh-terminplan.zip`. If no release asset exists, users land on a 404. Create a GitHub Release with the plugin zip named `gsh-terminplan.zip` before publishing.

- [ ] **Step 2: Verify in browser**

Open `website/download.html`. Check:
- Button starts disabled (greyed, `cursor: not-allowed`)
- Ticking checkbox enables button (amber, active)
- Unticking disables again
- Amber warning box renders correctly
- Responsive at 375px: no horizontal scroll

- [ ] **Step 3: Commit**

```bash
git add website/download.html
git commit -m "feat(website): add download.html with disclaimer click-through"
```

---

## Task 5: Create `website/anleitung.html`

**Files:**
- Create: `website/anleitung.html`

- [ ] **Step 1: Create the file**

```html
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Installationsanleitung – Terminplan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
  --teal-700: #0f766e; --teal-600: #0d9488; --teal-500: #14b8a6;
  --teal-400: #2dd4bf; --teal-300: #5eead4; --teal-200: #99f6e4;
  --teal-100: #ccfbf1; --teal-50: #f0fdfa;
  --amber-500: #f59e0b; --amber-400: #fbbf24; --amber-50: #fffbeb;
  --slate-900: #0f172a; --slate-800: #1e293b; --slate-700: #334155;
  --slate-600: #475569; --slate-500: #64748b; --slate-400: #94a3b8;
  --slate-300: #cbd5e1; --slate-200: #e2e8f0; --slate-100: #f1f5f9; --slate-50: #f8fafc;
  --ink: var(--slate-900); --ink-2: var(--slate-700); --ink-3: var(--slate-500);
  --line: var(--slate-200); --bg: var(--slate-50); --surface: #ffffff;
  --r-sm: 10px; --r-md: 16px; --r-lg: 24px;
  --shadow-sm: 0 1px 3px rgba(15,118,110,.06);
  --shadow-md: 0 4px 16px rgba(15,118,110,.08), 0 1px 4px rgba(15,118,110,.05);
  --font-display: 'Plus Jakarta Sans', system-ui, sans-serif;
  --font-body: 'Plus Jakarta Sans', system-ui, sans-serif;
  --max-w: 1100px; --gap: 96px;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body { font-family: var(--font-body); background: var(--bg); color: var(--ink); line-height: 1.6; -webkit-font-smoothing: antialiased; }
.container { max-width: var(--max-w); margin: 0 auto; padding: 0 32px; }
@media (max-width: 640px) { .container { padding: 0 18px; } }

/* nav */
.nav { position: sticky; top: 0; z-index: 100; background: var(--teal-700); box-shadow: 0 2px 16px rgba(15,118,110,.3); }
.nav-inner { max-width: var(--max-w); margin: 0 auto; padding: 0 32px; height: 62px; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
.nav-logo { display: flex; align-items: center; gap: 11px; text-decoration: none; }
.nav-logo-mark { width: 34px; height: 34px; background: rgba(255,255,255,.15); border-radius: 10px; display: flex; align-items: center; justify-content: center; border: 1.5px solid rgba(255,255,255,.25); }
.nav-logo-mark svg { width: 18px; height: 18px; }
.nav-logo-text { font-weight: 700; font-size: .95rem; color: #fff; letter-spacing: -.01em; }
.nav-logo-text span { color: rgba(255,255,255,.6); font-weight: 500; }
.nav-links { display: flex; gap: 28px; list-style: none; }
.nav-links a { font-size: .85rem; font-weight: 500; color: rgba(255,255,255,.75); text-decoration: none; transition: color .15s; }
.nav-links a:hover, .nav-links a[aria-current="page"] { color: #fff; }
.nav-cta { background: var(--amber-500); color: #fff; font-size: .82rem; font-weight: 700; padding: 9px 20px; border-radius: var(--r-sm); text-decoration: none; transition: opacity .15s, transform .1s; display: flex; align-items: center; gap: 7px; box-shadow: 0 2px 8px rgba(245,158,11,.35); }
.nav-cta:hover { opacity: .9; transform: translateY(-1px); }
@media (max-width: 640px) { .nav-links { display: none; } .nav-inner { padding: 0 18px; } }

/* hero */
.page-hero { background: linear-gradient(160deg, var(--teal-700) 0%, #065f57 100%); padding: 48px 0 44px; }
.breadcrumb { font-size: .78rem; color: rgba(255,255,255,.55); margin-bottom: 12px; }
.breadcrumb a { color: rgba(255,255,255,.55); text-decoration: none; }
.breadcrumb a:hover { color: rgba(255,255,255,.85); }
.breadcrumb span { margin: 0 6px; }
.page-hero h1 { font-family: var(--font-display); font-size: clamp(1.8rem, 4vw, 2.8rem); font-weight: 800; letter-spacing: -.04em; color: #fff; line-height: 1.1; }
.page-hero p { color: rgba(255,255,255,.7); font-size: 1rem; margin-top: 10px; max-width: 540px; }

/* badges */
.req-row { display: flex; flex-wrap: wrap; gap: 10px; margin: 36px 0; }
.req-badge { display: inline-flex; align-items: center; gap: 8px; background: var(--teal-50); border: 1.5px solid var(--teal-200); color: var(--teal-700); font-size: .82rem; font-weight: 700; padding: 7px 14px; border-radius: 100px; }
.req-badge svg { width: 14px; height: 14px; flex-shrink: 0; }

/* steps */
.steps-section { padding: 64px 0 0; }
.steps-section h2 { font-family: var(--font-display); font-size: clamp(1.6rem, 3.5vw, 2.2rem); font-weight: 800; letter-spacing: -.03em; margin-bottom: 10px; }
.steps-section p.lead { font-size: 1rem; color: var(--ink-3); margin-bottom: 48px; }

.step-list { display: flex; flex-direction: column; gap: 0; }
.step-item { display: flex; gap: 28px; position: relative; padding-bottom: 44px; }
.step-item:last-child { padding-bottom: 0; }
.step-item:last-child .step-line { display: none; }
.step-left { display: flex; flex-direction: column; align-items: center; flex-shrink: 0; }
.step-num {
  width: 40px; height: 40px; border-radius: 50%;
  background: var(--teal-600); color: #fff;
  font-size: .9rem; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; z-index: 1;
  box-shadow: 0 0 0 4px var(--teal-50);
}
.step-line { width: 2px; flex: 1; background: var(--teal-200); margin-top: 8px; }
.step-body { padding-top: 8px; flex: 1; min-width: 0; }
.step-body h3 { font-size: 1.05rem; font-weight: 700; letter-spacing: -.02em; margin-bottom: 8px; color: var(--ink); }
.step-body p { font-size: .9rem; color: var(--ink-2); line-height: 1.7; margin-bottom: 10px; }
.step-body p:last-child { margin-bottom: 0; }
.step-code { background: var(--slate-900); color: #e2e8f0; font-family: 'Consolas', 'Monaco', monospace; font-size: .82rem; padding: 14px 18px; border-radius: var(--r-sm); margin-top: 10px; overflow-x: auto; line-height: 1.6; }
.step-code .hlg { color: var(--teal-300); }
.step-link { display: inline-flex; align-items: center; gap: 6px; color: var(--teal-600); font-size: .88rem; font-weight: 600; text-decoration: none; margin-top: 8px; }
.step-link:hover { text-decoration: underline; }
.step-link svg { width: 14px; height: 14px; }

@media (max-width: 640px) {
  .step-item { gap: 16px; }
  .step-num { width: 32px; height: 32px; font-size: .8rem; }
}

/* workflow diagram */
.flow-section { padding: 64px 0; }
.flow-section h2 { font-family: var(--font-display); font-size: clamp(1.4rem, 3vw, 2rem); font-weight: 800; letter-spacing: -.03em; margin-bottom: 8px; }
.flow-section p.lead { font-size: .95rem; color: var(--ink-3); margin-bottom: 36px; }
.flow-track { display: flex; align-items: stretch; gap: 0; overflow-x: auto; padding-bottom: 4px; }
.flow-node { flex: 1; min-width: 130px; background: var(--surface); border: 1.5px solid var(--line); border-radius: var(--r-md); padding: 20px 16px; text-align: center; box-shadow: var(--shadow-sm); }
.flow-node-icon { width: 40px; height: 40px; border-radius: 12px; background: var(--teal-50); border: 1.5px solid var(--teal-100); display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; }
.flow-node-icon svg { width: 20px; height: 20px; color: var(--teal-600); }
.flow-node-title { font-size: .82rem; font-weight: 700; color: var(--ink); line-height: 1.3; margin-bottom: 4px; }
.flow-node-sub { font-size: .72rem; color: var(--ink-3); line-height: 1.4; }
.flow-arrow { display: flex; align-items: center; padding: 0 6px; flex-shrink: 0; color: var(--teal-300); }
.flow-arrow svg { width: 20px; height: 20px; }
@media (max-width: 768px) { .flow-track { gap: 6px; } .flow-arrow { padding: 0 2px; } }

/* faq */
.faq-section { padding: 0 0 96px; }
.faq-section h2 { font-family: var(--font-display); font-size: clamp(1.4rem, 3vw, 2rem); font-weight: 800; letter-spacing: -.03em; margin-bottom: 32px; }
.faq-list { display: flex; flex-direction: column; gap: 2px; }
details.faq-item { border: 1.5px solid var(--line); border-radius: var(--r-md); background: var(--surface); overflow: hidden; }
details.faq-item + details.faq-item { margin-top: 8px; }
details.faq-item[open] { border-color: var(--teal-300); }
summary.faq-q { list-style: none; display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 18px 22px; font-size: .95rem; font-weight: 600; color: var(--ink); cursor: pointer; user-select: none; }
summary.faq-q::-webkit-details-marker { display: none; }
.faq-chevron { width: 18px; height: 18px; flex-shrink: 0; color: var(--ink-3); transition: transform .2s; }
details[open] .faq-chevron { transform: rotate(180deg); }
.faq-body { padding: 0 22px 18px; font-size: .9rem; color: var(--ink-2); line-height: 1.75; }
.faq-body a { color: var(--teal-600); text-decoration: none; }
.faq-body a:hover { text-decoration: underline; }
.faq-body code { background: var(--slate-100); padding: 1px 6px; border-radius: 4px; font-size: .85em; font-family: 'Consolas', monospace; }

/* footer */
footer { background: var(--teal-700); }
.footer-inner { max-width: var(--max-w); margin: 0 auto; padding: 40px 32px; display: flex; align-items: flex-start; justify-content: space-between; gap: 32px; flex-wrap: wrap; }
.footer-logo { font-weight: 800; font-size: 1.1rem; color: #fff; letter-spacing: -.02em; margin-bottom: 6px; }
.footer-sub { font-size: .8rem; color: rgba(255,255,255,.5); line-height: 1.6; }
.footer-legal { display: flex; gap: 16px; flex-wrap: wrap; font-size: .78rem; margin-top: 12px; }
.footer-legal a { color: var(--teal-300); text-decoration: none; transition: color .15s; }
.footer-legal a:hover { color: #fff; }
.footer-legal span { color: rgba(255,255,255,.3); }
</style>
</head>
<body>

<nav class="nav" role="banner">
  <div class="nav-inner">
    <a class="nav-logo" href="index.html" aria-label="Terminplan Startseite">
      <div class="nav-logo-mark">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      </div>
      <div class="nav-logo-text">Terminplan <span>/ Deine Schule</span></div>
    </a>
    <ul class="nav-links" role="list">
      <li><a href="index.html#download">Downloads</a></li>
      <li><a href="anleitung.html" aria-current="page">Anleitung</a></li>
      <li><a href="download.html">Plugin</a></li>
      <li><a href="impressum.html">Impressum</a></li>
    </ul>
    <a class="nav-cta" href="download.html">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Herunterladen
    </a>
  </div>
</nav>

<div class="page-hero">
  <div class="container">
    <div class="breadcrumb"><a href="index.html">Startseite</a><span aria-hidden="true">›</span> Anleitung</div>
    <h1>Installationsanleitung</h1>
    <p>WordPress-Plugin einrichten, iCal-Feed verbinden, Termine online stellen — in 7 Schritten.</p>
  </div>
</div>

<main>
  <div class="container">

    <!-- Voraussetzungen -->
    <div class="req-row" role="list" aria-label="Systemvoraussetzungen">
      <div class="req-badge" role="listitem">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
        WordPress 6.0 oder höher
      </div>
      <div class="req-badge" role="listitem">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
        PHP 8.0 oder höher
      </div>
      <div class="req-badge" role="listitem">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
        IServ iCal-Feed (HTTPS)
      </div>
      <div class="req-badge" role="listitem">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
        WordPress-Admin-Zugang
      </div>
    </div>

    <!-- Steps -->
    <section class="steps-section" aria-labelledby="steps-title">
      <h2 id="steps-title">Schritt für Schritt</h2>
      <p class="lead">Folge den Schritten der Reihe nach. Der erste Einsatz dauert ca. 30 Minuten.</p>

      <ol class="step-list" role="list">

        <li class="step-item">
          <div class="step-left" aria-hidden="true"><div class="step-num">1</div><div class="step-line"></div></div>
          <div class="step-body">
            <h3>Plugin herunterladen</h3>
            <p>Öffne die Download-Seite und stimme dem Haftungsausschluss zu. Der Download startet als ZIP-Datei.</p>
            <a href="download.html" class="step-link">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              Zum Download
            </a>
          </div>
        </li>

        <li class="step-item">
          <div class="step-left" aria-hidden="true"><div class="step-num">2</div><div class="step-line"></div></div>
          <div class="step-body">
            <h3>Plugin in WordPress hochladen</h3>
            <p>Im WordPress-Adminbereich: <strong>Plugins → Installieren → Plugin hochladen</strong>. Die heruntergeladene ZIP-Datei auswählen und auf "Jetzt installieren" klicken.</p>
            <p>Alternativ: Den Ordner <code>gsh-terminplan</code> aus der ZIP manuell nach <code>wp-content/plugins/</code> kopieren (z.&nbsp;B. per SFTP).</p>
          </div>
        </li>

        <li class="step-item">
          <div class="step-left" aria-hidden="true"><div class="step-num">3</div><div class="step-line"></div></div>
          <div class="step-body">
            <h3>Plugin aktivieren</h3>
            <p>Nach dem Upload: <strong>Plugins → Installierte Plugins</strong>. Das Plugin "Schul-Terminplan" in der Liste suchen und auf "Aktivieren" klicken. Danach erscheint unter <strong>Einstellungen</strong> ein neuer Menüpunkt "Schul-Terminplan".</p>
          </div>
        </li>

        <li class="step-item">
          <div class="step-left" aria-hidden="true"><div class="step-num">4</div><div class="step-line"></div></div>
          <div class="step-body">
            <h3>iCal-URL aus IServ eintragen</h3>
            <p>In IServ: <strong>Kalender → Einstellungen → Externen Zugriff einrichten</strong>. Die HTTPS-URL des iCal-Feeds kopieren (endet auf <code>.ics</code>).</p>
            <p>Im WordPress-Admin unter <strong>Einstellungen → Schul-Terminplan → Profil-Tab</strong>: URL einfügen, Cache-Dauer festlegen, speichern. Das Plugin lädt den Feed beim ersten Speichern automatisch.</p>
            <div class="step-code">Beispiel-URL:<br><span class="hlg">https://iserv.deine-schule.de/iserv/calendar/pub/xyz123.ics</span></div>
          </div>
        </li>

        <li class="step-item">
          <div class="step-left" aria-hidden="true"><div class="step-num">5</div><div class="step-line"></div></div>
          <div class="step-body">
            <h3>Schuljahres-Vorlage erstellen (optional)</h3>
            <p>Falls Termine noch nicht in IServ eingetragen sind: Den <strong>ICS-Konverter</strong> öffnen, im Schritt 2 die Schuljahres-Daten und Ferien eintragen, Vorlage herunterladen. Die Excel-Datei dann in Excel befüllen.</p>
            <a href="index.html#download" class="step-link">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
              Konverter auf der Startseite
            </a>
          </div>
        </li>

        <li class="step-item">
          <div class="step-left" aria-hidden="true"><div class="step-num">6</div><div class="step-line"></div></div>
          <div class="step-body">
            <h3>Excel-Termine in ICS umwandeln (optional)</h3>
            <p>Im ICS-Konverter: Excel-Vorlage importieren (Schritt 3), Kategorien prüfen, ICS-Datei exportieren (Schritt 4). Die erzeugte <code>.ics</code>-Datei in IServ oder direkt in WordPress importieren.</p>
            <p>Dieser Schritt ist nur nötig, wenn Termine über die Excel-Vorlage erfasst wurden. Bei reiner IServ-Nutzung entfällt er.</p>
          </div>
        </li>

        <li class="step-item">
          <div class="step-left" aria-hidden="true"><div class="step-num">7</div><div class="step-line"></div></div>
          <div class="step-body">
            <h3>Shortcode auf einer WordPress-Seite einfügen</h3>
            <p>Eine neue Seite in WordPress erstellen (z.&nbsp;B. "Terminplan") und folgenden Shortcode einfügen:</p>
            <div class="step-code"><span class="hlg">[gsh_terminplan]</span></div>
            <p>Seite veröffentlichen. Der Kalender erscheint sofort mit Quartalansicht, Kategorie-Filter und Volltextsuche. Für IServ-Einbettung (Kiosk-Modus) die Token-URL in den Plugin-Einstellungen konfigurieren.</p>
          </div>
        </li>

      </ol>
    </section>

    <!-- Workflow Diagram -->
    <section class="flow-section" aria-labelledby="flow-title">
      <h2 id="flow-title">Gesamtablauf</h2>
      <p class="lead">So hängen die Komponenten zusammen.</p>
      <div class="flow-track" role="img" aria-label="Ablaufdiagramm: Excel befüllen zu Konverter zu ICS-Datei zu IServ zu WordPress Plugin zu Schulwebsite">
        <div class="flow-node">
          <div class="flow-node-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
          </div>
          <div class="flow-node-title">Excel-Vorlage</div>
          <div class="flow-node-sub">Termine eintragen</div>
        </div>
        <div class="flow-arrow" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></div>
        <div class="flow-node">
          <div class="flow-node-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
          </div>
          <div class="flow-node-title">ICS-Konverter</div>
          <div class="flow-node-sub">Excel importieren</div>
        </div>
        <div class="flow-arrow" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></div>
        <div class="flow-node">
          <div class="flow-node-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          </div>
          <div class="flow-node-title">.ics Datei</div>
          <div class="flow-node-sub">in IServ laden</div>
        </div>
        <div class="flow-arrow" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></div>
        <div class="flow-node">
          <div class="flow-node-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
          </div>
          <div class="flow-node-title">IServ iCal-Feed</div>
          <div class="flow-node-sub">HTTPS-URL</div>
        </div>
        <div class="flow-arrow" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></div>
        <div class="flow-node">
          <div class="flow-node-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          </div>
          <div class="flow-node-title">WP Plugin</div>
          <div class="flow-node-sub">Feed abrufen</div>
        </div>
        <div class="flow-arrow" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></div>
        <div class="flow-node">
          <div class="flow-node-icon" style="background: var(--amber-50); border-color: var(--amber-400);">
            <svg viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          </div>
          <div class="flow-node-title">Schulwebsite</div>
          <div class="flow-node-sub">Terminplan live</div>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section class="faq-section" aria-labelledby="faq-title">
      <h2 id="faq-title">Häufige Fragen</h2>
      <div class="faq-list">

        <details class="faq-item">
          <summary class="faq-q">
            Funktioniert das Plugin auch ohne IServ?
            <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
          </summary>
          <div class="faq-body">Ja, mit Einschränkungen. Das Plugin liest jeden gültigen iCal-Feed (HTTPS). Der Konverter erzeugt eine <code>.ics</code>-Datei, die als statische Datei auf einem Webserver gehostet werden kann. Ohne IServ entfällt der automatische Sync — die ICS-Datei muss manuell erneuert und neu verlinkt werden.</div>
        </details>

        <details class="faq-item">
          <summary class="faq-q">
            Wie oft wird der iCal-Feed aktualisiert?
            <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
          </summary>
          <div class="faq-body">Die Cache-Dauer ist in den Plugin-Einstellungen konfigurierbar (Standard: 60 Minuten). Das Plugin nutzt WordPress-Cron für Hintergrundaktualisierungen. Änderungen in IServ sind spätestens nach Ablauf des Cache-Intervalls sichtbar.</div>
        </details>

        <details class="faq-item">
          <summary class="faq-q">
            Kann ich eigene Kategorien anlegen?
            <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
          </summary>
          <div class="faq-body">Ja. Unter <strong>Einstellungen → Schul-Terminplan → Kategorien</strong> können Kategorien hinzugefügt, umbenannt, eingefärbt und mit Stichwörtern für automatisches Matching ausgestattet werden. Bis zu 20 Kategorien, die Farben frei wählbar.</div>
        </details>

        <details class="faq-item">
          <summary class="faq-q">
            Was passiert bei WordPress-Updates?
            <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
          </summary>
          <div class="faq-body">Das Plugin wird nicht automatisch mit WordPress aktualisiert (kein WordPress.org-Verzeichnis). Nach einem WordPress-Major-Update empfiehlt sich ein Test in einer Staging-Umgebung. Bei Kompatibilitätsproblemen bitte ein <a href="https://github.com/juwagn/gsh-terminplan/issues" target="_blank" rel="noopener">GitHub Issue</a> öffnen.</div>
        </details>

        <details class="faq-item">
          <summary class="faq-q">
            Wo melde ich Fehler oder Verbesserungsvorschläge?
            <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
          </summary>
          <div class="faq-body">Über <a href="https://github.com/juwagn/gsh-terminplan/issues" target="_blank" rel="noopener">GitHub Issues</a>. Kein Support garantiert — das ist ein Hobbyprojekt. Fehlermeldungen mit WordPress-Version, PHP-Version und konkreter Fehlerbeschreibung werden bevorzugt bearbeitet.</div>
        </details>

      </div>
    </section>

  </div>
</main>

<footer>
  <div class="footer-inner">
    <div>
      <div class="footer-logo">Terminplan</div>
      <div class="footer-sub">Digitaler Schulkalender · Open Source Schul-Projekt<br>Entwickelt von Julian Wagner · Stand Mai 2026</div>
      <div class="footer-legal">
        <a href="impressum.html">Impressum &amp; Datenschutz</a>
        <span aria-hidden="true">·</span>
        <a href="https://github.com/juwagn/gsh-terminplan" target="_blank" rel="noopener">GitHub</a>
      </div>
    </div>
  </div>
</footer>

</body>
</html>
```

- [ ] **Step 2: Verify in browser**

Open `website/anleitung.html`. Check:
- 7 numbered steps render with teal circles and vertical connecting lines
- Flow diagram shows all 6 nodes with arrows (scroll horizontally on mobile)
- FAQ accordions open/close via `<details>`/`<summary>` (no JS needed)
- Responsive at 375px: steps readable, no horizontal scroll on main content

- [ ] **Step 3: Commit**

```bash
git add website/anleitung.html
git commit -m "feat(website): add anleitung.html with 7-step guide, flow diagram, FAQ"
```

---

## Task 6: GitHub Pages config + Release asset

**Files:**
- No code files — GitHub settings + release

- [ ] **Step 1: Enable GitHub Pages**

In the GitHub repo (`https://github.com/juwagn/gsh-terminplan`):
1. Settings → Pages
2. Source: "Deploy from a branch"
3. Branch: `main`, Folder: `/website`
4. Save

After a few minutes, the site is live at `https://juwagn.github.io/gsh-terminplan/`

- [ ] **Step 2: Create a GitHub Release with plugin ZIP**

So the download button in `download.html` works (it links to `latest/download/gsh-terminplan.zip`):

1. In GitHub: Releases → Draft a new release
2. Tag: `v3.17.0`
3. Title: `v3.17.0`
4. Attach file: the plugin zip, named exactly `gsh-terminplan.zip` (zip the `plugin/` folder as `gsh-terminplan`)
5. Publish release

The URL `https://github.com/juwagn/gsh-terminplan/releases/latest/download/gsh-terminplan.zip` now resolves.

- [ ] **Step 3: Verify live site**

Open `https://juwagn.github.io/gsh-terminplan/` in browser. Check:
- All 4 pages load (index, anleitung, download, impressum)
- Nav links work cross-page
- Download button activates after checkbox
- Impressum shows correct address

- [ ] **Step 4: Final commit (update footer dates if needed)**

```bash
git add website/
git commit -m "chore(website): final verification pass before GitHub Pages launch"
```

---

## Self-Review

**Spec coverage:**
- Access model C (public + disclaimer download): covered by download.html click-through
- Site structure hybrid A+B: index.html extended (Tasks 1+2) + 3 new pages (Tasks 3-5)
- Design language: all pages use identical tokens from existing index.html
- Impressum TMG: covered in Task 3 with real address/email
- Haftungsausschluss (software): covered in both impressum.html and download.html warning box
- GitHub Pages hosting: covered in Task 6
- Nav updated: covered in Task 1
- Footer Impressum link: covered in Task 1

**No placeholders present.** All HTML is complete and runnable.

**Type/name consistency:** No shared JS functions across pages. `dl-btn`, `disclaimer-cb` IDs only in download.html. `RELEASE_URL` constant defined in download.html script block.

**Potential gap:** `index.html` footer currently shows "Stand März 2026" and "ICS Konverter v2.0". These should be updated to current values when Task 1 runs — update to "Stand Mai 2026" and "ICS Konverter v2.5".
