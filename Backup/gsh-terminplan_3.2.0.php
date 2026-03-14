<?php
/**
 * Plugin Name: GSH Terminplan Dashboard
 * Plugin URI:  https://gesamtschule-horst.de
 * Description: Interaktive Quartalsuebersicht des Schuljahresterminplans aus dem IServ-Kalender (iCal-Feed).
 * Version:     3.2.0
 * Author:      Gesamtschule Horst
 * License:     GPL v2 or later
 * Text Domain: gsh-terminplan
 *
 * Changelog 3.2.0:
 * - IServ-Kiosk-Modus: Neue Admin-Sektion „IServ-Einbettung (Kiosk-Modus)".
 * - Neue Option gsh_tp_kiosk_token: Geheimer Token für passwortlosen Zugang.
 *   Sanitize via sanitize_text_field(); Button erzeugt per crypto.getRandomValues()
 *   einen 32-stelligen Zufallsstring direkt im Browser.
 * - Neue Option gsh_tp_iserv_domain: IServ-URL für Frame-Protection (esc_url_raw).
 * - Kiosk-URL-Anzeige: Wird automatisch generiert, sobald Token + Seite mit
 *   Template page-terminplan-kiosk.php existieren (get_pages + _wp_page_template).
 *   Direkt daneben ein „Kiosk-Seite testen"-Link (target="_blank").
 * - Warnhinweis wenn Token oder Kiosk-Seite noch fehlt.
 *
 * Changelog 3.1.0:
 * - PDF-Export komplett überarbeitet (gtpPrint()):
 *   A4 Querformat, unterer Seitenrand 16 mm für Footer.
 * - Minimal-Header: Flex-Layout – links "GSH | Gesamtschule Horst" (Logo-Mark +
 *   Trennlinie + Schulname), rechts Titel "Schuljahresterminplan 2025/26" + Quelle.
 * - Typografie: 9 pt für Events, 8 pt fett für Datumsangaben (.gdl).
 * - Feine Tabellenränder: 0.1 pt solid #ddd für body-Zellen.
 * - Farb-Indikator statt farbiger Hintergründe: .ge erhält nur border-left:2.5pt solid
 *   in der Kategoriefarbe; Text durchgehend #1a1a2e auf weißem Hintergrund.
 * - Wochenenden/Feiertage: .gt-hol → background:#f9f9f9 (dezentes Grau).
 * - Legende: Kreispunkte (border-radius:50%) statt Quadrate.
 * - Seitenfußzeile auf jeder PDF-Seite (position:fixed): "Stand: DD.MM.YYYY |
 *   Erstellt über das GSH-Dashboard" mit feiner Trennlinie oben.
 *
 * Changelog 3.0.0:
 * - UI-Modernisierung: CSS Custom Properties (:root) für alle Farben – zentral anpassbar.
 * - Sticky Tabs: Quartal-Tabs kleben per position:sticky beim Scrollen oben.
 * - Event-Cards: .ge mit transition, hover-Lift, box-shadow und border-radius:6px.
 * - Heute-Puls: .gt-today::before animiert als pulsierender Punkt (gtpTodayPulse).
 * - Popup: Overlay mit backdrop-filter:blur(6px); Bottom Sheet auf Mobile mit Handle-Linie.
 * - Search Focus-Mode: Nicht-Treffer werden per .ge-focus-dim gedimmt (opacity:.12, grayscale)
 *   statt versteckt – gtpApplyVisibility() trennt Kategorie-Filter (display:none) von
 *   Suche (Focus-Mode). gtpDoSearch() entfernt .ge-focus-dim beim Reset.
 * - Mobile: iOS-Feed-Stil – Frosted-Glass-Nav, Datum als Kreis, sektionierte Wochenköpfe.
 * - Tablet: .gtp-tabs mit negativem Margin für volle Breite.
 * - Alle Farben der Kategorie-Badges im Popup via CSS-Variablen.
 *
 * Changelog 2.5.0:
 * - Monatsansicht und Ansichts-Umschalter entfernt (Revert 2.4.0).
 * - Drucken-Buttons entfernt – nur PDF-Export verbleibt im Footer.
 * - Performance: gsh_tp_build_date_index() erstellt einmalig einen
 *   Datum → Events-Lookup-Array. gsh_tp_day_events() nutzt diesen
 *   Index (O(1)) statt alle Events pro Tag zu iterieren (O(n)).
 *   Spart bei ~400 Events und 360 Tagen Darstellung ~144.000 Schleifendurchläufe.
 * - CSS-Kommentarblock entfernt, Kalender-CSS und ViewSwitch-CSS entfernt.
 * - JS: gtpViewSwitch(), gtpCalNav(), gtpCalToday() und sessionStorage-Code entfernt.
 *
 * Changelog 2.3.0:
 * - Änderungs-Benachrichtigungen: Beim Cache-Refresh wird ein Snapshot der
 *   Events gespeichert (Transient gsh_tp_events_snapshot) und mit dem
 *   vorherigen Snapshot verglichen (gsh_tp_diff).
 * - Neue Events (added), gelöschte Events (removed) und geänderte Events
 *   (changed: summary, start oder end) werden erkannt und als Transient
 *   gsh_tp_changes (max. 50 Einträge, 7 Tage) gespeichert.
 * - Gelber Banner zeigt Änderungen seit dem letzten Besuch an.
 *   Die letzte Besuchszeit wird pro Browser in localStorage gespeichert.
 * - Erster Besuch: kein Banner, Zeit wird initialisiert.
 * - Geänderte Events werden orange hervorgehoben, neue Events grün
 *   (3× pulsierender Rahmen, dann statisch).
 * - Klick auf "Anzeigen" klappt eine Detail-Liste auf.
 * - Klick auf "×" schließt den Banner und aktualisiert die Besuchszeit.
 * - iCal-Parser liest jetzt das UID-Feld; data-uid wird an .ge-Elementen
 *   ausgegeben, um sie mit den Snapshot-Daten abzugleichen.
 *
 * Changelog 2.2.0:
 * - PDF-Export: Neue Buttons "📄 Quartal als PDF" und "📄 Alle als PDF" im Footer.
 *   Der Browser erzeugt das PDF über den eigenen Druckdialog (kein Server nötig).
 *   Ein Hinweis-Banner erklärt browser-spezifisch (Chrome/Edge/Safari/Firefox),
 *   wie man "Als PDF speichern" wählt. Banner blendet sich nach 12 s automatisch aus.
 * - gtpPrint() erweitert: optionaler Parameter pdfTitle setzt den <title> des
 *   iframe-Dokuments (Browser schlägt ihn als PDF-Dateinamen vor).
 *   Zusätzlich: tr { page-break-inside: avoid } im iframe-CSS.
 * - Footer-Layout überarbeitet: zwei Gruppen "Drucken" und "PDF speichern"
 *   mit beschriftetem Label; PDF-Buttons visuell abgesetzt (blaue Umrandung).
 *
 * Changelog 2.1.0:
 * - Event-Detail-Popup: Klick/Tipp auf einen Termin öffnet ein modales Popup
 *   mit Titel, Datum, Uhrzeit, Ort, Beschreibung und Kategorie.
 *   Auf Desktop: zentriertes Modal; auf Mobile: Bottom-Sheet.
 *   Schließen per ×-Button, Escape-Taste oder Klick außerhalb der Karte.
 *   PHP: Neues `location`-Feld im iCal-Parser; neue Hilfsfunktion
 *   gsh_tp_event_data_attrs() erzeugt data-*-Attribute an .ge-Elementen.
 *   JS: gtpPopupOpen(), gtpPopupClose(), gtpDow(), gtpParseDate().
 *   CSS: Overlay mit Fade-In, Bottom-Sheet auf Mobile, print:none.
 *
 * Changelog 2.0.1:
 * - Bugfix Mobile-Navigation: Pfeil-Buttons (‹/›) und 📍-Button funktionierten
 *   nicht, weil btn.closest(".gtp-mob-weeks") null zurückgab. Die Buttons liegen
 *   in .gtp-mob-nav (Geschwisterelement), nicht in .gtp-mob-weeks.
 *   Fix in gtpMobNav() und gtpMobToday(): erst per closest(".gtp-mob") zum
 *   gemeinsamen Elternelement, dann querySelector(".gtp-mob-weeks") abwärts.
 *
 * Changelog 2.0:
 * - Mobile Agenda-Ansicht (< 768 px): Auf Smartphones wird die Tabelle durch
 *   eine vertikale Wochenliste ersetzt. PHP rendert beide Views; CSS schaltet
 *   per Media Query um – kein JavaScript nötig für den initialen Zustand.
 * - 2-Wochen-Schiebefenster: Immer zwei Wochen sichtbar, Navigation mit ‹/›-
 *   Buttons oder Wisch-Geste (Swipe). Heute-📍-Button springt zur aktuellen Woche.
 * - Leere Tage (ohne Termine) werden in der Agenda nicht gerendert, außer heute.
 * - Vergangene Tage (gtp-mob-past) und Ferientage (gtp-mob-hol) sind
 *   visuell markiert; heute (gtp-mob-today) ist hervorgehoben.
 * - Kategorie-Filter und Textsuche funktionieren automatisch auch auf Mobile,
 *   da dieselben .ge[data-c] Klassen verwendet werden.
 * - Tab-Wechsel setzt die Mobile-Navigation auf die aktuelle Woche zurück.
 * - Druckfunktion: .gtp-mob wird nicht gedruckt (gtpPrint() selektiert table).
 * - Neue PHP-Funktion: gsh_tp_mobile($events, $qd, $sjs).
 * - Neue JS-Funktionen: gtpMobNav(), gtpMobToday(), gtpMobUpdateNav(),
 *   gtpMobNavContainer(), gtpMobResetToToday(), gtpMobInit().
 * - Neue JS: Swipe-Handler (touchstart/touchend, Event-Delegation).
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
 *   1. Direktzugriff-Schutz & Konstanten          (ca. Zeile  200)
 *   2. Admin-Einstellungsseite                     (ca. Zeile  210)
 *   3. iCal abrufen & parsen                       (ca. Zeile  600)
 *   4. Schulwochen & Quartalsgrenzen               (ca. Zeile  860)
 *   5. Kategorie-Erkennung (Farb-Mapping)          (ca. Zeile  930)
 *   6. Shortcode / HTML-Hauptausgabe               (ca. Zeile 1010)
 *   7. Tabellen-Rendering + Date-Index             (ca. Zeile 1230)
 *   8. CSS (inline, alle Klassen)                  (ca. Zeile 1635)
 *   9. JavaScript (Tabs, Filter, Suche, PDF)       (ca. Zeile 2112)
 *  10. Deaktivierung & Deinstallation              (ca. Zeile 3200)
 * ═══════════════════════════════════════════════════════════
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Direktzugriff auf die PHP-Datei blockieren (WordPress-Standard)
}

define( 'GSH_TP_VERSION', '3.2.0' );
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
    register_setting( 'gsh_tp_options', 'gsh_tp_kiosk_token', array(
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ) );
    register_setting( 'gsh_tp_options', 'gsh_tp_iserv_domain', array(
        'sanitize_callback' => 'esc_url_raw',
        'default'           => '',
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

            <hr style="margin:2rem 0 1.5rem" />
            <h2>IServ-Einbettung (Kiosk-Modus)</h2>
            <div style="background:#eaf2f8;border-left:4px solid #2874a6;padding:12px 16px;margin-bottom:16px;border-radius:4px;">
                <strong>Was ist der Kiosk-Modus?</strong><br>
                Eine Ansicht des Terminplans ohne WordPress-Men&uuml; und Passwort.
                Ideal zum Einbetten in IServ als Navigations-Eintrag.
            </div>
            <table class="form-table">

                <tr>
                    <th><label for="gsh_tp_kiosk_token">Kiosk-Token</label></th>
                    <td>
                        <input type="text" id="gsh_tp_kiosk_token" name="gsh_tp_kiosk_token"
                               value="<?php echo esc_attr( get_option( 'gsh_tp_kiosk_token', '' ) ); ?>"
                               class="regular-text" autocomplete="off"
                               placeholder="mind. 20 Zeichen" />
                        <button type="button" class="button" style="margin-left:6px"
                                onclick="document.getElementById('gsh_tp_kiosk_token').value=Array.from(crypto.getRandomValues(new Uint8Array(24)),function(b){return b.toString(36);}).join('').slice(0,32);">
                            &#127922; Zuf&auml;lligen Token erzeugen
                        </button>
                        <p class="description">Geheimer Token f&uuml;r den Zugang zur Kiosk-Seite. Mind. 20 Zeichen empfohlen.</p>
                    </td>
                </tr>

                <tr>
                    <th><label for="gsh_tp_iserv_domain">IServ-Domain</label></th>
                    <td>
                        <input type="url" id="gsh_tp_iserv_domain" name="gsh_tp_iserv_domain"
                               value="<?php echo esc_attr( get_option( 'gsh_tp_iserv_domain', '' ) ); ?>"
                               class="regular-text" placeholder="https://example-school.de" />
                        <p class="description">Die vollst&auml;ndige URL eures IServ-Servers (mit https://). Wird ben&ouml;tigt damit nur euer IServ die Seite einbetten darf.</p>
                    </td>
                </tr>

                <tr>
                    <th>Kiosk-URL</th>
                    <td>
                        <?php
                        $kiosk_token = get_option( 'gsh_tp_kiosk_token', '' );
                        $kiosk_pages = get_pages( array(
                            'meta_key'   => '_wp_page_template',
                            'meta_value' => 'page-terminplan-kiosk.php',
                        ) );
                        $missing = array();
                        if ( empty( $kiosk_token ) ) {
                            $missing[] = 'Kiosk-Token';
                        }
                        if ( empty( $kiosk_pages ) ) {
                            $missing[] = 'Seite mit Vorlage <code>page-terminplan-kiosk.php</code>';
                        }
                        if ( empty( $missing ) ) {
                            $kiosk_url = trailingslashit( get_permalink( $kiosk_pages[0]->ID ) ) . '?token=' . urlencode( $kiosk_token );
                            echo '<code style="display:block;padding:6px 10px;background:#f6f7f7;border:1px solid #ddd;border-radius:3px;font-size:13px;word-break:break-all">'
                               . esc_html( $kiosk_url ) . '</code>';
                            echo '<a href="' . esc_url( $kiosk_url ) . '" target="_blank" rel="noopener" style="display:inline-block;margin-top:6px">'
                               . '&#128279; Kiosk-Seite testen</a>';
                        } else {
                            echo '<p style="color:#888;margin:0">&#9888; Noch nicht verf&uuml;gbar &ndash; folgendes fehlt: '
                               . implode( ', ', $missing ) . '.</p>';
                        }
                        ?>
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

    // Snapshot-Diff: Änderungen erkennen und akkumulieren
    $new_events   = gsh_tp_parse_events( $body );
    $old_snapshot = get_transient( 'gsh_tp_events_snapshot' );
    $new_snapshot = gsh_tp_build_snapshot( $new_events );

    if ( false !== $old_snapshot ) {
        $diff = gsh_tp_diff( $old_snapshot, $new_snapshot );
        if ( $diff['total'] > 0 ) {
            $changes = get_transient( 'gsh_tp_changes' );
            if ( ! is_array( $changes ) ) {
                $changes = array();
            }
            array_unshift( $changes, $diff );
            $changes = array_slice( $changes, 0, 50 );
            set_transient( 'gsh_tp_changes', $changes, 7 * DAY_IN_SECONDS );
        }
    }

    // Snapshot ohne Ablaufzeit speichern (0 = nie ablaufen)
    set_transient( 'gsh_tp_events_snapshot', $new_snapshot, 0 );

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
        'location'   => isset( $props['LOCATION'] )   ? $ue( $props['LOCATION'] )   : '',
        'allday'     => strlen( preg_replace( '/[^0-9]/', '', $props['DTSTART'] ) ) === 8,
        'uid'        => isset( $props['UID'] )         ? trim( $props['UID'] )       : '',
    );
}

/* ================================================================
   2b. ÄNDERUNGS-ERKENNUNG (Snapshot + Diff)
   ================================================================ */

