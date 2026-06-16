# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Syntax-check after every PHP change (mandatory before deploy)
php -l plugin/gsh-terminplan.php
php -l plugin/curriculr-auth.php
php -l plugin/curriculr-data-layer.php
php -l plugin/curriculr-guard.php

# Run individual test files
php tests/curriculr/test-auth.php
php tests/curriculr/test-guard.php
php tests/curriculr/test-ics.php
php tests/curriculr/test-stage.php
php tests/curriculr/test-revisions.php
php tests/curriculr/test-envelope.php
php tests/curriculr/test-integration-stubbed.php
php tests/curriculr/test-version.php
```

No build system. No Composer. No npm. Edit files, `php -l`, test, ZIP + upload.

## Plugin ZIP Deployment

ZIPs are built from `curriculr-terminplan/plugin/` and placed at the workspace root.  
ZIP contents: 5 PHP files at root + `assets/css/` subdir.

To build a new ZIP (PHP files at root + assets/ subdir):
```bash
cd curriculr-terminplan/plugin
VER=$(grep "define.*GSH_TP_VERSION" gsh-terminplan.php | grep -oE "[0-9]+\.[0-9]+\.[0-9]+" | head -1)
zip ../../curriculr-terminplan-$VER.zip gsh-terminplan.php curriculr-data-layer.php curriculr-auth.php curriculr-guard.php page-terminplan-entwurf.php
zip -r ../../curriculr-terminplan-$VER.zip assets/
```
ZIP contents: 5 PHP files at root (incl. `page-terminplan-entwurf.php` page template, loaded from the plugin dir) + `assets/css/design-tokens.css` + `assets/css/gsh-terminplan.css`.

## Plugin File Architecture

```
plugin/
  gsh-terminplan.php          # Main plugin entry — iCal fetch/cache, admin UI,
                              #   shortcode renderer, profiles, quartals, CSS/JS
  curriculr-auth.php          # IServ OIDC flow: /auth/authorize, /auth/callback,
                              #   POST /auth/token (app-token exchange + issuance)
  curriculr-guard.php         # Bearer app-token validation; permission_callback
                              #   for all curriculr/v1 routes
  curriculr-data-layer.php    # REST curriculr/v1: doc GET/PUT, revisions, ICS feed,
                              #   DB install/migrate, snapshot, stage management
```

Load order enforced by `require_once` sequence in `gsh-terminplan.php`:  
`curriculr-auth.php` → `curriculr-guard.php` → `curriculr-data-layer.php`

## REST API (`curriculr/v1`)

All routes except `/health` and `/auth/*` require Bearer app-token (validated by `gsh_tp_curriculr_guard_perm()`):

| Method | Route | Handler |
|--------|-------|---------|
| GET | `/health` | `gsh_tp_curriculr_rest_health` |
| GET | `/doc/{sj}` | `gsh_tp_curriculr_rest_get` |
| PUT | `/doc/{sj}` | `gsh_tp_curriculr_rest_put` |
| GET | `/doc/{sj}/revisions` | `gsh_tp_curriculr_rest_revisions_list` |
| GET | `/doc/{sj}/revisions/{id}` | `gsh_tp_curriculr_rest_revision_get` |
| GET | `/feed/{sj}/{token}.ics` | `gsh_tp_curriculr_rest_feed` (token-auth, public) |
| POST | `/auth/token` | (curriculr-auth) app-token exchange |

**Conflict detection:** PUT compares `base_version` in request envelope with current `version` in DB. Mismatch → 409 with `authorName`/`savedAt` from revisions table.

## Database

Two custom tables (created by `gsh_tp_curriculr_install()`, called on `register_activation_hook`):

- `wp_curriculr_docs` — one row per `schuljahr_id`: `sj`, `version`, `stage`, `doc_json`, `updated_at`
- `wp_curriculr_doc_revisions` — append-only log: `sj`, `version`, `json_str`, `author_sub`, `author_name`, `created_at`. Pruned to 50 per sj by `gsh_tp_curriculr_prune_revisions()`.

DB schema version tracked in `GSH_TP_DB_VERSION` constant. `dbDelta()` handles additive migrations — never drop columns.

## Versioning (4 places must stay in sync)

In `gsh-terminplan.php`:
1. Plugin header comment: `* Version: X.Y.Z`
2. `define('GSH_TP_VERSION', 'X.Y.Z')`
3. `gsh_tp_changelog()` — prepend a new entry
4. Changelog block in the header comment

Bump rule: bugfix → patch, new feature → minor, breaking REST/DB change → major.

## CSS Rule

All CSS lives exclusively in `plugin/assets/css/gsh-terminplan.css`.  
Never put CSS in PHP heredocs, `wp_add_inline_style()`, or echo strings.

## Sections of `gsh-terminplan.php` Not to Touch

- `gsh_tp_parse_events` / `gsh_tp_parse_event` (iCal parser)
- `gsh_tp_build_date_index` / `gsh_tp_day_events`
- Table rendering, PDF export, change-notification system
- Structural shape of `gsh_tp_js()` and `gsh_tp_css()`

## Test Framework

Dependency-free (`tests/curriculr/assert.php`). Tests define WP stubs inline and call plugin functions directly. Exit code 1 = failure. Tests that exercise auth/guard require constants (client-id, secret, token key) defined at the top of each file — check fixtures before adding tests.

## wp-config.php Constants Required for SSO

```php
define('CURRICULR_ISERV_BASE_URL',      'https://schule.iserv.de');
define('CURRICULR_ISERV_CLIENT_ID',     '...');
define('CURRICULR_ISERV_CLIENT_SECRET', '...');
define('CURRICULR_APP_TOKEN_KEY',       '...'); // 32-byte hex
define('CURRICULR_SPA_URL',             'https://juwagn.github.io/curriculr-planner/');
define('CURRICULR_ALLOWED_GROUPS',      'Schulleitung');
```

These are never echoed, never committed. Tests mock them with `define()` at the top.

## Language

- Identifiers/comments: English (code) or German (WP admin UI strings)
- WordPress Coding Standards for PHP style
