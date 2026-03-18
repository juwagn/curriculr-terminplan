<?php
/**
 * Plugin Name: GSH Terminplan Dashboard
 * Plugin URI:  https://gesamtschule-horst.de
 * Description: Interaktive Quartalsuebersicht des Schuljahresterminplans aus dem IServ-Kalender (iCal-Feed).
 * Version:     3.12.0
 * Author:      Gesamtschule Horst
 * License:     GPL v2 or later
 * Text Domain: gsh-terminplan
 *
 * Changelog 3.12.0:
 * - [FEATURE] Feedback-Button in der Fusszeile: Fehler melden, Funktionswuensche und Lob einreichen
 * - [FEATURE] Feedback-Modal: Typ waehlen + Freitext, oeffnet Microsoft Forms im neuen Tab
 * - [UX] Zeichenzaehler im Textfeld (max. 1000 Zeichen)
 * - [UX] Absenden-Button erst aktiv wenn Typ und Text ausgefuellt sind
 * - [UX] Bestaetigung im Footer-Button nach Absenden ("Danke!")
 *
 * Changelog 3.11.0:
 * - [UX] Alle Emoji-Icons durch konsistente Lucide-SVGs ersetzt (scharf, themefaehig, OS-unabhaengig)
 * - [UX] Suchfeld: Icon neben Eingabefeld statt im Placeholder-Text
 * - [INFRA] gsh_tp_icon()-Hilfsfunktion als zentrale Icon-Verwaltung (18 Icons, Lucide 0.395)
 * - [INFRA] CSS Design-Tokens fuer Spacing (4px-Raster) und Border-Radius in :root ergaenzt
 *
 * Changelog 3.10.0:
 * - [FEATURE] Onboarding-Tour: erklaert beim ersten Besuch Filter, Suche, PDF und Dark Mode
 * - [UX] "?"-Button im Header startet die Tour jederzeit manuell neu
 * - [UX] Tour merkt sich: nach Abschluss oder Abbruch kein automatischer Neustart
 * - [UX] Shepherd.js-Design angepasst ans Plugin-Layout (Farben, Radius, Typografie)
 * - [UX] Fortschrittsanzeige in jedem Tour-Schritt (z. B. "2 / 5")
 * - [INFRA] Shepherd.js 11.2.0 via cdnjs, wird nur auf der Terminplan-Seite geladen
 *
 * Changelog 3.9.0:
 * - [FEATURE] Dark Mode: neues dunkles Theme mit angepassten Farben
 * - [FEATURE] Theme-Switcher: schwebendes Gear-Icon unten rechts (Hell / Dunkel / System)
 * - [UX] Wahl wird im Browser gespeichert und beim naechsten Besuch wiederhergestellt
 * - [UX] Modus "System" folgt automatisch der Geraeteeinstellung (prefers-color-scheme)
 * - [INFRA] Dark-Mode-CSS in assets/css/gsh-terminplan.css via data-gtp-theme-Attribut
 *
 * Changelog 3.8.0:
 * - [FEATURE] Changelog-Modal: Versionsnummer im Frontend und Admin anklickbar
 * - [UX] Frontend zeigt letzte 3 Versionen, gefiltert auf benutzerrelevante Eintraege
 * - [UX] Admin zeigt vollstaendigen Changelog aller Versionen mit Tag-Farbcodierung
 * - [INFRA] gsh_tp_changelog() als strukturiertes PHP-Array (Single Source of Truth)
 *
 * Changelog 3.7.0:
 * - [UX] Fluid-Grid-Layout ersetzt hartes 2-Stufen-Breakpoint-System
 * - CSS Grid mit auto-fit/minmax() fuer fliegende Spaltenumbrueche
 * - Flexbox-Fallback fuer aeltere Browser (kein CSS-Grid-Support)
 * - Tabellen → Cards im Bereich 1024–1200 px (rein per CSS, kein JS)
 * - data-label-Attribute in gsh_tp_table() ergaenzt (SW, Mo-Fr, Hinweise)
 * - Neues CSS-File: assets/css/gsh-terminplan.css
 * - wp_enqueue_style() mit Cache-Busting via GSH_TP_VERSION
 *
 * Changelog 3.6.4:
 * - [INFRA] Cache-Key-Versionierung gegen stale Daten nach Plugin-Updates
 * - Neue Konstante GSH_TP_CACHE_VERSION (aktuell: 3) für versionierte Key-Suffixe
 * - Neue Hilfsfunktion gsh_tp_ck($prefix, $pid) erzeugt Keys im Format …_v3
 * - Neue Funktion gsh_tp_clear_version_caches($version) löscht veraltete Keys
 * - Migration gsh_tp_migrate_cache_version() läuft einmalig via admin_init
 * - Betrifft: gsh_tp_ical_, gsh_tp_fresh_, gsh_tp_chg_, gsh_tp_sync_logs_
 *
 * Changelog 3.6.3:
 * - [UX] Mobile: Heutige Woche deutlich sichtbarer hervorgehoben
 * - Pulsierender Punkt im Wochenkopf der aktuellen Schulwoche (@keyframes gtpPulse)
 * - "HEUTE"-Badge größer und prominenter (erhöhte Schrift- und Padding-Größe)
 * - Nav-Leiste zeigt "👉 Heute" wenn die aktuelle Woche im Sichtfenster liegt
 * - Neue JS-Funktion: gtpMobTodayIndicator() – aufgerufen aus gtpMobUpdateNav()
 *
 * Changelog 3.6.2:
 * - [UX] iCal-URL-Validierung mit sprechenden Fehlermeldungen im Admin
 * - Neue Funktion: gsh_tp_validate_ical_url() mit detaillierten Rückgabewerten
 * - Profil-Speicherung zeigt Warnung bei ungültiger URL statt stummem Reset
 * - URL-Input: pattern-Attribut + Inline-Feedback-Span
 * - JavaScript: 1s-Debounce-Validierung mit ✓/✗/⌛ Statusanzeige
 *
 * Changelog 3.6.1:
 * - [UX] Filter-Buttons: echter Toggle-Modus statt Exklusiv-Modus
 * - Jeder Button schaltet seine Kategorie unabhängig ein/aus
 * - Filter-Zustand wird per localStorage seitenübergreifend gespeichert
 * - aria-pressed="true/false" für Barrierefreiheit
 * - Zähler-Label zeigt "X/Y sichtbar" wenn mindestens eine Kategorie versteckt ist
 *
 * Changelog 3.6.0:
 * - [FEATURE] Admin-Dashboard für Sync-Verlauf
 * - Neue Tab: "Sync-Verlauf" zeigt letzte 20 Sync-Versuche pro Profil
 * - Details pro Sync: Timestamp, Status, Fehlertyp, Event-Count, Dauer
 * - Farb-Codierung: Grün (✓ erfolgreich), Rot (✗ Fehler)
 * - Automatisches Cleanup: Logs älter als 30 Tage entfernen
 * - Neue Funktionen: gsh_tp_log_sync_attempt(), gsh_tp_get_sync_logs(), gsh_tp_clear_old_logs()
 *
 * Changelog 3.5.3:
 * - [UX] Bestätigungs-Dialoge bei destruktiven Aktionen (Löschen, Cache-Reset)
 * - Neue Bestätigungen: Profil-Löschen, Cache-Leeren, Token-Regenerieren
 * - Verbessert Admin-Workflow durch klare Konsequenz-Anzeige
 * - Reduziert versehentliches Löschen von Schuljahr-Profilen
 *
 * Changelog 3.5.2:
 * - [SECURITY] Kiosk-Token Rate-Limiting und Timing-Safe-Vergleich
 * - Neue Funktion: gsh_tp_check_kiosk_access() für Token-Validierung
 * - Admin-Panel: Warnung bei fehlendem Token oder ungültiger IServ-Domain
 * - Max. 10 Kiosk-Zugriffe pro IP pro Stunde (DoS-Schutz)
 * - Logging fehlgeschlagener Kiosk-Versuche
 *
 * Changelog 3.5.1:
 * - [SECURITY] CSRF-Token-Validierung für alle Admin-Formulare
 * - [BUGFIX] Validierung von $_POST ohne wp_verify_nonce()
 * - Betroffene Funktionen: gsh_tp_settings_page(), Profil-Speicherung, Kategorien
 * - Empfehlung: Sofort einspielen (Sicherheits-Patch)
 *
 * Changelog 3.5.0:
 * - Zwei-Schuljahre-Verwaltung: Jedes Schuljahr hat ein eigenes Profil mit
 *   iCal-URL, Quartalsgrenzen, Schulwochenstart und eigenem Cache.
 * - Admin-Tab-System: WordPress-native nav-tab-wrapper ersetzt die flache
 *   Einstellungsseite. Ein Tab pro Schuljahr + Kategorien + Kiosk & System.
 * - Entwurfs-Modus: Noch nicht beschlossene Terminpläne sind nur für Admins
 *   sichtbar. Farbiges Banner im Frontend weist auf den Entwurfs-Status hin.
 * - Neues Schuljahr per Button anlegen (kopiert Struktur des aktiven Profils).
 * - Frontend-Umschalter zwischen aktiven Schuljahren (Pill-Buttons im Header).
 * - Neues Shortcode-Attribut: [gsh_terminplan schuljahr="entwurf"] für Vorschau.
 * - WP-Cron pro Profil: Jedes Schuljahr wird separat im Hintergrund aktualisiert.
 * - Migration: Bisherige Einstellungen werden automatisch als erstes Profil übernommen.
 * - Neue Option gsh_tp_profiles (serialisiertes Array).
 *
 * Changelog 3.4.0:
 * - Kategorien vollständig konfigurierbar: Neuer Admin-Bereich „Kategorien verwalten"
 *   mit Color-Pickern für Hintergrund-, Rahmen- und Textfarbe pro Kategorie.
 * - Stichwörter, Anzeigename und Sortierung pro Kategorie einstellbar.
 * - Neue Kategorien können hinzugefügt, bestehende geändert oder gelöscht werden.
 * - CSS wird dynamisch aus den Kategorie-Einstellungen generiert (keine hardcodierten
 *   Farbwerte mehr). Betrifft Frontend, Popup-Ansicht und PDF-Export.
 * - Filter-Buttons und Druck-Legende passen sich automatisch an.
 * - Neue Option gsh_tp_categories (serialisiertes Array).
 * - Migration: Default-Kategorien werden beim ersten Aufruf nach dem Update automatisch
 *   angelegt. Bestehende gsh_tp_kategorie_mapping bleibt als Fallback bestehen.
 * - Excel-zu-ICS Import-Tool: Neue Admin-Sektion „Terminplan-Import (Excel → ICS)".
 *   Schulleitung kann eine Excel-Datei mit Terminen hochladen (Drag & Drop oder
 *   Dateiauswahl), Vorschau der erkannten Termine sehen, und eine ICS-Datei
 *   herunterladen, die direkt in IServ importiert werden kann.
 * - Clientseitiges Parsing mit SheetJS (xlsx.js), kein PHP-Upload nötig.
 * - Intelligente Header-Erkennung und flexibles Spalten-Mapping.
 * - Unterstützt Uhrzeiten (DTSTART/DTEND mit TZID) und ganztägige Termine.
 * - CATEGORIES-Feld wird passend zum Plugin-Farb-Mapping gesetzt.
 * - Excel-Vorlage kann direkt aus dem Admin heruntergeladen werden.
 * - SheetJS wird nur auf der Plugin-Einstellungsseite geladen (kein globales Laden).
 *
 * Changelog 3.3.0:
 * - Stale-While-Revalidate Cache-Strategie: gsh_tp_fetch_ical() liefert sofort
 *   die gespeicherten Daten (Option gsh_tp_ical_data) zurück. Ein separater
 *   Freshness-Transient (gsh_tp_ical_freshness) steuert den Refresh-Zeitpunkt.
 *   Kein Besucher wartet mehr auf IServ – der Feed wird via WP-Cron (gsh_tp_cron_refresh)
 *   non-blocking im Hintergrund geholt (gsh_tp_do_refresh()).
 * - gsh_tp_fetch_sync(): synchroner Fallback nur bei Erstinstallation (noch keine Daten).
 * - gsh_tp_schedule_refresh(): verhindert Doppel-Scheduling (120-Sek.-Schutz-Transient)
 *   und ruft spawn_cron() non-blocking auf.
 * - gsh_tp_clear_page_cache(): leert WP Super Cache / W3TC / LiteSpeed / WP Fastest Cache
 *   nach jedem erfolgreichen Feed-Refresh automatisch.
 * - Cache-Invalidierung: update_option_gsh_tp_ical_url und
 *   update_option_gsh_tp_kategorie_mapping triggern gsh_tp_clear_page_cache().
 * - gsh_tp_opt(): statischer Request-Cache für alle Plugin-Optionen (8 Keys),
 *   spart wiederholte get_option()-Aufrufe im Rendering-Pfad.
 * - gsh_tp_quartale(), gsh_tp_build_map(), gsh_tp_shortcode() nutzen gsh_tp_opt().
 * - Migration: alter Transient gsh_tp_ical_data wird beim ersten Aufruf nach
 *   Update automatisch in die permanente Option migriert.
 * - Deaktivierung räumt WP-Cron-Hook und Freshness-Transient auf.
 * - HINWEIS: Date-Index (gsh_tp_build_date_index) und O(1)-Lookup (gsh_tp_day_events)
 *   existieren seit 2.5.0 – werden hier dokumentiert aber nicht nochmals geändert.
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
 *   [gsh_terminplan]                          → Aktives Schuljahr, aktuelles Quartal
 *   [gsh_terminplan quartal="2"]              → Quartal 2 (1–4)
 *   [gsh_terminplan quartal="alle"]           → Alle Quartale mit Tab-Navigation
 *   [gsh_terminplan schuljahr="sj_2026_27"]   → Bestimmtes Schuljahr
 *   [gsh_terminplan schuljahr="entwurf"]      → Entwurf-Vorschau (nur Admins)
 *
 * Wie verwaltet man mehrere Schuljahre? (Admin → Einstellungen → GSH Terminplan)
 *   - Tab-System: Jedes Schuljahr hat einen eigenen Tab mit allen Einstellungen.
 *   - Neues Schuljahr: "+ Neues Schuljahr" Button in der Tab-Leiste.
 *   - Entwurf: Nicht-beschlossene Pläne sind nur für Admins sichtbar.
 *   - Als aktiv setzen: Setzt ein Entwurfs-Profil als das angezeigte aktive Schuljahr.
 *
 * Was muss man jährlich anpassen? (Admin → Tab des neuen Schuljahres)
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
 *   0. Schuljahr-Profile (Hilfs-Funktionen, Migration)  (ca. Zeile  333)
 *   1. Admin-Einstellungen & Tab-System                  (ca. Zeile  491)
 *   2. iCal abrufen & parsen (profil-aware)              (ca. Zeile 1900)
 *   3. Schulwochen & Quartalsgrenzen (profil-aware)      (ca. Zeile 2600)
 *   4. Kategorie-Erkennung (Farb-Mapping)                (ca. Zeile 2660)
 *   5. Shortcode / HTML-Hauptausgabe                     (ca. Zeile 2740)
 *   6. Tabellen-Rendering + Date-Index                   (ca. Zeile 2960)
 *   7. CSS (inline, alle Klassen)                        (ca. Zeile 3350)
 *   8. JavaScript (Tabs, Filter, Suche, PDF)             (ca. Zeile 3850)
 *   9. Deaktivierung & Deinstallation                    (ca. Zeile 5170)
 * ═══════════════════════════════════════════════════════════
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Direktzugriff auf die PHP-Datei blockieren (WordPress-Standard)
}

define( 'GSH_TP_VERSION',       '3.12.0' );
define( 'GSH_TP_CACHE_VERSION', 3 );       // Bei Datenstruktur-Änderungen erhöhen → alte Caches werden automatisch ignoriert
define( 'GSH_TP_SLUG',     'gsh-terminplan' );
/** Microsoft Forms-URL für das Feedback-Formular (seit 3.12.0) */
define( 'GSH_TP_FEEDBACK_URL', 'https://forms.office.com/Pages/ResponsePage.aspx?id=Xqf0g2roqUG1ZIo3PFAWcieJREeQlHtErBGesyWVvYdUOFhIUFpVVUxJM09WTkRMQjZPVVkzMEQ3Vy4u' );
define( 'GSH_TP_CACHE_KEY', 'gsh_tp_ical_data' );      // Option (nie ablaufend)
define( 'GSH_TP_BACKUP_KEY', 'gsh_tp_ical_backup' );   // Option (Notfall-Backup)
define( 'GSH_TP_FRESH_KEY', 'gsh_tp_ical_freshness' ); // Transient (Ablaufsteuerung)

/**
 * Gibt ein Lucide-Icon als inline-SVG-String zurück.
 *
 * Alle Icons stammen aus Lucide 0.395 (MIT-Lizenz, https://lucide.dev).
 * viewBox ist immer "0 0 24 24", stroke="currentColor" erbt die Textfarbe.
 *
 * @since 3.11.0
 * @param string $name   Icon-Name (siehe switch unten)
 * @param string $size   CSS-Größe, Standard "1em"
 * @param string $class  Optionale zusätzliche CSS-Klasse
 * @return string        Vollständiger <svg>-String
 */
function gsh_tp_icon( $name, $size = '1em', $class = '' ) {
    $paths = array(
        'check'          => '<polyline points="20 6 9 17 4 12"/>',
        'x'              => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'alert-triangle' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'lock'           => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'file-text'      => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>',
        'download'       => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'map-pin'        => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'bell'           => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
        'search'         => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'dice'           => '<rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/><line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="2" y1="7" x2="7" y2="7"/><line x1="2" y1="17" x2="7" y2="17"/><line x1="17" y1="7" x2="22" y2="7"/><line x1="17" y1="17" x2="22" y2="17"/>',
        'trash'          => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>',
        'link'           => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        'play'           => '<polygon points="5 3 19 12 5 21 5 3"/>',
        'refresh-cw'     => '<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>',
        'chevron-left'   => '<polyline points="15 18 9 12 15 6"/>',
        'chevron-right'  => '<polyline points="9 18 15 12 9 6"/>',
        'loader'         => '<line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/>',
        'clock'          => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
    );

    if ( ! isset( $paths[ $name ] ) ) {
        return '';
    }

    $cls = 'gtp-icon' . ( $class ? ' ' . esc_attr( $class ) : '' );
    $sz  = esc_attr( $size );

    return '<svg class="' . $cls . '" xmlns="http://www.w3.org/2000/svg"'
        . ' width="' . $sz . '" height="' . $sz . '" viewBox="0 0 24 24"'
        . ' fill="none" stroke="currentColor" stroke-width="2"'
        . ' stroke-linecap="round" stroke-linejoin="round"'
        . ' aria-hidden="true" focusable="false">'
        . $paths[ $name ]
        . '</svg>';
}

/**
 * Gibt den vollständigen Plugin-Changelog als strukturiertes Array zurück.
 *
 * Tags:
 *   FEATURE  – neue Funktion für Benutzer
 *   UX       – Verbesserung der Bedienbarkeit / Darstellung
 *   BUGFIX   – Fehlerbehebung
 *   SECURITY – Sicherheitsrelevante Änderung
 *   INFRA    – Technisch intern; für Kolleg*innen nicht direkt sichtbar
 *
 * @since 3.8.0
 * @return array[] Array von Versions-Blöcken mit 'version' und 'entries'.
 */