/**
 * Erstellt einen kompakten Snapshot der aktuellen Events für den Diff-Vergleich.
 *
 * Gibt ein assoziatives Array zurück, das Events nach ihrer iCal-UID indiziert.
 * Pro Event werden nur die für den Vergleich relevanten Felder gespeichert
 * (summary, start, end). Events ohne UID werden übersprungen.
 *
 * @since 2.3.0
 * @param  array $events Event-Array aus gsh_tp_parse_events().
 * @return array         Snapshot-Array: uid → ['summary','start','end'].
 */
function gsh_tp_build_snapshot( $events ) {
    $snap = array();
    foreach ( $events as $ev ) {
        if ( empty( $ev['uid'] ) ) {
            continue;
        }
        $snap[ $ev['uid'] ] = array(
            'summary' => $ev['summary'],
            'start'   => $ev['start'],
            'end'     => $ev['end'],
        );
    }
    return $snap;
}

/**
 * Vergleicht zwei Event-Snapshots und gibt ein strukturiertes Diff zurück.
 *
 * Erkennt neu hinzugefügte Events (added), gelöschte Events (removed) und
 * geänderte Events (changed). Bei geänderten Events wird das erste geänderte
 * Feld (summary, start oder end) dokumentiert.
 *
 * @since 2.3.0
 * @param  array $old Alter Snapshot (uid → array).
 * @param  array $new Neuer Snapshot (uid → array).
 * @return array      Diff: added[], removed[], changed{}, total, time.
 */
function gsh_tp_diff( $old, $new ) {
    $added   = array();
    $removed = array();
    $changed = array();

    foreach ( $new as $uid => $ev ) {
        if ( ! isset( $old[ $uid ] ) ) {
            $added[] = $uid;
        } else {
            foreach ( array( 'summary', 'start', 'end' ) as $field ) {
                if ( $old[ $uid ][ $field ] !== $ev[ $field ] ) {
                    $changed[ $uid ] = array(
                        'field'   => $field,
                        'old'     => $old[ $uid ][ $field ],
                        'new'     => $ev[ $field ],
                        'summary' => $ev['summary'],
                    );
                    break;
                }
            }
        }
    }

    foreach ( $old as $uid => $ev ) {
        if ( ! isset( $new[ $uid ] ) ) {
            $removed[] = array(
                'uid'     => $uid,
                'summary' => $ev['summary'],
            );
        }
    }

    return array(
        'added'   => $added,
        'removed' => $removed,
        'changed' => $changed,
        'total'   => count( $added ) + count( $removed ) + count( $changed ),
        'time'    => ( new DateTime( 'now', new DateTimeZone( 'UTC' ) ) )->format( 'c' ),
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

    // Einmaliger Aufbau des Date-Index für O(1)-Lookup statt O(n) pro Tag
    $date_index = gsh_tp_build_date_index( $events );

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
    $changes      = get_transient( 'gsh_tp_changes' );
    $changes_json = esc_attr( wp_json_encode( is_array( $changes ) ? $changes : array() ) );

    $o  = gsh_tp_css();
    $o .= '<div class="gtp" id="gtp" data-changes="' . $changes_json . '">';

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
    $o .= '</div>'; // .gtp-hd

    // Änderungs-Banner (wird per JS befüllt und ggf. eingeblendet)
    $o .= '<div class="gtp-changes" id="gtpChanges" style="display:none">';
    $o .= '<div class="gtp-changes-inner">';
    $o .= '<span class="gtp-changes-icon">&#128276;</span>';
    $o .= '<span class="gtp-changes-text" id="gtpChangesText"></span>';
    $o .= '<button type="button" class="gtp-changes-show" id="gtpChangesShow"'
        . ' onclick="gtpChangesToggle()">Anzeigen</button>';
    $o .= '<button type="button" class="gtp-changes-close"'
        . ' onclick="gtpChangesDismiss()" aria-label="Schlie&szlig;en">&times;</button>';
    $o .= '</div>';
    $o .= '<div class="gtp-changes-list" id="gtpChangesList" style="display:none"></div>';
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
        $o .= gsh_tp_table( $date_index, $qd, $sjs );   // Desktop-Tabelle (≥ 768px)
        $o .= gsh_tp_mobile( $date_index, $qd, $sjs );  // Agenda-Ansicht  (< 768px)
        $o .= '</div>';
    }

    // Footer mit PDF-Buttons
    $o .= '<div class="gtp-ft">';
    $o .= '<button type="button" class="gtp-btn gtp-btn-pdf" onclick="gtpPdf()">&#128196; Quartal als PDF</button>';
    $o .= '<button type="button" class="gtp-btn gtp-btn-pdf" onclick="gtpPdfAll()">&#128196; Alle Quartale als PDF</button>';
    $o .= '<span class="gtp-src">Quelle: IServ-Kalender</span>';
    $o .= '</div>';

    $o .= '<button type="button" id="gtp-heute-btn" onclick="gtpScrollToday()" aria-label="Zur heutigen Woche springen">&#128205; Heute</button>';

    // Event-Detail-Popup – einmalig im DOM, wird per JS befüllt und geöffnet
    $o .= '<div id="gtpPopup" class="gtp-popup-overlay" role="dialog" aria-modal="true" aria-labelledby="gtpPopupTitle" style="display:none" tabindex="-1">';
    $o .= '<div class="gtp-popup-card">';
    $o .= '<button type="button" class="gtp-popup-close" onclick="gtpPopupClose()" aria-label="Popup schließen">&times;</button>';
    $o .= '<div class="gtp-popup-cat" id="gtpPopupCat"></div>';
    $o .= '<h3 class="gtp-popup-title" id="gtpPopupTitle"></h3>';
    $o .= '<div class="gtp-popup-meta" id="gtpPopupDate"></div>';
    $o .= '<div class="gtp-popup-meta" id="gtpPopupTime"></div>';
    $o .= '<div class="gtp-popup-meta" id="gtpPopupLoc"></div>';
    $o .= '<div class="gtp-popup-desc" id="gtpPopupDesc"></div>';
    $o .= '</div>'; // .gtp-popup-card
    $o .= '</div>'; // #gtpPopup

    $o .= '</div>'; // .gtp
    $o .= gsh_tp_js();
    return $o;
}

/* ================================================================
   5b. POPUP-HILFSFUNKTION
   ================================================================ */

/**
 * Gibt alle data-*-Attribute für ein .ge-Element als HTML-String zurück.
 *
 * Extrahiert ggf. eine Zeitangabe aus dem Termintitel im Format
 * „Titel (HH:MM–HH:MM Uhr)", um sie separat im Popup anzuzeigen.
 * data-summary enthält dann den Titel ohne Klammer, data-time die Zeitangabe.
 *
 * @since 2.1.0
 * @param array $ev Event-Array aus gsh_tp_parse_event().
 * @return string HTML-Attribut-String (z. B. data-summary="…" data-date="…" …).
 */
