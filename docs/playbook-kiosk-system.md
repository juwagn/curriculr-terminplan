# Playbook: Kiosk-System — Sicherheit, Änderungen, Dokumentation

> Grundlage: PR #6 (v4.20.0). Gilt für beide Kiosk-Pfade:
> `page-terminplan-kiosk.php` (Live) und `page-terminplan-entwurf.php` (Entwurf).

---

## 1. Architektur-Überblick

```
Browser / IServ-iframe
        │
        │  GET /kiosk-seite/?token=<tok>
        ▼
page-terminplan-kiosk.php
        │
        ├─ gsh_tp_check_kiosk_access($token)
        │       ├─ get_option('gsh_tp_kiosk_token')  ← WP-Options
        │       ├─ hash_equals()                      ← timing-sicher
        │       └─ Transient-Rate-Limit (10/h/IP)    ← WP-Transients
        │
        ├─ nocache_headers()
        ├─ CSP: frame-ancestors 'self' <iserv_domain>
        └─ do_shortcode('[gsh_terminplan]')
```

Jeder Kiosk-Pfad hat **eigenen Token + eigene Rate-Limit-Transients**.
Tokens werden in WP-Options gespeichert, nie in Code oder .env.

---

## 2. Sicherheits-Invarianten — dürfen nie gebrochen werden

| # | Invariante | Wo implementiert |
|---|-----------|-----------------|
| S1 | Token-Vergleich immer `hash_equals()`, kein `===` | `gsh_tp_check_kiosk_access()` |
| S2 | Leerer gespeicherter Token → sofort `false`, kein Fallback | Zeile: `if ( empty( $saved_token ) ) return false;` |
| S3 | Rate-Limit vor Token-Vergleich prüfen | Transient-Check vor `hash_equals()` |
| S4 | Token-Transient nur bei **Fehlversuch** inkrementieren, nie bei Erfolg | `set_transient` nur im `! hash_equals()`-Zweig |
| S5 | `status_header(403)` + `exit` bei ungültigem Token — vor jedem HTML-Output | Zeile 1 im Template nach ABSPATH-Check |
| S6 | `nocache_headers()` immer setzen (auch bei 403) | Beide Template-Pfade |
| S7 | CSP-Header vor `wp_head()` senden — WP darf ihn nicht überschreiben | `header()` vor `?><!DOCTYPE` |
| S8 | `esc_url_raw()` auf IServ-Domain vor Header-Injection | `gsh_tp_iserv_domain`-Wert |

---

## 3. Token-Verwaltung

### Eigenschaften des aktuellen Token-Generators

- Erzeugung: `crypto.getRandomValues(new Uint8Array(24))`, base36, auf 32 Zeichen geschnitten
- Effektive Entropie: ~124 Bit (24 Bytes × log₂(36) × 32/48 Zeichen nach Slice)
- Ausreichend für den Anwendungsfall (kein HSM-Äquivalent nötig)

### Wann Token rotieren

| Anlass | Sofortmaßnahme |
|--------|----------------|
| Token in E-Mail/Chat versehentlich geleakt | Neuen Token generieren → alten URLs sofort ungültig |
| Verdacht auf Brute-Force (Rate-Limit-Logs im WP-Transient-Monitor) | Token rotieren + IServ-Domain prüfen |
| Admin-Wechsel (Passwort-Änderung im WP) | Kiosk-Token unabhängig — kein Handlungsbedarf, aber Rotation empfohlen |
| Jährlich | Als Teil des Schuljahreswechsels (siehe `docs/schuljahreswechsel-anleitung.md`) |

### Rotation durchführen

1. Plugin-Admin → Kiosk & System
2. Button „Neuen Token generieren" → speichern
3. Neue Kiosk-URL kopieren → in IServ-Kachel ersetzen
4. Alte URL funktioniert sofort nicht mehr — kein Übergangszeitraum

### Token NICHT tun

- Token nicht in Code committen (test fixtures etc.)
- Token nicht als WP-Seiten-Slug oder URL-Pfad verwenden
- Token nicht per unverschlüsseltem HTTP übertragen (HTTPS Pflicht auf Produktiv)

---

## 4. CSP-Header — Regeln für Änderungen

### Aktuelles Verhalten

```php
// IServ-Domain gesetzt:
header("Content-Security-Policy: frame-ancestors 'self' " . esc_url_raw($iserv_domain));

// IServ-Domain leer:
header('X-Frame-Options: SAMEORIGIN');
```

### Bekannte Lücke: fehlende Doppelstrategie

`X-Frame-Options: ALLOW-FROM <url>` ist in modernen Browsern nicht mehr unterstützt
(Chrome 74+, Firefox 70+ ignorieren es). Der aktuelle Code sendet entweder CSP **oder**
SAMEORIGIN-Fallback — nie beide gleichzeitig.

**Für zukünftige Änderung empfohlen:**

```php
$iserv_domain = get_option( 'gsh_tp_iserv_domain', '' );
if ( $iserv_domain ) {
    // Beide Header: CSP für moderne Browser, X-Frame-Options als Legacy-Fallback
    header( "Content-Security-Policy: frame-ancestors 'self' " . esc_url_raw( $iserv_domain ) );
    header( 'X-Frame-Options: SAMEORIGIN' ); // kein ALLOW-FROM, das ist deprecated
} else {
    header( 'X-Frame-Options: SAMEORIGIN' );
}
```

### Was nicht in den CSP-Header darf

- Kein `$_GET`, `$_POST`, `$_SERVER` direkt in Header-String → immer `esc_url_raw()`
- Kein `ALLOW-FROM` in `X-Frame-Options` (deprecated, kein Browser-Support mehr)
- Keine Wildcards in `frame-ancestors` (z. B. `https://*.iserv.de`) ohne Sicherheits-Review