function gsh_tp_changelog() {
    return array(
        array(
            'version' => '3.12.0',
            'entries' => array(
                array( 'tag' => 'FEATURE', 'text' => 'Feedback-Button in der Fußzeile – Fehler, Wünsche und Lob direkt aus dem Terminplan einreichen' ),
                array( 'tag' => 'FEATURE', 'text' => 'Feedback-Modal: Typ wählen (Fehler / Wunsch / Lob / Sonstiges) und Freitext eingeben' ),
                array( 'tag' => 'UX',      'text' => 'Formular öffnet sich im neuen Tab – nur noch Senden klicken' ),
                array( 'tag' => 'UX',      'text' => 'Absenden-Button aktiviert sich erst wenn Typ und Text vollständig ausgefüllt sind' ),
            ),
        ),
        array(
            'version' => '3.11.0',
            'entries' => array(
                array( 'tag' => 'UX',    'text' => 'Alle Icons wurden vereinheitlicht: Lucide-SVGs statt Emojis – scharf auf allen Geräten und im Dark Mode' ),
                array( 'tag' => 'UX',    'text' => 'Suchfeld: Such-Icon direkt im Eingabefeld (nicht mehr im Placeholder-Text)' ),
                array( 'tag' => 'INFRA', 'text' => 'Neue Hilfsfunktion gsh_tp_icon() für konsistente Icon-Verwaltung (18 Lucide-Icons)' ),
                array( 'tag' => 'INFRA', 'text' => 'CSS Design-Tokens für Spacing und Border-Radius in :root (vereinfacht zukünftige Anpassungen)' ),
            ),
        ),
        array(
            'version' => '3.10.0',
            'entries' => array(
                array( 'tag' => 'FEATURE', 'text' => 'Onboarding-Tour: führt beim ersten Besuch durch Filter, Suche, PDF-Export und Dark Mode' ),
                array( 'tag' => 'UX',      'text' => 'Hilfe-Button (?) im Header – Tour jederzeit manuell starten' ),
                array( 'tag' => 'UX',      'text' => 'Tour-Fortschritt wird angezeigt (z. B. Schritt 2 von 5)' ),
                array( 'tag' => 'UX',      'text' => 'Nach Abschluss der Tour kein automatischer Neustart beim nächsten Besuch' ),
                array( 'tag' => 'INFRA',   'text' => 'Shepherd.js 11.2.0 wird nur auf der Terminplan-Seite geladen' ),
            ),
        ),
        array(
            'version' => '3.9.1',
            'entries' => array(
                array( 'tag' => 'BUGFIX', 'text' => 'Dark Mode: Tabellen-Kopfzeile (thead) war unsichtbar, da Hintergrundfarbe bereits dunkel war – jetzt korrekt in --gtp-surface dargestellt' ),
            ),
        ),
        array(
            'version' => '3.9.0',
            'entries' => array(
                array( 'tag' => 'FEATURE', 'text' => 'Dark Mode: Terminplan jetzt auch im dunklen Design verfügbar' ),
                array( 'tag' => 'FEATURE', 'text' => 'Theme-Switcher: Gear-Icon unten rechts – Hell, Dunkel oder System-Einstellung wählbar' ),
                array( 'tag' => 'UX',      'text' => 'Theme-Wahl wird gespeichert und beim nächsten Besuch automatisch wiederhergestellt' ),
                array( 'tag' => 'UX',      'text' => 'Modus „System" passt sich automatisch an die Geräteeinstellung (Hell/Dunkel) an' ),
                array( 'tag' => 'INFRA',   'text' => 'Dark-Mode-Farben als CSS Custom Properties in assets/css/gsh-terminplan.css' ),
            ),
        ),
        array(
            'version' => '3.8.0',
            'entries' => array(
                array( 'tag' => 'FEATURE',  'text' => 'Changelog-Modal: Versionsnummer im Frontend und Admin anklickbar' ),
                array( 'tag' => 'UX',       'text' => 'Frontend zeigt die letzten 3 Versionen mit benutzerrelevanten Änderungen' ),
                array( 'tag' => 'UX',       'text' => 'Admin zeigt vollständigen Changelog aller Versionen mit Tag-Farbcodierung' ),
                array( 'tag' => 'INFRA',    'text' => 'gsh_tp_changelog() als strukturiertes PHP-Array (Single Source of Truth)' ),
            ),
        ),
        array(
            'version' => '3.7.0',
            'entries' => array(
                array( 'tag' => 'UX',    'text' => 'Fluid-Grid-Layout ersetzt hartes 2-Stufen-Breakpoint-System' ),
                array( 'tag' => 'UX',    'text' => 'Tabellen transformieren sich im Bereich 1024–1200 px automatisch zu Cards (rein per CSS)' ),
                array( 'tag' => 'UX',    'text' => 'Flexbox-Fallback für ältere Browser ohne CSS-Grid-Support ergänzt' ),
                array( 'tag' => 'INFRA', 'text' => 'data-label-Attribute in gsh_tp_table() ergänzt (SW, Mo–Fr, Hinweise)' ),
                array( 'tag' => 'INFRA', 'text' => 'Neues Stylesheet: assets/css/gsh-terminplan.css mit wp_enqueue_style() + Cache-Busting' ),
            ),
        ),
        array(
            'version' => '3.6.4',
            'entries' => array(
                array( 'tag' => 'INFRA', 'text' => 'Cache-Key-Versionierung: stale Daten nach Plugin-Updates werden automatisch ignoriert' ),
                array( 'tag' => 'INFRA', 'text' => 'Neue Konstante GSH_TP_CACHE_VERSION (aktuell 3) für versionierte Key-Suffixe' ),
                array( 'tag' => 'INFRA', 'text' => 'Neue Hilfsfunktion gsh_tp_ck() erzeugt Keys im Format …_v3' ),
                array( 'tag' => 'INFRA', 'text' => 'gsh_tp_migrate_cache_version() bereinigt veraltete Cache-Keys einmalig via admin_init' ),
            ),
        ),
        array(
            'version' => '3.6.3',
            'entries' => array(
                array( 'tag' => 'UX', 'text' => 'Mobile: Heutige Woche deutlich sichtbarer hervorgehoben' ),
                array( 'tag' => 'UX', 'text' => 'Pulsierender Punkt im Wochenkopf der aktuellen Schulwoche' ),
                array( 'tag' => 'UX', 'text' => '„HEUTE"-Badge größer und prominenter gestaltet' ),
                array( 'tag' => 'UX', 'text' => 'Navigationsleiste zeigt „👉 Heute" wenn aktuelle Woche im Sichtfenster liegt' ),
            ),
        ),
        array(
            'version' => '3.6.2',
            'entries' => array(
                array( 'tag' => 'UX',    'text' => 'iCal-URL-Validierung mit sprechenden Fehlermeldungen im Admin-Bereich' ),
                array( 'tag' => 'UX',    'text' => 'Profil-Speicherung zeigt Warnung bei ungültiger URL statt stillem Zurücksetzen' ),
                array( 'tag' => 'INFRA', 'text' => 'Neue Funktion gsh_tp_validate_ical_url() mit detaillierten Rückgabewerten' ),
                array( 'tag' => 'INFRA', 'text' => 'URL-Eingabefeld: pattern-Attribut und 1-Sekunden-Debounce-Validierung mit Live-Feedback' ),
            ),
        ),
        array(
            'version' => '3.6.1',
            'entries' => array(
                array( 'tag' => 'UX', 'text' => 'Filter-Buttons: echter Toggle-Modus – jede Kategorie lässt sich unabhängig ein-/ausschalten' ),
                array( 'tag' => 'UX', 'text' => 'Filter-Zustand wird per localStorage seitenübergreifend gespeichert' ),
                array( 'tag' => 'UX', 'text' => 'Zähler-Label zeigt „X / Y sichtbar" wenn Kategorien ausgeblendet sind' ),
                array( 'tag' => 'UX', 'text' => 'aria-pressed-Attribut für bessere Barrierefreiheit der Filter-Buttons' ),
            ),
        ),
        array(
            'version' => '3.6.0',
            'entries' => array(
                array( 'tag' => 'FEATURE', 'text' => 'Neuer Admin-Tab „Sync-Verlauf" zeigt die letzten 20 Synchronisierungsversuche pro Schuljahr' ),
                array( 'tag' => 'UX',      'text' => 'Details pro Sync: Zeitstempel, Status, Fehlertyp, Terminanzahl, Dauer' ),
                array( 'tag' => 'UX',      'text' => 'Farb-Codierung: Grün (✓ erfolgreich), Rot (✗ Fehler)' ),
                array( 'tag' => 'INFRA',   'text' => 'Automatisches Bereinigen von Log-Einträgen älter als 30 Tage' ),
            ),
        ),
        array(
            'version' => '3.5.3',
            'entries' => array(
                array( 'tag' => 'UX', 'text' => 'Bestätigungs-Dialoge vor destruktiven Aktionen: Profil löschen, Cache leeren, Token erneuern' ),
                array( 'tag' => 'UX', 'text' => 'Verhindert versehentliches Löschen von Schuljahr-Profilen und Cache-Daten' ),
            ),
        ),
        array(
            'version' => '3.5.2',
            'entries' => array(
                array( 'tag' => 'SECURITY', 'text' => 'Kiosk-Token: Timing-sicherer Vergleich (hash_equals) gegen Timing-Angriffe' ),
                array( 'tag' => 'SECURITY', 'text' => 'Rate-Limiting: maximal 10 Kiosk-Zugriffsversuche pro IP und Stunde' ),
                array( 'tag' => 'UX',       'text' => 'Admin-Warnung bei fehlendem Kiosk-Token oder ungültiger IServ-Domain' ),
            ),
        ),
        array(
            'version' => '3.5.1',
            'entries' => array(
                array( 'tag' => 'SECURITY', 'text' => 'CSRF-Schutz: WordPress-Nonces für alle Admin-Formulare eingeführt' ),
                array( 'tag' => 'BUGFIX',   'text' => 'POST-Daten wurden ohne wp_verify_nonce() verarbeitet' ),
            ),
        ),
        array(
            'version' => '3.5.0',
            'entries' => array(
                array( 'tag' => 'FEATURE', 'text' => 'Zwei-Schuljahre-Verwaltung: Jedes Schuljahr hat ein eigenes Profil mit iCal-URL, Quartalsgrenzen und Cache' ),
                array( 'tag' => 'FEATURE', 'text' => 'Admin-Tab-System: Ein Tab pro Schuljahr + Kategorien + Kiosk & System' ),
                array( 'tag' => 'FEATURE', 'text' => 'Neues Schuljahr per Button anlegen; Frontend-Umschalter zwischen aktiven Schuljahren' ),
                array( 'tag' => 'UX',      'text' => 'Entwurfs-Modus: Nicht beschlossene Pläne nur für Admins sichtbar, mit farbigem Banner' ),
                array( 'tag' => 'INFRA',   'text' => 'Migration: Bisherige Einstellungen werden automatisch als erstes Profil übernommen' ),
            ),
        ),
        array(
            'version' => '3.4.0',
            'entries' => array(
                array( 'tag' => 'FEATURE', 'text' => 'Kategorien vollständig konfigurierbar: Anzeigename, Farben und Stichwörter pro Kategorie einstellbar' ),
                array( 'tag' => 'FEATURE', 'text' => 'Excel-zu-ICS Import-Tool: Excel-Datei hochladen, Vorschau, ICS-Download für IServ-Import' ),
                array( 'tag' => 'UX',      'text' => 'Filter-Buttons und Druck-Legende passen sich automatisch an die Kategorien an' ),
                array( 'tag' => 'INFRA',   'text' => 'CSS dynamisch aus Kategorie-Einstellungen generiert (keine hardcodierten Farbwerte mehr)' ),
            ),
        ),
        array(
            'version' => '3.3.0',
            'entries' => array(
                array( 'tag' => 'FEATURE', 'text' => 'Stale-While-Revalidate: Kalender wird sofort angezeigt, Aktualisierung läuft unsichtbar im Hintergrund' ),
                array( 'tag' => 'UX',      'text' => 'Kein Besucher wartet mehr auf IServ – der Feed wird via WP-Cron non-blocking geholt' ),
                array( 'tag' => 'INFRA',   'text' => 'Seiten-Cache (WP Super Cache, W3TC, LiteSpeed, WP Fastest Cache) wird nach Refresh automatisch geleert' ),
            ),
        ),
        array(
            'version' => '3.2.0',
            'entries' => array(
                array( 'tag' => 'FEATURE', 'text' => 'IServ-Kiosk-Modus: Passwortloser Terminplan-Zugang per Token-URL für Monitore und Infoscreens' ),
                array( 'tag' => 'UX',      'text' => 'Kiosk-URL wird automatisch im Admin angezeigt, sobald Token und Kiosk-Seite konfiguriert sind' ),
            ),
        ),
        array(
            'version' => '3.1.0',
            'entries' => array(
                array( 'tag' => 'UX', 'text' => 'PDF-Export komplett überarbeitet: A4 Querformat, professioneller Header, Seitenfußzeile auf jeder Seite' ),
                array( 'tag' => 'UX', 'text' => 'Farb-Indikator statt farbiger Hintergründe im Druck für bessere Toner-Ersparnis' ),
            ),
        ),
        array(
            'version' => '3.0.0',
            'entries' => array(
                array( 'tag' => 'UX', 'text' => 'Komplettes UI-Redesign: CSS Custom Properties, Sticky Tabs, Event-Cards mit Hover-Effekten' ),
                array( 'tag' => 'UX', 'text' => 'Event-Detail-Popup mit Backdrop-Blur; Bottom Sheet auf Mobile' ),
                array( 'tag' => 'UX', 'text' => 'Mobile iOS-Feed-Stil: Frosted-Glass-Navigation, Datum als Kreis' ),
                array( 'tag' => 'UX', 'text' => 'Textsuche mit Focus-Mode: Nicht-Treffer werden gedimmt statt versteckt' ),
            ),
        ),
        array(
            'version' => '2.5.0',
            'entries' => array(
                array( 'tag' => 'INFRA', 'text' => 'Performance: Date-Index reduziert Rendering von ~400 Events drastisch (O(1) statt O(n))' ),
            ),
        ),
        array(
            'version' => '2.3.0',
            'entries' => array(
                array( 'tag' => 'FEATURE', 'text' => 'Änderungs-Benachrichtigungen: Banner zeigt neue, geänderte und gelöschte Termine seit dem letzten Besuch' ),
                array( 'tag' => 'UX',      'text' => 'Neue und geänderte Events werden farbig hervorgehoben (pulsierender Rahmen)' ),
            ),
        ),
        array(
            'version' => '2.2.0',
            'entries' => array(
                array( 'tag' => 'FEATURE', 'text' => 'PDF-Export: Quartal oder alle Quartale als PDF speichern über Browser-Druckdialog' ),
            ),
        ),
        array(
            'version' => '2.1.0',
            'entries' => array(
                array( 'tag' => 'FEATURE', 'text' => 'Event-Detail-Popup: Klick auf Termin zeigt Titel, Datum, Uhrzeit, Ort und Beschreibung' ),
                array( 'tag' => 'UX',      'text' => 'Auf Mobile als Bottom Sheet, auf Desktop als zentriertes Modal' ),
            ),
        ),
        array(
            'version' => '2.0.1',
            'entries' => array(
                array( 'tag' => 'BUGFIX', 'text' => 'Mobile-Navigation: Pfeil-Buttons (‹/›) und 📍-Button funktionierten nicht' ),
            ),
        ),
        array(
            'version' => '2.0',
            'entries' => array(
                array( 'tag' => 'FEATURE', 'text' => 'Mobile Agenda-Ansicht: Auf Smartphones wird die Tabelle durch eine vertikale Wochenliste ersetzt' ),
                array( 'tag' => 'FEATURE', 'text' => '2-Wochen-Schiebefenster mit ‹/›-Navigation und Wisch-Geste; 📍-Button springt zur aktuellen Woche' ),
            ),
        ),
        array(
            'version' => '1.8',
            'entries' => array(
                array( 'tag' => 'SECURITY', 'text' => 'URL-Validierung nutzt wp_http_validate_url() statt einfachem Präfix-Check' ),
                array( 'tag' => 'INFRA',    'text' => 'Vollständige PHPDoc-Blöcke und Kurzanleitung für Kolleg*innen im Datei-Kopf' ),
            ),
        ),
        array(
            'version' => '1.7',
            'entries' => array(
                array( 'tag' => 'UX', 'text' => 'Vergangene Wochen ausgrauen (opacity 0.45); Hover hebt die Zeile wieder auf' ),
                array( 'tag' => 'UX', 'text' => 'Sticky Tabellenkopf: Kopfzeile klebt beim Scrollen oben' ),
            ),
        ),
        array(
            'version' => '1.6',
            'entries' => array(
                array( 'tag' => 'FEATURE', 'text' => 'Echtzeit-Textsuche: Suchfeld durchsucht alle Termine in allen Quartalen (mit Debounce)' ),
                array( 'tag' => 'UX',      'text' => 'Suchergebnis-Zeile zeigt Trefferzahl und klickbare Quartal-Links' ),
            ),
        ),
        array(
            'version' => '1.5',
            'entries' => array(
                array( 'tag' => 'UX', 'text' => 'Auto-Scroll zur aktuellen Woche beim Laden der Seite' ),
                array( 'tag' => 'UX', 'text' => 'Floating „Heute"-Button erscheint sobald die heutige Zeile aus dem Viewport gescrollt ist' ),
            ),
        ),
        array(
            'version' => '1.4',
            'entries' => array(
                array( 'tag' => 'UX', 'text' => 'Lange Termine (≥ 5 Tage) erscheinen nur in der Hinweise-Spalte, nicht in Tagesspalten' ),
            ),
        ),
        array(
            'version' => '1.3',
            'entries' => array(
                array( 'tag' => 'UX',      'text' => 'Professionelles Frontend-Design: Karten-Layout, moderne Tabs, farbige Kategorie-Filter' ),
                array( 'tag' => 'FEATURE', 'text' => 'Manuelle Synchronisierung per Button in den Einstellungen; Sync-Zeitstempel im Header' ),
            ),
        ),
        array(
            'version' => '1.2',
            'entries' => array(
                array( 'tag' => 'INFRA', 'text' => 'Druckfunktion via unsichtbarem iframe (kein Popup-Blocker-Problem)' ),
                array( 'tag' => 'INFRA', 'text' => 'Kategorie-Erkennung mit Wortgrenzen-Matching statt einfachem strpos' ),
            ),
        ),
    );
}

// Migration 3.3.0: gsh_tp_ical_data war vor 3.3.0 ein Transient.
// Beim ersten Request nach dem Update: Transient → permanente Option migrieren.
$_gsh_old = get_transient( 'gsh_tp_ical_data' );
if ( false !== $_gsh_old ) {
    if ( empty( get_option( GSH_TP_CACHE_KEY, '' ) ) ) {
        update_option( GSH_TP_CACHE_KEY, $_gsh_old, false );
        $dur = max( 300, absint( get_option( 'gsh_tp_cache_duration', 3600 ) ) );
        set_transient( GSH_TP_FRESH_KEY, time(), $dur );
    }
    delete_transient( 'gsh_tp_ical_data' );
}
unset( $_gsh_old );

// Migration 3.4.0: gsh_tp_categories wird neu eingeführt.
// Beim ersten Request nach dem Update: Default-Kategorien als permanente Option anlegen.
if ( ! get_option( 'gsh_tp_categories' ) ) {
    update_option( 'gsh_tp_categories', gsh_tp_default_categories(), true );
}

/* ================================================================
   0. SCHULJAHR-PROFILE (seit 3.5.0)
   ================================================================ */

/**
 * Liefert alle gespeicherten Schuljahr-Profile.
 *
 * @since 3.5.0
 * @return array Array von Profil-Arrays.
 */
function gsh_tp_get_profiles() {
    $raw = get_option( 'gsh_tp_profiles', array() );
    return is_array( $raw ) ? $raw : array();
}

/**
 * Liefert ein einzelnes Profil anhand seiner ID.
 *
 * @since 3.5.0
 * @param  string $profile_id Profil-ID (z.B. 'sj_2025_26').
 * @return array|null         Profil-Array oder null wenn nicht gefunden.
 */
function gsh_tp_get_profile( $profile_id ) {
    foreach ( gsh_tp_get_profiles() as $p ) {
        if ( $p['id'] === $profile_id ) {
            return $p;
        }
    }
    return null;
}

/**
 * Liefert die ID des aktiven (nicht-Entwurf) Profils.
 *
 * Sucht das erste Profil mit is_active=true und is_draft=false.
 * Fallback: erstes Profil überhaupt.
 *
 * @since 3.5.0
 * @return string Profil-ID oder leerer String wenn keine Profile existieren.
 */
function gsh_tp_active_profile_id() {
    foreach ( gsh_tp_get_profiles() as $p ) {
        if ( ! empty( $p['is_active'] ) && empty( $p['is_draft'] ) ) {
            return $p['id'];
        }
    }
    // Fallback: erstes Profil
    $profiles = gsh_tp_get_profiles();
    return $profiles ? $profiles[0]['id'] : '';
}

/**
 * Speichert das Profiles-Array in der Datenbank (autoload: true).
 *
 * @since 3.5.0
 * @param  array $profiles Bereinigte Profile.
 * @return void
 */
function gsh_tp_save_profiles( $profiles ) {
    update_option( 'gsh_tp_profiles', $profiles, true );
}

/**
 * Sanitiert ein einzelnes Profil-Array.
 *
 * Stellt sicher, dass alle Felder typsicher und sauber sind.
 * Profil-ID wird auf max. 30 Zeichen (sanitize_key) beschränkt.
 *
 * @since 3.5.0
 * @param  array $p Rohes Profil-Array.
 * @return array    Bereinigtes Profil-Array.
 */
function gsh_tp_sanitize_profile( $p ) {
    $id = substr( sanitize_key( $p['id'] ?? '' ), 0, 30 );
    if ( '' === $id ) {
        return array();
    }
    return array(
        'id'              => $id,
        'label'           => sanitize_text_field( $p['label'] ?? '' ),
        'ical_url'        => gsh_tp_sanitize_url_raw( $p['ical_url'] ?? '' ),
        'cache_duration'  => max( 300, min( 86400, absint( $p['cache_duration'] ?? 3600 ) ) ),
        'quartal_grenzen' => sanitize_textarea_field( $p['quartal_grenzen'] ?? '' ),
        'schuljahr_start' => sanitize_text_field( $p['schuljahr_start'] ?? '' ),
        'is_active'       => ! empty( $p['is_active'] ),
        'is_draft'        => ! empty( $p['is_draft'] ),
        'created'         => sanitize_text_field( $p['created'] ?? current_time( 'Y-m-d' ) ),
    );
}

/**
 * Sanitiert eine iCal-URL ohne Settings-Error-Feedback (für Profil-Speicherung).
 *
 * @since 3.5.0
 * @param  string $url Rohe URL.
 * @return string      Bereinigte HTTPS-URL oder leerer String.
 */
function gsh_tp_sanitize_url_raw( $url ) {
    $url = esc_url_raw( trim( $url ) );
    if ( empty( $url ) ) {
        return '';
    }
    if ( ! wp_http_validate_url( $url ) || strpos( $url, 'https://' ) !== 0 ) {
        return '';
    }
    return $url;
}

/**
 * Validiert eine iCal-Feed-URL und gibt ein strukturiertes Ergebnis zurück.
 *
 * Prüft in dieser Reihenfolge:
 *   1. URL leer?
 *   2. Beginnt mit https://?
 *   3. Besteht wp_http_validate_url()?
 *
 * Kein Netzwerk-Request – reine Syntax-Prüfung, damit die Funktion
 * auch im Admin-Render-Pfad ohne Performance-Kosten aufrufbar ist.
 *
 * @since 3.6.2
 * @param  string $url Rohe URL-Eingabe.
 * @return array {
 *   'valid' bool         true wenn die URL syntaktisch korrekt ist.
 *   'error' string|null  Lesbare Fehlermeldung oder null bei Erfolg.
 *   'url'   string       Bereinigte URL (esc_url_raw), ggf. leer.
 * }
 */
function gsh_tp_validate_ical_url( $url ) {
    $url = trim( $url );
    if ( empty( $url ) ) {
        return array( 'valid' => false, 'error' => 'URL ist leer', 'url' => '' );
    }
    $cleaned = esc_url_raw( $url );
    if ( strpos( $cleaned, 'https://' ) !== 0 ) {
        return array( 'valid' => false, 'error' => 'Nur HTTPS erlaubt', 'url' => $cleaned );
    }
    if ( ! wp_http_validate_url( $cleaned ) ) {
        return array( 'valid' => false, 'error' => 'Ungültige URL-Syntax', 'url' => $cleaned );
    }
    return array( 'valid' => true, 'error' => null, 'url' => $cleaned );
}