function gsh_tp_event_data_attrs( $ev ) {
    $summary = $ev['summary'];
    $time    = '';

    // Zeitangabe aus Klammern im Titel extrahieren: „Titel (HH:MM–HH:MM Uhr)"
    if ( preg_match( '/^(.*?)\s*\((\d{1,2}:\d{2}(?:\s*[–\-]\s*\d{1,2}:\d{2})?(?:\s*Uhr)?)\)\s*$/', $summary, $m ) ) {
        $summary = trim( $m[1] );
        $time    = trim( $m[2] );
    }

    $desc = $ev['description'];
    $loc  = isset( $ev['location'] ) ? $ev['location'] : '';
    $cat  = gsh_tp_cat( $ev['categories'] );

    $attrs  = ' data-summary="' . esc_attr( $summary ) . '"';
    $attrs .= ' data-date="'    . esc_attr( $ev['start'] ) . '"';
    $attrs .= ' data-end="'     . esc_attr( $ev['end'] ) . '"';
    $attrs .= ' data-time="'    . esc_attr( $time ) . '"';
    $attrs .= ' data-location="' . esc_attr( wp_strip_all_tags( $loc ) ) . '"';
    $attrs .= ' data-desc="'    . esc_attr( wp_strip_all_tags( $desc ) ) . '"';
    $attrs .= ' data-cat="'     . esc_attr( $cat ) . '"';
    $attrs .= ' data-allday="'  . ( $ev['allday'] ? '1' : '0' ) . '"';
    $attrs .= ' data-uid="'     . esc_attr( isset( $ev['uid'] ) ? $ev['uid'] : '' ) . '"';

    return $attrs;
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
 * @since 1.2.0 (optimiert 2.5.0: Date-Index statt Event-Array)
 * @param  array  $index  Date-Index aus gsh_tp_build_date_index().
 * @param  array  $qd     Quartalsgrenzen: array('start'=>'Y-m-d','end'=>'Y-m-d').
 * @param  string $sjs    Erster Montag des Schuljahres (Y-m-d) für Schulwochenberechnung.
 * @return string         HTML-String der kompletten Quartalstabelle.
 */
function gsh_tp_table( $index, $qd, $sjs ) {
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
            foreach ( gsh_tp_day_events( $index, $ds ) as $ev ) {
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
            $de = gsh_tp_day_events( $index, $ds );

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
                $h .= '<div class="ge gc-' . esc_attr( $cc )
                    . '" data-c="' . esc_attr( $cc )
                    . '" onclick="gtpPopupOpen(this)"'
                    . gsh_tp_event_data_attrs( $ev ) . '>'
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
 * Erstellt einen Datum → Events-Index für schnellen Zugriff.
 *
 * Statt für jeden Tag alle Events zu iterieren (O(tage × events)),
 * wird hier einmalig ein Lookup-Array aufgebaut. Jedes Event wird
 * allen Tagen in seinem Zeitraum zugeordnet. DTEND ist bei Ganztags-
 * Terminen iCal-konform exklusiv und wird um einen Tag zurückgesetzt.
 *
 * @since 2.5.0
 * @param  array $events Alle geparsten Events des Schuljahres.
 * @return array         Assoziatives Array: 'Y-m-d' → Event-Array[].
 */
function gsh_tp_build_date_index( $events ) {
    $index = array();
    foreach ( $events as $ev ) {
        $end = $ev['end'];
        if ( $ev['allday'] && $end > $ev['start'] ) {
            $ed = new DateTime( $end );
            $ed->modify( '-1 day' );
            $end = $ed->format( 'Y-m-d' );
        }
        $cur   = new DateTime( $ev['start'] );
        $limit = new DateTime( $end );
        $guard = 400; // Sicherheitsbegrenzung gegen sehr lange Events
        while ( $cur <= $limit && $guard-- > 0 ) {
            $index[ $cur->format( 'Y-m-d' ) ][] = $ev;
            $cur->modify( '+1 day' );
        }
    }
    return $index;
}

/**
 * Gibt alle Events zurück, die an einem bestimmten Tag stattfinden.
 * Schnelle Variante: Lookup im vorberechneten Date-Index (O(1)).
 *
 * @since 1.2.0 (optimiert 2.5.0)
 * @param  array  $index  Date-Index aus gsh_tp_build_date_index().
 * @param  string $date   Datum im Format Y-m-d.
 * @return array          Array der Events die an diesem Tag stattfinden.
 */
function gsh_tp_day_events( $index, $date ) {
    return isset( $index[ $date ] ) ? $index[ $date ] : array();
}

/**
 * Rendert die Mobile-Agenda-Ansicht für ein einzelnes Quartal.
 *
 * Erzeugt eine vertikale Liste von Wochen und Tagen als Alternative zur
 * Desktop-Tabelle (.gt). Auf Desktops ist dieser Block per CSS ausgeblendet
 * (display:none). Auf Mobile (< 768px) wird er eingeblendet, während .gt
 * per `display:none !important` verschwindet. Beide Views werden immer
 * gerendert – der Wechsel passiert per CSS Media Query, kein JS nötig.
 *
 * Leere Tage (keine kurzen Termine) werden nicht gerendert, um Platz zu
 * sparen – außer dem heutigen Tag, der immer erscheint. Lange Termine
 * (≥ 5 Tage, z. B. Ferien) erscheinen in der Agenda nicht einzeln, ihr
 * Einfluss auf den Ferientag-Status wird aber berücksichtigt.
 *
 * @since 2.0.0 (optimiert 2.5.0: Date-Index statt Event-Array)
 * @param  array  $index  Date-Index aus gsh_tp_build_date_index().
 * @param  array  $qd     Quartalsgrenzen: array('start'=>'Y-m-d','end'=>'Y-m-d').
 * @param  string $sjs    Erster Montag des Schuljahres (Y-m-d).
 * @return string         HTML-String der Agenda-Ansicht.
 */
function gsh_tp_mobile( $index, $qd, $sjs ) {
    $qs = new DateTime( $qd['start'] );
    $qe = new DateTime( $qd['end'] );
    $td = gmdate( 'Y-m-d' ); // Heutiges Datum (UTC, wie überall im Plugin)

    // Zur letzten Montag-Position zurückgehen (identisch zu gsh_tp_table)
    $dow = (int) $qs->format( 'N' );
    if ( $dow > 1 ) {
        $qs->modify( '-' . ( $dow - 1 ) . ' days' );
    }

    // Deutsche Wochentag-Kürzel für die Datumsspalte (Index 0=Mo … 4=Fr)
    $dow_labels = array( 'Mo', 'Di', 'Mi', 'Do', 'Fr' );

    // ── Alle Wochen in ein Array einlesen ──
    // Wir brauchen die Gesamtzahl (data-total) für die JS-Navigation und den
    // Index der "heute"-Woche (data-today-idx) für den 📍-Button.
    $weeks          = array();
    $today_week_idx = 0;
    $c              = clone $qs;
    $lim            = 50; // Sicherheitsbegrenzung gegen Endlosschleifen

    while ( $c <= $qe && $lim-- > 0 ) {
        $monday = $c->format( 'Y-m-d' );
        $friday = ( clone $c )->modify( '+4 days' )->format( 'Y-m-d' );
        // Prüfen ob der heutige Tag in dieser Woche liegt
        if ( $td >= $monday && $td <= $friday ) {
            $today_week_idx = count( $weeks );
        }
        $weeks[] = array(
            'monday' => $monday,
            'friday' => $friday,
            'sw'     => gsh_tp_schulwoche( $monday, $sjs ),
        );
        $c->modify( '+7 days' );
    }

    $total = count( $weeks );
    // Startwoche so wählen, dass "heute" im 2-Wochen-Fenster liegt.
    // Ist heute nicht im Quartal, zeigen wir die ersten zwei Wochen.
    $start = min( $today_week_idx, max( 0, $total - 2 ) );

    $h  = '<div class="gtp-mob">';

    // ── Sticky Navigationsleiste ──
    $h .= '<div class="gtp-mob-nav">';
    $h .= '<button type="button" class="gtp-mob-prev" onclick="gtpMobNav(this,-1)"'
        . ( 0 === $start ? ' disabled' : '' ) . '>&#8249;</button>';
    $h .= '<div class="gtp-mob-nav-info">'
        . '<div class="gtp-mob-nav-sw"></div>'   // wird von JS befüllt
        . '<div class="gtp-mob-nav-dates"></div>' // wird von JS befüllt
        . '</div>';
    // 📍-Button springt zur aktuellen Woche zurück
    $h .= '<button type="button" class="gtp-mob-today-btn" onclick="gtpMobToday(this)"'
        . ' aria-label="Zur heutigen Woche springen">&#128205;</button>';
    $h .= '<button type="button" class="gtp-mob-next" onclick="gtpMobNav(this,1)"'
        . ( ( $start + 2 ) >= $total ? ' disabled' : '' ) . '>&#8250;</button>';
    $h .= '</div>';

    // ── Wochen-Container ──
    // data-total     = Gesamtzahl Wochen im Quartal
    // data-visible   = Anzahl gleichzeitig sichtbarer Wochen (immer 2)
    // data-start     = Index der ersten sichtbaren Woche (PHP-Initialwert)
    // data-today-idx = Wochenindex der Woche mit "heute" (für 📍-Button)
    $h .= '<div class="gtp-mob-weeks"'
        . ' data-total="' . $total . '"'
        . ' data-visible="2"'
        . ' data-start="' . $start . '"'
        . ' data-today-idx="' . $today_week_idx . '">';

    foreach ( $weeks as $wi => $week ) {
        $monday_dt = new DateTime( $week['monday'] );
        $friday_dt = new DateTime( $week['friday'] );
        $sw        = $week['sw'];

        // PHP setzt die initiale Sichtbarkeit entsprechend dem Startfenster.
        // JS übernimmt ab dann die Navigation.
        $week_vis = ( $wi >= $start && $wi < $start + 2 ) ? '' : ' style="display:none"';

        $h .= '<div class="gtp-mob-week" data-wi="' . $wi . '"' . $week_vis . '>';

        // Wochenkopf: sticky, klebt unter der Navigationsleiste
        $h .= '<div class="gtp-mob-wh">';
        $h .= '<span>' . ( $sw > 0 ? 'Schulwoche&nbsp;' . sprintf( '%02d', $sw ) : '&ndash;' ) . '</span>';
        $h .= '<span class="gtp-mob-wh-sub">'
            . esc_html( $monday_dt->format( 'd.m.' ) )
            . ' &ndash; '
            . esc_html( $friday_dt->format( 'd.m.' ) )
            . '</span>';
        $h .= '</div>';

        // ── Tage Montag–Freitag ──
        for ( $d = 0; $d < 5; $d++ ) {
            $dy = clone $monday_dt;
            if ( $d > 0 ) {
                $dy->modify( "+{$d} days" );
            }
            $ds = $dy->format( 'Y-m-d' );
            $de = gsh_tp_day_events( $index, $ds );

            $is_today = ( $ds === $td );
            $is_past  = ( $ds < $td );

            // Ferientag erkennen (inklusive langer Ferien-Termine)
            $is_hol = false;
            foreach ( $de as $ev ) {
                if ( gsh_tp_cat( $ev['categories'] ) === 'frei' ) {
                    $is_hol = true;
                    break;
                }
            }

            // Lange Termine (≥ 5 Tage) erscheinen in der Agenda nicht –
            // sie stehen in der Desktop-Tabelle in der Hinweise-Spalte.
            $short_events = array_filter( $de, function ( $ev ) {
                return gsh_tp_event_duration( $ev ) < 5;
            } );

            // Leere Tage überspringen – außer heute (muss immer sichtbar sein)
            if ( empty( $short_events ) && ! $is_today ) {
                continue;
            }

            // CSS-Klassen für den Tag-Container
            $day_cls = 'gtp-mob-day';
            if ( $is_today ) { $day_cls .= ' gtp-mob-today'; }
            if ( $is_past )  { $day_cls .= ' gtp-mob-past'; }
            if ( $is_hol )   { $day_cls .= ' gtp-mob-hol'; }

            $h .= '<div class="' . esc_attr( $day_cls ) . '">';

            // Datumsspalte (links): Wochentag, Datum, heute-Badge
            $h .= '<div class="gtp-mob-date">';
            $h .= '<div class="gtp-mob-dow">' . esc_html( $dow_labels[ $d ] ) . '</div>';
            $h .= '<div class="gtp-mob-dm">' . esc_html( $dy->format( 'd.m.' ) ) . '</div>';
            if ( $is_today ) {
                $h .= '<div class="gtp-mob-badge">HEUTE</div>';
            }
            $h .= '</div>'; // .gtp-mob-date

            // Events-Spalte (rechts): gleiche .ge + .gc-* Klassen wie Desktop
            // → Kategorie-Filter (data-c) und Textsuche (.ge) funktionieren automatisch
            $h .= '<div class="gtp-mob-events">';
            foreach ( $short_events as $ev ) {
                $cc = gsh_tp_cat( $ev['categories'] );
                $h .= '<div class="ge gc-' . esc_attr( $cc )
                    . '" data-c="' . esc_attr( $cc )
                    . '" onclick="gtpPopupOpen(this)"'
                    . gsh_tp_event_data_attrs( $ev ) . '>'
                    . esc_html( $ev['summary'] ) . '</div>';
            }
            $h .= '</div>'; // .gtp-mob-events

            $h .= '</div>'; // .gtp-mob-day
        }

        $h .= '</div>'; // .gtp-mob-week
    }

    $h .= '</div>'; // .gtp-mob-weeks
    $h .= '</div>'; // .gtp-mob
    return $h;
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
/* ══════════════════════════════════════════════════════════════
   CSS Custom Properties – alle Farben zentral anpassbar
   ══════════════════════════════════════════════════════════════ */
:root{
  /* Primär / Akzent */
  --gtp-accent:       #2563eb;
  --gtp-accent-dark:  #1d4ed8;
  --gtp-accent-light: #eff6ff;
  /* Oberflächen & Text */
  --gtp-text:         #1e293b;
  --gtp-text-muted:   #64748b;
  --gtp-text-faint:   #94a3b8;
  --gtp-bg:           #ffffff;
  --gtp-surface:      #f8fafc;
  --gtp-border:       #e2e8f0;
  /* Heute-Akzent */
  --gtp-today-bg:     #eff6ff;
  --gtp-today-bd:     #2563eb;
  /* Übergänge */
  --gtp-tr:           all 0.3s ease;
  --gtp-tr-fast:      all 0.15s ease;
  /* Kategorie – Konferenz (Blau/Pastell) */
  --c-kf-bg:#dbeafe; --c-kf-bd:#3b82f6; --c-kf-tx:#1e40af;
  /* Kategorie – Prüfung (Rot/Pastell) */
  --c-pf-bg:#fee2e2; --c-pf-bd:#ef4444; --c-pf-tx:#991b1b;
  /* Kategorie – Projekt (Grün/Pastell) */
  --c-pr-bg:#dcfce7; --c-pr-bd:#22c55e; --c-pr-tx:#166534;
  /* Kategorie – Frei (Grau/Pastell) */
  --c-fr-bg:#f1f5f9; --c-fr-bd:#94a3b8; --c-fr-tx:#475569;
  /* Kategorie – Eltern (Orange/Pastell) */
  --c-el-bg:#ffedd5; --c-el-bd:#f97316; --c-el-tx:#9a3412;
  /* Kategorie – Frist (Gelb/Pastell) */
  --c-fs-bg:#fef9c3; --c-fs-bd:#eab308; --c-fs-tx:#713f12;
  /* Kategorie – Standard (Neutral/Pastell) */
  --c-st-bg:#f1f5f9; --c-st-bd:#64748b; --c-st-tx:#334155;
}

/* ── Container ── */
.gtp{
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
  max-width:1200px;margin:0 auto;color:var(--gtp-text);
  background:var(--gtp-bg);border-radius:16px;
  box-shadow:0 4px 24px rgba(0,0,0,.08),0 1px 4px rgba(0,0,0,.04);
  padding:1.75rem 2rem;
}

/* ── Header ── */
.gtp-hd{
  display:flex;justify-content:space-between;align-items:center;
  flex-wrap:wrap;margin-bottom:1.5rem;padding-bottom:1.25rem;
  border-bottom:2px solid var(--gtp-border);gap:.75rem;
}
.gtp-hd-left{display:flex;flex-direction:column;gap:.25rem}
.gtp-t{font-size:1.6rem;font-weight:800;color:var(--gtp-text);margin:0;letter-spacing:-.03em;line-height:1.15}
.gtp-subtitle{font-size:.8rem;color:var(--gtp-text-muted);font-weight:400;letter-spacing:.01em}
.gtp-meta{font-size:.72rem;color:var(--gtp-text-faint);white-space:nowrap;font-variant-numeric:tabular-nums}

/* ── Tabs (sticky beim Scrollen) ── */
.gtp-tabs{
  display:flex;gap:0;
  position:sticky;top:0;z-index:20;
  background:var(--gtp-bg);
  border-bottom:2px solid var(--gtp-border);
  margin:0 -2rem;padding:0 2rem;
  box-shadow:0 2px 8px rgba(0,0,0,.05);
}
.gtp-tab{
  padding:.6rem 1.5rem;
  border:none;border-bottom:3px solid transparent;
  background:transparent;
  color:var(--gtp-text-muted);font-weight:600;font-size:.88rem;
  cursor:pointer;transition:var(--gtp-tr);
  margin-bottom:-2px;letter-spacing:.01em;
}
.gtp-tab:hover{color:var(--gtp-accent);background:var(--gtp-accent-light)}
.gtp-tab-on{color:var(--gtp-accent);border-bottom-color:var(--gtp-accent)}
.admin-bar .gtp-tabs{top:32px}
@media screen and (max-width:782px){.admin-bar .gtp-tabs{top:46px}}

/* ── Filter ── */
.gtp-filt-wrap{
  margin:1.25rem 0;padding:.875rem 1rem;
  background:var(--gtp-surface);border-radius:12px;
  border:1px solid var(--gtp-border);
}
.gtp-filt-lbl{
  font-size:.68rem;font-weight:700;color:var(--gtp-text-muted);
  text-transform:uppercase;letter-spacing:.1em;
  display:block;margin-bottom:.6rem;
}
.gtp-filt{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
.gtp-fb{
  padding:5px 14px;border:1.5px solid;border-radius:20px;
  font-size:.75rem;cursor:pointer;font-weight:600;line-height:1.5;
  transition:var(--gtp-tr);
}
.gtp-fb[data-c="konferenz"]{border-color:var(--c-kf-bd);background:var(--c-kf-bg);color:var(--c-kf-tx)}
.gtp-fb[data-c="pruefung"] {border-color:var(--c-pf-bd);background:var(--c-pf-bg);color:var(--c-pf-tx)}
.gtp-fb[data-c="projekt"]  {border-color:var(--c-pr-bd);background:var(--c-pr-bg);color:var(--c-pr-tx)}
.gtp-fb[data-c="frei"]     {border-color:var(--c-fr-bd);background:var(--c-fr-bg);color:var(--c-fr-tx)}
.gtp-fb[data-c="eltern"]   {border-color:var(--c-el-bd);background:var(--c-el-bg);color:var(--c-el-tx)}
.gtp-fb[data-c="frist"]    {border-color:var(--c-fs-bd);background:var(--c-fs-bg);color:var(--c-fs-tx)}
.gtp-fb[data-c="standard"] {border-color:var(--c-st-bd);background:var(--c-st-bg);color:var(--c-st-tx)}
.gtp-fb:hover{filter:brightness(.9);transform:translateY(-1px);box-shadow:0 2px 6px rgba(0,0,0,.1)}
.gtp-fb-off{opacity:.25;filter:grayscale(.7)}

/* Reset-Button */
.gtp-reset{
  padding:5px 14px;border:1.5px solid var(--gtp-border);border-radius:20px;
  font-size:.75rem;cursor:pointer;font-weight:600;line-height:1.5;
  background:var(--gtp-bg);color:var(--gtp-text-muted);
  transition:var(--gtp-tr);margin-left:4px;
}
.gtp-reset:hover{background:var(--gtp-surface);border-color:var(--gtp-text-muted);color:var(--gtp-text)}

/* ── Quartal-Überschrift ── */
.gtp-qt{
  font-size:.85rem;font-weight:700;color:var(--gtp-text-muted);
  margin:1.25rem 0 .75rem;padding-left:2px;
  text-transform:uppercase;letter-spacing:.07em;
}

/* ── Tabelle ── */
.gt{width:100%;border-collapse:separate;border-spacing:0;font-size:.82rem;table-layout:fixed}
.gt thead th{
  background:var(--gtp-text);color:#fff;padding:9px 6px;
  text-align:center;font-weight:600;font-size:.74rem;
  border-right:1px solid rgba(255,255,255,.1);letter-spacing:.04em;
  position:sticky;top:44px;z-index:10;
}
.gt thead th:last-child{border-right:none}
.admin-bar .gt thead th{top:76px}
@media screen and (max-width:782px){.admin-bar .gt thead th{top:90px}}
/* ── Vergangene Wochen ── */
.gtp-past{opacity:.4;transition:var(--gtp-tr)}
.gtp-past:hover{opacity:.85}
.gtp-past .ge{filter:grayscale(50%)}
.gtp-past:hover .ge{filter:none}
.gt tbody tr:hover td{background:rgba(37,99,235,.02)}
.gt tbody td{
  border-right:1px solid var(--gtp-border);
  border-bottom:1px solid var(--gtp-border);
  padding:5px 6px;vertical-align:top;
  transition:background var(--gtp-tr-fast);
}
.gt tbody td:first-child{border-left:1px solid var(--gtp-border)}
.gt tbody tr:first-child td{border-top:1px solid var(--gtp-border)}
.gs{width:42px;text-align:center;background:var(--gtp-surface);font-size:.8rem;font-weight:700;color:var(--gtp-text-muted)}
.gh{width:140px;background:#fefdf8}
.gdl{display:block;font-size:.65rem;color:var(--gtp-text-faint);margin-bottom:3px;letter-spacing:.01em;font-variant-numeric:tabular-nums}
.gnc{font-size:.72rem;color:var(--gtp-text-muted)}

/* ── Event-Cards (Tagesspalten) ── */
.ge{
  padding:3px 7px;margin:2px 0;border-radius:6px;
  font-size:.73rem;line-height:1.4;
  border-left:3px solid transparent;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
  cursor:pointer;
  transition:var(--gtp-tr);
  box-shadow:0 1px 2px rgba(0,0,0,.05);
}
.ge:hover{
  transform:translateY(-1px);
  box-shadow:0 3px 8px rgba(0,0,0,.12);
  filter:brightness(.94);
}
.gc-konferenz{background:var(--c-kf-bg);border-left-color:var(--c-kf-bd);color:var(--c-kf-tx)}
.gc-pruefung {background:var(--c-pf-bg);border-left-color:var(--c-pf-bd);color:var(--c-pf-tx)}
.gc-projekt  {background:var(--c-pr-bg);border-left-color:var(--c-pr-bd);color:var(--c-pr-tx)}
.gc-frei     {background:var(--c-fr-bg);border-left-color:var(--c-fr-bd);color:var(--c-fr-tx)}
.gc-eltern   {background:var(--c-el-bg);border-left-color:var(--c-el-bd);color:var(--c-el-tx)}
.gc-frist    {background:var(--c-fs-bg);border-left-color:var(--c-fs-bd);color:var(--c-fs-tx)}
.gc-standard {background:var(--c-st-bg);border-left-color:var(--c-st-bd);color:var(--c-st-tx)}

/* ── Lange Termine in der Hinweise-Spalte ── */
.gn-long{
  padding:3px 6px;margin:2px 0;border-radius:5px;
  font-size:.72rem;line-height:1.4;
  border-left:3px solid transparent;
  font-weight:500;transition:var(--gtp-tr);
}
.gn-range{
  display:block;font-size:.63rem;opacity:.65;font-style:normal;
  margin-bottom:1px;font-variant-numeric:tabular-nums;
}

/* ── Frist-Notizen in der Hinweise-Spalte ── */
.gn{
  padding:3px 6px;margin:2px 0;
  background:var(--c-fs-bg);border-radius:5px;
  font-style:italic;font-size:.71rem;color:var(--c-fs-tx);
}

/* ── Heute – Akzent + Puls-Animation ── */
.gt-today{
  background:var(--gtp-today-bg)!important;
  box-shadow:inset 0 0 0 2px var(--gtp-today-bd);
  position:relative;
}
.gt-today .gdl{color:var(--gtp-today-bd);font-weight:700}
.gt-today::before{
  content:"";display:block;
  width:6px;height:6px;border-radius:50%;
  background:var(--gtp-today-bd);
  margin:0 auto 3px;
  animation:gtpTodayPulse 2s ease-in-out infinite;
}
@keyframes gtpTodayPulse{
  0%,100%{transform:scale(1);box-shadow:0 0 0 0 rgba(37,99,235,.45)}
  50%{transform:scale(1.5);box-shadow:0 0 0 6px rgba(37,99,235,0)}
}
.gt-hol{background:#f8f9fb!important}

/* ── Search Focus-Mode: Nicht-Treffer abdimmen ── */
.ge-focus-dim{
  opacity:.12!important;
  filter:grayscale(1)!important;
  pointer-events:none;
  transition:var(--gtp-tr);
}

/* ── Footer ── */
.gtp-ft{
  display:flex;flex-wrap:wrap;gap:10px;
  justify-content:center;align-items:center;
  margin-top:1.5rem;padding-top:1rem;
  border-top:1px solid var(--gtp-border);
}
.gtp-btn{
  padding:8px 16px;background:var(--gtp-text);color:#fff;
  border:none;border-radius:8px;
  cursor:pointer;font-size:.8rem;font-weight:600;
  transition:var(--gtp-tr);white-space:nowrap;
  box-shadow:0 1px 3px rgba(0,0,0,.15);
}
.gtp-btn:hover{background:#0f172a;transform:translateY(-1px);box-shadow:0 3px 8px rgba(0,0,0,.2)}
.gtp-btn-pdf{background:var(--gtp-bg);border:1.5px solid var(--gtp-accent);color:var(--gtp-accent);box-shadow:none}
.gtp-btn-pdf:hover{background:var(--gtp-accent-light);transform:translateY(-1px);box-shadow:0 3px 8px rgba(37,99,235,.15)}
.gtp-src{font-size:.7rem;color:var(--gtp-text-faint)}
/* PDF-Hinweis-Banner */
.gtp-pdf-hint{
  position:fixed;top:0;left:0;right:0;background:var(--gtp-text);color:#fff;
  padding:12px 20px;font-size:.82rem;text-align:center;z-index:10000;
  animation:gtpSlideDown .3s ease-out;
  display:flex;align-items:center;justify-content:center;gap:12px;
}
@keyframes gtpSlideDown{from{transform:translateY(-100%)}to{transform:translateY(0)}}
.gtp-pdf-hint-close{
  background:rgba(255,255,255,.15);border:none;color:#fff;
  border-radius:50%;width:24px;height:24px;cursor:pointer;
  font-size:14px;line-height:1;flex-shrink:0;transition:var(--gtp-tr-fast);
}
.gtp-pdf-hint-close:hover{background:rgba(255,255,255,.3)}

/* ── Druck-iframe ── */
#gtp-print-frame{position:fixed;left:-9999px;top:-9999px;width:0;height:0;border:none}

/* ── Floating Heute-Button ── */
#gtp-heute-btn{
  position:fixed;bottom:28px;right:28px;z-index:9999;
  padding:11px 22px;
  background:var(--gtp-accent);color:#fff;border:none;
  border-radius:50px;
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
  font-size:.88rem;font-weight:700;letter-spacing:.01em;
  box-shadow:0 4px 20px rgba(37,99,235,.35);
  cursor:pointer;
  opacity:0;pointer-events:none;
  transition:opacity .3s ease,transform .3s ease,box-shadow .3s ease;
  transform:translateY(10px);
}
#gtp-heute-btn.gtp-heute-vis{opacity:1;pointer-events:auto;transform:translateY(0)}
#gtp-heute-btn:hover{background:var(--gtp-accent-dark);box-shadow:0 6px 24px rgba(37,99,235,.45);transform:translateY(-2px)}

/* ── Suchfeld ── */
.gtp-search{
  display:flex;flex-direction:column;gap:4px;
  flex:1;max-width:300px;
}
.gtp-search-input{
  padding:8px 16px;
  border:1.5px solid var(--gtp-border);border-radius:24px;
  font-size:.84rem;color:var(--gtp-text);background:var(--gtp-surface);
  outline:none;width:100%;box-sizing:border-box;
  transition:var(--gtp-tr);
}
.gtp-search-input:focus{
  border-color:var(--gtp-accent);background:var(--gtp-bg);
  box-shadow:0 0 0 3px rgba(37,99,235,.12);
}
.gtp-search-results{
  display:flex;flex-wrap:wrap;gap:4px;align-items:center;
  font-size:.73rem;padding:0 4px;min-height:18px;
}
.gtp-sr-count{color:var(--gtp-text-muted);font-weight:600}
.gtp-sr-none{color:#ef4444;font-weight:600}
.gtp-sr-q{
  display:inline-block;padding:1px 9px;
  background:var(--gtp-accent-light);color:var(--gtp-accent);border-radius:10px;
  text-decoration:none;font-weight:700;font-size:.71rem;
  transition:var(--gtp-tr-fast);
}
.gtp-sr-q:hover{background:var(--gtp-accent);color:#fff}

/* ── Treffer-Highlight (Textsuche) ── */
.ge-hit{
  outline:2px solid #f59e0b!important;
  box-shadow:0 0 0 3px rgba(245,158,11,.2)!important;
  z-index:1;position:relative;
}

/* ── Mobile Agenda: auf Desktop versteckt ── */
.gtp-mob{display:none}

/* ════════════════════════════════════════════════════════════════
   MOBILE AGENDA – iOS-Feed-Stil (< 768px)
   ════════════════════════════════════════════════════════════════ */
@media(max-width:767px){

  .gt{display:none!important}
  .gtp-mob{display:block}

  .gtp{padding:.875rem 1rem;border-radius:12px}
  .gtp-hd{flex-direction:column;gap:6px;padding-bottom:1rem}
  .gtp-t{font-size:1.3rem}
  .gtp-filt-wrap{padding:.625rem .875rem}
  .gtp-filt{overflow-x:auto;flex-wrap:nowrap;gap:5px;padding-bottom:4px;scrollbar-width:none;-ms-overflow-style:none}
  .gtp-filt::-webkit-scrollbar{display:none}
  .gtp-fb{white-space:nowrap;font-size:.72rem;padding:4px 10px}
  .gtp-tabs{margin:0 -1rem;padding:0 1rem;overflow-x:auto;flex-wrap:nowrap;scrollbar-width:none;-ms-overflow-style:none}
  .gtp-tabs::-webkit-scrollbar{display:none}
  .gtp-tab{padding:.5rem 1rem;font-size:.82rem;white-space:nowrap;flex-shrink:0}
  .gtp-search{max-width:100%;width:100%}
  .gtp-search-input{width:100%}
  .gtp-ft{flex-direction:column;gap:8px;text-align:center}

  /* ── Navigationsleiste mit Frosted-Glass ── */
  .gtp-mob-nav{
    display:flex;align-items:center;
    position:sticky;top:44px;z-index:15;
    background:rgba(255,255,255,.88);
    backdrop-filter:saturate(180%) blur(12px);
    -webkit-backdrop-filter:saturate(180%) blur(12px);
    border-bottom:1px solid var(--gtp-border);
    padding:0;height:48px;
  }
  .admin-bar .gtp-mob-nav{top:76px}

  /* Vor/Zurück-Buttons */
  .gtp-mob-prev,.gtp-mob-next{
    flex:0 0 48px;height:48px;
    border:none;background:transparent;
    font-size:22px;color:var(--gtp-accent);cursor:pointer;
    touch-action:manipulation;transition:var(--gtp-tr-fast);
  }
  .gtp-mob-prev:disabled,.gtp-mob-next:disabled{color:var(--gtp-border);cursor:default}
  .gtp-mob-prev:not(:disabled):active,.gtp-mob-next:not(:disabled):active{background:var(--gtp-accent-light);border-radius:8px}

  /* Mitte: Schulwoche + Datumsbereich */
  .gtp-mob-nav-info{flex:1;text-align:center;line-height:1.3;overflow:hidden;padding:0 4px}
  .gtp-mob-nav-sw{font-size:.87rem;font-weight:700;color:var(--gtp-text)}
  .gtp-mob-nav-dates{font-size:.67rem;color:var(--gtp-text-muted)}

  /* 📍 Heute-Button */
  .gtp-mob-today-btn{
    padding:5px 12px;background:var(--gtp-accent-light);
    border:1.5px solid var(--gtp-accent);border-radius:16px;
    font-size:.84rem;cursor:pointer;
    touch-action:manipulation;margin-right:4px;
    color:var(--gtp-accent);font-weight:600;
    transition:var(--gtp-tr-fast);
  }
  .gtp-mob-today-btn:active{background:var(--gtp-accent);color:#fff}

  /* ── Wochenköpfe: iOS-Sektions-Header ── */
  .gtp-mob-wh{
    position:sticky;top:92px;z-index:10;
    display:flex;justify-content:space-between;align-items:center;
    background:rgba(248,250,252,.95);
    backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
    border-bottom:1px solid var(--gtp-border);
    padding:6px 0;
    font-size:.75rem;font-weight:700;color:var(--gtp-text-muted);
    text-transform:uppercase;letter-spacing:.07em;
    margin:12px 0 0;
  }
  .admin-bar .gtp-mob-wh{top:124px}
  .gtp-mob-wh-sub{font-weight:400;color:var(--gtp-text-faint);font-size:.7rem;text-transform:none;letter-spacing:0}

  /* ── Tag-Zeile (Feed-Karte) ── */
  .gtp-mob-day{
    display:flex;gap:12px;
    padding:10px 0;
    border-bottom:1px solid var(--gtp-border);
  }
  .gtp-mob-day:last-child{border-bottom:none}
  /* Heutiger Tag: prominenter Akzent */
  .gtp-mob-day.gtp-mob-today{
    background:var(--gtp-today-bg);
    border-radius:10px;
    padding:10px 8px 10px 12px;
    margin:0 -8px;
    border:none;
    border-left:3px solid var(--gtp-today-bd);
  }
  .gtp-mob-day.gtp-mob-hol{opacity:.7}
  .gtp-mob-day.gtp-mob-past{opacity:.45}

  /* ── Datumsspalte (iOS-Stil: Wochentag + Kreis-Zahl) ── */
  .gtp-mob-date{flex:0 0 44px;text-align:center;padding-top:2px}
  .gtp-mob-dow{
    font-size:.65rem;font-weight:600;color:var(--gtp-text-muted);
    text-transform:uppercase;letter-spacing:.06em;
    line-height:1;margin-bottom:4px;
  }
  .gtp-mob-dm{
    font-size:1.25rem;font-weight:700;color:var(--gtp-text);
    line-height:1;font-variant-numeric:tabular-nums;
  }
  .gtp-mob-today .gtp-mob-dow{color:var(--gtp-today-bd)}
  .gtp-mob-today .gtp-mob-dm{
    background:var(--gtp-today-bd);color:#fff;
    border-radius:50%;width:32px;height:32px;
    display:flex;align-items:center;justify-content:center;
    margin:2px auto 0;font-size:1rem;
  }
  .gtp-mob-hol .gtp-mob-dow,.gtp-mob-hol .gtp-mob-dm{color:var(--gtp-text-faint)}
  /* „HEUTE"-Badge */
  .gtp-mob-badge{
    font-size:.52rem;background:var(--gtp-today-bd);color:#fff;
    border-radius:4px;padding:1px 4px;margin-top:4px;
    font-weight:700;display:block;letter-spacing:.04em;text-align:center;
  }

  /* ── Events-Spalte (rechts) ── */
  .gtp-mob-events{flex:1;min-width:0;display:flex;flex-direction:column;gap:3px}
  .gtp-mob .ge{
    font-size:.82rem;padding:6px 10px;
    white-space:normal;overflow:visible;text-overflow:clip;
    border-radius:8px;border-left-width:4px;
    box-shadow:0 1px 3px rgba(0,0,0,.06);
  }

  /* ── Floating Heute-Button ── */
  #gtp-heute-btn{bottom:20px;right:20px;font-size:.84rem;padding:10px 18px}
}

/* Responsive für Tablets */
@media(min-width:768px) and (max-width:1024px){
  .gtp{padding:1.25rem;border-radius:12px}
  .gt{display:block;overflow-x:auto}
  .gtp-tabs{margin:0 -1.25rem;padding:0 1.25rem;overflow-x:auto;flex-wrap:nowrap}
  .gtp-tab{white-space:nowrap;flex-shrink:0}
  .gtp-hd{flex-direction:column;align-items:flex-start}
  .gtp-filt-wrap{padding:.625rem .875rem}
  .gtp-search{max-width:100%;width:100%}
  .gtp-search-input{width:100%}
}

/* --- Änderungs-Banner --- */
.gtp-changes{background:var(--c-fs-bg);border:1px solid var(--c-fs-bd);border-radius:10px;margin-bottom:14px;overflow:hidden}
.gtp-changes-inner{display:flex;align-items:center;gap:10px;padding:10px 16px}
.gtp-changes-icon{font-size:1.2rem;flex:0 0 auto}
.gtp-changes-text{flex:1;font-size:.84rem;color:var(--c-fs-tx);font-weight:600}
.gtp-changes-show{
  padding:4px 12px;background:var(--c-fs-bg);border:1.5px solid var(--c-fs-bd);border-radius:6px;
  font-size:.74rem;font-weight:600;color:var(--c-fs-tx);cursor:pointer;white-space:nowrap;
  transition:var(--gtp-tr-fast);
}
.gtp-changes-show:hover{filter:brightness(.9)}
.gtp-changes-close{background:none;border:none;color:var(--c-fs-bd);font-size:18px;cursor:pointer;padding:0 4px;transition:var(--gtp-tr-fast)}
.gtp-changes-close:hover{opacity:.7}
.gtp-changes-list{padding:0 16px 10px;font-size:.8rem;color:var(--gtp-text-muted)}
.gtp-changes-item{
  display:flex;align-items:flex-start;gap:8px;padding:5px 0;
  border-bottom:1px solid rgba(0,0,0,.06);
}
.gtp-changes-item:last-child{border-bottom:none}
.gtp-changes-badge{
  flex:0 0 auto;padding:1px 7px;border-radius:4px;
  font-size:.64rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;
}
.gtp-changes-badge-new{background:var(--c-pr-bg);color:var(--c-pr-tx)}
.gtp-changes-badge-del{background:var(--c-pf-bg);color:var(--c-pf-tx)}
.gtp-changes-badge-mod{background:var(--c-kf-bg);color:var(--c-kf-tx)}
/* Hervorhebung geänderter Events */
.ge-changed{box-shadow:0 0 0 2px #f59e0b,0 0 8px rgba(245,158,11,.25)!important;position:relative}
.ge-changed::after{content:"\25CF";position:absolute;top:-4px;right:-4px;font-size:8px;color:#f59e0b;line-height:1}
.ge-new{box-shadow:0 0 0 2px #22c55e,0 0 8px rgba(34,197,94,.25)!important;position:relative}
.ge-new::after{content:"\2605";position:absolute;top:-4px;right:-4px;font-size:8px;color:#22c55e;line-height:1}
@keyframes gtpPulse{
  0%,100%{box-shadow:0 0 0 2px #f59e0b}
  50%{box-shadow:0 0 0 2px #f59e0b,0 0 12px rgba(245,158,11,.45)}
}
@keyframes gtpPulseNew{
  0%,100%{box-shadow:0 0 0 2px #22c55e}
  50%{box-shadow:0 0 0 2px #22c55e,0 0 12px rgba(34,197,94,.45)}
}
.ge-changed{animation:gtpPulse 2s ease-in-out 3}
.ge-new{animation:gtpPulseNew 2s ease-in-out 3}
@media print{
  .gtp-changes{display:none!important}
  .ge-changed,.ge-new{box-shadow:none!important;animation:none!important}
  .ge-changed::after,.ge-new::after{display:none!important}
}

/* --- Event-Detail-Popup mit Backdrop-Blur --- */
.ge{cursor:pointer}
.gtp-popup-overlay{
  position:fixed;inset:0;
  background:rgba(0,0,0,.38);
  backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);
  z-index:9999;display:flex;align-items:center;justify-content:center;
  animation:gtpFadeIn .2s ease;
}
@keyframes gtpFadeIn{from{opacity:0}to{opacity:1}}
.gtp-popup-card{
  background:var(--gtp-bg);border-radius:16px;padding:1.6rem 1.75rem;
  max-width:500px;width:calc(100% - 2rem);
  box-shadow:0 16px 48px rgba(0,0,0,.2),0 2px 8px rgba(0,0,0,.1);
  position:relative;max-height:88vh;overflow-y:auto;
  animation:gtpSlideUp .22s ease;
}
@keyframes gtpSlideUp{from{transform:translateY(24px);opacity:0}to{transform:translateY(0);opacity:1}}
.gtp-popup-close{
  position:absolute;top:.75rem;right:.875rem;
  background:var(--gtp-surface);border:none;
  width:30px;height:30px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:1.1rem;line-height:1;cursor:pointer;
  color:var(--gtp-text-muted);transition:var(--gtp-tr-fast);
}
.gtp-popup-close:hover{background:var(--gtp-border);color:var(--gtp-text)}
.gtp-popup-cat{
  display:inline-block;font-size:.7rem;font-weight:700;letter-spacing:.07em;
  text-transform:uppercase;padding:.2rem .65rem;border-radius:20px;
  background:var(--gtp-surface);color:var(--gtp-text-muted);margin-bottom:.75rem;
}
.gtp-popup-cat.gc-konferenz{background:var(--c-kf-bg);color:var(--c-kf-tx)}
.gtp-popup-cat.gc-pruefung {background:var(--c-pf-bg);color:var(--c-pf-tx)}
.gtp-popup-cat.gc-projekt  {background:var(--c-pr-bg);color:var(--c-pr-tx)}
.gtp-popup-cat.gc-frei     {background:var(--c-fr-bg);color:var(--c-fr-tx)}
.gtp-popup-cat.gc-eltern   {background:var(--c-el-bg);color:var(--c-el-tx)}
.gtp-popup-cat.gc-frist    {background:var(--c-fs-bg);color:var(--c-fs-tx)}
.gtp-popup-cat.gc-standard {background:var(--c-st-bg);color:var(--c-st-tx)}
.gtp-popup-title{
  margin:0 0 .875rem;font-size:1.1rem;line-height:1.35;
  color:var(--gtp-text);font-weight:700;padding-right:1.5rem;
}
.gtp-popup-meta{font-size:.86rem;color:var(--gtp-text-muted);line-height:1.6;min-height:0}
.gtp-popup-meta:empty{display:none}
.gtp-popup-desc{
  margin-top:.75rem;font-size:.84rem;color:var(--gtp-text-muted);
  white-space:pre-line;border-top:1px solid var(--gtp-border);padding-top:.75rem;
  line-height:1.65;
}
.gtp-popup-desc:empty{display:none}
/* Bottom Sheet auf Mobile */
@media(max-width:767px){
  .gtp-popup-overlay{align-items:flex-end;background:rgba(0,0,0,.3)}
  .gtp-popup-card{
    border-bottom-left-radius:0;border-bottom-right-radius:0;
    border-top-left-radius:20px;border-top-right-radius:20px;
    max-width:100%;width:100%;margin:0;padding:1.25rem 1.5rem 2rem;
    animation:gtpSheetUp .25s cubic-bezier(.32,.72,0,1);
  }
  @keyframes gtpSheetUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
  /* Handle-Linie */
  .gtp-popup-card::before{
    content:"";display:block;
    width:40px;height:4px;border-radius:2px;
    background:var(--gtp-border);
    margin:0 auto .875rem;
  }
}
@media print{
  .gtp-popup-overlay{display:none!important}
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
 * Wechselt den Tab, scrollt zur Heute-Zeile falls vorhanden, setzt die
 * Mobile-Navigation auf die aktuelle Woche zurück und aktualisiert den
 * Heute-Button-Status.
 */
function gtpTab(q){
  gtpSwitchTab(q);
  var p=document.getElementById("gtp-q"+q);
  if(p && p.querySelector(".gt-today")){
    /* Desktop: Sanft zur Heute-Zeile im neuen Quartal scrollen */
    setTimeout(gtpScrollToday,60);
  }
  /* Heute-Button-Status nach dem Panel-Wechsel neu berechnen */
  setTimeout(gtpUpdateHeuteBtn,80);
  /* Mobile: Navigation im neuen Panel zur aktuellen Woche zurücksetzen */
  if(p){
    var mob=p.querySelector(".gtp-mob-weeks");
    if(mob) gtpMobResetToToday(mob);
  }
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
 * Kategorie-Filter: blendet nicht-passende Elemente komplett aus.
 * Suche (Focus-Mode): dimmt Nicht-Treffer per .ge-focus-dim statt sie zu verstecken,
 * damit der Kontext erhalten bleibt und Treffer markant hervorstechen.
 */
function gtpApplyVisibility(el){
  var c = el.getAttribute("data-c");
  var categoryOk = (gtpMode === "all") || !!gtpSel[c];

  /* Kategorie-Filter: komplett verstecken wenn nicht passend */
  el.style.display = categoryOk ? "" : "none";

  /* Suche: Focus-Mode – Nicht-Treffer abdimmen (nur bei aktiver Suche) */
  var inp = document.getElementById("gtp-search-input");
  var q   = inp ? inp.value.trim().toLowerCase() : "";

  if(categoryOk && q){
    var txt   = (el.textContent  || "").toLowerCase();
    var title = (el.getAttribute("title") || "").toLowerCase();
    var searchOk = txt.indexOf(q) !== -1 || title.indexOf(q) !== -1;
    el.classList.toggle("ge-focus-dim", !searchOk);
  } else {
    el.classList.remove("ge-focus-dim");
  }
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
 * Treffer erhalten .ge-hit (goldener Outline), Nicht-Treffer werden per
 * .ge-focus-dim gedimmt (Focus-Mode). Zählt Treffer pro Quartal und baut
 * klickbare Quartal-Links in der Ergebniszeile auf.
 */
function gtpDoSearch(val){
  var q = val.trim().toLowerCase();

  /* Highlight und Dim-Klassen zurücksetzen */
  document.querySelectorAll(".ge-hit").forEach(function(el){
    el.classList.remove("ge-hit");
  });
  document.querySelectorAll(".ge-focus-dim").forEach(function(el){
    el.classList.remove("ge-focus-dim");
  });

  /* Bei leerem Suchfeld: Normalmodus (Kategorie-Filter bleibt aktiv) */
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

/* Auto-Scroll & Init beim Laden – 200 ms Verzögerung damit WordPress und das
   Theme fertig gerendert haben und getBoundingClientRect() korrekte Werte liefert. */
/* DOM-Ready: Änderungs-Banner auswerten */
document.addEventListener("DOMContentLoaded",function(){
  gtpChangesInit();
});

setTimeout(function(){
  /* Desktop: zur Heute-Zeile scrollen (scrollt zum .gt-today in .gt Tabelle) */
  gtpScrollToday();
  /* Heute-Button initial auswerten */
  gtpUpdateHeuteBtn();
  /* Mobile: Nav-Anzeige befüllen (SW-Nummer + Datumsbereich) */
  gtpMobInit();
  /* Mobile: zur .gtp-mob-today Zeile scrollen wenn auf schmalen Bildschirm */
  if(window.innerWidth<768){
    var todayMob=document.querySelector(".gtp-mob-today");
    if(todayMob){
      var top=todayMob.getBoundingClientRect().top+window.pageYOffset;
      window.scrollTo({top:Math.max(0,top-Math.floor(window.innerHeight/3)),behavior:"smooth"});
    }
  }
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
/**
 * Öffnet den Druckdialog mit der Quartalstabelle im iframe.
 * @param {string} mode       "single" oder "all"
 * @param {string} [pdfTitle] Optionaler Dokumenttitel (= Vorschlag für PDF-Dateinamen)
 */
function gtpPrint(mode,pdfTitle){
  var ids;
  if(mode==="all"){
    ids=[1,2,3,4];
  }else{
    var at=document.querySelector(".gtp-tab-on");
    ids=[at ? parseInt(at.getAttribute("data-q")) : 1];
  }
  var docTitle=pdfTitle||"Terminplan Druck";
  var today=new Date().toLocaleDateString("de-DE",{day:"2-digit",month:"2-digit",year:"numeric"});

  /* ══════════════════════════════════════════════════
     PDF-CSS  –  A4 Querformat, minimalistisch
     ══════════════════════════════════════════════════ */
  var CSS=[
    /* Reset */
    "*{margin:0;padding:0;box-sizing:border-box}",
    /* Seite: A4 Querformat, Ränder lassen Platz für Footer */
    "@page{size:A4 landscape;margin:8mm 10mm 16mm 10mm}",
    /* Grundschrift */
    "body{font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#1a1a2e;font-size:9pt}",
    /* ── Kopfzeile: Logo links, Titel rechts ── */
    ".hdr{display:flex;justify-content:space-between;align-items:flex-end;" +
    "  margin-bottom:5pt;padding-bottom:4pt;border-bottom:0.5pt solid #999}",
    ".hdr-logo{display:flex;align-items:center;gap:5pt}",
    ".hdr-logo-mark{font-size:15pt;font-weight:900;color:#1a1a2e;letter-spacing:-.03em;line-height:1}",
    ".hdr-logo-sep{width:0.5pt;height:18pt;background:#ccc;margin:0 4pt}",
    ".hdr-logo-name{font-size:6.5pt;color:#666;line-height:1.4;font-weight:400}",
    ".hdr-title{text-align:right}",
    ".hdr-main{font-size:10.5pt;font-weight:700;color:#1a1a2e;letter-spacing:-.01em;line-height:1.2}",
    ".hdr-sub{font-size:6pt;color:#999;margin-top:2pt}",
    /* ── Quartal-Label ── */
    ".qt{font-size:7pt;font-weight:700;color:#666;margin:4pt 0 3pt;" +
    "  text-transform:uppercase;letter-spacing:.08em}",
    /* ── Tabelle: feine Linienführung ── */
    "table{width:100%;border-collapse:collapse;font-size:9pt;table-layout:fixed}",
    /* Kopfzeile dunkel/neutral, kein Blau */
    "thead th{background:#2d2d2d;color:#fff;padding:3pt 3pt;font-size:6.5pt;" +
    "  border:0.1pt solid #222;text-align:center;font-weight:600;letter-spacing:.05em}",
    /* Zellen: extrem feine Linie */
    "tbody td{border:0.1pt solid #ddd;padding:2pt 3pt;vertical-align:top}",
    /* Schulwochen-Spalte */
    ".gs{width:24pt;text-align:center;background:#f5f5f5;font-size:7pt;font-weight:700;color:#666}",
    /* Hinweise-Spalte */
    ".gh{width:90pt;background:#fafafa}",
    /* Datum-Label: 8pt fett */
    ".gdl{display:block;font-size:8pt;font-weight:700;color:#222;margin-bottom:2pt}",
    ".gnc{font-size:7pt;color:#555}",
    /* ── Event-Pill: Color-Indicator (Balken links), KEIN Hintergrund ── */
    ".ge{font-size:9pt;padding:1.5pt 2pt 1.5pt 5pt;margin:.5pt 0;" +
    "  line-height:1.3;color:#1a1a2e;background:transparent;" +
    "  border-left:2.5pt solid #ccc;display:block;border-radius:0}",
    ".gn-long{font-size:8pt;padding:1.5pt 2pt 1.5pt 5pt;margin:.5pt 0;" +
    "  line-height:1.3;color:#1a1a2e;background:transparent;" +
    "  border-left:2.5pt solid #ccc;font-weight:600;display:block}",
    ".gn-range{display:block;font-size:6pt;color:#888;margin-bottom:1pt}",
    /* Kategorie-Farben: NUR Balken-Farbe, kein Hintergrund, Text immer dunkel */
    ".gc-konferenz{border-left-color:#3b82f6}",
    ".gc-pruefung{border-left-color:#ef4444}",
    ".gc-projekt{border-left-color:#22c55e}",
    ".gc-frei{border-left-color:#94a3b8}",
    ".gc-eltern{border-left-color:#f97316}",
    ".gc-frist{border-left-color:#eab308}",
    ".gc-standard{border-left-color:#94a3b8}",
    /* Heute: sehr dezent */
    ".gt-today{background:#f0f7ff!important}",
    ".gt-today .gdl{color:#2563eb}",
    /* Ferientag: minimales Grau */
    ".gt-hol{background:#f9f9f9!important}",
    /* Frist-Notizen */
    ".gn{font-size:7.5pt;color:#555;padding:1pt 2pt 1pt 5pt;margin:.5pt 0;" +
    "  font-style:italic;border-left:2.5pt solid #eab308;display:block}",
    /* Vergangene Wochen: volle Opazität im Druck */
    ".gtp-past{opacity:1!important}",
    ".gtp-past .ge{filter:none!important}",
    /* Sticky-Header deaktivieren */
    "thead th{position:static!important}",
    /* Seitenumbruch */
    "tr{page-break-inside:avoid}",
    ".pb{page-break-before:always}",
    /* ── Legende ── */
    ".leg{display:flex;gap:10pt;flex-wrap:wrap;align-items:center;" +
    "  margin-top:5pt;padding-top:3pt;border-top:0.1pt solid #ddd;font-size:6.5pt;color:#333}",
    ".leg-item{display:inline-flex;align-items:center;gap:3pt}",
    /* Dot statt Farbfläche */
    ".ld{width:6pt;height:6pt;border-radius:50%;display:inline-block;flex-shrink:0}",
    /* ── Footer: erscheint auf JEDER Seite (position:fixed im Druck) ── */
    ".pdf-ft{position:fixed;bottom:0;left:0;right:0;" +
    "  border-top:0.1pt solid #ddd;padding:1.5pt 10mm;" +
    "  font-size:5.5pt;color:#aaa;text-align:center;background:#fff}",
    /* Druckfarben erzwingen */
    "thead th,.gs,.ge,.gn-long,.gn,.ld,.gt-today,.gt-hol," +
    ".gc-konferenz,.gc-pruefung,.gc-projekt,.gc-frei,.gc-eltern,.gc-frist,.gc-standard{" +
    "  -webkit-print-color-adjust:exact;print-color-adjust:exact}",
    "@media print{body{padding:0}}"
  ].join("\n");

  /* ── Legende: farbige Punkte statt Flächen ── */
  var LEG='<div class="leg">'
    +'<span class="leg-item"><span class="ld" style="background:#3b82f6"></span>Konferenz</span>'
    +'<span class="leg-item"><span class="ld" style="background:#ef4444"></span>Pr\u00fcfung</span>'
    +'<span class="leg-item"><span class="ld" style="background:#22c55e"></span>Projekt</span>'
    +'<span class="leg-item"><span class="ld" style="background:#94a3b8"></span>Ferien/Frei</span>'
    +'<span class="leg-item"><span class="ld" style="background:#f97316"></span>Eltern</span>'
    +'<span class="leg-item"><span class="ld" style="background:#eab308"></span>Fristen</span>'
    +'</div>';

  /* ── Kopfzeile: Logo links | Titel rechts ── */
  var HDR='<div class="hdr">'
    +'<div class="hdr-logo">'
    +'<span class="hdr-logo-mark">GSH</span>'
    +'<span class="hdr-logo-sep"></span>'
    +'<span class="hdr-logo-name">Gesamtschule<br>Horst</span>'
    +'</div>'
    +'<div class="hdr-title">'
    +'<div class="hdr-main">Schuljahresterminplan\u00a02025\u202f/\u202f26</div>'
    +'<div class="hdr-sub">Quelle: IServ-Kalender</div>'
    +'</div>'
    +'</div>';

  /* ── Footer: fixed → erscheint auf jeder PDF-Seite ── */
  var FTR='<div class="pdf-ft">Stand: '+today+'\u2003|\u2003Erstellt \u00fcber das GSH-Dashboard</div>';

  var body=FTR; /* Footer zuerst (fixed, daher immer gerendert) */
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
    '<meta charset="utf-8">','<title>'+docTitle+'</title>',
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

/* ══════════════════════════════════════════════════════
   MOBILE AGENDA-NAVIGATION (< 768px)
   ══════════════════════════════════════════════════════
   PHP rendert alle Wochen als .gtp-mob-week Divs im .gtp-mob-weeks Container.
   Die Sichtbarkeit wird initial per PHP gesteuert (data-start Attribut).
   Hier steuert JS:
   - Navigation vor/zurück (‹/›-Buttons oder Wisch-Geste)
   - Navigationsanzeige (Schulwoche + Datumsbereich in sticky Nav)
   - Zurück-zur-aktuellen-Woche (📍-Button)
   ══════════════════════════════════════════════════════ */

/**
 * Aktualisiert die Anzeige in der sticky Nav-Leiste.
 * Liest die Inhalte der sichtbaren .gtp-mob-wh Köpfe und zeigt
 * Schulwoche(n) und Datumsbereich kompakt an.
 * @param {Element} container  Das .gtp-mob-weeks Element.
 */
function gtpMobUpdateNav(container){
  var mob=container.closest(".gtp-mob");
  if(!mob) return;
  var swEl=mob.querySelector(".gtp-mob-nav-sw");
  var dtEl=mob.querySelector(".gtp-mob-nav-dates");
  if(!swEl||!dtEl) return;

  /* Alle aktuell sichtbaren Wochen sammeln */
  var vis=[];
  container.querySelectorAll(".gtp-mob-week").forEach(function(w){
    if(w.style.display!=="none") vis.push(w);
  });
  if(!vis.length) return;

  /* Schulwochen-Nummern aus den Wochenköpfen lesen */
  var swTxts=[];
  vis.forEach(function(w){
    var s=w.querySelector(".gtp-mob-wh span:first-child");
    if(s) swTxts.push(s.textContent.replace("Schulwoche\u00a0","SW "));
  });
  swEl.textContent=swTxts.join(" \u2013 ");

  /* Datumsbereich: Montag der ersten bis Freitag der letzten sichtbaren Woche */
  var fSub=vis[0].querySelector(".gtp-mob-wh-sub");
  var lSub=vis[vis.length-1].querySelector(".gtp-mob-wh-sub");
  if(fSub&&lSub){
    /* .gtp-mob-wh-sub enthält "dd.mm. – dd.mm." */
    var fp=fSub.textContent.split("\u2013");
    var lp=lSub.textContent.split("\u2013");
    dtEl.textContent=(fp[0]||"").trim()+" \u2013 "+(lp[1]||lp[0]||"").trim();
  }
}

/**
 * Interner Helper: Verschiebt das 2-Wochen-Fenster um dir (+1/-1) Schritte.
 * Aktualisiert data-start, Woche-Sichtbarkeit, Prev/Next-Buttons und Nav-Text.
 * @param {Element} container  Das .gtp-mob-weeks Element.
 * @param {number}  dir        +1 (vorwärts) oder -1 (rückwärts).
 */
function gtpMobNavContainer(container,dir){
  var total  =parseInt(container.getAttribute("data-total"),  10)||0;
  var visible=parseInt(container.getAttribute("data-visible"),10)||2;
  var start  =parseInt(container.getAttribute("data-start"),  10)||0;

  var ns=start+dir;
  /* Grenzen einhalten: 0 … total-visible */
  if(ns<0)             ns=0;
  if(ns>total-visible) ns=total-visible;
  if(ns===start)       return; /* Keine Änderung nötig */

  container.setAttribute("data-start",ns);

  /* Woche-Divs ein-/ausblenden */
  container.querySelectorAll(".gtp-mob-week").forEach(function(w){
    var wi=parseInt(w.getAttribute("data-wi"),10);
    w.style.display=(wi>=ns&&wi<ns+visible)?"":"none";
  });

  /* Prev/Next-Buttons an den Rändern deaktivieren */
  var mob=container.closest(".gtp-mob");
  if(mob){
    var pb=mob.querySelector(".gtp-mob-prev");
    var nb=mob.querySelector(".gtp-mob-next");
    if(pb) pb.disabled=(ns===0);
    if(nb) nb.disabled=(ns>=total-visible);
  }
  gtpMobUpdateNav(container);
}

/**
 * Navigiert das sichtbare Fenster um 1 Woche vor (+1) oder zurück (-1).
 * Aufgerufen durch onclick der ‹/›-Buttons.
 * @param {Element} btn  Der geklickte Button (für closest-Suche).
 * @param {number}  dir  +1 oder -1.
 */
function gtpMobNav(btn,dir){
  /* btn liegt in .gtp-mob-nav, das ein Geschwisterelement von .gtp-mob-weeks ist.
     closest() sucht nur aufwärts – deshalb erst zum Elternelement .gtp-mob,
     dann querySelector() nach unten zum richtigen Container. */
  var mob=btn.closest(".gtp-mob");
  var c=mob?mob.querySelector(".gtp-mob-weeks"):null;
  if(c) gtpMobNavContainer(c,dir);
}

/**
 * Setzt das Schiebefenster auf die Woche mit "heute" zurück.
 * Wird intern von gtpMobToday() und gtpTab() genutzt.
 * @param {Element} container  Das .gtp-mob-weeks Element.
 */
function gtpMobResetToToday(container){
  var todayIdx=parseInt(container.getAttribute("data-today-idx"),10);
  if(isNaN(todayIdx)) todayIdx=0;
  var total  =parseInt(container.getAttribute("data-total"),  10)||0;
  var visible=parseInt(container.getAttribute("data-visible"),10)||2;

  /* Fenster so setzen dass today-Woche am Anfang liegt, aber Grenze beachten */
  var ns=Math.min(todayIdx,Math.max(0,total-visible));
  container.setAttribute("data-start",ns);

  container.querySelectorAll(".gtp-mob-week").forEach(function(w){
    var wi=parseInt(w.getAttribute("data-wi"),10);
    w.style.display=(wi>=ns&&wi<ns+visible)?"":"none";
  });

  var mob=container.closest(".gtp-mob");
  if(mob){
    var pb=mob.querySelector(".gtp-mob-prev");
    var nb=mob.querySelector(".gtp-mob-next");
    if(pb) pb.disabled=(ns===0);
    if(nb) nb.disabled=(ns>=total-visible);
  }
  gtpMobUpdateNav(container);
}

/**
 * Springt zur Woche mit "heute" und scrollt sanft zur .gtp-mob-today Zeile.
 * Aufgerufen durch den 📍-Button in der Nav-Leiste.
 * @param {Element} btn  Der geklickte Button.
 */
function gtpMobToday(btn){
  /* Gleiche Situation wie gtpMobNav: btn ist in .gtp-mob-nav (Geschwister),
     nicht in .gtp-mob-weeks. Deshalb über .gtp-mob-Elternelement navigieren. */
  var mob=btn.closest(".gtp-mob");
  var c=mob?mob.querySelector(".gtp-mob-weeks"):null;
  if(!c) return;
  gtpMobResetToToday(c);
  /* Sanft zu .gtp-mob-today scrollen */
  setTimeout(function(){
    var el=mob.querySelector(".gtp-mob-today");
    if(!el) return;
    var top=el.getBoundingClientRect().top+window.pageYOffset;
    window.scrollTo({top:Math.max(0,top-Math.floor(window.innerHeight/3)),behavior:"smooth"});
  },60);
}

/* ── Swipe-Geste: Horizontaler Wisch navigiert die Agenda ──
   Event-Delegation auf document → keine Listener pro Quartal nötig.
   Swipe links (dx < 0) = nächste Wochen, Swipe rechts (dx > 0) = vorherige. */
(function(){
  var sx=0,st=null; /* Startposition und Ziel-Container */
  document.addEventListener("touchstart",function(e){
    st=e.target.closest(".gtp-mob-weeks");
    sx=e.changedTouches[0].clientX;
  },{passive:true});
  document.addEventListener("touchend",function(e){
    if(!st) return;
    var dx=e.changedTouches[0].clientX-sx;
    /* Nur bei horizontalen Wischen > 60 px reagieren */
    if(Math.abs(dx)<60){st=null;return;}
    gtpMobNavContainer(st,dx<0?1:-1);
    st=null;
  },{passive:true});
})();

/**
 * Initialisiert alle vier Mobile-Agenda-Container beim Laden.
 * Aktualisiert die Nav-Anzeige (SW-Nummern + Datumsbereich) für jedes Quartal,
 * da die Divs initial per PHP gesetzt wurden.
 */
function gtpMobInit(){
  document.querySelectorAll(".gtp-mob-weeks").forEach(function(c){
    gtpMobUpdateNav(c);
  });
}

/* ========== Änderungs-Benachrichtigungen ========== */

/** Schlüssel für localStorage */
var GTP_VISIT_KEY="gtp_last_visit";

/**
 * Liest den ISO-Zeitstempel des letzten Besuchs aus localStorage.
 * Gibt null zurück, wenn kein Eintrag vorhanden oder localStorage nicht verfügbar.
 * @returns {string|null}
 */
function gtpGetLastVisit(){
  try{ return localStorage.getItem(GTP_VISIT_KEY)||null; }catch(e){ return null; }
}

/**
 * Speichert die aktuelle Zeit als letzten Besuch in localStorage.
 */
function gtpSetLastVisit(){
  try{ localStorage.setItem(GTP_VISIT_KEY,new Date().toISOString()); }catch(e){}
}

/**
 * Wertet die data-changes-Daten am .gtp-Container aus und zeigt ggf.
 * den Änderungs-Banner an. Hebt geänderte und neue Events im Plan hervor.
 * Wird bei DOMContentLoaded aufgerufen.
 */
function gtpChangesInit(){
  var container=document.querySelector(".gtp");
  if(!container) return;

  var attr=container.getAttribute("data-changes");
  if(!attr) return;

  var allChanges;
  try{ allChanges=JSON.parse(attr); }catch(e){ return; }
  if(!allChanges||!allChanges.length) return;

  var lastVisit=gtpGetLastVisit();

  /* Erster Besuch: keine Benachrichtigung, Zeit speichern */
  if(!lastVisit){
    gtpSetLastVisit();
    return;
  }

  /* Nur Einträge seit dem letzten Besuch berücksichtigen */
  var relevant=[];
  for(var i=0;i<allChanges.length;i++){
    if(allChanges[i].time>lastVisit) relevant.push(allChanges[i]);
  }
  if(!relevant.length) return;

  /* UIDs aller betroffenen Events sammeln */
  var addedUids={};
  var changedUids={};
  var removedItems=[];

  for(var r=0;r<relevant.length;r++){
    var diff=relevant[r];
    if(diff.added){
      for(var a=0;a<diff.added.length;a++) addedUids[diff.added[a]]=true;
    }
    if(diff.changed){
      for(var uid in diff.changed) changedUids[uid]=diff.changed[uid];
    }
    if(diff.removed){
      for(var d=0;d<diff.removed.length;d++) removedItems.push(diff.removed[d]);
    }
  }

  var totalNew=Object.keys(addedUids).length;
  var totalChanged=Object.keys(changedUids).length;
  var totalRemoved=removedItems.length;
  var total=totalNew+totalChanged+totalRemoved;
  if(!total) return;

  /* Banner-Text zusammensetzen */
  var parts=[];
  if(totalNew>0) parts.push(totalNew+" neu");
  if(totalChanged>0) parts.push(totalChanged+" ge\u00e4ndert");
  if(totalRemoved>0) parts.push(totalRemoved+" entfernt");
  var text=total+" \u00c4nderung"+(total>1?"en":"")
    +" seit deinem letzten Besuch ("+parts.join(", ")+")";

  var textEl=document.getElementById("gtpChangesText");
  if(textEl) textEl.textContent=text;

  var banner=document.getElementById("gtpChanges");
  if(banner) banner.style.display="block";

  /* Events im Plan hervorheben */
  var allGe=document.querySelectorAll(".ge[data-uid]");
  for(var g=0;g<allGe.length;g++){
    var geUid=allGe[g].getAttribute("data-uid");
    if(geUid&&addedUids[geUid]){
      allGe[g].classList.add("ge-new");
    }else if(geUid&&changedUids[geUid]){
      allGe[g].classList.add("ge-changed");
    }
  }

  /* Detail-Liste aufbauen */
  var list=document.getElementById("gtpChangesList");
  if(!list) return;
  var html="";

  /* Neue Events */
  for(var na in addedUids){
    /* Zusammenfassung aus dem DOM lesen (falls Event sichtbar) */
    var naEl=document.querySelector(".ge[data-uid=\""+na+"\"]");
    var naSummary=naEl?naEl.getAttribute("data-summary")||naEl.textContent:na;
    html+="<div class=\"gtp-changes-item\">"
      +"<span class=\"gtp-changes-badge gtp-changes-badge-new\">Neu</span>"
      +"<span>"+escHtml(naSummary)+"</span>"
      +"</div>";
  }

  /* Geänderte Events */
  for(var cu in changedUids){
    var ci=changedUids[cu];
    var fieldLabel=ci.field==="summary"?"Titel":ci.field==="start"?"Datum":ci.field==="end"?"Ende":ci.field;
    html+="<div class=\"gtp-changes-item\">"
      +"<span class=\"gtp-changes-badge gtp-changes-badge-mod\">Ge\u00e4ndert</span>"
      +"<span>"+escHtml(ci.summary)
      +" <small>(\u00c4nderung: "+fieldLabel
      +" \u00ab"+escHtml(ci.old)+"\u00bb \u2192 \u00ab"+escHtml(ci["new"])+"\u00bb)</small>"
      +"</span>"
      +"</div>";
  }

  /* Entfernte Events */
  for(var ri=0;ri<removedItems.length;ri++){
    var rem=removedItems[ri];
    var remSummary=typeof rem==="object"&&rem.summary?rem.summary:String(rem);
    html+="<div class=\"gtp-changes-item\">"
      +"<span class=\"gtp-changes-badge gtp-changes-badge-del\">Entfernt</span>"
      +"<span>"+escHtml(remSummary)+"</span>"
      +"</div>";
  }

  list.innerHTML=html;
}

/**
 * Kleine Hilfsfunktion: HTML-Sonderzeichen in einem String escapen,
 * damit er sicher per innerHTML eingefügt werden kann.
 * @param {string} s
 * @returns {string}
 */
function escHtml(s){
  return String(s)
    .replace(/&/g,"&amp;").replace(/</g,"&lt;")
    .replace(/>/g,"&gt;").replace(/"/g,"&quot;");
}

/**
 * Klappt die Änderungs-Detailliste auf bzw. zu und passt den Button-Text an.
 */
function gtpChangesToggle(){
  var list=document.getElementById("gtpChangesList");
  var btn=document.getElementById("gtpChangesShow");
  if(!list) return;
  var isVisible=list.style.display!=="none";
  list.style.display=isVisible?"none":"block";
  if(btn) btn.textContent=isVisible?"Anzeigen":"Verbergen";
}

/**
 * Schließt den Änderungs-Banner, entfernt alle Hervorhebungen und
 * speichert die aktuelle Zeit als neuen "letzten Besuch".
 */
function gtpChangesDismiss(){
  var banner=document.getElementById("gtpChanges");
  if(banner) banner.style.display="none";

  /* Hervorhebungen entfernen */
  var highlighted=document.querySelectorAll(".ge-changed,.ge-new");
  for(var i=0;i<highlighted.length;i++){
    highlighted[i].classList.remove("ge-changed","ge-new");
  }

  gtpSetLastVisit();
}

/* ========== PDF-Export ========== */

/**
 * Zeigt einen browser-spezifischen Hinweis-Banner, wie man im Druckdialog
 * "Als PDF speichern" wählt. Blendet sich nach 12 Sekunden automatisch aus.
 */
function gtpPdfHint(){
  /* Falls bereits ein Hinweis sichtbar ist, erst entfernen */
  var old=document.getElementById("gtpPdfHint");
  if(old) old.remove();

  var ua=navigator.userAgent;
  var isChrome=/Chrome/.test(ua)&&!/Edg/.test(ua);
  var isSafari=/Safari/.test(ua)&&!/Chrome/.test(ua);
  var isFirefox=/Firefox/.test(ua);
  /* isEdge: alle anderen Chromium-Edge-Versionen */

  var msg;
  if(isChrome){
    msg='Waehle im Druckdialog bei \u201eZiel\u201c \u2192 \u201eAls PDF speichern\u201c und klicke \u201eSpeichern\u201c.';
  }else if(isSafari){
    msg='Klicke im Druckdialog unten links auf \u201ePDF\u201c \u2192 \u201eAls PDF sichern\u201c.';
  }else if(isFirefox){
    msg='Waehle im Druckdialog bei \u201eDrucker\u201c \u2192 \u201eMicrosoft Print to PDF\u201c oder \u201eAls PDF speichern\u201c.';
  }else{
    msg='Waehle im Druckdialog die Option \u201eAls PDF speichern\u201c.';
  }

  var hint=document.createElement("div");
  hint.className="gtp-pdf-hint";
  hint.id="gtpPdfHint";
  hint.innerHTML='<span>\uD83D\uDCA1 '+msg+'</span>'
    +'<button class="gtp-pdf-hint-close" onclick="this.parentElement.remove()" aria-label="Hinweis schließen">\u00d7</button>';
  document.body.prepend(hint);

  /* Automatisch ausblenden nach 12 Sekunden */
  setTimeout(function(){
    var el=document.getElementById("gtpPdfHint");
    if(el) el.remove();
  },12000);
}

/**
 * Exportiert das aktive Quartal als PDF (über den Browser-Druckdialog).
 * Liest den Quartalstitel und setzt ihn als Dokumenttitel (= PDF-Dateinamen-Vorschlag).
 */
function gtpPdf(){
  var at=document.querySelector(".gtp-tab-on");
  var q=at ? parseInt(at.getAttribute("data-q")) : 1;
  var panel=document.getElementById("gtp-q"+q);
  var qtEl=panel ? panel.querySelector(".gtp-qt") : null;
  var title=qtEl
    ? "Terminplan GSH 2025-26 - "+qtEl.textContent
    : "Terminplan GSH 2025-26";
  gtpPdfHint();
  gtpPrint("single",title);
}

/**
 * Exportiert alle vier Quartale als PDF (über den Browser-Druckdialog).
 */
function gtpPdfAll(){
  gtpPdfHint();
  gtpPrint("all","Terminplan GSH 2025-26 - Komplett");
}

/* ========== Event-Detail-Popup ========== */

/**
 * Gibt den deutschen Wochentagsnamen (kurz) für ein Date-Objekt zurück.
 * @param {Date} d
 * @returns {string}  z. B. "Mo", "Fr"
 */
function gtpDow(d){
  return["So","Mo","Di","Mi","Do","Fr","Sa"][d.getDay()];
}

/**
 * Parst einen ISO-8601-Datums-String (YYYY-MM-DD) ohne UTC-Offset.
 * Erstellt das Date-Objekt über Einzelwerte (Jahr, Monat, Tag), damit
 * keine Zeitzone den Wert verschiebt.
 * @param {string} s  "YYYY-MM-DD"
 * @returns {Date}
 */
function gtpParseDate(s){
  var p=s.split("-");
  return new Date(parseInt(p[0],10),parseInt(p[1],10)-1,parseInt(p[2],10));
}

/**
 * Öffnet das Event-Detail-Popup und befüllt es mit den data-*-Attributen
 * des geklickten .ge-Elements. Setzt Tab-Trap und Fokus auf das Popup.
 * @param {HTMLElement} el  Das geklickte .ge-Element
 */
function gtpPopupOpen(el){
  var popup=document.getElementById("gtpPopup");
  if(!popup) return;

  var summary =el.dataset.summary||"";
  var dateStr =el.dataset.date||"";
  var endStr  =el.dataset.end||"";
  var time    =el.dataset.time||"";
  var loc     =el.dataset.location||"";
  var desc    =el.dataset.desc||"";
  var cat     =el.dataset.cat||"";
  var allday  =el.dataset.allday==="1";

  /* Kategorie-Badge */
  var catEl=document.getElementById("gtpPopupCat");
  catEl.textContent=cat?cat:"";
  catEl.className="gtp-popup-cat gc-"+cat;
  catEl.style.display=cat?"":"none";

  /* Titel */
  document.getElementById("gtpPopupTitle").textContent=summary;

  /* Datum (und ggf. Endedatum bei mehrtägigen Terminen) */
  var dateEl=document.getElementById("gtpPopupDate");
  if(dateStr){
    var ds=gtpParseDate(dateStr);
    var dateLabel=gtpDow(ds)+", "+ds.getDate()+"."+(ds.getMonth()+1)+"."+ds.getFullYear();
    if(endStr && endStr!==dateStr){
      var de=gtpParseDate(endStr);
      /* iCal DTEND ist exklusiv → einen Tag zurückrechnen */
      de.setDate(de.getDate()-1);
      if(de.getTime()!==ds.getTime()){
        dateLabel+=" \u2013 "+gtpDow(de)+", "+de.getDate()+"."+(de.getMonth()+1)+"."+de.getFullYear();
      }
    }
    dateEl.textContent="\uD83D\uDCC5 "+dateLabel;
  } else {
    dateEl.textContent="";
  }

  /* Uhrzeit */
  var timeEl=document.getElementById("gtpPopupTime");
  timeEl.textContent=time?"\u23F0 "+time:"";

  /* Ort */
  var locEl=document.getElementById("gtpPopupLoc");
  locEl.textContent=loc?"\uD83D\uDCCD "+loc:"";

  /* Beschreibung (textContent ist XSS-sicher; white-space:pre-line zeigt Zeilenumbrüche) */
  document.getElementById("gtpPopupDesc").textContent=desc;

  /* Popup sichtbar machen und fokussieren */
  popup.style.display="flex";
  popup.focus();

  /* Tab-Trap: Fokus bleibt innerhalb des Popups */
  popup._trapFn=function(e){
    if(e.key!=="Tab") return;
    var focusable=popup.querySelectorAll('button,[href],input,select,textarea,[tabindex]:not([tabindex="-1"])');
    var first=focusable[0],last=focusable[focusable.length-1];
    if(!first) return;
    if(e.shiftKey){
      if(document.activeElement===first){e.preventDefault();last.focus();}
    } else {
      if(document.activeElement===last){e.preventDefault();first.focus();}
    }
  };
  document.addEventListener("keydown",popup._trapFn);
}

/**
 * Schließt das Event-Detail-Popup und entfernt den Tab-Trap.
 */
function gtpPopupClose(){
  var popup=document.getElementById("gtpPopup");
  if(!popup) return;
  popup.style.display="none";
  if(popup._trapFn){
    document.removeEventListener("keydown",popup._trapFn);
    popup._trapFn=null;
  }
}

/* Escape-Taste schließt das Popup */
document.addEventListener("keydown",function(e){
  if(e.key==="Escape"){gtpPopupClose();}
});

/* Klick auf den Overlay-Hintergrund (außerhalb der Karte) schließt das Popup */
document.addEventListener("click",function(e){
  var popup=document.getElementById("gtpPopup");
  if(popup && popup.style.display!=="none" && e.target===popup){
    gtpPopupClose();
  }
});
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