---

## 5. Rate-Limiting — Grenzen kennen

### Aktuelle Parameter

| Parameter | Wert | Konstante |
|-----------|------|-----------|
| Max. Fehlversuche | 10 | hartcodiert in Funktionskörper |
| Zeitfenster | 1 Stunde | `HOUR_IN_SECONDS` |
| Key-Basis | `md5(REMOTE_ADDR)` | nicht konfigurierbar |

### Bekannte Einschränkungen

**Shared Hosting / kein Reverse Proxy:** `REMOTE_ADDR` ist die echte Client-IP — Rate-Limit
funktioniert korrekt.

**CDN/Proxy vor WP (z. B. Cloudflare):** `REMOTE_ADDR` ist die Proxy-IP → alle Clients teilen
ein Rate-Limit-Bucket. Das Template explizit nutzt **nicht** `X-Forwarded-For`, da dies auf
shared hosting manipulierbar ist. Akzeptierter Trade-off.

**WP-Transients auf DB-Basis (kein Object Cache):** Transients landen in `wp_options`.
Bei sehr hoher Last können Transient-Schreiboperationen einen Lock-Contention-Effekt erzeugen.
Für Schulumgebungen (niedrige Last) kein Problem.

### Monitoring ohne Plugin

```sql
-- Rate-Limit-Einträge im DB-Transient prüfen (in phpMyAdmin ausführen)
SELECT option_name, option_value
FROM wp_options
WHERE option_name LIKE '_transient_gsh_tp_kiosk_rl_%'
   OR option_name LIKE '_transient_gsh_tp_draft_rl_%'
ORDER BY option_name;
```

Einträge mit `option_value >= 10` bedeuten: diese IP ist gesperrt.
Einträge verschwinden automatisch nach 1 Stunde (Transient-Ablauf).

---

## 6. Template-Registrierung — Checkliste für neue Templates

Falls ein drittes Kiosk-Template (z. B. für ein drittes Publikum) hinzukommt:

```
[ ] 1. Template-Datei anlegen: plugin/page-terminplan-<name>.php
        – ABSPATH-Check als erste Zeile
        – Token-Check vor jedem Output
        – nocache_headers() nach validem Token
        – eigene gsh_tp_check_<name>_access()-Funktion oder bestehende nutzen
        – CSP-Header setzen
[ ] 2. In gsh-terminplan.php:
        – gsh_tp_<name>_template_include() analog zu gsh_tp_kiosk_template_include()
        – gsh_tp_register_<name>_template() analog zu gsh_tp_register_kiosk_template()
        – Beide add_filter()-Aufrufe im "Hooks"-Block (Zeile ~2144)
        – Neuen Option-Key für Token in gsh_tp_opt() / register_setting() aufnehmen
        – Rate-Limit-Transient-Key mit eigenem Prefix (kein Sharing mit anderen Kiosks)
[ ] 3. Admin-UI: Token-Feld + URL-Anzeige im Kiosk & System Tab
[ ] 4. Changelog-Eintrag in gsh_tp_changelog() voranstellen
[ ] 5. Version bump (minor für neues Feature)
[ ] 6. ZIP neu bauen (6+1 PHP-Dateien)
[ ] 7. docs/anleitung-kiosk-einrichtung.md aktualisieren
[ ] 8. README.md aktualisieren
[ ] 9. php -l auf alle geänderten PHP-Dateien
[  ] 10. Smoketest: Token fehlt → 403; Token falsch → 403; Token korrekt → 200
```

---

## 7. Sicherheits-relevante Änderungen — Prozess

Änderungen an `gsh_tp_check_kiosk_access()`, `gsh_tp_check_draft_kiosk_access()` oder
den Header-Setzungen in den Templates erfordern:

1. **Test schreiben** in `tests/curriculr/test-auth.php` (oder neue Datei)
   — mindestens: leerer Token, falscher Token, richtiger Token, Rate-Limit-Überschreitung
2. **Alle Tests laufen lassen:**
   ```bash
   php tests/curriculr/test-auth.php
   php tests/curriculr/test-guard.php
   ```
3. **Header manuell prüfen** (nach Deploy):
   ```bash
   curl -si "https://schule.de/terminplan-live/?token=DEIN_TOKEN" | grep -i "content-security\|x-frame\|cache"
   ```
4. **Rate-Limit testen:** 11× falschen Token senden → 12. Versuch muss ebenfalls 403 zurückgeben

### Nie ohne Review ändern

- `hash_equals()` durch `===` ersetzen
- Rate-Limit-Schwelle erhöhen (>50) oder ganz entfernen
- `REMOTE_ADDR` durch `X-Forwarded-For` ersetzen ohne IP-Validierung
- Token in Session/Cookie statt URL-Parameter speichern (bricht IServ-iframe-Flow)

---

## 8. Dokumentations-Regel

Jede Kiosk-Änderung, die das Setup für Admins ändert, muss **synchron** aktualisiert werden:

| Datei | Was aktuell halten |
|-------|--------------------|
| `docs/anleitung-kiosk-einrichtung.md` | Schritt-für-Schritt-Setup für WP-Admins ohne Entwicklerhintergrund |
| `README.md` → Teil 2 + Teil 3 | Kurzfassung der Einrichtung + Sicherheitshinweis |
| `plugin/gsh-terminplan.php` | Changelog-Eintrag in `gsh_tp_changelog()` + Header-Kommentar |
| Dieses Playbook | Neue Invarianten, neue bekannte Grenzen |

**Faustregel:** Wenn Patrick nach dem Update eine neue manuelle Aktion braucht → Anleitung updaten.
Wenn sich ein Sicherheitsmechanismus ändert → dieses Playbook updaten.