/**
 * Erzeugt einen versions-spezifischen Cache-Schlüssel.
 *
 * Format: {prefix}{pid}_v{GSH_TP_CACHE_VERSION}
 * Wenn GSH_TP_CACHE_VERSION erhöht wird, zeigen alle Lese-Calls auf neue Keys –
 * alte Daten bleiben in der DB, werden aber ignoriert und bei Migration gelöscht.
 *
 * @since 3.6.4
 * @param  string $prefix Schlüssel-Präfix inkl. Unterstrich, z.B. 'gsh_tp_ical_'.
 * @param  string $pid    Profil-ID (bereits sanitize_key'd).
 * @return string         Vollständiger Cache-Schlüssel mit Versions-Suffix.
 */
function gsh_tp_ck( $prefix, $pid ) {
    return $prefix . $pid . '_v' . GSH_TP_CACHE_VERSION;
}

/**
 * Migration 3.4.0 → 3.5.0: Legt das erste Schuljahr-Profil aus den alten Einzeloptionen an.
 *
 * Wird via admin_init aufgerufen. Läuft nur einmal (Guard: gsh_tp_profiles existiert bereits).
 * Migriert iCal-Daten, Backup und Last-Sync in profil-spezifische Options-Keys.
 *
 * @since 3.5.0
 * @return void
 */
function gsh_tp_maybe_migrate() {
    // Bereits migriert → nichts tun
    if ( get_option( 'gsh_tp_profiles' ) ) {
        return;
    }

    $default_quartal = "2025-08-25|2025-10-31\n2025-11-03|2026-02-06\n2026-02-09|2026-05-01\n2026-05-04|2026-07-17";

    $profile = array(
        'id'              => 'sj_2025_26',
        'label'           => 'Schuljahr 2025/26',
        'ical_url'        => get_option( 'gsh_tp_ical_url', '' ),
        'cache_duration'  => max( 300, absint( get_option( 'gsh_tp_cache_duration', 3600 ) ) ),
        'quartal_grenzen' => get_option( 'gsh_tp_quartal_grenzen', $default_quartal ),
        'schuljahr_start' => get_option( 'gsh_tp_schuljahr_start', '2025-08-25' ),
        'is_active'       => true,
        'is_draft'        => false,
        'created'         => current_time( 'Y-m-d' ),
    );
    update_option( 'gsh_tp_profiles', array( $profile ), true );

    // iCal-Daten in profil-spezifische Keys migrieren
    $old_data = get_option( 'gsh_tp_ical_data', '' );
    if ( $old_data ) {
        update_option( 'gsh_tp_ical_sj_2025_26', $old_data, false );
    }
    $old_backup = get_option( 'gsh_tp_ical_backup', '' );
    if ( $old_backup ) {
        update_option( 'gsh_tp_backup_sj_2025_26', $old_backup, false );
    }
    $old_sync = get_option( 'gsh_tp_last_sync', '' );
    if ( $old_sync ) {
        update_option( 'gsh_tp_sync_sj_2025_26', $old_sync );
    }
}
add_action( 'admin_init', 'gsh_tp_maybe_migrate' );

/**
 * Löscht alle Cache-Keys einer bestimmten Schema-Version über alle Profile.
 *
 * Wird von gsh_tp_migrate_cache_version() genutzt um nach einem Versions-Sprung
 * die alten, nun ungültigen Einträge aus der Datenbank zu entfernen.
 *
 * @since 3.6.4
 * @param  int $version Die zu löschende Cache-Schema-Version (z.B. 2).
 * @return void
 */
function gsh_tp_clear_version_caches( $version ) {
    $sfx = '_v' . (int) $version;
    foreach ( gsh_tp_get_profiles() as $p ) {
        $pid = sanitize_key( $p['id'] );
        delete_option( 'gsh_tp_ical_' . $pid . $sfx );
        delete_option( 'gsh_tp_sync_logs_' . $pid . $sfx );
        delete_transient( 'gsh_tp_fresh_' . $pid . $sfx );
        delete_transient( 'gsh_tp_chg_' . $pid . $sfx );
    }
}

/**
 * Einmalige Migration beim Plugin-Update: Löscht veraltete Cache-Keys und
 * trägt die aktuelle Schema-Version in gsh_tp_cache_ver ein.
 *
 * Läuft via admin_init. Bei v0 → v3 (Ersteinführung der Versionierung) werden
 * die alten Keys ohne Suffix entfernt, sodass ein Fresh-Fetch ausgelöst wird.
 * Bei späteren Updates (z.B. v3 → v4) werden die alten _v3-Keys bereinigt.
 *
 * @since 3.6.4
 * @return void
 */
function gsh_tp_migrate_cache_version() {
    $stored_ver = (int) get_option( 'gsh_tp_cache_ver', 0 );
    if ( $stored_ver === GSH_TP_CACHE_VERSION ) {
        return; // Keine Migration nötig
    }

    foreach ( gsh_tp_get_profiles() as $p ) {
        $pid = sanitize_key( $p['id'] );
        if ( 0 === $stored_ver ) {
            // Erste Versionierung (3.6.4): unversionierte Legacy-Keys entfernen
            delete_option( 'gsh_tp_ical_' . $pid );
            delete_option( 'gsh_tp_sync_logs_' . $pid );
            delete_transient( 'gsh_tp_fresh_' . $pid );
            delete_transient( 'gsh_tp_chg_' . $pid );
        } else {
            // Spätere Updates: alle Zwischenversionen bereinigen
            for ( $v = $stored_ver; $v < GSH_TP_CACHE_VERSION; $v++ ) {
                gsh_tp_clear_version_caches( $v );
            }
        }
    }

    update_option( 'gsh_tp_cache_ver', GSH_TP_CACHE_VERSION );
}
add_action( 'admin_init', 'gsh_tp_migrate_cache_version' );

/**
 * Prüft den Kiosk-Zugriff per Token mit IP-basiertem Rate-Limiting.
 *
 * Vergleicht den übergebenen Token timing-sicher (hash_equals) mit dem gespeicherten
 * Token. Verhindert Brute-Force-Angriffe: Nach 10 Fehlversuchen von derselben IP
 * innerhalb einer Stunde wird der Zugriff blockiert.
 *
 * Aufruf aus dem Page-Template page-terminplan-kiosk.php:
 *   $token = sanitize_text_field( $_GET['token'] ?? '' );
 *   if ( ! gsh_tp_check_kiosk_access( $token ) ) { status_header( 403 ); exit; }
 *
 * @since 3.5.2
 * @param  string $token Der vom Nutzer übergebene Token (?token= URL-Parameter).
 * @return bool          true bei gültigem Token, false bei falschem Token oder Rate-Limit.
 */
function gsh_tp_check_kiosk_access( $token ) {
    $saved_token = get_option( 'gsh_tp_kiosk_token', '' );

    // Kein Token konfiguriert → Zugriff grundsätzlich verweigern
    if ( empty( $saved_token ) ) {
        return false;
    }

    // IP ermitteln – REMOTE_ADDR ist verlässlicher als X-Forwarded-For
    $ip       = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
    $rate_key = 'gsh_tp_kiosk_rl_' . md5( $ip );
    $attempts = (int) get_transient( $rate_key );

    // Rate-Limit: max. 10 Fehlversuche pro Stunde und IP
    if ( $attempts >= 10 ) {
        return false;
    }

    // Timing-sicherer Vergleich (verhindert Timing-Angriffe)
    if ( ! hash_equals( $saved_token, (string) $token ) ) {
        // Fehlversuch zählen; Transient läuft nach 1 Stunde automatisch ab
        set_transient( $rate_key, $attempts + 1, HOUR_IN_SECONDS );
        return false;
    }

    return true;
}

/**
 * Speichert einen Sync-Versuch im Sync-Log eines Profils.
 *
 * Hält die letzten 50 Einträge pro Profil in einer Datenbank-Option vor.
 * Ältere Einträge werden automatisch entfernt (FIFO).
 *
 * @since 3.6.0
 * @param string $profile_id Profil-ID.
 * @param string $status     'success' oder 'error'.
 * @param array  $details    Optional: error_type, event_count, duration_ms, message.
 * @return void
 */
function gsh_tp_log_sync_attempt( $profile_id, $status, $details = array() ) {
    $pid     = sanitize_key( $profile_id );
    $log_key = gsh_tp_ck( 'gsh_tp_sync_logs_', $pid );
    $logs    = get_option( $log_key, array() );
    if ( ! is_array( $logs ) ) {
        $logs = array();
    }
    array_unshift( $logs, array(
        'timestamp'   => gmdate( 'Y-m-d H:i:s' ),
        'status'      => ( 'success' === $status ) ? 'success' : 'error',
        'error_type'  => sanitize_key( $details['error_type'] ?? '' ),
        'event_count' => absint( $details['event_count'] ?? 0 ),
        'duration_ms' => absint( $details['duration_ms'] ?? 0 ),
        'message'     => sanitize_text_field( $details['message'] ?? '' ),
    ) );
    $logs = array_slice( $logs, 0, 50 ); // max. 50 Einträge vorhalten
    update_option( $log_key, $logs, false );
}

/**
 * Gibt die letzten Sync-Log-Einträge eines Profils zurück.
 *
 * @since 3.6.0
 * @param string $profile_id Profil-ID.
 * @param int    $limit      Max. Anzahl zurückgegebener Einträge (Standard: 20).
 * @return array             Array von Log-Einträgen, neueste zuerst.
 */
function gsh_tp_get_sync_logs( $profile_id, $limit = 20 ) {
    $pid  = sanitize_key( $profile_id );
    $logs = get_option( gsh_tp_ck( 'gsh_tp_sync_logs_', $pid ), array() );
    return is_array( $logs ) ? array_slice( $logs, 0, max( 1, (int) $limit ) ) : array();
}

/**
 * Entfernt Sync-Log-Einträge, die älter als $days Tage sind.
 *
 * Läuft über alle bekannten Profile und bereinigt deren Logs.
 *
 * @since 3.6.0
 * @param int $days Einträge älter als dieser Wert (in Tagen) werden gelöscht.
 * @return int      Anzahl entfernter Einträge über alle Profile.
 */
function gsh_tp_clear_old_logs( $days = 30 ) {
    $cutoff  = gmdate( 'Y-m-d H:i:s', time() - absint( $days ) * DAY_IN_SECONDS );
    $removed = 0;
    foreach ( gsh_tp_get_profiles() as $p ) {
        $pid     = sanitize_key( $p['id'] );
        $log_key = gsh_tp_ck( 'gsh_tp_sync_logs_', $pid );
        $logs    = get_option( $log_key, array() );
        if ( ! is_array( $logs ) ) {
            continue;
        }
        $filtered = array_values( array_filter( $logs, function ( $entry ) use ( $cutoff ) {
            return isset( $entry['timestamp'] ) && $entry['timestamp'] >= $cutoff;
        } ) );
        $removed += count( $logs ) - count( $filtered );
        update_option( $log_key, $filtered, false );
    }
    return $removed;
}

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
 * Bindet das externe Frontend-Stylesheet des Plugins ein.
 *
 * Lädt assets/css/gsh-terminplan.css auf allen öffentlichen Seiten.
 * Die Versionsnummer GSH_TP_VERSION sorgt für automatisches Cache-Busting
 * bei jedem Plugin-Update.
 *
 * @since 3.7.0
 * @return void
 */
