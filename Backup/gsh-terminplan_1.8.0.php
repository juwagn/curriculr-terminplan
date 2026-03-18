<?php
/**
 * Plugin Name: GSH Terminplan Dashboard
 * Plugin URI:  https://gesamtschule-horst.de
 * Description: Interaktive Quartalsuebersicht des Schuljahresterminplans aus dem IServ-Kalender (iCal-Feed).
 * Version:     1.8.0
 * Author:      Gesamtschule Horst
 * License:     GPL v2 or later
 * Text Domain: gsh-terminplan
 *
 * Changelog 1.8:
 * - Sicherheitsaudit: URL-Validierung nutzt jetzt wp_http_validate_url() statt
 *   eines einfachen Präfix-Checks (SECURITY FIX).
 * - Vollständige PHPDoc-Blöcke für alle Funktionen: Typ-Annotationen, Parameter,
 *   Rückgabewerte und Klartext-Beschreibungen auf Deutsch.
 * - Kurzanleitung für Kolleg*innen direkt im Datei-Kopf (Datenfluss, Shortcodes,
 *   jährliche Pflege, Dateistruktur mit Zeilenangaben).
 * - CSS-Klassenübersicht als Kommentar am Anfang von gsh_tp_css().
 * - Inline-Kommentare an nicht-offensichtlichen Code-Stellen erklären das „Warum".
 * - Versionsnummer im CSS-Kommentar auf 1.8 aktualisiert.
 *
 * Changelog 1.7:
 * - Vergangene Wochen ausgrauen: Wochenzeilen, deren Freitag vor dem heutigen
 *   Datum liegt, erhalten die Klasse gtp-past (opacity 0.45, Termine grau).
 *   Hover hebt die Zeile wieder auf. Im Druck werden alle Zeilen voll opak.
 * - Sticky Tabellenkopf: Die Kopfzeile der Quartalstabelle klebt beim Scrollen
 *   oben (position:sticky), mit korrektem Offset für die WordPress Admin-Bar.
 *   Im Druck wird sticky deaktiviert.
 *
 * Changelog 1.6:
 * - Echtzeit-Textsuche: Suchfeld im Header durchsucht alle Termine in allen Quartalen.
 * - Debounce (200 ms) für flüssige Performance auch bei ~400 Events.
 * - Suchergebnis-Zeile zeigt Gesamt-Trefferzahl und klickbare Quartal-Links
 *   (z. B. „Q2: 3"), die den Tab wechseln und zum ersten Treffer scrollen.
 * - Treffer werden mit gelbem Outline hervorgehoben (.ge-hit).
 * - Kategorie-Filter und Textsuche arbeiten kombiniert: Ein Termin erscheint
 *   nur, wenn er sowohl den Suchbegriff als auch den Kategorie-Filter erfüllt.
 * - Tab-Wechsel setzt die laufende Suche nicht zurück.
 *
 * Changelog 1.5:
 * - Auto-Scroll: Beim Laden der Seite wird sanft zur aktuellen Woche gescrollt
 *   (heutiger Tag im oberen Drittel des Viewports).
 * - Floating „Heute"-Button: Schwebt unten rechts, erscheint sobald die heutige
 *   Zeile aus dem Viewport gescrollt ist oder sich im falschen Quartal-Tab befindet.
 *   Klick scrollt sanft zurück und wechselt ggf. automatisch den Tab.
 * - Tab-Wechsel: Wechsel in das Quartal mit dem heutigen Datum scrollt automatisch
 *   zur Heute-Zeile.
 * - Interner Refactor: gtpSwitchTab() (reiner Tab-Wechsel) und gtpTab() (mit Scroll)
 *   sind jetzt getrennt, um Rekursion und Seiteneffekte zu vermeiden.
 *
 * Changelog 1.4:
 * - Filter: Neues Verhalten – erster Klick wechselt in Exklusiv-Modus (nur gewählte
 *   Kategorie sichtbar), weitere Klicks fügen Kategorien hinzu. Reset-Button blendet
 *   alle Termine wieder ein.
 * - Lange Termine (>= 5 Tage / ganze Woche oder länger) erscheinen NUR in der
 *   Hinweise-Spalte, nicht mehr in den einzelnen Tagesspalten. Datumsbereich wird
 *   in der Hinweise-Spalte mit angezeigt.
 * - Ferien-/Urlaubstermine markieren weiterhin Tagesspalten als Ferientag (grau),
 *   auch wenn ihr Text jetzt in der Hinweise-Spalte steht.
 *
 * Changelog 1.3:
 * - Professionelles Frontend-Design: Karten-Layout, moderne Tabs, farbige Kategorie-Filter.
 * - Manuelle Synchronisierung: Button in den Einstellungen leert Cache und ruft sofort neu ab.
 * - Letzter Sync-Zeitstempel wird gespeichert und im Header + Admin angezeigt.
 *
 * Changelog 1.2:
 * - Druckfunktion: Unsichtbarer iframe statt Popup – kein Popup-Blocker-Problem.
 * - Kategorie-Erkennung: Echtes Wortgrenzen-Matching (\b) statt strpos.
 * - Kategorie wird NUR aus dem CATEGORIES-Feld gelesen, NICHT aus dem Termintext.
 */

/**
 * ═══════════════════════════════════════════════════════════
 * KURZANLEITUNG FÜR KOLLEGINNEN UND KOLLEGEN
 * ═══════════════════════════════════════════════════════════
 *
 * Was macht dieses Plugin?
 *   Dieses Plugin holt automatisch den IServ-Schulkalender und zeigt ihn
 *   als übersichtlichen Jahresterminplan auf der WordPress-Seite an.
 *   Termine werden nach Kategorien farbig dargestellt und lassen sich
 *   filtern, durchsuchen und drucken.
 *
 * Wie funktioniert es? (Datenfluss)
 *   IServ → iCal-Feed (URL) → Plugin lädt Feed herunter → Parst Events
 *   → Speichert im Cache → Zeigt farbige Quartalstabellen an.
 *   Der Cache hält die Daten (Standard: 1 Stunde), um den IServ-Server
 *   zu entlasten. Bei Bedarf kann man in den Einstellungen manuell
 *   synchronisieren.
 *
 * Wo werden Einstellungen gemacht?
 *   WordPress Admin → Einstellungen → GSH Terminplan
 *   Dort: iCal-URL eintragen, Cache-Dauer anpassen, Quartalsgrenzen
 *   setzen, Kategorie-Stichwörter pflegen, manuell synchronisieren.
 *
 * Wie benutzt man den Shortcode?
 *   [gsh_terminplan]                → Zeigt automatisch das aktuelle Quartal
 *   [gsh_terminplan quartal="2"]    → Zeigt nur Quartal 2 (1–4)
 *   [gsh_terminplan quartal="alle"] → Alle Quartale mit Tab-Navigation
 *
 * Was muss man jährlich anpassen? (Admin → Einstellungen → GSH Terminplan)
 *   - Quartalsgrenzen: Start- und Enddaten der vier Quartale
 *   - Start Schulwoche 01: Erster Montag nach den Sommerferien
 *   - iCal-URL: Falls sich die IServ-Adresse geändert hat
 *
 * Wie funktioniert die Farbzuordnung?
 *   IServ speichert zu jedem Termin ein CATEGORIES-Feld. Das Plugin gleicht
 *   dieses Feld mit der Stichwort-Liste ab (Admin → Einstellungen → Mapping).
 *   Beispiel: Enthält CATEGORIES das Wort „Konferenz" → Termin erscheint blau.
 *   Termine ohne passende Kategorie werden grau (Sonstige) dargestellt.
 *
 * Aufbau dieser Datei:
 *   1. Direktzugriff-Schutz & Konstanten          (ca. Zeile  80)
 *   2. Admin-Einstellungsseite                     (ca. Zeile  90)
 *   3. iCal abrufen & parsen                       (ca. Zeile 490)
 *   4. Schulwochen & Quartalsgrenzen               (ca. Zeile 640)
 *   5. Kategorie-Erkennung (Farb-Mapping)          (ca. Zeile 690)
 *   6. Shortcode / HTML-Hauptausgabe               (ca. Zeile 760)
 *   7. Tabellen-Rendering (eine Quartalstabelle)   (ca. Zeile 900)
 *   8. CSS (inline, alle Klassen)                  (ca. Zeile 1070)
 *   9. JavaScript (Tabs, Filter, Suche, Druck)     (ca. Zeile 1330)
 *  10. Deaktivierung & Deinstallation              (ca. Zeile 1770)
 * ═══════════════════════════════════════════════════════════
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Direktzugriff auf die PHP-Datei blockieren (WordPress-Standard)
}

define( 'GSH_TP_VERSION', '1.8.0' );
define( 'GSH_TP_SLUG', 'gsh-terminplan' );
define( 'GSH_TP_CACHE_KEY', 'gsh_tp_ical_data' );
define( 'GSH_TP_BACKUP_KEY', 'gsh_tp_ical_backup' );

/* ================================================================
   1. ADMIN-EINSTELLUNGEN
   ================================================================ */

/**
 * Registriert den Einstellungsmenüeintrag im WordPress-Backend.
 *
 * Der Menüpunkt erscheint unter „Einstellungen → GSH Terminplan" und ist nur
 * für Benutzer mit der Berechtigung „manage_options" (Admins) sichtbar.
 *
 * @since 1.2.0
 * @return void
 */
add_action( 'admin_menu', 'gsh_tp_admin_menu' );
function gsh_tp_admin_menu() {
    add_options_page(
        'GSH Terminplan',
        'GSH Terminplan',
        'manage_options',
        GSH_TP_SLUG,
        'gsh_tp_settings_page'
    );
}

/**
 * Meldet alle Plugin-Optionen bei der WordPress Settings API an.
 *
 * Jede Option bekommt einen Sanitize-Callback, der die Eingabe bereinigt,
 * bevor sie in der Datenbank gespeichert wird. Das verhindert unerwünschte
 * Zeichen und schützt vor fehlerhaften Eingaben.
 *
 * @since 1.2.0
 * @return void
 */
add_action( 'admin_init', 'gsh_tp_register_settings' );
function gsh_tp_register_settings() {
    register_setting( 'gsh_tp_options', 'gsh_tp_ical_url', array(
        'sanitize_callback' => 'gsh_tp_sanitize_url',
        'default'           => '',
    ) );
    register_setting( 'gsh_tp_options', 'gsh_tp_cache_duration', array(
        'sanitize_callback' => 'absint',
        'default'           => 3600,
    ) );
    register_setting( 'gsh_tp_options', 'gsh_tp_schuljahr_start', array(
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '2025-08-25',
    ) );
    register_setting( 'gsh_tp_options', 'gsh_tp_quartal_grenzen', array(
        'sanitize_callback' => 'sanitize_textarea_field',
        'default'           => "2025-08-25|2025-10-31\n2025-11-03|2026-02-06\n2026-02-09|2026-05-01\n2026-05-04|2026-07-17",
    ) );
    register_setting( 'gsh_tp_options', 'gsh_tp_kategorie_mapping', array(
        'sanitize_callback' => 'sanitize_textarea_field',
        'default'           => gsh_tp_default_mapping(),
    ) );
}

/**
 * Liefert das voreingestellte Kategorie-Mapping als mehrzeiligen String.
 *
 * Jede Zeile hat das Format „Stichwort|farbklasse". Zeilen die mit # beginnen
 * sind Kommentare und werden beim Parsen übersprungen. Dieses Mapping wird als
 * Startwert für neue Installationen und als Fallback verwendet, wenn die
 * Einstellung in der Datenbank noch nicht existiert.
 *
 * @since 1.2.0
 * @return string Mehrzeiliger String mit allen Standard-Mappings.
 */
