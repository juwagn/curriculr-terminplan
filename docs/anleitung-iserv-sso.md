# IServ-SSO einrichten (Administrator)

Diese Anleitung verbindet den Curriculr-Planner über IServ-Single-Sign-On mit
dem WordPress-Plugin. Voraussetzung: Admin-Zugang zu IServ und zur
`wp-config.php` des WordPress-Servers.

## 1. IServ-Client anlegen

1. In IServ: **Verwaltung → System → Single-Sign-On → Hinzufügen**.
2. **Name:** z. B. `Curriculr-Planner`.
3. **Gruppen-/Rollenrechte:** nur die berechtigte(n) Gruppe(n) freigeben, z. B.
   `Schulleitung` (Gruppenfilter #1 am IServ).
4. **Scopes:** `openid`, `profile`, `iserv:groups`.
5. **Grant-Type:** Authorization Code.
6. **Redirect-URI:** exakt den Wert eintragen, den das Plugin im System-Tab unter
   „IServ-SSO" anzeigt (Form: `https://<wp-host>/wp-json/curriculr/v1/auth/callback`).
7. **Client-ID** und **Client-Secret** notieren.

## 2. wp-config.php befüllen

```php
define( 'CURRICULR_ISERV_BASE_URL',     'https://<schule>.iserv.de' ); // ohne /iserv
define( 'CURRICULR_ISERV_CLIENT_ID',    '<client-id>' );
define( 'CURRICULR_ISERV_CLIENT_SECRET','<client-secret>' );
define( 'CURRICULR_APP_TOKEN_KEY',      '<32+ zufällige Zeichen>' );
define( 'CURRICULR_SPA_URL',            'https://juwagn.github.io/curriculr-planner/' );
define( 'CURRICULR_ALLOWED_GROUPS',     'Schulleitung' ); // Komma-Liste
// optional: define( 'CURRICULR_APP_TOKEN_TTL', 1800 );
```

> `CURRICULR_APP_TOKEN_KEY` per `wp_generate_password()` o. ä. erzeugen; geheim halten.

## 3. Prüfen

1. WP-Admin → Plugin-Einstellungen → **System-Tab → IServ-SSO**: alle vier
   Konstanten müssen „gesetzt" zeigen, die Redirect-URI muss mit der in IServ
   registrierten übereinstimmen.
2. Test-Login mit einem Konto der freigegebenen Gruppe (ab M3 in der SPA verfügbar).
3. Konto **außerhalb** der Gruppe testen → muss abgewiesen werden (Gruppenfilter #2).

## Sicherheitshinweise

- Client-Secret und App-Token-Schlüssel nur in `wp-config.php`, nie in der DB,
  nie im Repository.
- Redirect-URI exakt registrieren (keine Wildcards).
- App-Token ist kurzlebig (Default 30 Min) und lebt im Browser nur im RAM.

Referenzen:
- IServ SSO (Verwaltung): https://doku.iserv.de/manage/system/sso/
- IServ OAuth/OpenID (Entwickler): https://doku.iserv.de/development/oauth/