function gsh_tp_enqueue_frontend_styles() {
    wp_enqueue_style(
        'gsh-terminplan',
        plugin_dir_url( __FILE__ ) . 'assets/css/gsh-terminplan.css',
        array(),
        GSH_TP_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'gsh_tp_enqueue_frontend_styles' );

/**
 * Shepherd.js für die Onboarding-Tour laden (nur auf der Terminplan-Seite).
 *
 * Shepherd.js wird von cdnjs geladen – dieselbe Quelle wie SheetJS im Admin.
 * CSS und JS werden nur eingebunden wenn der Shortcode auf der Seite vorhanden ist.
 *
 * @since 3.10.0
 * @return void
 */
function gsh_tp_enqueue_tour_assets() {
    global $post;
    if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'gsh_terminplan' ) ) {
        return;
    }

    // Shepherd.js CSS
    wp_enqueue_style(
        'shepherd-js',
        'https://cdnjs.cloudflare.com/ajax/libs/shepherd.js/11.2.0/css/shepherd.min.css',
        array(),
        '11.2.0'
    );

    // Shepherd.js Script (im Footer laden – nach dem Plugin-JS)
    wp_enqueue_script(
        'shepherd-js',
        'https://cdnjs.cloudflare.com/ajax/libs/shepherd.js/11.2.0/js/shepherd.min.js',
        array(),
        '11.2.0',
        true // im Footer
    );
}
add_action( 'wp_enqueue_scripts', 'gsh_tp_enqueue_tour_assets' );

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
// Hintergrund-Refresh via WP-Cron (non-blocking, kein Besucher wartet)
add_action( 'gsh_tp_cron_refresh', 'gsh_tp_do_refresh' );
// Seiten-Cache leeren wenn relevante Optionen geändert werden
add_action( 'update_option_gsh_tp_ical_url',          'gsh_tp_clear_page_cache' );
add_action( 'update_option_gsh_tp_kategorie_mapping',  'gsh_tp_clear_page_cache' );
add_action( 'update_option_gsh_tp_categories',         'gsh_tp_clear_page_cache' );

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
    register_setting( 'gsh_tp_options', 'gsh_tp_categories', array(
        'sanitize_callback' => 'gsh_tp_sanitize_categories',
        'default'           => gsh_tp_default_categories(),
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
 * Liefert die Standard-Kategoriendefinitionen als Array.
 *
 * Wird als Fallback genutzt, wenn gsh_tp_categories noch nicht in der
 * Datenbank gespeichert ist (Erstinstallation, Migration).
 *
 * @since 3.4.0
 * @return array Array von Kategorie-Arrays mit slug, label, bg, border, text, keywords, order.
 */
function gsh_tp_default_categories() {
    return array(
        array(
            'slug'     => 'konferenz',
            'label'    => 'Konferenzen',
            'bg'       => '#dbeafe',
            'border'   => '#3b82f6',
            'text'     => '#1e40af',
            'keywords' => array( 'Konferenz', 'LK', 'FaKo', 'Fachkonferenz', 'TBS',
                'Dienstbesprechung', 'DB', 'PK', 'ZK', 'SK', 'SchiLF', 'Pflegschaft',
                'Schulkonferenz', 'Teamsitzung', 'Teamtag', 'Teamnachmittag', 'Studientag' ),
            'order'    => 1,
        ),
        array(
            'slug'     => 'pruefung',
            'label'    => 'Prüfungen',
            'bg'       => '#fee2e2',
            'border'   => '#ef4444',
            'text'     => '#991b1b',
            'keywords' => array( 'Prüfung', 'Pruefung', 'ZP', 'Abi', 'Klausur', 'Vera',
                'DST', 'ZKL', 'mAP', 'ZAA', 'Sprachstandstest', 'Nachprüfung' ),
            'order'    => 2,
        ),
        array(
            'slug'     => 'projekt',
            'label'    => 'Projekte',
            'bg'       => '#dcfce7',
            'border'   => '#22c55e',
            'text'     => '#166534',
            'keywords' => array( 'Projekt', 'ProWo', 'Wandertag', 'Sportspektakel', 'Praktikum',
                'SV-Fahrt', 'Studienfahrt', 'Abschlussfahrt', 'Fahrtenwoche', 'Exkursion',
                'Turnier', 'Olympiade', 'Sponsorenlauf', 'Friedenstag', 'Antirassismustag',
                'Abifeier', 'Entlassfeier', 'Horst forscht' ),
            'order'    => 3,
        ),
        array(
            'slug'     => 'frei',
            'label'    => 'Ferien / Frei',
            'bg'       => '#f1f5f9',
            'border'   => '#94a3b8',
            'text'     => '#475569',
            'keywords' => array( 'Frei', 'Ferien', 'Feiertag', 'Ferientag', 'Rosenmontag',
                'Weihnacht', 'Pfingsten', 'Himmelfahrt', 'Fronleichnam', 'Maifeiertag',
                'Tag der Deutschen Einheit' ),
            'order'    => 4,
        ),
        array(
            'slug'     => 'eltern',
            'label'    => 'Eltern / Beratung',
            'bg'       => '#ffedd5',
            'border'   => '#f97316',
            'text'     => '#9a3412',
            'keywords' => array( 'Eltern', 'Beratung', 'Info-Abend', 'Infoabend',
                'Potenzialanalyse', 'KAoA', 'Kurswahl', 'Anmeldung',
                'Tag der offenen Tür', 'Schnuppertag', 'Berufemarkt', 'Lernberatungstag' ),
            'order'    => 5,
        ),
        array(
            'slug'     => 'frist',
            'label'    => 'Fristen / Hinweise',
            'bg'       => '#fef9c3',
            'border'   => '#eab308',
            'text'     => '#713f12',
            'keywords' => array( 'Frist', 'Noten', 'Fachkommentar', 'Berichtszeugnis',
                'Zeugnis', 'Evaluation', 'Foerderplaene', 'Förderpläne', 'Meldung mAP' ),
            'order'    => 6,
        ),
    );
}

/**
 * Sanitize-Callback für die gsh_tp_categories Option.
 *
 * Prüft jeden Eintrag auf gültige Werte, bereinigt Slugs (sanitize_key),
 * Farben (Hex-Regex), Labels (sanitize_text_field) und Keywords.
 * Erzwingt Uniqueness der Slugs und limitiert auf 20 Kategorien.
 *
 * @since 3.4.0
 * @param  mixed $input Roheingabe aus dem POST.
 * @return array        Bereinigtes Kategorie-Array.
 */
function gsh_tp_sanitize_categories( $input ) {
    if ( ! is_array( $input ) ) {
        return gsh_tp_default_categories();
    }
    $clean = array();
    $slugs = array();
    $hex   = '/^#[0-9a-fA-F]{6}$/';
    foreach ( $input as $i => $cat ) {
        if ( count( $clean ) >= 20 ) {
            break; // Maximal 20 Kategorien
        }
        $label = sanitize_text_field( $cat['label'] ?? '' );
        if ( '' === $label ) {
            continue;
        }
        $slug = sanitize_key( $cat['slug'] ?? $label );
        if ( '' === $slug || in_array( $slug, $slugs, true ) ) {
            $slug = sanitize_key( $label ) . '_' . count( $clean );
        }
        $slugs[] = $slug;
        $bg     = preg_match( $hex, $cat['bg'] ?? '' )     ? $cat['bg']     : '#f0f0f0';
        $border = preg_match( $hex, $cat['border'] ?? '' ) ? $cat['border'] : '#cccccc';
        $text   = preg_match( $hex, $cat['text'] ?? '' )   ? $cat['text']   : '#333333';
        $kw_raw   = sanitize_text_field( $cat['keywords'] ?? '' );
        $keywords = array_values( array_filter( array_map( 'trim', explode( ',', $kw_raw ) ) ) );
        $order    = absint( $cat['order'] ?? ( $i + 1 ) );
        $clean[]  = compact( 'slug', 'label', 'bg', 'border', 'text', 'keywords', 'order' );
    }
    if ( empty( $clean ) ) {
        return gsh_tp_default_categories();
    }
    usort( $clean, static function ( $a, $b ) {
        return $a['order'] <=> $b['order'];
    } );
    return $clean;
}

/**
 * Liefert die konfigurierten Kategorien aus der Datenbank (gecacht).
 *
 * Liest gsh_tp_categories einmalig pro Request, sortiert nach order,
 * fällt auf gsh_tp_default_categories() zurück wenn leer.
 *
 * @since 3.4.0
 * @return array Sortiertes Kategorie-Array.
 */
function gsh_tp_get_categories() {
    static $cats = null;
    if ( null === $cats ) {
        $raw  = get_option( 'gsh_tp_categories', array() );
        $cats = ( is_array( $raw ) && ! empty( $raw ) ) ? $raw : gsh_tp_default_categories();
        usort( $cats, static function ( $a, $b ) {
            return ( $a['order'] ?? 0 ) <=> ( $b['order'] ?? 0 );
        } );
    }
    return $cats;
}

/**
 * Erzeugt dynamisches CSS für alle konfigurierten Kategorien.
 *
 * Gibt ein Array mit zwei Strings zurück:
 * - 'vars':  CSS Custom Properties für :root (--c-{slug}-bg etc.)
 * - 'rules': CSS-Regeln für .gc-{slug}, .gtp-fb[data-c] und .gtp-popup-cat
 *
 * @since 3.4.0
 * @return array{vars: string, rules: string}
 */
function gsh_tp_category_css() {
    $cats = gsh_tp_get_categories();
    $vars  = '';
    $rules = '';
    foreach ( $cats as $c ) {
        $s      = esc_attr( $c['slug'] );
        $bg     = esc_attr( $c['bg'] );
        $bd     = esc_attr( $c['border'] );
        $tx     = esc_attr( $c['text'] );
        $vars  .= "--c-{$s}-bg:{$bg};--c-{$s}-bd:{$bd};--c-{$s}-tx:{$tx};";
        $rules .= ".gc-{$s}{background:var(--c-{$s}-bg);border-left-color:var(--c-{$s}-bd);color:var(--c-{$s}-tx)}";
        $rules .= ".gtp-fb[data-c=\"{$s}\"]{border-color:var(--c-{$s}-bd);background:var(--c-{$s}-bg);color:var(--c-{$s}-tx)}";
        $rules .= ".gtp-popup-cat.gc-{$s}{background:var(--c-{$s}-bg);color:var(--c-{$s}-tx)}";
    }
    return array( 'vars' => $vars, 'rules' => $rules );
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

/**
 * Liefert eine Plugin-Option aus einem statischen Request-Cache.
 *
 * Profil-unabhängige Keys (kiosk_token, iserv_domain, categories, kat_mapping)
 * werden einmalig geladen und gecacht.
 * Profil-abhängige Keys (ical_url, cache_duration, schuljahr_start, quartal_grenzen,
 * last_sync) werden aus dem jeweiligen Profil-Eintrag gelesen.
 *
 * @since 3.3.0 (profil-aware seit 3.5.0)
 * @param  string $key        Kurzschlüssel (z.B. 'schuljahr_start').
 * @param  mixed  $default    Fallback wenn Schlüssel unbekannt.
 * @param  string $profile_id Optionale Profil-ID; leer = aktives Profil.
 * @return mixed              Optionswert.
 */
function gsh_tp_opt( $key, $default = '', $profile_id = '' ) {
    // Profil-unabhängige Keys – einmalig pro Request laden
    static $global = null;
    if ( null === $global ) {
        $global = array(
            'kat_mapping'  => get_option( 'gsh_tp_kategorie_mapping', '' ),
            'categories'   => get_option( 'gsh_tp_categories', array() ),
            'kiosk_token'  => get_option( 'gsh_tp_kiosk_token', '' ),
            'iserv_domain' => get_option( 'gsh_tp_iserv_domain', '' ),
        );
    }
    if ( isset( $global[ $key ] ) ) {
        return $global[ $key ];
    }

    // Profil-abhängige Keys aus dem Profil-Array lesen
    if ( ! $profile_id ) {
        $profile_id = gsh_tp_active_profile_id();
    }
    $profile = gsh_tp_get_profile( $profile_id );

    // Profil nicht gefunden – Fallback auf alte Einzel-Options (rückwärtskompatibel)
    if ( ! $profile ) {
        $legacy = array(
            'ical_url'        => get_option( 'gsh_tp_ical_url', '' ),
            'cache_duration'  => max( 300, absint( get_option( 'gsh_tp_cache_duration', 3600 ) ) ),
            'schuljahr_start' => get_option( 'gsh_tp_schuljahr_start', '2025-08-25' ),
            'quartal_grenzen' => get_option( 'gsh_tp_quartal_grenzen',
                "2025-08-25|2025-10-31\n2025-11-03|2026-02-06\n2026-02-09|2026-05-01\n2026-05-04|2026-07-17" ),
            'last_sync'       => get_option( 'gsh_tp_last_sync', '' ),
        );
        return isset( $legacy[ $key ] ) ? $legacy[ $key ] : $default;
    }

    $profil_map = array(
        'ical_url'        => 'ical_url',
        'cache_duration'  => 'cache_duration',
        'schuljahr_start' => 'schuljahr_start',
        'quartal_grenzen' => 'quartal_grenzen',
    );
    if ( isset( $profil_map[ $key ] ) ) {
        return isset( $profile[ $profil_map[ $key ] ] ) ? $profile[ $profil_map[ $key ] ] : $default;
    }
    // Last-Sync wird als separate Option pro Profil gespeichert
    if ( 'last_sync' === $key ) {
        return get_option( 'gsh_tp_sync_' . sanitize_key( $profile_id ), '' );
    }

    return $default;
}

/* ── Admin-Seite ── */

/**
 * Rendert die Einstellungsseite mit WordPress-nativem Tab-System.
 *
 * Verarbeitet alle Profil-POST-Aktionen (anlegen, speichern, löschen, aktivieren)
 * sowie Sync- und Cache-Aktionen, jeweils mit eigenem Nonce-Schutz und
 * manage_options Capability-Check. Anschließend wird die Tab-Navigation
 * ausgegeben und die passende Render-Funktion aufgerufen.
 *
 * @since 1.2.0 (Tab-System seit 3.5.0)
 * @return void
 */
function gsh_tp_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Zugriff verweigert.' );
    }

    $profiles = gsh_tp_get_profiles();
    if ( empty( $profiles ) ) {
        gsh_tp_maybe_migrate();
        $profiles = gsh_tp_get_profiles();
    }

    // ── POST: Neues Schuljahr-Profil anlegen ──
    if ( isset( $_POST['gsh_tp_new_profile'] ) ) {
        if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_np_n'] ?? '' ) ), 'gsh_tp_new_profile' ) ) {
            if ( count( $profiles ) < 5 ) {
                $year   = (int) date( 'Y' );
                $new_id = 'sj_' . $year . '_' . ( $year + 1 );
                $exist  = array_column( $profiles, 'id' );
                if ( in_array( $new_id, $exist, true ) ) {
                    $new_id = $new_id . '_2';
                }
                $active = gsh_tp_get_profile( gsh_tp_active_profile_id() );
                $profiles[] = array(
                    'id'              => sanitize_key( $new_id ),
                    'label'           => 'Schuljahr ' . $year . '/' . ( $year + 1 ),
                    'ical_url'        => $active['ical_url'] ?? '',
                    'cache_duration'  => $active['cache_duration'] ?? 3600,
                    'quartal_grenzen' => $active['quartal_grenzen'] ?? '',
                    'schuljahr_start' => $active['schuljahr_start'] ?? '',
                    'is_active'       => false,
                    'is_draft'        => true,
                    'created'         => current_time( 'Y-m-d' ),
                );
                gsh_tp_save_profiles( $profiles );
                $profiles = gsh_tp_get_profiles();
                echo '<div class="notice notice-success"><p>Neues Schuljahr-Profil <strong>'
                   . esc_html( 'Schuljahr ' . $year . '/' . ( $year + 1 ) )
                   . '</strong> als Entwurf angelegt.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>Sicherheitspr&uuml;fung fehlgeschlagen.</p></div>';
        }
    }

    // ── POST: Profil speichern ──
    if ( isset( $_POST['gsh_tp_save_profile'] ) ) {
        if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_sp_n'] ?? '' ) ), 'gsh_tp_profile_save' ) ) {
            $save_id    = sanitize_key( $_POST['gsh_tp_profile_id'] ?? '' );
            $raw_data   = $_POST['gsh_tp_profile'][ $save_id ] ?? array();
            $url_check  = gsh_tp_validate_ical_url( $raw_data['ical_url'] ?? '' );
            $profiles   = array_map( function ( $p ) use ( $save_id, $raw_data, $url_check ) {
                if ( $p['id'] !== $save_id ) {
                    return $p;
                }
                return array_merge( $p, array(
                    'label'           => sanitize_text_field( $raw_data['label'] ?? $p['label'] ),
                    'ical_url'        => $url_check['url'], // bereits bereinigt
                    'cache_duration'  => max( 300, min( 86400, absint( $raw_data['cache_duration'] ?? 3600 ) ) ),
                    'quartal_grenzen' => sanitize_textarea_field( $raw_data['quartal_grenzen'] ?? '' ),
                    'schuljahr_start' => sanitize_text_field( $raw_data['schuljahr_start'] ?? '' ),
                    'is_active'       => $p['is_active'], // Aktiv-Status nur via activate_profile ändern
                    'is_draft'        => ! empty( $raw_data['is_draft'] ),
                ) );
            }, $profiles );
            gsh_tp_save_profiles( $profiles );
            $profiles = gsh_tp_get_profiles();
            if ( ! $url_check['valid'] && ! empty( $raw_data['ical_url'] ) ) {
                echo '<div class="notice notice-warning"><p>' . gsh_tp_icon( 'alert-triangle' ) . ' Profil gespeichert, aber die iCal-URL ist ung&uuml;ltig: <strong>'
                   . esc_html( $url_check['error'] ) . '</strong>. Die URL wurde nicht &uuml;bernommen.</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>' . gsh_tp_icon( 'check' ) . ' Profil gespeichert.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>Sicherheitspr&uuml;fung fehlgeschlagen.</p></div>';
        }
    }

    // ── POST: Profil aktivieren ──
    if ( isset( $_POST['gsh_tp_activate_profile'] ) ) {
        if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_ap_n'] ?? '' ) ), 'gsh_tp_activate_profile' ) ) {
            $act_id   = sanitize_key( $_POST['gsh_tp_profile_id'] ?? '' );
            $profiles = array_map( function ( $p ) use ( $act_id ) {
                $p['is_active'] = ( $p['id'] === $act_id );
                if ( $p['id'] === $act_id ) {
                    $p['is_draft'] = false;
                }
                return $p;
            }, $profiles );
            gsh_tp_save_profiles( $profiles );
            $profiles = gsh_tp_get_profiles();
            echo '<div class="notice notice-success"><p>' . gsh_tp_icon( 'check' ) . ' Profil als aktiv gesetzt.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Sicherheitspr&uuml;fung fehlgeschlagen.</p></div>';
        }
    }

    // ── POST: Profil löschen ──
    if ( isset( $_POST['gsh_tp_delete_profile'] ) ) {
        if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_dp_n'] ?? '' ) ), 'gsh_tp_delete_profile' ) ) {
            $del_id   = sanitize_key( $_POST['gsh_tp_profile_id'] ?? '' );
            $del_prof = gsh_tp_get_profile( $del_id );
            if ( count( $profiles ) <= 1 ) {
                echo '<div class="notice notice-error"><p>Das letzte Profil kann nicht gel&ouml;scht werden.</p></div>';
            } elseif ( ! $del_prof || ! empty( $del_prof['is_active'] ) ) {
                echo '<div class="notice notice-error"><p>Das aktive Profil kann nicht gel&ouml;scht werden.</p></div>';
            } else {
                $pid = sanitize_key( $del_id );
                // Versionierte Keys (aktuell und eventuelle Vorgänger)
                delete_option( gsh_tp_ck( 'gsh_tp_ical_', $pid ) );
                delete_option( gsh_tp_ck( 'gsh_tp_sync_logs_', $pid ) );
                delete_transient( gsh_tp_ck( 'gsh_tp_fresh_', $pid ) );
                delete_transient( gsh_tp_ck( 'gsh_tp_chg_', $pid ) );
                // Unversionierte Keys (Backup, Sync-Zeitstempel, Snapshot, Guard)
                delete_option( 'gsh_tp_backup_' . $pid );
                delete_option( 'gsh_tp_sync_' . $pid );
                delete_transient( 'gsh_tp_snap_' . $pid );
                $profiles = array_values( array_filter( $profiles, function ( $p ) use ( $del_id ) {
                    return $p['id'] !== $del_id;
                } ) );
                gsh_tp_save_profiles( $profiles );
                wp_safe_redirect( admin_url( 'options-general.php?page=gsh-terminplan' ) );
                exit;
            }
        } else {
            echo '<div class="notice notice-error"><p>Sicherheitspr&uuml;fung fehlgeschlagen.</p></div>';
        }
    }

    // ── POST: Kalender synchronisieren ──
    if ( isset( $_POST['gsh_tp_sync'] ) ) {
        if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_sn'] ?? '' ) ), 'gsh_tp_sync_manual' ) ) {
            $sync_pid = sanitize_key( $_POST['gsh_tp_sync_pid'] ?? gsh_tp_active_profile_id() );
            // Whitelist-Check: muss ein bekanntes Profil sein
            $known = array_column( $profiles, 'id' );
            if ( ! in_array( $sync_pid, $known, true ) ) {
                $sync_pid = gsh_tp_active_profile_id();
            }
            $ok = gsh_tp_do_refresh( $sync_pid );
            if ( $ok ) {
                echo '<div class="notice notice-success"><p>' . gsh_tp_icon( 'check' ) . ' Kalender erfolgreich synchronisiert ('
                   . esc_html( wp_date( 'd.m.Y, H:i' ) ) . ' Uhr).</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . gsh_tp_icon( 'x' ) . ' Synchronisierung fehlgeschlagen &ndash; '
                   . 'bitte die iCal-URL pr&uuml;fen oder warten, bis der IServ-Server erreichbar ist.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>Sicherheitspr&uuml;fung fehlgeschlagen.</p></div>';
        }
    }

    // ── POST: Cache leeren ──
    if ( isset( $_POST['gsh_tp_cc'] ) ) {
        if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_cn'] ?? '' ) ), 'gsh_tp_cc' ) ) {
            $cc_pid = sanitize_key( $_POST['gsh_tp_cc_pid'] ?? '' );
            $known  = array_column( $profiles, 'id' );
            if ( $cc_pid && in_array( $cc_pid, $known, true ) ) {
                delete_transient( gsh_tp_ck( 'gsh_tp_fresh_', $cc_pid ) );
            } else {
                foreach ( $profiles as $p ) {
                    delete_transient( gsh_tp_ck( 'gsh_tp_fresh_', sanitize_key( $p['id'] ) ) );
                }
            }
            echo '<div class="notice notice-success"><p>Cache als veraltet markiert &ndash; '
               . 'wird beim n&auml;chsten Seitenaufruf im Hintergrund aktualisiert.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Sicherheitspr&uuml;fung fehlgeschlagen.</p></div>';
        }
    }

    // ── POST: Kategorien speichern ──
    if ( isset( $_POST['gsh_tp_save_categories'] ) ) {
        if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_cats_n'] ?? '' ) ), 'gsh_tp_categories_save' ) ) {
            $raw_cats = isset( $_POST['gsh_tp_categories'] ) ? (array) $_POST['gsh_tp_categories'] : array();
            update_option( 'gsh_tp_categories', gsh_tp_sanitize_categories( $raw_cats ) );
            echo '<div class="notice notice-success"><p>' . gsh_tp_icon( 'check' ) . ' Kategorien gespeichert.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Sicherheitspr&uuml;fung fehlgeschlagen.</p></div>';
        }
    }

    // ── POST: Alte Sync-Logs löschen ──
    if ( isset( $_POST['gsh_tp_clear_logs'] ) ) {
        if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_cl_n'] ?? '' ) ), 'gsh_tp_clear_logs' ) ) {
            $removed = gsh_tp_clear_old_logs( 30 );
            echo '<div class="notice notice-success"><p>' . gsh_tp_icon( 'check' ) . ' ' . (int) $removed
               . ' veraltete Log-Eintr&auml;ge gel&ouml;scht.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Sicherheitspr&uuml;fung fehlgeschlagen.</p></div>';
        }
    }

    // ── Tabs aufbauen ──
    $tabs = array();
    foreach ( $profiles as $p ) {
        $suffix = '';
        if ( ! empty( $p['is_draft'] ) ) {
            $suffix = ' (Entwurf)';
        } elseif ( ! empty( $p['is_active'] ) ) {
            $suffix = ' (aktiv)';
        }
        $tabs[ $p['id'] ] = esc_html( $p['label'] ) . $suffix;
    }
    $tabs['_kategorien'] = 'Kategorien';
    $tabs['_system']     = 'Kiosk &amp; System';
    $tabs['_sync_log']   = 'Sync-Verlauf';

    // Tab aus URL-Parameter lesen – Whitelist-Check gegen $tabs
    $active_tab = sanitize_key( $_GET['tab'] ?? '' );
    if ( ! array_key_exists( $active_tab, $tabs ) ) {
        $active_tab = array_key_first( $tabs );
    }
    ?>
    <div class="wrap">
        <h1>GSH Terminplan &ndash; Einstellungen
            <button type="button"
                    id="gsh-admin-cl-btn"
                    onclick="gshAdminChangelogOpen()"
                    style="font-size:13px;font-weight:400;margin-left:10px;vertical-align:middle;
                           cursor:pointer;border:1px solid #c3c4c7;border-radius:4px;
                           padding:2px 10px;background:#f6f7f7;color:#3c434a">
                v<?php echo esc_html( GSH_TP_VERSION ); ?> &#x25BE; Changelog
            </button>
        </h1>

        <!-- Admin-Changelog-Modal -->
        <div id="gshAdminChangelog"
             role="dialog" aria-modal="true" aria-label="Vollständiger Changelog"
             style="display:none;position:fixed;inset:0;z-index:100000;
                    background:rgba(0,0,0,.55);overflow-y:auto">
            <div style="background:#fff;max-width:680px;margin:60px auto;border-radius:8px;
                        padding:28px 32px;position:relative;max-height:80vh;overflow-y:auto">
                <button type="button"
                        onclick="gshAdminChangelogClose()"
                        aria-label="Schließen"
                        style="position:absolute;top:14px;right:16px;border:none;background:none;
                               font-size:22px;cursor:pointer;color:#666;line-height:1">&times;</button>
                <h2 style="margin-top:0">&#128221; Vollständiger Changelog</h2>
                <?php
                $cl_tag_colors = array(
                    'FEATURE'  => '#16a34a',
                    'UX'       => '#2563eb',
                    'BUGFIX'   => '#dc2626',
                    'SECURITY' => '#d97706',
                    'INFRA'    => '#6b7280',
                );
                foreach ( gsh_tp_changelog() as $block ) :
                    $version_clean = esc_html( $block['version'] );
                ?>
                <div style="margin-bottom:1.5rem">
                    <strong style="font-size:15px;color:#1d2327">Version <?php echo $version_clean; ?></strong>
                    <ul style="margin:.4rem 0 0 1rem;padding:0">
                    <?php foreach ( $block['entries'] as $entry ) :
                        $tag   = esc_html( $entry['tag'] );
                        $color = $cl_tag_colors[ $entry['tag'] ] ?? '#6b7280';
                    ?>
                        <li style="margin:.3rem 0;font-size:13px;color:#3c434a">
                            <span style="display:inline-block;font-size:10px;font-weight:700;
                                         text-transform:uppercase;letter-spacing:.04em;
                                         padding:1px 6px;border-radius:3px;margin-right:6px;
                                         background:<?php echo esc_attr( $color ); ?>22;
                                         color:<?php echo esc_attr( $color ); ?>;
                                         border:1px solid <?php echo esc_attr( $color ); ?>44">
                                <?php echo $tag; ?>
                            </span>
                            <?php echo esc_html( $entry['text'] ); ?>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <script>
        /* Admin-Changelog-Modal – Funktionen müssen auf der Admin-Seite definiert sein,
           da gsh_tp_js() (Frontend-JS) hier nicht geladen wird. */
        function gshAdminChangelogOpen() {
            var m = document.getElementById('gshAdminChangelog');
            if ( m ) m.style.display = 'block';
        }
        function gshAdminChangelogClose() {
            var m = document.getElementById('gshAdminChangelog');
            if ( m ) m.style.display = 'none';
        }
        document.addEventListener('keydown', function(e) {
            if ( e.key === 'Escape' ) gshAdminChangelogClose();
        });
        document.addEventListener('click', function(e) {
            var m = document.getElementById('gshAdminChangelog');
            if ( m && m.style.display !== 'none' && e.target === m ) {
                gshAdminChangelogClose();
            }
        });
        </script>

        <?php settings_errors(); ?>

        <nav class="nav-tab-wrapper" style="margin-bottom:1.5rem">
            <?php foreach ( $tabs as $id => $label ) : ?>
                <a href="<?php echo esc_url( admin_url( 'options-general.php?page=gsh-terminplan&tab=' . rawurlencode( $id ) ) ); ?>"
                   class="nav-tab <?php echo ( $active_tab === $id ) ? 'nav-tab-active' : ''; ?>">
                    <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
            <?php if ( count( $profiles ) < 5 ) : ?>
                <form method="post" style="display:inline">
                    <?php wp_nonce_field( 'gsh_tp_new_profile', 'gsh_tp_np_n' ); ?>
                    <button type="submit" name="gsh_tp_new_profile" value="1" class="nav-tab"
                            style="cursor:pointer;color:#27ae60;border-style:dashed;background:transparent"
                            onclick="return confirm('Neues Schuljahr-Profil anlegen?')">
                        + Neues Schuljahr
                    </button>
                </form>
            <?php endif; ?>
        </nav>

        <?php
        if ( '_kategorien' === $active_tab ) {
            gsh_tp_render_kategorien_tab();
        } elseif ( '_system' === $active_tab ) {
            gsh_tp_render_system_tab();
        } elseif ( '_sync_log' === $active_tab ) {
            gsh_tp_render_sync_log_tab();
        } else {
            gsh_tp_render_profile_tab( $active_tab );
        }
        ?>
    </div>
    <?php
}

/**
 * Rendert den Tab für ein einzelnes Schuljahr-Profil.
 *
 * Zeigt ein Formular mit iCal-URL, Cache-Dauer, Schulwochenstart,
 * Quartalsgrenzen und Entwurf/Beschlossen-Status. Darunter folgt
 * der Sync-Status mit Buttons für manuelle Synchronisierung und
 * Cache-Invalidierung.
 *
 * @since 3.5.0
 * @param  string $profile_id Profil-ID.
 * @return void
 */