function gsh_tp_default_mapping() {
    return implode( "\n", array(
        '# ──── Konferenzen (blau) ────',
        'Konferenz|konferenz',
        'LK|konferenz',
        'FaKo|konferenz',
        'Fachkonferenz|konferenz',
        'TBS|konferenz',
        'Dienstbesprechung|konferenz',
        'DB|konferenz',
        'PK|konferenz',
        'ZK|konferenz',
        'SK|konferenz',
        'SchiLF|konferenz',
        'Pflegschaft|konferenz',
        'Schulkonferenz|konferenz',
        'Teamsitzung|konferenz',
        'Teamtag|konferenz',
        'Teamnachmittag|konferenz',
        'Studientag|konferenz',
        '',
        '# ──── Prüfungen (rot) ────',
        'Prüfung|pruefung',
        'Pruefung|pruefung',
        'ZP|pruefung',
        'Abi|pruefung',
        'Klausur|pruefung',
        'Vera|pruefung',
        'DST|pruefung',
        'ZKL|pruefung',
        'mAP|pruefung',
        'ZAA|pruefung',
        'Sprachstandstest|pruefung',
        'Nachprüfung|pruefung',
        '',
        '# ──── Projekte (grün) ────',
        'Projekt|projekt',
        'ProWo|projekt',
        'Wandertag|projekt',
        'Sportspektakel|projekt',
        'Praktikum|projekt',
        'SV-Fahrt|projekt',
        'Studienfahrt|projekt',
        'Abschlussfahrt|projekt',
        'Fahrtenwoche|projekt',
        'Exkursion|projekt',
        'Turnier|projekt',
        'Olympiade|projekt',
        'Sponsorenlauf|projekt',
        'Friedenstag|projekt',
        'Antirassismustag|projekt',
        'Abifeier|projekt',
        'Entlassfeier|projekt',
        'Horst forscht|projekt',
        '',
        '# ──── Ferien / Frei (grau) ────',
        'Frei|frei',
        'Ferien|frei',
        'Feiertag|frei',
        'Ferientag|frei',
        'Rosenmontag|frei',
        'Weihnacht|frei',
        'Pfingsten|frei',
        'Himmelfahrt|frei',
        'Fronleichnam|frei',
        'Maifeiertag|frei',
        'Tag der Deutschen Einheit|frei',
        '',
        '# ──── Eltern / Beratung (orange) ────',
        'Eltern|eltern',
        'Beratung|eltern',
        'Info-Abend|eltern',
        'Infoabend|eltern',
        'Potenzialanalyse|eltern',
        'KAoA|eltern',
        'Kurswahl|eltern',
        'Anmeldung|eltern',
        'Tag der offenen Tür|eltern',
        'Schnuppertag|eltern',
        'Berufemarkt|eltern',
        'Lernberatungstag|eltern',
        '',
        '# ──── Fristen / Hinweise (gelb) ────',
        'Frist|frist',
        'Noten|frist',
        'Fachkommentar|frist',
        'Berichtszeugnis|frist',
        'Zeugnis|frist',
        'Evaluation|frist',
        'Foerderplaene|frist',
        'Förderpläne|frist',
        'Meldung mAP|frist',
    ) );
}

/**
 * Bereinigt und validiert die iCal-Feed-URL vor dem Speichern.
 *
 * Diese Funktion wird als Sanitize-Callback von register_setting() aufgerufen.
 * Sie stellt sicher, dass nur vollständig gültige HTTPS-URLs gespeichert werden.
 * Bei einer ungültigen Eingabe wird eine Fehlermeldung angezeigt und die
 * bisherige URL bleibt unverändert in der Datenbank.
 *
 * @since 1.2.0
 * @param  string $url Die eingegebene URL aus dem Admin-Formular.
 * @return string      Die bereinigte URL oder die bisherige Option bei Fehler.
 */
function gsh_tp_sanitize_url( $url ) {
    // esc_url_raw() bereinigt die URL und entfernt gefährliche Zeichen
    $url = esc_url_raw( trim( $url ) );
    if ( empty( $url ) ) {
        return '';
    }
    // SECURITY FIX: wp_http_validate_url() prüft ob es eine vollständig gültige
    // HTTP(S)-URL ist – nicht nur ob der String mit "https://" beginnt.
    // Zusätzlich prüfen wir explizit auf HTTPS (kein HTTP erlaubt).
    if ( ! wp_http_validate_url( $url ) || strpos( $url, 'https://' ) !== 0 ) {
        add_settings_error( 'gsh_tp_ical_url', 'scheme',
            'Die iCal-URL muss eine gültige HTTPS-URL sein.', 'error' );
        return get_option( 'gsh_tp_ical_url', '' );
    }
    return $url;
}

/* ── Admin-Seite ── */

/**
 * Rendert die komplette Einstellungsseite im WordPress-Backend.
 *
 * Verarbeitet die POST-Aktionen „Cache leeren" und „Kalender synchronisieren",
 * jeweils geschützt durch eigene Nonces. Danach wird das Einstellungsformular
 * mit allen Plugin-Optionen ausgegeben.
 *
 * @since 1.2.0
 * @return void
 */
