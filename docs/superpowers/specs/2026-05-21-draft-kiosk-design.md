# Design: Entwurf-Kiosk (Schulleitungs-Vorschau)

**Datum:** 2026-05-21  
**Status:** Genehmigt  
**Feature:** Token-gesicherte Vorschau-Seite für Entwurfs-Terminpläne

---

## Hintergrund

Die Schulleitung möchte Entwurfs-Terminpläne (`is_draft=true`) vor der Freigabe dem Schulleitungsteam zur Einsicht bereitstellen — ohne dass Teamitglieder einen WordPress-Admin-Account benötigen. Die Seite ist read-only (kein Kommentarfeld).

---

## Entscheidungen

| Frage | Entscheidung |
|---|---|
| Kommentare? | Nein — reine Vorschau |
| Zugangskontrolle | Eigener Draft-Kiosk-Token (separater Token vom Live-Kiosk) |
| Seitenstruktur | Eigene WordPress-Seite mit neuem Page-Template |

---

## Architektur

```
[Admin: Kiosk & System Tab]
  → gsh_tp_draft_kiosk_token (neue WP-Option)
  → URL-Anzeige + Warnungen
  → „Neu generieren"-Button + „Testen"-Link

[WordPress-Seite z.B. /entwurf/]
  → Page-Template: page-terminplan-entwurf.php
  → Token-Validierung: gsh_tp_check_draft_kiosk_access($token)
  → Setzt Draft-Kiosk-Kontext via statische Variable
  → Ausgabe: do_shortcode('[gsh_terminplan schuljahr="entwurf"]')
  → Banner: „ENTWURF – noch nicht beschlossen"

[Sicherheit]
  → hash_equals() Timing-sicherer Token-Vergleich
  → Rate-Limiting: 10 Fehlversuche/Stunde/IP
  → Transient-Key: gsh_tp_draft_rl_{md5(ip)}
  → Shortcode-Guard: nur mit gesetztem Draft-Kiosk-Kontext
```

---

## Datenmodell

**Neue WP-Option:**
```
gsh_tp_draft_kiosk_token  →  string, 32 Zeichen, zufällig generiert
```
- `register_setting()` mit `sanitize_text_field` Callback
- Analog zu `gsh_tp_kiosk_token` (Zeile 1798 in gsh-terminplan.php)
- In Deinstallations-Cleanup (`delete_option`) ergänzen

**Rate-Limiting-Transient (temporär, kein Cleanup):**
```
gsh_tp_draft_rl_{md5(ip)}  →  int, läuft nach 1h ab
```

Kein neues Datenmodell für Entwurf-Profile — `is_draft` in `gsh_tp_profiles` bleibt unverändert.

---

## Admin-UI (Kiosk & System Tab)

Neue Sektion **vor** dem bestehenden IServ-Kiosk-Block:

```
┌─ Entwurf-Vorschau (Schulleitungsteam) ──────────────────┐
│                                                           │
│  Entwurf-Token  [ xk92...abc          ] [Neu generieren] │
│                                                           │
│  ⚠ Kein Entwurf-Profil vorhanden.          (wenn leer)  │
│  ⚠ Keine Seite mit Template gefunden.      (wenn fehlt)  │
│                                                           │
│  Vorschau-URL   https://schule.de/entwurf/?token=XYZ     │
│                 [Vorschau testen ↗]                       │
└───────────────────────────────────────────────────────────┘
```

Warnungen anzeigen wenn:
- Token leer → Hinweis Token generieren
- Kein Profil mit `is_draft=true` vorhanden → Hinweis
- Keine WP-Seite mit Template `page-terminplan-entwurf.php` → Hinweis

„Neu generieren"-Button: gleiches JS wie Kiosk-Token (Zeile 3281) — `crypto.getRandomValues`, Confirm-Dialog mit Hinweis dass alte Links ungültig werden.

---

## Page-Template: `page-terminplan-entwurf.php`