function gsh_tp_render_profile_tab( $profile_id ) {
    $profile = gsh_tp_get_profile( $profile_id );
    if ( ! $profile ) {
        echo '<p>' . esc_html__( 'Profil nicht gefunden.', 'gsh-terminplan' ) . '</p>';
        return;
    }
    $pid       = sanitize_key( $profile_id );
    $cache_key = gsh_tp_ck( 'gsh_tp_ical_', $pid );
    $fresh_key = gsh_tp_ck( 'gsh_tp_fresh_', $pid );
    $has_data  = ! empty( get_option( $cache_key, '' ) );
    $is_fresh  = false !== get_transient( $fresh_key );
    $sync_raw  = get_option( 'gsh_tp_sync_' . $pid, '' );
    $sync_disp = '';
    if ( $sync_raw ) {
        $dt = new DateTime( $sync_raw, new DateTimeZone( 'UTC' ) );
        $dt->setTimezone( wp_timezone() );
        $sync_disp = $dt->format( 'd.m.Y, H:i' );
    }

    // Entwurfs-Hinweis
    if ( ! empty( $profile['is_draft'] ) ) {
        echo '<div style="background:#fef9c3;border-left:4px solid #eab308;padding:10px 16px;'
           . 'margin-bottom:16px;border-radius:4px">'
           . '<strong>Entwurf</strong> &ndash; dieser Terminplan ist noch nicht beschlossen '
           . 'und nur f&uuml;r Admins sichtbar.</div>';
    }
    ?>
    <form method="post">
        <?php wp_nonce_field( 'gsh_tp_profile_save', 'gsh_tp_sp_n' ); ?>
        <input type="hidden" name="gsh_tp_profile_id" value="<?php echo esc_attr( $profile_id ); ?>" />
        <table class="form-table">
            <tr>
                <th><label>Anzeigename</label></th>
                <td>
                    <input type="text"
                           name="gsh_tp_profile[<?php echo esc_attr( $profile_id ); ?>][label]"
                           value="<?php echo esc_attr( $profile['label'] ); ?>"
                           class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="gsh_tp_url_<?php echo esc_attr( $pid ); ?>">iCal-Feed-URL</label></th>
                <td>
                    <input type="url"
                           id="gsh_tp_url_<?php echo esc_attr( $pid ); ?>"
                           name="gsh_tp_profile[<?php echo esc_attr( $profile_id ); ?>][ical_url]"
                           value="<?php echo esc_attr( $profile['ical_url'] ); ?>"
                           class="regular-text" placeholder="https://iserv.example.de/ical/..."
                           pattern="https://.+" />
                    <p class="description">HTTPS-URL des IServ-Kalender-Exports (.ics).</p>
                    <?php
                    // Server-seitige Statusanzeige der gespeicherten URL
                    if ( ! empty( $profile['ical_url'] ) ) {
                        $saved_check = gsh_tp_validate_ical_url( $profile['ical_url'] );
                        if ( $saved_check['valid'] ) {
                            echo '<span style="display:block;margin-top:4px;color:#166534;font-size:13px">'
                               . gsh_tp_icon( 'check' ) . ' Gespeicherte URL ist g&uuml;ltig</span>';
                        } else {
                            echo '<span style="display:block;margin-top:4px;color:#991b1b;font-size:13px">'
                               . gsh_tp_icon( 'x' ) . ' Gespeicherte URL ungültig: <strong>'
                               . esc_html( $saved_check['error'] ) . '</strong></span>';
                        }
                    }
                    ?>
                    <span id="gsh_tp_url_fb_<?php echo esc_attr( $pid ); ?>" style="display:block;margin-top:4px;font-size:13px"></span>
                    <script>
                    (function(){
                        var inp = document.getElementById('gsh_tp_url_<?php echo esc_js( $pid ); ?>');
                        var fb  = document.getElementById('gsh_tp_url_fb_<?php echo esc_js( $pid ); ?>');
                        if(!inp || !fb) return;
                        var icoCheck  = '<svg class="gtp-icon" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>';
                        var icoX      = '<svg class="gtp-icon" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
                        var icoLoader = '<svg class="gtp-icon" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/></svg>';
                        var timer = null;
                        inp.addEventListener('input', function(){
                            clearTimeout(timer);
                            var val = inp.value.trim();
                            if(!val){ fb.textContent = ''; return; }
                            fb.innerHTML = '<span style="color:#888">' + icoLoader + ' Wird gepr\u00fcft\u2026</span>';
                            timer = setTimeout(function(){
                                if(val.indexOf('https://') !== 0){
                                    fb.innerHTML = '<span style="color:#991b1b">' + icoX + ' Nur HTTPS erlaubt</span>';
                                    return;
                                }
                                try{
                                    var u = new URL(val);
                                    if(u.protocol !== 'https:'){
                                        fb.innerHTML = '<span style="color:#991b1b">' + icoX + ' Nur HTTPS erlaubt</span>';
                                        return;
                                    }
                                    if(!u.hostname || u.hostname.indexOf('.') === -1){
                                        fb.innerHTML = '<span style="color:#991b1b">' + icoX + ' Ung\u00fcltige URL-Syntax (kein g\u00fcltiger Hostname)</span>';
                                        return;
                                    }
                                    fb.innerHTML = '<span style="color:#166534">' + icoCheck + ' URL-Syntax g\u00fcltig \u2013 wird beim Speichern server-seitig gepr\u00fcft</span>';
                                }catch(e){
                                    fb.innerHTML = '<span style="color:#991b1b">' + icoX + ' Ung\u00fcltige URL-Syntax</span>';
                                }
                            }, 1000);
                        });
                    })();
                    </script>
                </td>
            </tr>
            <tr>
                <th><label>Cache-Dauer (Sek.)</label></th>
                <td>
                    <input type="number"
                           name="gsh_tp_profile[<?php echo esc_attr( $profile_id ); ?>][cache_duration]"
                           value="<?php echo esc_attr( $profile['cache_duration'] ); ?>"
                           min="300" max="86400" />
                    <p class="description">Standard: 3600 (1 Stunde). Min. 300, Max. 86400.</p>
                </td>
            </tr>
            <tr>
                <th><label>Start Schulwoche 01</label></th>
                <td>
                    <input type="date"
                           name="gsh_tp_profile[<?php echo esc_attr( $profile_id ); ?>][schuljahr_start]"
                           value="<?php echo esc_attr( $profile['schuljahr_start'] ); ?>" />
                    <p class="description">Erster Montag nach den Sommerferien.</p>
                </td>
            </tr>
            <tr>
                <th><label>Quartalsgrenzen</label></th>
                <td>
                    <textarea name="gsh_tp_profile[<?php echo esc_attr( $profile_id ); ?>][quartal_grenzen]"
                              rows="5" class="large-text"><?php
                        echo esc_textarea( $profile['quartal_grenzen'] );
                    ?></textarea>
                    <p class="description">Pro Zeile: Startdatum|Enddatum (JJJJ-MM-TT).</p>
                </td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    <label style="margin-right:1.5rem">
                        <input type="radio"
                               name="gsh_tp_profile[<?php echo esc_attr( $profile_id ); ?>][is_draft]"
                               value="0" <?php checked( empty( $profile['is_draft'] ) ); ?> />
                        Beschlossen
                    </label>
                    <label>
                        <input type="radio"
                               name="gsh_tp_profile[<?php echo esc_attr( $profile_id ); ?>][is_draft]"
                               value="1" <?php checked( ! empty( $profile['is_draft'] ) ); ?> />
                        Entwurf <span style="color:#888">(nur f&uuml;r Admins sichtbar)</span>
                    </label>
                </td>
            </tr>
        </table>
        <p>
            <button type="submit" name="gsh_tp_save_profile" value="1" class="button button-primary">
                <?php echo gsh_tp_icon( 'check' ); ?> Profil speichern
            </button>
        </p>
    </form>

    <?php
    // Aktivieren-Formular (eigenes Formular, da separate Nonce)
    if ( ! empty( $profile['is_draft'] ) ) : ?>
    <form method="post" style="display:inline;margin-right:8px">
        <?php wp_nonce_field( 'gsh_tp_activate_profile', 'gsh_tp_ap_n' ); ?>
        <input type="hidden" name="gsh_tp_profile_id" value="<?php echo esc_attr( $profile_id ); ?>" />
        <button type="submit" name="gsh_tp_activate_profile" value="1"
                class="button" style="color:#1e8449;border-color:#1e8449">
            <?php echo gsh_tp_icon( 'play' ); ?> Als aktiv setzen
        </button>
    </form>
    <?php endif; ?>

    <?php
    // Löschen-Formular (nur wenn Entwurf und nicht letztes Profil)
    if ( ! empty( $profile['is_draft'] ) && count( gsh_tp_get_profiles() ) > 1 ) : ?>
    <form method="post" style="display:inline">
        <?php wp_nonce_field( 'gsh_tp_delete_profile', 'gsh_tp_dp_n' ); ?>
        <input type="hidden" name="gsh_tp_profile_id" value="<?php echo esc_attr( $profile_id ); ?>" />
        <button type="submit" name="gsh_tp_delete_profile" value="1"
                class="button" style="color:#c0392b;border-color:#c0392b"
                onclick="return confirm('Wirklich l\u00f6schen? Alle Terminplan-Daten f\u00fcr dieses Schuljahr gehen verloren.')">
            <?php echo gsh_tp_icon( 'x' ); ?> Profil l&ouml;schen
        </button>
    </form>
    <?php endif; ?>

    <hr />
    <h2>Synchronisation &ndash; <?php echo esc_html( $profile['label'] ); ?></h2>
    <table class="form-table" style="max-width:700px">
        <tr>
            <th style="width:200px">Letzte Synchronisierung</th>
            <td>
                <?php if ( $sync_disp ) : ?>
                    <strong><?php echo esc_html( $sync_disp ); ?> Uhr</strong>
                <?php else : ?>
                    <em style="color:#888">Noch nicht synchronisiert</em>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Cache-Status</th>
            <td>
                <?php
                if ( $has_data && $is_fresh ) {
                    echo '<span style="color:#1e8449">' . gsh_tp_icon( 'check' ) . ' Daten frisch &ndash; n&auml;chster Hintergrund-Refresh bei Ablauf des Freshness-Timers</span>';
                } elseif ( $has_data ) {
                    echo '<span style="color:#b7950b">' . gsh_tp_icon( 'clock' ) . ' Daten veraltet &ndash; Hintergrund-Refresh l&auml;uft beim n&auml;chsten Seitenaufruf</span>';
                } else {
                    echo '<span style="color:#888">&#8212; Noch keine Daten &ndash; werden beim n&auml;chsten Seitenaufruf synchron geladen</span>';
                }
                ?>
            </td>
        </tr>
    </table>
    <form method="post" style="margin-top:.75rem;display:inline-block;margin-right:8px">
        <?php wp_nonce_field( 'gsh_tp_sync_manual', 'gsh_tp_sn' ); ?>
        <input type="hidden" name="gsh_tp_sync_pid" value="<?php echo esc_attr( $profile_id ); ?>" />
        <button type="submit" name="gsh_tp_sync" value="1"
                class="button button-primary" style="height:36px;font-size:14px;padding:0 18px">
            <?php echo gsh_tp_icon( 'refresh-cw' ); ?> Jetzt synchronisieren
        </button>
        <span class="description" style="margin-left:10px;line-height:36px">
            Leert den Cache und ruft sofort die aktuellen Kalenderdaten vom IServ ab.
        </span>
    </form>
    <form method="post" style="display:inline-block;margin-top:.5rem">
        <?php wp_nonce_field( 'gsh_tp_cc', 'gsh_tp_cn' ); ?>
        <input type="hidden" name="gsh_tp_cc_pid" value="<?php echo esc_attr( $profile_id ); ?>" />
        <button type="submit" name="gsh_tp_cc" value="1" class="button"
                onclick="return confirm('Cache leeren? Beim n\u00e4chsten Seitenaufruf werden die Kalenderdaten neu vom IServ abgerufen.')">Cache leeren</button>
        <span class="description" style="margin-left:10px">
            Markiert den Cache als veraltet. Beim n&auml;chsten Seitenaufruf startet ein Hintergrund-Refresh.
        </span>
    </form>
    <?php
}

/**
 * Rendert den Kategorien-Tab (Kategorien verwalten).
 *
 * Gibt das Formular zum Bearbeiten der Terminplan-Kategorien aus,
 * das über die WordPress Settings API (options.php) gespeichert wird.
 *
 * @since 3.4.0 (Tab-Wrapper seit 3.5.0)
 * @return void
 */
function gsh_tp_render_kategorien_tab() {
    ?>
    <p class="description" style="margin-bottom:1rem">
        Legen Sie hier die Terminplan-Kategorien fest. Jede Kategorie hat einen
        <strong>Anzeigenamen</strong>, drei <strong>Farben</strong> und eine Liste von
        <strong>Stichwörtern</strong> (kommagetrennt), die im CATEGORIES-Feld der
        IServ-Termine gesucht werden. Matching mit <strong>Wortgrenzen</strong>:
        &bdquo;LK&ldquo; findet &bdquo;LK 3&ldquo;, aber nicht &bdquo;Volk&ldquo;.
    </p>

    <form method="post" action="<?php echo esc_url( admin_url( 'options-general.php?page=gsh-terminplan&tab=_kategorien' ) ); ?>">
        <?php wp_nonce_field( 'gsh_tp_categories_save', 'gsh_tp_cats_n' ); ?>
        <input type="hidden" name="gsh_tp_save_categories" value="1" />

        <?php
        $existing_cats = gsh_tp_get_categories();
        ?>
        <div id="gsh-cat-editor">
        <table class="widefat" id="gsh-cat-table" style="table-layout:fixed;margin-bottom:8px">
            <thead>
                <tr>
                    <th style="width:42px">Reihenf.</th>
                    <th style="width:160px">Anzeigename</th>
                    <th style="width:68px">Hintergrund</th>
                    <th style="width:68px">Rahmen</th>
                    <th style="width:68px">Text</th>
                    <th>Stichwörter (kommasepariert)</th>
                    <th style="width:34px"></th>
                </tr>
            </thead>
            <tbody id="gsh-cat-tbody">
            <?php foreach ( $existing_cats as $i => $cat ) : ?>
                <tr class="gsh-cat-row">
                    <td>
                        <input type="number" name="gsh_tp_categories[<?php echo $i; ?>][order]"
                               value="<?php echo esc_attr( $cat['order'] ?? ( $i + 1 ) ); ?>"
                               min="1" max="99" style="width:38px"
                               class="gsh-cat-order" />
                        <input type="hidden" name="gsh_tp_categories[<?php echo $i; ?>][slug]"
                               value="<?php echo esc_attr( $cat['slug'] ); ?>" />
                    </td>
                    <td>
                        <input type="text" name="gsh_tp_categories[<?php echo $i; ?>][label]"
                               value="<?php echo esc_attr( $cat['label'] ); ?>"
                               style="width:100%" class="gsh-cat-label"
                               placeholder="Anzeigename" />
                    </td>
                    <td>
                        <input type="color" name="gsh_tp_categories[<?php echo $i; ?>][bg]"
                               value="<?php echo esc_attr( $cat['bg'] ); ?>"
                               class="gsh-cat-bg" style="width:48px;height:32px;padding:2px;cursor:pointer" />
                    </td>
                    <td>
                        <input type="color" name="gsh_tp_categories[<?php echo $i; ?>][border]"
                               value="<?php echo esc_attr( $cat['border'] ); ?>"
                               class="gsh-cat-bd" style="width:48px;height:32px;padding:2px;cursor:pointer" />
                    </td>
                    <td>
                        <input type="color" name="gsh_tp_categories[<?php echo $i; ?>][text]"
                               value="<?php echo esc_attr( $cat['text'] ); ?>"
                               class="gsh-cat-tx" style="width:48px;height:32px;padding:2px;cursor:pointer" />
                    </td>
                    <td>
                        <input type="text" name="gsh_tp_categories[<?php echo $i; ?>][keywords]"
                               value="<?php echo esc_attr( implode( ', ', $cat['keywords'] ) ); ?>"
                               style="width:100%" class="gsh-cat-kw"
                               placeholder="Stichwort1, Stichwort2, ..." />
                        <span class="gsh-cat-preview" style="display:inline-block;margin-top:4px;
                            padding:2px 8px;border-radius:4px;font-size:12px;
                            border-left:3px solid <?php echo esc_attr( $cat['border'] ); ?>;
                            background:<?php echo esc_attr( $cat['bg'] ); ?>;
                            color:<?php echo esc_attr( $cat['text'] ); ?>">Beispieltermin</span>
                    </td>
                    <td>
                        <button type="button" class="button gsh-cat-del"
                                title="Kategorie löschen"><?php echo gsh_tp_icon( 'x', '0.9em' ); ?></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <button type="button" class="button" id="gsh-cat-add">+ Neue Kategorie hinzufügen</button>
        </div>

        <script>
        (function(){
            var tbody  = document.getElementById('gsh-cat-tbody');
            var addBtn = document.getElementById('gsh-cat-add');

            // Live-Vorschau aktualisieren wenn Farben sich ändern
            function updatePreview(row) {
                var bg  = row.querySelector('.gsh-cat-bg').value;
                var bd  = row.querySelector('.gsh-cat-bd').value;
                var tx  = row.querySelector('.gsh-cat-tx').value;
                var pre = row.querySelector('.gsh-cat-preview');
                if (pre) {
                    pre.style.background    = bg;
                    pre.style.borderLeftColor = bd;
                    pre.style.color         = tx;
                }
            }

            tbody.addEventListener('input', function(e) {
                var row = e.target.closest('.gsh-cat-row');
                if (row) updatePreview(row);
            });

            tbody.addEventListener('click', function(e) {
                if (!e.target.classList.contains('gsh-cat-del')) return;
                var rows = tbody.querySelectorAll('.gsh-cat-row');
                if (rows.length <= 1) {
                    alert('Es muss mindestens eine Kategorie vorhanden sein.');
                    return;
                }
                if (!confirm('Kategorie wirklich löschen?')) return;
                e.target.closest('.gsh-cat-row').remove();
                reIndex();
            });

            addBtn.addEventListener('click', function() {
                var idx  = tbody.querySelectorAll('.gsh-cat-row').length;
                var order = idx + 1;
                var row  = document.createElement('tr');
                row.className = 'gsh-cat-row';
                row.innerHTML =
                    '<td>' +
                        '<input type="number" name="gsh_tp_categories[' + idx + '][order]" value="' + order + '" min="1" max="99" style="width:38px" class="gsh-cat-order" />' +
                        '<input type="hidden" name="gsh_tp_categories[' + idx + '][slug]" value="" />' +
                    '</td>' +
                    '<td><input type="text" name="gsh_tp_categories[' + idx + '][label]" value="" style="width:100%" class="gsh-cat-label" placeholder="Anzeigename" /></td>' +
                    '<td><input type="color" name="gsh_tp_categories[' + idx + '][bg]" value="#f0f0f0" class="gsh-cat-bg" style="width:48px;height:32px;padding:2px;cursor:pointer" /></td>' +
                    '<td><input type="color" name="gsh_tp_categories[' + idx + '][border]" value="#cccccc" class="gsh-cat-bd" style="width:48px;height:32px;padding:2px;cursor:pointer" /></td>' +
                    '<td><input type="color" name="gsh_tp_categories[' + idx + '][text]" value="#333333" class="gsh-cat-tx" style="width:48px;height:32px;padding:2px;cursor:pointer" /></td>' +
                    '<td>' +
                        '<input type="text" name="gsh_tp_categories[' + idx + '][keywords]" value="" style="width:100%" class="gsh-cat-kw" placeholder="Stichwort1, Stichwort2, ..." />' +
                        '<span class="gsh-cat-preview" style="display:inline-block;margin-top:4px;padding:2px 8px;border-radius:4px;font-size:12px;border-left:3px solid #cccccc;background:#f0f0f0;color:#333333">Beispieltermin</span>' +
                    '</td>' +
                    '<td><button type="button" class="button gsh-cat-del" title="Kategorie l\u00f6schen"><svg class="gtp-icon" xmlns="http://www.w3.org/2000/svg" width="0.9em" height="0.9em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></td>';
                tbody.appendChild(row);
            });

            function reIndex() {
                tbody.querySelectorAll('.gsh-cat-row').forEach(function(row, idx) {
                    row.querySelectorAll('[name]').forEach(function(el) {
                        el.name = el.name.replace(/\[\d+\]/, '[' + idx + ']');
                    });
                });
            }
        })();
        </script>

        <?php submit_button( 'Kategorien speichern' ); ?>
    </form>
    <?php
}

/**
 * Rendert den System-Tab (Kiosk-Modus, Shortcode-Hilfe).
 *
 * Kiosk-Einstellungen (Token, IServ-Domain) werden über die WordPress
 * Settings API (options.php) gespeichert.
 *
 * @since 3.2.0 (Tab-Wrapper seit 3.5.0)
 * @return void
 */