function gsh_tp_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Zugriff verweigert.' );
    }

    // Cache leeren
    if ( isset( $_POST['gsh_tp_cc'] ) &&
         wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_cn'] ?? '' ) ), 'gsh_tp_cc' ) ) {
        delete_transient( GSH_TP_CACHE_KEY );
        echo '<div class="notice notice-success"><p>Cache geleert.</p></div>';
    }

    // Kalender manuell synchronisieren
    if ( isset( $_POST['gsh_tp_sync'] ) &&
         wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_sn'] ?? '' ) ), 'gsh_tp_sync' ) ) {
        delete_transient( GSH_TP_CACHE_KEY );
        $fresh = gsh_tp_fetch_ical();
        if ( $fresh ) {
            echo '<div class="notice notice-success"><p>'
               . '&#10003; Kalender erfolgreich synchronisiert ('
               . esc_html( wp_date( 'd.m.Y, H:i' ) ) . ' Uhr).'
               . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>'
               . '&#10007; Synchronisierung fehlgeschlagen &ndash; '
               . 'bitte die iCal-URL prüfen oder warten, bis der IServ-Server erreichbar ist.'
               . '</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>GSH Terminplan &ndash; Einstellungen</h1>
        <?php settings_errors(); ?>

        <form method="post" action="options.php">
            <?php settings_fields( 'gsh_tp_options' ); ?>
            <table class="form-table">

                <tr>
                    <th><label for="gsh_tp_ical_url">iCal-Feed-URL</label></th>
                    <td>
                        <input type="url" id="gsh_tp_ical_url" name="gsh_tp_ical_url"
                               value="<?php echo esc_attr( get_option( 'gsh_tp_ical_url', '' ) ); ?>"
                               class="regular-text" placeholder="https://iserv.example.de/ical/..." />
                        <p class="description">HTTPS-URL des IServ-Kalender-Exports (.ics).</p>
                    </td>
                </tr>

                <tr>
                    <th><label for="gsh_tp_cache_duration">Cache-Dauer (Sek.)</label></th>
                    <td>
                        <input type="number" id="gsh_tp_cache_duration" name="gsh_tp_cache_duration"
                               value="<?php echo esc_attr( get_option( 'gsh_tp_cache_duration', 3600 ) ); ?>"
                               min="300" max="86400" />
                        <p class="description">Standard: 3600 (1 Stunde). Min. 300, Max. 86400.</p>
                    </td>
                </tr>

                <tr>
                    <th><label for="gsh_tp_schuljahr_start">Start Schulwoche 01</label></th>
                    <td>
                        <input type="date" id="gsh_tp_schuljahr_start" name="gsh_tp_schuljahr_start"
                               value="<?php echo esc_attr( get_option( 'gsh_tp_schuljahr_start', '2025-08-25' ) ); ?>" />
                        <p class="description">Erster Montag nach den Sommerferien.</p>
                    </td>
                </tr>

                <tr>
                    <th><label for="gsh_tp_quartal_grenzen">Quartalsgrenzen</label></th>
                    <td>
                        <textarea id="gsh_tp_quartal_grenzen" name="gsh_tp_quartal_grenzen"
                                  rows="5" class="large-text"><?php
                            echo esc_textarea( get_option( 'gsh_tp_quartal_grenzen',
                                "2025-08-25|2025-10-31\n2025-11-03|2026-02-06\n2026-02-09|2026-05-01\n2026-05-04|2026-07-17"
                            ) );
                        ?></textarea>
                        <p class="description">Pro Zeile: Startdatum|Enddatum (JJJJ-MM-TT).</p>
                    </td>
                </tr>

                <tr>
                    <th><label for="gsh_tp_kategorie_mapping">Kategorie-Stichwörter &rarr; Farben</label></th>
                    <td>
                        <textarea id="gsh_tp_kategorie_mapping" name="gsh_tp_kategorie_mapping"
                                  rows="14" class="large-text code" style="font-size:12px"><?php
                            echo esc_textarea( get_option( 'gsh_tp_kategorie_mapping',
                                gsh_tp_default_mapping() ) );
                        ?></textarea>
                        <p class="description">
                            Pro Zeile: <code>Stichwort|farbklasse</code> &ndash;
                            Matching mit <strong>Wortgrenzen</strong>:
                            &bdquo;LK&ldquo; findet &bdquo;LK 3&ldquo; aber NICHT &bdquo;Volk&ldquo;.
                            <br>
                            Geprüft wird <strong>nur das CATEGORIES-Feld</strong> aus IServ,
                            nicht der Termintext. Termine ohne Kategorie werden grau dargestellt.
                        </p>
                        <details style="margin-top:8px">
                            <summary style="cursor:pointer;font-weight:600;color:#1a5276">
                                Verfügbare Farbklassen anzeigen
                            </summary>
                            <table class="widefat" style="max-width:460px;margin-top:8px">
                                <tr><td><b>konferenz</b></td>
                                    <td style="background:#d6eaf8;color:#1a5276;padding:4px 8px">Blau &ndash; Konferenzen, Sitzungen</td></tr>
                                <tr><td><b>pruefung</b></td>
                                    <td style="background:#fadbd8;color:#922b21;padding:4px 8px">Rot &ndash; Prüfungen, Klausuren</td></tr>
                                <tr><td><b>projekt</b></td>
                                    <td style="background:#d5f5e3;color:#1e8449;padding:4px 8px">Grün &ndash; Projekte, Fahrten, Sport</td></tr>
                                <tr><td><b>frei</b></td>
                                    <td style="background:#eaecee;color:#616a6b;padding:4px 8px">Grau &ndash; Ferien, Feiertage</td></tr>
                                <tr><td><b>eltern</b></td>
                                    <td style="background:#fdebd0;color:#b9770e;padding:4px 8px">Orange &ndash; Elternarbeit, Beratung</td></tr>
                                <tr><td><b>frist</b></td>
                                    <td style="background:#fcf3cf;color:#7d6608;padding:4px 8px">Gelb &ndash; Fristen, Hinweise</td></tr>
                            </table>
                        </details>
                    </td>
                </tr>

            </table>
            <?php submit_button( 'Einstellungen speichern' ); ?>
        </form>

        <hr />
        <h2>Kalender-Synchronisation</h2>
        <table class="form-table" style="max-width:700px">
            <tr>
                <th style="width:200px">Letzte Synchronisierung</th>
                <td>
                    <?php
                    $last_sync = get_option( 'gsh_tp_last_sync', '' );
                    if ( $last_sync ) {
                        $dt = new DateTime( $last_sync, new DateTimeZone( 'UTC' ) );
                        $dt->setTimezone( wp_timezone() );
                        echo '<strong>' . esc_html( $dt->format( 'd.m.Y, H:i' ) ) . ' Uhr</strong>';
                    } else {
                        echo '<em style="color:#888">Noch nicht synchronisiert</em>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th>Cache-Status</th>
                <td>
                    <?php
                    $cached = get_transient( GSH_TP_CACHE_KEY );
                    if ( $cached ) {
                        echo '<span style="color:#1e8449">&#10003; Cache vorhanden &ndash; Kalenderdaten werden aus dem Cache geladen</span>';
                    } else {
                        echo '<span style="color:#888">&#8212; Kein Cache aktiv &ndash; wird beim nächsten Seitenaufruf neu abgerufen</span>';
                    }
                    ?>
                </td>
            </tr>
        </table>
        <form method="post" style="margin-top:.75rem">
            <?php wp_nonce_field( 'gsh_tp_sync', 'gsh_tp_sn' ); ?>
            <p>
                <button type="submit" name="gsh_tp_sync" class="button button-primary" style="height:36px;font-size:14px;padding:0 18px">
                    &#8635; Jetzt synchronisieren
                </button>
                <span class="description" style="margin-left:10px;line-height:36px">
                    Leert den Cache und ruft sofort die aktuellen Kalenderdaten vom IServ ab.
                </span>
            </p>
        </form>

        <hr />
        <h2>Cache manuell leeren</h2>
        <form method="post">
            <?php wp_nonce_field( 'gsh_tp_cc', 'gsh_tp_cn' ); ?>
            <p>
                <button type="submit" name="gsh_tp_cc" class="button">Cache leeren</button>
                <span class="description" style="margin-left:10px">
                    Löscht nur den gespeicherten Cache. Die Daten werden beim nächsten Seitenaufruf neu abgerufen.
                </span>
            </p>
        </form>

        <hr />
        <h2>Shortcode-Verwendung</h2>
        <p><code>[gsh_terminplan]</code> &ndash; Zeigt automatisch das aktuelle Quartal</p>
        <p><code>[gsh_terminplan quartal="2"]</code> &ndash; Zeigt ein bestimmtes Quartal (1&ndash;4)</p>
        <p><code>[gsh_terminplan quartal="alle"]</code> &ndash; Alle Quartale mit Tabs</p>
    </div>
    <?php
}

/* ================================================================
   2. iCAL ABRUFEN & PARSEN
   ================================================================ */

/**
 * Holt den iCal-Feed von IServ und speichert ihn im Cache.
 *
 * Diese Funktion prüft zuerst, ob die Daten noch im WordPress-Transient-Cache
 * vorhanden sind. Falls nicht, wird der Feed von IServ heruntergeladen.
 * Falls IServ nicht erreichbar ist, werden die zuletzt gespeicherten Daten
 * als Notfall-Backup zurückgegeben. Bei einem erfolgreichen Download wird
 * der Zeitstempel der letzten Synchronisierung aktualisiert.
 *
 * @since 1.2.0
 * @return string Der rohe iCal-Text (RFC 5545) oder ein leerer String bei Fehler.
 */
function gsh_tp_fetch_ical() {
    $cached = get_transient( GSH_TP_CACHE_KEY );
    if ( false !== $cached ) {
        return $cached;
    }

    $url = get_option( 'gsh_tp_ical_url', '' );
    if ( empty( $url ) ) {
        return '';
    }

    $resp = wp_remote_get( $url, array(
        'timeout'   => 15,
        'sslverify' => true,
        'headers'   => array( 'Accept' => 'text/calendar' ),
    ) );

    if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) {
        $bk = get_option( GSH_TP_BACKUP_KEY, '' );
        if ( $bk ) {
            set_transient( GSH_TP_CACHE_KEY, $bk, 600 );
            return $bk;
        }
        return '';
    }

    $body = wp_remote_retrieve_body( $resp );
    if ( strpos( $body, 'BEGIN:VCALENDAR' ) === false ) {
        return get_option( GSH_TP_BACKUP_KEY, '' );
    }

    $dur = max( 300, absint( get_option( 'gsh_tp_cache_duration', 3600 ) ) );
    set_transient( GSH_TP_CACHE_KEY, $body, $dur );
    update_option( GSH_TP_BACKUP_KEY, $body, false );
    update_option( 'gsh_tp_last_sync', gmdate( 'Y-m-d H:i:s' ) );
    return $body;
}

/**
 * Parst den rohen iCal-Text in ein Array von Event-Arrays.
 *
 * Extrahiert alle VEVENT-Blöcke mit einer Regex und übergibt jeden Block
 * einzeln an gsh_tp_parse_event(). Das Ergebnis-Array wird nach Startdatum
 * sortiert, damit die Termine später chronologisch verarbeitet werden.
 *
 * @since 1.2.0
 * @param  string $data Roher iCal-Text (BEGIN:VCALENDAR … END:VCALENDAR).
 * @return array        Sortiertes Array von Event-Arrays (oder leeres Array).
 */
function gsh_tp_parse_events( $data ) {
    if ( empty( $data ) ) {
        return array();
    }
    preg_match_all( '/BEGIN:VEVENT(.*?)END:VEVENT/s', $data, $m );
    if ( empty( $m[1] ) ) {
        return array();
    }
    $evts = array();
    foreach ( $m[1] as $blk ) {
        $e = gsh_tp_parse_event( $blk );
        if ( $e ) {
            $evts[] = $e;
        }
    }
    usort( $evts, function ( $a, $b ) {
        return strcmp( $a['start'], $b['start'] );
    } );
    return $evts;
}

/**
 * Wandelt einen einzelnen VEVENT-Block in ein strukturiertes Array um.
 *
 * Verarbeitet RFC-5545-Besonderheiten: Zeilenumbrüche normalisieren,
 * gefaltete Zeilen (Folding) zusammenführen, Datum-Formate (DATE und
 * DATE-TIME) parsen, iCal-Escape-Sequenzen (\n, \,, \;) auflösen.
 * Mehrfache CATEGORIES-Felder werden zu einem kommaseparierten String
 * zusammengefügt. Gibt null zurück wenn DTSTART oder SUMMARY fehlen.
 *
 * @since 1.2.0
 * @param  string     $blk Inhalt eines VEVENT-Blocks (ohne BEGIN/END:VEVENT).
 * @return array|null      Assoziatives Array mit start, end, summary,
 *                         description, categories, allday – oder null.
 */
function gsh_tp_parse_event( $blk ) {
    // Zeilenumbrüche normalisieren, gefaltete Zeilen zusammenführen (RFC 5545)
    $blk   = str_replace( "\r\n", "\n", $blk );
    $blk   = preg_replace( '/\n[ \t]/', '', $blk );
    $props = array();

    foreach ( explode( "\n", trim( $blk ) ) as $ln ) {
        $ln = trim( $ln );
        if ( $ln && preg_match( '/^([A-Z\-]+)(?:;[^:]*)?:(.*)$/s', $ln, $p ) ) {
            $k = strtoupper( $p[1] );
            // CATEGORIES kann mehrfach vorkommen → zusammenführen
            $props[ $k ] = ( isset( $props[ $k ] ) && $k === 'CATEGORIES' )
                ? $props[ $k ] . ',' . $p[2]
                : $p[2];
        }
    }

    if ( empty( $props['DTSTART'] ) || empty( $props['SUMMARY'] ) ) {
        return null;
    }

    // iCal-Escaping rückgängig machen
    $ue = function ( $s ) {
        return str_replace(
            array( '\\n', '\\,', '\\;', '\\\\' ),
            array( "\n", ',', ';', '\\' ),
            $s
        );
    };

    // Datum parsen (VALUE=DATE und VALUE=DATE-TIME)
    $pd = function ( $d ) {
        $c = preg_replace( '/[^0-9T]/', '', $d );
        return strlen( $c ) >= 8
            ? substr( $c, 0, 4 ) . '-' . substr( $c, 4, 2 ) . '-' . substr( $c, 6, 2 )
            : null;
    };

    $start = $pd( $props['DTSTART'] );
    $end   = isset( $props['DTEND'] ) ? $pd( $props['DTEND'] ) : $start;
    if ( ! $start ) {
        return null;
    }
    if ( ! $end ) {
        $end = $start;
    }

    return array(
        'start'      => $start,
        'end'        => $end,
        'summary'    => $ue( $props['SUMMARY'] ),
        'description'=> isset( $props['DESCRIPTION'] ) ? $ue( $props['DESCRIPTION'] ) : '',
        'categories' => isset( $props['CATEGORIES'] ) ? $ue( $props['CATEGORIES'] ) : '',
        'allday'     => strlen( preg_replace( '/[^0-9]/', '', $props['DTSTART'] ) ) === 8,
    );
}

/* ================================================================
   3. SCHULWOCHEN & QUARTALE
   ================================================================ */

/**
 * Berechnet die Schulwochennummer für ein bestimmtes Datum.
 *
 * Zählt die Kalenderwochen ab dem ersten Montag des Schuljahres. Dates vor
 * dem Schuljahresstart geben 0 zurück. Die Schulwochennummern entsprechen
 * nicht den ISO-Kalenderwochen, sondern einer fortlaufenden Zählung ab
 * Schuljahresbeginn (Schulwoche 01, 02, … usw.).
 *
 * @since 1.2.0
 * @param  string $date  Datum im Format Y-m-d.
 * @param  string $start Erster Montag des Schuljahres (Y-m-d).
 * @return int           Schulwochennummer (≥1) oder 0 wenn vor Schuljahresbeginn.
 */
function gsh_tp_schulwoche( $date, $start ) {
    $days = (int) ( new DateTime( $start ) )->diff( new DateTime( $date ) )->format( '%r%a' );
    return $days < 0 ? 0 : (int) floor( $days / 7 ) + 1;
}

/**
 * Ermittelt das aktuell laufende Quartal (1–4) anhand des heutigen Datums.
 *
 * Vergleicht das aktuelle UTC-Datum mit den gespeicherten Quartalsgrenzen.
 * Liegt das heutige Datum in keinem Quartal (z. B. in den Sommerferien),
 * wird Quartal 1 zurückgegeben.
 *
 * @since 1.2.0
 * @return int Quartalsnummer 1–4.
 */
function gsh_tp_current_q() {
    $t = gmdate( 'Y-m-d' );
    foreach ( gsh_tp_quartale() as $i => $q ) {
        if ( $t >= $q['start'] && $t <= $q['end'] ) {
            return $i + 1;
        }
    }
    return 1;
}

/**
 * Liest die Quartalsgrenzen aus den Einstellungen und gibt sie als Array zurück.
 *
 * Liest den mehrzeiligen String aus der Option „gsh_tp_quartal_grenzen",
 * parst jede Zeile im Format „JJJJ-MM-TT|JJJJ-MM-TT" und gibt ein Array
 * mit je 'start' und 'end' zurück. Falls weniger als 4 Quartale definiert
 * sind, werden Platzhalter aufgefüllt.
 *
 * @since 1.2.0
 * @return array Array mit 4 Einträgen, jeder mit 'start' und 'end' (Y-m-d).
 */
function gsh_tp_quartale() {
    $raw = get_option( 'gsh_tp_quartal_grenzen',
        "2025-08-25|2025-10-31\n2025-11-03|2026-02-06\n2026-02-09|2026-05-01\n2026-05-04|2026-07-17" );
    $r = array();
    foreach ( array_filter( array_map( 'trim', explode( "\n", $raw ) ) ) as $ln ) {
        $p = explode( '|', $ln );
        if ( count( $p ) === 2 ) {
            $r[] = array( 'start' => trim( $p[0] ), 'end' => trim( $p[1] ) );
        }
    }
    while ( count( $r ) < 4 ) {
        $r[] = array( 'start' => '2025-08-25', 'end' => '2026-07-17' );
    }
    return $r;
}

/* ================================================================
   4. KATEGORIE-ERKENNUNG (Wortgrenzen-Matching)
   ================================================================ */

/**
 * Bestimmt die CSS-Farbklasse für einen Termin anhand seines CATEGORIES-Felds.
 *
 * Gleicht den übergebenen Kategoriestring mit dem konfigurierten Mapping ab.
 * Das Mapping wird beim ersten Aufruf geladen und in einer statischen Variable
 * gecacht, damit es bei ~400 Events nicht bei jedem Aufruf neu aus der
 * Datenbank geladen wird. Gibt 'standard' (grau) zurück wenn keine
 * passende Kategorie gefunden wird.
 *
 * @since 1.2.0
 * @param  string $categories Das CATEGORIES-Feld des iCal-Termins.
 * @return string             CSS-Klassenname ohne Präfix, z. B. 'konferenz'.
 */
function gsh_tp_cat( $categories ) {
    // Mapping nur einmal laden – nicht bei jedem der ~400 Events neu
    static $map = null;
    if ( null === $map ) {
        $map = gsh_tp_build_map();
    }

    $cats = trim( $categories );
    if ( $cats === '' ) {
        return 'standard';
    }

    foreach ( $map as $entry ) {
        if ( preg_match( $entry['regex'], $cats ) ) {
            return $entry['cls'];
        }
    }

    return 'standard';
}

/**
 * Baut das Kategorie-Mapping aus den Einstellungen als Array von Regex-Regeln.
 *
 * Liest den gespeicherten Mapping-Text, überspringt leere Zeilen und Kommentare
 * (Zeilen mit #) und erstellt für jedes Stichwort einen vorcompilierten
 * regulären Ausdruck mit Wortgrenzen (\b). preg_quote() wird verwendet, um
 * Sonderzeichen in Stichwörtern (z. B. Klammern) sicher zu escapen.
 *
 * @since 1.2.0
 * @return array Array von Arrays mit 'regex' (string) und 'cls' (string).
 */
function gsh_tp_build_map() {
    $raw = get_option( 'gsh_tp_kategorie_mapping', gsh_tp_default_mapping() );
    $r   = array();

    foreach ( array_filter( array_map( 'trim', explode( "\n", $raw ) ) ) as $ln ) {
        if ( $ln === '' || $ln[0] === '#' ) {
            continue;
        }
        $p = explode( '|', $ln );
        if ( count( $p ) !== 2 ) {
            continue;
        }
        $kw  = trim( $p[0] );
        $cls = sanitize_html_class( trim( $p[1] ) );
        if ( $kw === '' || $cls === '' ) {
            continue;
        }
        // preg_quote() escaped Sonderzeichen im Stichwort (z. B. "Tag der Deutschen Einheit"),
        // damit sie den Regex nicht kaputt machen (Regex-Injection-Schutz).
        $escaped = preg_quote( $kw, '/' );
        $r[] = array(
            // /iu = case-insensitive + Unicode-Unterstützung für Umlaute
            'regex' => '/\b' . $escaped . '\b/iu',
            'cls'   => $cls,
        );
    }

    return $r;
}

/* ================================================================
   5. SHORTCODE (Hauptausgabe)
   ================================================================ */

/**
 * Shortcode-Handler: Erzeugt den kompletten Terminplan-HTML-Block.
 *
 * Wird aufgerufen wenn WordPress [gsh_terminplan] im Seitentext findet.
 * Lädt die Kalenderdaten, parst die Events, baut den Header (Titel, Suche,
 * Sync-Zeitstempel), die Quartal-Tabs, die Filterbuttons, alle vier
 * Quartalstabellen sowie den Footer mit Druckbuttons zusammen. Am Ende wird
 * das gesamte inline-CSS und -JavaScript eingehängt.
 *
 * @since 1.2.0
 * @param  array  $atts Shortcode-Attribute. Unterstützt: quartal (auto|1-4|alle).
 * @return string       Kompletter HTML-String des Terminplan-Widgets.
 */
add_shortcode( 'gsh_terminplan', 'gsh_tp_shortcode' );

function gsh_tp_shortcode( $atts ) {
    $atts = shortcode_atts( array( 'quartal' => 'auto' ), $atts, 'gsh_terminplan' );

    $data = gsh_tp_fetch_ical();
    if ( ! $data ) {
        return '<div style="padding:1.5rem;background:#fadbd8;border:1px solid #e74c3c;'
             . 'border-radius:8px;color:#922b21;text-align:center">'
             . esc_html__( 'Keine Kalenderdaten verfügbar. Bitte die iCal-URL in den Plugin-Einstellungen prüfen.', 'gsh-terminplan' )
             . '</div>';
    }

    $events  = gsh_tp_parse_events( $data );
    $grenzen = gsh_tp_quartale();
    $sjs     = get_option( 'gsh_tp_schuljahr_start', '2025-08-25' );
    $aq      = ( $atts['quartal'] === 'auto' || $atts['quartal'] === 'alle' )
               ? gsh_tp_current_q()
               : max( 1, min( 4, absint( $atts['quartal'] ) ) );

    $qlbl = array(
        1 => 'Quartal 1 &ndash; August bis Oktober',
        2 => 'Quartal 2 &ndash; November bis Januar',
        3 => 'Quartal 3 &ndash; Februar bis April',
        4 => 'Quartal 4 &ndash; Mai bis Juli',
    );

    // Letzte Sync-Zeit ermitteln
    $last_sync = get_option( 'gsh_tp_last_sync', '' );
    if ( $last_sync ) {
        $dt = new DateTime( $last_sync, new DateTimeZone( 'UTC' ) );
        $dt->setTimezone( wp_timezone() );
        $sync_display = $dt->format( 'd.m.Y, H:i' );
    } else {
        $sync_display = wp_date( 'd.m.Y, H:i' );
    }

    // ── Ausgabe zusammenbauen ──
    $o  = gsh_tp_css();
    $o .= '<div class="gtp" id="gtp">';

    // Header
    $o .= '<div class="gtp-hd">';
    $o .= '<div class="gtp-hd-left">';
    $o .= '<h2 class="gtp-t">Jahresterminplan</h2>';
    $o .= '<span class="gtp-subtitle">Schuljahr 2025/26 &mdash; Gesamtschule Horst</span>';
    $o .= '</div>';
    $o .= '<div class="gtp-search">';
    $o .= '<input type="search" id="gtp-search-input" class="gtp-search-input"'
        . ' placeholder="&#128269; Termin suchen&hellip;" autocomplete="off"'
        . ' oninput="gtpSearchInput(this.value)" />';
    $o .= '<div class="gtp-search-results" id="gtp-search-results" style="display:none"></div>';
    $o .= '</div>';
    $o .= '<span class="gtp-meta">Aktualisiert: ' . esc_html( $sync_display ) . ' Uhr</span>';
    $o .= '</div>';

    // Tabs
    $o .= '<div class="gtp-tabs" role="tablist">';
    for ( $i = 1; $i <= 4; $i++ ) {
        $on = $i === $aq ? ' gtp-tab-on' : '';
        $o .= '<button type="button" class="gtp-tab' . $on . '" data-q="' . $i
            . '" role="tab" aria-selected="' . ( $i === $aq ? 'true' : 'false' )
            . '" onclick="gtpTab(' . $i . ')">Quartal ' . $i . '</button>';
    }
    $o .= '</div>';

    // Filter-Buttons
    $fc = array(
        'konferenz' => 'Konferenzen',
        'pruefung'  => 'Prüfungen',
        'projekt'   => 'Projekte',
        'frei'      => 'Ferien / Frei',
        'eltern'    => 'Eltern',
        'frist'     => 'Fristen',
        'standard'  => 'Sonstige',
    );
    $o .= '<div class="gtp-filt-wrap">';
    $o .= '<span class="gtp-filt-lbl">Anzeige filtern:</span>';
    $o .= '<div class="gtp-filt">';
    foreach ( $fc as $k => $l ) {
        $o .= '<button type="button" class="gtp-fb gtp-fb-on" data-c="'
            . esc_attr( $k ) . '" onclick="gtpFil(this)">'
            . esc_html( $l ) . '</button>';
    }
    $o .= '<button type="button" id="gtp-reset" class="gtp-reset" onclick="gtpReset()" style="display:none">'
        . '&#10005; Alle anzeigen</button>';
    $o .= '</div></div>';

    // Quartalspanels
    for ( $q = 1; $q <= 4; $q++ ) {
        $vis = $q === $aq ? 'block' : 'none';
        $qd  = $grenzen[ $q - 1 ] ?? null;
        if ( ! $qd ) {
            continue;
        }
        $o .= '<div class="gtp-qp" id="gtp-q' . $q . '" style="display:' . $vis . '">';
        $o .= '<h3 class="gtp-qt">' . $qlbl[ $q ] . '</h3>';
        $o .= gsh_tp_table( $events, $qd, $sjs );
        $o .= '</div>';
    }

    // Footer mit Druckbuttons
    $o .= '<div class="gtp-ft"><div class="gtp-ft-btns">';
    $o .= '<button type="button" class="gtp-btn" onclick="gtpPrint(\'single\')">'
        . 'Quartal drucken</button>';
    $o .= '<button type="button" class="gtp-btn gtp-b2" onclick="gtpPrint(\'all\')">'
        . 'Alle Quartale drucken</button>';
    $o .= '</div>';
    $o .= '<span class="gtp-src">Quelle: IServ-Kalender</span>';
    $o .= '</div>';

    $o .= '<button type="button" id="gtp-heute-btn" onclick="gtpScrollToday()" aria-label="Zur heutigen Woche springen">&#128205; Heute</button>';
    $o .= '</div>'; // .gtp
    $o .= gsh_tp_js();
    return $o;
}

/* ================================================================
   6. TABELLE (eine Quartalstabelle)
   ================================================================ */

/**
 * Gibt die Laufzeit eines Termins in ganzen Tagen zurück.
 *
 * Für ganztägige iCal-Termine ist DTEND exklusiv (d. h. der Folgetag nach dem
 * letzten Tag). Deshalb liefert die direkte Datumsdifferenz bereits die korrekte
 * Anzahl ganzer Tage – keine manuelle -1-Korrektur nötig.
 * Beispiel: Montag–Freitag → DTSTART=Mo, DTEND=Sa → diff = 5 Tage ✓.
 * Termine mit Start = Ende (Punkttermin) geben 1 zurück.
 *
 * @since 1.4.0
 * @param  array $ev Event-Array mit 'start' und 'end' (Y-m-d).
 * @return int       Dauer in Tagen, mindestens 1.
 */
function gsh_tp_event_duration( $ev ) {
    $s = new DateTime( $ev['start'] );
    $e = new DateTime( $ev['end'] );
    return max( 1, (int) $s->diff( $e )->format( '%a' ) );
}

/**
 * Erzeugt die HTML-Tabelle für ein einzelnes Quartal.
 *
 * Iteriert wochenweise vom Quartalsbeginn bis zum Quartalssende. Für jede Woche
 * werden zunächst lange Termine (≥5 Tage, z. B. Ferien) gesammelt. Danach werden
 * die fünf Tagesspalten (Mo–Fr) gerendert, wobei lange Termine dort übersprungen
 * und stattdessen mit Datumsbereich in der Hinweise-Spalte ausgegeben werden.
 * Vergangene Wochen (Freitag < heute) erhalten die Klasse „gtp-past".
 *
 * @since 1.2.0
 * @param  array  $events Alle geparsten Events des Schuljahres.
 * @param  array  $qd     Quartalsgrenzen: array('start'=>'Y-m-d','end'=>'Y-m-d').
 * @param  string $sjs    Erster Montag des Schuljahres (Y-m-d) für Schulwochenberechnung.
 * @return string         HTML-String der kompletten Quartalstabelle.
 */
function gsh_tp_table( $events, $qd, $sjs ) {
    $qs  = new DateTime( $qd['start'] );
    $qe  = new DateTime( $qd['end'] );
    $td  = gmdate( 'Y-m-d' );

    // Zur letzten Montag-Position zurückgehen
    $dow = (int) $qs->format( 'N' );
    if ( $dow > 1 ) {
        $qs->modify( '-' . ( $dow - 1 ) . ' days' );
    }

    $h  = '<table class="gt"><thead><tr>';
    $h .= '<th class="gs">SW</th>';
    $h .= '<th>Mo</th><th>Di</th><th>Mi</th><th>Do</th><th>Fr</th>';
    $h .= '<th class="gh">Hinweise</th>';
    $h .= '</tr></thead><tbody>';

    $c   = clone $qs;
    $lim = 50; // Sicherheit gegen Endlosschleifen

    while ( $c <= $qe && $lim-- > 0 ) {
        $sw      = gsh_tp_schulwoche( $c->format( 'Y-m-d' ), $sjs );
        $friday  = ( clone $c )->modify( '+4 days' )->format( 'Y-m-d' );
        $h .= $friday < $td ? '<tr class="gtp-past">' : '<tr>';
        $h .= '<td class="gs"><b>' . ( $sw > 0 ? sprintf( '%02d', $sw ) : '–' ) . '</b></td>';

        // ── Vorarbeiten: Lange Termine dieser Woche für die Hinweise-Spalte sammeln ──
        // Ein Termin gilt als "lang", wenn er >= 5 Tage dauert (ganze Woche oder länger).
        $hinweise_keys = array(); // Duplikate über Wochentage hinweg verhindern
        $hinweise_long = array(); // Lange Termine → nur in Hinweise, nicht in Tagesspalte
        $hinweise_frist = array(); // Frist-Termine (kurz) → zusätzlich in Hinweise

        for ( $d = 0; $d < 5; $d++ ) {
            $dy = clone $c;
            $dy->modify( "+{$d} days" );
            $ds = $dy->format( 'Y-m-d' );
            foreach ( gsh_tp_day_events( $events, $ds ) as $ev ) {
                if ( gsh_tp_event_duration( $ev ) >= 5 ) {
                    $key = $ev['start'] . '|' . $ev['summary'];
                    if ( ! isset( $hinweise_keys[ $key ] ) ) {
                        $hinweise_keys[ $key ] = true;
                        $hinweise_long[] = $ev;
                    }
                }
            }
        }

        // ── Tagesspalten rendern ──
        for ( $d = 0; $d < 5; $d++ ) {
            $dy = clone $c;
            $dy->modify( "+{$d} days" );
            $ds = $dy->format( 'Y-m-d' );
            $de = gsh_tp_day_events( $events, $ds );

            $cl = 'gd';
            if ( $ds === $td ) {
                $cl .= ' gt-today';
            }

            // Ferientag erkennen – auch lange Ferien-Termine markieren den Tag grau
            $hol = false;
            foreach ( $de as $ev ) {
                if ( gsh_tp_cat( $ev['categories'] ) === 'frei' ) {
                    $hol = true;
                    break;
                }
            }
            if ( $hol ) {
                $cl .= ' gt-hol';
            }

            $h .= '<td class="' . esc_attr( $cl ) . '">';
            $h .= '<span class="gdl">' . esc_html( $dy->format( 'd.m.' ) ) . '</span>';

            foreach ( $de as $ev ) {
                // Lange Termine (>= 5 Tage) erscheinen nur in der Hinweise-Spalte
                if ( gsh_tp_event_duration( $ev ) >= 5 ) {
                    continue;
                }

                $cc = gsh_tp_cat( $ev['categories'] );
                $tt = $ev['description'] ? $ev['description'] : $ev['summary'];
                $h .= '<div class="ge gc-' . esc_attr( $cc )
                    . '" data-c="' . esc_attr( $cc )
                    . '" title="' . esc_attr( wp_strip_all_tags( $tt ) ) . '">'
                    . esc_html( $ev['summary'] ) . '</div>';

                // Frist-Termine zusätzlich in Hinweise merken
                if ( $cc === 'frist' ) {
                    $key = $ev['start'] . '|' . $ev['summary'];
                    if ( ! isset( $hinweise_keys[ $key ] ) ) {
                        $hinweise_keys[ $key ] = true;
                        $hinweise_frist[] = $ev;
                    }
                }
            }

            $h .= '</td>';
        }

        // ── Hinweise-Spalte rendern ──
        $h .= '<td class="gh gnc">';

        // Lange Termine (mit Kategorie-Farbe und Datumsbereich)
        foreach ( $hinweise_long as $ev ) {
            $cc      = gsh_tp_cat( $ev['categories'] );
            $tt      = $ev['description'] ? $ev['description'] : $ev['summary'];
            $eff_end = new DateTime( $ev['end'] );
            if ( $ev['allday'] && $ev['end'] > $ev['start'] ) {
                $eff_end->modify( '-1 day' );
            }
            $range = ( new DateTime( $ev['start'] ) )->format( 'd.m.' )
                   . '&ndash;' . $eff_end->format( 'd.m.' );
            $h .= '<div class="gn-long gc-' . esc_attr( $cc )
                . '" data-c="' . esc_attr( $cc )
                . '" title="' . esc_attr( wp_strip_all_tags( $tt ) ) . '">'
                . '<span class="gn-range">' . $range . '</span>'
                . esc_html( $ev['summary'] )
                . '</div>';
        }

        // Frist-Notizen
        foreach ( $hinweise_frist as $ev ) {
            $h .= '<div class="gn" data-c="frist">' . esc_html( $ev['summary'] ) . '</div>';
        }

        $h .= '</td>';
        $h .= '</tr>';
        $c->modify( '+7 days' );
    }

    $h .= '</tbody></table>';
    return $h;
}

/**
 * Gibt alle Events zurück, die an einem bestimmten Tag stattfinden.
 *
 * Prüft für jeden Termin ob das übergebene Datum im Zeitraum start–end liegt.
 * Für ganztägige Events wird DTEND um einen Tag zurückgesetzt, weil iCal
 * DTEND exklusiv definiert (der Tag NACH dem letzten Veranstaltungstag).
 *
 * @since 1.2.0
 * @param  array  $events Alle geparsten Events des Schuljahres.
 * @param  string $date   Datum im Format Y-m-d.
 * @return array          Array der Events die an diesem Tag stattfinden.
 */
function gsh_tp_day_events( $events, $date ) {
    $r = array();
    foreach ( $events as $ev ) {
        $end = $ev['end'];
        // iCal: DTEND bei Ganztags-Terminen ist exklusiv (der Tag DANACH).
        // Beispiel: Ferien Mo–Fr → DTEND = Sa → -1 Tag = Fr → korrekte Grenze.
        if ( $ev['allday'] && $end > $ev['start'] ) {
            $ed = new DateTime( $end );
            $ed->modify( '-1 day' );
            $end = $ed->format( 'Y-m-d' );
        }
        if ( $date >= $ev['start'] && $date <= $end ) {
            $r[] = $ev;
        }
    }
    return $r;
}

/* ================================================================
   7. CSS
   ================================================================ */

/**
 * Gibt den kompletten inline-CSS-Block des Terminplan-Widgets zurück.
 *
 * Das gesamte Styling ist inline, damit das Plugin ohne separate Stylesheets
 * auf jeder WordPress-Installation funktioniert. Alle Klassen sind mit dem
 * Präfix „gtp-" (Container/UI) oder „gt-" (Tabelle) namespaced, um Konflikte
 * mit Theme-CSS zu vermeiden.
 *
 * @since 1.3.0
 * @return string HTML-<style>-Block mit dem gesamten Plugin-CSS.
 */
function gsh_tp_css() {
    return '<style>
/* ═══════════════════════════════════════════════════════════
   GSH Terminplan Dashboard v1.8 – CSS-KLASSEN ÜBERSICHT
   ═══════════════════════════════════════════════════════════
   Namensraum: „gtp-" = Plugin-UI-Elemente, „gt-" = Tabellen-Elemente,
               „ge-" = Event-Zustände, „gd-"/„gn-" = Zellen-Inhalte.

   Layout / Struktur:
     .gtp          Haupt-Container (Karte mit Schatten)
     .gtp-hd       Header (Titel, Suchfeld, Sync-Zeitstempel)
     .gtp-hd-left  Linke Seite des Headers (Titel + Untertitel)
     .gtp-tabs     Tab-Leiste (Quartal 1–4)
     .gtp-tab      Einzelner Tab-Button
     .gtp-tab-on   Aktiver Tab
     .gtp-filt-wrap Umrandung der Filter-Leiste
     .gtp-filt     Reihe der Kategorie-Filterbuttons
     .gtp-fb       Einzelner Filter-Button
     .gtp-fb-on    Filter aktiv (voll sichtbar)
     .gtp-fb-off   Filter inaktiv (ausgegraut)
     .gtp-reset    „Alle anzeigen"-Reset-Button
     .gtp-qp       Quartalspanel (eins pro Quartal, ein/ausgeblendet)
     .gtp-qt       Quartal-Überschrift (z. B. „Quartal 1 – August bis Oktober")
     .gtp-ft       Footer (Druckbuttons + Quellenangabe)
     .gtp-past     Vergangene Wochenzeile (abgedunkelt)

   Tabelle (.gt):
     .gt           Quartalstabelle (border-collapse, fixed layout)
     .gs           Schulwochen-Spalte (links, blauer Text)
     .gh           Hinweise-Spalte (rechts, creme Hintergrund)
     .gd           Tageszelle (Mo–Fr), dynamisch gesetzt
     .gdl          Datumslabel in der Tageszelle (z. B. „14.10.")
     .gnc          Container der Hinweise-Spalte (kleinere Schrift)

   Events:
     .ge           Einzelnes Event-Tag (farbiger Balken in Tagesspalte)
     .gn-long      Langer Termin (≥5 Tage) in Hinweise-Spalte
     .gn-range     Datumsbereich-Label im langen Termin
     .gn           Frist-Notiz in Hinweise-Spalte (kursiv, gelb)
     .ge-hit       Treffer-Highlight bei Textsuche (gelber Outline)

   Kategorie-Farben (kombiniert mit .ge und .gn-long):
     .gc-konferenz Blau   – Konferenzen, Sitzungen, Pflegschaften
     .gc-pruefung  Rot    – Prüfungen, Klausuren, Abschlussprüfungen
     .gc-projekt   Grün   – Projekte, Fahrten, Sport, Veranstaltungen
     .gc-frei      Grau   – Ferien, Feiertage, schulfreie Tage
     .gc-eltern    Orange – Elternarbeit, Beratung, Info-Abende
     .gc-frist     Gelb   – Fristen, Notenabgaben, Hinweise
     .gc-standard  Hellgrau – Termine ohne erkannte Kategorie

   Zellen-Zustände:
     .gt-today     Heutige Tageszelle (blauer Rahmen, hellblauer HG)
     .gt-hol       Ferientag-Hintergrund (hellgrau)

   Suchfeld:
     .gtp-search         Suchfeld-Container
     .gtp-search-input   Pill-förmiges Eingabefeld
     .gtp-search-results Ergebniszeile unter dem Suchfeld
     .gtp-sr-count       Trefferanzahl
     .gtp-sr-none        „Keine Treffer"-Meldung
     .gtp-sr-q           Klickbarer Quartal-Link (z. B. „Q2: 3")

   Floating Button:
     #gtp-heute-btn      Schwebender „Heute"-Button (fixed, unten rechts)
     .gtp-heute-vis      Sichtbarer Zustand des Heute-Buttons
   ═══════════════════════════════════════════════════════════ */

/* ── Container ── */
.gtp{
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
  max-width:1200px;margin:0 auto;color:#1a1a2e;
  background:#fff;border-radius:12px;
  box-shadow:0 2px 20px rgba(0,0,0,.09);
  padding:1.5rem 1.75rem;
}

/* ── Header ── */
.gtp-hd{
  display:flex;justify-content:space-between;align-items:center;
  flex-wrap:wrap;margin-bottom:1.25rem;padding-bottom:1rem;
  border-bottom:3px solid #1a5276;gap:.75rem;
}
.gtp-hd-left{display:flex;flex-direction:column;gap:.2rem}
.gtp-t{font-size:1.5rem;font-weight:700;color:#1a5276;margin:0;letter-spacing:-.01em}
.gtp-subtitle{font-size:.78rem;color:#7f8c8d;font-weight:400}
.gtp-meta{font-size:.75rem;color:#adb5bd;white-space:nowrap}

/* ── Tabs ── */
.gtp-tabs{
  display:flex;gap:0;margin-bottom:0;
  border-bottom:2px solid #d4e6f1;
}
.gtp-tab{
  padding:.55rem 1.4rem;
  border:2px solid transparent;border-bottom:none;
  border-radius:8px 8px 0 0;background:transparent;
  color:#5d7a8a;font-weight:600;font-size:.88rem;
  cursor:pointer;transition:background .18s,color .18s;
  margin-bottom:-2px;
}
.gtp-tab:hover{background:#f0f7fc;color:#1a5276}
.gtp-tab-on{background:#fff;color:#1a5276;border-color:#d4e6f1;border-bottom-color:#fff}

/* ── Filter ── */
.gtp-filt-wrap{
  margin:1rem 0;padding:.75rem 1rem;
  background:#f8fafc;border-radius:8px;
  border:1px solid #e4ecf3;
}
.gtp-filt-lbl{
  font-size:.7rem;font-weight:700;color:#7f8c8d;
  text-transform:uppercase;letter-spacing:.07em;
  display:block;margin-bottom:.5rem;
}
.gtp-filt{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
.gtp-fb{
  padding:4px 13px;border:1.5px solid;border-radius:20px;
  font-size:.76rem;cursor:pointer;font-weight:500;line-height:1.6;
  transition:opacity .18s,filter .18s;
}
.gtp-fb[data-c="konferenz"]{border-color:#2874a6;background:#d6eaf8;color:#1a5276}
.gtp-fb[data-c="pruefung"] {border-color:#c0392b;background:#fadbd8;color:#922b21}
.gtp-fb[data-c="projekt"]  {border-color:#27ae60;background:#d5f5e3;color:#1e8449}
.gtp-fb[data-c="frei"]     {border-color:#95a5a6;background:#eaecee;color:#616a6b}
.gtp-fb[data-c="eltern"]   {border-color:#e67e22;background:#fdebd0;color:#b9770e}
.gtp-fb[data-c="frist"]    {border-color:#d4ac0d;background:#fcf3cf;color:#7d6608}
.gtp-fb[data-c="standard"] {border-color:#566573;background:#f2f3f4;color:#2c3e50}
.gtp-fb-off{opacity:.28;filter:grayscale(.5)}

/* Reset-Button */
.gtp-reset{
  padding:4px 13px;border:1.5px solid #aaa;border-radius:20px;
  font-size:.76rem;cursor:pointer;font-weight:500;line-height:1.6;
  background:#fff;color:#555;transition:all .18s;margin-left:4px;
}
.gtp-reset:hover{background:#eaecee;border-color:#666;color:#333}

/* ── Quartal-Überschrift ── */
.gtp-qt{
  font-size:.92rem;font-weight:700;color:#1a5276;
  margin:1rem 0 .5rem;padding-left:2px;
}

/* ── Tabelle ── */
.gt{width:100%;border-collapse:collapse;font-size:.82rem;table-layout:fixed}
.gt thead th{
  background:#1a5276;color:#fff;padding:8px 6px;
  text-align:center;font-weight:600;font-size:.76rem;
  border:1px solid #15456a;letter-spacing:.02em;
  position:sticky;top:0;z-index:10;
  box-shadow:0 1px 0 0 #15456a;
}
.admin-bar .gt thead th{top:32px}
@media screen and (max-width:782px){.admin-bar .gt thead th{top:46px}}
/* ── Vergangene Wochen ── */
.gtp-past{opacity:.45;transition:opacity .15s}
.gtp-past:hover{opacity:.85}
.gtp-past .ge{filter:grayscale(40%)}
.gtp-past:hover .ge{filter:none}
.gt tbody tr:hover td{background:rgba(26,82,118,.03)}
.gt tbody td{
  border:1px solid #e8ecef;padding:4px 5px;vertical-align:top;
  transition:background .1s;
}
.gs{width:44px;text-align:center;background:#f7f9fc;font-size:.84rem;font-weight:700;color:#1a5276}
.gh{width:140px;background:#fefdf8}
.gdl{display:block;font-size:.67rem;color:#adb5bd;margin-bottom:2px;letter-spacing:.01em}
.gnc{font-size:.72rem;color:#666}

/* ── Event-Tags (Tagesspalten) ── */
.ge{
  padding:2px 6px;margin:2px 0;border-radius:4px;
  font-size:.73rem;line-height:1.4;
  border-left:3px solid transparent;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
  cursor:default;
}
.gc-konferenz{background:#d6eaf8;border-left-color:#2874a6;color:#1a5276}
.gc-pruefung {background:#fadbd8;border-left-color:#c0392b;color:#922b21}
.gc-projekt  {background:#d5f5e3;border-left-color:#27ae60;color:#1e8449}
.gc-frei     {background:#eaecee;border-left-color:#95a5a6;color:#616a6b}
.gc-eltern   {background:#fdebd0;border-left-color:#e67e22;color:#b9770e}
.gc-frist    {background:#fef9e7;border-left-color:#d4ac0d;color:#7d6608}
.gc-standard {background:#f2f3f4;border-left-color:#566573;color:#2c3e50}

/* ── Lange Termine in der Hinweise-Spalte ── */
.gn-long{
  padding:2px 5px;margin:2px 0;border-radius:3px;
  font-size:.72rem;line-height:1.4;
  border-left:3px solid transparent;
  font-weight:500;
  /* gc-XXX-Klassen übernehmen Farbe und Hintergrund */
}
.gn-range{
  display:block;font-size:.65rem;opacity:.7;font-style:normal;
  margin-bottom:1px;
}

/* ── Frist-Notizen in der Hinweise-Spalte ── */
.gn{
  padding:2px 5px;margin:2px 0;
  background:#fef9e7;border-radius:3px;
  font-style:italic;font-size:.72rem;color:#7d6608;
}

/* ── Heute & Ferien ── */
.gt-today{background:#e8f4fd!important;box-shadow:inset 0 0 0 2px #2874a6}
.gt-hol  {background:#f5f5f5!important}

/* ── Footer ── */
.gtp-ft{
  display:flex;justify-content:space-between;align-items:center;
  margin-top:1.25rem;padding-top:.75rem;
  border-top:1px solid #e8ecef;flex-wrap:wrap;gap:.5rem;
}
.gtp-ft-btns{display:flex;gap:.5rem;flex-wrap:wrap}
.gtp-btn{
  padding:7px 16px;background:#1a5276;color:#fff;border:none;
  border-radius:6px;cursor:pointer;font-size:.82rem;font-weight:500;
  transition:background .18s;
}
.gtp-btn:hover{background:#154360}
.gtp-b2{background:#566573}
.gtp-b2:hover{background:#2c3e50}
.gtp-src{font-size:.7rem;color:#adb5bd}

/* ── Druck-iframe ── */
#gtp-print-frame{position:fixed;left:-9999px;top:-9999px;width:0;height:0;border:none}

/* ── Floating Heute-Button ── */
#gtp-heute-btn{
  position:fixed;bottom:24px;right:24px;z-index:9999;
  padding:10px 20px;
  background:#1a5276;color:#fff;border:none;
  border-radius:50px;
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
  font-size:.88rem;font-weight:600;letter-spacing:.01em;
  box-shadow:0 4px 16px rgba(0,0,0,.28);
  cursor:pointer;
  opacity:0;pointer-events:none;
  transition:opacity .25s,transform .25s;
  transform:translateY(8px);
}
#gtp-heute-btn.gtp-heute-vis{opacity:1;pointer-events:auto;transform:translateY(0)}
#gtp-heute-btn:hover{background:#154360;box-shadow:0 6px 20px rgba(0,0,0,.32)}

/* ── Suchfeld ── */
.gtp-search{
  display:flex;flex-direction:column;gap:4px;
  flex:1;max-width:300px;
}
.gtp-search-input{
  padding:7px 14px;
  border:1.5px solid #d4e6f1;border-radius:20px;
  font-size:.84rem;color:#1a1a2e;background:#fff;
  outline:none;width:100%;box-sizing:border-box;
  transition:border-color .18s,box-shadow .18s;
}
.gtp-search-input:focus{
  border-color:#1a5276;
  box-shadow:0 0 0 3px rgba(26,82,118,.12);
}
.gtp-search-results{
  display:flex;flex-wrap:wrap;gap:4px;align-items:center;
  font-size:.74rem;padding:0 4px;min-height:18px;
}
.gtp-sr-count{color:#566573;font-weight:600}
.gtp-sr-none{color:#c0392b;font-weight:600}
.gtp-sr-q{
  display:inline-block;padding:1px 8px;
  background:#d6eaf8;color:#1a5276;border-radius:10px;
  text-decoration:none;font-weight:600;font-size:.71rem;
  transition:background .15s,color .15s;
}
.gtp-sr-q:hover{background:#2874a6;color:#fff}

/* ── Treffer-Highlight (Textsuche) ── */
.ge-hit{outline:2px solid #f39c12!important;background:#fef9e7!important}

/* ── Responsive ── */
@media(max-width:768px){
  .gtp{padding:1rem;border-radius:8px}
  .gt{display:block;overflow-x:auto}
  .gtp-tabs{overflow-x:auto;flex-wrap:nowrap;border-bottom:none}
  .gtp-tab{white-space:nowrap;flex-shrink:0}
  .gtp-hd{flex-direction:column;align-items:flex-start}
  .gtp-filt-wrap{padding:.5rem .75rem}
  .gtp-search{max-width:100%;width:100%}
  .gtp-search-input{width:100%}
}
</style>';
}

/* ================================================================
   8. JAVASCRIPT – Tabs, Filter, Druck
   ================================================================ */

/**
 * Gibt den kompletten inline-JavaScript-Block des Widgets zurück.
 *
 * Das JS ist in einem PHP-Heredoc mit einfachen Hochkommas (<<<'JSEOF') eingebettet,
 * damit PHP-Variablen mit $ nicht als PHP-Ausdrücke interpretiert werden.
 * Kein jQuery, keine externen Abhängigkeiten – nur Vanilla JavaScript (ES5-kompatibel).
 *
 * Enthält: Tab-Wechsel, Kategorie-Filter, kombinierte Sichtbarkeitslogik,
 * Echtzeit-Textsuche (debounced), Scroll-to-Today, Floating-Heute-Button,
 * Druckfunktion (iframe-basiert).
 *
 * @since 1.3.0
 * @return string HTML-<script>-Block mit dem gesamten Plugin-JavaScript.
 */
function gsh_tp_js() {
    // Heredoc mit einfachen Hochkommas: PHP interpoliert $ NICHT als Variable
    return <<<'JSEOF'
<script>
/* ════════════════════════════════════════════════════════
   TAB-WECHSEL
   ════════════════════════════════════════════════════════ */

/**
 * Reiner Tab-Wechsel ohne Scroll-Seiteneffekte.
 * Wird intern von gtpTab(), gtpScrollToday() und gtpSearchJump() aufgerufen,
 * damit diese Funktionen den Tab wechseln können ohne eine weitere Scroll-Aktion
 * auszulösen (Endlosrekursion vermeiden).
 */
function gtpSwitchTab(q){
  document.querySelectorAll(".gtp-qp").forEach(function(e){e.style.display="none"});
  document.querySelectorAll(".gtp-tab").forEach(function(e){
    e.classList.remove("gtp-tab-on");
    e.setAttribute("aria-selected","false");
  });
  var p=document.getElementById("gtp-q"+q);
  if(p) p.style.display="block";
  var t=document.querySelector('.gtp-tab[data-q="'+q+'"]');
  if(t){t.classList.add("gtp-tab-on");t.setAttribute("aria-selected","true");}
}

/**
 * Öffentlicher Tab-Wechsel – aufgerufen durch onclick der Tab-Buttons.
 * Wechselt den Tab und scrollt anschließend automatisch zur heutigen Zeile,
 * falls der neue Tab das aktuelle Datum enthält. Aktualisiert auch den
 * Heute-Button-Status.
 */
function gtpTab(q){
  gtpSwitchTab(q);
  var p=document.getElementById("gtp-q"+q);
  if(p && p.querySelector(".gt-today")){
    /* Sanft zur Heute-Zeile im neuen Quartal scrollen */
    setTimeout(gtpScrollToday,60);
  }
  /* Heute-Button-Status nach dem Panel-Wechsel neu berechnen */
  setTimeout(gtpUpdateHeuteBtn,80);
}

/* ══════════════════════════════════════════════════════
   FILTER-LOGIK
   ══════════════════════════════════════════════════════
   Zustand "all":    Alle Kategorien sichtbar, alle Buttons aktiv.
   Zustand "select": Nur ausgewählte Kategorien sichtbar.

   Klick im Zustand "all":
     → Wechsel zu "select", nur die geklickte Kategorie ist aktiv.

   Klick im Zustand "select":
     → Aktiv: Kategorie entfernen. Wenn keine mehr aktiv → zurück zu "all".
     → Inaktiv: Kategorie hinzufügen.

   Reset-Button: Zurück zu "all".
   ══════════════════════════════════════════════════════ */

var gtpMode = "all";   // "all" | "select"
var gtpSel  = {};      // { kategorie: true, ... } im Modus "select"

/**
 * Verarbeitet einen Klick auf einen Kategorie-Filterbutton.
 * Erster Klick: wechselt in den Exklusiv-Modus (nur diese Kategorie sichtbar).
 * Weiterer Klick auf aktive Kategorie: deaktiviert sie. Sind alle deaktiviert,
 * kehrt der Modus zu "all" zurück. Aufgerufen durch onclick der .gtp-fb-Buttons.
 */
function gtpFil(btn){
  var c = btn.getAttribute("data-c");

  if(gtpMode === "all"){
    /* Erster Klick: Exklusiv-Modus starten */
    gtpMode = "select";
    gtpSel  = {};
    gtpSel[c] = true;
  } else {
    if(gtpSel[c]){
      /* Aktive Kategorie ausschalten */
      delete gtpSel[c];
      if(Object.keys(gtpSel).length === 0){
        gtpMode = "all"; /* Alle deaktiviert → zurück zu "alle anzeigen" */
      }
    } else {
      /* Weitere Kategorie hinzuschalten */
      gtpSel[c] = true;
    }
  }
  gtpApply();
}

/**
 * Setzt den Kategorie-Filter zurück – alle Termine werden wieder angezeigt.
 * Aufgerufen durch onclick des Reset-Buttons (#gtp-reset).
 */
function gtpReset(){
  gtpMode = "all";
  gtpSel  = {};
  gtpApply();
}

/* ════════════════════════════════════════════════════════
   SICHTBARKEITS-HELPER (Kategorie-Filter + Textsuche kombiniert)
   ════════════════════════════════════════════════════════
   Entscheidet für ein einzelnes .ge-Element, ob es angezeigt
   wird. Beide Bedingungen müssen gleichzeitig erfüllt sein:
   1. Kategorie ist im Filter aktiv (gtpMode/gtpSel)
   2. textContent/title enthält den aktuellen Suchbegriff
   ════════════════════════════════════════════════════════ */

/**
 * Entscheidet für ein einzelnes .ge-Element ob es sichtbar ist.
 * Kombiniert Kategorie-Filter (gtpMode/gtpSel) und aktuelle Textsuche.
 * Nur wenn BEIDE Bedingungen erfüllt sind, wird das Element angezeigt.
 * Zentrale Funktion die von gtpApply() und gtpDoSearch() genutzt wird.
 */
function gtpApplyVisibility(el){
  var c = el.getAttribute("data-c");
  var categoryOk = (gtpMode === "all") || !!gtpSel[c];

  var inp = document.getElementById("gtp-search-input");
  var q   = inp ? inp.value.trim().toLowerCase() : "";
  var searchOk;
  if(!q){
    searchOk = true;
  } else {
    var txt   = (el.textContent  || "").toLowerCase();
    var title = (el.getAttribute("title") || "").toLowerCase();
    searchOk  = txt.indexOf(q) !== -1 || title.indexOf(q) !== -1;
  }

  el.style.display = (categoryOk && searchOk) ? "" : "none";
}

/**
 * Wendet den aktuellen Kategorie-Filter auf alle Elemente an.
 * Aktualisiert die Filter-Button-Optik (aktiv/inaktiv), blendet Events
 * (.ge), lange Termine (.gn-long) und Frist-Notizen (.gn) passend ein/aus.
 * Zeigt oder versteckt den Reset-Button je nach Filterzustand.
 */
function gtpApply(){
  var isAll = (gtpMode === "all");

  /* Filter-Buttons aktualisieren */
  document.querySelectorAll(".gtp-fb").forEach(function(btn){
    var c = btn.getAttribute("data-c");
    var active = isAll || !!gtpSel[c];
    btn.classList.toggle("gtp-fb-on",  active);
    btn.classList.toggle("gtp-fb-off", !active);
  });

  /* Termine in Tagesspalten: kombinierter Check (Suche + Kategorie) */
  document.querySelectorAll(".ge[data-c]").forEach(function(el){
    gtpApplyVisibility(el);
  });

  /* Lange Termine + Frist-Notizen: nur Kategorie-Filter */
  document.querySelectorAll(".gn-long[data-c], .gn[data-c]").forEach(function(el){
    var c = el.getAttribute("data-c");
    el.style.display = (isAll || !!gtpSel[c]) ? "" : "none";
  });

  /* Reset-Button zeigen / verstecken */
  var resetBtn = document.getElementById("gtp-reset");
  if(resetBtn) resetBtn.style.display = isAll ? "none" : "";
}

/* ════════════════════════════════════════════════════════
   TEXTSUCHE (Echtzeit, alle Quartale)
   ════════════════════════════════════════════════════════
   - Debounce 200 ms für flüssige Performance (~400 Events).
   - Durchsucht textContent und title-Attribut jedes .ge-Elements.
   - Zeigt Gesamttreffer + klickbare Quartal-Links.
   - Arbeitet kombiniert mit dem Kategorie-Filter.
   ════════════════════════════════════════════════════════ */

var gtpSearchTimer = null;

/**
 * Debounce-Wrapper für die Suche – aufgerufen durch oninput des Suchfelds.
 * Wartet 200 ms nach der letzten Tasteneingabe bevor gtpDoSearch() läuft,
 * damit bei schnellem Tippen nicht bei jedem Buchstaben eine Suche ausgeführt
 * wird (~400 Events × DOM-Zugriffe wären sonst spürbar).
 */
function gtpSearchInput(val){
  clearTimeout(gtpSearchTimer);
  gtpSearchTimer = setTimeout(function(){ gtpDoSearch(val); }, 200);
}

/**
 * Führt die eigentliche Suche über alle .ge-Elemente in allen Quartalen durch.
 * Markiert Treffer mit .ge-hit (gelber Outline), zählt sie pro Quartal und
 * baut die Ergebniszeile mit klickbaren Quartal-Links auf. Bei leerem Suchfeld
 * wird .ge-hit entfernt und der Normalmodus (gtpApplyVisibility) wiederhergestellt.
 */
function gtpDoSearch(val){
  var q = val.trim().toLowerCase();

  /* Highlight-Klassen zurücksetzen */
  document.querySelectorAll(".ge-hit").forEach(function(el){
    el.classList.remove("ge-hit");
  });

  /* Bei leerem Suchfeld: Normalmodus wiederherstellen */
  if(!q){
    document.querySelectorAll(".ge[data-c]").forEach(function(el){
      gtpApplyVisibility(el);
    });
    var r = document.getElementById("gtp-search-results");
    if(r){ r.style.display = "none"; r.innerHTML = ""; }
    return;
  }

  /* Alle .ge-Elemente (alle Quartale) durchgehen */
  var totalHits  = 0;
  var hitsByQ    = {};   /* { "1": count, "2": count, ... } */

  document.querySelectorAll(".ge[data-c]").forEach(function(el){
    var txt   = (el.textContent  || "").toLowerCase();
    var title = (el.getAttribute("title") || "").toLowerCase();
    var match = txt.indexOf(q) !== -1 || title.indexOf(q) !== -1;

    if(match){
      el.classList.add("ge-hit");
      totalHits++;
      var panel = el.closest(".gtp-qp");
      if(panel){
        var qid = panel.id.replace("gtp-q","");
        hitsByQ[qid] = (hitsByQ[qid] || 0) + 1;
      }
    }

    /* Sichtbarkeit: Suche + Kategorie-Filter kombiniert */
    gtpApplyVisibility(el);
  });

  /* Ergebnisanzeige aufbauen */
  var resultsEl = document.getElementById("gtp-search-results");
  if(!resultsEl) return;

  if(totalHits === 0){
    resultsEl.innerHTML = '<span class="gtp-sr-none">Keine Treffer</span>';
    resultsEl.style.display = "";
    return;
  }

  var html = '<span class="gtp-sr-count">' + totalHits
           + (totalHits === 1 ? " Treffer" : " Treffer") + '</span>';

  var qKeys = Object.keys(hitsByQ).sort();
  if(qKeys.length > 0){
    html += "&ensp;";
    for(var i=0; i<qKeys.length; i++){
      var qid = qKeys[i];
      html += '<a href="#" class="gtp-sr-q"'
            + ' onclick="gtpSearchJump(' + qid + ');return false;">'
            + "Q" + qid + ":&thinsp;" + hitsByQ[qid] + "</a> ";
    }
  }

  resultsEl.innerHTML = html;
  resultsEl.style.display = "";
}

/**
 * Wechselt zum angegebenen Quartal und scrollt zum ersten Suchtreffer darin.
 * Aufgerufen durch onclick der .gtp-sr-q-Links in der Suchergebnis-Zeile.
 */
function gtpSearchJump(q){
  gtpSwitchTab(q);
  var panel = document.getElementById("gtp-q"+q);
  if(!panel) return;
  var firstHit = panel.querySelector(".ge-hit");
  if(!firstHit) return;
  setTimeout(function(){
    var row    = firstHit.closest("tr") || firstHit;
    var top    = row.getBoundingClientRect().top + window.pageYOffset;
    var offset = Math.max(0, top - Math.floor(window.innerHeight / 3));
    window.scrollTo({top: offset, behavior: "smooth"});
    setTimeout(gtpUpdateHeuteBtn, 420);
  }, 60);
}

/* ════════════════════════════════════════════════════════
   SCROLL ZUR HEUTIGEN ZEILE
   ════════════════════════════════════════════════════════
   - Prüft, ob .gt-today in einem sichtbaren Panel liegt.
   - Falls nicht: wechselt zuerst den Tab (via gtpSwitchTab).
   - Scrollt die Tabellenzeile in das obere Drittel des Viewports.
   ════════════════════════════════════════════════════════ */

/**
 * Scrollt die heutige Tabellenzeile (.gt-today) ins obere Drittel des Viewports.
 * Falls sich .gt-today in einem versteckten Panel befindet, wird zuerst der
 * Tab gewechselt. Wartet kurz auf den Reflow nach dem Panel-Wechsel bevor
 * gescrollt wird. Aufgerufen beim Laden der Seite und durch den Heute-Button.
 */
function gtpScrollToday(){
  var today=document.querySelector(".gt-today");
  if(!today) return;

  /* Falls heute in einem versteckten Panel liegt: Tab wechseln */
  var panel=today.closest(".gtp-qp");
  if(panel && panel.style.display==="none"){
    var qnum=parseInt(panel.id.replace("gtp-q",""),10);
    if(!isNaN(qnum)) gtpSwitchTab(qnum);
  }

  /* Kurz warten (Reflow nach eventuellem Panel-Wechsel) */
  setTimeout(function(){
    var row=today.closest("tr")||today;
    var top=row.getBoundingClientRect().top+window.pageYOffset;
    var offset=Math.max(0,top-Math.floor(window.innerHeight/3));
    window.scrollTo({top:offset,behavior:"smooth"});
    /* Button-Status nach dem Scroll-Ende aktualisieren */
    setTimeout(gtpUpdateHeuteBtn,420);
  },50);
}

/* ════════════════════════════════════════════════════════
   FLOATING HEUTE-BUTTON: SICHTBARKEIT
   ════════════════════════════════════════════════════════
   Zeigt den Button, wenn .gt-today außerhalb des Viewports
   liegt ODER sich in einem aktuell versteckten Quartal befindet.
   ════════════════════════════════════════════════════════ */

/**
 * Aktualisiert die Sichtbarkeit des schwebenden Heute-Buttons.
 * Der Button wird eingeblendet wenn .gt-today außerhalb des Viewports liegt
 * ODER sich in einem aktuell versteckten Quartalspanel befindet.
 * Läuft bei scroll- und resize-Events (passive listener = kein Layout-Block).
 */
function gtpUpdateHeuteBtn(){
  var today=document.querySelector(".gt-today");
  var btn=document.getElementById("gtp-heute-btn");
  if(!btn) return;

  if(!today){
    btn.classList.remove("gtp-heute-vis");
    return;
  }

  var panel=today.closest(".gtp-qp");
  var inActive=!panel||panel.style.display!=="none";

  if(!inActive){
    /* Heute liegt im falschen Quartal → Button immer zeigen */
    btn.classList.add("gtp-heute-vis");
    return;
  }

  /* Heute im aktiven Panel → Button zeigen wenn außerhalb des Viewports */
  var rect=today.getBoundingClientRect();
  var visible=rect.top<window.innerHeight&&rect.bottom>0;
  btn.classList.toggle("gtp-heute-vis",!visible);
}

window.addEventListener("scroll",gtpUpdateHeuteBtn,{passive:true});
window.addEventListener("resize",gtpUpdateHeuteBtn,{passive:true});

/* Auto-Scroll & Button-Init beim Laden – 200 ms Verzögerung damit WordPress
   und das Theme fertig gerendert haben und getBoundingClientRect() korrekte
   Werte liefert. */
setTimeout(function(){
  gtpScrollToday();
  gtpUpdateHeuteBtn();
},200);

/* ══════════════════════════════════════════════════════
   DRUCKFUNKTION – iframe-basiert
   ══════════════════════════════════════════════════════ */

/**
 * Druckt das aktuelle oder alle Quartale über einen unsichtbaren iframe.
 * Baut einen vollständigen HTML-Dokument-String mit eigenem Print-CSS auf,
 * schreibt ihn in den iframe und ruft contentWindow.print() auf. Der iframe-
 * Ansatz umgeht Popup-Blocker (kein window.open nötig). Fällt bei iframe-
 * Fehler auf window.open() zurück. Aufgerufen durch die Druckbuttons im Footer.
 * @param {string} mode  "single" = aktives Quartal, "all" = alle vier Quartale.
 */
function gtpPrint(mode){
  var ids;
  if(mode==="all"){
    ids=[1,2,3,4];
  }else{
    var at=document.querySelector(".gtp-tab-on");
    ids=[at ? parseInt(at.getAttribute("data-q")) : 1];
  }

  var CSS=[
    "*{margin:0;padding:0;box-sizing:border-box}",
    "body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#1a1a2e;padding:6mm 8mm}",
    ".hdr{text-align:center;margin-bottom:8pt;padding-bottom:5pt;border-bottom:2pt solid #1a5276}",
    ".hdr h2{font-size:13pt;color:#1a5276;margin:0 0 2pt}",
    ".hdr p{font-size:7.5pt;color:#666;margin:0}",
    ".qt{font-size:10pt;color:#1a5276;margin:6pt 0 4pt;font-weight:700}",
    "table{width:100%;border-collapse:collapse;font-size:7pt;table-layout:fixed}",
    "thead th{background:#1a5276;color:#fff;padding:3pt 2pt;font-size:6.5pt;border:.4pt solid #15456a;text-align:center;font-weight:600}",
    "tbody td{border:.4pt solid #ccc;padding:2pt 2pt;vertical-align:top}",
    ".gs{width:28pt;text-align:center;background:#f0f4f8;font-weight:700}",
    ".gh{width:85pt;background:#fefdf5}",
    ".gdl{display:block;font-size:5pt;color:#95a5a6;margin-bottom:1pt}",
    ".ge{font-size:6pt;padding:1pt 2pt;margin:.4pt 0;border-radius:1pt;line-height:1.2;border-left:2pt solid transparent}",
    ".gn-long{font-size:6pt;padding:1pt 2pt;margin:.4pt 0;border-radius:1pt;line-height:1.2;border-left:2pt solid transparent;font-weight:500}",
    ".gn-range{display:block;font-size:5pt;opacity:.7}",
    ".gc-konferenz{background:#d6eaf8;border-left-color:#2874a6;color:#1a5276}",
    ".gc-pruefung{background:#fadbd8;border-left-color:#c0392b;color:#922b21}",
    ".gc-projekt{background:#d5f5e3;border-left-color:#27ae60;color:#1e8449}",
    ".gc-frei{background:#eaecee;border-left-color:#95a5a6;color:#616a6b}",
    ".gc-eltern{background:#fdebd0;border-left-color:#e67e22;color:#b9770e}",
    ".gc-frist{background:#fcf3cf;border-left-color:#f1c40f;color:#7d6608}",
    ".gc-standard{background:#f2f3f4;border-left-color:#566573;color:#2c3e50}",
    ".gt-today{box-shadow:inset 0 0 0 1pt #2874a6;background:#e8f4fd}",
    ".gt-hol{background:#f0f0f0}",
    ".gn{font-size:5pt;background:#fef9e7;padding:1pt 2pt;margin:1pt 0;font-style:italic}",
    ".gnc{font-size:5pt}",
    ".leg{display:flex;gap:10pt;flex-wrap:wrap;margin-top:6pt;padding-top:4pt;border-top:.4pt solid #ccc;font-size:6pt}",
    ".leg span{display:inline-flex;align-items:center;gap:2pt}",
    ".ld{width:7pt;height:7pt;border-radius:1pt;display:inline-block}",
    ".pb{page-break-before:always}",
    "thead th,.gs,.gh,.ge,.gn-long,.gc-konferenz,.gc-pruefung,.gc-projekt,.gc-frei,.gc-eltern,.gc-frist,.gc-standard,.gt-today,.gt-hol,.gn,.ld{-webkit-print-color-adjust:exact;print-color-adjust:exact}",
    ".gtp-past{opacity:1 !important}",
    ".gtp-past .ge{filter:none !important}",
    "thead th{position:static !important}",
    "@page{size:A4 landscape;margin:6mm 8mm}",
    "@media print{body{padding:0}}"
  ].join("\n");

  var LEG='<div class="leg">'
    +'<span><span class="ld" style="background:#d6eaf8;border-left:2pt solid #2874a6"></span> Konferenz</span>'
    +'<span><span class="ld" style="background:#fadbd8;border-left:2pt solid #c0392b"></span> Pr\u00fcfung</span>'
    +'<span><span class="ld" style="background:#d5f5e3;border-left:2pt solid #27ae60"></span> Projekt</span>'
    +'<span><span class="ld" style="background:#eaecee;border-left:2pt solid #95a5a6"></span> Ferien</span>'
    +'<span><span class="ld" style="background:#fdebd0;border-left:2pt solid #e67e22"></span> Eltern</span>'
    +'<span><span class="ld" style="background:#fcf3cf;border-left:2pt solid #f1c40f"></span> Frist</span>'
    +'</div>';

  var HDR='<div class="hdr"><h2>Gesamtschule Horst \u2013 Jahresterminplan</h2>'
    +'<p>Schuljahr 2025/26 \u00b7 Stand: '
    +new Date().toLocaleDateString("de-DE")
    +' \u00b7 Quelle: IServ-Kalender</p></div>';

  var body="";
  for(var i=0;i<ids.length;i++){
    var panel=document.getElementById("gtp-q"+ids[i]);
    if(!panel) continue;
    if(i>0) body+='<div class="pb"></div>';
    body+=HDR;
    var tt=panel.querySelector(".gtp-qt");
    if(tt) body+='<div class="qt">'+tt.textContent+'</div>';
    var tb=panel.querySelector("table");
    if(tb) body+=tb.outerHTML;
    body+=LEG;
  }

  var frame=document.getElementById("gtp-print-frame");
  if(!frame){
    frame=document.createElement("iframe");
    frame.id="gtp-print-frame";
    frame.style.cssText="position:fixed;left:-9999px;top:-9999px;width:0;height:0;border:none";
    document.body.appendChild(frame);
  }

  var doc=frame.contentDocument || frame.contentWindow.document;
  doc.open();
  doc.write([
    '<!DOCTYPE html>','<html lang="de">','<head>',
    '<meta charset="utf-8">','<title>Terminplan Druck</title>',
    '<style>'+CSS+'</style>','</head>','<body>',body,'</body>','</html>'
  ].join(''));
  doc.close();

  var printed=false;
  function doPrint(){
    if(printed) return;
    printed=true;
    try{
      frame.contentWindow.focus();
      frame.contentWindow.print();
    }catch(e){
      var w=window.open("","_blank");
      if(w){
        w.document.open();
        w.document.write(doc.documentElement.outerHTML);
        w.document.close();
        w.focus();
        w.print();
      }
    }
  }
  frame.onload=doPrint;
  setTimeout(doPrint,500);
}
</script>
JSEOF;
}

/* ================================================================
   9. DEAKTIVIERUNG / DEINSTALLATION
   ================================================================ */

/**
 * Deaktivierungs-Hook: Löscht beim Deaktivieren des Plugins nur den Cache.
 *
 * Die Einstellungen (iCal-URL, Quartalsgrenzen usw.) bleiben erhalten,
 * damit sie nach einer erneuten Aktivierung sofort wieder verfügbar sind.
 * Den Cache zu löschen erzwingt beim nächsten Aktivieren einen frischen
 * Datenabruf von IServ.
 *
 * @since 1.2.0
 */
register_deactivation_hook( __FILE__, function () {
    delete_transient( GSH_TP_CACHE_KEY );
} );

/**
 * Deinstallations-Hook: Entfernt alle Plugin-Daten vollständig aus WordPress.
 *
 * Löscht alle gespeicherten Optionen und den Transient-Cache, sodass nach
 * einer Deinstallation keine Überreste in der WordPress-Datenbank verbleiben.
 * Wird nur aufgerufen wenn das Plugin im Backend vollständig gelöscht wird.
 *
 * @since 1.2.0
 * @return void
 */
register_uninstall_hook( __FILE__, 'gsh_tp_uninstall' );
function gsh_tp_uninstall() {
    foreach ( array(
        'gsh_tp_ical_url',
        'gsh_tp_cache_duration',
        'gsh_tp_schuljahr_start',
        'gsh_tp_quartal_grenzen',
        'gsh_tp_kategorie_mapping',
        'gsh_tp_last_sync',
        GSH_TP_BACKUP_KEY,
    ) as $opt ) {
        delete_option( $opt );
    }
    delete_transient( GSH_TP_CACHE_KEY );
}