```php
<?php
/**
 * Template Name: Terminplan Entwurf-Vorschau
 */

$token = sanitize_text_field( $_GET['token'] ?? '' );
if ( ! gsh_tp_check_draft_kiosk_access( $token ) ) {
    status_header( 403 );
    exit;
}

get_header();
?>
<div class="gsh-tp-draft-preview-wrap">
    <div class="gsh-tp-draft-banner">
        <?php echo gsh_tp_icon('warning'); ?>
        <strong>Entwurf</strong> – dieser Terminplan ist noch nicht beschlossen.
        Bitte Änderungswünsche direkt an die Schulleitung melden.
    </div>
    <?php echo do_shortcode('[gsh_terminplan schuljahr="entwurf"]'); ?>
</div>
<?php get_footer(); ?>
```

Theme bleibt aktiv (kein stripped-down Kiosk-Layout) — Schulleitung sieht normale Seite mit Banner.

---

## Neue Funktionen in `gsh-terminplan.php`

### `gsh_tp_check_draft_kiosk_access( string $token ): bool`

```php
function gsh_tp_check_draft_kiosk_access( string $token ): bool {
    $saved = get_option( 'gsh_tp_draft_kiosk_token', '' );
    if ( empty( $saved ) ) return false;

    $ip       = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ) );
    $rate_key = 'gsh_tp_draft_rl_' . md5( $ip );
    $attempts = (int) get_transient( $rate_key );
    if ( $attempts >= 10 ) return false;

    if ( ! hash_equals( $saved, $token ) ) {
        set_transient( $rate_key, $attempts + 1, HOUR_IN_SECONDS );
        return false;
    }

    gsh_tp_draft_kiosk_context( true );
    return true;
}
```

### `gsh_tp_draft_kiosk_context( bool $set = false ): bool`

Einzelne Funktion mit gemeinsamer statischer Variable — verhindert Zustandsteilung-Problem zwischen zwei separaten Funktionen:

```php
function gsh_tp_draft_kiosk_context( bool $set = false ): bool {
    static $active = false;
    if ( $set ) {
        $active = true;
    }
    return $active;
}
```

Aufruf zum Setzen: `gsh_tp_draft_kiosk_context( true );`  
Aufruf zum Prüfen: `gsh_tp_draft_kiosk_context()`

### Änderung in `gsh_tp_shortcode()`

An der Stelle wo `schuljahr="entwurf"` + `! current_user_can('manage_options')` geprüft wird (Zeile 4058–4076):

```php
if ( 'entwurf' === $atts['schuljahr'] ) {
    if ( ! current_user_can( 'manage_options' ) && ! gsh_tp_is_draft_kiosk_context() ) {
        return '...gesperrt...';
    }
    // Entwurf-Profil bestimmen ...
}
```

Und Zeile 4091 (Fallback-Guard):

```php
if ( ! empty( $profile['is_draft'] ) 
     && ! current_user_can( 'manage_options' )
     && ! gsh_tp_draft_kiosk_context() ) {
    return '...gesperrt...';
}
```

---

## Sicherheitsübersicht

| Angriff | Schutz |
|---|---|
| Token erraten (Brute-Force) | Rate-Limit: 10 Versuche/h/IP |
| Timing-Angriff | `hash_equals()` |
| Token fehlt/leer | `empty()` → sofort false |
| Shortcode direkt aufrufen | `gsh_tp_is_draft_kiosk_context()` Guard |
| Entwurf ohne Token sehen | Bestehender Admin-Guard bleibt für alle anderen Requests |
| Live-Kiosk-Token funktioniert nicht für Draft | Separate Optionen, separate Funktion |

---

## Dateien

| Datei | Änderung |
|---|---|
| `plugin/gsh-terminplan.php` | +`gsh_tp_draft_kiosk_token` Option, +`gsh_tp_check_draft_kiosk_access()`, +Kontext-Hilfsfunktionen, Admin-UI-Sektion, Shortcode-Guard-Erweiterung, Deinstallations-Cleanup |
| `plugin/page-terminplan-entwurf.php` | Neu — Page-Template |
| `plugin/assets/css/gsh-terminplan.css` | +`.gsh-tp-draft-preview-wrap`, Banner-Styles falls nötig |

---

## Versionierung

Feature-Addition → Minor-Bump (z.B. v4.x → v4.(x+1).0). Alle vier Versionsstellen synchron aktualisieren.