function gsh_tp_render_system_tab() {
    ?>
    <h2>IServ-Einbettung (Kiosk-Modus)</h2>
    <div style="background:#eaf2f8;border-left:4px solid #2874a6;padding:12px 16px;margin-bottom:16px;border-radius:4px;">
        <strong>Was ist der Kiosk-Modus?</strong><br>
        Eine Ansicht des Terminplans ohne WordPress-Men&uuml; und Passwort.
        Ideal zum Einbetten in IServ als Navigations-Eintrag.
    </div>

    <form method="post" action="options.php">
        <?php settings_fields( 'gsh_tp_options' ); ?>
        <table class="form-table">

            <tr>
                <th><label for="gsh_tp_kiosk_token">Kiosk-Token</label></th>
                <td>
                    <input type="text" id="gsh_tp_kiosk_token" name="gsh_tp_kiosk_token"
                           value="<?php echo esc_attr( get_option( 'gsh_tp_kiosk_token', '' ) ); ?>"
                           class="regular-text" autocomplete="off"
                           placeholder="mind. 20 Zeichen" />
                    <button type="button" class="button" style="margin-left:6px"
                            onclick="if(!confirm('Token wird ersetzt. Alte Kiosk-Links funktionieren nicht mehr.'))return;document.getElementById('gsh_tp_kiosk_token').value=Array.from(crypto.getRandomValues(new Uint8Array(24)),function(b){return b.toString(36);}).join('').slice(0,32);">
                        <?php echo gsh_tp_icon( 'dice' ); ?> Zuf&auml;lligen Token erzeugen
                    </button>
                    <p class="description">Geheimer Token f&uuml;r den Zugang zur Kiosk-Seite. Mind. 20 Zeichen empfohlen.</p>
                    <?php
                    $cur_token = get_option( 'gsh_tp_kiosk_token', '' );
                    if ( empty( $cur_token ) ) {
                        echo '<p style="color:#c0392b;margin-top:6px"><strong>' . gsh_tp_icon( 'alert-triangle' ) . ' Kein Token gesetzt</strong> &ndash; '
                           . 'die Kiosk-Seite ist ohne Authentifizierung erreichbar!</p>';
                    } elseif ( strlen( $cur_token ) < 20 ) {
                        echo '<p style="color:#e67e22;margin-top:6px"><strong>' . gsh_tp_icon( 'alert-triangle' ) . ' Token zu kurz</strong> &ndash; '
                           . 'aus Sicherheitsgr&uuml;nden mind. 20 Zeichen verwenden.</p>';
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <th><label for="gsh_tp_iserv_domain">IServ-Domain</label></th>
                <td>
                    <input type="url" id="gsh_tp_iserv_domain" name="gsh_tp_iserv_domain"
                           value="<?php echo esc_attr( get_option( 'gsh_tp_iserv_domain', '' ) ); ?>"
                           class="regular-text" placeholder="https://example-school.de" />
                    <p class="description">Die vollst&auml;ndige URL eures IServ-Servers (mit https://). Wird ben&ouml;tigt damit nur euer IServ die Seite einbetten darf.</p>
                    <?php if ( empty( get_option( 'gsh_tp_iserv_domain', '' ) ) ) : ?>
                        <p style="color:#e67e22;margin-top:6px"><strong><?php echo gsh_tp_icon( 'alert-triangle' ); ?> IServ-Domain fehlt</strong> &ndash;
                        ohne diese Einstellung kann jede Website die Kiosk-Seite einbetten.</p>
                    <?php endif; ?>
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
                           . gsh_tp_icon( 'link' ) . ' Kiosk-Seite testen</a>';
                    } else {
                        echo '<p style="color:#888;margin:0">' . gsh_tp_icon( 'alert-triangle' ) . ' Noch nicht verf&uuml;gbar &ndash; folgendes fehlt: '
                           . implode( ', ', $missing ) . '.</p>';
                    }
                    ?>
                </td>
            </tr>

        </table>

        <?php submit_button( 'Kiosk-Einstellungen speichern' ); ?>
    </form>

    <hr />
    <h2>Shortcode-Verwendung</h2>
    <p><code>[gsh_terminplan]</code> &ndash; Zeigt automatisch das aktuelle Quartal (aktives Schuljahr)</p>
    <p><code>[gsh_terminplan quartal="2"]</code> &ndash; Zeigt ein bestimmtes Quartal (1&ndash;4)</p>
    <p><code>[gsh_terminplan quartal="alle"]</code> &ndash; Alle Quartale mit Tabs</p>
    <p><code>[gsh_terminplan schuljahr="sj_2026_27"]</code> &ndash; Bestimmtes Schuljahr-Profil anzeigen</p>
    <p><code>[gsh_terminplan schuljahr="entwurf"]</code> &ndash; Entwurf-Vorschau (nur Admins)</p>

    <?php
    // Import-Sektion entfernt in v3.12.0 – wird durch externes Tool ersetzt.
    // (Ehemals: Terminplan-Import Excel → ICS mit SheetJS)
    ?>
        <?php
}

/**
 * Rendert den Sync-Verlauf-Tab.
 *
 * Zeigt die letzten 20 Sync-Versuche aller Profile tabellarisch.
 * Erfolgreiche Zeilen sind grün hinterlegt, fehlerhafte rot.
 * Ein Cleanup-Button entfernt Einträge älter als 30 Tage.
 *
 * @since 3.6.0
 * @return void
 */
function gsh_tp_render_sync_log_tab() {
    $profiles = gsh_tp_get_profiles();
    ?>
    <h2>Sync-Verlauf</h2>
    <p class="description" style="margin-bottom:1.5rem">
        Die letzten Synchronisierungs-Versuche aller Schuljahr-Profile.
        Hilfreich beim Debuggen von Verbindungs- und Konfigurationsproblemen
        (Timeouts, ungültige iCal-URLs, Netzwerkfehler).
    </p>

    <?php foreach ( $profiles as $p ) :
        $logs = gsh_tp_get_sync_logs( $p['id'], 20 );
    ?>
    <h3 style="margin-top:1.5rem;margin-bottom:.5rem"><?php echo esc_html( $p['label'] ); ?></h3>
    <?php if ( empty( $logs ) ) : ?>
        <p style="color:#888"><em>Noch keine Sync-Versuche gespeichert.</em></p>
    <?php else : ?>
    <table class="widefat" style="max-width:960px;margin-bottom:12px;border-collapse:collapse">
        <thead>
            <tr style="background:#f6f7f7">
                <th style="width:148px;padding:8px 10px">Zeitpunkt</th>
                <th style="width:72px;padding:8px 10px">Status</th>
                <th style="width:110px;padding:8px 10px">Fehlertyp</th>
                <th style="width:68px;padding:8px 10px">Events</th>
                <th style="width:72px;padding:8px 10px">Dauer</th>
                <th style="padding:8px 10px">Meldung</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $logs as $entry ) :
            $is_ok  = ( 'success' === ( $entry['status'] ?? '' ) );
            $row_bg = $is_ok ? '#f0fdf4' : '#fff5f5';
            $ts_disp = '';
            if ( ! empty( $entry['timestamp'] ) ) {
                $dt = new DateTime( $entry['timestamp'], new DateTimeZone( 'UTC' ) );
                $dt->setTimezone( wp_timezone() );
                $ts_disp = $dt->format( 'd.m.Y H:i:s' );
            }
        ?>
            <tr style="background:<?php echo esc_attr( $row_bg ); ?>;border-top:1px solid #e0e0e0">
                <td style="padding:6px 10px;font-size:13px"><?php echo esc_html( $ts_disp ); ?></td>
                <td style="padding:6px 10px;font-size:13px">
                    <?php if ( $is_ok ) : ?>
                        <span style="color:#166534;font-weight:600">&#10003; OK</span>
                    <?php else : ?>
                        <span style="color:#991b1b;font-weight:600">&#10005; Fehler</span>
                    <?php endif; ?>
                </td>
                <td style="padding:6px 10px;font-size:13px">
                    <?php if ( ! empty( $entry['error_type'] ) ) : ?>
                        <code style="font-size:12px"><?php echo esc_html( $entry['error_type'] ); ?></code>
                    <?php else : ?>
                        <span style="color:#888">&ndash;</span>
                    <?php endif; ?>
                </td>
                <td style="padding:6px 10px;font-size:13px">
                    <?php echo $is_ok ? esc_html( $entry['event_count'] ?? 0 ) : '<span style="color:#888">&ndash;</span>'; ?>
                </td>
                <td style="padding:6px 10px;font-size:13px">
                    <?php echo esc_html( ( $entry['duration_ms'] ?? 0 ) . ' ms' ); ?>
                </td>
                <td style="padding:6px 10px;font-size:13px;color:#555">
                    <?php echo esc_html( $entry['message'] ?? '' ); ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <?php endforeach; ?>

    <hr style="margin:2rem 0 1.5rem" />
    <h3>Wartung</h3>
    <form method="post" style="display:inline-block">
        <?php wp_nonce_field( 'gsh_tp_clear_logs', 'gsh_tp_cl_n' ); ?>
        <button type="submit" name="gsh_tp_clear_logs" value="1" class="button"
                onclick="return confirm('Alle Log-Eintr\u00e4ge \u00e4lter als 30 Tage l\u00f6schen?')">
            <?php echo gsh_tp_icon( 'trash' ); ?> Logs &auml;lter als 30 Tage l&ouml;schen
        </button>
        <span class="description" style="margin-left:10px">
            Entfernt veraltete Sync-Log-Eintr&auml;ge aus der Datenbank aller Profile.
        </span>
    </form>
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
/**
 * Stale-While-Revalidate: Liefert sofort die gespeicherten Kalenderdaten zurück.
 *
 * Die eigentlichen Daten liegen in der permanenten Option gsh_tp_ical_{profile_id}
 * und laufen nie ab. Ein separater Freshness-Transient steuert wann ein Refresh
 * nötig ist. Ist er abgelaufen, wird ein WP-Cron-Job eingeplant – der Besucher
 * wartet jedoch nicht auf den Download von IServ.
 *
 * Nur beim allerersten Aufruf (keine Daten gespeichert) wird synchron geladen.
 *
 * @since 1.2.0 (Stale-While-Revalidate seit 3.3.0; profil-aware seit 3.5.0)
 * @param  string $profile_id Profil-ID; leer = aktives Profil.
 * @return string             iCal-Text oder leerer String wenn noch keine Daten vorhanden.
 */
function gsh_tp_fetch_ical( $profile_id = '' ) {
    if ( ! $profile_id ) {
        $profile_id = gsh_tp_active_profile_id();
    }
    $pid = sanitize_key( $profile_id );

    // Profil-spezifische Keys (versioniert, mit Fallback auf globale Konstanten für Migration)
    $cache_key = $pid ? gsh_tp_ck( 'gsh_tp_ical_', $pid ) : GSH_TP_CACHE_KEY;
    $fresh_key = $pid ? gsh_tp_ck( 'gsh_tp_fresh_', $pid ) : GSH_TP_FRESH_KEY;

    // 1. Sofort die gespeicherten Daten liefern (läuft nie ab)
    $data = get_option( $cache_key, '' );
    // Fallback auf globalen Cache-Key (Migration)
    if ( '' === $data && $cache_key !== GSH_TP_CACHE_KEY ) {
        $data = get_option( GSH_TP_CACHE_KEY, '' );
    }

    // 2. Freshness prüfen – bei Ablauf Hintergrund-Refresh anstoßen
    if ( false === get_transient( $fresh_key ) ) {
        gsh_tp_schedule_refresh( $profile_id );
    }

    // 3. Erstinstallation: noch keine Daten → einmalig synchron laden
    if ( empty( $data ) ) {
        $data = gsh_tp_fetch_sync( $profile_id );
    }

    return $data;
}

/**
 * Synchroner iCal-Abruf – nur bei Erstinstallation, wenn noch keine Daten vorliegen.
 *
 * @since 3.3.0 (profil-aware seit 3.5.0)
 * @param  string $profile_id Profil-ID; leer = aktives Profil.
 * @return string             iCal-Text oder leerer String bei Fehler.
 */
function gsh_tp_fetch_sync( $profile_id = '' ) {
    if ( ! $profile_id ) {
        $profile_id = gsh_tp_active_profile_id();
    }
    $pid     = sanitize_key( $profile_id );
    $profile = gsh_tp_get_profile( $profile_id );

    // URL aus Profil lesen; Fallback auf alte Einzel-Option (Migration)
    if ( $profile ) {
        $url = $profile['ical_url'];
        $dur = max( 300, absint( $profile['cache_duration'] ) );
    } else {
        $url = get_option( 'gsh_tp_ical_url', '' );
        $dur = max( 300, absint( get_option( 'gsh_tp_cache_duration', 3600 ) ) );
    }

    if ( empty( $url ) ) {
        return '';
    }

    $cache_key  = $pid ? gsh_tp_ck( 'gsh_tp_ical_', $pid )   : GSH_TP_CACHE_KEY;
    $backup_key = $pid ? 'gsh_tp_backup_' . $pid              : GSH_TP_BACKUP_KEY; // Rohdaten – kein Versionssuffix
    $fresh_key  = $pid ? gsh_tp_ck( 'gsh_tp_fresh_', $pid )  : GSH_TP_FRESH_KEY;
    $sync_key   = $pid ? 'gsh_tp_sync_' . $pid                : 'gsh_tp_last_sync'; // Zeitstempel – kein Versionssuffix

    $resp = wp_remote_get( $url, array(
        'timeout'   => 15,
        'sslverify' => true,
        'headers'   => array( 'Accept' => 'text/calendar' ),
    ) );
    if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) {
        return get_option( $backup_key, '' );
    }
    $body = wp_remote_retrieve_body( $resp );
    if ( strpos( $body, 'BEGIN:VCALENDAR' ) === false ) {
        return get_option( $backup_key, '' );
    }
    update_option( $cache_key, $body, false );
    update_option( $backup_key, $body, false );
    update_option( $sync_key, gmdate( 'Y-m-d H:i:s' ) );
    set_transient( $fresh_key, time(), $dur );
    return $body;
}

/**
 * Plant einen einmaligen WP-Cron-Refresh ein und schützt gegen Doppel-Scheduling.
 *
 * Pro Profil wird ein eigener Guard-Transient gesetzt (120 Sekunden).
 * Die Profil-ID wird als Parameter an den Cron-Hook übergeben.
 * spawn_cron() startet den Cron non-blocking.
 *
 * @since 3.3.0 (profil-aware seit 3.5.0)
 * @param  string $profile_id Profil-ID; leer = aktives Profil.
 * @return void
 */
function gsh_tp_schedule_refresh( $profile_id = '' ) {
    if ( ! $profile_id ) {
        $profile_id = gsh_tp_active_profile_id();
    }
    $pid   = sanitize_key( $profile_id );
    $guard = $pid ? 'gsh_tp_sched_' . $pid                   : GSH_TP_FRESH_KEY; // Guard – kein Versionssuffix
    $fresh = $pid ? gsh_tp_ck( 'gsh_tp_fresh_', $pid )       : GSH_TP_FRESH_KEY;

    // Schutz-Transient: verhindert Doppel-Scheduling für 120 Sekunden
    set_transient( $fresh, time(), 120 );
    if ( ! get_transient( $guard ) ) {
        set_transient( $guard, 1, 120 );
        wp_schedule_single_event( time(), 'gsh_tp_cron_refresh', array( $profile_id ) );
    }
    // Cron non-blocking anstoßen (wartet nicht auf Ergebnis)
    if ( function_exists( 'spawn_cron' ) ) {
        spawn_cron();
    }
}

/**
 * Holt den iCal-Feed von IServ und aktualisiert alle gespeicherten Daten.
 *
 * Wird via WP-Cron (gsh_tp_cron_refresh) im Hintergrund aufgerufen.
 * Kann auch direkt im Admin-Panel aufgerufen werden (Sync-Button).
 * Führt den Snapshot-Diff durch und leert danach den Seiten-Cache.
 *
 * @since 3.3.0 (profil-aware seit 3.5.0)
 * @param  string $profile_id Profil-ID; leer = aktives Profil.
 * @return bool               true bei Erfolg, false bei Fehler.
 */
function gsh_tp_do_refresh( $profile_id = '' ) {
    if ( ! $profile_id ) {
        $profile_id = gsh_tp_active_profile_id();
    }
    $pid     = sanitize_key( $profile_id );
    $profile = gsh_tp_get_profile( $profile_id );

    // URL und Cache-Dauer aus Profil lesen; Fallback auf alte Einzel-Options
    if ( $profile ) {
        $url = $profile['ical_url'];
        $dur = max( 300, absint( $profile['cache_duration'] ) );
    } else {
        $url = get_option( 'gsh_tp_ical_url', '' );
        $dur = max( 300, absint( get_option( 'gsh_tp_cache_duration', 3600 ) ) );
    }

    if ( empty( $url ) ) {
        gsh_tp_log_sync_attempt( $profile_id, 'error', array( 'error_type' => 'url_invalid' ) );
        return false;
    }

    $start_ms = round( microtime( true ) * 1000 );
    $resp = wp_remote_get( $url, array(
        'timeout'   => 30, // Im Hintergrund darf es länger dauern
        'sslverify' => true,
        'headers'   => array( 'Accept' => 'text/calendar' ),
    ) );
    $duration_ms = round( microtime( true ) * 1000 ) - $start_ms;

    if ( is_wp_error( $resp ) ) {
        gsh_tp_log_sync_attempt( $profile_id, 'error', array(
            'error_type'  => 'network_error',
            'duration_ms' => $duration_ms,
            'message'     => $resp->get_error_message(),
        ) );
        return false;
    }
    if ( wp_remote_retrieve_response_code( $resp ) !== 200 ) {
        gsh_tp_log_sync_attempt( $profile_id, 'error', array(
            'error_type'  => 'network_error',
            'duration_ms' => $duration_ms,
            'message'     => 'HTTP ' . wp_remote_retrieve_response_code( $resp ),
        ) );
        return false;
    }
    $body = wp_remote_retrieve_body( $resp );
    if ( strpos( $body, 'BEGIN:VCALENDAR' ) === false ) {
        gsh_tp_log_sync_attempt( $profile_id, 'error', array(
            'error_type'  => 'invalid_ical',
            'duration_ms' => $duration_ms,
        ) );
        return false;
    }

    // Profil-spezifische Cache-Keys (versionierte Keys für Datenstrukturen)
    $cache_key  = $pid ? gsh_tp_ck( 'gsh_tp_ical_', $pid )  : GSH_TP_CACHE_KEY;
    $backup_key = $pid ? 'gsh_tp_backup_' . $pid             : GSH_TP_BACKUP_KEY; // Rohdaten – kein Versionssuffix
    $fresh_key  = $pid ? gsh_tp_ck( 'gsh_tp_fresh_', $pid ) : GSH_TP_FRESH_KEY;
    $sync_key   = $pid ? 'gsh_tp_sync_' . $pid               : 'gsh_tp_last_sync'; // Zeitstempel – kein Versionssuffix

    // Daten, Backup und Sync-Zeitstempel aktualisieren
    update_option( $cache_key, $body, false );
    update_option( $backup_key, $body, false );
    update_option( $sync_key, gmdate( 'Y-m-d H:i:s' ) );

    // Freshness-Timer mit konfigurierter Dauer neu starten
    set_transient( $fresh_key, time(), $dur );

    // Snapshot-Diff: Änderungen erkennen und akkumulieren (pro Profil)
    $snap_key     = $pid ? 'gsh_tp_snap_' . $pid                     : 'gsh_tp_events_snapshot'; // Snapshot – kein Versionssuffix
    $changes_key  = $pid ? gsh_tp_ck( 'gsh_tp_chg_', $pid )         : 'gsh_tp_changes';
    $new_events   = gsh_tp_parse_events( $body );
    $old_snapshot = get_transient( $snap_key );
    $new_snapshot = gsh_tp_build_snapshot( $new_events );
    if ( false !== $old_snapshot ) {
        $diff = gsh_tp_diff( $old_snapshot, $new_snapshot );
        if ( $diff['total'] > 0 ) {
            $changes = get_transient( $changes_key );
            if ( ! is_array( $changes ) ) {
                $changes = array();
            }
            array_unshift( $changes, $diff );
            $changes = array_slice( $changes, 0, 50 );
            set_transient( $changes_key, $changes, 7 * DAY_IN_SECONDS );
        }
    }
    set_transient( $snap_key, $new_snapshot, 0 );

    // Seiten-Cache von Cache-Plugins invalidieren
    gsh_tp_clear_page_cache();

    // Erfolgreichen Sync-Versuch protokollieren
    gsh_tp_log_sync_attempt( $profile_id, 'success', array(
        'event_count' => count( $new_events ),
        'duration_ms' => $duration_ms,
    ) );

    return true;
}

/**
 * Leert den Seiten-Cache gängiger WordPress-Cache-Plugins.
 *
 * Unterstützt WP Super Cache, W3 Total Cache, LiteSpeed Cache und
 * WP Fastest Cache. Wird nach jedem erfolgreichen Feed-Refresh und
 * bei Einstellungsänderungen (ical_url, kategorie_mapping) aufgerufen.
 *
 * @since 3.3.0
 * @return void
 */
function gsh_tp_clear_page_cache() {
    // WP Super Cache
    if ( function_exists( 'wp_cache_clear_cache' ) ) {
        wp_cache_clear_cache();
    }
    // W3 Total Cache
    if ( function_exists( 'w3tc_flush_posts' ) ) {
        w3tc_flush_posts();
    }
    // LiteSpeed Cache
    if ( class_exists( 'LiteSpeed_Cache_API' ) ) {
        LiteSpeed_Cache_API::purge_all();
    }
    // WP Fastest Cache
    if ( function_exists( 'wpfc_clear_all_cache' ) ) {
        wpfc_clear_all_cache( true );
    }
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
function gsh_tp_current_q( $profile_id = '' ) {
    $t = gmdate( 'Y-m-d' );
    foreach ( gsh_tp_quartale( $profile_id ) as $i => $q ) {
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
 * @since 1.2.0 (profil-aware seit 3.5.0)
 * @param  string $profile_id Optionale Profil-ID; leer = aktives Profil.
 * @return array              Array mit 4 Einträgen, jeder mit 'start' und 'end' (Y-m-d).
 */
function gsh_tp_quartale( $profile_id = '' ) {
    $raw = gsh_tp_opt( 'quartal_grenzen',
        "2025-08-25|2025-10-31\n2025-11-03|2026-02-06\n2026-02-09|2026-05-01\n2026-05-04|2026-07-17",
        $profile_id );
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
 * Baut das Kategorie-Mapping aus den konfigurierten Kategorien als Array von Regex-Regeln.
 *
 * Liest die Kategorien aus gsh_tp_get_categories() und erstellt für jedes Stichwort
 * einen vorcompilierten regulären Ausdruck mit Wortgrenzen (\b). preg_quote() escaped
 * Sonderzeichen sicher (Regex-Injection-Schutz).
 *
 * @since 1.2.0 (überarbeitet 3.4.0 – nutzt jetzt gsh_tp_get_categories())
 * @return array Array von Arrays mit 'regex' (string) und 'cls' (string).
 */
function gsh_tp_build_map() {
    $cats = gsh_tp_get_categories();
    $r    = array();
    foreach ( $cats as $c ) {
        foreach ( $c['keywords'] as $kw ) {
            $kw = trim( $kw );
            if ( '' === $kw ) {
                continue;
            }
            // preg_quote() escaped Sonderzeichen (z. B. "Tag der Deutschen Einheit").
            // /iu = case-insensitive + Unicode-Unterstützung für Umlaute.
            $r[] = array(
                'regex' => '/\b' . preg_quote( $kw, '/' ) . '\b/iu',
                'cls'   => $c['slug'],
            );
        }
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
    $atts = shortcode_atts( array(
        'quartal'   => 'auto',
        'schuljahr' => '',      // leer = aktives Profil; 'entwurf' = Entwurf-Vorschau
    ), $atts, 'gsh_terminplan' );

    // ── Profil bestimmen ──
    // URL-Parameter ?sj= überschreibt Shortcode-Attribut (Frontend-Umschalter)
    $sj_param   = sanitize_key( $_GET['sj'] ?? '' );
    $profile_id = $sj_param ?: sanitize_key( $atts['schuljahr'] );

    if ( 'entwurf' === $atts['schuljahr'] ) {
        // Entwurf-Modus: nur für Admins sichtbar
        if ( ! current_user_can( 'manage_options' ) ) {
            return '<div style="padding:1.5rem;background:#f1f5f9;border:1px solid #94a3b8;'
                 . 'border-radius:8px;color:#475569;text-align:center">'
                 . gsh_tp_icon( 'lock' ) . ' Dieser Terminplan ist noch nicht freigegeben.</div>';
        }
        $profile_id = '';
        foreach ( gsh_tp_get_profiles() as $p ) {
            if ( ! empty( $p['is_draft'] ) ) {
                $profile_id = $p['id'];
                break;
            }
        }
        if ( ! $profile_id ) {
            return '<div style="padding:1.5rem;background:#f1f5f9;border:1px solid #94a3b8;'
                 . 'border-radius:8px;color:#475569;text-align:center">'
                 . 'Kein Entwurf-Profil vorhanden.</div>';
        }
    }

    if ( ! $profile_id ) {
        $profile_id = gsh_tp_active_profile_id();
    }

    $profile = gsh_tp_get_profile( $profile_id );
    if ( ! $profile ) {
        return '<div style="padding:1.5rem;background:#fadbd8;border:1px solid #e74c3c;'
             . 'border-radius:8px;color:#922b21;text-align:center">'
             . 'Kein Schuljahr-Profil gefunden. Bitte die Plugin-Einstellungen pr&uuml;fen.</div>';
    }

    // Entwurf-Modus: Entwürfe ohne schuljahr="entwurf" nur für Admins
    if ( ! empty( $profile['is_draft'] ) && ! current_user_can( 'manage_options' ) ) {
        return '<div style="padding:1.5rem;background:#f1f5f9;border:1px solid #94a3b8;'
             . 'border-radius:8px;color:#475569;text-align:center">'
             . gsh_tp_icon( 'lock' ) . ' Dieser Terminplan ist noch nicht freigegeben.</div>';
    }

    $data = gsh_tp_fetch_ical( $profile_id );
    if ( ! $data ) {
        return '<div style="padding:1.5rem;background:#fadbd8;border:1px solid #e74c3c;'
             . 'border-radius:8px;color:#922b21;text-align:center">'
             . esc_html__( 'Keine Kalenderdaten verfügbar. Bitte die iCal-URL in den Plugin-Einstellungen prüfen.', 'gsh-terminplan' )
             . '</div>';
    }

    $events  = gsh_tp_parse_events( $data );
    $grenzen = gsh_tp_quartale( $profile_id );
    $sjs     = gsh_tp_opt( 'schuljahr_start', '2025-08-25', $profile_id );

    // Einmaliger Aufbau des Date-Index für O(1)-Lookup statt O(n) pro Tag
    $date_index = gsh_tp_build_date_index( $events );

    $aq      = ( $atts['quartal'] === 'auto' || $atts['quartal'] === 'alle' )
               ? gsh_tp_current_q( $profile_id )
               : max( 1, min( 4, absint( $atts['quartal'] ) ) );

    $qlbl = array(
        1 => 'Quartal 1 &ndash; August bis Oktober',
        2 => 'Quartal 2 &ndash; November bis Januar',
        3 => 'Quartal 3 &ndash; Februar bis April',
        4 => 'Quartal 4 &ndash; Mai bis Juli',
    );

    // Letzte Sync-Zeit ermitteln (profil-spezifisch)
    $last_sync = gsh_tp_opt( 'last_sync', '', $profile_id );
    if ( $last_sync ) {
        $dt = new DateTime( $last_sync, new DateTimeZone( 'UTC' ) );
        $dt->setTimezone( wp_timezone() );
        $sync_display = $dt->format( 'd.m.Y, H:i' );
    } else {
        $sync_display = wp_date( 'd.m.Y, H:i' );
    }

    // ── Ausgabe zusammenbauen ──
    $pid          = sanitize_key( $profile_id );
    $chg_key      = $pid ? gsh_tp_ck( 'gsh_tp_chg_', $pid ) : 'gsh_tp_changes';
    $changes      = get_transient( $chg_key );
    $changes_json = esc_attr( wp_json_encode( is_array( $changes ) ? $changes : array() ) );

    // Kategorien als JSON für dynamisches Druck-CSS und Legende
    $cats_for_js  = array_map( static function ( $c ) {
        return array(
            'slug'   => $c['slug'],
            'label'  => $c['label'],
            'border' => $c['border'],
            'bg'     => $c['bg'],
            'text'   => $c['text'],
        );
    }, gsh_tp_get_categories() );
    $cats_json = esc_attr( wp_json_encode( $cats_for_js ) );

    $o  = gsh_tp_css();
    $o .= '<div class="gtp" id="gtp" data-changes="' . $changes_json . '" data-categories="' . $cats_json . '">';

    // Entwurfs-Banner
    if ( ! empty( $profile['is_draft'] ) ) {
        $o .= '<div style="background:#fef9c3;border:1px solid #eab308;padding:10px 16px;'
            . 'border-radius:8px;margin-bottom:16px;font-weight:600;color:#92400e">'
            . gsh_tp_icon( 'lock' ) . ' ENTWURF &ndash; Dieser Terminplan ist noch nicht beschlossen.</div>';
    }

    // Frontend-Umschalter: erscheint wenn mehrere aktive (nicht-Entwurf) Profile vorhanden
    $visible_profiles = array_filter( gsh_tp_get_profiles(), function ( $p ) {
        return ! empty( $p['is_active'] ) && empty( $p['is_draft'] );
    } );
    if ( count( $visible_profiles ) > 1 ) {
        $o .= '<div class="gtp-sj-switch" style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px">';
        foreach ( $visible_profiles as $vp ) {
            $on_cls = ( $vp['id'] === $profile_id ) ? ';background:var(--gtp-accent);color:#fff' : '';
            $switch_url = esc_url( add_query_arg( 'sj', rawurlencode( $vp['id'] ) ) );
            $o .= '<a href="' . $switch_url . '" class="gtp-sj-btn"'
                . ' style="padding:4px 14px;border-radius:20px;border:1.5px solid var(--gtp-accent);'
                . 'font-size:13px;font-weight:600;text-decoration:none;color:var(--gtp-accent)' . $on_cls . '">'
                . esc_html( $vp['label'] ) . '</a>';
        }
        $o .= '</div>';
    }

    // Header
    $o .= '<div class="gtp-hd">';
    $o .= '<div class="gtp-hd-left">';
    $o .= '<h2 class="gtp-t">Jahresterminplan</h2>';
    $o .= '<span class="gtp-subtitle">' . esc_html( $profile['label'] ) . ' &mdash; Gesamtschule Horst</span>';
    $o .= '</div>';
    $o .= '<div class="gtp-search">';
    $o .= '<div class="gtp-search-wrap">';
    $o .= gsh_tp_icon( 'search', '1rem', 'gtp-search-icon' );
    $o .= '<input type="search" id="gtp-search-input" class="gtp-search-input"'
        . ' placeholder="Termin suchen\u2026" autocomplete="off"'
        . ' oninput="gtpSearchInput(this.value)" />';
    $o .= '</div>';
    $o .= '<div class="gtp-search-results" id="gtp-search-results" style="display:none"></div>';
    $o .= '</div>'; // .gtp-search
    $o .= '<span class="gtp-meta">Aktualisiert: ' . esc_html( $sync_display ) . ' Uhr</span>';
    $o .= '<button type="button" id="gtp-tour-btn" onclick="gtpTourStart(true)"'
        . ' aria-label="Hilfe-Tour starten"'
        . ' title="Hilfe-Tour starten">&#10067;</button>';
    $o .= '</div>'; // .gtp-hd

    // Änderungs-Banner (wird per JS befüllt und ggf. eingeblendet)
    $o .= '<div class="gtp-changes" id="gtpChanges" style="display:none">';
    $o .= '<div class="gtp-changes-inner">';
    $o .= '<span class="gtp-changes-icon">' . gsh_tp_icon( 'bell' ) . '</span>';
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

    // Filter-Buttons (dynamisch aus Kategorie-Einstellungen)
    $o .= '<div class="gtp-filt-wrap">';
    $o .= '<span class="gtp-filt-lbl">Anzeige filtern: <span id="gtp-filt-count" class="gtp-filt-count"></span></span>';
    $o .= '<div class="gtp-filt">';
    foreach ( gsh_tp_get_categories() as $cat ) {
        $o .= '<button type="button" class="gtp-fb gtp-fb-on" data-c="'
            . esc_attr( $cat['slug'] ) . '" onclick="gtpFil(this)" aria-pressed="true">'
            . esc_html( $cat['label'] ) . '</button>';
    }
    // „Sonstige"-Button für Termine ohne Kategorie-Match
    $o .= '<button type="button" class="gtp-fb gtp-fb-on" data-c="standard" onclick="gtpFil(this)" aria-pressed="true">Sonstige</button>';
    $o .= '<button type="button" id="gtp-reset" class="gtp-reset" onclick="gtpReset()" style="display:none">'
        . gsh_tp_icon( 'x', '0.85em' ) . ' Alle anzeigen</button>';
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
    $o .= '<button type="button" class="gtp-btn gtp-btn-pdf" onclick="gtpPdf()">' . gsh_tp_icon( 'file-text' ) . ' Quartal als PDF</button>';
    $o .= '<button type="button" class="gtp-btn gtp-btn-pdf" onclick="gtpPdfAll()">' . gsh_tp_icon( 'file-text' ) . ' Alle Quartale als PDF</button>';
    $o .= '<button type="button" class="gtp-btn gtp-btn-feedback" id="gtp-feedback-btn"'
        . ' onclick="gtpFeedbackOpen()" aria-label="Feedback geben">'
        . '&#128172; Feedback</button>';
    $o .= '<span class="gtp-src">Quelle: IServ-Kalender</span>';
    $o .= ' &nbsp;|&nbsp; <button type="button" class="gtp-version-btn" '
        . 'onclick="gtpChangelogOpen()" aria-label="Changelog anzeigen">'
        . 'v' . esc_html( GSH_TP_VERSION ) . '</button>';
    $o .= '</div>'; // .gtp-ft

    // ── Feedback-Modal ────────────────────────────────────────────────────────
    $o .= '<div id="gtp-feedback-overlay" class="gtp-popup-overlay" role="dialog"'
        . ' aria-modal="true" aria-labelledby="gtp-feedback-title"'
        . ' style="display:none" tabindex="-1">';
    $o .= '<div class="gtp-popup-card gtp-feedback-card">';
    $o .= '<button type="button" class="gtp-popup-close" onclick="gtpFeedbackClose()"'
        . ' aria-label="Schließen">&times;</button>';
    $o .= '<h3 class="gtp-popup-title" id="gtp-feedback-title">&#128172; Feedback geben</h3>';
    $o .= '<p class="gtp-feedback-intro">Dein Hinweis hilft uns den Terminplan zu verbessern.'
        . ' Das Formular öffnet sich im neuen Tab &ndash; du musst dort nur noch auf <em>Senden</em> klicken.</p>';
    $o .= '<div class="gtp-feedback-types" role="group" aria-label="Feedback-Typ wählen">';
    $types = array(
        'bug'    => array( 'emoji' => '&#128027;', 'label' => 'Fehler melden' ),
        'wish'   => array( 'emoji' => '&#128161;', 'label' => 'Funktionswunsch' ),
        'praise' => array( 'emoji' => '&#128077;', 'label' => 'Lob' ),
        'other'  => array( 'emoji' => '&#128172;', 'label' => 'Sonstiges' ),
    );
    foreach ( $types as $key => $t ) {
        $o .= '<button type="button" class="gtp-feedback-type" data-type="' . esc_attr( $key ) . '"'
            . ' data-label="' . esc_attr( $t['label'] ) . '"'
            . ' onclick="gtpFeedbackType(this)">'
            . $t['emoji'] . ' ' . esc_html( $t['label'] )
            . '</button>';
    }
    $o .= '</div>'; // .gtp-feedback-types
    $o .= '<div class="gtp-feedback-field">';
    $o .= '<label for="gtp-feedback-text" class="gtp-feedback-label">Beschreibe kurz dein Anliegen</label>';
    $o .= '<textarea id="gtp-feedback-text" class="gtp-feedback-textarea"'
        . ' rows="4" maxlength="1000"'
        . ' placeholder="Was ist aufgefallen? Was wünschst du dir?"></textarea>';
    $o .= '<div class="gtp-feedback-counter"><span id="gtp-feedback-count">0</span> / 1000</div>';
    $o .= '</div>';
    $o .= '<div class="gtp-feedback-actions">';
    $o .= '<button type="button" class="gtp-btn" id="gtp-feedback-submit"'
        . ' onclick="gtpFeedbackSubmit()"'
        . ' data-url="' . esc_attr( GSH_TP_FEEDBACK_URL ) . '"'
        . ' disabled>Formular &ouml;ffnen &#8599;</button>';
    $o .= '<button type="button" class="gtp-btn gtp-btn-pdf" onclick="gtpFeedbackClose()">Abbrechen</button>';
    $o .= '</div>';
    $o .= '</div>'; // .gtp-feedback-card
    $o .= '</div>'; // #gtp-feedback-overlay

    $o .= '<button type="button" id="gtp-heute-btn" onclick="gtpScrollToday()" aria-label="Zur heutigen Woche springen">' . gsh_tp_icon( 'map-pin' ) . ' Heute</button>';

    // ── Theme-Switcher (schwebendes Gear-Icon unten rechts) ──────────────────
    $o .= '<div id="gtp-theme-wrap" aria-label="Theme-Einstellungen">';
    $o .= '<div id="gtp-theme-panel" role="group" aria-label="Theme wählen">';
    $o .= '<button type="button" class="gtp-theme-opt" data-gtp-mode="light" '
        . 'onclick="gtpSetTheme(\'light\')" aria-label="Helles Theme">'
        . '<span class="gtp-theme-opt-icon">&#9728;&#65039;</span> Hell'
        . '</button>';
    $o .= '<button type="button" class="gtp-theme-opt" data-gtp-mode="dark" '
        . 'onclick="gtpSetTheme(\'dark\')" aria-label="Dunkles Theme">'
        . '<span class="gtp-theme-opt-icon">&#127769;</span> Dunkel'
        . '</button>';
    $o .= '<button type="button" class="gtp-theme-opt" data-gtp-mode="auto" '
        . 'onclick="gtpSetTheme(\'auto\')" aria-label="System-Theme">'
        . '<span class="gtp-theme-opt-icon">&#128187;</span> System'
        . '</button>';
    $o .= '</div>'; // #gtp-theme-panel
    $o .= '<button type="button" id="gtp-theme-btn" onclick="gtpThemeToggle()" '
        . 'aria-label="Theme wechseln" aria-expanded="false">'
        . '&#9881;&#65039;'
        . '</button>';
    $o .= '</div>'; // #gtp-theme-wrap

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

    // ── Changelog-Modal (Frontend) ───────────────────────────────────────────
    // Zeigt nur FEATURE/UX/BUGFIX/SECURITY, maximal die letzten 3 Versionen
    $cl_frontend_tags = array( 'FEATURE', 'UX', 'BUGFIX', 'SECURITY' );
    $cl_all           = gsh_tp_changelog();
    $cl_frontend      = array();
    foreach ( $cl_all as $version_block ) {
        $filtered = array_values( array_filter(
            $version_block['entries'],
            static fn( $e ) => in_array( $e['tag'], $cl_frontend_tags, true )
        ) );
        if ( ! empty( $filtered ) ) {
            $cl_frontend[] = array(
                'version' => $version_block['version'],
                'entries' => $filtered,
            );
        }
        if ( count( $cl_frontend ) >= 3 ) {
            break;
        }
    }

    $o .= '<div id="gtpChangelog" class="gtp-popup-overlay" role="dialog" aria-modal="true" '
        . 'aria-label="Changelog" style="display:none" tabindex="-1">';
    $o .= '<div class="gtp-popup-card gtp-changelog-card">';
    $o .= '<button type="button" class="gtp-popup-close" onclick="gtpChangelogClose()" '
        . 'aria-label="Schließen">&times;</button>';
    $o .= '<h3 class="gtp-popup-title">&#128221; Was ist neu?</h3>';
    foreach ( $cl_frontend as $block ) {
        $o .= '<div class="gtp-cl-version">';
        $o .= '<span class="gtp-cl-vtag">Version ' . esc_html( $block['version'] ) . '</span>';
        $o .= '<ul class="gtp-cl-list">';
        foreach ( $block['entries'] as $entry ) {
            $tag_class = 'gtp-cl-tag gtp-cl-tag--' . strtolower( esc_attr( $entry['tag'] ) );
            $o .= '<li><span class="' . $tag_class . '">' . esc_html( $entry['tag'] ) . '</span> '
                . esc_html( $entry['text'] ) . '</li>';
        }
        $o .= '</ul>';
        $o .= '</div>';
    }
    $o .= '</div>'; // .gtp-changelog-card
    $o .= '</div>'; // #gtpChangelog

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

    // Spaltenbezeichner für data-label (Card-Layout im Bereich 1024–1200 px)
    $day_labels = array( 'Mo', 'Di', 'Mi', 'Do', 'Fr' );

    while ( $c <= $qe && $lim-- > 0 ) {
        $sw      = gsh_tp_schulwoche( $c->format( 'Y-m-d' ), $sjs );
        $friday  = ( clone $c )->modify( '+4 days' )->format( 'Y-m-d' );
        $h .= $friday < $td ? '<tr class="gtp-past">' : '<tr>';
        $h .= '<td class="gs" data-label="' . esc_attr__( 'SW', 'gsh-terminplan' ) . '"><b>' . ( $sw > 0 ? sprintf( '%02d', $sw ) : '–' ) . '</b></td>';

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

            $h .= '<td class="' . esc_attr( $cl ) . '" data-label="' . esc_attr( $day_labels[ $d ] ) . '">';
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
        $h .= '<td class="gh gnc" data-label="' . esc_attr__( 'Hinweise', 'gsh-terminplan' ) . '">';

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
        . ( 0 === $start ? ' disabled' : '' ) . '>' . gsh_tp_icon( 'chevron-left' ) . '</button>';
    $h .= '<div class="gtp-mob-nav-info">'
        . '<div class="gtp-mob-nav-sw"></div>'   // wird von JS befüllt
        . '<div class="gtp-mob-nav-dates"></div>' // wird von JS befüllt
        . '</div>';
    // 📍-Button springt zur aktuellen Woche zurück
    $h .= '<button type="button" class="gtp-mob-today-btn" onclick="gtpMobToday(this)"'
        . ' aria-label="Zur heutigen Woche springen">' . gsh_tp_icon( 'map-pin' ) . '</button>';
    $h .= '<button type="button" class="gtp-mob-next" onclick="gtpMobNav(this,1)"'
        . ( ( $start + 2 ) >= $total ? ' disabled' : '' ) . '>' . gsh_tp_icon( 'chevron-right' ) . '</button>';
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

        $week_cls = 'gtp-mob-week' . ( $wi === $today_week_idx ? ' gtp-mob-today-week' : '' );
        $h .= '<div class="' . $week_cls . '" data-wi="' . $wi . '"' . $week_vis . '>';

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
    $cat = gsh_tp_category_css();
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
  /* Spacing-Tokens (basierend auf 4px-Raster) */
  --gtp-space-1:  4px;
  --gtp-space-2:  8px;
  --gtp-space-3:  12px;
  --gtp-space-4:  16px;
  --gtp-space-6:  24px;
  --gtp-space-8:  32px;
  /* Border-Radius-Tokens */
  --gtp-radius-sm:   4px;
  --gtp-radius-md:   8px;
  --gtp-radius-lg:   12px;
  --gtp-radius-xl:   16px;
  --gtp-radius-pill: 50px;
  --gtp-radius-full: 50%;
  /* Kategorie – Standard (Neutral/Pastell) – immer als Fallback vorhanden */
  --c-st-bg:#f1f5f9; --c-st-bd:#64748b; --c-st-tx:#334155;
  /* Konfigurierbare Kategorien (dynamisch – via gsh_tp_category_css()) */
  ' . $cat['vars'] . '
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
.gtp-filt-count{
  font-size:.68rem;font-weight:500;color:var(--gtp-text-muted);
  margin-left:.35em;letter-spacing:.02em;text-transform:none;
}
.gtp-fb{
  padding:5px 14px;border:1.5px solid;border-radius:20px;
  font-size:.75rem;cursor:pointer;font-weight:600;line-height:1.5;
  transition:var(--gtp-tr);user-select:none;
}
.gtp-fb[data-c="standard"] {border-color:var(--c-st-bd);background:var(--c-st-bg);color:var(--c-st-tx)}
.gtp-fb:hover{filter:brightness(.9);transform:translateY(-1px);box-shadow:0 2px 6px rgba(0,0,0,.1)}
/* Toggle-Zustand: aktiv = volle Farbe, inaktiv = deutlich gedimmt */
.gtp-fb-on{/* volle Farbe via .gc-* / [data-c] Regeln */}
.gtp-fb-off{opacity:.28;filter:grayscale(.8);text-decoration:line-through;text-decoration-thickness:1.5px}

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

  /* ── Heutige Woche: pulsierender Punkt + hervorgehobener Header ── v3.6.3 */
  @keyframes gtpMobPulse{
    0%,100%{opacity:1;transform:scale(1)}
    50%{opacity:.4;transform:scale(1.55)}
  }
  .gtp-mob-today-week .gtp-mob-wh{
    background:var(--gtp-today-bg);
    color:var(--gtp-today-bd);
    border-bottom-color:var(--gtp-today-bd);
  }
  .gtp-mob-today-week .gtp-mob-wh::before{
    content:"";display:inline-block;flex-shrink:0;
    width:7px;height:7px;border-radius:50%;
    background:var(--gtp-today-bd);margin-right:8px;
    animation:gtpMobPulse 1.6s ease-in-out infinite;
  }
  .gtp-mob-today-week .gtp-mob-wh-sub{color:var(--gtp-today-bd);opacity:.7}

  /* Vergrößerter „HEUTE"-Badge (v3.6.3) */
  .gtp-mob-badge{font-size:.62rem;padding:2px 6px;border-radius:5px}

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
/* ── Changelog-Modal (Frontend) ────────────────────────────────────────── */
.gtp-version-btn{
  background:var(--gtp-surface,#f8fafc);
  border:1px solid var(--gtp-border,#e2e8f0);border-radius:4px;
  padding:3px 9px;font-size:11px;color:var(--gtp-text-muted,#64748b);
  cursor:pointer;transition:background .15s,color .15s,border-color .15s;
  font-family:inherit;
}
.gtp-version-btn:hover{
  background:var(--gtp-accent,#2563eb);color:#fff;
  border-color:var(--gtp-accent,#2563eb);
}
.gtp-changelog-card{
  max-width:560px;
  max-height:75vh;
  overflow-y:auto;
}
.gtp-cl-version{margin-bottom:1.2rem}
.gtp-cl-vtag{
  display:inline-block;font-weight:700;font-size:13px;
  color:var(--gtp-text,#1e293b);margin-bottom:.4rem;
}
.gtp-cl-list{margin:.3rem 0 0 1rem;padding:0;list-style:disc}
.gtp-cl-list li{font-size:13px;color:var(--gtp-text-muted,#64748b);margin:.25rem 0}
.gtp-cl-tag{
  display:inline-block;font-size:10px;font-weight:700;text-transform:uppercase;
  letter-spacing:.04em;padding:1px 6px;border-radius:3px;
  margin-right:5px;vertical-align:middle;
}
.gtp-cl-tag--feature {background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0}
.gtp-cl-tag--ux      {background:#dbeafe;color:#2563eb;border:1px solid #bfdbfe}
.gtp-cl-tag--bugfix  {background:#fee2e2;color:#dc2626;border:1px solid #fecaca}
.gtp-cl-tag--security{background:#fef3c7;color:#d97706;border:1px solid #fde68a}
/* ── Dynamische Kategorie-CSS (konfigurierbare Kategorien überschreiben Standard) ── */
' . $cat['rules'] . '
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

var gtpSel = {}; // { slug: true } → Kategorie VERSTECKT; leer = alle sichtbar

/**
 * Verarbeitet einen Klick auf einen Kategorie-Filterbutton.
 * Jeder Klick schaltet die Kategorie unabhängig ein oder aus (echter Toggle-Modus).
 * Der Zustand wird in localStorage gespeichert und beim nächsten Besuch wiederhergestellt.
 * Aufgerufen durch onclick der .gtp-fb-Buttons.
 */
function gtpFil(btn){
  var c = btn.getAttribute("data-c");
  if(gtpSel[c]){
    delete gtpSel[c]; /* Kategorie wieder einblenden */
  } else {
    gtpSel[c] = true; /* Kategorie ausblenden */
  }
  gtpApply();
  gtpSaveFilters();
}

/**
 * Speichert den aktuellen Filter-Zustand im localStorage.
 */
function gtpSaveFilters(){
  try{ localStorage.setItem("gtp_filters", JSON.stringify(gtpSel)); }catch(e){}
}

/**
 * Setzt den Kategorie-Filter zurück – alle Kategorien werden wieder eingeblendet.
 * Aufgerufen durch onclick des Reset-Buttons (#gtp-reset).
 */
function gtpReset(){
  gtpSel = {};
  gtpApply();
  gtpSaveFilters();
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
  var categoryOk = !gtpSel[c]; /* sichtbar wenn Kategorie nicht in der Versteckt-Liste */

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
  var hiddenCount = Object.keys(gtpSel).length;
  var totalBtns = 0, visibleBtns = 0;

  /* Filter-Buttons aktualisieren */
  document.querySelectorAll(".gtp-fb").forEach(function(btn){
    var c      = btn.getAttribute("data-c");
    var active = !gtpSel[c]; /* aktiv = NICHT in der Versteckt-Liste */
    totalBtns++;
    if(active) visibleBtns++;
    btn.classList.toggle("gtp-fb-on",  active);
    btn.classList.toggle("gtp-fb-off", !active);
    btn.setAttribute("aria-pressed", active ? "true" : "false");
  });

  /* Zähler-Label aktualisieren */
  var countEl = document.getElementById("gtp-filt-count");
  if(countEl){
    countEl.textContent = hiddenCount > 0
      ? "(" + visibleBtns + "\u202f/\u202f" + totalBtns + " sichtbar)"
      : "";
  }

  /* Termine in Tagesspalten: kombinierter Check (Suche + Kategorie) */
  document.querySelectorAll(".ge[data-c]").forEach(function(el){
    gtpApplyVisibility(el);
  });

  /* Lange Termine + Frist-Notizen: nur Kategorie-Filter */
  document.querySelectorAll(".gn-long[data-c], .gn[data-c]").forEach(function(el){
    var c = el.getAttribute("data-c");
    el.style.display = !gtpSel[c] ? "" : "none";
  });

  /* Reset-Button zeigen / verstecken */
  var resetBtn = document.getElementById("gtp-reset");
  if(resetBtn) resetBtn.style.display = hiddenCount > 0 ? "" : "none";
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
  /* Filter-Zustand aus localStorage wiederherstellen */
  try{
    var saved = localStorage.getItem("gtp_filters");
    if(saved){ gtpSel = JSON.parse(saved); gtpApply(); }
  }catch(e){}
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
    /* Kategorie-Farben: NUR Balken-Farbe (dynamisch aus data-categories) */
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
    /* Druckfarben erzwingen (Basis; Kategorie-Klassen werden dynamisch ergänzt) */
    "thead th,.gs,.ge,.gn-long,.gn,.ld,.gt-today,.gt-hol,.gc-standard{" +
    "  -webkit-print-color-adjust:exact;print-color-adjust:exact}",
    "@media print{body{padding:0}}"
  ].join("\n");

  /* ── Kategorien dynamisch aus data-categories ── */
  var gtpEl=document.getElementById("gtp");
  var cats=[];
  try{cats=JSON.parse((gtpEl&&gtpEl.dataset.categories)||"[]");}catch(e){}

  /* Dynamisches Kategorie-CSS (Balken-Farbe + print-color-adjust) */
  var catCSS=cats.map(function(c){
    return ".gc-"+c.slug+"{border-left-color:"+c.border+"}";
  }).join("\n");
  var gcClasses=cats.map(function(c){return ".gc-"+c.slug;}).join(",");
  if(gcClasses){
    catCSS+="\n"+gcClasses+"{-webkit-print-color-adjust:exact;print-color-adjust:exact}";
  }
  CSS+="\n"+catCSS;

  /* ── Legende: farbige Punkte (dynamisch) ── */
  var LEG='<div class="leg">'
    +cats.map(function(c){
      return '<span class="leg-item"><span class="ld" style="background:'+c.border+'"></span>'+c.label+'</span>';
    }).join('')
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
 * Zeigt "👉 Heute" in der Nav-Mitte wenn die aktuelle Woche im Sichtfenster liegt.
 * Wird von gtpMobUpdateNav() nach jedem Navigationsschritt aufgerufen.
 * @param {Element} container  Das .gtp-mob-weeks Element.
 * @param {Element} mob        Das übergeordnete .gtp-mob Element.
 * @param {number}  start      Aktueller Fensterbeginn (data-start).
 * @param {number}  visible    Fenstergröße (data-visible).
 */
function gtpMobTodayIndicator(container,mob,start,visible){
  var todayIdx=parseInt(container.getAttribute("data-today-idx"),10);
  if(isNaN(todayIdx)) return;
  var swEl=mob.querySelector(".gtp-mob-nav-sw");
  if(!swEl) return;
  var inWindow=(todayIdx>=start&&todayIdx<start+visible);
  /* Kleinen "Heute"-Hinweis voranstellen wenn die heutige Woche sichtbar ist */
  if(inWindow){
    swEl.setAttribute("data-gtp-had-today","1");
    if(swEl.firstChild&&swEl.firstChild.nodeType===Node.TEXT_NODE){
      /* Prefix nur einmal hinzufügen */
      if(swEl.textContent.indexOf("\ud83d\udc49")===0) return;
      swEl.textContent="\ud83d\udc49\u00a0"+swEl.textContent;
    }
  } else {
    /* Heute nicht im Fenster → Prefix entfernen falls vorhanden */
    if(swEl.textContent.indexOf("\ud83d\udc49")===0){
      swEl.textContent=swEl.textContent.replace(/^\ud83d\udc49\u00a0/,"");
    }
  }
}

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

  /* Heute-Indikator in der Nav aktualisieren (v3.6.3) */
  var start  =parseInt(container.getAttribute("data-start"),  10)||0;
  var visible=parseInt(container.getAttribute("data-visible"),10)||2;
  gtpMobTodayIndicator(container,mob,start,visible);
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
  /* Changelog-Modal: Klick auf Overlay-Hintergrund schließt es */
  var cl=document.getElementById("gtpChangelog");
  if(cl && cl.style.display!=="none" && e.target===cl){
    gtpChangelogClose();
  }
});

/* ── Changelog-Modal (Frontend) ────────────────────────────── */
function gtpChangelogOpen(){
  var m=document.getElementById("gtpChangelog");
  if(!m)return;
  m.style.display="flex";
  m.focus();
}
function gtpChangelogClose(){
  var m=document.getElementById("gtpChangelog");
  if(m)m.style.display="none";
}

/* ── Changelog-Modal (Admin) ────────────────────────────────── */
function gshAdminChangelogOpen(){
  var m=document.getElementById("gshAdminChangelog");
  if(m)m.style.display="block";
}
function gshAdminChangelogClose(){
  var m=document.getElementById("gshAdminChangelog");
  if(m)m.style.display="none";
}

/* Escape-Taste schließt beide Changelog-Modals */
document.addEventListener("keydown",function(e){
  if(e.key==="Escape"){
    gtpChangelogClose();
    gshAdminChangelogClose();
  }
});

/* ── Theme-Switcher ─────────────────────────────────────────────── */

/**
 * Theme auf den .gtp-Container anwenden und in localStorage speichern.
 * @param {string} mode  'light' | 'dark' | 'auto'
 */
function gtpSetTheme(mode){
  if(mode!=="light"&&mode!=="dark"&&mode!=="auto")mode="auto";
  var container=document.getElementById("gtp");
  if(container)container.setAttribute("data-gtp-theme",mode);
  try{localStorage.setItem("gtp-theme",mode);}catch(e){}
  /* Aktiv-Klasse auf den gewählten Button setzen */
  document.querySelectorAll(".gtp-theme-opt").forEach(function(btn){
    btn.classList.toggle("gtp-theme-opt-active",btn.getAttribute("data-gtp-mode")===mode);
  });
  gtpThemePanelClose();
}

/**
 * Gespeicherte Theme-Einstellung beim Laden anwenden.
 * Läuft sofort – verhindert Aufblitzen des falschen Themes.
 */
function gtpInitTheme(){
  var saved="auto";
  try{saved=localStorage.getItem("gtp-theme")||"auto";}catch(e){}
  if(saved!=="light"&&saved!=="dark"&&saved!=="auto")saved="auto";
  gtpSetTheme(saved);
}

/** Theme-Panel öffnen / schließen */
function gtpThemeToggle(){
  var panel=document.getElementById("gtp-theme-panel");
  var btn=document.getElementById("gtp-theme-btn");
  if(!panel||!btn)return;
  if(panel.classList.contains("gtp-theme-panel-vis")){
    gtpThemePanelClose();
  }else{
    panel.classList.add("gtp-theme-panel-vis");
    btn.classList.add("gtp-theme-open");
    btn.setAttribute("aria-expanded","true");
  }
}

function gtpThemePanelClose(){
  var panel=document.getElementById("gtp-theme-panel");
  var btn=document.getElementById("gtp-theme-btn");
  if(panel)panel.classList.remove("gtp-theme-panel-vis");
  if(btn){btn.classList.remove("gtp-theme-open");btn.setAttribute("aria-expanded","false");}
}

/* Panel schließen bei Klick außerhalb des Theme-Wrappers */
document.addEventListener("click",function(e){
  var wrap=document.getElementById("gtp-theme-wrap");
  if(wrap&&!wrap.contains(e.target))gtpThemePanelClose();
});

/* Escape schließt das Panel */
document.addEventListener("keydown",function(e){
  if(e.key==="Escape")gtpThemePanelClose();
});

/* System-Theme live übernehmen wenn Modus = auto */
if(window.matchMedia){
  window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change",function(){
    var container=document.getElementById("gtp");
    if(container&&container.getAttribute("data-gtp-theme")==="auto"){
      /* data-gtp-theme="auto" bleibt – CSS @media-Regel übernimmt die Darstellung */
      document.querySelectorAll(".gtp-theme-opt").forEach(function(btn){
        btn.classList.toggle("gtp-theme-opt-active",btn.getAttribute("data-gtp-mode")==="auto");
      });
    }
  });
}

/* Theme sofort initialisieren (kein Aufblitzen) */
gtpInitTheme();

/* ── Onboarding-Tour (Shepherd.js) ──────────────────────────── */

/**
 * Startet die Onboarding-Tour.
 * @param {boolean} force  true = auch wenn bereits gesehen (manueller Start)
 */
function gtpTourStart(force) {
  // Shepherd.js noch nicht geladen? Abbruch mit Hinweis.
  if (typeof Shepherd === 'undefined') {
    console.warn('GSH Tour: Shepherd.js nicht geladen.');
    return;
  }

  // Beim ersten Besuch automatisch starten – sonst nur wenn force=true
  var seen = false;
  try { seen = !!localStorage.getItem('gtp-tour-seen'); } catch(e) {}
  if (seen && !force) return;

  var tour = new Shepherd.Tour({
    useModalOverlay: true,
    defaultStepOptions: {
      cancelIcon:  { enabled: true },
      scrollTo:    { behavior: 'smooth', block: 'center' },
      modalOverlayOpeningRadius: 8,
      popperOptions: {
        modifiers: [{ name: 'offset', options: { offset: [0, 14] } }]
      }
    }
  });

  // Hilfsfunktion: Shepherd-native Buttons-Array bauen
  function makeButtons(idx, total, isLast) {
    var btns = [];
    if (idx > 1) {
      btns.push({
        text:    '\u276E',
        classes: 'shepherd-button shepherd-button-secondary gtp-tour-arrow',
        action:  function() { tour.back(); }
      });
    }
    btns.push({
      text:    isLast ? '\u2713' : '\u276F',
      classes: 'shepherd-button shepherd-button-primary gtp-tour-arrow',
      action:  function() { isLast ? tour.complete() : tour.next(); }
    });
    return btns;
  }

  var steps = [
    {
      id:      'tabs',
      title:   '\uD83D\uDCC5 Quartal-Navigation',
      text:    'Mit diesen Tabs wechselst du zwischen den vier Schulquartalen. Das aktuelle Quartal ist beim \u00D6ffnen automatisch ausgew\u00E4hlt.',
      attachTo: { element: '.gtp-tabs', on: 'bottom' },
      index:    1
    },
    {
      id:      'filter',
      title:   '\uD83C\uDFA8 Kategorien filtern',
      text:    'Hier kannst du einzelne Terminarten ein- oder ausblenden \u2013 z.\u202FB. nur Konferenzen oder nur Ferientermine anzeigen. Einfach auf eine Kategorie klicken.',
      attachTo: { element: '.gtp-filt-wrap', on: 'bottom' },
      index:    2
    },
    {
      id:      'search',
      title:   '\uD83D\uDD0D Terminsuche',
      text:    'Tippe hier einen Begriff ein um alle Quartale gleichzeitig zu durchsuchen. Treffer werden direkt hervorgehoben.',
      attachTo: { element: '#gtp-search-input', on: 'bottom' },
      index:    3
    },
    {
      id:      'pdf',
      title:   '\uD83D\uDDA8\uFE0F PDF-Export',
      text:    'Den aktuellen Terminplan als PDF speichern oder drucken \u2013 entweder das aktuelle Quartal oder alle vier auf einmal.',
      attachTo: { element: '.gtp-btn-pdf', on: 'top' },
      index:    4
    },
    {
      id:      'theme',
      title:   '\uD83C\uDF19 Dark Mode',
      text:    'Hier kannst du zwischen hellem und dunklem Design wechseln \u2013 oder die Einstellung deines Ger\u00E4ts automatisch \u00FCbernehmen lassen.',
      attachTo: { element: '#gtp-theme-btn', on: 'left' },
      index:    5
    }
  ];

  var total = steps.length;

  steps.forEach(function(s) {
    var isLast = s.index === total;
    tour.addStep({
      id:      s.id,
      title:   s.title,
      // Fortschritt direkt im Text-Bereich als kleine Zeile
      text:    s.text + '<p class="gtp-tour-progress">' + s.index + '\u00A0/\u00A0' + total + '</p>',
      attachTo: s.attachTo,
      buttons:  makeButtons(s.index, total, isLast)
    });
  });

  // Tour abgeschlossen oder abgebrochen: als gesehen markieren
  tour.on('complete', function() {
    try { localStorage.setItem('gtp-tour-seen', '1'); } catch(e) {}
  });
  tour.on('cancel', function() {
    try { localStorage.setItem('gtp-tour-seen', '1'); } catch(e) {}
  });

  tour.start();
}

// Automatisch beim ersten Besuch starten (nach kurzem Delay damit die Seite fertig ist)
document.addEventListener('DOMContentLoaded', function() {
  setTimeout(function() { gtpTourStart(false); }, 800);
});

/* ── Feedback-Modal ──────────────────────────────────────────── */

var gtpFeedbackSelectedType  = '';
var gtpFeedbackSelectedLabel = '';

function gtpFeedbackOpen() {
  var overlay = document.getElementById('gtp-feedback-overlay');
  if (!overlay) return;
  overlay.style.display = 'flex';
  overlay.focus();
  gtpFeedbackSelectedType  = '';
  gtpFeedbackSelectedLabel = '';
  var textarea = document.getElementById('gtp-feedback-text');
  if (textarea) textarea.value = '';
  var counter = document.getElementById('gtp-feedback-count');
  if (counter) counter.textContent = '0';
  var submit = document.getElementById('gtp-feedback-submit');
  if (submit) submit.disabled = true;
  document.querySelectorAll('.gtp-feedback-type').forEach(function(b) {
    b.classList.remove('gtp-feedback-type-active');
  });
}

function gtpFeedbackClose() {
  var overlay = document.getElementById('gtp-feedback-overlay');
  if (overlay) overlay.style.display = 'none';
}

function gtpFeedbackType(btn) {
  document.querySelectorAll('.gtp-feedback-type').forEach(function(b) {
    b.classList.remove('gtp-feedback-type-active');
  });
  btn.classList.add('gtp-feedback-type-active');
  gtpFeedbackSelectedType  = btn.getAttribute('data-type')  || '';
  gtpFeedbackSelectedLabel = btn.getAttribute('data-label') || '';
  gtpFeedbackUpdateSubmit();
}

function gtpFeedbackUpdateSubmit() {
  var textarea = document.getElementById('gtp-feedback-text');
  var submit   = document.getElementById('gtp-feedback-submit');
  if (!submit) return;
  var hasType = gtpFeedbackSelectedType !== '';
  var hasText = textarea && textarea.value.trim().length > 0;
  submit.disabled = !(hasType && hasText);
}

function gtpFeedbackSubmit() {
  var submit   = document.getElementById('gtp-feedback-submit');
  var textarea = document.getElementById('gtp-feedback-text');
  if (!submit || submit.disabled) return;
  var baseUrl = submit.getAttribute('data-url') || '';
  var url = baseUrl;
  window.open(url, '_blank', 'noopener,noreferrer');
  gtpFeedbackClose();
  var fbBtn = document.getElementById('gtp-feedback-btn');
  if (fbBtn) {
    var orig = fbBtn.innerHTML;
    fbBtn.innerHTML = '\u2713 Danke!';
    fbBtn.disabled  = true;
    setTimeout(function() {
      fbBtn.innerHTML = orig;
      fbBtn.disabled  = false;
    }, 3000);
  }
}

// Escape schließt das Feedback-Modal
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    var overlay = document.getElementById('gtp-feedback-overlay');
    if (overlay && overlay.style.display !== 'none') gtpFeedbackClose();
  }
});

// Zeichenzähler
document.addEventListener('DOMContentLoaded', function() {
  var ta = document.getElementById('gtp-feedback-text');
  if (ta) {
    ta.addEventListener('input', function() {
      var counter = document.getElementById('gtp-feedback-count');
      if (counter) counter.textContent = ta.value.length;
      gtpFeedbackUpdateSubmit();
    });
  }
  // Klick auf Overlay-Hintergrund schließt Modal
  var overlay = document.getElementById('gtp-feedback-overlay');
  if (overlay) {
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) gtpFeedbackClose();
    });
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
    // Alle profil-spezifischen Cron-Jobs und Freshness-Transients leeren
    wp_clear_scheduled_hook( 'gsh_tp_cron_refresh' );
    delete_transient( GSH_TP_FRESH_KEY ); // globaler Fallback-Key
    foreach ( gsh_tp_get_profiles() as $p ) {
        $pid = sanitize_key( $p['id'] );
        delete_transient( gsh_tp_ck( 'gsh_tp_fresh_', $pid ) );
        delete_transient( 'gsh_tp_sched_' . $pid );
    }
} );

/**
 * Deinstallations-Hook: Entfernt alle Plugin-Daten vollständig aus WordPress.
 *
 * Löscht alle gespeicherten Optionen und den Transient-Cache, sodass nach
 * einer Deinstallation keine Überreste in der WordPress-Datenbank verbleiben.
 * Wird nur aufgerufen wenn das Plugin im Backend vollständig gelöscht wird.
 *
 * @since 1.2.0 (profil-aware seit 3.5.0)
 * @return void
 */
register_uninstall_hook( __FILE__, 'gsh_tp_uninstall' );
function gsh_tp_uninstall() {
    // Globale und Legacy-Options
    foreach ( array(
        'gsh_tp_ical_url',
        'gsh_tp_cache_duration',
        'gsh_tp_schuljahr_start',
        'gsh_tp_quartal_grenzen',
        'gsh_tp_kategorie_mapping',
        'gsh_tp_categories',       // konfigurierbare Kategorien (seit 3.4.0)
        'gsh_tp_kiosk_token',
        'gsh_tp_iserv_domain',
        'gsh_tp_last_sync',
        GSH_TP_CACHE_KEY,          // permanente Daten-Option (seit 3.3.0)
        GSH_TP_BACKUP_KEY,
    ) as $opt ) {
        delete_option( $opt );
    }
    delete_transient( GSH_TP_FRESH_KEY );

    // Profil-spezifische Options und Transients löschen
    $profiles = get_option( 'gsh_tp_profiles', array() );
    if ( is_array( $profiles ) ) {
        foreach ( $profiles as $p ) {
            $pid = sanitize_key( $p['id'] ?? '' );
            if ( ! $pid ) {
                continue;
            }
            // Versionierte Keys (aktuelle Version und alle Vorgänger)
            delete_option( gsh_tp_ck( 'gsh_tp_ical_', $pid ) );
            delete_option( gsh_tp_ck( 'gsh_tp_sync_logs_', $pid ) );
            delete_transient( gsh_tp_ck( 'gsh_tp_fresh_', $pid ) );
            delete_transient( gsh_tp_ck( 'gsh_tp_chg_', $pid ) );
            // Legacy unversionierte Keys (falls noch vorhanden)
            delete_option( 'gsh_tp_ical_' . $pid );
            delete_option( 'gsh_tp_sync_logs_' . $pid );
            delete_transient( 'gsh_tp_fresh_' . $pid );
            delete_transient( 'gsh_tp_chg_' . $pid );
            // Unversionierte Keys
            delete_option( 'gsh_tp_backup_' . $pid );
            delete_option( 'gsh_tp_sync_' . $pid );
            delete_transient( 'gsh_tp_snap_' . $pid );
            delete_transient( 'gsh_tp_sched_' . $pid );
        }
    }
    delete_option( 'gsh_tp_profiles' );
    delete_option( 'gsh_tp_cache_ver' );

    wp_clear_scheduled_hook( 'gsh_tp_cron_refresh' );
}
