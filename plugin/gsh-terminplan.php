<?php
/**
 * Plugin Name: Schul-Terminplan Dashboard
 * Plugin URI:  https://example.com
 * Description: Interaktive Quartalsuebersicht des Schuljahresterminplans aus dem IServ-Kalender (iCal-Feed).
 * Version:     4.36.0
 * Author:      Open Source Community
 * License:     GPL v2 or later
 * Text Domain: gsh-terminplan
 * v4.36.0
 * - [NEU] IServ-Kalender direkt verbinden: Im Schuljahr-Bereich lässt sich wieder die Adresse eines freigegebenen IServ-Kalenders eintragen — das Plugin holt die Termine dann selbst ab und baut daraus Quartalsansicht und Druckansicht, ganz ohne Planer. Seit 4.24.0 war das Feld aus der Oberfläche verschwunden und die angezeigte Feed-URL nur noch die vom Plugin selbst erzeugte Abo-Adresse
 * - [FIX] Verbundene IServ-Kalender werden beim Senden aus dem Planer nicht mehr überschrieben (neues Feld source=planner|extern pro Kalender; after_put überspringt externe Quellen)
 * v4.35.1
 * - [FIX] Anmerkungen-Spalte blieb in allen WP-Ansichten (Kiosk, Entwurf-Vorschau, öffentliche Seite) leer, sobald ein Schuljahr über das nested schoolyears-Model (seit 4.24.0) bzw. SPA-Auto-Provisioning angelegt wurde: die Annotation-Lookup nutzte ausschließlich die alte, dafür nie befüllte gsh_tp_curriculr_profile_map-Option. Nutzt jetzt zuerst sj_key direkt aus dem Profil, Legacy-Mapping nur noch als Fallback für alte Installs
 * v4.35.0
 * - [NEU] Kalender-Fußzeile: Kolleg:innen können den ICS-Kalender-Feed über die öffentliche Seite und die Kiosk-Ansichten abonnieren — webcal://-Link plus gleich gestalteter Copy-URL-Button (webcal lädt ohne registrierten OS-Handler nur eine Datei statt zu abonnieren) als eigene Toolbar-Zeile mit Trennlinie statt Box, plus ausklappbarer Kurzanleitung (SVG-Chevron) für Outlook/Google Kalender/Apple Kalender/Thunderbird (nur bei Curriculr-verwalteten Kalendern)
 * v4.34.2
 * - [UX] Filter-Kategorien: Labels umbrachen bei zwei Wörtern (z. B. „Jahrgang 5/6") auf zwei Zeilen und machten die Pillen ungleich hoch — jetzt einzeilig. Horizontaler Scroll ohne sichtbare Scrollbar (Kategorien fielen unbemerkt aus dem sichtbaren Bereich) ersetzt durch Zeilenumbruch + funktionierendes Einklappen, generalisiert die 4.34.1-Kiosk-Lösung auf die Hauptseite
 * v4.34.1
 * - [FIX] Kiosk: Filter-Leiste ließ sich oberhalb 768px Breite nicht einklappen (Collapse-CSS war an die Mobile-Breakpoint gekoppelt) — Toggle wirkt jetzt in jeder Kiosk-Kachelbreite
 * v4.34.0
 * - [FIX] Kategorien-Sync: Planner ist jetzt alleinige Quelle — Kategorien ohne Planner-Gegenstück werden beim Push entfernt statt dauerhaft mitgeführt (WP-Stichwörter bei getroffenen Kategorien bleiben erhalten)
 * v4.33.0
 * - [NEU] Gruppen-Filter im Terminplan: Kolleg:innen können auf der öffentlichen Seite und in den Kiosk-Ansichten nach Planner-Gruppen (z. B. Eltern, Kollegium) filtern
 * - [NEU] Kategorien-Sync: Labels und Farben aus dem Planner überschreiben beim Senden automatisch die Plugin-Kategorien — Stichwörter fürs IServ-Matching bleiben erhalten
 * - [NEU] Neue Schuljahre aus dem Planner werden automatisch mit allen Gruppen-Kalendern angelegt (max 7)
 * - [UX] Schuljahr-Karte warnt, wenn Planner-Gruppen ohne eigenen Kalender sind
 * v4.32.0
 * - [UX] Schuljahr-Karte zeigt jetzt zusätzlich, wer zuletzt gespeichert hat (Autor + Version) — bisher nur Zeitstempel ohne Person
 * v4.31.0
 * - [FIX] Vom Planner gesendete neue Schuljahre waren in WordPress unsichtbar: after_put war ohne Zuordnung ein stilles No-op. Jetzt wird das Schuljahr automatisch (inaktiv) angelegt inkl. Haupt-Kalender + ICS-Cache — im Admin sichtbar und zuordenbar, Live-Anzeige bleibt unberührt
 * v4.30.0
 * - [NEU] Neue Einstellung "Schulbezeichnung (PDF-Kopfzeile)": eigener Schulname statt fest verdrahtetem "Gesamtschule Horst" auf dem Termin-PDF frei einstellbar
 * v4.29.0
 * - [SECURITY] Google-Fonts-Import entfernt — Inter wird self-hosted aus assets/fonts/ geladen (DSGVO: keine IP-Übertragung an Google mehr)
 * - [SECURITY] App-Token-Verify prüft jetzt iss/aud-Claims; Guard antwortet 401 statt 403 bei fehlendem/ungültigem Token
 * - [SECURITY] REST-PUT/Doc-Upload: Tiefenvalidierung je Event (Datum/Zeit-Format) — kaputte Events können den öffentlichen ICS-Feed nicht mehr mit 500 lahmlegen (defensives build_event)
 * - [SECURITY] repo_put atomar: paralleles Speichern zweier Nutzer erzeugt jetzt zuverlässig 409 statt Lost Update
 * - [SECURITY] Kiosk-/Entwurf-Templates senden Referrer-Policy: no-referrer (Token in URL)
 * - [SECURITY] Backup-Cron: Dateien älter als 30 Tage werden gelöscht (DSGVO-Speicherbegrenzung); index.html-Schutz neben .htaccess
 * - [DESIGN] Design-Transfer aus dem Planner: Widget---gtp-Tokens auf Curricu:lr design-tokens gemappt (Marine-Farben, Inter, Radien 14/10/6) inkl. Dark-Mode-Gegenwerte
 * - [UX] Fehler-/Hinweisboxen des Shortcodes und der Kiosk-Templates von Inline-Styles auf Token-basierte CSS-Klassen umgestellt
 * - [INFRA] Feedback-Log: Einträge älter als 90 Tage werden automatisch entfernt; doc_list ohne N+1-Query
 * v4.28.2
 * - [FIX] Kiosk (beide Modi): Anfang/Ende-Uhrzeiten wurden nirgends angezeigt — gsh_tp_parse_event() behält von DATE-TIME-Werten nur das Datum, die Uhrzeit ging verloren. Jetzt zusätzlich aus DTSTART/DTEND extrahiert (gsh_tp_augment_event_times()) und als Zeit-Label über dem Termin-Titel angezeigt
 * Changelog 4.28.1:
 * - [SECURITY] Entwurf-Kiosk-Template (page-terminplan-entwurf.php): CSP frame-ancestors + X-Frame-Options SAMEORIGIN tatsächlich ergänzt — der 4.27.2-Changelog-Eintrag dafür war nie committed worden, Server-/Host-Default blockierte weiterhin das IServ-iframe-Embedding
 * Changelog 4.28.0:
 * - [NEU] Schuljahr-Karte: manueller Planungsdokument-Upload (JSON) als Alternative zu IServ-SSO — Admin exportiert im Planer "JSON-Backup" und lädt es hier hoch; inkl. "Sichern ↓"-Download des aktuellen Stands vor dem Überschreiben
 * Changelog 4.27.1:
 * - [FIX] Schuljahre-Tab (v2): Kalender-Status (Entwurf/Beschlossen) war seit 4.24.0 nur Text-Anzeige — Umschalten fehlte, Entwurf-Kiosk dadurch für neue/synchronisierte Schuljahre unerreichbar
 * Changelog 4.27.0:
 * - [NEU] Kategorien-Tab: Kategorien aus einem Planner-Schuljahr übernehmen (Label/Farbe je Kategorie werden übernommen, WP-seitige Stichwörter für das IServ-Keyword-Matching bleiben unverändert)
 * Changelog 4.26.0:
 * - [UX] Tab „Schuljahr-Profile" umbenannt in „Schuljahre"
 * - [UX] Kiosk-Tab in System-Tab integriert; ?tab=_kiosk leitet auf ?tab=_system weiter
 * - [UX] „Als aktiv setzen" → „Als aktives Schuljahr setzen" mit Erklärungstext
 * - [UX] Schuljahr-ID und Schlüssel-Feld hinter „Erweitert" versteckt
 * - [NEU] Status-Anzeige in Schuljahr-Karte (Stufe + Zeitstempel)
 * Changelog 4.25.0:
 * - [NEU] Schuljahr löschen: nicht-aktive Schuljahre inkl. DB-Daten und ICS-Cache entfernbar
 * - [UX]  Danger-Zone als <details>-Element — zweistufige Bestätigung ohne nativen confirm()-Dialog
 * Changelog 4.24.0:
 * - [UX] Schuljahr-Profile-Tab: schoolyear-zentriertes Layout, freie Label/Key-Eingabe beim Anlegen
 * - [UX] Curriculr-Sync-Tab entfernt — Origin-Einstellung jetzt im System-Tab
 * Changelog 4.23.0:
 * - [NEU] Update-Hinweis im WP-Admin nach Plugin-Update (dismissibel, per-Version)
 * Changelog 4.22.0:
 * - [NEU] Mehrfach-Kalender: ein Schuljahr kann mehrere Gruppen-ICS-Feeds bedienen (n:m Profil-Mapping)
 * - [NEU] REST POST /curriculr/v1/profile-map — SPA kann Gruppen→Profil-Mapping direkt speichern
 * - [NEU] Lazy Migration: altes Einzel-Profil-Format wird automatisch zum neuen Array-Format migriert
 * Changelog 4.21.0:
 * - [NEU] Datensicherung-Seite: Einstellungen als JSON exportieren und importieren
 * - [NEU] Warnhinweis beim Plugin-Löschen mit Link zur Datensicherung
 * - [FIX] Uninstall-Hook löscht jetzt alle curriculr_origin / curriculr_profile_map / curriculr_db_version Optionen
 * - [FIX] Uninstall-Hook bereinigt jetzt auch den gsh_tp_curriculr_daily_backup Cron-Job
 * Changelog 4.20.1:
 * - [SECURITY] Kiosk-Template: X-Frame-Options SAMEORIGIN immer senden (Legacy-Fallback für Browser ohne CSP-Support); zuvor nur CSP frame-ancestors gesendet wenn IServ-Domain gesetzt
 * Changelog 4.20.0:
 * - [FEATURE] IServ-Kiosk-Template page-terminplan-kiosk.php: token-gesicherte Kiosk-Seite, CSP frame-ancestors für IServ-Einbettung, kein Theme-Copy nötig
 * - [FEATURE] theme_page_templates-Filter: Vorlage „Terminplan Kiosk" automatisch im WP-Seiten-Editor
 * Changelog 4.19.4:
 * - [FIX]     Sticky-Thead überlappte erste Zeile (SW 00) trotz 4.19.2/4.19.3 weiterhin — eigentliche Ursache: overflow-x:auto auf .gtp-tbl-scroll machte den Wrapper zum vertikalen Scroll-Kontext, an dem position:sticky klebte (statt am Viewport). overflow:visible; .gt ist table-layout:fixed/100% und braucht keinen H-Scroll, <768px display:none
 * Changelog 4.19.3:
 * - [FIX]     Kiosk/Tablet: Sticky-Thead fiel auf Tablets (768–1024 px) aus — min-width:46rem auf .gt + overflow-x:auto brach position:sticky in Chrome/Safari. min-width entfernt.
 * - [FIX]     Sticky-Offsets dynamisch per JS berechnet (--gtp-thead-top, --gtp-scroll-margin); gtpStickyH() berücksichtigt jetzt Admin-Bar
 * Changelog 4.19.2:
 * - [FIX]     Sticky-Header überlagerte erste Terminzeile (SW 00) — gtpScrollToday/gtpTab/gtpSearchJump verwenden jetzt gtpStickyH() statt Viewport/3; scroll-margin-top auf tbody tr gesetzt
 * Changelog 4.19.1:
 * - [FIX]     SW-Nummerierung 1-basiert → 0-basiert (SW 00 = erste Schulwoche, entspricht Planner-Logik); gsh_tp_schulwoche() entfernt +1
 * - [FIX]     SW 00 zeigte "–" statt "00" (Bedingung $sw > 0 → $sw >= 0); Anmerkungen-Index $sw-1 → $sw
 * - [FIX]     Body-Klasse "admin-bar" fehlte in page-terminplan-entwurf.php → sticky Tabs/Thead hatten falschen top-Offset bei eingeloggten Admins
 * Changelog 4.19.0:
 * - [FIX]      Schulferien und Feiertage werden jetzt im ICS-Feed enthalten: gsh_tp_curriculr_build_ics() erzeugt VEVENT-Einträge aus schoolyear.holidays → graue Ferien-Zeilen und Anmerkungen-Spalte in WP-Anzeige
 * - [SECURITY] Backup-Verzeichnis (curriculr-backups) wird bei erstem Backup-Lauf mit .htaccess gesperrt (Deny from all) — predictable URLs waren öffentlich erreichbar
 * Changelog 4.18.0:
 * - [FIX]     Anmerkungen-Spalte in Entwurf-Vorschau: gsh_tp_table() und gsh_tp_mobile() zeigen jetzt Planner-Anmerkungen (annotations) aus dem gespeicherten Curriculr-Doc
 * - [UX]      Spaltenbezeichnung "Hinweise" → "Anmerkungen" in Tabelle und Agenda (Konsistenz mit Planner)
 * - [SECURITY] App-Token enthält nur erlaubte IServ-Gruppen (CURRICULR_ALLOWED_GROUPS), nicht alle Gruppen des Nutzers
 * Changelog 4.17.0:
 * - [FEATURE] REST: GET /docs Plan-Liste für Planner-Startseite
 * Changelog 4.16.0:
 * - [UX] iPad/Tablet zeigt die volle Tabelle (horizontal scrollbar) statt der Mobil-Kartenansicht
 * - [UX] PDF-Export auf Handy/iPad öffnet eine eigene Druckseite (zoombar, native „Als PDF") statt unleserlichem iframe-Druck
 * Changelog 4.15.0:
 * - [UX] Admin-Einstellungen neu organisiert — Schuljahr-Profil per Dropdown statt Tab, funktionale Tabs (Schuljahr-Profil/Kategorien/Curriculr-Sync/Kiosk/System & Logs); POST-Handler in benannte Funktionen extrahiert
 *
 * Changelog 4.14.0:
 * - [DESIGN] Display-Frontend auf flaches Planner-Papier-Design vereinheitlicht — Glas-Optik entfernt, solide Karten mit dezentem Schatten, Quartal-Tabs mit Gelb-Akzent-Unterstreichung, eckigere Buttons/Badges; Design-Tokens als Spiegel der Planner-Tokens
 * - [INFRA]  ZIP-Build enthält jetzt assets/ — CSS deployt mit dem Plugin-ZIP
 *
 * Changelog 4.13.0:
 * - [FIX]   Entwurf-Vorschau zeigte keine Termine: after_put synchronisiert jetzt quartal_grenzen + schuljahr_start des gemappten Profils aus dem gepushten Planner-Doc (Snap-to-Friday wie schoolweeks.ts)
 *
 * Changelog 4.12.1:
 * - [FIX]   after_put für alle Stages aufgerufen — Entwurf-Vorschau zeigt aktuelle Daten nach jedem Push
 *
 * Changelog 4.12.0:
 * - [M5]    409-Konflikt-Response enthält authorName + savedAt aus Revisions-Tabelle
 *
 * Changelog 4.11.0:
 * - [SECURITY] M2 REST Guard: App-Token Bearer-Validierung auf allen geschützten curriculr/v1-Routen; 403 bei fehlendem/abgelaufenem/ungültigem Token
 * - [FEATURE] Revisions-Attribution: author_sub und author_name in wp_curriculr_doc_revisions (DB-Version 4)
 *
 * Changelog 4.10.0:
 * - [FEATURE] M1 IServ-SSO: Auth-Endpunkte (/auth/login, /auth/callback, /auth/token, /auth/logout), Confidential-Client code→token serverseitig, kurzlebiges HS256-App-Token, Einmal-Handoff (kein Token in URL), Gruppen-Whitelist (fail-closed)
 *
 * Changelog 4.9.0:
 * - [FEATURE] SP4 Hardening: Revisions-Snapshots (wp_curriculr_doc_revisions), REST GET list+single, Retention-Prune, Nacht-Backup via wp-cron
 *
 * Changelog 4.8.0:
 * - [FEATURE] Curriculr Profil-Zuordnung: Schuljahr-Schlüssel ↔ WP-Profil im System-Tab konfigurierbar
 *
 * Changelog 4.7.0:
 * - [FEATURE] Curriculr Planner-Sync: Einstellung „Erlaubte Planner-Adresse" (CORS-Origin) im System-Tab
 *
 * Changelog 4.6.0:
 * - [FEATURE] Curriculr: Publikations-Stufe (Entwurf/Genehmigt/Öffentlich); Feed-Reuse nur für explizit zugeordnetes Profil und nur öffentlich
 *
 * Changelog 4.5.0:
 * - [FEATURE] Curriculr Data Layer: REST-Speicherung des Planner-Dokuments + Token-ICS-Feed (Feed-Reuse)
 *
 * Changelog 4.4.0:
 * - [FEATURE] Mobile Jahresansicht: Heatmap-Streifen mit Inline-Expand
 * - [UX] Desktop/Tablet: optionaler Heatmap-Toggle-Button
 * - [UX] Kategorie-Filter filtert Heatmap-Kacheln (Opacity)
 *
 * Changelog 4.3.4:
 * - [FIX] Entwurf-Vorschau + IServ-Kiosk: je eigenes Formular mit direktem POST-Handler, kein options.php
 * - [UX] Kiosk & System Tab: zwei klar getrennte Sektionen mit eigenem Speichern-Button
 * - [FEATURE] theme_page_templates-Filter: Vorlage „Terminplan Entwurf-Vorschau" automatisch im WP-Seiten-Editor
 *
 * Changelog 4.3.0:
 * - [DESIGN] Header einzeilig: Logo + Suche + Aktionen in einer Zeile
 * - [DESIGN] Sticky Command Bar: Quartal-Tabs + Jahresansicht-Toggle in einer Leiste
 * - [UX] Filter-Bar: horizontales Scroll auf Desktop statt Zeilenumbruch; Toggle überall sichtbar
 * - [UX] Hilfe-Button mit Textlabel; PDF-Buttons mit Loading-Zustand
 * - [FIX] Tab-Design: externes CSS Konflikt (Navy-Pill → Underline-Tab)
 * - [FIX] border-left Antipattern: 8 Instanzen auf full-border umgestellt
 * - [FIX] Schriftgröße Event-Cards .73rem → .8rem (WCAG AA)
 * - [FIX] Kategorie-Fallback-Farbe → #94a3b8 (Slate-400)
 *
 * Changelog 4.2.0:
 * - [FEATURE] Jahresansicht: klassisches Monats-Tage-Raster (Sep–Jul) als neue Ansicht
 * - [UX] Toggle-Button zum Wechseln zwischen Quartals- und Jahresansicht
 * - [FEATURE] Kategorie-Filter und Ereignis-Popup funktionieren in Jahresansicht
 *
 * Changelog 4.1.0:
 * - [FEATURE] Entwurf-Kiosk: Token-gesicherte Vorschau-Seite für Entwurfs-Terminpläne (Schulleitungsteam)
 * - [SECURITY] gsh_tp_check_draft_kiosk_access(): timing-sicherer Vergleich + Rate-Limiting (10/h/IP)
 * - [UX] Admin: Entwurf-Vorschau-Sektion im Kiosk & System Tab mit Token-Generator und URL-Anzeige
 *
 * Changelog 4.0.0:
 * - [DESIGN] Curricu:lr Branding — Design-Token-System (design-tokens.css), IServ-Farbpalette (#00345C/#00467D), Glassmorphism-Cards
 * - [DESIGN] Plugin-Frontend: vollständige CSS-Neuschrift mit Token-Referenzen, Pill-Buttons, responsive Table→Card-Layout
 * - [DESIGN] Konverter: CSS-Neuschrift mit identischem Design-System, SVG-Curricu:lr-Logo im Header
 * - [INFRA]  design-tokens.css als separate Enqueue-Datei mit Cache-Busting via GSH_TP_VERSION
 *
 * Changelog 3.17.0:
 * - [UX] Header zweizeilig: Titel/Subtitle oben, Suche in voller Breite darunter
 * - [UX] Quartal-Tabs: kleiner Dot markiert das aktuelle Quartal
 * - [UX] Filter-Bar: Toggle-Button auf Mobile – Filter ein-/ausklappbar
 * - [UX] Footer: Aktions-Buttons links, Metadaten rechts (strukturiert)
 * - [UX] Entwurfs-Banner via CSS-Klasse statt Inline-Style
 * - [UX] Theme-Switcher: Emoji-Icons (☀️🌙💻⚙️) durch konsistente Lucide-SVGs ersetzt
 * - [UX] Feedback-Button: Emoji durch message-circle-SVG ersetzt
 * - [INFRA] Schuljahr-Switcher: Inline-Style durch CSS-Klassen (.gtp-sj-btn, .gtp-sj-btn-on) ersetzt
 * - [INFRA] 4 neue Icons registriert: sun, monitor, message-circle, settings
 *
 * Changelog 3.16.1:
 * - [FEATURE] Neue Standardkategorie „Inklusion" (IFÖ, AL-SuS, Förderpläne, AOSF, I-Helfer etc.)
 *
 * Changelog 3.16.0:
 * - [FEATURE] Aufgabe 1: Slug wird beim Umbenennen einer Kategorie automatisch synchronisiert;
 *   Slug-Vorschau (readonly) direkt unter dem Label-Feld im Admin sichtbar
 * - [FEATURE] Aufgabe 2: Stichwörter (Keywords) pro Kategorie editierbar im Admin;
 *   Datenmodell erweitert um 'keywords'-Feld; GSH_TP_DEFAULT_CATEGORIES enthält alle
 *   bisherigen Keyword-Listen; gsh_tp_assign_categories_to_event() liest Keywords aus DB
 * - [FEATURE] Aufgabe 3: Shepherd.js vollständig entfernt; neues schlichtes Hilfe-Overlay
 *   öffnet sich nur auf Klick (kein Auto-Start), kein externes CDN mehr nötig
 * - [INFRA] gsh_tp_enqueue_tour_assets() und gtpTourStart() entfernt
 *
 * Changelog 3.15.2:
 * - [BUGFIX] gsh_tp_color_derive(): Textfarbe wird jetzt gegen die helle Pastell-Hintergrundfarbe
 *   ($bg) berechnet statt gegen die Originalfarbe – verhindert weißen/unsichtbaren Text auf
 *   hellem Hintergrund (z.B. #27ae60 Dunkelgrün → Pastell ~#e5f7ed → Text jetzt #000000)
 * - [INFRA] gsh_tp_contrast_color() steht jetzt im Code vor gsh_tp_color_derive() (Abhängigkeit)
 *
 * Changelog 3.15.1:
 * - [BUGFIX] Kategorie-Badges und Filter-Buttons: Textfarbe wird jetzt per WCAG-Formel berechnet
 *   statt hardcoded #fff – bei hellen Kategoriefarben (Gelb, Hellgrün etc.) ist der Text nun schwarz
 * - [INFRA] gsh_tp_contrast_color(): neue Hilfsfunktion berechnet #000000 oder #ffffff anhand der
 *   relativen WCAG-2.1-Luminanz (Schwellenwert 0.179)
 * - [CSS] --cat-text / --btn-text CSS-Custom-Properties ersetzen hardcoded color-Werte in Badges
 *   und Filter-Buttons; Dark Mode: kein hardcoded #1a1a2e mehr als Textfarbe
 *
 * Changelog 3.15.0:
 * - [FEATURE] Kategorien-System komplett neu aufgebaut mit GSH_TP_DEFAULT_CATEGORIES-Konstante
 *   als unveränderlichem Fundament – Kategorien verschwinden nicht mehr nach dem Reload
 * - [FEATURE] gsh_tp_assign_categories_to_event(): Keyword-Matching auf Titel/Beschreibung/
 *   Ort/CATEGORIES-Feld; wird vor dem Rendern auf alle Events angewendet
 * - [FEATURE] Vereinfachtes Kategorie-Datenmodell: id, label, color, slug (statt 7 Felder)
 * - [FEATURE] Admin-UI: Kategorie-Editor mit einzelnem Farbwähler und Farb-Vorschau
 * - [FEATURE] AJAX-Save: POST-Parameter 'categories' (statt 'tags'), neuer Nonce-String
 * - [INFRA] gsh_tp_color_derive(): leitet bg/border/text automatisch aus einer Hauptfarbe ab
 * - [INFRA] gsh_tp_save_categories(): eigenständige Speicher-Funktion mit Validierung
 *
 * Changelog 3.14.1:
 * - [BUGFIX] AJAX-Kategorien-Speichern: update_option()-Rückgabewert wird jetzt geprüft – DB-Fehler
 *   werden als echter Fehler gemeldet statt fälschlicherweise als Erfolg angezeigt
 * - [BUGFIX] Nach dem Speichern werden auto-generierte Slugs neuer Kategorien ins DOM
 *   zurückgespielt – verhindert inkonsistente CSS-Klassen bei erneutem Speichern ohne Reload
 *
 * Changelog 3.14.0:
 * - [FEATURE] Kategorien-Editor: Speichern jetzt per AJAX (kein Seiten-Reload, sofortige Rückmeldung)
 *
 * Changelog 3.13.1:
 * - [BUGFIX] Kategorien-Speichern: wp_safe_redirect+exit im Page-Callback brach Response ab (Seite hing)
 *
 * Changelog 3.13.0:
 * - [BUGFIX] Stichwörter/Tags: POST-Redirect-GET nach Speichern verhindert Static-Cache-Problem
 * - [BUGFIX] Suchfeld-Placeholder zeigte "…" statt echtem Auslassungszeichen
 * - [UX] Feedback-Log als Tab in Plugin-Admin integriert (kein separater Menüpunkt mehr)
 *
 * Changelog 3.12.0:
 * - [FEATURE] Feedback per AJAX + wp_mail(): HTML-E-Mail mit Absendername, Typ, Rate-Limiting, Honeypot
 * - [FEATURE] DB-Fallback-Log: Feedback immer gespeichert auch bei E-Mail-Fehler (wp_options-basiert)
 * - [FEATURE] Admin-Seite "Terminplan Feedback": Log einsehen, SMTP-Diagnose-Hinweis
 * - [BUGFIX] Kategorien-Editor: Löschen-Button reagiert jetzt auch auf Klick ins SVG-Icon
 * - [BUGFIX] Suchfeld: Placeholder und Text im Dark Mode korrekt kontrastreich dargestellt
 * - [UX] Optionales Absender-Namensfeld im Feedback-Modal
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
 * - Minimal-Header: Flex-Layout – links "Schul-Logo | Deine Schule" (Logo-Mark +
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
 *   WordPress Admin → Einstellungen → Schul-Terminplan
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
 * Wie verwaltet man mehrere Schuljahre? (Admin → Einstellungen → Schul-Terminplan)
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

define( 'GSH_TP_VERSION',       '4.36.0' );
define( 'GSH_TP_CACHE_VERSION', 3 );       // Bei Datenstruktur-Änderungen erhöhen → alte Caches werden automatisch ignoriert
define( 'GSH_TP_SLUG',     'gsh-terminplan' );
define( 'GSH_TP_CACHE_KEY', 'gsh_tp_ical_data' );      // Option (nie ablaufend)
define( 'GSH_TP_BACKUP_KEY', 'gsh_tp_ical_backup' );   // Option (Notfall-Backup)
define( 'GSH_TP_FRESH_KEY', 'gsh_tp_ical_freshness' ); // Transient (Ablaufsteuerung)

// Curriculr Data Layer (REST-Speicherung des Planner-Dokuments + Token-ICS-Feed).
require_once plugin_dir_path( __FILE__ ) . 'curriculr-auth.php';
require_once plugin_dir_path( __FILE__ ) . 'curriculr-guard.php';
require_once plugin_dir_path( __FILE__ ) . 'curriculr-data-layer.php';

/**
 * Standard-Kategorien des Schul-Terminplans.
 *
 * Dienen als Fundament: werden beim ersten Aufruf von gsh_tp_get_categories()
 * automatisch in die Datenbank geschrieben, falls noch keine Kategorien gespeichert
 * sind. Eine Konstante stellt sicher, dass dieser Fallback unveränderlich ist.
 *
 * Felder je Eintrag: id (string), label (string), color (#rrggbb), slug (string).
 *
 * @since 3.15.0
 */
const GSH_TP_DEFAULT_CATEGORIES = [
    [
        'id'       => 'jg-5-6',
        'label'    => 'Jahrgang 5/6',
        'color'    => '#e74c3c',
        'slug'     => 'jg56',
        'keywords' => [
            'klasse 5', 'klasse 6', 'kl. 5', 'kl. 6',
            'kl.5', 'kl.6', 'jg 5', 'jg 6', 'jg. 5', 'jg. 6',
            'jg5', 'jg6', '5a', '5b', '5c', '5d', '5e',
            '6a', '6b', '6c', '6d', '6e',
            'einschulung', 'schulanfang', 'orientierungsphase',
            'kennenlerntag', 'schnuppertag', 'grundschule',
            'übergangsfeier', 'einführungswoche',
            'elternabend 5', 'elternabend 6',
            'wandertag 5', 'wandertag 6',
            'projekttag 5', 'projekttag 6',
            'klassenfahrt 5', 'klassenfahrt 6',
        ],
    ],
    [
        'id'       => 'jg-7-8',
        'label'    => 'Jahrgang 7/8',
        'color'    => '#27ae60',
        'slug'     => 'jg78',
        'keywords' => [
            'klasse 7', 'klasse 8', 'kl. 7', 'kl. 8',
            'kl.7', 'kl.8', 'jg 7', 'jg 8', 'jg. 7', 'jg. 8',
            'jg7', 'jg8', '7a', '7b', '7c', '7d', '7e',
            '8a', '8b', '8c', '8d', '8e',
            'berufsorientierung', 'berufsfelderkundung',
            'betriebsbesichtigung', 'praktikumsvorbereitung',
            'schülerbetriebspraktikum', 'bop',
            'elternabend 7', 'elternabend 8',
            'wandertag 7', 'wandertag 8',
            'projekttag 7', 'projekttag 8',
            'klassenfahrt 7', 'klassenfahrt 8',
            'sportfest 7', 'sportfest 8',
        ],
    ],
    [
        'id'       => 'jg-9-10',
        'label'    => 'Jahrgang 9/10',
        'color'    => '#e67e22',
        'slug'     => 'jg910',
        'keywords' => [
            'klasse 9', 'klasse 10', 'kl. 9', 'kl. 10',
            'kl.9', 'kl.10', 'jg 9', 'jg 10', 'jg. 9', 'jg. 10',
            'jg9', 'jg10', '9a', '9b', '9c', '9d', '9e',
            '10a', '10b', '10c', '10d', '10e',
            'schülerpraktikum', 'betriebspraktikum', 'praktikum',
            'berufsberatung', 'berufswegeplanung',
            'mittlerer schulabschluss', 'msa', 'fachoberschulreife',
            'abschlussfeier', 'abschlussfahrt',
            'prüfung klasse 10', 'abschlussprüfung',
            'elternabend 9', 'elternabend 10',
            'wandertag 9', 'wandertag 10',
            'projekttag 9', 'projekttag 10',
            'klassenfahrt 9', 'klassenfahrt 10',
        ],
    ],
    [
        'id'       => 'oberstufe',
        'label'    => 'Oberstufe',
        'color'    => '#2980b9',
        'slug'     => 'oberstufe',
        'keywords' => [
            'ef', 'einführungsphase',
            'q1', 'q2', 'qualifikationsphase',
            'oberstufe', 'sek ii', 'sek. ii', 'sekundarstufe ii',
            'abitur', 'abi', 'abiturzeugnis', 'abiturvorbereitung',
            'abiturprüfung', 'schriftliches abitur', 'mündliches abitur',
            'zentralabitur', 'abi-feier', 'abiball', 'abschlussball',
            'abistreich', 'abigottesdienst',
            'lk', 'leistungskurs', 'grundkurs', 'gk',
            'projektkurs', 'vertiefungskurs',
            'kurswahl', 'fachwahl', 'belegung',
            'studienberatung', 'hochschultag', 'unibesuch',
            'studienfahrt', 'bildungsfahrt',
            'elternabend oberstufe', 'elternabend ef',
            'elternabend q1', 'elternabend q2',
        ],
    ],
    [
        'id'       => 'inklusion',
        'label'    => 'Inklusion',
        'color'    => '#1abc9c',
        'slug'     => 'inklusion',
        'keywords' => [
            'ifö', 'ifo', 'ifö-sus', 'ifo-sus', 'al sus', 'al-sus',
            'al 1', 'al 2', 'al 3', 'al1', 'al2', 'al3',
            'internationale förderschüler', 'übergabe beratungsteam',
            'inklusion', 'inklusiv', 'inkl.',
            'sonderpädagogik', 'sonderpädagogisch', 'förderbedarf',
            'aosf', 'i-helfer', 'integrationshelfer',
            'beratungsteam', 'förderplan', 'förderpläne',
            'förderlehrer', 'fö-lul', 'fö lul', 'fö-lehrer',
            'nachteilsausgleich', 'gemeinsames lernen',
        ],
    ],
    [
        'id'       => 'feiertage',
        'label'    => 'Feiertage',
        'color'    => '#f39c12',
        'slug'     => 'feiertage',
        'keywords' => [
            'feiertag', 'gesetzlicher feiertag',
            'neujahr', 'heilige drei könige',
            'karfreitag', 'karsamstag', 'ostermontag', 'ostersonntag',
            'christi himmelfahrt', 'vatertag', 'pfingstmontag',
            'fronleichnam', 'tag der deutschen einheit',
            'allerheiligen', 'nikolaus',
            'heiligabend', 'weihnachten', '1. weihnachtstag', '2. weihnachtstag',
            'silvester',
            'ferien', 'schulferien', 'ferientag',
            'herbstferien', 'weihnachtsferien', 'winterferien',
            'osterferien', 'pfingstferien', 'sommerferien',
            'brückentag', 'beweglicher ferientag',
            'schulfrei', 'unterrichtsfrei',
            'studientag', 'frei',
        ],
    ],
    [
        'id'       => 'konferenzen',
        'label'    => 'Konferenzen',
        'color'    => '#8e44ad',
        'slug'     => 'konferenzen',
        'keywords' => [
            'konferenz', 'konf.',
            'lehrerkonferenz', 'gesamtkonferenz', 'geko',
            'fachkonferenz', 'klassenkonferenz', 'teilkonferenz',
            'schulkonferenz', 'zeugniskonferenz', 'versetzungskonferenz',
            'notenkonferenz', 'erziehungskonferenz',
            'jahrgangsteam', 'jahrgangsbesprechung',
            'dienstbesprechung', 'teambesprechung', 'teamsitzung',
            'abteilungsbesprechung', 'steuergruppe',
            'schulleitungssitzung', 'fortbildung',
            'pädagogischer tag', 'pädagogische konferenz',
            'elternpflegschaft', 'schulpflegschaft',
            'schülervertretung', 'sv-sitzung', 'sv sitzung',
            'schulvorstand', 'schilf', 'lk', 'fako', 'fachkonferenz',
            'db', 'pk', 'zk', 'sk', 'pflegschaft',
        ],
    ],
];

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
        'calendar'       => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'tag'            => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>',
        'moon'           => '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>',
        'info'           => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
        'help-circle'    => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'sun'            => '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>',
        'monitor'        => '<rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>',
        'message-circle' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'settings'       => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'plus'           => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
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
            'version' => '4.36.0',
            'entries' => array(
                array( 'tag' => 'NEU', 'text' => 'IServ-Kalender direkt verbinden: Im Schuljahr-Bereich lässt sich wieder die Adresse eines freigegebenen IServ-Kalenders eintragen — das Plugin holt die Termine dann selbst ab und baut daraus Quartalsansicht und Druckansicht, ganz ohne Planer. Seit 4.24.0 war das Feld aus der Oberfläche verschwunden und die angezeigte Feed-URL nur noch die vom Plugin selbst erzeugte Abo-Adresse' ),
                array( 'tag' => 'FIX', 'text' => 'Verbundene IServ-Kalender werden beim Senden aus dem Planer nicht mehr überschrieben (neues Feld source=planner|extern pro Kalender; after_put überspringt externe Quellen)' ),
            ),
        ),
        array(
            'version' => '4.35.1',
            'entries' => array(
                array( 'tag' => 'FIX', 'text' => 'Anmerkungen-Spalte blieb in allen WP-Ansichten (Kiosk, Entwurf-Vorschau, öffentliche Seite) leer, sobald ein Schuljahr über das nested schoolyears-Model bzw. SPA-Auto-Provisioning angelegt wurde — Annotation-Lookup nutzt jetzt sj_key direkt aus dem Profil statt der nie befüllten Legacy-Option gsh_tp_curriculr_profile_map' ),
            ),
        ),
        array(
            'version' => '4.35.0',
            'entries' => array(
                array( 'tag' => 'NEU', 'text' => 'Kalender-Fußzeile: Kolleg:innen können den ICS-Kalender-Feed über die öffentliche Seite und die Kiosk-Ansichten abonnieren — webcal://-Link plus gleich gestalteter Copy-URL-Button (webcal lädt ohne registrierten OS-Handler nur eine Datei statt zu abonnieren) als eigene Toolbar-Zeile mit Trennlinie statt Box, plus ausklappbarer Kurzanleitung (SVG-Chevron) für Outlook/Google Kalender/Apple Kalender/Thunderbird (nur bei Curriculr-verwalteten Kalendern)' ),
            ),
        ),
        array(
            'version' => '4.34.2',
            'entries' => array(
                array( 'tag' => 'UX', 'text' => 'Filter-Kategorien: Labels umbrachen bei zwei Wörtern (z. B. „Jahrgang 5/6") auf zwei Zeilen und machten die Pillen ungleich hoch — jetzt einzeilig. Horizontaler Scroll ohne sichtbare Scrollbar ersetzt durch Zeilenumbruch + funktionierendes Einklappen (generalisiert die 4.34.1-Kiosk-Lösung auf die Hauptseite)' ),
            ),
        ),
        array(
            'version' => '4.34.1',
            'entries' => array(
                array( 'tag' => 'FIX', 'text' => 'Kiosk: Filter-Leiste ließ sich oberhalb 768px Breite nicht einklappen (Collapse-CSS war an die Mobile-Breakpoint gekoppelt) — Toggle wirkt jetzt in jeder Kiosk-Kachelbreite' ),
            ),
        ),
        array(
            'version' => '4.34.0',
            'entries' => array(
                array( 'tag' => 'FIX', 'text' => 'Kategorien-Sync: Planner ist jetzt alleinige Quelle — Kategorien ohne Planner-Gegenstück werden beim Push entfernt statt dauerhaft mitgeführt (WP-Stichwörter bei getroffenen Kategorien bleiben erhalten)' ),
            ),
        ),
        array(
            'version' => '4.33.0',
            'entries' => array(
                array( 'tag' => 'NEU', 'text' => 'Gruppen-Filter im Terminplan: Kolleg:innen können auf der öffentlichen Seite und in den Kiosk-Ansichten nach Planner-Gruppen (z. B. Eltern, Kollegium) filtern' ),
                array( 'tag' => 'NEU', 'text' => 'Kategorien-Sync: Labels und Farben aus dem Planner überschreiben beim Senden automatisch die Plugin-Kategorien — Stichwörter fürs IServ-Matching bleiben erhalten' ),
                array( 'tag' => 'NEU', 'text' => 'Neue Schuljahre aus dem Planner werden automatisch mit allen Gruppen-Kalendern angelegt (max 7)' ),
                array( 'tag' => 'UX', 'text' => 'Schuljahr-Karte warnt, wenn Planner-Gruppen ohne eigenen Kalender sind' ),
            ),
        ),
        array(
            'version' => '4.32.0',
            'entries' => array(
                array( 'tag' => 'UX', 'text' => 'Schuljahr-Karte zeigt jetzt zusätzlich, wer zuletzt gespeichert hat (Autor + Version) — bisher nur Zeitstempel ohne Person' ),
            ),
        ),
        array(
            'version' => '4.31.0',
            'entries' => array(
                array( 'tag' => 'FIX', 'text' => 'Vom Planner gesendete neue Schuljahre waren in WordPress unsichtbar (stilles No-op ohne Zuordnung). Jetzt automatische, inaktive Anlage inkl. Haupt-Kalender und ICS-Cache — im Admin sichtbar und zuordenbar, Live-Anzeige bleibt unberührt' ),
            ),
        ),
        array(
            'version' => '4.30.0',
            'entries' => array(
                array( 'tag' => 'NEU', 'text' => 'Neue Einstellung "Schulbezeichnung (PDF-Kopfzeile)": eigener Schulname statt fest verdrahtetem "Gesamtschule Horst" auf dem Termin-PDF frei einstellbar' ),
            ),
        ),
        array(
            'version' => '4.29.0',
            'entries' => array(
                array( 'tag' => 'SECURITY', 'text' => 'Google-Fonts-Import entfernt — Inter wird self-hosted aus assets/fonts/ geladen (DSGVO: keine IP-Übertragung an Google mehr)' ),
                array( 'tag' => 'SECURITY', 'text' => 'App-Token-Verify prüft jetzt iss/aud-Claims; Guard antwortet 401 statt 403 bei fehlendem/ungültigem Token' ),
                array( 'tag' => 'SECURITY', 'text' => 'REST-PUT/Doc-Upload: Tiefenvalidierung je Event — kaputte Events können den öffentlichen ICS-Feed nicht mehr mit 500 lahmlegen' ),
                array( 'tag' => 'SECURITY', 'text' => 'Speichern ist jetzt atomar: gleichzeitiges Speichern zweier Nutzer erzeugt zuverlässig einen Konflikt-Hinweis statt stillem Überschreiben' ),
                array( 'tag' => 'SECURITY', 'text' => 'Kiosk-/Entwurf-Seiten senden Referrer-Policy: no-referrer; tägliche Backups werden nach 30 Tagen gelöscht (DSGVO)' ),
                array( 'tag' => 'DESIGN', 'text' => 'Design-Angleichung an den Curricu:lr Planner: Marine-Farbtöne, Inter-Schrift und Radien der Terminplan-Ansicht folgen jetzt den zentralen Design-Tokens — inklusive Dark Mode' ),
                array( 'tag' => 'UX', 'text' => 'Fehler- und Hinweisboxen nutzen einheitliche, Theme-fähige Gestaltung statt fest verdrahteter Farben' ),
                array( 'tag' => 'INFRA', 'text' => 'Feedback-Log löscht Einträge älter als 90 Tage automatisch; Schuljahr-Liste ohne N+1-Datenbankabfragen' ),
            ),
        ),
        array(
            'version' => '4.28.2',
            'entries' => array(
                array( 'tag' => 'FIX', 'text' => 'Kiosk (beide Modi): Anfang/Ende-Uhrzeiten wurden nirgends angezeigt — gsh_tp_parse_event() behält von DATE-TIME-Werten nur das Datum, die Uhrzeit ging verloren. Jetzt zusätzlich aus DTSTART/DTEND extrahiert und als Zeit-Label über dem Termin-Titel angezeigt' ),
            ),
        ),
        array(
            'version' => '4.28.1',
            'entries' => array(
                array( 'tag' => 'SECURITY', 'text' => 'Entwurf-Kiosk-Template: CSP frame-ancestors + X-Frame-Options SAMEORIGIN tatsächlich ergänzt — der 4.27.2-Eintrag dafür war nie committed worden, Server-/Host-Default blockierte weiterhin das IServ-iframe-Embedding' ),
            ),
        ),
        array(
            'version' => '4.28.0',
            'entries' => array(
                array( 'tag' => 'NEU', 'text' => 'Schuljahr-Karte: manueller Planungsdokument-Upload (JSON) als Alternative zu IServ-SSO — inkl. "Sichern ↓"-Download des aktuellen Stands vor dem Überschreiben' ),
            ),
        ),
        array(
            'version' => '4.27.1',
            'entries' => array(
                array( 'tag' => 'FIX', 'text' => 'Schuljahre-Tab (v2): Kalender-Status (Entwurf/Beschlossen) war seit 4.24.0 nur Text-Anzeige — Umschalten fehlte, Entwurf-Kiosk dadurch für neue/synchronisierte Schuljahre unerreichbar' ),
            ),
        ),
        array(
            'version' => '4.27.0',
            'entries' => array(
                array( 'tag' => 'NEU', 'text' => 'Kategorien-Tab: Kategorien aus einem Planner-Schuljahr übernehmen (Label/Farbe je Kategorie werden übernommen, WP-seitige Stichwörter für das IServ-Keyword-Matching bleiben unverändert)' ),
            ),
        ),
        array(
            'version' => '4.26.0',
            'entries' => array(
                array( 'tag' => 'UX',  'text' => 'Tab „Schuljahr-Profile" umbenannt in „Schuljahre"' ),
                array( 'tag' => 'UX',  'text' => 'Kiosk-Tab in System-Tab integriert; ?tab=_kiosk leitet auf ?tab=_system weiter' ),
                array( 'tag' => 'UX',  'text' => 'Schaltfläche „Als aktiv setzen" → „Als aktives Schuljahr setzen" mit Erklärungstext' ),
                array( 'tag' => 'UX',  'text' => 'Schuljahr-ID und Schlüssel-Feld hinter „Erweitert" versteckt' ),
                array( 'tag' => 'NEU', 'text' => 'Status-Anzeige in Schuljahr-Karte: Veröffentlichungs-Stufe (Entwurf/Intern/Öffentlich) und letzter Sync-Zeitstempel' ),
            ),
        ),
        array(
            'version'  => '4.25.0',
            'entries'  => array(
                array( 'tag' => 'NEU', 'text' => 'Schuljahr löschen: nicht-aktive Schuljahre inkl. DB-Daten und ICS-Cache entfernbar' ),
                array( 'tag' => 'UX',  'text' => 'Danger-Zone als <details>-Element — zweistufige Bestätigung ohne nativen confirm()-Dialog' ),
            ),
        ),
        array(
            'version'  => '4.24.0',
            'entries'  => array(
                array( 'tag' => 'UX', 'text' => 'Schuljahr-Profile-Tab: schoolyear-zentriertes Layout, freie Label/Key-Eingabe beim Anlegen' ),
                array( 'tag' => 'UX', 'text' => 'Curriculr-Sync-Tab entfernt — Origin-Einstellung jetzt im System-Tab' ),
            ),
        ),
        array(
            'version'  => '4.23.0',
            'entries'  => array(
                array( 'tag' => 'NEU', 'text' => 'Update-Hinweis im WP-Admin nach Plugin-Update (dismissibel, per-Version)' ),
            ),
        ),
        array(
            'version'  => '4.22.0',
            'entries'  => array(
                array( 'tag' => 'NEU', 'text' => 'Mehrfach-Kalender: ein Schuljahr kann mehrere Gruppen-ICS-Feeds bedienen (n:m Profil-Mapping)' ),
                array( 'tag' => 'NEU', 'text' => 'REST POST /curriculr/v1/profile-map — SPA kann Gruppen→Profil-Mapping direkt speichern' ),
                array( 'tag' => 'NEU', 'text' => 'Lazy Migration: altes Einzel-Profil-Format wird automatisch zum neuen Array-Format migriert' ),
            ),
        ),
        array(
            'version'  => '4.21.0',
            'entries'  => array(
                array( 'tag' => 'NEU', 'text' => 'Datensicherung-Seite: Einstellungen als JSON exportieren und importieren' ),
                array( 'tag' => 'NEU', 'text' => 'Warnhinweis beim Plugin-Löschen mit Link zur Datensicherung' ),
                array( 'tag' => 'FIX', 'text' => 'Uninstall-Hook löscht jetzt alle curriculr_origin / curriculr_profile_map / curriculr_db_version Optionen' ),
                array( 'tag' => 'FIX', 'text' => 'Uninstall-Hook bereinigt jetzt auch den gsh_tp_curriculr_daily_backup Cron-Job' ),
            ),
        ),
        array(
            'version'  => '4.20.1',
            'entries'  => array(
                array( 'tag' => 'SECURITY', 'text' => 'Kiosk-Template: X-Frame-Options: SAMEORIGIN immer senden — Legacy-Fallback für Browser ohne CSP frame-ancestors Support (war zuvor nur gesetzt wenn keine IServ-Domain konfiguriert)' ),
            ),
        ),
        array(
            'version'  => '4.20.0',
            'entries'  => array(
                array( 'tag' => 'FEATURE', 'text' => 'IServ-Kiosk-Template page-terminplan-kiosk.php: token-gesicherte Kiosk-Seite ohne Theme-Copy, CSP frame-ancestors für IServ-iframe-Einbettung (gsh_tp_iserv_domain)' ),
                array( 'tag' => 'FEATURE', 'text' => 'theme_page_templates-Filter: Vorlage „Terminplan Kiosk" automatisch im WP-Seiten-Editor ohne Theme-Datei' ),
            ),
        ),
        array(
            'version'  => '4.19.4',
            'entries'  => array(
                array( 'tag' => 'BUGFIX', 'text' => 'Sticky-Tabellenkopf überlappte erste Zeile (SW 00) trotz 4.19.2/4.19.3 weiterhin. Eigentliche Ursache: overflow-x:auto auf .gtp-tbl-scroll machte diesen Wrapper zum Scroll-Kontext, an dem .gt thead (position:sticky) vertikal klebte — statt am Viewport. Dadurch wanderte der Kopf mit und verdeckte die Chips der ersten Schulwoche. Fix: overflow:visible. Die Tabelle ist table-layout:fixed/width:100% (kein horizontaler Scroll nötig) und unter 768px ohnehin display:none. Die JS-Offsets aus 4.19.2/4.19.3 waren korrekt, konnten aber nicht greifen, solange sticky am falschen Container hing.' ),
            ),
        ),
        array(
            'version'  => '4.19.3',
            'entries'  => array(
                array( 'tag' => 'BUGFIX', 'text' => 'Kiosk/Tablet: Sticky-Thead fiel auf Tablets (768–1024 px) aus — min-width:46rem auf .gt erzeugte horizontalen Überlauf im overflow-x:auto-Container, was position:sticky in Chrome/Safari bricht. min-width entfernt.' ),
                array( 'tag' => 'BUGFIX', 'text' => 'Sticky-Offsets (--gtp-thead-top, --gtp-scroll-margin) werden jetzt per JS aus tatsächlichen Elementhöhen berechnet statt hardcodierter 44px-Annahme. gtpStickyH() berücksichtigt jetzt auch die Admin-Bar.' ),
            ),
        ),
        array(
            'version'  => '4.19.2',
            'entries'  => array(
                array( 'tag' => 'BUGFIX', 'text' => 'Sticky-Header überlagerte erste Terminzeile (SW 00) beim Scrollen — Scroll-Funktionen berechnen jetzt korrekte Sticky-Höhe; scroll-margin-top auf tbody tr' ),
            ),
        ),
        array(
            'version'  => '4.19.1',
            'entries'  => array(
                array( 'tag' => 'BUGFIX', 'text' => 'SW-Nummerierung 1-basiert → 0-basiert (SW 00 = erste Schulwoche, entspricht Planner-Logik)' ),
                array( 'tag' => 'BUGFIX', 'text' => 'SW 00 zeigte "–" statt "00" — Bedingung $sw > 0 → $sw >= 0, Anmerkungen-Index $sw-1 → $sw' ),
                array( 'tag' => 'BUGFIX', 'text' => 'Body-Klasse admin-bar fehlte in Entwurf-Vorschau — sticky Tabs/Thead hatten falschen top-Offset bei eingeloggten Admins' ),
            ),
        ),
        array(
            'version'  => '4.17.0',
            'entries'  => array(
                array( 'tag' => 'FEATURE', 'text' => 'REST: GET /docs Plan-Liste für Planner-Startseite' ),
            ),
        ),
        array(
            'version'  => '4.16.0',
            'entries'  => array(
                array( 'tag' => 'UX', 'text' => 'iPad/Tablet zeigt die volle Tabelle (horizontal scrollbar) statt der Mobil-Kartenansicht' ),
                array( 'tag' => 'UX', 'text' => 'PDF-Export auf Handy/iPad öffnet eine eigene Druckseite (zoombar, native „Als PDF sichern") statt unleserlichem iframe-Druck' ),
            ),
        ),
        array(
            'version'  => '4.15.0',
            'entries'  => array(
                array( 'tag' => 'UX', 'text' => 'Admin-Einstellungen neu organisiert — Schuljahr-Profil per Dropdown statt Tab, funktionale Tabs sauber getrennt; POST-Handler extrahiert' ),
            ),
        ),
        array(
            'version'  => '4.14.0',
            'entries'  => array(
                array( 'tag' => 'DESIGN', 'text' => 'Display-Frontend auf flaches Planner-Papier-Design vereinheitlicht — Glas-Optik entfernt, solide Karten, Quartal-Tabs mit Gelb-Akzent-Unterstreichung, eckigere Buttons/Badges' ),
                array( 'tag' => 'INFRA',  'text' => 'ZIP-Build enthält jetzt assets/ — CSS deployt mit dem Plugin-ZIP' ),
            ),
        ),
        array(
            'version'  => '4.13.0',
            'entries'  => array(
                array( 'tag' => 'FIX', 'text' => 'Entwurf-Vorschau zeigte keine Termine — Quartalsgrenzen + Schuljahr-Start des gemappten Profils werden jetzt bei jedem Push aus dem Planner-Dokument übernommen' ),
            ),
        ),
        array(
            'version'  => '4.12.1',
            'entries'  => array(
                array( 'tag' => 'FIX', 'text' => 'after_put für alle Stages aufgerufen — Entwurf-Vorschau zeigt jetzt aktuelle Daten nach jedem Push' ),
            ),
        ),
        array(
            'version'  => '4.9.0',
            'entries'  => array(
                array( 'tag' => 'FEATURE', 'text' => 'SP4 Hardening: Revisions-Snapshots (wp_curriculr_doc_revisions), REST GET list+single, Retention-Prune, Nacht-Backup via wp-cron' ),
            ),
        ),
        array(
            'version'  => '4.8.0',
            'entries'  => array(
                array( 'tag' => 'FEATURE', 'text' => 'Curriculr Profil-Zuordnung: Schuljahr-Schlüssel ↔ WP-Profil im System-Tab konfigurierbar' ),
            ),
        ),
        array(
            'version'  => '4.7.0',
            'entries'  => array(
                array( 'tag' => 'FEATURE', 'text' => 'Curriculr Planner-Sync: Einstellung „Erlaubte Planner-Adresse" (CORS-Origin) im System-Tab' ),
            ),
        ),
        array(
            'version'  => '4.6.0',
            'entries'  => array(
                array( 'tag' => 'FEATURE', 'text' => 'Curriculr: Publikations-Stufe (Entwurf/Genehmigt/Öffentlich); Feed-Reuse nur für explizit zugeordnetes Profil und nur öffentlich' ),
            ),
        ),
        array(
            'version'  => '4.5.0',
            'entries'  => array(
                array( 'tag' => 'FEATURE', 'text' => 'Curriculr Data Layer: REST-Speicherung des Planner-Dokuments + Token-ICS-Feed (Feed-Reuse), Renderer unverändert' ),
            ),
        ),
        array(
            'version'  => '4.4.0',
            'entries'  => array(
                array( 'tag' => 'FEATURE', 'text' => 'Mobile Jahresansicht: Heatmap-Streifen mit Inline-Expand für gestapelte Tage' ),
                array( 'tag' => 'FEATURE', 'text' => 'Desktop/Tablet: optionaler Heatmap-Toggle-Button im Command Bar' ),
                array( 'tag' => 'UX',      'text' => 'Kategorie-Filter filtert Heatmap-Kacheln via Opacity-Effekt' ),
            ),
        ),
        array(
            'version'  => '4.3.4',
            'entries'  => array(
                array( 'tag' => 'FIX',     'text' => 'Entwurf-Vorschau und IServ-Kiosk: je eigenes Formular mit direktem POST-Handler — kein options.php mehr' ),
                array( 'tag' => 'UX',      'text' => 'Kiosk & System Tab: zwei klar getrennte Sektionen mit eigenem Speichern-Button' ),
                array( 'tag' => 'FEATURE', 'text' => 'theme_page_templates-Filter: Vorlage „Terminplan Entwurf-Vorschau" automatisch im WP-Seiten-Editor' ),
            ),
        ),
        array(
            'version'  => '4.3.0',
            'entries'  => array(
                array( 'tag' => 'DESIGN', 'text' => 'Header einzeilig: Logo + Suche + Aktionen in einer Zeile statt zwei' ),
                array( 'tag' => 'DESIGN', 'text' => 'Sticky Command Bar: Quartal-Tabs und Jahresansicht-Toggle in einer gemeinsamen Leiste' ),
                array( 'tag' => 'UX',     'text' => 'Jahresansicht-Toggle in der Sticky Bar — immer sichtbar, kein versteckter Button mehr' ),
                array( 'tag' => 'UX',     'text' => 'Filter-Bar: horizontales Scroll auf Desktop statt Zeilenumbruch; Toggle überall sichtbar' ),
                array( 'tag' => 'UX',     'text' => 'Hilfe-Button mit Textlabel "Hilfe" statt Icon-only' ),
                array( 'tag' => 'UX',     'text' => 'PDF-Buttons zeigen Loading-Zustand während Druckdialog vorbereitet wird' ),
                array( 'tag' => 'FIX',    'text' => 'Tab-Design: externes CSS Konflikt behoben — aktive Tabs zeigen Unterbalken statt dunklem Navy-Pill' ),
                array( 'tag' => 'FIX',    'text' => 'border-left Antipattern: 8 Instanzen (Event-Cards, Admin-Notices, Filter-Notices) auf full-border umgestellt' ),
                array( 'tag' => 'FIX',    'text' => 'Schriftgröße Event-Cards: .73rem → .8rem (WCAG 2.1 AA näher)' ),
                array( 'tag' => 'FIX',    'text' => 'Kategorie-Fallback-Farbe #6c757d → #94a3b8 (Slate-400, passt zum Design-System)' ),
            ),
        ),
        array(
            'version'  => '4.2.0',
            'entries'  => array(
                array( 'tag' => 'FEATURE',  'text' => 'Jahresansicht: klassisches Monats-Tage-Raster (Sep–Jul) als neue Ansicht' ),
                array( 'tag' => 'UX',       'text' => 'Toggle-Button zum Wechseln zwischen Quartals- und Jahresansicht' ),
                array( 'tag' => 'FEATURE',  'text' => 'Kategorie-Filter und Ereignis-Popup funktionieren in Jahresansicht' ),
            ),
        ),
        array(
            'version'  => '4.1.0',
            'entries'  => array(
                array( 'tag' => 'FEATURE',  'text' => 'Entwurf-Kiosk: Token-gesicherte Vorschau-Seite für Entwurfs-Terminpläne (Schulleitungsteam ohne WP-Login)' ),
                array( 'tag' => 'SECURITY', 'text' => 'gsh_tp_check_draft_kiosk_access(): Timing-sicherer Token-Vergleich (hash_equals) + IP-Rate-Limiting (10/h)' ),
                array( 'tag' => 'UX',       'text' => 'Admin Kiosk & System Tab: Entwurf-Vorschau-Sektion mit Token-Generator und automatischer URL-Anzeige' ),
            ),
        ),
        array(
            'version' => '4.0.0',
            'entries' => array(
                array( 'tag' => 'DESIGN', 'text' => 'Curricu:lr Branding: Design-Token-CSS-Datei (design-tokens.css), IServ-inspirierte Farbpalette (#00345C/#00467D), Glassmorphism-Cards' ),
                array( 'tag' => 'DESIGN', 'text' => 'Plugin-Frontend: vollständige CSS-Neuschrift mit Token-Referenzen, Pill-Buttons, responsive Table→Card-Layout' ),
                array( 'tag' => 'DESIGN', 'text' => 'Konverter: CSS-Neuschrift mit identischem Design-System, SVG-Logo im Header' ),
                array( 'tag' => 'INFRA',  'text' => 'design-tokens.css als separate Enqueue-Datei mit Cache-Busting via GSH_TP_VERSION' ),
            ),
        ),
        array(
            'version' => '3.17.0',
            'entries' => array(
                array( 'tag' => 'UX',     'text' => 'Header zweizeilig: Titel/Subtitle oben, Suche in voller Breite darunter' ),
                array( 'tag' => 'UX',     'text' => 'Quartal-Tabs: kleiner Dot markiert das aktuelle Quartal' ),
                array( 'tag' => 'UX',     'text' => 'Filter-Bar: Toggle-Button auf Mobile – Filter ein-/ausklappbar' ),
                array( 'tag' => 'UX',     'text' => 'Footer: Aktions-Buttons links, Metadaten rechts (strukturiert)' ),
                array( 'tag' => 'UX',     'text' => 'Theme-Switcher und Feedback-Button: Emoji-Icons durch konsistente Lucide-SVGs ersetzt' ),
                array( 'tag' => 'INFRA',  'text' => '4 neue Icons registriert: sun, monitor, message-circle, settings' ),
            ),
        ),
        array(
            'version' => '3.16.1',
            'entries' => array(
                array( 'tag' => 'FEATURE', 'text' => 'Neue Standardkategorie „Inklusion" (IFÖ, AL-SuS, Förderpläne, AOSF, I-Helfer, Beratungsteam etc.) in GSH_TP_DEFAULT_CATEGORIES' ),
            ),
        ),
        array(
            'version' => '3.16.0',
            'entries' => array(
                array( 'tag' => 'FEATURE', 'text' => 'Aufgabe 1: Slug synchronisiert sich beim Umbenennen automatisch; Slug-Vorschau direkt unter dem Label-Feld im Admin' ),
                array( 'tag' => 'FEATURE', 'text' => 'Aufgabe 2: Keywords pro Kategorie im Admin editierbar; Datenmodell um \'keywords\'-Feld erweitert; gsh_tp_assign_categories_to_event() liest Keywords aus DB statt hardcoded match-Block' ),
                array( 'tag' => 'FEATURE', 'text' => 'Aufgabe 3: Shepherd.js entfernt; neues Hilfe-Overlay öffnet sich nur auf Klick – kein Auto-Start, kein externes CDN' ),
                array( 'tag' => 'INFRA',   'text' => 'gsh_tp_enqueue_tour_assets() und gtpTourStart() entfernt' ),
            ),
        ),
        array(
            'version' => '3.15.2',
            'entries' => array(
                array( 'tag' => 'BUGFIX', 'text' => 'gsh_tp_color_derive(): Textfarbe wird gegen die helle Pastell-Hintergrundfarbe berechnet statt gegen die Originalfarbe – kein weißer Text mehr auf hellem Hintergrund' ),
                array( 'tag' => 'INFRA',  'text' => 'gsh_tp_contrast_color() steht jetzt vor gsh_tp_color_derive() (explizite Definitionsreihenfolge)' ),
            ),
        ),
        array(
            'version' => '3.15.1',
            'entries' => array(
                array( 'tag' => 'BUGFIX', 'text' => 'Kategorie-Badges und Filter-Buttons: Textfarbe per WCAG-Luminanzformel berechnet – bei hellen Farben (Gelb, Hellgrün) ist der Text jetzt schwarz statt weiß' ),
                array( 'tag' => 'INFRA',  'text' => 'gsh_tp_contrast_color(): neue Hilfsfunktion berechnet #000000 oder #ffffff nach WCAG-2.1-Relative-Luminanz (Schwellenwert 0.179)' ),
                array( 'tag' => 'CSS',    'text' => '--cat-text / --btn-text CSS-Custom-Properties ersetzen hardcoded color-Werte; Dark Mode: kein hardcoded #1a1a2e mehr' ),
            ),
        ),
        array(
            'version' => '3.15.0',
            'entries' => array(
                array( 'tag' => 'FEATURE', 'text' => 'Kategorien-System neu aufgebaut: GSH_TP_DEFAULT_CATEGORIES als unveränderliches Fundament – Kategorien verschwinden nicht mehr nach dem Reload' ),
                array( 'tag' => 'FEATURE', 'text' => 'Keyword-Matching: gsh_tp_assign_categories_to_event() sucht in Titel, Beschreibung, Ort und CATEGORIES-Feld mit schulspezifischen Begriffen' ),
                array( 'tag' => 'FEATURE', 'text' => 'Vereinfachtes Datenmodell: id, label, color, slug – eine Hauptfarbe statt drei separate Farbwähler' ),
                array( 'tag' => 'FEATURE', 'text' => 'Admin-UI: Kategorie-Editor zeigt automatisch abgeleitete Vorschaufarbe aus der Hauptfarbe' ),
                array( 'tag' => 'INFRA',   'text' => 'gsh_tp_color_derive(): leitet bg/border/text automatisch aus einer Hex-Hauptfarbe ab' ),
                array( 'tag' => 'INFRA',   'text' => 'gsh_tp_primary_slug(): Hilfsfunktion ersetzt gsh_tp_cat() + gsh_tp_build_map()' ),
            ),
        ),
        array(
            'version' => '3.14.1',
            'entries' => array(
                array( 'tag' => 'BUGFIX', 'text' => 'Kategorien-Speichern: DB-Fehler werden jetzt als echter Fehler gemeldet statt fälschlicherweise als Erfolg' ),
                array( 'tag' => 'BUGFIX', 'text' => 'Neue Kategorien: auto-generierter Slug wird nach dem Speichern ins DOM zurückgespielt – verhindert inkonsistente CSS-Klassen bei Folge-Speichern ohne Reload' ),
            ),
        ),
        array(
            'version' => '3.14.0',
            'entries' => array(
                array( 'tag' => 'FEATURE', 'text' => 'Kategorien-Editor: Speichern jetzt per AJAX – kein Seiten-Reload, sofortige Rückmeldung' ),
            ),
        ),
        array(
            'version' => '3.13.1',
            'entries' => array(
                array( 'tag' => 'BUGFIX', 'text' => 'Kategorien speichern: Seite hing sich nicht mehr auf nach dem Speichern' ),
            ),
        ),
        array(
            'version' => '3.13.0',
            'entries' => array(
                array( 'tag' => 'BUGFIX',  'text' => 'Stichwörter im Kategorien-Editor werden jetzt zuverlässig gespeichert' ),
                array( 'tag' => 'BUGFIX',  'text' => 'Suchfeld: Platzhaltertext zeigte \\u2026 statt dem Auslassungszeichen …' ),
                array( 'tag' => 'UX',      'text' => 'Feedback-Log ist jetzt als Tab in die Plugin-Einstellungen integriert' ),
            ),
        ),
        array(
            'version' => '3.12.0',
            'entries' => array(
                array( 'tag' => 'FEATURE', 'text' => 'Feedback-Button in der Fußzeile – Fehler, Wünsche und Lob direkt aus dem Terminplan einsenden' ),
                array( 'tag' => 'FEATURE', 'text' => 'Feedback wird als HTML-E-Mail zugestellt – mit Absendername, Typ-Kennzeichnung und Formatierung' ),
                array( 'tag' => 'FEATURE', 'text' => 'Feedback-Log im Admin: alle Einträge einsehbar, auch wenn E-Mail-Versand fehlschlug' ),
                array( 'tag' => 'UX',      'text' => 'Rate-Limiting: max. 3 Feedbacks pro 10 Minuten schützt vor unbeabsichtigtem Mehrfach-Absenden' ),
                array( 'tag' => 'UX',      'text' => 'SMTP-Diagnose-Hinweis im Admin wenn E-Mail-Versand wiederholt fehlschlägt' ),
                array( 'tag' => 'BUGFIX',  'text' => 'Löschen-Button in Kategorien-Editor: Klick auf Icon-Bereich funktioniert jetzt zuverlässig' ),
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

// Migration 3.4.0 → 3.15.0: gsh_tp_get_categories() übernimmt die Initialisierung
// automatisch beim ersten Aufruf (schreibt GSH_TP_DEFAULT_CATEGORIES wenn null).

/* ================================================================
   0. SCHULJAHR-PROFILE (seit 3.5.0)
   ================================================================ */

/**
 * Liefert alle gespeicherten Schuljahr-Profile.
 *
 * Since 4.24.0 this reads from the nested gsh_tp_schoolyears source-of-truth
 * and projects each calendar entry to the flat profile shape consumed by the
 * rest of the plugin. Falls back to the legacy gsh_tp_profiles flat option
 * when no schoolyears have been saved yet (pre-migration state).
 *
 * @since 3.5.0 (rewritten 4.24.0)
 * @return array Array von Profil-Arrays.
 */
function gsh_tp_get_profiles() {
    $schoolyears = gsh_tp_get_schoolyears();
    if ( ! empty( $schoolyears ) ) {
        // Nested source-of-truth: project to flat profile shape.
        $profiles = array();
        foreach ( $schoolyears as $sy ) {
            foreach ( ( $sy['calendars'] ?? array() ) as $cal ) {
                $id = gsh_tp_calendar_id( $sy['key'], $cal['group'] );
                $profiles[] = array(
                    'id'              => $id,
                    'label'           => $cal['label'],
                    'ical_url'        => $cal['ical_url'] ?? '',
                    'cache_duration'  => $sy['shared']['cache_duration'] ?? 3600,
                    'quartal_grenzen' => $sy['shared']['quartal_grenzen'] ?? '',
                    'schuljahr_start' => $sy['shared']['schuljahr_start'] ?? '',
                    'is_active'       => ( ! empty( $sy['is_active'] ) && null === $cal['group'] ),
                    'is_draft'        => ! empty( $cal['is_draft'] ),
                    'created'         => $sy['created'] ?? '',
                    // Extra fields for new code
                    'sj_key'          => $sy['key'],
                    'group'           => $cal['group'],
                    'managed'         => ! empty( $cal['managed'] ),
                    'orphaned'        => ! empty( $cal['orphaned'] ),
                    'source'          => gsh_tp_cal_is_extern( $cal ) ? 'extern' : 'planner',
                );
            }
        }
        return $profiles;
    }
    // Fallback: pre-migration flat option.
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

/* ── Schuljahr-Model (nested, 4.24.0) ── */

/**
 * Liefert alle Schuljahre aus der nested Source-of-Truth.
 *
 * @since 4.24.0
 * @return array Array von Schuljahr-Arrays.
 */
function gsh_tp_get_schoolyears() {
    $raw = get_option( 'gsh_tp_schoolyears', array() );
    return is_array( $raw ) ? $raw : array();
}

/**
 * Reads stage + last-sent timestamp + version for a schoolyear from wp_curriculr_docs.
 *
 * @param  string $sj_key  Schoolyear key, e.g. 'sj_2026_27'.
 * @return array{ stage: string, last_sent: string, version: int }|null  Null if no doc exists yet.
 */
function gsh_tp_get_doc_status( $sj_key ) {
    global $wpdb;
    $table = $wpdb->prefix . 'curriculr_docs';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT stage, updated_at, version FROM `{$table}` WHERE schoolyear = %s LIMIT 1",
            $sj_key
        ),
        ARRAY_A
    );
    if ( ! $row ) {
        return null;
    }
    $version     = (int) ( $row['version'] ?? 0 );
    $author_name = '';
    if ( function_exists( 'gsh_tp_curriculr_revisions_table' ) ) {
        $rev_table = gsh_tp_curriculr_revisions_table();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $author_name = (string) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT author_name FROM `{$rev_table}` WHERE schoolyear = %s AND version = %d LIMIT 1",
                $sj_key,
                $version
            )
        );
    }
    return array(
        'stage'       => (string) ( $row['stage']      ?? 'entwurf' ),
        'last_sent'   => (string) ( $row['updated_at'] ?? '' ),
        'version'     => $version,
        'author_name' => $author_name,
    );
}

/**
 * Speichert das Schuljahr-Array.
 *
 * @since 4.24.0
 * @param  array $schoolyears Bereinigte Schuljahre.
 */
function gsh_tp_save_schoolyears( $schoolyears ) {
    update_option( 'gsh_tp_schoolyears', $schoolyears, true );
}

/**
 * Erzeugt eine stabile Profil-ID für einen Kalender.
 *
 * Haupt-Kalender: id = sj_key.
 * Gruppen-Kalender: id = sj_key . '__' . sanitize_key(group).
 *
 * @since 4.24.0
 * @param  string      $sj_key Schuljahr-Schlüssel.
 * @param  string|null $group  Gruppenname oder null für Haupt-Kalender.
 * @return string              Stabile Profil-ID.
 */
function gsh_tp_calendar_id( $sj_key, $group ) {
    $base = sanitize_key( $sj_key );
    if ( null === $group || '' === $group ) {
        return $base;
    }
    return $base . '__' . sanitize_key( $group );
}

/**
 * Sanitiert einen Kalender-Eintrag.
 *
 * @since 4.24.0
 * @param  array $cal Roher Kalender-Eintrag.
 * @return array      Bereinigter Kalender-Eintrag.
 */
function gsh_tp_sanitize_calendar( $cal ) {
    $group = isset( $cal['group'] ) && is_string( $cal['group'] ) && '' !== $cal['group']
        ? sanitize_text_field( $cal['group'] ) : null;
    return array(
        'group'    => $group,
        'label'    => sanitize_text_field( $cal['label'] ?? '' ),
        'ical_url' => isset( $cal['ical_url'] ) ? gsh_tp_sanitize_url_raw( $cal['ical_url'] ) : '',
        'is_draft' => ! empty( $cal['is_draft'] ),
        'managed'  => ! empty( $cal['managed'] ),
        'orphaned' => ! empty( $cal['orphaned'] ),
        'source'   => gsh_tp_cal_is_extern( $cal ) ? 'extern' : 'planner',
    );
}

/**
 * Prueft, ob ein Kalender-Eintrag als extern (IServ-Feed) markiert ist.
 *
 * Zentrale Stelle fuer den source-Discriminator ('planner'|'extern'), damit
 * Default-Wert und Vergleich nicht an sieben Stellen einzeln geschrieben werden.
 *
 * @since 4.36.0
 * @param  array $cal Kalender-Eintrag.
 * @return bool       true wenn source==='extern'.
 */
function gsh_tp_cal_is_extern( $cal ) {
    return 'extern' === ( $cal['source'] ?? 'planner' );
}

/**
 * Sanitiert ein Schuljahr-Array.
 *
 * @since 4.24.0
 * @param  array $sy Rohes Schuljahr-Array.
 * @return array     Bereinigtes Schuljahr-Array oder leeres Array bei fehlendem Key.
 */
function gsh_tp_sanitize_schoolyear( $sy ) {
    $key = sanitize_key( $sy['key'] ?? '' );
    if ( '' === $key ) {
        return array();
    }
    $shared = $sy['shared'] ?? array();
    $cals   = array();
    foreach ( (array) ( $sy['calendars'] ?? array() ) as $cal ) {
        $clean = gsh_tp_sanitize_calendar( $cal );
        if ( '' !== ( $clean['label'] ?? '' ) || null === $clean['group'] ) {
            $cals[] = $clean;
        }
    }
    return array(
        'key'       => $key,
        'label'     => sanitize_text_field( $sy['label'] ?? $key ),
        'is_active' => ! empty( $sy['is_active'] ),
        'created'   => sanitize_text_field( $sy['created'] ?? current_time( 'Y-m-d' ) ),
        'shared'    => array(
            'quartal_grenzen' => sanitize_textarea_field( $shared['quartal_grenzen'] ?? '' ),
            'schuljahr_start' => sanitize_text_field( $shared['schuljahr_start'] ?? '' ),
            'cache_duration'  => max( 300, min( 86400, absint( $shared['cache_duration'] ?? 3600 ) ) ),
        ),
        'calendars' => $cals,
    );
}

/**
 * Einmalige Migration: flache gsh_tp_profiles → gsh_tp_schoolyears.
 *
 * Guard: läuft nur wenn gsh_tp_schoolyears leer ist und gsh_tp_profiles Daten hat.
 * profile_map wird NICHT migriert — Kompat-Pfad bleibt aktiv.
 * Wird per admin_init aufgerufen.
 *
 * @since 4.24.0
 */
function gsh_tp_migrate_profiles_to_schoolyears() {
    // Guard: schoolyears already populated → nothing to do.
    if ( ! empty( gsh_tp_get_schoolyears() ) ) {
        return;
    }
    $flat = get_option( 'gsh_tp_profiles', array() );
    if ( empty( $flat ) || ! is_array( $flat ) ) {
        return;
    }

    $schoolyears = array();
    foreach ( $flat as $p ) {
        $key = sanitize_key( $p['id'] ?? '' );
        if ( '' === $key ) {
            continue;
        }
        // Check for duplicate key (shouldn't happen but be safe).
        foreach ( $schoolyears as $existing ) {
            if ( $existing['key'] === $key ) {
                continue 2;
            }
        }
        $schoolyears[] = array(
            'key'       => $key,
            'label'     => sanitize_text_field( $p['label'] ?? $key ),
            'is_active' => ! empty( $p['is_active'] ),
            'created'   => sanitize_text_field( $p['created'] ?? current_time( 'Y-m-d' ) ),
            'shared'    => array(
                'quartal_grenzen' => sanitize_textarea_field( $p['quartal_grenzen'] ?? '' ),
                'schuljahr_start' => sanitize_text_field( $p['schuljahr_start'] ?? '' ),
                'cache_duration'  => max( 300, min( 86400, absint( $p['cache_duration'] ?? 3600 ) ) ),
            ),
            'calendars' => array(
                array(
                    'group'    => null,
                    'label'    => sanitize_text_field( $p['label'] ?? $key ) . ' · Alle Termine',
                    'ical_url' => gsh_tp_sanitize_url_raw( $p['ical_url'] ?? '' ),
                    'is_draft' => ! empty( $p['is_draft'] ),
                    'managed'  => false, // pre-existing calendars are manual, not managed
                    'orphaned' => false,
                    // 4.36.0: Eine vor 4.24.0 von Hand eingetragene URL zeigt auf einen
                    // fremden Kalender (IServ) und darf vom Planner nicht ueberschrieben werden.
                    'source'   => gsh_tp_classify_calendar_source( $p['ical_url'] ?? '' ),
                ),
            ),
        );
    }

    if ( ! empty( $schoolyears ) ) {
        gsh_tp_save_schoolyears( $schoolyears );
    }
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
add_action( 'admin_init', 'gsh_tp_migrate_profiles_to_schoolyears' );

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
/**
 * Prueft, ob eine URL auf einen vom Plugin selbst erzeugten Curriculr-Feed zeigt.
 *
 * Unterscheidungsmerkmal fuer die Quelle eines Kalenders: der eigene Feed liegt
 * immer unter der REST-Route curriculr/v1/feed/. Alles andere ist ein fremder
 * Kalender (typisch: IServ).
 *
 * @since 4.36.0
 * @param  string $url Zu pruefende URL.
 * @return bool        true wenn es der eigene Feed ist.
 */
function gsh_tp_is_curriculr_feed_url( $url ) {
    $url = (string) $url;
    return '' !== $url && false !== strpos( $url, '/curriculr/v1/feed/' );
}

/**
 * Bestimmt die Quelle ('planner'|'extern') eines Kalenders anhand seiner URL.
 *
 * Zentrale Klassifizierung fuer beide Nachruest-Migrationen (Flat-Profile-
 * Migration und Source-Backfill), damit die Einordnung nicht an zwei Stellen
 * getrennt gepflegt werden muss.
 *
 * @since 4.36.0
 * @param  string $url Rohe oder bereits bereinigte iCal-URL.
 * @return string       'planner' oder 'extern'.
 */
function gsh_tp_classify_calendar_source( $url ) {
    $clean = gsh_tp_sanitize_url_raw( (string) $url );
    if ( '' === $clean ) {
        return 'planner'; // Kein Wert -> wird vom Planner befuellt, sobald gesendet wird.
    }
    return gsh_tp_is_curriculr_feed_url( $clean ) ? 'planner' : 'extern';
}

/**
 * Einmalige 4.36.0-Nachrueststufe: Quelle bestehender Kalender bestimmen.
 *
 * Installationen, die vor 4.24.0 eine IServ-URL von Hand eingetragen hatten,
 * wurden beim Modellwechsel ohne Quellen-Kennzeichnung uebernommen. Ohne diese
 * Kennzeichnung wuerde der naechste Planner-Push die IServ-URL ueberschreiben.
 * Laeuft genau einmal, danach schuetzt das Options-Flag vor Wiederholung.
 *
 * @since 4.36.0
 * @return void
 */
function gsh_tp_migrate_calendar_source() {
    if ( get_option( 'gsh_tp_source_migrated' ) ) {
        return;
    }
    $schoolyears = gsh_tp_get_schoolyears();
    $changed     = false;
    foreach ( $schoolyears as &$sy ) {
        // Wichtig: direkt ueber $sy['calendars'] iterieren. Ein Ausdruck wie
        // ( $sy['calendars'] ?? array() ) liefert eine Kopie — Referenz-Schreibzugriffe
        // darauf gingen verloren und die Migration liefe wirkungslos durch.
        if ( ! isset( $sy['calendars'] ) || ! is_array( $sy['calendars'] ) ) {
            continue;
        }
        foreach ( $sy['calendars'] as &$cal ) {
            if ( ! is_array( $cal ) || isset( $cal['source'] ) ) {
                continue;
            }
            $cal['source'] = gsh_tp_classify_calendar_source( $cal['ical_url'] ?? '' );
            $changed       = true;
        }
        unset( $cal );
    }
    unset( $sy );
    if ( $changed ) {
        gsh_tp_save_schoolyears( $schoolyears );
    }
    update_option( 'gsh_tp_source_migrated', 1 );
}
add_action( 'admin_init', 'gsh_tp_migrate_calendar_source' );

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
 * Liefert und setzt den Draft-Kiosk-Anfrage-Kontext.
 *
 * Einzelne Funktion mit statischer Variable — verhindert das Zustandsteilungs-
 * Problem zwischen zwei separaten Funktionen. Nach erfolgreichem Token-Check
 * auf true setzen, damit gsh_tp_shortcode() den Admin-Check überspringt.
 *
 * @since 4.1.0
 * @param bool $set true = Kontext aktivieren, false (Standard) = nur abfragen.
 * @return bool     Ob der Draft-Kiosk-Kontext aktiv ist.
 */
function gsh_tp_draft_kiosk_context( bool $set = false ): bool {
    static $active = false;
    if ( $set ) {
        $active = true;
    }
    return $active;
}

/**
 * Prüft den Entwurf-Kiosk-Zugriff per Token mit IP-basiertem Rate-Limiting.
 *
 * Vergleicht den übergebenen Token timing-sicher (hash_equals) mit dem gespeicherten
 * Entwurf-Token. Verhindert Brute-Force: Nach 10 Fehlversuchen von derselben IP
 * innerhalb einer Stunde wird der Zugriff blockiert.
 *
 * Aufruf aus dem Page-Template page-terminplan-entwurf.php:
 *   $token = sanitize_text_field( $_GET['token'] ?? '' );
 *   if ( ! gsh_tp_check_draft_kiosk_access( $token ) ) { status_header( 403 ); exit; }
 *
 * @since 4.1.0
 * @param  string $token Der vom Nutzer übergebene Token (?token= URL-Parameter).
 * @return bool          true bei gültigem Token, false bei falschem Token oder Rate-Limit.
 */
function gsh_tp_check_draft_kiosk_access( string $token ): bool {
    $saved = get_option( 'gsh_tp_draft_kiosk_token', '' );
    if ( empty( $saved ) ) {
        return false;
    }

    $ip       = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ) );
    $rate_key = 'gsh_tp_draft_rl_' . md5( $ip );
    $attempts = (int) get_transient( $rate_key );
    if ( $attempts >= 10 ) {
        return false;
    }

    if ( ! hash_equals( $saved, $token ) ) {
        set_transient( $rate_key, $attempts + 1, HOUR_IN_SECONDS );
        return false;
    }

    gsh_tp_draft_kiosk_context( true );
    return true;
}

/**
 * Leitet WordPress-Anfragen an das Plugin-eigene Entwurf-Template um.
 *
 * Ersetzt das Theme-Template wenn eine Seite mit dem Meta-Wert
 * page-terminplan-entwurf.php aufgerufen wird. Kein Theme-Copy nötig.
 *
 * @since 4.3.4
 * @param  string $template Aktuell gewähltes Template.
 * @return string           Plugin-Template-Pfad oder unverändertes $template.
 */
function gsh_tp_draft_template_include( string $template ): string {
    if ( is_page() && 'page-terminplan-entwurf.php' === get_post_meta( get_the_ID(), '_wp_page_template', true ) ) {
        $plugin_tpl = plugin_dir_path( __FILE__ ) . 'page-terminplan-entwurf.php';
        if ( file_exists( $plugin_tpl ) ) {
            return $plugin_tpl;
        }
    }
    return $template;
}

/**
 * Registriert das Entwurf-Template im WP-Seitenvorlage-Dropdown.
 *
 * Ermöglicht die Auswahl von „Terminplan Entwurf-Vorschau" im Seiten-Editor
 * ohne das Template in den Theme-Ordner kopieren zu müssen.
 *
 * @since 4.3.4
 * @param  array $templates Bestehende Template-Liste.
 * @return array            Erweiterte Template-Liste.
 */
function gsh_tp_register_draft_template( array $templates ): array {
    $templates['page-terminplan-entwurf.php'] = 'Terminplan Entwurf-Vorschau';
    return $templates;
}

/**
 * Leitet WordPress-Anfragen an das Plugin-eigene Kiosk-Template um.
 *
 * Ersetzt das Theme-Template wenn eine Seite mit dem Meta-Wert
 * page-terminplan-kiosk.php aufgerufen wird. Kein Theme-Copy nötig.
 *
 * @since 4.20.0
 * @param  string $template Aktuell gewähltes Template.
 * @return string           Plugin-Template-Pfad oder unverändertes $template.
 */
function gsh_tp_kiosk_template_include( string $template ): string {
    if ( is_page() && 'page-terminplan-kiosk.php' === get_post_meta( get_the_ID(), '_wp_page_template', true ) ) {
        $plugin_tpl = plugin_dir_path( __FILE__ ) . 'page-terminplan-kiosk.php';
        if ( file_exists( $plugin_tpl ) ) {
            return $plugin_tpl;
        }
    }
    return $template;
}

/**
 * Registriert das Kiosk-Template im WP-Seitenvorlage-Dropdown.
 *
 * Ermöglicht die Auswahl von „Terminplan Kiosk" im Seiten-Editor
 * ohne das Template in den Theme-Ordner kopieren zu müssen.
 *
 * @since 4.20.0
 * @param  array $templates Bestehende Template-Liste.
 * @return array            Erweiterte Template-Liste.
 */
function gsh_tp_register_kiosk_template( array $templates ): array {
    $templates['page-terminplan-kiosk.php'] = 'Terminplan Kiosk';
    return $templates;
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

/**
 * AJAX-Handler: Feedback-E-Mail versenden.
 *
 * Empfängt Typ und Nachricht via POST, validiert die Eingaben,
 * baut eine strukturierte E-Mail und sendet sie via wp_mail().
 *
 * @since 3.12.0
 * @return void  Antwortet mit JSON und beendet die Ausführung.
 */
function gsh_tp_ajax_feedback() {
    // Nonce prüfen
    check_ajax_referer( 'gsh_tp_feedback_nonce', 'nonce' );

    // Honeypot: verstecktes Feld muss leer sein (Spam-Schutz)
    if ( ! empty( $_POST['gsh_tp_hp'] ) ) {
        wp_send_json_success( array( 'message' => 'Feedback gesendet. Danke!' ) );
    }

    // Rate-Limiting: max. 3 Feedbacks pro IP in 10 Minuten
    $ip      = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
    $ip_hash = hash( 'sha256', $ip . wp_salt() );
    $rl_key  = 'gsh_tp_rl_' . substr( $ip_hash, 0, 20 );
    $rl      = (int) get_transient( $rl_key );
    if ( $rl >= 3 ) {
        wp_send_json_error( array( 'message' => 'Bitte warte einige Minuten bevor du erneut Feedback sendest.' ) );
    }

    // Eingaben bereinigen
    $sender   = sanitize_text_field( $_POST['sender'] ?? '' );
    $type_key = sanitize_key( $_POST['type'] ?? '' );
    $message  = sanitize_textarea_field( $_POST['message'] ?? '' );

    // Erlaubte Typen
    $allowed_types = array(
        'bug'    => '🐛 Fehler melden',
        'wish'   => '💡 Funktionswunsch',
        'praise' => '👍 Lob',
        'other'  => '💬 Sonstiges',
    );

    // Validierung
    if ( ! isset( $allowed_types[ $type_key ] ) ) {
        wp_send_json_error( array( 'message' => 'Ungültiger Feedback-Typ.' ) );
    }
    if ( mb_strlen( $message ) < 3 ) {
        wp_send_json_error( array( 'message' => 'Nachricht zu kurz.' ) );
    }
    if ( mb_strlen( $message ) > 1000 ) {
        wp_send_json_error( array( 'message' => 'Nachricht zu lang (max. 1000 Zeichen).' ) );
    }

    $type_label = $allowed_types[ $type_key ];

    // Empfänger aus Einstellungen (Fallback: WordPress-Admin)
    $to = get_option( 'gsh_tp_feedback_email', get_bloginfo( 'admin_email' ) );
    if ( ! is_email( $to ) ) {
        $to = get_bloginfo( 'admin_email' );
    }

    // Betreff
    $subject = sprintf( '[Schul-Terminplan] %s', $type_label );

    // HTML-E-Mail-Body
    $name_line = $sender ? '<tr><td style="padding:4px 0;color:#64748b;font-weight:600;width:120px">Absender:</td><td style="padding:4px 0">' . esc_html( $sender ) . '</td></tr>' : '';
    $body = '<!DOCTYPE html><html><body style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;color:#1e293b;margin:0;padding:0">'
        . '<div style="max-width:540px;margin:0 auto;padding:32px 24px">'
        . '<h2 style="margin:0 0 24px;font-size:1.1rem;color:#1e293b">Neues Feedback aus dem Schul-Terminplan</h2>'
        . '<table style="border-collapse:collapse;width:100%">'
        . $name_line
        . '<tr><td style="padding:4px 0;color:#64748b;font-weight:600;width:120px">Typ:</td><td style="padding:4px 0">' . esc_html( $type_label ) . '</td></tr>'
        . '<tr><td style="padding:12px 0 4px;color:#64748b;font-weight:600;vertical-align:top">Nachricht:</td><td style="padding:12px 0 4px"><div style="background:#f8fafc;border-left:3px solid #00467D;padding:12px 16px;border-radius:0 8px 8px 0;white-space:pre-wrap">' . esc_html( $message ) . '</div></td></tr>'
        . '</table>'
        . '<hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0">'
        . '<p style="font-size:.78rem;color:#94a3b8;margin:0">Plugin v' . GSH_TP_VERSION . ' &bull; ' . esc_html( wp_date( 'd.m.Y, H:i' ) ) . ' Uhr &bull; ' . esc_url( home_url() ) . '</p>'
        . '</div></body></html>';

    // Absender explizit setzen
    add_filter( 'wp_mail_from',         'gsh_tp_feedback_mail_from' );
    add_filter( 'wp_mail_from_name',    'gsh_tp_feedback_mail_from_name' );
    add_filter( 'wp_mail_content_type', 'gsh_tp_feedback_mail_content_type' );

    $sent = wp_mail( $to, $subject, $body );

    remove_filter( 'wp_mail_from',         'gsh_tp_feedback_mail_from' );
    remove_filter( 'wp_mail_from_name',    'gsh_tp_feedback_mail_from_name' );
    remove_filter( 'wp_mail_content_type', 'gsh_tp_feedback_mail_content_type' );

    // Rate-Limit-Zähler erhöhen
    set_transient( $rl_key, $rl + 1, 10 * MINUTE_IN_SECONDS );

    // DB-Log: immer speichern (auch bei Erfolg – für Diagnose)
    gsh_tp_feedback_log( $type_key, $sender, $message, $ip_hash, $sent ? 'sent' : 'failed' );

    if ( $sent ) {
        wp_send_json_success( array( 'message' => 'Feedback gesendet. Danke!' ) );
    } else {
        // Fehlerzähler für SMTP-Diagnose-Warnung
        $fail_count = (int) get_option( 'gsh_tp_mail_fail_count', 0 ) + 1;
        update_option( 'gsh_tp_mail_fail_count', $fail_count );
        wp_send_json_error( array( 'message' => 'E-Mail konnte nicht gesendet werden. Dein Feedback wurde aber gespeichert und kann im Admin eingesehen werden.' ) );
    }
}

/**
 * Setzt den Absender-Namen für Feedback-E-Mails.
 * Wird nur während gsh_tp_ajax_feedback() als Filter aktiv.
 *
 * @since 3.12.0
 */
function gsh_tp_feedback_mail_from_name() {
    return get_bloginfo( 'name' );
}

/**
 * Setzt die Absender-Adresse für Feedback-E-Mails.
 * Wird nur während gsh_tp_ajax_feedback() als Filter aktiv.
 *
 * @since 3.12.0
 */
function gsh_tp_feedback_mail_from() {
    return get_bloginfo( 'admin_email' );
}

/**
 * Setzt den Content-Type für Feedback-E-Mails auf HTML.
 * @since 3.12.0
 */
function gsh_tp_feedback_mail_content_type() {
    return 'text/html';
}

/**
 * Speichert einen Feedback-Eintrag in der WordPress-Datenbank (Transient-basiertes Log).
 *
 * Nutzt wp_options als einfaches Append-Log (max. 200 Einträge).
 * Kein Schema, kein CREATE TABLE nötig.
 *
 * @since 3.12.0
 * @param string $type     Feedback-Typ-Schlüssel
 * @param string $sender   Absender-Name (optional)
 * @param string $message  Feedback-Text
 * @param string $ip_hash  SHA-256-Hash der IP (DSGVO-konform)
 * @param string $status   'sent' oder 'failed'
 * @return void
 */
function gsh_tp_feedback_log( $type, $sender, $message, $ip_hash, $status ) {
    $log     = get_option( 'gsh_tp_feedback_log', array() );
    if ( ! is_array( $log ) ) {
        $log = array();
    }
    array_unshift( $log, array(
        'ts'      => current_time( 'Y-m-d H:i:s' ),
        'type'    => $type,
        'sender'  => $sender,
        'message' => $message,
        'ip'      => $ip_hash,
        'status'  => $status,
    ) );
    // Einträge älter als 90 Tage entfernen
    $cutoff = strtotime( '-90 days', current_time( 'timestamp' ) );
    $log = array_values( array_filter( $log, function ( $e ) use ( $cutoff ) {
        return isset( $e['ts'] ) && strtotime( $e['ts'] ) >= $cutoff;
    } ) );
    // Maximal 200 Einträge behalten
    $log = array_slice( $log, 0, 200 );
    update_option( 'gsh_tp_feedback_log', $log, false );
}

/**
 * Registriert den Einstellungsmenüeintrag im WordPress-Backend.
 *
 * Der Menüpunkt erscheint unter „Einstellungen → Schul-Terminplan" und ist nur
 * für Benutzer mit der Berechtigung „manage_options" (Admins) sichtbar.
 *
 * @since 1.2.0
 * @return void
 */
add_action( 'admin_menu', 'gsh_tp_admin_menu' );
function gsh_tp_admin_menu() {
    add_options_page(
        'Schul-Terminplan',
        'Schul-Terminplan',
        'manage_options',
        GSH_TP_SLUG,
        'gsh_tp_settings_page'
    );
    add_options_page(
        'Schul-Terminplan – Datensicherung',
        'Datensicherung',
        'manage_options',
        'gsh-terminplan-backup',
        'gsh_tp_backup_page'
    );
    // Kein separater Menüpunkt für Feedback-Log mehr – ist jetzt Tab in der Hauptseite
}

/**
 * Rendert die Datensicherungs-Seite (Export + Import) im WordPress-Backend.
 *
 * Erreichbar unter Einstellungen → Datensicherung.
 *
 * @since 4.21.0
 * @return void
 */
function gsh_tp_backup_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $imported   = ! empty( $_GET['imported'] );
    $import_err = (int) ( $_GET['import_error'] ?? 0 );
    $err_msgs   = array(
        1 => 'Keine Datei ausgewählt.',
        2 => 'Datei zu groß (max. 512 KB).',
        3 => 'Ungültiges Dateiformat. Bitte eine Curriculr-Export-Datei wählen.',
    );
    ?>
    <div class="wrap gsh-backup-wrap">
        <h1>Schul-Terminplan – Datensicherung</h1>

        <?php if ( $imported ) : ?>
        <div class="notice notice-success is-dismissible"><p>Einstellungen erfolgreich importiert.</p></div>
        <?php endif; ?>
        <?php if ( $import_err && isset( $err_msgs[ $import_err ] ) ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $err_msgs[ $import_err ] ); ?></p></div>
        <?php endif; ?>

        <div class="gsh-backup-section">
            <h2>Einstellungen exportieren</h2>
            <p>Lädt alle Plugin-Einstellungen als JSON-Datei herunter. Curriculr-Planungsdokumente sind nicht enthalten — diese verbleiben in der Datenbank.</p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'gsh_tp_curriculr_export_nonce' ); ?>
                <input type="hidden" name="action" value="gsh_tp_curriculr_export">
                <button type="submit" class="button button-primary">Einstellungen exportieren</button>
            </form>
        </div>

        <div class="gsh-backup-section">
            <h2>Einstellungen importieren</h2>
            <p>Stellt alle Plugin-Einstellungen aus einer Export-Datei wieder her. Vorhandene Einstellungen werden überschrieben.</p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field( 'gsh_tp_import_settings' ); ?>
                <input type="hidden" name="action" value="gsh_tp_import_settings">
                <input type="file" name="settings_file" accept=".json" required class="gsh-backup-file-input">
                <?php submit_button( 'Einstellungen importieren', 'secondary', 'submit', false ); ?>
            </form>
        </div>
    </div>
    <?php
}

/**
 * Rendert den Feedback-Log-Tab in der Plugin-Admin-Seite.
 *
 * Umbenannt von gsh_tp_feedback_log_page() – ist jetzt Tab statt eigener Menüpunkt.
 *
 * @since 3.12.0 (Tab seit 3.13.0)
 * @return void
 */
function gsh_tp_render_feedback_log_tab() {
    $log        = get_option( 'gsh_tp_feedback_log', array() );
    $fail_count = (int) get_option( 'gsh_tp_mail_fail_count', 0 );

    $type_labels = array(
        'bug'    => '🐛 Fehler',
        'wish'   => '💡 Wunsch',
        'praise' => '👍 Lob',
        'other'  => '💬 Sonstiges',
    );

    if ( $fail_count >= 3 ) : ?>
    <div class="notice notice-warning inline" style="margin-bottom:16px">
        <p><strong>⚠ Hinweis:</strong> Die letzten <?php echo (int) $fail_count; ?> Feedback-E-Mails konnten nicht zugestellt werden.
        Bitte prüfe die WordPress-E-Mail-Konfiguration oder installiere ein SMTP-Plugin wie
        <a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=wp+mail+smtp&tab=search&type=term' ) ); ?>">WP Mail SMTP</a>.</p>
    </div>
    <?php endif;

    if ( empty( $log ) ) : ?>
        <p>Noch kein Feedback eingegangen.</p>
    <?php else : ?>
        <p><?php echo count( $log ); ?> Einträge (neueste zuerst, max. 200 gespeichert)</p>
        <table class="widefat striped" style="max-width:1000px">
            <thead>
                <tr>
                    <th style="width:140px">Zeitpunkt</th>
                    <th style="width:120px">Typ</th>
                    <th style="width:130px">Absender</th>
                    <th>Nachricht</th>
                    <th style="width:70px">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $log as $entry ) : ?>
                <tr>
                    <td style="white-space:nowrap"><?php echo esc_html( $entry['ts'] ?? '–' ); ?></td>
                    <td><?php echo esc_html( $type_labels[ $entry['type'] ?? '' ] ?? $entry['type'] ?? '–' ); ?></td>
                    <td><?php echo esc_html( $entry['sender'] ?: '(anonym)' ); ?></td>
                    <td style="white-space:pre-wrap"><?php echo esc_html( $entry['message'] ?? '' ); ?></td>
                    <td>
                        <?php if ( ( $entry['status'] ?? '' ) === 'sent' ) : ?>
                            <span style="color:#166534;font-weight:700">✓ gesendet</span>
                        <?php else : ?>
                            <span style="color:#991b1b;font-weight:700">✗ Fehler</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <form method="post" action="<?php echo esc_url( admin_url( 'options-general.php?page=gsh-terminplan&tab=_system' ) ); ?>" style="margin-top:16px">
            <?php wp_nonce_field( 'gsh_tp_clear_feedback_log' ); ?>
            <input type="hidden" name="gsh_tp_clear_feedback_log" value="1">
            <?php submit_button( 'Log leeren', 'secondary', 'submit', false ); ?>
        </form>
    <?php endif;
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
        'gsh-terminplan-tokens',
        plugin_dir_url( __FILE__ ) . 'assets/css/design-tokens.css',
        array(),
        GSH_TP_VERSION
    );
    wp_enqueue_style(
        'gsh-terminplan',
        plugin_dir_url( __FILE__ ) . 'assets/css/gsh-terminplan.css',
        array( 'gsh-terminplan-tokens' ),
        GSH_TP_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'gsh_tp_enqueue_frontend_styles' );

/**
 * Enqueues the delete-warning script on plugins.php and the backup-page CSS
 * on both plugins.php and the backup settings page.
 *
 * @since 4.21.0
 * @param string $hook Current admin page hook.
 * @return void
 */
function gsh_tp_enqueue_admin_delete_warn( $hook ) {
    // Enqueue backup-page CSS on both plugins.php and the backup page itself.
    if ( in_array( $hook, array( 'plugins.php', 'settings_page_gsh-terminplan-backup' ), true ) ) {
        wp_enqueue_style(
            'gsh-terminplan',
            plugin_dir_url( __FILE__ ) . 'assets/css/gsh-terminplan.css',
            array(),
            GSH_TP_VERSION
        );
    }
    if ( 'plugins.php' !== $hook ) {
        return;
    }
    wp_enqueue_script(
        'gsh-curriculr-delete-warn',
        plugin_dir_url( __FILE__ ) . 'assets/js/curriculr-delete-warn.js',
        array(),
        GSH_TP_VERSION,
        true
    );
    wp_localize_script(
        'gsh-curriculr-delete-warn',
        'gshDeleteWarn',
        array( 'backupUrl' => admin_url( 'options-general.php?page=gsh-terminplan-backup' ) )
    );
}
add_action( 'admin_enqueue_scripts', 'gsh_tp_enqueue_admin_delete_warn' );

/**
 * Adds a "Einstellungen sichern" link to the plugin action links on plugins.php.
 *
 * @since 4.21.0
 * @param string[] $links Existing action links.
 * @return string[] Modified action links with backup link prepended.
 */
function gsh_tp_plugin_action_links( $links ) {
    $backup_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=gsh-terminplan-backup' ) ) . '" class="gsh-plugin-backup-link">Einstellungen sichern ↗</a>';
    array_unshift( $links, $backup_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'gsh_tp_plugin_action_links' );

// ENTFERNT v3.16.0: gsh_tp_enqueue_tour_assets() – Shepherd.js vollständig entfernt.
// Das Hilfe-Overlay ist jetzt ein schlichtes natives HTML/CSS/JS-Overlay (kein CDN).

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
// Feedback-AJAX (eingeloggte und nicht-eingeloggte Nutzer)
add_action( 'wp_ajax_gsh_tp_feedback',        'gsh_tp_ajax_feedback' );
add_action( 'wp_ajax_nopriv_gsh_tp_feedback', 'gsh_tp_ajax_feedback' );
// Kategorien-AJAX (nur eingeloggte Admins)
add_action( 'wp_ajax_gsh_tp_save_categories', 'gsh_tp_ajax_save_categories' );
add_action( 'wp_ajax_gsh_tp_import_categories_from_planner', 'gsh_tp_ajax_import_categories_from_planner' );
// Update-Notice (erscheint nach Plugin-Update bis dismissed)
add_action( 'admin_notices',                 'gsh_tp_update_notice' );
add_action( 'wp_ajax_gsh_tp_dismiss_notice', 'gsh_tp_ajax_dismiss_notice' );
// Entwurf-Vorschau: Plugin-Template servieren + in WP-Seitenvorlage-Dropdown registrieren
add_filter( 'template_include',    'gsh_tp_draft_template_include' );
add_filter( 'theme_page_templates', 'gsh_tp_register_draft_template' );
// IServ-Kiosk: Plugin-Template servieren + in WP-Seitenvorlage-Dropdown registrieren
add_filter( 'template_include',    'gsh_tp_kiosk_template_include' );
add_filter( 'theme_page_templates', 'gsh_tp_register_kiosk_template' );
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
    // gsh_tp_categories, gsh_tp_draft_kiosk_token, gsh_tp_kiosk_token,
    // gsh_tp_iserv_domain, gsh_tp_feedback_email werden nicht über die WP
    // Settings API gespeichert — direkter POST-Handler in gsh_tp_settings_page().
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

// ENTFERNT v3.15.0 – Kategorie-Neuaufbau:
// gsh_tp_default_categories()   → Konstante GSH_TP_DEFAULT_CATEGORIES ersetzt diese Funktion
// gsh_tp_sanitize_categories()  → Sanitisierung erfolgt in gsh_tp_save_categories()
// gsh_tp_ajax_save_categories() → Durch neue Version mit POST-Param 'categories' ersetzt
// gsh_tp_get_categories()       → Durch neue Version mit Konstanten-Fundament ersetzt

/**
 * Gibt alle gespeicherten Kategorien zurück (gecacht per Request).
 *
 * Beim ersten Aufruf: Wenn noch keine Kategorien in der Datenbank gespeichert
 * sind (null), werden GSH_TP_DEFAULT_CATEGORIES einmalig geschrieben und
 * zurückgegeben. Sind Daten vorhanden, werden sie geladen. Ist das Array
 * leer oder beschädigt, wird GSH_TP_DEFAULT_CATEGORIES als Fallback verwendet,
 * ohne dabei die Datenbank zu überschreiben.
 *
 * @since 3.15.0
 * @return array Array von Kategorie-Arrays (id, label, color, slug).
 */
function gsh_tp_get_categories(): array {
    static $cats = null;
    if ( null !== $cats ) {
        return $cats;
    }

    $saved = get_option( 'gsh_tp_categories', null );

    // Noch nie gespeichert → Standardkategorien einmalig in DB schreiben
    if ( null === $saved ) {
        update_option( 'gsh_tp_categories', GSH_TP_DEFAULT_CATEGORIES, false );
        $cats = GSH_TP_DEFAULT_CATEGORIES;
        return $cats;
    }

    // Gespeichert aber leer oder beschädigt → Konstantfallback, OHNE in DB zu schreiben
    if ( ! is_array( $saved ) || empty( $saved ) ) {
        $cats = GSH_TP_DEFAULT_CATEGORIES;
        return $cats;
    }

    $cats = $saved;
    return $cats;
}

/**
 * Speichert Kategorien in der Datenbank und aktualisiert den Request-Cache.
 *
 * Validiert jeden Eintrag (id, label, color, slug) und überspringt
 * unvollständige Einträge. Gibt bei Erfolg das gespeicherte Array zurück
 * (inkl. dem Fall, dass der Wert identisch war und kein DB-Update nötig war).
 *
 * @since 3.15.0
 * @param  array $categories Roheingabe – wird intern bereinigt.
 * @return array|false       Gespeichertes Kategorien-Array oder false bei Fehler.
 */
function gsh_tp_save_categories( array $categories ) {
    if ( empty( $categories ) ) {
        return false;
    }

    $clean = array();
    $hex   = '/^#[0-9a-fA-F]{6}$/';
    $ids   = array(); // Duplikat-Schutz für IDs
    $slugs = array(); // Duplikat-Schutz für Slugs

    foreach ( $categories as $cat ) {
        $id    = sanitize_key( $cat['id'] ?? '' );
        $label = sanitize_text_field( $cat['label'] ?? '' );
        if ( '' === $id || '' === $label ) {
            continue; // Unvollständige Einträge überspringen
        }
        if ( in_array( $id, $ids, true ) ) {
            continue; // Doppelte IDs überspringen
        }
        $slug = sanitize_key( $cat['slug'] ?? $id );
        if ( '' === $slug || in_array( $slug, $slugs, true ) ) {
            $slug = $id; // Fallback auf id
        }
        $color = preg_match( $hex, $cat['color'] ?? '' ) ? $cat['color'] : '#94a3b8';

        // Keywords: kommaseparierter String (aus Admin-Textarea) oder Array → bereinigtes Array
        $raw_kw = $cat['keywords'] ?? [];
        if ( is_string( $raw_kw ) ) {
            $raw_kw = explode( ',', $raw_kw );
        }
        $keywords = array_values( array_filter(
            array_map( 'sanitize_text_field', array_map( 'trim', (array) $raw_kw ) )
        ) );

        $ids[]   = $id;
        $slugs[] = $slug;
        $clean[] = array(
            'id'       => $id,
            'label'    => $label,
            'color'    => $color,
            'slug'     => $slug,
            'keywords' => $keywords,
        );
    }

    if ( empty( $clean ) ) {
        return false;
    }

    // update_option() gibt false wenn Wert identisch – das ist kein Fehler
    update_option( 'gsh_tp_categories', $clean, false );

    // Gegencheck: Option muss in DB vorhanden und nicht leer sein.
    // Gibt gleichzeitig den gespeicherten Stand zurück (spart zusätzliches get_option() beim Aufrufer).
    $verify = get_option( 'gsh_tp_categories', array() );
    if ( ! is_array( $verify ) || empty( $verify ) ) {
        return false;
    }

    // Hinweis: gsh_tp_get_categories() nutzt eine static-Variable die nach dem Speichern
    // nicht zurückgesetzt werden kann – da der AJAX-Request danach endet, ist das kein Problem.

    return $verify;
}

/**
 * Zeigt einen dismissiblen Admin-Hinweis nach einem Plugin-Update.
 * Vergleich GSH_TP_VERSION mit gespeicherter gsh_tp_noticed_version.
 *
 * @since 4.23.0
 */
function gsh_tp_update_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( GSH_TP_VERSION === get_option( 'gsh_tp_noticed_version', '' ) ) {
        return;
    }
    $cl      = gsh_tp_changelog();
    $entries = ! empty( $cl ) ? $cl[0]['entries'] : array();
    $nonce   = wp_create_nonce( 'gsh_tp_dismiss_notice' );
    ?>
    <div class="notice notice-info is-dismissible" id="gsh-tp-update-notice">
        <p>
            <strong>Curriculr <?php echo esc_html( GSH_TP_VERSION ); ?> installiert</strong>
            &mdash; <a href="#" onclick="if(typeof gshAdminChangelogOpen==='function'){gshAdminChangelogOpen();}return false;">Vollständiges Changelog</a>
        </p>
        <?php if ( ! empty( $entries ) ) : ?>
        <ul>
            <?php foreach ( $entries as $entry ) : ?>
            <li>
                <strong>[<?php echo esc_html( $entry['tag'] ); ?>]</strong>
                <?php echo esc_html( $entry['text'] ); ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <script>
    (function() {
        var el = document.getElementById('gsh-tp-update-notice');
        if ( ! el ) { return; }
        el.addEventListener('click', function(e) {
            if ( ! e.target.classList.contains('notice-dismiss') ) { return; }
            jQuery.post(ajaxurl, {
                action:      'gsh_tp_dismiss_notice',
                _ajax_nonce: <?php echo wp_json_encode( $nonce ); ?>
            });
        });
    }());
    </script>
    <?php
}

/**
 * AJAX-Handler: Notice dismisst → gsh_tp_noticed_version auf aktuelle Version setzen.
 *
 * @since 4.23.0
 */
function gsh_tp_ajax_dismiss_notice() {
    check_ajax_referer( 'gsh_tp_dismiss_notice' );
    if ( current_user_can( 'manage_options' ) ) {
        update_option( 'gsh_tp_noticed_version', GSH_TP_VERSION, false );
    }
    wp_die();
}

/**
 * AJAX-Handler: Kategorien via fetch() speichern. (v3.15.0)
 *
 * Empfängt Kategorien als JSON-String im POST-Parameter 'categories',
 * validiert per Nonce + Capability, bereinigt und speichert via
 * gsh_tp_save_categories(). Gibt den gespeicherten Stand zurück.
 *
 * @since 3.15.0
 * @return void  Sendet JSON-Response und beendet die Ausführung.
 */
function gsh_tp_ajax_save_categories(): void {
    // 1. Nonce prüfen (Action-String muss mit wp_create_nonce() identisch sein)
    check_ajax_referer( 'gsh_tp_save_categories_nonce', 'nonce' );

    // 2. Berechtigung prüfen
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Keine Berechtigung.' ), 403 );
        return;
    }

    // 3. Daten auslesen – wp_unslash() VOR json_decode() ist zwingend notwendig
    $raw = wp_unslash( $_POST['categories'] ?? '[]' );
    $categories = json_decode( $raw, true );

    // 4. JSON-Fehler abfangen
    if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $categories ) ) {
        wp_send_json_error( array(
            'message' => 'Ungültiges Datenformat: ' . json_last_error_msg(),
        ), 400 );
        return;
    }

    // 5. Speichern – gibt bei Erfolg das gespeicherte Array zurück, sonst false
    $saved = gsh_tp_save_categories( $categories );

    if ( false === $saved ) {
        wp_send_json_error( array( 'message' => 'Speichern fehlgeschlagen.' ), 500 );
        return;
    }

    // 6. Gespeicherten Stand zurückgeben (kommt direkt aus gsh_tp_save_categories(), kein extra DB-Call)
    wp_send_json_success( array(
        'message'    => 'Kategorien gespeichert.',
        'categories' => $saved,
    ) );
}

/**
 * AJAX-Handler: liefert die Kategorien eines Planner-Schuljahrs zum Übernehmen.
 *
 * Rein lesend — schreibt niemals gsh_tp_categories. Persistiert wird
 * ausschließlich über den bestehenden gsh_tp_ajax_save_categories()-Pfad,
 * nachdem der Admin das clientseitige Merge-Ergebnis geprüft hat.
 *
 * @since 4.27.0
 * @return void  Sendet JSON-Response und beendet die Ausführung.
 */
function gsh_tp_ajax_import_categories_from_planner(): void {
    check_ajax_referer( 'gsh_tp_save_categories_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Keine Berechtigung.' ), 403 );
        return;
    }

    $sj  = sanitize_key( wp_unslash( $_POST['sj'] ?? '' ) );
    $row = $sj ? gsh_tp_curriculr_repo_get( $sj ) : null;
    if ( ! $row ) {
        wp_send_json_error( array( 'message' => 'Schuljahr nicht gefunden.' ), 404 );
        return;
    }

    $doc  = json_decode( $row['json'], true );
    $cats = is_array( $doc['categories'] ?? null ) ? $doc['categories'] : array();

    wp_send_json_success( array( 'categories' => $cats ) );
}

/**
 * Berechnet ob auf einer gegebenen Hintergrundfarbe schwarze oder weiße
 * Schrift besser lesbar ist.
 *
 * Basiert auf der WCAG 2.1 Relative-Luminanz-Formel mit Schwellenwert 0.179.
 * Gibt '#000000' zurück wenn die Farbe hell genug ist, sonst '#ffffff'.
 * Bei ungültigem Input (kein valider Hex-Code) wird '#ffffff' zurückgegeben.
 *
 * Muss vor gsh_tp_color_derive() definiert sein, da diese Funktion sie aufruft.
 *
 * @since 3.15.1
 * @param  string $hex_color Hex-Farbe mit # (z.B. '#e74c3c' oder '#abc').
 * @return string            '#000000' oder '#ffffff'.
 */
function gsh_tp_contrast_color( string $hex_color ): string {
    $hex = ltrim( $hex_color, '#' );

    // Kurzform (#abc) auf Langform (#aabbcc) expandieren
    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    // Ungültige Farbe → Standard: weiß
    if ( strlen( $hex ) !== 6 || ! ctype_xdigit( $hex ) ) {
        return '#ffffff';
    }

    // RGB-Werte (0–255) in lineare Werte (0–1) umrechnen
    $r = hexdec( substr( $hex, 0, 2 ) ) / 255;
    $g = hexdec( substr( $hex, 2, 2 ) ) / 255;
    $b = hexdec( substr( $hex, 4, 2 ) ) / 255;

    // Gamma-Korrektur (sRGB → linear)
    $r = $r <= 0.03928 ? $r / 12.92 : ( ( $r + 0.055 ) / 1.055 ) ** 2.4;
    $g = $g <= 0.03928 ? $g / 12.92 : ( ( $g + 0.055 ) / 1.055 ) ** 2.4;
    $b = $b <= 0.03928 ? $b / 12.92 : ( ( $b + 0.055 ) / 1.055 ) ** 2.4;

    // Relative Luminanz (WCAG 2.1)
    $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

    // Helle Farben (> 0.179) → schwarze Schrift; dunkle → weiße Schrift
    return $luminance > 0.179 ? '#000000' : '#ffffff';
}

/**
 * Leitet bg-, border- und text-Farbe aus einer einzelnen Hauptfarbe ab.
 *
 * bg: 12 % der Farbe + 88 % Weiß (sehr heller Pastellton).
 * border: die Hauptfarbe selbst.
 * text: Kontrastfarbe (#000000 oder #ffffff) berechnet gegen die helle $bg,
 *       nicht gegen die Originalfarbe – verhindert weißen Text auf hellem Hintergrund.
 *
 * @since 3.15.0
 * @updated 3.15.2 Textfarbe wird jetzt gegen $bg (Pastelton) berechnet via gsh_tp_contrast_color().
 * @param  string $color Hex-Farbe (#rrggbb).
 * @return array{bg: string, border: string, text: string}
 */
function gsh_tp_color_derive( string $color ): array {
    if ( ! preg_match( '/^#[0-9a-fA-F]{6}$/', $color ) ) {
        $color = '#94a3b8';
    }
    $r  = hexdec( substr( $color, 1, 2 ) );
    $g  = hexdec( substr( $color, 3, 2 ) );
    $b  = hexdec( substr( $color, 5, 2 ) );

    // Helle Pastell-Hintergrundfarbe berechnen (88 % Weiß + 12 % Originalfarbe)
    $bg = sprintf( '#%02x%02x%02x',
        min( 255, (int) ( $r * 0.12 + 0.88 * 255 ) ),
        min( 255, (int) ( $g * 0.12 + 0.88 * 255 ) ),
        min( 255, (int) ( $b * 0.12 + 0.88 * 255 ) )
    );

    // Textfarbe gegen die helle Hintergrundfarbe berechnen – nicht gegen die Originalfarbe.
    // Pastelfarben sind immer hell → Ergebnis ist immer '#000000' (schwarze Schrift).
    $tx = gsh_tp_contrast_color( $bg );

    return array( 'bg' => $bg, 'border' => $color, 'text' => $tx );
}

/**
 * Weist einem Termin automatisch Kategorien per Keyword-Matching zu.
 *
 * Läuft NUR wenn der Termin noch keine Kategorien hat (categories ist
 * kein nicht-leeres Array). Durchsucht Titel, Beschreibung, Ort und
 * das rohe iCal-CATEGORIES-Feld. Bei keinem Treffer bleibt categories
 * ein leeres Array – der Termin wird ungefärbt dargestellt.
 *
 * @since 3.15.0
 * @param  array $event Geparster Event-Array.
 * @return array        Event-Array mit befülltem categories-Array.
 */
function gsh_tp_assign_categories_to_event( array $event ): array {
    // Bereits kategorisiert (Array mit Einträgen) → nicht überschreiben
    if ( is_array( $event['categories'] ) && ! empty( $event['categories'] ) ) {
        return $event;
    }

    $categories = gsh_tp_get_categories();

    // Suchtext: Titel, Beschreibung, Ort und CATEGORIES-String (für IServ-Felder)
    $ical_cats  = is_string( $event['categories'] ) ? $event['categories'] : '';
    $search_raw = ( $event['summary']     ?? '' ) . ' '
                . ( $event['description'] ?? '' ) . ' '
                . ( $event['location']    ?? '' ) . ' '
                . $ical_cats;
    $search_text = mb_strtolower( $search_raw, 'UTF-8' );

    $assigned = array();

    foreach ( $categories as $cat ) {
        $keywords = array(
            mb_strtolower( $cat['label'], 'UTF-8' ),
            mb_strtolower( $cat['slug'],  'UTF-8' ),
        );

        // Schulspezifische Stichwörter aus den Kategoriedaten laden (seit 3.16.0 in DB editierbar)
        $extra = $cat['keywords'] ?? [];

        $all_keywords = array_unique( array_merge( $keywords, $extra ) );

        foreach ( $all_keywords as $keyword ) {
            if ( '' === $keyword ) {
                continue;
            }
            if ( str_contains( $search_text, $keyword ) ) {
                $assigned[] = $cat['id'];
                break; // Pro Kategorie nur einmal zuweisen
            }
        }
    }

    // Nur setzen wenn wirklich etwas gefunden – sonst leeres Array (ungefärbt)
    $event['categories'] = array_unique( $assigned );
    return $event;
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
    $cats  = gsh_tp_get_categories();
    $vars  = '';
    $rules = '';
    foreach ( $cats as $c ) {
        $s       = esc_attr( $c['slug'] );
        $color   = $c['color'] ?? '#94a3b8';
        $derived = gsh_tp_color_derive( $color );
        $bg      = esc_attr( $derived['bg'] );
        $bd      = esc_attr( $derived['border'] ); // = color
        $tx      = esc_attr( $derived['text'] );
        $vars   .= "--c-{$s}-bg:{$bg};--c-{$s}-bd:{$bd};--c-{$s}-tx:{$tx};";
        $rules  .= ".gc-{$s}{background:var(--c-{$s}-bg);border-color:var(--c-{$s}-bd);color:var(--c-{$s}-tx)}";
        $rules  .= ".gtp-fb[data-c=\"{$s}\"]{--btn-color:{$bd};border-color:var(--c-{$s}-bd);background:var(--c-{$s}-bg);color:var(--c-{$s}-tx)}";
        $rules  .= ".gtp-popup-cat.gc-{$s}{background:var(--c-{$s}-bg);color:var(--c-{$s}-tx)}";
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

/* ── Admin POST-Handler ── */

/**
 * POST-Handler: Neues Schuljahr-Profil anlegen.
 *
 * @param array $profiles Profil-Array (by reference).
 * @return void
 */
function gsh_tp_handle_new_profile( &$profiles ) {
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

/* ── POST-Handler: Schuljahr-Admin (4.24.0) ── */

/**
 * POST-Handler: Neues Schuljahr anlegen.
 *
 * @since 4.24.0
 * @return void
 */
function gsh_tp_handle_new_schoolyear() {
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_nsy_n'] ?? '' ) ), 'gsh_tp_new_schoolyear' ) ) {
        echo '<div class="notice notice-error"><p>Sicherheitsprüfung fehlgeschlagen.</p></div>'; return;
    }
    $key   = sanitize_key( wp_unslash( $_POST['gsh_tp_new_sy_key'] ?? '' ) );
    $label = sanitize_text_field( wp_unslash( $_POST['gsh_tp_new_sy_label'] ?? '' ) );
    if ( '' === $key ) {
        // Kein Schlüssel angegeben (neues Formular zeigt das Feld nur "Erweitert") — aus Label ableiten.
        $key = 'sj_' . sanitize_key( str_replace( '/', '_', $label ) );
    }
    if ( '' === $key ) { echo '<div class="notice notice-error"><p>Schuljahr-Schlüssel fehlt.</p></div>'; return; }

    $schoolyears = gsh_tp_get_schoolyears();
    foreach ( $schoolyears as $sy ) {
        if ( $sy['key'] === $key ) {
            echo '<div class="notice notice-warning"><p>Schuljahr <strong>' . esc_html( $key ) . '</strong> existiert bereits.</p></div>'; return;
        }
    }
    if ( count( $schoolyears ) >= 5 ) {
        echo '<div class="notice notice-error"><p>Maximal 5 Schuljahre.</p></div>'; return;
    }
    $schoolyears[] = array(
        'key'       => $key,
        'label'     => $label ?: $key,
        'is_active' => false,
        'created'   => current_time( 'Y-m-d' ),
        'shared'    => array( 'quartal_grenzen' => '', 'schuljahr_start' => '', 'cache_duration' => 3600 ),
        'calendars' => array(),
    );
    gsh_tp_save_schoolyears( $schoolyears );
    echo '<div class="notice notice-success"><p>Schuljahr <strong>' . esc_html( $label ?: $key ) . '</strong> angelegt.</p></div>';
}

/**
 * POST-Handler: Schuljahr-Label speichern.
 *
 * @since 4.24.0
 * @return void
 */
function gsh_tp_handle_save_schoolyear() {
    $sy_key = sanitize_key( wp_unslash( $_POST['gsh_tp_ssy_key'] ?? '' ) );
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ 'gsh_tp_ssy_n_' . $sy_key ] ?? '' ) ), 'gsh_tp_save_schoolyear_' . $sy_key ) ) {
        echo '<div class="notice notice-error"><p>Sicherheitsprüfung fehlgeschlagen.</p></div>'; return;
    }
    $label       = sanitize_text_field( wp_unslash( $_POST['gsh_tp_ssy_label'] ?? '' ) );
    $schoolyears = gsh_tp_get_schoolyears();
    $changed     = false;
    foreach ( $schoolyears as &$sy ) {
        if ( $sy['key'] === $sy_key ) { $sy['label'] = $label ?: $sy['key']; $changed = true; break; }
    }
    unset( $sy );
    if ( $changed ) { gsh_tp_save_schoolyears( $schoolyears ); echo '<div class="notice notice-success"><p>Schuljahr gespeichert.</p></div>'; }
}

/**
 * POST-Handler: Geteilte Schuljahr-Einstellungen speichern.
 *
 * @since 4.24.0
 * @return void
 */
function gsh_tp_handle_save_shared() {
    $sy_key = sanitize_key( wp_unslash( $_POST['gsh_tp_ssh_key'] ?? '' ) );
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_ssh_n'] ?? '' ) ), 'gsh_tp_save_shared_' . $sy_key ) ) {
        echo '<div class="notice notice-error"><p>Sicherheitsprüfung fehlgeschlagen.</p></div>'; return;
    }
    $schoolyears = gsh_tp_get_schoolyears();
    foreach ( $schoolyears as &$sy ) {
        if ( $sy['key'] !== $sy_key ) { continue; }
        $sy['shared']['schuljahr_start'] = sanitize_text_field( wp_unslash( $_POST['gsh_tp_ssh_start'] ?? '' ) );
        $sy['shared']['cache_duration']  = max( 300, min( 86400, absint( $_POST['gsh_tp_ssh_cache'] ?? 3600 ) ) );
        $sy['shared']['quartal_grenzen'] = sanitize_textarea_field( wp_unslash( $_POST['gsh_tp_ssh_quartal'] ?? '' ) );
        break;
    }
    unset( $sy );
    gsh_tp_save_schoolyears( $schoolyears );
    echo '<div class="notice notice-success"><p>Einstellungen gespeichert.</p></div>';
}

/**
 * POST-Handler: Externen IServ-Kalender mit einem Schuljahr verbinden.
 *
 * Setzt source='extern' auf dem Haupt-Kalender und speichert die Feed-URL.
 * Solange source='extern' gilt, laesst gsh_tp_curriculr_after_put_v2() diesen
 * Kalender unangetastet — Planner-Uebertragungen ueberschreiben ihn nicht mehr.
 * Leere Eingabe loest die Verbindung und stellt source='planner' wieder her.
 *
 * @since 4.36.0
 * @return void
 */
function gsh_tp_handle_save_extern_feed() {
    $sy_key = sanitize_key( wp_unslash( $_POST['gsh_tp_xf_key'] ?? '' ) );
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_xf_n'] ?? '' ) ), 'gsh_tp_save_extern_feed_' . $sy_key ) ) {
        echo '<div class="notice notice-error"><p>Sicherheitspr&uuml;fung fehlgeschlagen.</p></div>'; return;
    }

    $raw_url     = trim( (string) wp_unslash( $_POST['gsh_tp_xf_url'] ?? '' ) );
    $schoolyears = gsh_tp_get_schoolyears();

    $sy_idx = null;
    foreach ( $schoolyears as $i => $sy ) {
        if ( $sy['key'] === $sy_key ) { $sy_idx = $i; break; }
    }
    if ( null === $sy_idx ) {
        echo '<div class="notice notice-error"><p>Schuljahr nicht gefunden.</p></div>'; return;
    }

    // Leeres Feld = Verbindung loesen.
    if ( '' === $raw_url ) {
        $touched = false;
        foreach ( $schoolyears[ $sy_idx ]['calendars'] as &$cal ) {
            if ( null === $cal['group'] && gsh_tp_cal_is_extern( $cal ) ) {
                $cal['source']   = 'planner';
                $cal['ical_url'] = '';
                // Wieder wie jeder andere Planner-Hauptkalender behandeln, sonst bleibt
                // z.B. die Abonnieren-Fusszeile im Frontend dauerhaft verborgen (gated auf 'managed').
                $cal['managed']  = true;
                $touched         = true;
            }
        }
        unset( $cal );
        if ( $touched ) {
            gsh_tp_save_schoolyears( $schoolyears );
            echo '<div class="notice notice-success"><p>Verbindung zum IServ-Kalender getrennt. '
               . 'Das Schuljahr wird jetzt wieder aus dem Planer bef&uuml;llt.</p></div>';
        }
        return;
    }

    $check = gsh_tp_validate_ical_url( $raw_url );
    if ( ! $check['valid'] ) {
        echo '<div class="notice notice-error"><p>Die Adresse wurde nicht &uuml;bernommen: <strong>'
           . esc_html( $check['error'] ) . '</strong>. In IServ findest du sie unter '
           . 'Kalender &rarr; Verwaltung &rarr; Freigabe als Link, der mit <code>https://</code> beginnt.</p></div>';
        return;
    }

    $found = false;
    foreach ( $schoolyears[ $sy_idx ]['calendars'] as &$cal ) {
        if ( null === $cal['group'] ) {
            $cal['source']   = 'extern';
            $cal['ical_url'] = $check['url'];
            $cal['managed']  = false;
            $found           = true;
            break;
        }
    }
    unset( $cal );

    if ( ! $found ) {
        array_unshift( $schoolyears[ $sy_idx ]['calendars'], array(
            'group'    => null,
            'label'    => $schoolyears[ $sy_idx ]['label'] . ' &middot; Alle Termine',
            'ical_url' => $check['url'],
            'is_draft' => false,
            'managed'  => false,
            'orphaned' => false,
            'source'   => 'extern',
        ) );
    }

    gsh_tp_save_schoolyears( $schoolyears );

    // Direkt abrufen, damit sofort sichtbar ist ob die Adresse stimmt.
    $pid = sanitize_key( gsh_tp_calendar_id( $sy_key, null ) );
    delete_transient( gsh_tp_ck( 'gsh_tp_fresh_', $pid ) );
    if ( gsh_tp_do_refresh( $pid ) ) {
        $count = count( gsh_tp_parse_events( gsh_tp_fetch_ical( $pid ) ) );
        echo '<div class="notice notice-success"><p>' . gsh_tp_icon( 'check' ) . ' Kalender verbunden &ndash; <strong>'
           . (int) $count . '</strong> Termine &uuml;bernommen.</p></div>';
    } else {
        echo '<div class="notice notice-warning"><p>' . gsh_tp_icon( 'alert-triangle' )
           . ' Adresse gespeichert, aber der Abruf hat nicht geklappt. Das liegt meist daran, dass der Kalender '
           . 'in IServ noch nicht freigegeben ist oder die Adresse unvollst&auml;ndig kopiert wurde. '
           . 'Details stehen im Tab &bdquo;System &amp; Logs&ldquo;.</p></div>';
    }
}

/**
 * POST-Handler: Schuljahr als aktiv setzen.
 *
 * @since 4.24.0
 * @return void
 */
function gsh_tp_handle_activate_schoolyear() {
    $act_key = sanitize_key( wp_unslash( $_POST['gsh_tp_asy_key'] ?? '' ) );
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_asy_n'] ?? '' ) ), 'gsh_tp_activate_sy_' . $act_key ) ) {
        echo '<div class="notice notice-error"><p>Sicherheitsprüfung fehlgeschlagen.</p></div>'; return;
    }
    $schoolyears = gsh_tp_get_schoolyears();
    foreach ( $schoolyears as &$sy ) { $sy['is_active'] = ( $sy['key'] === $act_key ); }
    unset( $sy );
    gsh_tp_save_schoolyears( $schoolyears );
    echo '<div class="notice notice-success"><p>Schuljahr als aktiv gesetzt.</p></div>';
}

/**
 * POST-Handler: Nicht-Haupt-Kalender aus einem Schuljahr löschen.
 *
 * @since 4.24.0
 * @return void
 */
function gsh_tp_handle_delete_calendar() {
    $sy_key = sanitize_key( wp_unslash( $_POST['gsh_tp_dc_sy'] ?? '' ) );
    $group  = sanitize_text_field( wp_unslash( $_POST['gsh_tp_dc_cal'] ?? '' ) );
    $cal_id = gsh_tp_calendar_id( $sy_key, $group );
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_dc_n'] ?? '' ) ), 'gsh_tp_del_cal_' . sanitize_key( $cal_id ) ) ) {
        echo '<div class="notice notice-error"><p>Sicherheitsprüfung fehlgeschlagen.</p></div>'; return;
    }
    if ( '' === $group ) { echo '<div class="notice notice-error"><p>Haupt-Kalender kann nicht gelöscht werden.</p></div>'; return; }
    $schoolyears = gsh_tp_get_schoolyears();
    foreach ( $schoolyears as &$sy ) {
        if ( $sy['key'] !== $sy_key ) { continue; }
        $sy['calendars'] = array_values( array_filter( $sy['calendars'], function ( $c ) use ( $group ) { return $c['group'] !== $group; } ) );
        break;
    }
    unset( $sy );
    gsh_tp_save_schoolyears( $schoolyears );
    echo '<div class="notice notice-success"><p>Kalender <strong>' . esc_html( $group ) . '</strong> gelöscht.</p></div>';
}

/**
 * POST-Handler: Status (Entwurf/Beschlossen) eines Kalenders umschalten.
 *
 * @since 4.26.1
 * @return void
 */
function gsh_tp_handle_toggle_draft() {
    $sy_key = sanitize_key( wp_unslash( $_POST['gsh_tp_td_sy'] ?? '' ) );
    $group  = sanitize_text_field( wp_unslash( $_POST['gsh_tp_td_cal'] ?? '' ) );
    $cal_id = gsh_tp_calendar_id( $sy_key, '' === $group ? null : $group );
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_td_n'] ?? '' ) ), 'gsh_tp_toggle_draft_' . sanitize_key( $cal_id ) ) ) {
        echo '<div class="notice notice-error"><p>Sicherheitsprüfung fehlgeschlagen.</p></div>'; return;
    }
    $schoolyears = gsh_tp_get_schoolyears();
    $changed     = false;
    foreach ( $schoolyears as &$sy ) {
        if ( $sy['key'] !== $sy_key ) { continue; }
        foreach ( $sy['calendars'] as &$cal ) {
            $this_group = null === $cal['group'] ? '' : $cal['group'];
            if ( $this_group !== $group ) { continue; }
            $cal['is_draft'] = empty( $cal['is_draft'] );
            $changed = true;
            break;
        }
        unset( $cal );
        break;
    }
    unset( $sy );
    if ( $changed ) {
        gsh_tp_save_schoolyears( $schoolyears );
        echo '<div class="notice notice-success"><p>Status aktualisiert.</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Kalender nicht gefunden.</p></div>';
    }
}

/**
 * POST-Handler: Schuljahr löschen (inkl. DB-Daten und ICS-Cache).
 *
 * Verweigert das Löschen des aktiven Schuljahres und des letzten verbleibenden.
 *
 * @since 4.25.0
 * @return void
 */
function gsh_tp_handle_delete_schoolyear() {
    global $wpdb;
    $sy_key = sanitize_key( wp_unslash( $_POST['gsh_tp_dsy_key'] ?? '' ) );
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_dsy_n'] ?? '' ) ), 'gsh_tp_del_sy_' . $sy_key ) ) {
        echo '<div class="notice notice-error"><p>Sicherheitsprüfung fehlgeschlagen.</p></div>'; return;
    }
    if ( '' === $sy_key ) {
        echo '<div class="notice notice-error"><p>Schuljahr-Schlüssel fehlt.</p></div>'; return;
    }

    $schoolyears = gsh_tp_get_schoolyears();
    $target      = null;
    foreach ( $schoolyears as $sy ) {
        if ( $sy['key'] === $sy_key ) { $target = $sy; break; }
    }
    if ( null === $target ) {
        echo '<div class="notice notice-error"><p>Schuljahr nicht gefunden.</p></div>'; return;
    }
    if ( ! empty( $target['is_active'] ) ) {
        echo '<div class="notice notice-error"><p>Das aktive Schuljahr kann nicht gelöscht werden.</p></div>'; return;
    }
    if ( count( $schoolyears ) <= 1 ) {
        echo '<div class="notice notice-error"><p>Das letzte Schuljahr kann nicht gelöscht werden.</p></div>'; return;
    }

    // ICS-Cache und Transients für alle Kalender dieses Schuljahres entfernen.
    foreach ( $target['calendars'] as $cal ) {
        $cal_id  = gsh_tp_calendar_id( $sy_key, $cal['group'] );
        $pid_key = sanitize_key( $cal_id );
        delete_option( gsh_tp_ck( 'gsh_tp_ical_', $pid_key ) );
        delete_transient( gsh_tp_ck( 'gsh_tp_fresh_', $pid_key ) );
        delete_transient( gsh_tp_ck( 'gsh_tp_chg_', $pid_key ) );
    }

    // Dokument und alle Revisionen aus DB löschen.
    $docs_table = gsh_tp_curriculr_table();
    $rev_table  = gsh_tp_curriculr_revisions_table();
    $wpdb->delete( $docs_table, array( 'schoolyear' => $sy_key ), array( '%s' ) );
    $wpdb->delete( $rev_table,  array( 'schoolyear' => $sy_key ), array( '%s' ) );

    // Schuljahr aus der Option entfernen.
    $schoolyears = array_values( array_filter( $schoolyears, function ( $s ) use ( $sy_key ) {
        return $s['key'] !== $sy_key;
    } ) );
    gsh_tp_save_schoolyears( $schoolyears );

    wp_safe_redirect( admin_url( 'options-general.php?page=gsh-terminplan' ) );
    exit;
}

/**
 * POST-Handler: Profil speichern.
 *
 * @param array $profiles Profil-Array (by reference).
 * @return void
 */
function gsh_tp_handle_save_profile( &$profiles ) {
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

/**
 * POST-Handler: Profil aktivieren.
 *
 * @param array $profiles Profil-Array (by reference).
 * @return void
 */
function gsh_tp_handle_activate_profile( &$profiles ) {
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

/**
 * POST-Handler: Entwurf-Token speichern.
 *
 * @param array $profiles Profil-Array (by reference, unused but kept for uniform signature).
 * @return void
 */
function gsh_tp_handle_save_draft( &$profiles ) {
    if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_sd_n'] ?? '' ) ), 'gsh_tp_save_draft' ) ) {
        update_option( 'gsh_tp_draft_kiosk_token', sanitize_text_field( wp_unslash( $_POST['gsh_tp_draft_kiosk_token'] ?? '' ) ) );
        echo '<div class="notice notice-success"><p>' . gsh_tp_icon( 'check' ) . ' Entwurf-Token gespeichert.</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Sicherheitspr&uuml;fung fehlgeschlagen.</p></div>';
    }
}

/**
 * POST-Handler: Kiosk-Einstellungen speichern.
 *
 * @param array $profiles Profil-Array (by reference, unused but kept for uniform signature).
 * @return void
 */
function gsh_tp_handle_save_kiosk( &$profiles ) {
    if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_sk_n'] ?? '' ) ), 'gsh_tp_save_kiosk' ) ) {
        update_option( 'gsh_tp_kiosk_token',    sanitize_text_field( wp_unslash( $_POST['gsh_tp_kiosk_token'] ?? '' ) ) );
        update_option( 'gsh_tp_iserv_domain',   esc_url_raw( wp_unslash( $_POST['gsh_tp_iserv_domain'] ?? '' ) ) );
        update_option( 'gsh_tp_feedback_email', sanitize_email( wp_unslash( $_POST['gsh_tp_feedback_email'] ?? '' ) ) );
        update_option( 'gsh_tp_school_name', sanitize_text_field( wp_unslash( $_POST['gsh_tp_school_name'] ?? '' ) ) );
        echo '<div class="notice notice-success"><p>' . gsh_tp_icon( 'check' ) . ' Kiosk-Einstellungen gespeichert.</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Sicherheitspr&uuml;fung fehlgeschlagen.</p></div>';
    }
}

/**
 * POST-Handler: Curriculr-Planner-Sync-Einstellungen speichern.
 *
 * @param array $profiles Profil-Array (by reference, unused but kept for uniform signature).
 * @return void
 */
function gsh_tp_handle_save_curriculr( &$profiles ) {
    if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_cur_n'] ?? '' ) ), 'gsh_tp_save_curriculr' ) ) {
        update_option( 'gsh_tp_curriculr_origin', esc_url_raw( wp_unslash( $_POST['gsh_tp_curriculr_origin'] ?? '' ) ) );
        $sj_key     = sanitize_key( wp_unslash( $_POST['gsh_tp_curriculr_sj_key']     ?? '' ) );
        $profile_id = sanitize_key( wp_unslash( $_POST['gsh_tp_curriculr_profile_id'] ?? '' ) );
        if ( $sj_key && $profile_id ) {
            update_option( 'gsh_tp_curriculr_profile_map', array( $sj_key => $profile_id ), false );
        } elseif ( ! $sj_key && ! $profile_id ) {
            update_option( 'gsh_tp_curriculr_profile_map', array(), false );
        }
        echo '<div class="notice notice-success"><p>' . gsh_tp_icon( 'check' ) . ' Curriculr-Sync-Einstellungen gespeichert.</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Sicherheitspr&uuml;fung fehlgeschlagen.</p></div>';
    }
}

/**
 * POST-Handler: Profil löschen.
 *
 * @param array $profiles Profil-Array (by reference).
 * @return void
 */
function gsh_tp_handle_delete_profile( &$profiles ) {
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

/**
 * POST-Handler: Kalender synchronisieren.
 *
 * @param array $profiles Profil-Array (by reference, read-only).
 * @return void
 */
function gsh_tp_handle_sync( &$profiles ) {
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

/**
 * POST-Handler: Cache leeren.
 *
 * @param array $profiles Profil-Array (by reference, read-only).
 * @return void
 */
function gsh_tp_handle_clear_cache( &$profiles ) {
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

/**
 * POST-Handler: Feedback-Log leeren.
 *
 * @param array $profiles Profil-Array (by reference, unused but kept for uniform signature).
 * @return void
 */
function gsh_tp_handle_clear_feedback_log( &$profiles ) {
    if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'gsh_tp_clear_feedback_log' ) ) {
        delete_option( 'gsh_tp_feedback_log' );
        delete_option( 'gsh_tp_mail_fail_count' );
        // Bugfix v3.13.1: gleiche Ursache – inline statt Redirect
        echo '<div class="notice notice-success"><p>' . gsh_tp_icon( 'check' ) . ' Feedback-Log geleert.</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Sicherheitspr&uuml;fung fehlgeschlagen.</p></div>';
    }
}

/**
 * POST-Handler: Alte Sync-Logs löschen.
 *
 * @param array $profiles Profil-Array (by reference, unused but kept for uniform signature).
 * @return void
 */
function gsh_tp_handle_clear_logs( &$profiles ) {
    if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_cl_n'] ?? '' ) ), 'gsh_tp_clear_logs' ) ) {
        $removed = gsh_tp_clear_old_logs( 30 );
        echo '<div class="notice notice-success"><p>' . gsh_tp_icon( 'check' ) . ' ' . (int) $removed
           . ' veraltete Log-Eintr&auml;ge gel&ouml;scht.</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Sicherheitspr&uuml;fung fehlgeschlagen.</p></div>';
    }
}

/**
 * Dispatcher: leitet POST-Aktionen der Einstellungsseite an benannte Handler weiter.
 *
 * @param array $profiles Profil-Array (by reference).
 * @return void
 */
function gsh_tp_settings_handle_post( &$profiles ) {
    $map = array(
        'gsh_tp_new_profile'        => 'gsh_tp_handle_new_profile',
        'gsh_tp_save_profile'       => 'gsh_tp_handle_save_profile',
        'gsh_tp_activate_profile'   => 'gsh_tp_handle_activate_profile',
        'gsh_tp_save_draft'         => 'gsh_tp_handle_save_draft',
        'gsh_tp_save_kiosk'         => 'gsh_tp_handle_save_kiosk',
        'gsh_tp_save_curriculr'     => 'gsh_tp_handle_save_curriculr',
        'gsh_tp_delete_profile'     => 'gsh_tp_handle_delete_profile',
        'gsh_tp_sync'               => 'gsh_tp_handle_sync',
        'gsh_tp_cc'                 => 'gsh_tp_handle_clear_cache',
        'gsh_tp_clear_feedback_log' => 'gsh_tp_handle_clear_feedback_log',
        'gsh_tp_clear_logs'         => 'gsh_tp_handle_clear_logs',
    );
    foreach ( $map as $key => $fn ) {
        if ( isset( $_POST[ $key ] ) ) {
            $fn( $profiles );
        }
    }
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

    gsh_tp_settings_handle_post( $profiles );

    // POST: new schoolyear admin actions (4.24.0)
    if ( isset( $_POST['gsh_tp_new_schoolyear'] ) )      { gsh_tp_handle_new_schoolyear(); }
    if ( isset( $_POST['gsh_tp_save_schoolyear'] ) )     { gsh_tp_handle_save_schoolyear(); }
    if ( isset( $_POST['gsh_tp_save_shared'] ) )         { gsh_tp_handle_save_shared(); }
    if ( isset( $_POST['gsh_tp_save_extern_feed'] ) )    { gsh_tp_handle_save_extern_feed(); }
    if ( isset( $_POST['gsh_tp_activate_schoolyear'] ) ) { gsh_tp_handle_activate_schoolyear(); }
    if ( isset( $_POST['gsh_tp_del_cal'] ) )             { gsh_tp_handle_delete_calendar(); }
    if ( isset( $_POST['gsh_tp_toggle_draft'] ) )        { gsh_tp_handle_toggle_draft(); }
    if ( isset( $_POST['gsh_tp_del_sy'] ) )              { gsh_tp_handle_delete_schoolyear(); }
    if ( isset( $_POST['gsh_tp_doc_import'] ) )          { gsh_tp_curriculr_handle_doc_import(); }

    // ── Tabs (fest, funktional) ──
    $tabs = array(
        '_profile'    => 'Schuljahre',
        '_kategorien' => 'Kategorien',
        '_system'     => 'System &amp; Logs',
        // '_sync' removed — Curriculr-Sync 1:1 mapping superseded by SPA auto-provisioning
    );

    // Aktiver Tab (Whitelist gegen $tabs)
    $active_tab = sanitize_key( $_GET['tab'] ?? '' );
    // Redirect legacy _kiosk bookmarks — Kiosk settings are now in System tab.
    if ( '_kiosk' === $active_tab ) {
        wp_redirect( admin_url( 'options-general.php?page=gsh-terminplan&tab=_system' ) );
        exit;
    }
    if ( ! array_key_exists( $active_tab, $tabs ) ) {
        $active_tab = '_profile';
    }

    // Gewähltes Profil für den Profil-Tab (Default: aktives Profil)
    $sel_profile = sanitize_key( $_GET['profile'] ?? '' );
    $profile_ids = array_column( $profiles, 'id' );
    if ( ! in_array( $sel_profile, $profile_ids, true ) ) {
        $sel_profile = gsh_tp_active_profile_id();
    }
    ?>
    <div class="wrap">
        <h1>Schul-Terminplan &ndash; Einstellungen
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
                    'UX'       => '#00467D',
                    'BUGFIX'   => '#dc2626',
                    'SECURITY' => '#d97706',
                    'SEC'      => '#d97706',
                    'DESIGN'   => '#7C3AED',
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
        </nav>

        <?php
        if ( '_kategorien' === $active_tab ) {
            gsh_tp_render_kategorien_tab();
        } elseif ( '_system' === $active_tab ) {
            gsh_tp_render_system_tab();
        } else { // _profile
            gsh_tp_render_profile_tab_v2();
        }
        ?>
    </div>
    <?php
}

/**
 * Rendert den Profil-Chooser (Dropdown + "+ Neues Schuljahr"-Button).
 *
 * @since 4.15.0
 * @param  array  $profiles    Alle Schuljahr-Profile.
 * @param  string $sel_profile Aktuell gewählte Profil-ID.
 * @return void
 */
function gsh_tp_render_profile_chooser( $profiles, $sel_profile ) {
    ?>
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap">
        <form method="get" style="margin:0;display:flex;align-items:center;gap:6px">
            <input type="hidden" name="page" value="gsh-terminplan" />
            <input type="hidden" name="tab" value="_profile" />
            <label for="gsh_tp_profile_sel" style="font-weight:600">Schuljahr:</label>
            <select id="gsh_tp_profile_sel" name="profile" onchange="this.form.submit()">
                <?php foreach ( $profiles as $p ) :
                    $suffix = ! empty( $p['is_draft'] ) ? ' (Entwurf)' : ( ! empty( $p['is_active'] ) ? ' (aktiv)' : '' );
                ?>
                    <option value="<?php echo esc_attr( $p['id'] ); ?>" <?php selected( $sel_profile, $p['id'] ); ?>>
                        <?php echo esc_html( $p['label'] . $suffix ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php if ( count( $profiles ) < 5 ) : ?>
            <form method="post" style="margin:0;display:inline">
                <?php wp_nonce_field( 'gsh_tp_new_profile', 'gsh_tp_np_n' ); ?>
                <button type="submit" name="gsh_tp_new_profile" value="1" class="button"
                        style="color:#27ae60;border-color:#27ae60"
                        onclick="return confirm('Neues Schuljahr-Profil anlegen?')">
                    + Neues Schuljahr
                </button>
            </form>
        <?php endif; ?>
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
        echo '<div style="background:#fef9c3;border:1px solid #eab308;padding:10px 16px;'
           . 'margin-bottom:16px;border-radius:6px">'
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
 * Rendert den Kategorien-Tab (Kategorien verwalten). – v3.15.0 Neuaufbau
 *
 * Vereinfachtes Datenmodell: id, label, color, slug.
 * Kategorien werden per AJAX gespeichert (gsh_tp_save_categories_nonce).
 * Keyword-Matching ist hardcoded in gsh_tp_assign_categories_to_event().
 *
 * @since 3.4.0 (komplett neu seit 3.15.0)
 * @return void
 */
function gsh_tp_render_kategorien_tab() {
    $existing_cats = gsh_tp_get_categories();
    $nonce_value   = wp_create_nonce( 'gsh_tp_save_categories_nonce' );
    ?>
    <p class="description" style="margin-bottom:1rem">
        Legen Sie hier die Terminplan-Kategorien fest. Jede Kategorie hat einen
        <strong>Anzeigenamen</strong> und eine <strong>Hauptfarbe</strong>.
        Die Hintergrundfarbe und Textfarbe werden automatisch aus der Hauptfarbe abgeleitet.<br>
        Die <strong>Stichwörter</strong> für das automatische Matching werden pro Kategorie
        angezeigt und können direkt bearbeitet werden.
    </p>

    <input type="hidden" id="gsh-cat-nonce" value="<?php echo esc_attr( $nonce_value ); ?>">

    <?php
    global $wpdb;
    $docs_table   = gsh_tp_curriculr_table();
    $planner_docs = $wpdb->get_results( "SELECT schoolyear, json FROM $docs_table ORDER BY updated_at DESC", ARRAY_A );
    $planner_docs = is_array( $planner_docs ) ? $planner_docs : array();

    $active_sj = '';
    foreach ( gsh_tp_get_schoolyears() as $sy ) {
        if ( ! empty( $sy['is_active'] ) ) { $active_sj = $sy['key']; break; }
    }
    ?>
    <?php if ( ! empty( $planner_docs ) ) : ?>
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;padding:10px 12px;background:#f6f7f7;border:1px solid #dcdcde;border-radius:4px;flex-wrap:wrap">
        <label for="gsh-cat-import-sj" style="font-weight:600">Aus Planner übernehmen:</label>
        <select id="gsh-cat-import-sj">
            <?php foreach ( $planner_docs as $d ) :
                $doc_arr = json_decode( $d['json'], true );
                $name    = ( is_array( $doc_arr ) && ! empty( $doc_arr['meta']['name'] ) ) ? $doc_arr['meta']['name'] : $d['schoolyear'];
            ?>
                <option value="<?php echo esc_attr( $d['schoolyear'] ); ?>" <?php selected( $active_sj, $d['schoolyear'] ); ?>>
                    <?php echo esc_html( $name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="button" id="gsh-cat-import-btn">Aus Planner übernehmen</button>
        <span id="gsh-cat-import-status" style="font-size:12px;color:#646970"></span>
    </div>
    <?php else : ?>
    <p class="description" style="margin-bottom:14px">Keine Planner-Schuljahre synchronisiert – „Aus Planner übernehmen" ist noch nicht verfügbar.</p>
    <?php endif; ?>

    <div id="gsh-cat-editor">
    <table class="widefat" id="gsh-cat-table" style="table-layout:fixed;margin-bottom:8px">
        <thead>
            <tr>
                <th style="width:180px">Anzeigename</th>
                <th style="width:80px">Farbe</th>
                <th style="width:180px">Vorschau</th>
                <th style="width:120px">ID / Slug</th>
                <th style="width:34px"></th>
            </tr>
        </thead>
        <tbody id="gsh-cat-tbody">
        <?php foreach ( $existing_cats as $cat ) :
            $derived = gsh_tp_color_derive( $cat['color'] ?? '#94a3b8' );
        ?>
            <tr class="gsh-cat-row">
                <td>
                    <input type="hidden" class="gsh-cat-id"
                           value="<?php echo esc_attr( $cat['id'] ?? $cat['slug'] ?? '' ); ?>" />
                    <input type="hidden" class="gsh-cat-slug"
                           value="<?php echo esc_attr( $cat['slug'] ); ?>" />
                    <input type="text" class="gsh-cat-label"
                           value="<?php echo esc_attr( $cat['label'] ); ?>"
                           style="width:100%" placeholder="Anzeigename" />
                    <span class="gsh-cat-slug-preview">Slug: <code class="gsh-cat-slug-display"><?php echo esc_html( $cat['slug'] ); ?></code></span>
                    <details class="gsh-cat-keywords-wrap">
                        <summary>Stichwörter <span class="gsh-cat-kw-count">(<?php echo count( $cat['keywords'] ?? [] ); ?> Stichwörter)</span></summary>
                        <textarea class="gsh-cat-keywords" rows="3" placeholder="klasse 5, jg 5, 5a, 5b, ..."><?php echo esc_html( implode( ', ', $cat['keywords'] ?? [] ) ); ?></textarea>
                        <p class="gsh-cat-keywords-hint">Kommagetrennte Begriffe. Groß-/Kleinschreibung wird ignoriert. Treffer in Titel oder Beschreibung ordnen diesen Kategorie zu.</p>
                    </details>
                </td>
                <td>
                    <input type="color" class="gsh-cat-color"
                           value="<?php echo esc_attr( $cat['color'] ?? '#94a3b8' ); ?>"
                           style="width:56px;height:36px;padding:2px;cursor:pointer" />
                </td>
                <td>
                    <span class="gsh-cat-preview" style="display:inline-flex;align-items:center;
                        padding:3px 10px;border-radius:4px;font-size:12px;
                        border-left:3px solid <?php echo esc_attr( $derived['border'] ); ?>;
                        background:<?php echo esc_attr( $derived['bg'] ); ?>;
                        color:<?php echo esc_attr( $derived['text'] ); ?>">
                        <?php echo esc_html( $cat['label'] ); ?>
                    </span>
                </td>
                <td style="font-size:11px;color:#64748b">
                    <?php echo esc_html( $cat['id'] ?? $cat['slug'] ?? '–' ); ?>
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

    <div style="margin-top:12px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <button type="button" class="button button-primary" id="gsh-cat-save">Kategorien speichern</button>
        <div id="gsh-cat-status"></div>
    </div>

    <script>
    (function () {
        'use strict';

        // Kategorien-State aus dem initialen DOM aufbauen
        var tbody         = document.getElementById('gsh-cat-tbody');
        var addBtn        = document.getElementById('gsh-cat-add');
        var saveBtn       = document.getElementById('gsh-cat-save');
        var statusEl      = document.getElementById('gsh-cat-status');
        var nonce         = (document.getElementById('gsh-cat-nonce') || {}).value || '';
        var importBtn     = document.getElementById('gsh-cat-import-btn');
        var importSelect  = document.getElementById('gsh-cat-import-sj');
        var importStatus  = document.getElementById('gsh-cat-import-status');

        // Live-Vorschau nach Farbänderung aktualisieren
        function updatePreview(row) {
            var color = (row.querySelector('.gsh-cat-color') || {}).value || '#94a3b8';
            var label = (row.querySelector('.gsh-cat-label') || {}).value || 'Beispiel';
            var pre   = row.querySelector('.gsh-cat-preview');
            if (!pre) return;
            // Helligkeit schätzen: einfache Graustufenformel
            var r = parseInt(color.slice(1, 3), 16);
            var g = parseInt(color.slice(3, 5), 16);
            var b = parseInt(color.slice(5, 7), 16);
            var lum = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
            // bg: 12 % Farbe + 88 % Weiß
            function blend(c) { return Math.min(255, Math.round(c * 0.12 + 0.88 * 255)); }
            var bg = 'rgb(' + blend(r) + ',' + blend(g) + ',' + blend(b) + ')';
            var tx = lum > 0.5 ? '#1e293b' : '#f1f5f9';
            pre.style.background      = bg;
            pre.style.borderLeftColor = color;
            pre.style.color           = tx;
            pre.textContent           = label || 'Vorschau';
        }

        // Slug aus Label-Text generieren (Umlaute → ae/oe/ue/ss, Sonderzeichen → Bindestrich)
        function makeSlug(label) {
            return label
                .toLowerCase()
                .replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss')
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-|-$/g, '');
        }

        // Alle Kategorien aus dem DOM sammeln
        function collectFromDOM() {
            var cats = [];
            tbody.querySelectorAll('.gsh-cat-row').forEach(function (row) {
                var id    = (row.querySelector('.gsh-cat-id')    || {}).value || '';
                var slug  = (row.querySelector('.gsh-cat-slug')  || {}).value || '';
                var label = (row.querySelector('.gsh-cat-label') || {}).value || '';
                var color = (row.querySelector('.gsh-cat-color') || {}).value || '#94a3b8';
                var kwRaw = (row.querySelector('.gsh-cat-keywords') || {}).value || '';
                if (!label) return; // Leere Labels überspringen
                // Slug aus Label generieren wenn noch keiner vorhanden
                if (!slug) { slug = makeSlug(label); }
                if (!id)   { id   = slug; }
                var kwArr = kwRaw.split(',').map(function(s){ return s.trim(); }).filter(Boolean);
                cats.push({ id: id, label: label, color: color, slug: slug, keywords: kwArr });
            });
            return cats;
        }

        // Status-Meldung anzeigen
        function showStatus(ok, msg) {
            statusEl.textContent   = msg;
            statusEl.className     = ok ? 'gsh-cat-status-ok' : 'gsh-cat-status-err';
            statusEl.style.display = 'block';
            if (ok) { setTimeout(function () { statusEl.style.display = 'none'; }, 4000); }
        }

        // HTML für neue Zeile generieren
        function buildRowHtml(cat) {
            var svgX = '<?php echo addslashes( gsh_tp_icon( 'x', '0.9em' ) ); ?>';
            function ea(s) {
                return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            }
            var color = cat.color || '#94a3b8';
            var kwStr = Array.isArray(cat.keywords) ? cat.keywords.join(', ') : '';
            var kwCnt = Array.isArray(cat.keywords) ? cat.keywords.length : 0;
            return '<td>'
                + '<input type="hidden" class="gsh-cat-id"    value="' + ea(cat.id    || '') + '">'
                + '<input type="hidden" class="gsh-cat-slug"  value="' + ea(cat.slug  || '') + '">'
                + '<input type="text"   class="gsh-cat-label" value="' + ea(cat.label || '') + '" style="width:100%" placeholder="Anzeigename">'
                + '<span class="gsh-cat-slug-preview">Slug: <code class="gsh-cat-slug-display">' + ea(cat.slug || '') + '</code></span>'
                + '<details class="gsh-cat-keywords-wrap">'
                + '<summary>Stichwörter <span class="gsh-cat-kw-count">(' + kwCnt + ' Stichwörter)</span></summary>'
                + '<textarea class="gsh-cat-keywords" rows="3" placeholder="klasse 5, jg 5, 5a, 5b, ...">' + ea(kwStr) + '</textarea>'
                + '<p class="gsh-cat-keywords-hint">Kommagetrennte Begriffe. Gro\u00df-/Kleinschreibung wird ignoriert.</p>'
                + '</details>'
                + '</td>'
                + '<td><input type="color" class="gsh-cat-color" value="' + ea(color) + '" style="width:56px;height:36px;padding:2px;cursor:pointer"></td>'
                + '<td><span class="gsh-cat-preview" style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:4px;font-size:12px;border-left:3px solid ' + ea(color) + ';background:#f0f0f0;color:#333">' + ea(cat.label || 'Vorschau') + '</span></td>'
                + '<td style="font-size:11px;color:#64748b">' + ea(cat.id || '(neu)') + '</td>'
                + '<td><button type="button" class="button gsh-cat-del" title="Kategorie l\u00f6schen">' + svgX + '</button></td>';
        }

        // ── Event-Listener ──────────────────────────────────────────────

        // Live-Vorschau, Slug-Synchronisation und Keyword-Zähler bei Eingabe
        if (tbody) {
            tbody.addEventListener('input', function (e) {
                var row = e.target.closest('.gsh-cat-row');
                if (!row) return;
                // Slug synchronisieren wenn Label geändert
                if (e.target.classList.contains('gsh-cat-label')) {
                    var newSlug     = makeSlug(e.target.value);
                    var slugField   = row.querySelector('.gsh-cat-slug');
                    var slugDisplay = row.querySelector('.gsh-cat-slug-display');
                    if (slugField)   slugField.value        = newSlug;
                    if (slugDisplay) slugDisplay.textContent = newSlug;
                }
                // Keyword-Anzahl live aktualisieren
                if (e.target.classList.contains('gsh-cat-keywords')) {
                    var kwCnt = row.querySelector('.gsh-cat-kw-count');
                    if (kwCnt) {
                        var kc = e.target.value.split(',').map(function(s){ return s.trim(); }).filter(Boolean).length;
                        kwCnt.textContent = '(' + kc + ' Stichwörter)';
                    }
                }
                updatePreview(row);
            });
            // Löschen-Button
            tbody.addEventListener('click', function (e) {
                var delBtn = e.target.closest('.gsh-cat-del');
                if (!delBtn) return;
                var rows = tbody.querySelectorAll('.gsh-cat-row');
                if (rows.length <= 1) { alert('Mindestens eine Kategorie muss vorhanden sein.'); return; }
                if (!confirm('Kategorie wirklich l\u00f6schen?')) return;
                delBtn.closest('.gsh-cat-row').remove();
            });
        }

        // Neue Kategorie hinzufügen
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                var newCat = { id: 'neu-' + Date.now(), label: '', color: '#94a3b8', slug: '', keywords: [] };
                var row = document.createElement('tr');
                row.className = 'gsh-cat-row';
                row.innerHTML = buildRowHtml(newCat);
                tbody.appendChild(row);
            });
        }

        // Speichern via AJAX (fetch, ES2020)
        if (saveBtn) {
            saveBtn.addEventListener('click', async function () {
                var data    = collectFromDOM();
                var origTxt = saveBtn.textContent;
                saveBtn.disabled    = true;
                saveBtn.textContent = 'Wird gespeichert\u2026';
                statusEl.style.display = 'none';

                try {
                    var response = await fetch(
                        (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php'),
                        {
                            method:  'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body:    new URLSearchParams({
                                action:     'gsh_tp_save_categories',
                                nonce:      nonce,
                                categories: JSON.stringify(data),
                            }).toString()
                        }
                    );
                    var result = await response.json();

                    if (result.success) {
                        // Server-bestätigten Stand zurückspielen (IDs/Slugs ggf. bereinigt)
                        if (result.data && Array.isArray(result.data.categories)) {
                            var rows = tbody.querySelectorAll('.gsh-cat-row');
                            result.data.categories.forEach(function (cat, i) {
                                if (!rows[i]) return;
                                var idInput     = rows[i].querySelector('.gsh-cat-id');
                                var slugInput   = rows[i].querySelector('.gsh-cat-slug');
                                var slugDisplay = rows[i].querySelector('.gsh-cat-slug-display');
                                if (idInput)     idInput.value        = cat.id   || '';
                                if (slugInput)   slugInput.value       = cat.slug || '';
                                if (slugDisplay) slugDisplay.textContent = cat.slug || '';
                            });
                        }
                        showStatus(true, '\u2713 Kategorien gespeichert.');
                    } else {
                        var msg = result.data && result.data.message
                                  ? result.data.message : 'Fehler beim Speichern.';
                        showStatus(false, '\u2717 ' + msg);
                    }
                } catch (err) {
                    showStatus(false, '\u2717 Netzwerkfehler: ' + err.message);
                } finally {
                    saveBtn.disabled    = false;
                    saveBtn.textContent = origTxt;
                }
            });
        }

        // Aus Planner übernehmen: fetch + clientseitiger Merge in die Tabelle
        if (importBtn) {
            importBtn.addEventListener('click', async function () {
                var sj = importSelect ? importSelect.value : '';
                if (!sj) return;
                importBtn.disabled    = true;
                var origImportTxt     = importBtn.textContent;
                importBtn.textContent = 'Wird geladen…';
                importStatus.textContent = '';

                try {
                    var response = await fetch(
                        (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php'),
                        {
                            method:  'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body:    new URLSearchParams({
                                action: 'gsh_tp_import_categories_from_planner',
                                nonce:  nonce,
                                sj:     sj,
                            }).toString()
                        }
                    );
                    var result = await response.json();

                    if (result.success && result.data && Array.isArray(result.data.categories)) {
                        var updated = 0, added = 0;
                        result.data.categories.forEach(function (cat) {
                            var rows  = tbody.querySelectorAll('.gsh-cat-row');
                            var match = null;
                            rows.forEach(function (row) {
                                var idVal = (row.querySelector('.gsh-cat-id') || {}).value || '';
                                if (idVal === cat.id) { match = row; }
                            });
                            if (match) {
                                var labelInput = match.querySelector('.gsh-cat-label');
                                var colorInput = match.querySelector('.gsh-cat-color');
                                if (labelInput) labelInput.value = cat.label || '';
                                if (colorInput) colorInput.value = cat.color || '#94a3b8';
                                updatePreview(match);
                                updated++;
                            } else {
                                var newRow = document.createElement('tr');
                                newRow.className = 'gsh-cat-row';
                                newRow.innerHTML = buildRowHtml({ id: cat.id, slug: cat.slug, label: cat.label, color: cat.color, keywords: [] });
                                tbody.appendChild(newRow);
                                added++;
                            }
                        });
                        importStatus.textContent = added + ' übernommen, ' + updated + ' aktualisiert – bitte prüfen und speichern.';
                    } else {
                        var msg = result.data && result.data.message ? result.data.message : 'Fehler beim Laden.';
                        importStatus.textContent = '✗ ' + msg;
                    }
                } catch (err) {
                    importStatus.textContent = '✗ Netzwerkfehler: ' + err.message;
                } finally {
                    importBtn.disabled    = false;
                    importBtn.textContent = origImportTxt;
                }
            });
        }
    })();
    </script>
    <?php
}

/**
 * Rendert den Schuljahr-Profile-Tab (schoolyear-zentriert, 4.24.0).
 *
 * @since 4.24.0
 * @return void
 */
function gsh_tp_render_profile_tab_v2() {
    $schoolyears = gsh_tp_get_schoolyears();
    ?>
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap">
        <h2 style="margin:0">Schuljahre</h2>
        <?php if ( count( $schoolyears ) < 5 ) : ?>
        <form method="post" style="margin:0;display:flex;align-items:center;gap:6px;flex-wrap:wrap">
            <?php wp_nonce_field( 'gsh_tp_new_schoolyear', 'gsh_tp_nsy_n' ); ?>
            <input type="text" name="gsh_tp_new_sy_label" placeholder="2027/28"
                   class="regular-text" style="width:110px" required />
            <button type="submit" name="gsh_tp_new_schoolyear" value="1"
                    class="button" style="color:#27ae60;border-color:#27ae60">
                + Schuljahr anlegen
            </button>
            <details style="display:inline-block">
                <summary style="color:#aaa;font-size:11px;cursor:pointer;list-style:none">
                    Schlüssel (Erweitert)
                </summary>
                <div style="margin-top:4px">
                    <input type="text" name="gsh_tp_new_sy_key" placeholder="sj_2027_28"
                           class="regular-text" style="width:130px" />
                    <p class="description" style="font-size:11px;margin-top:2px">
                        Wird automatisch aus dem Label vorgeschlagen. Nur ändern wenn nötig.
                    </p>
                </div>
            </details>
        </form>
        <?php endif; ?>
    </div>

    <?php if ( empty( $schoolyears ) ) : ?>
        <p class="description">Noch keine Schuljahre vorhanden. Erstelle das erste Schuljahr oder synchronisiere über den Planner.</p>
    <?php endif; ?>

    <?php foreach ( $schoolyears as $sy ) :
        $sy_key = $sy['key'];
        $pid    = sanitize_key( $sy_key );
    ?>
    <div style="border:1px solid #c3c4c7;border-radius:6px;margin-bottom:20px;overflow:hidden">
        <!-- Schuljahr-Header -->
        <div style="background:<?php echo ! empty( $sy['is_active'] ) ? '#e6f4ea' : '#f6f7f7'; ?>;padding:12px 16px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <form method="post" style="margin:0;display:flex;align-items:center;gap:8px;flex:1">
                <?php wp_nonce_field( 'gsh_tp_save_schoolyear_' . $pid, 'gsh_tp_ssy_n_' . $pid ); ?>
                <input type="hidden" name="gsh_tp_ssy_key" value="<?php echo esc_attr( $sy_key ); ?>" />
                <strong style="min-width:60px">Schuljahr:</strong>
                <input type="text" name="gsh_tp_ssy_label" value="<?php echo esc_attr( $sy['label'] ); ?>"
                       class="regular-text" style="width:140px" />
                <button type="submit" name="gsh_tp_save_schoolyear" value="1" class="button button-small">Speichern</button>
            </form>
            <?php if ( empty( $sy['is_active'] ) ) : ?>
            <form method="post" style="margin:0">
                <?php wp_nonce_field( 'gsh_tp_activate_sy_' . $pid, 'gsh_tp_asy_n' ); ?>
                <input type="hidden" name="gsh_tp_asy_key" value="<?php echo esc_attr( $sy_key ); ?>" />
                <button type="submit" name="gsh_tp_activate_schoolyear" value="1"
                        class="button button-small" style="color:#1e8449;border-color:#1e8449">
                    Als aktives Schuljahr setzen
                </button>
                <p class="description" style="margin:4px 0 0;font-size:12px">
                    Dieses Schuljahr wird dann auf der Schulwebsite angezeigt.
                </p>
            </form>
            <?php else : ?>
                <span style="background:#1e8449;color:#fff;padding:2px 10px;border-radius:12px;font-size:12px;font-weight:600">AKTIV</span>
            <?php endif; ?>
            <details style="display:inline-block">
                <summary style="color:#aaa;font-size:11px;cursor:pointer;list-style:none">Erweitert</summary>
                <span style="color:#888;font-size:12px;display:block;margin-top:4px">
                    ID: <code><?php echo esc_html( $sy_key ); ?></code>
                </span>
            </details>
        </div>

        <?php
        // Status badge — shows Veröffentlichungs-Stufe from wp_curriculr_docs.
        $doc_status   = gsh_tp_get_doc_status( $sy_key );
        $stage_labels = array(
            'entwurf'     => 'Entwurf',
            'genehmigt'   => 'Intern',
            'oeffentlich' => 'Öffentlich',
        );
        $stage_colors = array(
            'entwurf'     => '#d97706',
            'genehmigt'   => '#00467D',
            'oeffentlich' => '#16a34a',
        );
        if ( $doc_status ) :
            $s_label = $stage_labels[ $doc_status['stage'] ] ?? esc_html( $doc_status['stage'] );
            $s_color = $stage_colors[ $doc_status['stage'] ] ?? '#888';
            $s_time  = $doc_status['last_sent']
                ? date_i18n( 'd.m.Y, H:i', strtotime( $doc_status['last_sent'] ) ) . ' Uhr'
                : '';
        ?>
        <div style="padding:6px 16px;background:#f0f0f1;border-bottom:1px solid #c3c4c7;font-size:12px;color:#3c434a">
            Veröffentlichung:
            <span style="display:inline-block;font-weight:600;padding:1px 8px;border-radius:10px;margin-left:4px;
                         background:<?php echo esc_attr( $s_color ); ?>22;
                         color:<?php echo esc_attr( $s_color ); ?>;
                         border:1px solid <?php echo esc_attr( $s_color ); ?>44">
                <?php echo esc_html( $s_label ); ?>
            </span>
            <?php if ( $s_time ) : ?>
                <span style="color:#888;margin-left:8px">
                    Zuletzt gesendet: <?php echo esc_html( $s_time ); ?>
                    <?php if ( ! empty( $doc_status['author_name'] ) ) : ?>
                        von <strong style="color:#3c434a"><?php echo esc_html( $doc_status['author_name'] ); ?></strong>
                    <?php endif; ?>
                    · Version <?php echo (int) $doc_status['version']; ?>
                </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php
        // Diskrepanz Planner-Gruppen ↔ provisionierte Kalender sichtbar machen (Spec 2026-07-15).
        $gsh_doc_row = function_exists( 'gsh_tp_curriculr_repo_get' ) ? gsh_tp_curriculr_repo_get( $sy_key ) : null;
        if ( $gsh_doc_row ) :
            $gsh_doc_json  = json_decode( $gsh_doc_row['json'], true );
            $gsh_plan_grps = ( is_array( $gsh_doc_json ) && is_array( $gsh_doc_json['availableGroups'] ?? null ) )
                ? array_map( 'strval', $gsh_doc_json['availableGroups'] ) : array();
            $gsh_cal_grps  = array();
            foreach ( $sy['calendars'] as $gsh_cal ) {
                if ( null !== $gsh_cal['group'] && empty( $gsh_cal['orphaned'] ) ) {
                    $gsh_cal_grps[] = (string) $gsh_cal['group'];
                }
            }
            $gsh_missing = array_diff( $gsh_plan_grps, $gsh_cal_grps );
            if ( $gsh_missing ) :
        ?>
        <div style="padding:6px 16px;background:#fff8e5;border-bottom:1px solid #c3c4c7;font-size:12px;color:#8a6d3b">
            Gruppen im Plan ohne eigenen Kalender:
            <strong><?php echo esc_html( implode( ', ', $gsh_missing ) ); ?></strong>
            — im Planner unter Einstellungen &rarr; Ver&ouml;ffentlichung anhaken und
            &bdquo;Kalender einrichten&ldquo; ausf&uuml;hren. Abo-Anleitung f&uuml;r Kolleg:innen:
            <code>docs/kalender-abo-anleitung.md</code> im Planner-Repository.
        </div>
        <?php endif; endif; ?>

        <!-- Planungsdokument: manueller Upload (SSO-Alternative, 4.28.0) -->
        <div style="padding:12px 16px;border-bottom:1px solid #c3c4c7;background:#fafafa">
            <strong style="display:block;margin-bottom:6px">Planungsdokument (manueller Upload)</strong>
            <p class="description" style="margin:0 0 8px">
                Für Schulen ohne IServ-SSO: Plan im Planer exportieren (Export ↓ → „JSON-Backup") und hier hochladen.
            </p>
            <?php if ( $doc_status ) :
                $export_nonce = wp_create_nonce( 'gsh_tp_curriculr_doc_export_' . $pid );
                $export_url   = admin_url( 'admin-post.php?action=gsh_tp_curriculr_doc_export&sj=' . rawurlencode( $sy_key ) . '&_wpnonce=' . $export_nonce );
            ?>
            <p style="margin:0 0 8px">
                Aktueller Stand: Version <?php echo (int) $doc_status['version']; ?><?php echo $s_time ? ', ' . esc_html( $s_time ) : ''; ?>
                — <a href="<?php echo esc_url( $export_url ); ?>">Sichern ↓</a>
            </p>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <?php wp_nonce_field( 'gsh_tp_doc_import_' . $pid, 'gsh_tp_di_n_' . $pid ); ?>
                <input type="hidden" name="gsh_tp_di_sy" value="<?php echo esc_attr( $sy_key ); ?>" />
                <input type="file" name="gsh_tp_di_file" accept=".json" required />
                <select name="gsh_tp_di_stage">
                    <option value="entwurf">Entwurf</option>
                    <option value="genehmigt">Intern</option>
                    <option value="oeffentlich">Öffentlich</option>
                </select>
                <?php if ( $doc_status ) : ?>
                <label style="font-size:12px">
                    <input type="checkbox" name="gsh_tp_di_confirm" value="1" /> aktuellen Stand überschreiben
                </label>
                <?php endif; ?>
                <button type="submit" name="gsh_tp_doc_import" value="1" class="button">
                    <?php echo $doc_status ? 'Dokument aktualisieren' : 'Dokument hochladen'; ?>
                </button>
            </form>
        </div>

        <!-- Shared Settings (Quartal etc.) -->
        <div style="padding:12px 16px;border-bottom:1px solid #c3c4c7">
            <form method="post">
                <?php wp_nonce_field( 'gsh_tp_save_shared_' . $pid, 'gsh_tp_ssh_n' ); ?>
                <input type="hidden" name="gsh_tp_ssh_key" value="<?php echo esc_attr( $sy_key ); ?>" />
                <table class="form-table" style="margin:0">
                    <tr>
                        <th style="padding:4px 10px 4px 0;width:200px"><label>Start Schulwoche 01</label></th>
                        <td style="padding:4px 0">
                            <input type="date" name="gsh_tp_ssh_start" value="<?php echo esc_attr( $sy['shared']['schuljahr_start'] ?? '' ); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th style="padding:4px 10px 4px 0"><label>Cache-Dauer (Sek.)</label></th>
                        <td style="padding:4px 0">
                            <input type="number" name="gsh_tp_ssh_cache" min="300" max="86400"
                                   value="<?php echo esc_attr( $sy['shared']['cache_duration'] ?? 3600 ); ?>" style="width:100px" />
                        </td>
                    </tr>
                    <tr>
                        <th style="padding:4px 10px 4px 0"><label>Quartalsgrenzen</label></th>
                        <td style="padding:4px 0">
                            <textarea name="gsh_tp_ssh_quartal" rows="4" class="large-text"
                            ><?php echo esc_textarea( $sy['shared']['quartal_grenzen'] ?? '' ); ?></textarea>
                            <p class="description" style="margin:2px 0 0">Pro Zeile: Startdatum|Enddatum (JJJJ-MM-TT).</p>
                        </td>
                    </tr>
                </table>
                <p><button type="submit" name="gsh_tp_save_shared" value="1" class="button">Einstellungen speichern</button></p>
            </form>
        </div>

        <!-- Externer IServ-Kalender (4.36.0) -->
        <?php
        $x_main = null;
        foreach ( $sy['calendars'] as $x_cal ) {
            if ( null === $x_cal['group'] ) { $x_main = $x_cal; break; }
        }
        $x_extern = $x_main && gsh_tp_cal_is_extern( $x_main );
        $x_url    = $x_extern ? ( $x_main['ical_url'] ?? '' ) : '';
        $x_pid    = sanitize_key( gsh_tp_calendar_id( $sy_key, null ) );
        $x_last   = get_option( 'gsh_tp_sync_' . $x_pid, '' );
        ?>
        <div style="padding:12px 16px;border-bottom:1px solid #c3c4c7;border-left:3px solid #0058a0;background:<?php echo $x_extern ? '#f0f6fc' : '#fafafa'; ?>">
            <strong style="display:block;margin-bottom:6px">IServ-Kalender direkt verbinden</strong>
            <p class="description" style="margin:0 0 8px">
                Ohne Planer: Adresse eines freigegebenen IServ-Kalenders eintragen (in IServ meist unter
                Kalender &rarr; Verwaltung &rarr; Freigabe als Link). Das Plugin holt die Termine dann selbst ab
                und baut daraus Quartalsansicht und Druckansicht. Feld leeren trennt die Verbindung wieder.
            </p>
            <form method="post" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <?php wp_nonce_field( 'gsh_tp_save_extern_feed_' . $pid, 'gsh_tp_xf_n' ); ?>
                <input type="hidden" name="gsh_tp_xf_key" value="<?php echo esc_attr( $sy_key ); ?>" />
                <input type="url" name="gsh_tp_xf_url" value="<?php echo esc_attr( $x_url ); ?>"
                       placeholder="https://schule.iserv.de/public/calendar/..."
                       style="flex:1;min-width:320px;padding:4px 8px" />
                <button type="submit" name="gsh_tp_save_extern_feed" value="1" class="button button-primary">
                    <?php echo $x_extern ? 'Adresse aktualisieren' : 'Kalender verbinden'; ?>
                </button>
            </form>
            <?php if ( $x_extern ) : ?>
            <div style="margin-top:8px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <span style="font-size:12px;color:#3c434a">
                    Aktiv &ndash; Planer-&Uuml;bertragungen &uuml;berschreiben diesen Kalender nicht.
                    <?php if ( $x_last ) : ?>
                        Zuletzt abgerufen: <?php echo esc_html( date_i18n( 'd.m.Y, H:i', strtotime( $x_last . ' UTC' ) ) ); ?> Uhr.
                    <?php endif; ?>
                </span>
                <form method="post" style="margin:0">
                    <?php wp_nonce_field( 'gsh_tp_sync_manual', 'gsh_tp_sn' ); ?>
                    <input type="hidden" name="gsh_tp_sync_pid" value="<?php echo esc_attr( $x_pid ); ?>" />
                    <button type="submit" name="gsh_tp_sync" value="1" class="button button-small">Jetzt neu abrufen</button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Kalender-Liste -->
        <table class="widefat" style="border:none;box-shadow:none">
            <thead>
                <tr>
                    <th>Kalender</th>
                    <th>Feed-URL</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $sy['calendars'] as $cal ) :
                $cal_id  = gsh_tp_calendar_id( $sy_key, $cal['group'] );
                $is_main = null === $cal['group'];
                $row_bg  = ! empty( $cal['orphaned'] ) ? '#fff8f0' : '#fff';
            ?>
                <tr style="background:<?php echo esc_attr( $row_bg ); ?>">
                    <td>
                        <?php if ( $is_main ) : ?>
                            <strong>Alle Termine</strong>
                            <span style="font-size:11px;color:#888;margin-left:6px">(Haupt-Kalender)</span>
                            <?php if ( gsh_tp_cal_is_extern( $cal ) ) : ?>
                                <span style="font-size:11px;background:#e6f0ff;color:#1a56db;padding:1px 6px;border-radius:10px;margin-left:4px">IServ</span>
                            <?php endif; ?>
                        <?php else : ?>
                            <?php echo esc_html( $cal['group'] ); ?>
                            <?php if ( ! empty( $cal['managed'] ) ) : ?>
                                <span style="font-size:11px;background:#e6f0ff;color:#1a56db;padding:1px 6px;border-radius:10px;margin-left:4px">Curriculr</span>
                            <?php endif; ?>
                            <?php if ( ! empty( $cal['orphaned'] ) ) : ?>
                                <span style="font-size:11px;background:#fef3c7;color:#92400e;padding:1px 6px;border-radius:10px;margin-left:4px">verwaist</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        <br><span style="font-size:11px;color:#aaa">ID: <code><?php echo esc_html( $cal_id ); ?></code></span>
                    </td>
                    <td>
                        <?php $cal_extern = gsh_tp_cal_is_extern( $cal ); ?>
                        <?php if ( ! empty( $cal['ical_url'] ) ) : ?>
                            <input type="text" readonly value="<?php echo esc_attr( $cal['ical_url'] ); ?>"
                                   style="width:100%;font-size:12px;border:1px solid #ddd;padding:3px 6px"
                                   onclick="this.select()" title="Klicken zum Auswählen" />
                            <span style="font-size:11px;color:#666">
                                <?php echo $cal_extern
                                    ? 'Quelle: IServ-Kalender (oben eingetragen)'
                                    : 'Abo-Adresse für IServ und andere Kalender-Apps'; ?>
                            </span>
                        <?php elseif ( $cal_extern ) : ?>
                            <em style="color:#aaa;font-size:12px">— Adresse oben eintragen —</em>
                        <?php else : ?>
                            <em style="color:#aaa;font-size:12px">— wird nach Planner-Speichern gesetzt —</em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post" style="margin:0;display:flex;align-items:center;gap:8px">
                            <?php wp_nonce_field( 'gsh_tp_toggle_draft_' . sanitize_key( $cal_id ), 'gsh_tp_td_n' ); ?>
                            <input type="hidden" name="gsh_tp_td_sy"  value="<?php echo esc_attr( $sy_key ); ?>" />
                            <input type="hidden" name="gsh_tp_td_cal" value="<?php echo esc_attr( $cal['group'] ?? '' ); ?>" />
                            <?php echo ! empty( $cal['is_draft'] ) ? '<span style="color:#b7950b">Entwurf</span>' : '<span style="color:#1e8449">Beschlossen</span>'; ?>
                            <button type="submit" name="gsh_tp_toggle_draft" value="1" class="button button-small">
                                <?php echo ! empty( $cal['is_draft'] ) ? 'Als beschlossen markieren' : 'Als Entwurf markieren'; ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <?php if ( ! $is_main ) : ?>
                        <form method="post" style="margin:0;display:inline">
                            <?php wp_nonce_field( 'gsh_tp_del_cal_' . sanitize_key( $cal_id ), 'gsh_tp_dc_n' ); ?>
                            <input type="hidden" name="gsh_tp_dc_sy"  value="<?php echo esc_attr( $sy_key ); ?>" />
                            <input type="hidden" name="gsh_tp_dc_cal" value="<?php echo esc_attr( $cal['group'] ); ?>" />
                            <button type="submit" name="gsh_tp_del_cal" value="1"
                                    class="button button-small" style="color:#c0392b;border-color:#c0392b"
                                    onclick="return confirm('Kalender «<?php echo esc_js( $cal['group'] ); ?>» wirklich löschen?')">
                                &times; Löschen
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ( empty( $sy['is_active'] ) ) : ?>
        <details style="padding:10px 16px;border-top:1px solid #c3c4c7">
            <summary style="cursor:pointer;color:#a93226;font-size:12px;user-select:none;list-style:none;display:flex;align-items:center;gap:6px">
                <span>&#9656;</span> Schuljahr löschen&hellip;
            </summary>
            <div style="margin-top:10px;padding:14px 16px;background:#fdf0f0;border:1px solid #e8a0a0;border-radius:4px">
                <p style="margin:0 0 10px;color:#7b241c;font-size:13px">
                    <strong>Achtung:</strong> Alle Termin-Daten, Revisionen und Kalender für
                    <strong>„<?php echo esc_html( $sy['label'] ); ?>"</strong>
                    werden unwiderruflich gelöscht. Diese Aktion kann nicht rückgängig gemacht werden.
                </p>
                <form method="post" style="margin:0">
                    <?php wp_nonce_field( 'gsh_tp_del_sy_' . $pid, 'gsh_tp_dsy_n' ); ?>
                    <input type="hidden" name="gsh_tp_dsy_key" value="<?php echo esc_attr( $sy_key ); ?>" />
                    <button type="submit" name="gsh_tp_del_sy" value="1"
                            class="button"
                            style="background:#c0392b;color:#fff;border-color:#a93226;box-shadow:none">
                        Schuljahr „<?php echo esc_html( $sy['label'] ); ?>" endgültig löschen
                    </button>
                </form>
            </div>
        </details>
        <?php endif; ?>

    </div>
    <?php endforeach; ?>
    <?php
}

/**
 * Rendert den Sync-Tab (Curriculr Planner-Sync).
 *
 * @since 4.10.0
 * @return void
 */
function gsh_tp_render_sync_tab() {
    ?>
    <h2>Curriculr Planner-Sync</h2>
    <div style="background:#eaf2f8;border:1px solid #2874a6;padding:12px 16px;margin-bottom:16px;border-radius:6px;">
        <strong>Was ist das?</strong><br>
        Erlaubt dem Curriculr-Planner, Terminpl&auml;ne direkt an dieses WordPress zu senden (REST-Schnittstelle <code>curriculr/v1</code>).
        Trage die Adresse ein, von der aus der Planner ge&ouml;ffnet wird &ndash; nur diese Adresse darf senden (CORS-Schutz).
    </div>
    <form method="post" action="">
        <?php wp_nonce_field( 'gsh_tp_save_curriculr', 'gsh_tp_cur_n' ); ?>
        <input type="hidden" name="gsh_tp_save_curriculr" value="1" />
        <table class="form-table">
            <tr>
                <th><label for="gsh_tp_curriculr_origin">Erlaubte Planner-Adresse</label></th>
                <td>
                    <input type="url" id="gsh_tp_curriculr_origin" name="gsh_tp_curriculr_origin"
                           value="<?php echo esc_attr( get_option( 'gsh_tp_curriculr_origin', 'https://juwagn.github.io' ) ); ?>"
                           class="regular-text" placeholder="https://juwagn.github.io" />
                    <p class="description">
                        Online-Planner: <code>https://juwagn.github.io</code> (Standard).<br>
                        Zum lokalen Testen: <code>http://localhost:5173</code> &ndash; nur Schema + Host + Port, ohne Pfad, ohne Schr&auml;gstrich am Ende.
                    </p>
                </td>
            </tr>
            <tr>
                <th>REST-Schnittstelle</th>
                <td>
                    <?php
                    $cur_origin = get_option( 'gsh_tp_curriculr_origin', 'https://juwagn.github.io' );
                    echo '<code style="display:block;padding:6px 10px;background:#f6f7f7;border:1px solid #ddd;border-radius:3px;font-size:13px;word-break:break-all">' . esc_html( rest_url( 'curriculr/v1/health' ) ) . '</code>';
                    echo '<p class="description" style="margin-top:6px">Aktuell erlaubte Adresse: <strong>' . esc_html( $cur_origin ) . '</strong></p>';
                    ?>
                </td>
            </tr>
            <?php
            $cur_map     = get_option( 'gsh_tp_curriculr_profile_map', array() );
            $cur_sj_key  = array_key_first( $cur_map ) ?? '';
            $cur_prof_id = $cur_map ? reset( $cur_map ) : '';
            ?>
            <tr>
                <th><label for="gsh_tp_curriculr_sj_key">Profil-Zuordnung</label></th>
                <td>
                    <input type="text" id="gsh_tp_curriculr_sj_key" name="gsh_tp_curriculr_sj_key"
                           value="<?php echo esc_attr( $cur_sj_key ); ?>"
                           class="regular-text" placeholder="sj_2026_27" />
                    &rarr;
                    <select name="gsh_tp_curriculr_profile_id">
                        <option value="">— kein Profil —</option>
                        <?php foreach ( gsh_tp_get_profiles() as $p ) : ?>
                            <option value="<?php echo esc_attr( $p['id'] ); ?>"
                                <?php selected( $cur_prof_id, $p['id'] ); ?>>
                                <?php echo esc_html( $p['id'] . ( ! empty( $p['is_active'] ) ? ' (aktiv)' : '' ) ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        Schuljahr-Schl&uuml;ssel, den der Planner sendet (z.B. <code>sj_2026_27</code>), dem Profil zuordnen, das der Terminplan anzeigen soll.<br>
                        Nur wenn diese Zuordnung gesetzt ist, aktualisiert sich die Anzeige automatisch bei &bdquo;&Ouml;ffentlich schalten&ldquo;.
                    </p>
                </td>
            </tr>
        </table>
        <?php submit_button( 'Curriculr-Sync speichern' ); ?>
    </form>
    <?php
}

/**
 * Rendert den Kiosk-Tab (Entwurf-Vorschau + IServ-Einbettung Kiosk-Modus).
 *
 * @since 4.10.0
 * @return void
 */
function gsh_tp_render_kiosk_tab() {
    ?>
    <h2>Entwurf-Vorschau (Schulleitungsteam)</h2>
    <div style="background:#eaf2f8;border:1px solid #2874a6;padding:12px 16px;margin-bottom:16px;border-radius:6px;">
        <strong>Was ist die Entwurf-Vorschau?</strong><br>
        Erm&ouml;glicht dem Schulleitungsteam, Entwurfs-Terminpl&auml;ne vorab einzusehen &ndash; ohne WordPress-Login.
        Teilt einfach den generierten Link.
    </div>

    <form method="post" action="">
        <?php wp_nonce_field( 'gsh_tp_save_draft', 'gsh_tp_sd_n' ); ?>
        <input type="hidden" name="gsh_tp_save_draft" value="1" />
        <table class="form-table">
            <tr>
                <th><label for="gsh_tp_draft_kiosk_token">Entwurf-Token</label></th>
                <td>
                    <input type="text" id="gsh_tp_draft_kiosk_token" name="gsh_tp_draft_kiosk_token"
                           value="<?php echo esc_attr( get_option( 'gsh_tp_draft_kiosk_token', '' ) ); ?>"
                           class="regular-text" autocomplete="off" placeholder="mind. 20 Zeichen" />
                    <button type="button" class="button" style="margin-left:6px"
                            onclick="if(!confirm('Token wird ersetzt. Alte Entwurf-Links funktionieren nicht mehr.'))return;document.getElementById('gsh_tp_draft_kiosk_token').value=Array.from(crypto.getRandomValues(new Uint8Array(24)),function(b){return b.toString(36);}).join('').slice(0,32);">
                        <?php echo gsh_tp_icon( 'dice' ); ?> Zuf&auml;lligen Token erzeugen
                    </button>
                    <p class="description">Geheimer Token f&uuml;r den Zugang zur Entwurf-Vorschau. Mind. 20 Zeichen empfohlen.</p>
                    <?php
                    $cur_draft_token = get_option( 'gsh_tp_draft_kiosk_token', '' );
                    if ( empty( $cur_draft_token ) ) {
                        echo '<p style="color:#c0392b;margin-top:6px"><strong>' . gsh_tp_icon( 'alert-triangle' ) . ' Kein Token gesetzt</strong> &ndash; Entwurf-Vorschau nicht aktiv.</p>';
                    } elseif ( strlen( $cur_draft_token ) < 20 ) {
                        echo '<p style="color:#e67e22;margin-top:6px"><strong>' . gsh_tp_icon( 'alert-triangle' ) . ' Token zu kurz</strong> &ndash; mind. 20 Zeichen empfohlen.</p>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th>Vorschau-URL</th>
                <td>
                    <?php
                    $draft_token  = get_option( 'gsh_tp_draft_kiosk_token', '' );
                    $draft_pages  = get_pages( array( 'meta_key' => '_wp_page_template', 'meta_value' => 'page-terminplan-entwurf.php' ) );
                    $has_draft_profile = false;
                    foreach ( gsh_tp_get_profiles() as $p ) {
                        if ( ! empty( $p['is_draft'] ) ) { $has_draft_profile = true; break; }
                    }
                    $missing = array();
                    if ( empty( $draft_token ) )       { $missing[] = 'Entwurf-Token'; }
                    if ( ! $has_draft_profile )        { $missing[] = 'Profil mit Status &bdquo;Entwurf&ldquo;'; }
                    if ( empty( $draft_pages ) )       { $missing[] = 'Vorschau-Seite (Anleitung unten)'; }
                    if ( empty( $missing ) ) {
                        $draft_url = trailingslashit( get_permalink( $draft_pages[0]->ID ) ) . '?token=' . urlencode( $draft_token );
                        echo '<code style="display:block;padding:6px 10px;background:#f6f7f7;border:1px solid #ddd;border-radius:3px;font-size:13px;word-break:break-all">' . esc_html( $draft_url ) . '</code>';
                        echo '<a href="' . esc_url( $draft_url ) . '" target="_blank" rel="noopener" style="display:inline-block;margin-top:6px">' . gsh_tp_icon( 'link' ) . ' Vorschau testen</a>';
                    } else {
                        echo '<p style="color:#888;margin:0 0 6px">' . gsh_tp_icon( 'alert-triangle' ) . ' Noch nicht verf&uuml;gbar &ndash; folgendes fehlt: ' . implode( ', ', $missing ) . '.</p>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th>Vorschau-Seite einrichten</th>
                <td>
                    <ol style="margin:.25rem 0 0;padding-left:1.25rem;line-height:1.9">
                        <li>WordPress-Admin &rarr; <strong>Seiten &rarr; Erstellen</strong></li>
                        <li>Titel vergeben (z.&nbsp;B. <em>Entwurf-Vorschau</em>)</li>
                        <li>Rechts unter <strong>Seitenvorlage</strong> &rarr; <strong>Terminplan Entwurf-Vorschau</strong> w&auml;hlen</li>
                        <li>Seite ver&ouml;ffentlichen &ndash; URL erscheint oben automatisch</li>
                    </ol>
                    <p class="description" style="margin-top:.4rem"><?php echo gsh_tp_icon( 'info' ); ?> Seitenvorlage ist im Plugin integriert &ndash; kein Theme-Copy n&ouml;tig.</p>
                </td>
            </tr>
        </table>
        <?php submit_button( 'Entwurf-Einstellungen speichern' ); ?>
    </form>

    <hr style="margin:24px 0" />

    <h2>IServ-Einbettung (Kiosk-Modus)</h2>
    <div style="background:#eaf2f8;border:1px solid #2874a6;padding:12px 16px;margin-bottom:16px;border-radius:6px;">
        <strong>Was ist der Kiosk-Modus?</strong><br>
        Eine Ansicht des Terminplans ohne WordPress-Men&uuml; und Passwort.
        Ideal zum Einbetten in IServ als Navigations-Eintrag.
    </div>

    <form method="post" action="">
        <?php wp_nonce_field( 'gsh_tp_save_kiosk', 'gsh_tp_sk_n' ); ?>
        <input type="hidden" name="gsh_tp_save_kiosk" value="1" />
        <?php
        $fail_count = (int) get_option( 'gsh_tp_mail_fail_count', 0 );
        if ( $fail_count >= 3 ) : ?>
        <div class="notice notice-warning inline" style="margin-bottom:16px">
            <p><strong>⚠ E-Mail-Diagnose:</strong> Die letzten <?php echo (int) $fail_count; ?> Feedback-E-Mails konnten nicht zugestellt werden.
            Empfehlung: <a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=wp+mail+smtp&tab=search&type=term' ) ); ?>">WP Mail SMTP installieren</a>.
            <a href="<?php echo esc_url( admin_url( 'options-general.php?page=gsh-terminplan&tab=_system' ) ); ?>">Feedback-Log ansehen</a></p>
        </div>
        <?php endif; ?>
        <table class="form-table">
            <tr>
                <th><label for="gsh_tp_kiosk_token">Kiosk-Token</label></th>
                <td>
                    <input type="text" id="gsh_tp_kiosk_token" name="gsh_tp_kiosk_token"
                           value="<?php echo esc_attr( get_option( 'gsh_tp_kiosk_token', '' ) ); ?>"
                           class="regular-text" autocomplete="off" placeholder="mind. 20 Zeichen" />
                    <button type="button" class="button" style="margin-left:6px"
                            onclick="if(!confirm('Token wird ersetzt. Alte Kiosk-Links funktionieren nicht mehr.'))return;document.getElementById('gsh_tp_kiosk_token').value=Array.from(crypto.getRandomValues(new Uint8Array(24)),function(b){return b.toString(36);}).join('').slice(0,32);">
                        <?php echo gsh_tp_icon( 'dice' ); ?> Zuf&auml;lligen Token erzeugen
                    </button>
                    <p class="description">Geheimer Token f&uuml;r den Zugang zur Kiosk-Seite. Mind. 20 Zeichen empfohlen.</p>
                    <?php
                    $cur_token = get_option( 'gsh_tp_kiosk_token', '' );
                    if ( empty( $cur_token ) ) {
                        echo '<p style="color:#c0392b;margin-top:6px"><strong>' . gsh_tp_icon( 'alert-triangle' ) . ' Kein Token gesetzt</strong> &ndash; Kiosk-Seite ist ohne Authentifizierung erreichbar!</p>';
                    } elseif ( strlen( $cur_token ) < 20 ) {
                        echo '<p style="color:#e67e22;margin-top:6px"><strong>' . gsh_tp_icon( 'alert-triangle' ) . ' Token zu kurz</strong> &ndash; mind. 20 Zeichen empfohlen.</p>';
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
                        <p style="color:#e67e22;margin-top:6px"><strong><?php echo gsh_tp_icon( 'alert-triangle' ); ?> IServ-Domain fehlt</strong> &ndash; ohne diese Einstellung kann jede Website die Kiosk-Seite einbetten.</p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="gsh_tp_feedback_email">Feedback-Empf&auml;nger</label></th>
                <td>
                    <input type="email" id="gsh_tp_feedback_email" name="gsh_tp_feedback_email"
                           value="<?php echo esc_attr( get_option( 'gsh_tp_feedback_email', get_bloginfo( 'admin_email' ) ) ); ?>"
                           class="regular-text" placeholder="deine@schule.de" />
                    <p class="description">An diese Adresse werden Feedback-Nachrichten aus dem Terminplan gesendet.
                    Standard: WordPress-Admin-E-Mail (<code><?php echo esc_html( get_bloginfo( 'admin_email' ) ); ?></code>).</p>
                </td>
            </tr>
            <tr>
                <th><label for="gsh_tp_school_name">Schulbezeichnung (PDF-Kopfzeile)</label></th>
                <td>
                    <input type="text" id="gsh_tp_school_name" name="gsh_tp_school_name"
                           value="<?php echo esc_attr( get_option( 'gsh_tp_school_name', 'Gesamtschule Horst' ) ); ?>"
                           class="regular-text" placeholder="Gesamtschule Horst" />
                    <p class="description">Erscheint oben links neben dem Curriculr-Logo auf dem Termin-PDF (Quartal- und Jahres-Export).
                    Bei zwei Worten wird nach dem ersten Wort umgebrochen (z.&nbsp;B. "Gesamtschule" / "Horst").</p>
                </td>
            </tr>
            <tr>
                <th>Kiosk-URL</th>
                <td>
                    <?php
                    $kiosk_token = get_option( 'gsh_tp_kiosk_token', '' );
                    $kiosk_pages = get_pages( array( 'meta_key' => '_wp_page_template', 'meta_value' => 'page-terminplan-kiosk.php' ) );
                    $missing = array();
                    if ( empty( $kiosk_token ) ) { $missing[] = 'Kiosk-Token'; }
                    if ( empty( $kiosk_pages ) ) { $missing[] = 'Seite mit Vorlage <code>page-terminplan-kiosk.php</code>'; }
                    if ( empty( $missing ) ) {
                        $kiosk_url = trailingslashit( get_permalink( $kiosk_pages[0]->ID ) ) . '?token=' . urlencode( $kiosk_token );
                        echo '<code style="display:block;padding:6px 10px;background:#f6f7f7;border:1px solid #ddd;border-radius:3px;font-size:13px;word-break:break-all">' . esc_html( $kiosk_url ) . '</code>';
                        echo '<a href="' . esc_url( $kiosk_url ) . '" target="_blank" rel="noopener" style="display:inline-block;margin-top:6px">' . gsh_tp_icon( 'link' ) . ' Kiosk-Seite testen</a>';
                    } else {
                        echo '<p style="color:#888;margin:0">' . gsh_tp_icon( 'alert-triangle' ) . ' Noch nicht verf&uuml;gbar &ndash; folgendes fehlt: ' . implode( ', ', $missing ) . '.</p>';
                    }
                    ?>
                </td>
            </tr>
        </table>
        <?php submit_button( 'Kiosk-Einstellungen speichern' ); ?>
    </form>
    <?php
}

/**
 * Rendert den System-Tab (IServ-SSO, Datenschutz, Shortcode-Hilfe, Logs).
 *
 * @since 3.2.0 (Tab-Wrapper seit 3.5.0)
 * @return void
 */
function gsh_tp_render_system_tab() {
    // Kiosk + Entwurf-Vorschau — moved from former standalone Kiosk tab.
    gsh_tp_render_kiosk_tab();
    echo '<hr style="margin:24px 0" />';

    $cur_cfg = gsh_tp_curriculr_auth_config();
    $cur_defs = array(
        'CURRICULR_ISERV_BASE_URL'      => ! empty( $cur_cfg['iserv_base'] ),
        'CURRICULR_ISERV_CLIENT_ID'     => ! empty( $cur_cfg['client_id'] ),
        'CURRICULR_ISERV_CLIENT_SECRET' => ! empty( $cur_cfg['client_secret'] ),
        'CURRICULR_APP_TOKEN_KEY'       => ! empty( $cur_cfg['app_token_key'] ),
    );
    $cur_ready = gsh_tp_curriculr_auth_is_configured( $cur_cfg );
    ?>
    <h2><?php echo esc_html__( 'IServ-SSO (Mehrbenutzer-Anmeldung)', 'gsh-terminplan' ); ?></h2>
    <p>
        <?php echo wp_kses_post( $cur_ready
            ? gsh_tp_icon( 'check' ) . ' <strong>Konfiguriert.</strong> Die Anmeldung &uuml;ber IServ ist aktiv.'
            : gsh_tp_icon( 'alert-triangle' ) . ' <strong>Noch nicht vollst&auml;ndig konfiguriert.</strong> Bitte die fehlenden Konstanten in <code>wp-config.php</code> erg&auml;nzen.' ); ?>
    </p>
    <table class="widefat" style="max-width:640px">
        <thead><tr><th>Konstante (in wp-config.php)</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ( $cur_defs as $const => $set ) : ?>
            <tr>
                <td><code><?php echo esc_html( $const ); ?></code></td>
                <td><?php echo wp_kses_post( $set
                    ? gsh_tp_icon( 'check' ) . ' gesetzt'
                    : gsh_tp_icon( 'alert-triangle' ) . ' fehlt' ); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p>
        <strong>Redirect-URI f&uuml;r die IServ-Client-Registrierung:</strong><br>
        <code><?php echo esc_html( $cur_cfg['redirect_uri'] ); ?></code>
    </p>
    <p>
        <strong>Erlaubte Gruppen (Whitelist):</strong>
        <code><?php echo esc_html( $cur_cfg['allowed_groups'] ? implode( ', ', $cur_cfg['allowed_groups'] ) : '— keine — (Anmeldung gesperrt)' ); ?></code>
    </p>

    <h2><?php echo esc_html__( 'Datenschutz &amp; Transparenz', 'gsh-terminplan' ); ?></h2>
    <p>
        Verarbeitete Daten bei aktivierter IServ-Anmeldung: IServ-Kennung
        (<code>sub</code>), Anzeigename und freigegebene Gruppen &mdash; sowie die
        Plandaten des Schuljahres. Speicherung der Plandaten auf dem
        WordPress-Server (Hoster w3w.de, DE/EU). IServ-Tokens werden nicht
        dauerhaft gespeichert.
    </p>
    <p>
        Hinweis: Die Planner-Oberfl&auml;che wird von GitHub Pages
        (GitHub/Microsoft, USA) geladen; dabei wird die IP-Adresse in ein
        Drittland &uuml;bertragen. Dort werden <em>keine</em> Plandaten verarbeitet
        (nur statisches JavaScript/CSS). Zweck: gemeinsame Terminplanung.
        Rechtsgrundlage und Ansprechpartner: siehe schulisches
        Datenschutzkonzept.
    </p>
    <p style="padding:12px;border-left:4px solid var(--gsh-marine,#00345C);background:#f1f5f9">
        <strong>Hinweis (&bdquo;Vibecoding&ldquo;):</strong> Diese Werkzeuge
        (Planner und WordPress-Plugin) wurden im Wege des &bdquo;Vibecodings&ldquo;
        &mdash; also KI-gest&uuml;tzter Softwareentwicklung &mdash; erstellt. Vor dem
        produktiven Einsatz mit personenbezogenen Daten sind die &uuml;bliche
        Sorgfalt, Tests und eine datenschutzrechtliche Bewertung anzuwenden.
    </p>

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

    echo '<hr style="margin:24px 0" />';

    // Curriculr REST-Einstellungen (Origin + Profil-Zuordnung) — ehemals im Curriculr-Sync-Tab
    ?>
    <h2>Curriculr REST-Einstellungen</h2>
    <div style="background:#eaf2f8;border:1px solid #2874a6;padding:12px 16px;margin-bottom:16px;border-radius:6px;">
        <strong>Erlaubte Planner-Adresse (CORS-Origin):</strong>
        Nur Anfragen von dieser Adresse werden vom REST-Endpunkt <code>curriculr/v1</code> akzeptiert.
    </div>
    <form method="post" action="">
        <?php wp_nonce_field( 'gsh_tp_save_curriculr', 'gsh_tp_cur_n' ); ?>
        <input type="hidden" name="gsh_tp_save_curriculr" value="1" />
        <table class="form-table">
            <tr>
                <th><label for="gsh_tp_curriculr_origin_sys">Erlaubte Planner-Adresse</label></th>
                <td>
                    <input type="url" id="gsh_tp_curriculr_origin_sys" name="gsh_tp_curriculr_origin"
                           value="<?php echo esc_attr( get_option( 'gsh_tp_curriculr_origin', 'https://juwagn.github.io' ) ); ?>"
                           class="regular-text" placeholder="https://juwagn.github.io" />
                    <p class="description">
                        Online-Planner: <code>https://juwagn.github.io</code> (Standard).<br>
                        Zum lokalen Testen: <code>http://localhost:5173</code> &ndash; nur Schema + Host + Port, ohne Pfad, ohne Schr&auml;gstrich am Ende.
                    </p>
                </td>
            </tr>
            <tr>
                <th>REST-Schnittstelle</th>
                <td>
                    <?php
                    $cur_origin = get_option( 'gsh_tp_curriculr_origin', 'https://juwagn.github.io' );
                    echo '<code style="display:block;padding:6px 10px;background:#f6f7f7;border:1px solid #ddd;border-radius:3px;font-size:13px;word-break:break-all">' . esc_html( rest_url( 'curriculr/v1/health' ) ) . '</code>';
                    echo '<p class="description" style="margin-top:6px">Aktuell erlaubte Adresse: <strong>' . esc_html( $cur_origin ) . '</strong></p>';
                    ?>
                </td>
            </tr>
            <?php
            $cur_map     = get_option( 'gsh_tp_curriculr_profile_map', array() );
            $cur_sj_key  = array_key_first( $cur_map ) ?? '';
            $cur_prof_id = $cur_map ? reset( $cur_map ) : '';
            ?>
            <tr>
                <th><label for="gsh_tp_curriculr_sj_key_sys">Profil-Zuordnung</label></th>
                <td>
                    <input type="text" id="gsh_tp_curriculr_sj_key_sys" name="gsh_tp_curriculr_sj_key"
                           value="<?php echo esc_attr( $cur_sj_key ); ?>"
                           class="regular-text" placeholder="sj_2026_27" />
                    &rarr;
                    <select name="gsh_tp_curriculr_profile_id">
                        <option value="">— kein Profil —</option>
                        <?php foreach ( gsh_tp_get_profiles() as $p ) : ?>
                            <option value="<?php echo esc_attr( $p['id'] ); ?>"
                                <?php selected( $cur_prof_id, $p['id'] ); ?>>
                                <?php echo esc_html( $p['id'] . ( ! empty( $p['is_active'] ) ? ' (aktiv)' : '' ) ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        Schuljahr-Schl&uuml;ssel, den der Planner sendet (z.B. <code>sj_2026_27</code>), dem Profil zuordnen, das der Terminplan anzeigen soll.<br>
                        Nur wenn diese Zuordnung gesetzt ist, aktualisiert sich die Anzeige automatisch bei &bdquo;&Ouml;ffentlich schalten&ldquo;.
                    </p>
                </td>
            </tr>
        </table>
        <?php submit_button( 'Curriculr-Einstellungen speichern' ); ?>
    </form>

    <?php
    echo '<hr style="margin:24px 0" />';
    gsh_tp_render_sync_log_tab();
    echo '<hr style="margin:24px 0" />';
    echo '<h2>Feedback-Log</h2>';
    gsh_tp_render_feedback_log_tab();
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

/**
 * Ergänzt bereits geparste Events um die echte Uhrzeit aus DTSTART/DTEND.
 *
 * gsh_tp_parse_event() behält aus DATE-TIME-Werten nur das Datum (Y-m-d) —
 * die Uhrzeit geht dabei verloren. Diese Funktion holt sie zusätzlich direkt
 * aus dem iCal-Rohtext (per UID gematcht), ohne den Parser selbst anzufassen.
 *
 * @since 4.28.2
 * @param  array  $events Events aus gsh_tp_parse_events().
 * @param  string $data   Roher iCal-Text (gleiche Quelle wie beim Parsen).
 * @return array          $events, ergänzt um 'time_start'/'time_end' (H:i oder '').
 */
function gsh_tp_augment_event_times( array $events, $data ) {
    if ( empty( $events ) || empty( $data ) ) {
        return $events;
    }

    preg_match_all( '/BEGIN:VEVENT(.*?)END:VEVENT/s', $data, $m );
    if ( empty( $m[1] ) ) {
        return $events;
    }

    $blocks_by_uid = array();
    foreach ( $m[1] as $blk ) {
        $blk = str_replace( "\r\n", "\n", $blk );
        $blk = preg_replace( '/\n[ \t]/', '', $blk );
        if ( preg_match( '/^UID:(.*)$/m', $blk, $um ) ) {
            $blocks_by_uid[ trim( $um[1] ) ] = $blk;
        }
    }

    $extract_time = function ( $blk, $prop ) {
        if ( preg_match( '/^' . $prop . '(?:;[^:\n]*)?:(\d{8}T\d{6})/m', $blk, $tm ) ) {
            return substr( $tm[1], 9, 2 ) . ':' . substr( $tm[1], 11, 2 );
        }
        return '';
    };

    foreach ( $events as &$ev ) {
        $ev['time_start'] = '';
        $ev['time_end']   = '';
        if ( $ev['allday'] || empty( $ev['uid'] ) || empty( $blocks_by_uid[ $ev['uid'] ] ) ) {
            continue;
        }
        $blk               = $blocks_by_uid[ $ev['uid'] ];
        $ev['time_start']  = $extract_time( $blk, 'DTSTART' );
        $ev['time_end']    = $extract_time( $blk, 'DTEND' );
    }
    unset( $ev );

    return $events;
}

/**
 * Reichert geparste Events um Gruppen aus X-GSH-GROUPS an (Roh-ICS-Scan
 * per UID, analog gsh_tp_augment_event_times — der Parser bleibt unberührt).
 * Multi-Value-Semantik wie CATEGORIES: Split an un-escaped Kommas, danach
 * ICS-Unescape je Wert.
 *
 * @since 4.33.0
 * @param  array  $events Geparste Events (mit 'uid').
 * @param  string $data   Rohe ICS-Daten.
 * @return array          Events mit 'groups' => string[].
 */
function gsh_tp_augment_event_groups( array $events, $data ) {
    foreach ( $events as &$ev ) {
        $ev['groups'] = array();
    }
    unset( $ev );
    if ( empty( $events ) || empty( $data ) ) {
        return $events;
    }

    preg_match_all( '/BEGIN:VEVENT(.*?)END:VEVENT/s', $data, $m );
    if ( empty( $m[1] ) ) {
        return $events;
    }

    $groups_by_uid = array();
    foreach ( $m[1] as $blk ) {
        $blk = str_replace( "\r\n", "\n", $blk );
        $blk = preg_replace( '/\n[ \t]/', '', $blk );
        if ( ! preg_match( '/^UID:(.*)$/m', $blk, $um ) ) {
            continue;
        }
        if ( ! preg_match( '/^X-GSH-GROUPS:(.*)$/m', $blk, $gm ) ) {
            continue;
        }
        $list = array();
        foreach ( preg_split( '/(?<!\\\\),/', trim( $gm[1] ) ) as $p ) {
            $p = str_replace( array( '\\,', '\\;', '\\n', '\\\\' ), array( ',', ';', "\n", '\\' ), $p );
            $p = trim( $p );
            if ( '' !== $p ) {
                $list[] = $p;
            }
        }
        $groups_by_uid[ trim( $um[1] ) ] = $list;
    }

    foreach ( $events as &$ev ) {
        if ( ! empty( $ev['uid'] ) && isset( $groups_by_uid[ $ev['uid'] ] ) ) {
            $ev['groups'] = $groups_by_uid[ $ev['uid'] ];
        }
    }
    unset( $ev );

    return $events;
}

/**
 * Liefert Anzeige-Titel (ohne eingeklammerte Zeitangabe) und Zeit-Label für
 * ein Event. Bevorzugt die echte Uhrzeit aus time_start/time_end (siehe
 * gsh_tp_augment_event_times()), fällt sonst auf die Legacy-Konvention
 * „Titel (HH:MM–HH:MM Uhr)" im Titeltext zurück.
 *
 * @since 4.28.2
 * @param  array $ev Event-Array, ggf. ergänzt um time_start/time_end.
 * @return array      array( 'summary' => string, 'time' => string ).
 */
function gsh_tp_event_time_label( $ev ) {
    $summary = $ev['summary'];
    $time    = '';

    if ( ! empty( $ev['time_start'] ) ) {
        $time = ( ! empty( $ev['time_end'] ) && $ev['time_end'] !== $ev['time_start'] )
            ? $ev['time_start'] . '–' . $ev['time_end']
            : $ev['time_start'];
    }

    // Zeitangabe aus Klammern im Titel extrahieren: „Titel (HH:MM–HH:MM Uhr)"
    if ( preg_match( '/^(.*?)\s*\((\d{1,2}:\d{2}(?:\s*[–\-]\s*\d{1,2}:\d{2})?(?:\s*Uhr)?)\)\s*$/', $summary, $m ) ) {
        $summary = trim( $m[1] );
        if ( ! $time ) {
            $time = trim( $m[2] );
        }
    }

    return array(
        'summary' => $summary,
        'time'    => $time,
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
 * Schuljahresbeginn (Schulwoche 00, 01, … usw., 0-basiert wie der Planner).
 *
 * @since 1.2.0
 * @param  string $date  Datum im Format Y-m-d.
 * @param  string $start Erster Montag des Schuljahres (Y-m-d).
 * @return int           Schulwochennummer (≥0) oder negativ wenn vor Schuljahresbeginn.
 */
function gsh_tp_schulwoche( $date, $start ) {
    $days = (int) ( new DateTime( $start ) )->diff( new DateTime( $date ) )->format( '%r%a' );
    return (int) floor( $days / 7 );
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

// ENTFERNT v3.15.0 – Kategorie-Neuaufbau:
// gsh_tp_cat()       → ersetzt durch gsh_tp_assign_categories_to_event() + $ev['categories']-Array
// gsh_tp_build_map() → ersetzt durch hardcodierte Keywords in gsh_tp_assign_categories_to_event()

/**
 * Gibt den CSS-Slug der primären Kategorie eines Events zurück.
 *
 * Hilfsfunktion für Rendering-Code: mappt die erste Kategorie-ID
 * aus dem categories-Array auf den zugehörigen Slug.
 * Gibt 'standard' zurück wenn keine Kategorie vorhanden.
 *
 * @since 3.15.0
 * @param  array $cat_ids  Array von Kategorie-IDs aus $event['categories'].
 * @param  array $cat_map  Assoziatives Array: id → Kategorie (aus array_column).
 * @return string          CSS-Slug, z. B. 'konferenzen' oder 'standard'.
 */
function gsh_tp_primary_slug( array $cat_ids, array $cat_map ): string {
    if ( empty( $cat_ids ) ) {
        return 'standard';
    }
    $cat = $cat_map[ $cat_ids[0] ] ?? null;
    return $cat ? $cat['slug'] : 'standard';
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
        // Entwurf-Modus: nur für Admins oder validierten Draft-Kiosk-Zugang sichtbar
        if ( ! current_user_can( 'manage_options' ) && ! gsh_tp_draft_kiosk_context() ) {
            return '<div class="gtp-msg gtp-msg--info">'
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
            return '<div class="gtp-msg gtp-msg--info">'
                 . 'Kein Entwurf-Profil vorhanden.</div>';
        }
    }

    if ( ! $profile_id ) {
        $profile_id = gsh_tp_active_profile_id();
    }

    $profile = gsh_tp_get_profile( $profile_id );
    if ( ! $profile ) {
        return '<div class="gtp-msg gtp-msg--error">'
             . 'Kein Schuljahr-Profil gefunden. Bitte die Plugin-Einstellungen pr&uuml;fen.</div>';
    }

    // Entwurf-Modus: Entwürfe ohne schuljahr="entwurf" nur für Admins oder Draft-Kiosk
    if ( ! empty( $profile['is_draft'] ) && ! current_user_can( 'manage_options' ) && ! gsh_tp_draft_kiosk_context() ) {
        return '<div class="gtp-msg gtp-msg--info">'
             . gsh_tp_icon( 'lock' ) . ' Dieser Terminplan ist noch nicht freigegeben.</div>';
    }

    $data = gsh_tp_fetch_ical( $profile_id );
    if ( ! $data ) {
        return '<div class="gtp-msg gtp-msg--error">'
             . esc_html__( 'Keine Kalenderdaten verfügbar. Bitte die iCal-URL in den Plugin-Einstellungen prüfen.', 'gsh-terminplan' )
             . '</div>';
    }

    $events  = gsh_tp_parse_events( $data );
    $events  = gsh_tp_augment_event_times( $events, $data );
    $events = gsh_tp_augment_event_groups( $events, $data );

    // Union aller Termin-Gruppen für die Filter-Chips (Filter-Bar-Builder liest sie).
    $group_union = array();
    foreach ( $events as $ev_g ) {
        foreach ( (array) ( $ev_g['groups'] ?? array() ) as $g ) {
            $group_union[ $g ] = true;
        }
    }
    ksort( $group_union );
    $GLOBALS['gsh_tp_group_union'] = array_keys( $group_union );

    $grenzen = gsh_tp_quartale( $profile_id );
    $sjs     = gsh_tp_opt( 'schuljahr_start', '2025-08-25', $profile_id );

    // Kategorien per Keyword-Matching auf alle Events anwenden (einmalig vor dem Indexing)
    $events = array_map( 'gsh_tp_assign_categories_to_event', $events );

    // Einmaliger Aufbau des Date-Index für O(1)-Lookup statt O(n) pro Tag
    $date_index = gsh_tp_build_date_index( $events );

    // ── Annotationen aus dem Curriculr-Dokument laden ──
    // Die Planner-Anmerkungenspalte (0-basierte schoolweek) auf PHP-Schulwochen (1-basiert) mappen.
    $annotation_map = array();
    // Nested schoolyears-Model (seit 4.24.0) liefert sj_key direkt am Profil.
    $ann_sj_key = $profile['sj_key'] ?? '';
    if ( '' === $ann_sj_key ) {
        // Legacy-Kompat: alte Installs ohne nested schoolyears-Model.
        $cur_sj_map = get_option( 'gsh_tp_curriculr_profile_map', array() );
        if ( is_array( $cur_sj_map ) ) {
            $found = array_search( $profile_id, $cur_sj_map, true );
            $ann_sj_key = false !== $found ? $found : '';
        }
    }
    if ( '' !== $ann_sj_key ) {
        $ann_row = gsh_tp_curriculr_repo_get( $ann_sj_key );
        if ( $ann_row && ! empty( $ann_row['json'] ) ) {
            $ann_doc = json_decode( $ann_row['json'], true );
            if ( is_array( $ann_doc ) && ! empty( $ann_doc['annotations'] ) ) {
                foreach ( $ann_doc['annotations'] as $ann ) {
                    if ( isset( $ann['schoolweek'], $ann['text'] ) && trim( $ann['text'] ) !== '' ) {
                        $annotation_map[ (int) $ann['schoolweek'] ] = $ann['text'];
                    }
                }
            }
        }
    }

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

    // Kategorien als JSON für dynamisches Druck-CSS und Legende (v3.15.0: neues Datenmodell)
    $cats_for_js = array_map( static function ( $c ) {
        $derived = gsh_tp_color_derive( $c['color'] ?? '#94a3b8' );
        return array(
            'id'     => $c['id']    ?? $c['slug'],
            'slug'   => $c['slug'],
            'label'  => $c['label'],
            'color'  => $c['color'] ?? '#94a3b8',
            'border' => $derived['border'],
            'bg'     => $derived['bg'],
            'text'   => $derived['text'],
        );
    }, gsh_tp_get_categories() );
    $cats_json = esc_attr( wp_json_encode( $cats_for_js ) );

    $o  = gsh_tp_css();

    // Nonce und AJAX-URL für Feedback-AJAX ins Frontend übergeben
    $feedback_nonce = wp_create_nonce( 'gsh_tp_feedback_nonce' );
    $ajax_url       = admin_url( 'admin-ajax.php' );

    $school_name = esc_attr( get_option( 'gsh_tp_school_name', 'Gesamtschule Horst' ) );

    $o .= '<div class="gtp" id="gtp" data-view="quartal" data-changes="' . $changes_json . '" data-categories="' . $cats_json . '" data-school="' . $school_name . '">';

    // Entwurfs-Banner — nicht im Kiosk-Template (dort steht bereits ein Banner).
    if ( ! empty( $profile['is_draft'] ) && ! gsh_tp_draft_kiosk_context() ) {
        $o .= '<div class="gtp-draft-banner">'
            . gsh_tp_icon( 'lock' ) . ' ENTWURF &ndash; Dieser Terminplan ist noch nicht beschlossen.</div>';
    }

    // Frontend-Umschalter: erscheint wenn mehrere aktive (nicht-Entwurf) Profile vorhanden
    $visible_profiles = array_filter( gsh_tp_get_profiles(), function ( $p ) {
        return ! empty( $p['is_active'] ) && empty( $p['is_draft'] );
    } );
    if ( count( $visible_profiles ) > 1 ) {
        $o .= '<div class="gtp-sj-switch">';
        foreach ( $visible_profiles as $vp ) {
            $active_cls = ( $vp['id'] === $profile_id ) ? ' gtp-sj-btn-on' : '';
            $switch_url = esc_url( add_query_arg( 'sj', rawurlencode( $vp['id'] ) ) );
            $o .= '<a href="' . $switch_url . '" class="gtp-sj-btn' . $active_cls . '">'
                . esc_html( $vp['label'] ) . '</a>';
        }
        $o .= '</div>';
    }

    // Header – einzeilig: Logo + Suche + Actions
    $o .= '<div class="gtp-hd">';
    $o .= '<div class="gtp-hd-left">';
    $o .= '<img src="' . esc_url( plugin_dir_url( __FILE__ ) . 'assets/img/curriculr-logo-dark.svg' ) . '" alt="Curricu:lr" class="gtp-header-logo" width="160" height="28" loading="lazy">';
    $o .= '<span class="gtp-subtitle">' . esc_html( $profile['label'] ) . ' &mdash; Deine Schule</span>';
    $o .= '</div>';
    $o .= '<div class="gtp-search-wrap">';
    $o .= gsh_tp_icon( 'search', '1rem', 'gtp-search-icon' );
    $o .= '<input type="search" id="gtp-search-input" class="gtp-search-input"'
        . ' placeholder="Termin suchen…" autocomplete="off"'
        . ' oninput="gtpSearchInput(this.value)" />';
    $o .= '</div>';
    $o .= '<div class="gtp-hd-actions">';
    $o .= '<span class="gtp-meta">Aktualisiert: ' . esc_html( $sync_display ) . ' Uhr</span>';
    $o .= '<button type="button" id="gtp-tour-btn" onclick="gtpHelpOpen()"'
        . ' aria-label="Hilfe öffnen">'
        . gsh_tp_icon( 'help-circle', '1em' ) . '<span>Hilfe</span></button>';
    $o .= '</div>'; // .gtp-hd-actions
    $o .= '</div>'; // .gtp-hd
    $o .= '<div class="gtp-search-results" id="gtp-search-results" style="display:none"></div>';

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

    // Sticky Command Bar: Quartal-Tabs + Jahresansicht-Toggle
    $current_q = gsh_tp_current_q( $profile_id );
    $o .= '<div class="gtp-tabs">';
    $o .= '<div class="gtp-tab-group" role="tablist">';
    for ( $i = 1; $i <= 4; $i++ ) {
        $on      = $i === $aq ? ' gtp-tab-on' : '';
        $now_dot = ( $i === $current_q ) ? '<span class="gtp-tab-now" aria-label="aktuelles Quartal"></span>' : '';
        $o .= '<button type="button" class="gtp-tab' . $on . '" data-q="' . $i
            . '" role="tab" aria-selected="' . ( $i === $aq ? 'true' : 'false' )
            . '" onclick="gtpTab(' . $i . ')">Quartal ' . $i . $now_dot . '</button>';
    }
    $o .= '</div>'; // .gtp-tab-group
    $o .= '<div class="gtp-tab-sep" aria-hidden="true"></div>';
    $o .= '<button type="button" class="gtp-view-toggle" id="gtp-view-toggle"'
        . ' data-label-quartal="Quartalsansicht"'
        . ' data-label-year="Jahresansicht"'
        . ' onclick="gtpViewToggle(this)">'
        . gsh_tp_icon( 'calendar', '1em', 'gtp-view-toggle-icon' )
        . '<span class="gtp-view-toggle-label">Jahresansicht</span></button>';
    $o .= '<button type="button" class="gtp-view-toggle" id="gtp-heatmap-toggle"'
        . ' onclick="gtpHeatmapToggle(this)">'
        . gsh_tp_icon( 'calendar', '1em', 'gtp-view-toggle-icon' )
        . '<span class="gtp-view-toggle-label">Heatmap</span></button>';
    $o .= '</div>'; // .gtp-tabs

    // Filter-Buttons (dynamisch aus Kategorie-Einstellungen – v3.15.0: --btn-color)
    $o .= '<div class="gtp-filt-wrap">';
    $o .= '<div class="gtp-filt-header">';
    $o .= '<span class="gtp-filt-lbl">Filter: <span id="gtp-filt-count" class="gtp-filt-count"></span></span>';
    $o .= '<button type="button" class="gtp-filt-toggle" onclick="gtpFilterToggle()" aria-expanded="true" aria-controls="gtp-filt-body">'
        . gsh_tp_icon( 'chevron-right', '0.9em', 'gtp-filt-chevron' ) . '</button>';
    $o .= '<button type="button" id="gtp-reset" class="gtp-reset" onclick="gtpReset()" style="display:none">'
        . gsh_tp_icon( 'x', '0.85em' ) . ' Alle anzeigen</button>';
    $o .= '</div>'; // .gtp-filt-header
    $o .= '<div class="gtp-filt gtp-filt-open" id="gtp-filt-body">';
    foreach ( gsh_tp_get_categories() as $cat ) {
        $color      = $cat['color'] ?? '#94a3b8';
        $text_color = gsh_tp_contrast_color( $color );
        $o .= '<button type="button" class="gtp-fb gtp-fb-on" data-c="'
            . esc_attr( $cat['slug'] ) . '" style="--btn-color:' . esc_attr( $color ) . ';--btn-text:' . esc_attr( $text_color ) . '"'
            . ' onclick="gtpFil(this)" aria-pressed="true">'
            . esc_html( $cat['label'] ) . '</button>';
    }
    // „Sonstige"-Button für Termine ohne Kategorie-Match
    $o .= '<button type="button" class="gtp-fb gtp-fb-on" data-c="standard" onclick="gtpFil(this)" aria-pressed="true">Sonstige</button>';
    $o .= '</div>'; // #gtp-filt-body
    $kiosk_groups = isset( $GLOBALS['gsh_tp_group_union'] ) ? (array) $GLOBALS['gsh_tp_group_union'] : array();
    if ( $kiosk_groups ) {
        $o .= '<div class="gtp-filt gtp-filt-open gtp-grp-row" id="gtp-grp-body">';
        $o .= '<span class="gtp-grp-label">Gruppen:</span>';
        foreach ( $kiosk_groups as $g ) {
            $o .= '<button type="button" class="gtp-gb gtp-gb-on" data-g="' . esc_attr( $g )
                . '" onclick="gtpGrpFil(this)" aria-pressed="true">' . esc_html( $g ) . '</button>';
        }
        $o .= '</div>';
    }
    $o .= '</div>'; // .gtp-filt-wrap

    // Quartalspanels
    $o .= '<div class="gtp-quarters-wrap">';
    for ( $q = 1; $q <= 4; $q++ ) {
        $vis = $q === $aq ? 'block' : 'none';
        $qd  = $grenzen[ $q - 1 ] ?? null;
        if ( ! $qd ) {
            continue;
        }
        $o .= '<div class="gtp-qp" id="gtp-q' . $q . '" style="display:' . $vis . '">';
        $o .= '<h3 class="gtp-qt">' . $qlbl[ $q ] . '</h3>';
        $o .= gsh_tp_table( $date_index, $qd, $sjs, $annotation_map );   // Desktop-Tabelle (≥ 768px)
        $o .= gsh_tp_mobile( $date_index, $qd, $sjs, $annotation_map );  // Agenda-Ansicht  (< 768px)
        $o .= '</div>';
    }
    $o .= '</div>'; // .gtp-quarters-wrap

    // Jahresansicht
    $o .= gsh_tp_yearview( $date_index, $grenzen, $profile_id );

    // Footer – Aktionen links, Meta rechts
    $o .= '<div class="gtp-ft">';
    $o .= '<div class="gtp-ft-actions">';
    $o .= '<button type="button" class="gtp-btn gtp-btn-pdf" onclick="gtpPdf(this)">' . gsh_tp_icon( 'file-text' ) . ' Quartal PDF</button>';
    $o .= '<button type="button" class="gtp-btn gtp-btn-pdf" onclick="gtpPdfAll(this)">' . gsh_tp_icon( 'file-text' ) . ' Alle PDF</button>';
    $o .= '<button type="button" class="gtp-btn gtp-btn-feedback" id="gtp-feedback-btn"'
        . ' onclick="gtpFeedbackOpen()" aria-label="Feedback geben">'
        . gsh_tp_icon( 'message-circle' ) . ' Feedback</button>';
    // Abo nur für Curriculr-verwaltete Kalender — bei manuell konfigurierten Profilen
    // zeigt ical_url auf die (ggf. private) IServ-Fetch-Quelle des Admins.
    //
    // webcal:// löst nur dann direkt ein Kalender-Abo aus, wenn das Betriebssystem einen
    // Handler dafür registriert hat (macOS/iOS: automatisch via Kalender-App; Windows/Linux/
    // die meisten Browser: i. d. R. NICHT ohne manuelle Einrichtung). Ohne Handler behandelt
    // der Browser den Link faktisch wie https:// und lädt die Datei herunter statt zu
    // abonnieren. Deshalb: webcal-Link als Versuch anbieten, aber Copy-URL-Fallback mit
    // Anleitung als verlässlichen, plattformunabhängigen Weg gleichwertig danebenstellen.
    if ( ! empty( $profile['managed'] ) && ! empty( $profile['ical_url'] ) ) {
        $ics_url    = $profile['ical_url'];
        $webcal_url = preg_replace( '#^https?://#i', 'webcal://', $ics_url );
        $o .= '<div class="gtp-ics-group">';
        $o .= '<a href="' . esc_url( $webcal_url ) . '" class="gtp-btn gtp-btn-ics"'
            . ' aria-label="Kalender abonnieren (funktioniert nur mit eingerichteter Kalender-App)">'
            . gsh_tp_icon( 'bell' ) . ' Kalender abonnieren</a>';
        $o .= '<button type="button" class="gtp-btn gtp-btn-ics" data-feed-url="' . esc_url( $ics_url ) . '"'
            . ' onclick="gtpCopyFeed(this)" aria-label="Feed-URL kopieren">'
            . gsh_tp_icon( 'link' ) . ' Feed-URL kopieren</button>';
        // Ausklappbare Kurzanleitung — ohne die geht ein Teil des Kollegiums davon aus,
        // der Klick allein reiche, und wundert sich dann über die heruntergeladene Datei
        // statt ein laufendes Abo zu haben.
        $o .= '<details class="gtp-ics-help">';
        $o .= '<summary>' . gsh_tp_icon( 'chevron-right', '0.85em', 'gtp-ics-chevron' ) . ' Wie funktioniert das Abo?</summary>';
        $o .= '<div class="gtp-ics-help-body">';
        $o .= '<p>„Kalender abonnieren" anklicken. Öffnet sich deine Kalender-App? Dann bist du fertig — der Terminplan aktualisiert sich ab jetzt von selbst, sobald sich etwas ändert.</p>';
        $o .= '<p>Passiert nichts oder wird stattdessen nur eine Datei heruntergeladen? Dann „Feed-URL kopieren" klicken und die Adresse manuell in deiner Kalender-App eintragen:</p>';
        $o .= '<ul>';
        $o .= '<li><strong>Outlook:</strong> Kalender hinzufügen &rarr; Aus dem Internet abonnieren &rarr; Adresse einfügen</li>';
        $o .= '<li><strong>Apple Kalender:</strong> Ablage &rarr; Neues Kalenderabo &rarr; Adresse einfügen</li>';
        $o .= '<li><strong>Google Kalender:</strong> „Weitere Kalender" (+) &rarr; Per URL &rarr; Adresse einfügen</li>';
        $o .= '<li><strong>Thunderbird:</strong> Neuer Kalender &rarr; Im Netzwerk &rarr; iCalendar (ICS) &rarr; Adresse einfügen</li>';
        $o .= '</ul>';
        $o .= '</div>'; // .gtp-ics-help-body
        $o .= '</details>';
        $o .= '</div>'; // .gtp-ics-group
    }
    $o .= '</div>'; // .gtp-ft-actions
    $o .= '<div class="gtp-ft-meta">';
    $o .= '<span class="gtp-src">Quelle: IServ-Kalender</span>';
    $o .= '<button type="button" class="gtp-version-btn"'
        . ' onclick="gtpChangelogOpen()" aria-label="Changelog anzeigen">'
        . 'v' . esc_html( GSH_TP_VERSION ) . '</button>';
    $o .= '</div>'; // .gtp-ft-meta
    $o .= '</div>'; // .gtp-ft

    // ── Feedback-Modal (wp_mail, seit 3.12.0) ────────────────────────────────
    $o .= '<div id="gtp-feedback-overlay" class="gtp-popup-overlay" role="dialog"'
        . ' aria-modal="true" aria-labelledby="gtp-feedback-title"'
        . ' style="display:none" tabindex="-1">';
    $o .= '<div class="gtp-popup-card gtp-feedback-card">';
    $o .= '<button type="button" class="gtp-popup-close" onclick="gtpFeedbackClose()"'
        . ' aria-label="Schließen">&times;</button>';
    $o .= '<h3 class="gtp-popup-title" id="gtp-feedback-title">&#128172; Feedback geben</h3>';
    $o .= '<p class="gtp-feedback-intro">Dein Hinweis hilft uns den Terminplan zu verbessern.</p>';
    // Honeypot: verstecktes Anti-Spam-Feld (muss leer bleiben)
    $o .= '<input type="text" name="gsh_tp_hp" id="gtp-feedback-hp" '
        . 'autocomplete="off" tabindex="-1" '
        . 'style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0" '
        . 'aria-hidden="true">';
    // Typ-Auswahl
    $o .= '<div class="gtp-feedback-types" role="group" aria-label="Feedback-Typ wählen">';
    $types = array(
        'bug'    => array( 'emoji' => '&#128027;', 'label' => 'Fehler melden' ),
        'wish'   => array( 'emoji' => '&#128161;', 'label' => 'Funktionswunsch' ),
        'praise' => array( 'emoji' => '&#128077;', 'label' => 'Lob' ),
        'other'  => array( 'emoji' => '&#128172;', 'label' => 'Sonstiges' ),
    );
    foreach ( $types as $key => $t ) {
        $o .= '<button type="button" class="gtp-feedback-type" data-type="' . esc_attr( $key ) . '"'
            . ' onclick="gtpFeedbackType(this)">'
            . $t['emoji'] . ' ' . esc_html( $t['label'] ) . '</button>';
    }
    $o .= '</div>';
    // Optionales Absender-Feld
    $o .= '<div class="gtp-feedback-field">';
    $o .= '<label for="gtp-feedback-sender" class="gtp-feedback-label">Dein Name <span style="font-weight:400;text-transform:none;letter-spacing:0">(optional)</span></label>';
    $o .= '<input type="text" id="gtp-feedback-sender" class="gtp-feedback-textarea" '
        . 'style="min-height:auto;resize:none;padding:8px 14px" '
        . 'maxlength="80" placeholder="z. B. Frau Muster" autocomplete="name">';
    $o .= '</div>';
    // Freitextfeld
    $o .= '<div class="gtp-feedback-field">';
    $o .= '<label for="gtp-feedback-text" class="gtp-feedback-label">Dein Anliegen</label>';
    $o .= '<textarea id="gtp-feedback-text" class="gtp-feedback-textarea"'
        . ' rows="4" maxlength="1000"'
        . ' placeholder="Was ist aufgefallen? Was wünschst du dir?"></textarea>';
    $o .= '<div class="gtp-feedback-counter"><span id="gtp-feedback-count">0</span> / 1000</div>';
    $o .= '</div>';
    // Status-Meldung (per JS befüllt)
    $o .= '<div id="gtp-feedback-status" class="gtp-feedback-status" style="display:none"></div>';
    // Aktions-Buttons
    $o .= '<div class="gtp-feedback-actions">';
    $o .= '<button type="button" class="gtp-btn" id="gtp-feedback-submit"'
        . ' onclick="gtpFeedbackSubmit()" disabled>Absenden</button>';
    $o .= '<button type="button" class="gtp-btn gtp-btn-sec" onclick="gtpFeedbackClose()">Abbrechen</button>';
    $o .= '</div>';
    $o .= '</div>'; // .gtp-feedback-card
    $o .= '</div>'; // #gtp-feedback-overlay

    $o .= '<button type="button" id="gtp-heute-btn" onclick="gtpScrollToday()" aria-label="Zur heutigen Woche springen">' . gsh_tp_icon( 'map-pin' ) . ' Heute</button>';

    // ── Theme-Switcher (schwebendes Gear-Icon unten rechts) ──────────────────
    $o .= '<div id="gtp-theme-wrap" aria-label="Theme-Einstellungen">';
    $o .= '<div id="gtp-theme-panel" role="group" aria-label="Theme wählen">';
    $o .= '<button type="button" class="gtp-theme-opt" data-gtp-mode="light" '
        . 'onclick="gtpSetTheme(\'light\')" aria-label="Helles Theme">'
        . '<span class="gtp-theme-opt-icon">' . gsh_tp_icon( 'sun', '1em' ) . '</span> Hell'
        . '</button>';
    $o .= '<button type="button" class="gtp-theme-opt" data-gtp-mode="dark" '
        . 'onclick="gtpSetTheme(\'dark\')" aria-label="Dunkles Theme">'
        . '<span class="gtp-theme-opt-icon">' . gsh_tp_icon( 'moon', '1em' ) . '</span> Dunkel'
        . '</button>';
    $o .= '<button type="button" class="gtp-theme-opt" data-gtp-mode="auto" '
        . 'onclick="gtpSetTheme(\'auto\')" aria-label="System-Theme">'
        . '<span class="gtp-theme-opt-icon">' . gsh_tp_icon( 'monitor', '1em' ) . '</span> System'
        . '</button>';
    $o .= '</div>'; // #gtp-theme-panel
    $o .= '<button type="button" id="gtp-theme-btn" onclick="gtpThemeToggle()" '
        . 'aria-label="Theme wechseln" aria-expanded="false">'
        . gsh_tp_icon( 'settings', '1.1em' )
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
    $cl_frontend_tags = array( 'FEATURE', 'UX', 'BUGFIX', 'SECURITY', 'DESIGN', 'INFRA' );
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

    // ── Hilfe-Overlay (seit 3.16.0, ersetzt Shepherd.js-Tour) ───────────────────
    $o .= '<div id="gtp-help-overlay" class="gtp-help-overlay" role="dialog"'
        . ' aria-modal="true" aria-label="Hilfe &amp; Funktionsübersicht" hidden>';
    $o .= '<div class="gtp-help-panel">';
    $o .= '<div class="gtp-help-header">';
    $o .= '<h2 class="gtp-help-title">' . gsh_tp_icon( 'help-circle', '1.1em' ) . ' Hilfe &amp; Funktionsübersicht</h2>';
    $o .= '<button type="button" class="gtp-help-close" onclick="gtpHelpClose()"'
        . ' aria-label="Hilfe schließen">' . gsh_tp_icon( 'x' ) . '</button>';
    $o .= '</div>'; // .gtp-help-header
    $o .= '<div class="gtp-help-body">';
    $help_sections = array(
        array( 'icon' => 'calendar',   'title' => 'Quartal-Navigation',
               'text' => 'Die vier Tabs oben zeigen die Schulquartale. Das aktuelle Quartal wird beim Öffnen automatisch angezeigt. Ein Klick wechselt das Quartal.' ),
        array( 'icon' => 'tag',        'title' => 'Kategorien filtern',
               'text' => 'Die farbigen Buttons unter den Quartal-Tabs sind Kategorie-Filter. Ein Klick blendet eine Kategorie ein oder aus – z.B. nur Konferenzen oder nur Ferientermine anzeigen.' ),
        array( 'icon' => 'search',     'title' => 'Terminsuche',
               'text' => 'Das Suchfeld durchsucht alle Quartale gleichzeitig. Treffer werden sofort hervorgehoben. Die Suche ignoriert Groß-/Kleinschreibung.' ),
        array( 'icon' => 'file-text',  'title' => 'PDF-Export',
               'text' => 'Mit dem PDF-Button unten rechts kannst du das aktuelle Quartal oder alle vier Quartale als druckbares PDF speichern.' ),
        array( 'icon' => 'moon',       'title' => 'Dark Mode',
               'text' => 'Das Zahnrad-Symbol unten rechts öffnet die Anzeigeeinstellungen. Dort kannst du zwischen hellem Design, dunklem Design oder automatischer Anpassung an dein Gerät wählen.' ),
        array( 'icon' => 'info',       'title' => 'Farb-Legende',
               'text' => 'Jede Kategorie hat eine eigene Farbe. Termine werden automatisch anhand ihrer Beschreibung zugeordnet. Die Legende entspricht den farbigen Filter-Buttons.' ),
    );
    foreach ( $help_sections as $s ) {
        $o .= '<div class="gtp-help-section">';
        $o .= '<div class="gtp-help-section-icon">' . gsh_tp_icon( $s['icon'], '1.2em' ) . '</div>';
        $o .= '<div class="gtp-help-section-body">';
        $o .= '<strong>' . esc_html( $s['title'] ) . '</strong>';
        $o .= '<p>' . esc_html( $s['text'] ) . '</p>';
        $o .= '</div>';
        $o .= '</div>';
    }
    $o .= '</div>'; // .gtp-help-body
    $o .= '<div class="gtp-help-footer">';
    $o .= '<span class="gtp-help-version">Schul-Terminplan Dashboard v' . GSH_TP_VERSION . ' &middot; Deine Schule</span>';
    $o .= '</div>';
    $o .= '</div>'; // .gtp-help-panel
    $o .= '</div>'; // #gtp-help-overlay

    // Versteckte Felder für JS (AJAX-URL und Nonce)
    $o .= '<input type="hidden" id="gtp-ajax-url" value="' . esc_attr( $ajax_url ) . '">';
    $o .= '<input type="hidden" id="gtp-feedback-nonce" value="' . esc_attr( $feedback_nonce ) . '">';

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
    $lbl     = gsh_tp_event_time_label( $ev );
    $summary = $lbl['summary'];
    $time    = $lbl['time'];

    $desc    = $ev['description'];
    $loc     = isset( $ev['location'] ) ? $ev['location'] : '';
    // v3.15.0: data-cat = Slug der primären Kategorie (aus dem Array, nicht via gsh_tp_cat)
    $cat_ids = (array) ( $ev['categories'] ?? array() );
    static $cat_map_attr = null;
    if ( null === $cat_map_attr ) {
        $cat_map_attr = array_column( gsh_tp_get_categories(), null, 'id' );
    }
    $cat = gsh_tp_primary_slug( $cat_ids, $cat_map_attr );

    $attrs  = ' data-summary="' . esc_attr( $summary ) . '"';
    $attrs .= ' data-date="'    . esc_attr( $ev['start'] ) . '"';
    $attrs .= ' data-end="'     . esc_attr( $ev['end'] ) . '"';
    $attrs .= ' data-time="'    . esc_attr( $time ) . '"';
    $attrs .= ' data-location="' . esc_attr( wp_strip_all_tags( $loc ) ) . '"';
    $attrs .= ' data-desc="'    . esc_attr( wp_strip_all_tags( $desc ) ) . '"';
    $attrs .= ' data-cat="'     . esc_attr( $cat ) . '"';
    $attrs .= ' data-allday="'  . ( $ev['allday'] ? '1' : '0' ) . '"';
    $attrs .= ' data-uid="'     . esc_attr( isset( $ev['uid'] ) ? $ev['uid'] : '' ) . '"';

    $groups = (array) ( $ev['groups'] ?? array() );
    if ( $groups ) {
        $attrs .= ' data-groups="' . esc_attr( implode( '|', $groups ) ) . '"';
    }

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
 * @param  string $sjs            Erster Montag des Schuljahres (Y-m-d) für Schulwochenberechnung.
 * @param  array  $annotation_map Optionale Map: 0-basierter Schulwochenindex → Anmerkungstext.
 * @return string                 HTML-String der kompletten Quartalstabelle.
 */
function gsh_tp_table( $index, $qd, $sjs, $annotation_map = array() ) {
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
    $h .= '<th class="gh">Anmerkungen</th>';
    $h .= '</tr></thead><tbody>';

    $c   = clone $qs;
    $lim = 50; // Sicherheit gegen Endlosschleifen

    // Spaltenbezeichner für data-label (Card-Layout im Bereich 1024–1200 px)
    $day_labels = array( 'Mo', 'Di', 'Mi', 'Do', 'Fr' );

    while ( $c <= $qe && $lim-- > 0 ) {
        $sw      = gsh_tp_schulwoche( $c->format( 'Y-m-d' ), $sjs );
        $friday  = ( clone $c )->modify( '+4 days' )->format( 'Y-m-d' );
        $h .= $friday < $td ? '<tr class="gtp-past">' : '<tr>';
        $h .= '<td class="gs" data-label="' . esc_attr__( 'SW', 'gsh-terminplan' ) . '"><b>' . ( $sw >= 0 ? sprintf( '%02d', $sw ) : '–' ) . '</b></td>';

        // ── Vorarbeiten: Lange Termine dieser Woche für die Hinweise-Spalte sammeln ──
        // Ein Termin gilt als "lang", wenn er >= 5 Tage dauert (ganze Woche oder länger).
        $hinweise_keys = array(); // Duplikate über Wochentage hinweg verhindern
        $hinweise_long = array(); // Lange Termine → nur in Hinweise, nicht in Tagesspalte
        // $hinweise_frist entfernt v3.15.0 – keine 'frist'-Kategorie mehr im neuen Modell

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

            // Ferientag erkennen – 'feiertage'-Kategorie markiert den Tag grau (v3.15.0)
            $hol = false;
            foreach ( $de as $ev ) {
                if ( in_array( 'feiertage', (array) ( $ev['categories'] ?? array() ), true ) ) {
                    $hol = true;
                    break;
                }
            }
            if ( $hol ) {
                $cl .= ' gt-hol';
            }

            $h .= '<td class="' . esc_attr( $cl ) . '" data-label="' . esc_attr( $day_labels[ $d ] ) . '">';
            $h .= '<span class="gdl">' . esc_html( $dy->format( 'd.m.' ) ) . '</span>';

            // Kategorie-Map einmalig aufbauen (gecacht via gsh_tp_get_categories())
            static $cat_map_table = null;
            if ( null === $cat_map_table ) {
                $cat_map_table = array_column( gsh_tp_get_categories(), null, 'id' );
            }

            foreach ( $de as $ev ) {
                // Lange Termine (>= 5 Tage) erscheinen nur in der Hinweise-Spalte
                if ( gsh_tp_event_duration( $ev ) >= 5 ) {
                    continue;
                }

                $cat_ids  = (array) ( $ev['categories'] ?? array() );
                $cc       = gsh_tp_primary_slug( $cat_ids, $cat_map_table );
                $data_cats = esc_attr( implode( ',', $cat_ids ) );
                $lbl       = gsh_tp_event_time_label( $ev );
                $time_html = $lbl['time'] ? '<span class="ge-time">' . esc_html( $lbl['time'] ) . '</span>' : '';
                $h .= '<div class="ge gc-' . esc_attr( $cc )
                    . '" data-c="' . esc_attr( $cc )
                    . '" data-categories="' . $data_cats
                    . '" title="' . esc_attr( $ev['summary'] ) . '"'
                    . ' onclick="gtpPopupOpen(this)"'
                    . gsh_tp_event_data_attrs( $ev ) . '>'
                    . $time_html . '<span class="ge-title">' . esc_html( $lbl['summary'] ) . '</span></div>';
            }

            $h .= '</td>';
        }

        // ── Hinweise-Spalte rendern ──
        $h .= '<td class="gh gnc" data-label="' . esc_attr__( 'Anmerkungen', 'gsh-terminplan' ) . '">';

        // Lange Termine (mit Kategorie-Farbe und Datumsbereich) – v3.15.0: Array-Slugs
        foreach ( $hinweise_long as $ev ) {
            $cat_ids  = (array) ( $ev['categories'] ?? array() );
            $cc       = gsh_tp_primary_slug( $cat_ids, $cat_map_table );
            $data_cats = esc_attr( implode( ',', $cat_ids ) );
            $tt       = $ev['description'] ? $ev['description'] : $ev['summary'];
            $eff_end  = new DateTime( $ev['end'] );
            if ( $ev['allday'] && $ev['end'] > $ev['start'] ) {
                $eff_end->modify( '-1 day' );
            }
            $range = ( new DateTime( $ev['start'] ) )->format( 'd.m.' )
                   . '&ndash;' . $eff_end->format( 'd.m.' );
            $groups_long = (array) ( $ev['groups'] ?? array() );
            $h .= '<div class="gn-long gc-' . esc_attr( $cc )
                . '" data-c="' . esc_attr( $cc )
                . '" data-categories="' . $data_cats . '"'
                . ( $groups_long ? ' data-groups="' . esc_attr( implode( '|', $groups_long ) ) . '"' : '' )
                . ' title="' . esc_attr( wp_strip_all_tags( $tt ) ) . '">'
                . '<span class="gn-range">' . $range . '</span>'
                . esc_html( $ev['summary'] )
                . '</div>';
        }

        // Planner-Anmerkung (0-basierter Index = SW-Nummer direkt)
        if ( $sw >= 0 && isset( $annotation_map[ $sw ] ) ) {
            $h .= '<div class="gn-annotation">' . esc_html( $annotation_map[ $sw ] ) . '</div>';
        }

        $h .= '</td>';
        $h .= '</tr>';
        $c->modify( '+7 days' );
    }

    $h .= '</tbody></table>';
    return '<div class="gtp-tbl-scroll">' . $h . '</div>';
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
function gsh_tp_mobile( $index, $qd, $sjs, $annotation_map = array() ) {
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
        $h .= '<span>' . ( $sw >= 0 ? 'Schulwoche&nbsp;' . sprintf( '%02d', $sw ) : '&ndash;' ) . '</span>';
        $h .= '<span class="gtp-mob-wh-sub">'
            . esc_html( $monday_dt->format( 'd.m.' ) )
            . ' &ndash; '
            . esc_html( $friday_dt->format( 'd.m.' ) )
            . '</span>';
        $h .= '</div>';

        // Planner-Anmerkung unterhalb des Wochenkopfs
        if ( $sw >= 0 && isset( $annotation_map[ $sw ] ) ) {
            $h .= '<div class="gtp-mob-annotation">' . esc_html( $annotation_map[ $sw ] ) . '</div>';
        }

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
            // Ferientag erkennen: 'feiertage'-Kategorie (v3.15.0)
            $is_hol = false;
            foreach ( $de as $ev ) {
                if ( in_array( 'feiertage', (array) ( $ev['categories'] ?? array() ), true ) ) {
                    $is_hol = true;
                    break;
                }
            }

            // Kategorie-Map einmalig aufbauen (gecacht via gsh_tp_get_categories())
            static $cat_map_mob = null;
            if ( null === $cat_map_mob ) {
                $cat_map_mob = array_column( gsh_tp_get_categories(), null, 'id' );
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
                $cat_ids   = (array) ( $ev['categories'] ?? array() );
                $cc        = gsh_tp_primary_slug( $cat_ids, $cat_map_mob );
                $data_cats = esc_attr( implode( ',', $cat_ids ) );
                $lbl       = gsh_tp_event_time_label( $ev );
                $time_html = $lbl['time'] ? '<span class="ge-time">' . esc_html( $lbl['time'] ) . '</span>' : '';
                $h .= '<div class="ge gc-' . esc_attr( $cc )
                    . '" data-c="' . esc_attr( $cc )
                    . '" data-categories="' . $data_cats
                    . '" title="' . esc_attr( $ev['summary'] ) . '"'
                    . ' onclick="gtpPopupOpen(this)"'
                    . gsh_tp_event_data_attrs( $ev ) . '>'
                    . $time_html . '<span class="ge-title">' . esc_html( $lbl['summary'] ) . '</span></div>';
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

/**
 * Rendert das Jahresraster (Sep–Jul, 10 Monate × 31 Tage).
 * Nutzt den bestehenden Date-Index; kein eigener iCal-Fetch.
 *
 * @param array  $index    Date-Index aus gsh_tp_build_date_index().
 * @param array  $quartale Array mit 4 Quartalsgrenzen [['start'=>'Y-m-d','end'=>'Y-m-d'], …].
 * @param string $pid      Profil-ID (für zukünftige Erweiterungen, derzeit unused).
 * @return string HTML-String des Jahresrasters.
 */
function gsh_tp_yearview( $index, $quartale, $pid ) {
    if ( empty( $quartale ) || empty( $index ) ) {
        return '';
    }

    // Schuljahr: Sep des Q1-Startjahres bis Jul des Folgejahres
    $start_year = (int) substr( $quartale[0]['start'], 0, 4 );
    $months = array(
        array( 'n' => 9,  'year' => $start_year,     'label' => 'Sep' ),
        array( 'n' => 10, 'year' => $start_year,     'label' => 'Okt' ),
        array( 'n' => 11, 'year' => $start_year,     'label' => 'Nov' ),
        array( 'n' => 12, 'year' => $start_year,     'label' => 'Dez' ),
        array( 'n' => 1,  'year' => $start_year + 1, 'label' => 'Jan' ),
        array( 'n' => 2,  'year' => $start_year + 1, 'label' => 'Feb' ),
        array( 'n' => 3,  'year' => $start_year + 1, 'label' => 'Mär' ),
        array( 'n' => 4,  'year' => $start_year + 1, 'label' => 'Apr' ),
        array( 'n' => 5,  'year' => $start_year + 1, 'label' => 'Mai' ),
        array( 'n' => 6,  'year' => $start_year + 1, 'label' => 'Jun' ),
        array( 'n' => 7,  'year' => $start_year + 1, 'label' => 'Jul' ),
    );

    $today_str = gmdate( 'Y-m-d' );

    // Kategorie-Map für gsh_tp_primary_slug (gecacht per static)
    static $cat_map_yr = null;
    if ( null === $cat_map_yr ) {
        $cat_map_yr = array_column( gsh_tp_get_categories(), null, 'id' );
    }

    $o  = '<div class="gtp-year-wrap" role="region" aria-label="Jahresterminplan">';
    $o .= '<table class="gtp-yr">';

    // Kopfzeile: Monatsnamen
    $o .= '<thead><tr><th class="gtp-yr-dh" scope="col" aria-label="Tag"></th>';
    foreach ( $months as $m ) {
        $o .= '<th class="gtp-yr-mh" scope="col">' . esc_html( $m['label'] ) . '</th>';
    }
    $o .= '</tr></thead>';

    // Zeilen: Tage 1–31
    $o .= '<tbody>';
    for ( $day = 1; $day <= 31; $day++ ) {
        // Prüfen ob mind. ein Monat diesen Tag hat (sonst Zeile überspringen)
        $row_has_valid = false;
        foreach ( $months as $m ) {
            if ( checkdate( $m['n'], $day, $m['year'] ) ) {
                $row_has_valid = true;
                break;
            }
        }

        // Heutige Zeile bestimmen
        $is_today_row = false;
        if ( $row_has_valid ) {
            foreach ( $months as $m ) {
                if ( checkdate( $m['n'], $day, $m['year'] ) ) {
                    $d = sprintf( '%04d-%02d-%02d', $m['year'], $m['n'], $day );
                    if ( $d === $today_str ) {
                        $is_today_row = true;
                        break;
                    }
                }
            }
        }

        $row_cls = $is_today_row ? ' class="gtp-yr-today"' : '';
        $o .= '<tr' . $row_cls . '>';
        $o .= '<th class="gtp-yr-dn" scope="row">' . (int) $day . '</th>';

        foreach ( $months as $m ) {
            if ( ! checkdate( $m['n'], $day, $m['year'] ) ) {
                $o .= '<td class="gtp-yr-cell gtp-yr-invalid" aria-hidden="true"></td>';
                continue;
            }

            $date_str = sprintf( '%04d-%02d-%02d', $m['year'], $m['n'], $day );
            $events   = gsh_tp_day_events( $index, $date_str );
            $o       .= '<td class="gtp-yr-cell">';

            foreach ( $events as $ev ) {
                $cat_ids = (array) ( isset( $ev['categories'] ) ? $ev['categories'] : array() );
                $slug    = gsh_tp_primary_slug( $cat_ids, $cat_map_yr );
                $short   = esc_html( mb_substr( wp_strip_all_tags( $ev['summary'] ), 0, 18 ) );

                $o .= '<span class="gtp-yr-ev gc-' . esc_attr( $slug ) . '"'
                    . ' data-c="' . esc_attr( $slug ) . '"'
                    . gsh_tp_event_data_attrs( $ev )
                    . ' onclick="gtpPopupOpen(this)">'
                    . $short . '</span>';
            }

            $o .= '</td>';
        }

        $o .= '</tr>';
    }
    $o .= '</tbody></table>';

    /* Heatmap-Block (Mobile Jahresansicht v4.4.0) */
    $o .= '<div class="gtp-yr-heatmap" role="presentation" aria-hidden="true">';
    foreach ( $months as $m ) {
        $o .= '<div class="gtp-yr-hm-month">';
        $o .= '<div class="gtp-yr-hm-label">' . esc_html( strtoupper( $m['label'] ) ) . '</div>';
        $o .= '<div class="gtp-yr-hm-grid">';
        for ( $day = 1; $day <= 31; $day++ ) {
            if ( ! checkdate( $m['n'], $day, $m['year'] ) ) {
                $o .= '<div class="gtp-yr-hm-sq" data-valid="0" aria-hidden="true"></div>';
                continue;
            }
            $date_str = sprintf( '%04d-%02d-%02d', $m['year'], $m['n'], $day );
            $evs      = gsh_tp_day_events( $index, $date_str );
            $slugs    = array();
            foreach ( $evs as $ev ) {
                $cids = (array) ( $ev['categories'] ?? array() );
                foreach ( $cids as $cid ) {
                    if ( isset( $cat_map_yr[ $cid ] ) ) {
                        $slugs[ $cat_map_yr[ $cid ]['slug'] ] = true;
                    }
                }
            }
            $today_cls = ( $date_str === $today_str ) ? ' gtp-yr-hm-sq--today' : '';
            $cats_attr = ! empty( $slugs )
                ? ' data-cats="' . esc_attr( implode( ' ', array_keys( $slugs ) ) ) . '"'
                : '';
            $o .= '<div class="gtp-yr-hm-sq' . $today_cls . '"'
                . ' data-valid="1"'
                . ' data-date="' . esc_attr( $date_str ) . '"'
                . $cats_attr . '></div>';
        }
        $o .= '</div>'; /* .gtp-yr-hm-grid */
        $o .= '<div class="gtp-yr-hm-expand" hidden></div>';
        $o .= '</div>'; /* .gtp-yr-hm-month */
    }
    $o .= '<div class="gtp-yr-hm-legend">';
    foreach ( gsh_tp_get_categories() as $cat ) {
        $color = $cat['color'] ?? '#94a3b8';
        $o    .= '<span class="gtp-yr-hm-legend-item">'
            . '<span class="gtp-yr-hm-legend-dot" style="background:' . esc_attr( $color ) . '"></span>'
            . esc_html( $cat['label'] )
            . '</span>';
    }
    $o .= '</div>'; /* .gtp-yr-hm-legend */
    $o .= '</div>'; /* .gtp-yr-heatmap */

    $o .= '</div>'; /* .gtp-year-wrap */
    return $o;
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
  --gtp-accent:       var(--marine-700, #00467D);
  --gtp-accent-dark:  var(--marine-800, #00345C);
  --gtp-accent-light: var(--marine-100, #E6F4FF);
  /* Oberflächen & Text */
  --gtp-text:         var(--text-main, #1e293b);
  --gtp-text-muted:   var(--text-muted, #475569);
  --gtp-text-faint:   #94a3b8;
  --gtp-bg:           var(--bg-surface-solid, #ffffff);
  --gtp-surface:      var(--bg-muted, #f8fafc);
  --gtp-border:       var(--border-strong, #cbd5e1);
  /* Heute-Akzent */
  --gtp-today-bg:     #E6F4FF;
  --gtp-today-bd:     #00467D;
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
  --gtp-radius-sm:   var(--radius-sm, 6px);
  --gtp-radius-md:   var(--radius-btn, 10px);
  --gtp-radius-lg:   var(--radius-card, 14px);
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
  font-family:var(--font-body, -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif);
  max-width:1200px;margin:0 auto;color:var(--gtp-text);
  background:var(--gtp-bg);border-radius:16px;
  box-shadow:0 4px 24px rgba(0,0,0,.08),0 1px 4px rgba(0,0,0,.04);
  padding:1.75rem 2rem;
}

/* ── Header ── */
.gtp-hd{
  display:flex;align-items:center;gap:1rem;
  padding-bottom:.875rem;border-bottom:2px solid var(--gtp-border);margin-bottom:0;
}
.gtp-hd-left{display:flex;align-items:center;gap:.75rem;flex-shrink:0}
.gtp-hd-actions{display:flex;align-items:center;gap:.75rem;flex-shrink:0;margin-left:auto}
.gtp-hd .gtp-search-wrap{flex:1;max-width:360px;position:relative;display:flex;align-items:center}
.gtp-search-icon{position:absolute;left:14px;pointer-events:none;display:flex;align-items:center;color:var(--gtp-text-muted);flex-shrink:0}
.gtp-t{font-size:1.6rem;font-weight:800;color:var(--gtp-text);margin:0;letter-spacing:-.03em;line-height:1.15}
.gtp-subtitle{font-size:.8rem;color:var(--gtp-text-muted);font-weight:400;letter-spacing:.01em}
.gtp-meta{font-size:.72rem;color:var(--gtp-text-faint);white-space:nowrap;font-variant-numeric:tabular-nums}
/* Schuljahr-Umschalter */
.gtp-sj-switch{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}
.gtp-sj-btn{
  padding:4px 14px;border-radius:20px;border:1.5px solid var(--gtp-accent);
  font-size:.8rem;font-weight:600;text-decoration:none;color:var(--gtp-accent);
  transition:var(--gtp-tr-fast);
}
.gtp-sj-btn:hover{background:var(--gtp-accent-light)}
.gtp-sj-btn-on{background:var(--gtp-accent);color:#fff}
.gtp-sj-btn-on:hover{background:var(--gtp-accent-dark)}
/* Hilfe-Button */
#gtp-tour-btn{
  height:36px;border-radius:20px;padding:0 14px;gap:6px;
  border:1.5px solid var(--gtp-border);background:transparent;
  color:var(--gtp-text-muted);cursor:pointer;font-size:.75rem;font-weight:600;
  display:flex;align-items:center;justify-content:center;
  flex-shrink:0;transition:var(--gtp-tr-fast);
}
#gtp-tour-btn:hover{border-color:var(--gtp-accent);color:var(--gtp-accent);background:var(--gtp-accent-light)}
/* Entwurfs-Banner */
.gtp-draft-banner{
  display:flex;align-items:center;gap:.5rem;
  background:#fef9c3;border:1px solid #eab308;
  padding:10px 16px;border-radius:8px;margin-bottom:16px;
  font-weight:600;color:#92400e;
}

/* ── Sticky Command Bar (Tabs + Jahresansicht-Toggle) ── */
.gtp-tabs{
  display:flex;gap:0;
  position:sticky;top:0;z-index:20;
  background:var(--gtp-bg);
  border-bottom:2px solid var(--gtp-border);
  margin:0 -2rem;padding:0 2rem;
  box-shadow:0 2px 8px rgba(0,0,0,.05);
}
.gtp-tab-group{display:flex}
.gtp-tab-sep{
  width:1px;height:20px;background:var(--gtp-border);
  margin:0 .5rem;flex-shrink:0;align-self:center;
}
.gtp-tab{
  padding:.85rem 1.5rem;
  border:none;border-bottom:3px solid transparent;
  background:transparent;
  color:var(--gtp-text-muted);font-weight:600;font-size:.88rem;
  cursor:pointer;transition:var(--gtp-tr);
  margin-bottom:-2px;letter-spacing:.01em;
}
.gtp-tab:hover{color:var(--gtp-accent);background:var(--gtp-accent-light)}
.gtp-tab-on{color:var(--gtp-accent);border-bottom-color:var(--gtp-accent);background:var(--gtp-accent-light)}
/* Aktuell-Quartal-Dot */
.gtp-tab-now{
  display:inline-block;width:6px;height:6px;border-radius:50%;
  background:var(--gtp-accent);margin-left:5px;vertical-align:middle;
  opacity:.85;
}
.admin-bar .gtp-tabs{top:32px}
@media screen and (max-width:782px){.admin-bar .gtp-tabs{top:46px}}

/* ── Filter ── */
.gtp-filt-wrap{
  margin:.75rem 0;padding:.75rem 1rem;
  background:var(--gtp-surface);border-radius:12px;
  border:1px solid var(--gtp-border);
}
/* Filter-Header: Label + Toggle + Reset in einer Zeile */
.gtp-filt-header{
  display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.5rem;
}
.gtp-filt-lbl{
  font-size:.68rem;font-weight:700;color:var(--gtp-text-muted);
  text-transform:uppercase;letter-spacing:.1em;
}
/* Toggle-Button immer sichtbar */
.gtp-filt-toggle{
  display:flex;background:transparent;border:none;padding:2px;
  cursor:pointer;color:var(--gtp-text-muted);line-height:1;
}
.gtp-filt-chevron{transition:transform .2s ease}
.gtp-filt-open .gtp-filt-chevron,.gtp-filt-toggle[aria-expanded="true"] .gtp-filt-chevron{transform:rotate(90deg)}
/* Zeilenumbruch statt verstecktem Horizontal-Scroll: alle Kategorien bleiben
   sichtbar (kein Cut-off ohne Scrollbar-Hinweis), Toggle klappt echt ein/aus –
   einheitlich mit dem Kiosk-Verhalten, nicht mehr auf Mobile beschränkt. */
.gtp-filt{
  display:flex;flex-wrap:wrap;gap:6px;align-items:center;
  max-height:0;overflow:hidden;transition:max-height .25s ease;
}
.gtp-filt.gtp-filt-open{max-height:400px;overflow:visible}
.gtp-filt-count{
  font-size:.68rem;font-weight:500;color:var(--gtp-text-muted);
  margin-left:.35em;letter-spacing:.02em;text-transform:none;
}
.gtp-fb{
  padding:5px 14px;border:1.5px solid;border-radius:20px;
  font-size:.75rem;cursor:pointer;font-weight:600;line-height:1.5;
  white-space:nowrap;
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
  position:sticky;top:var(--gtp-thead-top,44px);z-index:10;
}
.gt thead th:last-child{border-right:none}
/* ── Schulwochenzeilen: Sticky-Header-Abstand beim Scrollen ── */
.gt tbody tr{scroll-margin-top:var(--gtp-scroll-margin,92px)}
/* ── Vergangene Wochen ── */
.gtp-past{opacity:.4;transition:var(--gtp-tr)}
.gtp-past:hover{opacity:.85}
.gtp-past .ge{filter:grayscale(50%)}
.gtp-past:hover .ge{filter:none}
.gt tbody tr:hover td{background:rgba(0,70,125,.02)}
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
  font-size:.8rem;line-height:1.4;
  border:1px solid transparent;
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
.gc-standard {background:var(--c-st-bg);border-color:var(--c-st-bd);color:var(--c-st-tx)}

/* ── Lange Termine in der Hinweise-Spalte ── */
.gn-long{
  padding:3px 6px;margin:2px 0;border-radius:5px;
  font-size:.8rem;line-height:1.4;
  border:1px solid transparent;
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
  font-style:italic;font-size:.8rem;color:var(--c-fs-tx);
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
  0%,100%{transform:scale(1);box-shadow:0 0 0 0 rgba(0,70,125,.45)}
  50%{transform:scale(1.5);box-shadow:0 0 0 6px rgba(0,70,125,0)}
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
  justify-content:space-between;align-items:flex-start;
  margin-top:1.5rem;padding-top:1rem;
  border-top:1px solid var(--gtp-border);
}
.gtp-ft-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.gtp-ft-meta{display:flex;align-items:center;gap:8px;color:var(--gtp-text-faint);font-size:.75rem;padding-top:4px}
.gtp-btn{
  padding:8px 16px;background:var(--gtp-text);color:#fff;
  border:none;border-radius:8px;
  cursor:pointer;font-size:.8rem;font-weight:600;
  transition:var(--gtp-tr);white-space:nowrap;
  box-shadow:0 1px 3px rgba(0,0,0,.15);
}
.gtp-btn:hover{background:#0f172a;transform:translateY(-1px);box-shadow:0 3px 8px rgba(0,0,0,.2)}
.gtp-btn-pdf{background:var(--gtp-bg);border:1.5px solid var(--gtp-accent);color:var(--gtp-accent);box-shadow:none}
.gtp-btn-pdf:hover{background:var(--gtp-accent-light);transform:translateY(-1px);box-shadow:0 3px 8px rgba(0,70,125,.15)}
.gtp-btn-ics{background:var(--gtp-bg);border:1.5px solid var(--gtp-accent);color:var(--gtp-accent);box-shadow:none;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.gtp-btn-ics:hover{background:var(--gtp-accent-light);transform:translateY(-1px);box-shadow:0 3px 8px rgba(0,70,125,.15);color:var(--gtp-accent)}
.gtp-btn-sec{background:var(--gtp-surface);border:1.5px solid var(--gtp-border);color:var(--gtp-text-muted);box-shadow:none}
.gtp-btn-sec:hover{background:var(--gtp-bg);border-color:var(--gtp-text-muted);color:var(--gtp-text);transform:translateY(-1px);box-shadow:none}
.gtp-btn-copied{background:var(--gtp-accent-light);border-color:var(--gtp-accent);color:var(--gtp-accent)}
.gtp-ics-group{
  display:flex;flex-wrap:wrap;align-items:center;gap:8px;max-width:100%;
  flex-basis:100%;margin-top:12px;padding-top:12px;
  border-top:1px solid var(--gtp-border);
}
.gtp-ics-help{flex-basis:100%;font-size:.78rem;color:var(--gtp-text-muted)}
.gtp-ics-help summary{cursor:pointer;color:var(--gtp-accent);font-weight:600;list-style:none;display:inline-flex;align-items:center;gap:5px}
.gtp-ics-help summary::-webkit-details-marker{display:none}
.gtp-ics-chevron{transition:transform .15s}
.gtp-ics-help[open] .gtp-ics-chevron{transform:rotate(90deg)}
.gtp-ics-help-body{margin-top:8px;padding:10px 12px;background:var(--gtp-bg);border-radius:8px;line-height:1.5}
.gtp-ics-help-body p{margin:0 0 8px}
.gtp-ics-help-body ul{margin:4px 0 0;padding-left:18px}
.gtp-ics-help-body li{margin-bottom:4px}
.gtp-pdf-loading{opacity:.55;pointer-events:none;cursor:default}
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
  box-shadow:0 4px 20px rgba(0,70,125,.35);
  cursor:pointer;
  opacity:0;pointer-events:none;
  transition:opacity .3s ease,transform .3s ease,box-shadow .3s ease;
  transform:translateY(10px);
}
#gtp-heute-btn.gtp-heute-vis{opacity:1;pointer-events:auto;transform:translateY(0)}
#gtp-heute-btn:hover{background:var(--gtp-accent-dark);box-shadow:0 6px 24px rgba(0,52,92,.35);transform:translateY(-2px)}

/* ── Suchfeld ── */
.gtp-search-results{
  padding:2px 4px;
}
.gtp-search-input{
  padding:8px 16px 8px 42px;
  border:1.5px solid var(--gtp-border);border-radius:24px;
  font-size:.84rem;color:var(--gtp-text);background:var(--gtp-surface);
  outline:none;width:100%;box-sizing:border-box;
  transition:var(--gtp-tr);
}
.gtp-search-input:focus{
  border-color:var(--gtp-accent);background:var(--gtp-bg);
  box-shadow:0 0 0 3px rgba(0,70,125,.12);
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

/* v4.19.4: KEIN overflow-x:auto. Sonst wird dieser Wrapper zum Scroll-Kontext,
   an dem .gt thead{position:sticky} VERTIKAL klebt (statt am Viewport) → der Kopf
   wandert mit und überlappt SW 00. .gt ist table-layout:fixed/width:100% (kein
   H-Scroll nötig) und <768px display:none. Darum overflow:visible. */
.gtp-tbl-scroll{overflow:visible;width:100%}
/* ── Mobile Agenda: auf Desktop versteckt ── */
.gtp-mob{display:none}

/* ════════════════════════════════════════════════════════════════
   MOBILE AGENDA – iOS-Feed-Stil (< 768px)
   ════════════════════════════════════════════════════════════════ */
@media(max-width:767px){

  .gt{display:none!important}
  .gtp-mob{display:block}

  .gtp{padding:.875rem 1rem;border-radius:12px}
  .gtp-hd{flex-wrap:wrap;gap:.5rem;padding-bottom:.75rem}
  .gtp-hd-left{flex:1;min-width:0}
  .gtp-hd .gtp-search-wrap{order:3;width:100%;max-width:100%;flex:0 0 100%}
  .gtp-tab-sep{display:none}
  .gtp-view-toggle{font-size:.76rem;padding:.25em .6em}
  .gtp-filt-wrap{padding:.625rem .875rem}
  .gtp-fb{white-space:nowrap;font-size:.72rem;padding:4px 10px}
  .gtp-tabs{margin:0 -1rem;padding:0 1rem;overflow-x:auto;flex-wrap:nowrap;scrollbar-width:none;-ms-overflow-style:none}
  .gtp-tabs::-webkit-scrollbar{display:none}
  .gtp-tab{padding:.5rem 1rem;font-size:.82rem;white-space:nowrap;flex-shrink:0}
  .gtp-search{max-width:100%;width:100%}
  .gtp-search-input{width:100%}
  .gtp-ft{flex-direction:column;gap:8px;align-items:flex-start}
  .gtp-ft-meta{justify-content:flex-start}

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
    box-shadow:inset 0 0 0 2px var(--gtp-today-bd);
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
    border-radius:8px;
    box-shadow:0 1px 3px rgba(0,0,0,.06);
  }

  /* ── Floating Heute-Button ── */
  #gtp-heute-btn{bottom:20px;right:20px;font-size:.84rem;padding:10px 18px}
}

/* Responsive für Tablets */
@media(min-width:768px) and (max-width:1024px){
  .gtp{padding:1.25rem;border-radius:12px}
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
/* ── Feedback-Modal ───────────────────────────────────────── */
.gtp-feedback-intro{font-size:.875rem;color:var(--gtp-text-muted);margin:0 0 1.1rem;line-height:1.55}
.gtp-feedback-types{display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1.1rem}
.gtp-feedback-type{
  font-size:.75rem;font-weight:600;font-family:inherit;
  padding:.35rem .85rem;border-radius:20px;cursor:pointer;
  border:1.5px solid var(--gtp-border);
  background:var(--gtp-surface);color:var(--gtp-text-muted);
  transition:border-color .15s,background .15s,color .15s;
  white-space:nowrap;line-height:1.4;
}
.gtp-feedback-type:hover{border-color:var(--gtp-accent);color:var(--gtp-accent)}
.gtp-feedback-type-active{
  border-color:var(--gtp-accent);
  background:var(--gtp-accent-light,#e6f4ff);
  color:var(--gtp-accent);
}
.gtp-feedback-field{display:flex;flex-direction:column;gap:.4rem;margin-bottom:.875rem}
.gtp-feedback-label{
  display:block;font-size:.72rem;font-weight:700;
  text-transform:uppercase;letter-spacing:.06em;
  color:var(--gtp-text-muted);
}
.gtp-feedback-textarea{
  display:block;width:100%;
  font-family:inherit;font-size:.875rem;color:var(--gtp-text);
  background:var(--gtp-bg);
  border:1.5px solid var(--gtp-border);border-radius:8px;
  padding:.55rem .875rem;
  transition:border-color .15s;
  resize:vertical;line-height:1.5;
}
.gtp-feedback-textarea:focus{outline:none;border-color:var(--gtp-accent)}
.gtp-feedback-counter{font-size:.72rem;color:var(--gtp-text-faint);text-align:right}
.gtp-feedback-actions{display:flex;gap:.625rem;margin-top:1.1rem}
.gtp-feedback-actions .gtp-btn{flex:1;justify-content:center;text-align:center}
.gtp-feedback-status{
  padding:.6rem .875rem;border-radius:8px;font-size:.84rem;line-height:1.5;
  margin-top:.75rem;
}
.gtp-feedback-status-ok{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0}
.gtp-feedback-status-err{background:#fee2e2;color:#b91c1c;border:1px solid #fecaca}
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
  background:var(--gtp-accent,#00467D);color:#fff;
  border-color:var(--gtp-accent,#00467D);
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
.gtp-cl-tag--ux      {background:#dce8f5;color:#00467D;border:1px solid #b3cfe6}
.gtp-cl-tag--bugfix  {background:#fee2e2;color:#dc2626;border:1px solid #fecaca}
.gtp-cl-tag--security{background:#fef3c7;color:#d97706;border:1px solid #fde68a}
/* ── Dynamische Kategorie-CSS (konfigurierbare Kategorien überschreiben Standard) ── */
' . $cat['rules'] . '

/* ── Hilfe-Overlay ── */
.gtp-help-overlay{
  position:fixed;inset:0;
  background:rgba(0,0,0,.45);
  backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);
  z-index:99999;display:flex;align-items:center;justify-content:center;padding:1rem;
  animation:gtpFadeIn .2s ease;
}
.gtp-help-overlay[hidden]{display:none!important}
.gtp-help-panel{
  background:var(--gtp-bg);border-radius:16px;
  width:100%;max-width:520px;max-height:82vh;
  overflow:hidden;display:flex;flex-direction:column;
  box-shadow:0 20px 60px rgba(0,0,0,.22),0 2px 8px rgba(0,0,0,.1);
}
.gtp-help-header{
  display:flex;align-items:center;justify-content:space-between;
  padding:1.25rem 1.5rem;
  border-bottom:1px solid var(--gtp-border);flex-shrink:0;
}
.gtp-help-title{
  font-size:1rem;font-weight:700;margin:0;
  color:var(--gtp-text);display:flex;align-items:center;gap:.5rem;
}
.gtp-help-close{
  width:32px;height:32px;border-radius:50%;
  border:1px solid var(--gtp-border);background:transparent;
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  color:var(--gtp-text-muted);transition:var(--gtp-tr-fast);flex-shrink:0;
}
.gtp-help-close:hover{background:var(--gtp-surface);color:var(--gtp-text)}
.gtp-help-body{
  overflow-y:auto;padding:1.25rem 1.5rem;
  display:flex;flex-direction:column;gap:.875rem;flex:1;
}
.gtp-help-section{display:flex;gap:.75rem;align-items:flex-start}
.gtp-help-section-icon{
  width:36px;height:36px;border-radius:8px;
  background:var(--gtp-accent-light);color:var(--gtp-accent);
  display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.gtp-help-section-body strong{
  font-size:.88rem;font-weight:700;color:var(--gtp-text);
  display:block;margin-bottom:.2rem;
}
.gtp-help-section-body p{font-size:.8rem;color:var(--gtp-text-muted);margin:0;line-height:1.5}
.gtp-help-footer{
  padding:.875rem 1.5rem;
  border-top:1px solid var(--gtp-border);
  background:var(--gtp-surface);border-radius:0 0 16px 16px;flex-shrink:0;
}
.gtp-help-version{font-size:.72rem;color:var(--gtp-text-faint)}
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
  } else if(p){
    /* Erste Tabellenzeile klar unterhalb des Sticky-Headers positionieren */
    setTimeout(function(){
      var tbl=p.querySelector(".gt");
      if(!tbl) return;
      var top=tbl.getBoundingClientRect().top+window.pageYOffset;
      window.scrollTo({top:Math.max(0,top-gtpStickyH()-8),behavior:"smooth"});
    },60);
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

var gtpGrpSel = {}; /* { gruppe: true } -> Gruppe VERSTECKT; leer = alle sichtbar */
function gtpGrpFil(btn){
  var g = btn.getAttribute("data-g");
  if(gtpGrpSel[g]){ delete gtpGrpSel[g]; } else { gtpGrpSel[g] = true; }
  btn.classList.toggle("gtp-gb-on", !gtpGrpSel[g]);
  btn.setAttribute("aria-pressed", gtpGrpSel[g] ? "false" : "true");
  try{ localStorage.setItem("gtpGrpSel", JSON.stringify(gtpGrpSel)); }catch(e){}
  gtpApply();
}

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
  gtpGrpSel = {};
  gtpApply();
  gtpSaveFilters();
  try{ localStorage.removeItem("gtpGrpSel"); }catch(e){}
  document.querySelectorAll(".gtp-gb").forEach(function(b){
    b.classList.add("gtp-gb-on");
    b.setAttribute("aria-pressed","true");
  });
}

/* Kategorie-Filter (+ Gruppen-Filter-Zeile) auf Mobile ein-/ausklappen */
function gtpFilterToggle(){
  var body = document.getElementById("gtp-filt-body");
  var btn  = document.querySelector(".gtp-filt-toggle");
  if(!body) return;
  body.classList.toggle("gtp-filt-open");
  var open = body.classList.contains("gtp-filt-open");
  var grpBody = document.getElementById("gtp-grp-body");
  if(grpBody) grpBody.classList.toggle("gtp-filt-open", open);
  if(btn) btn.setAttribute("aria-expanded", open ? "true" : "false");
}

/* Schaltet zwischen Quartals- und Jahresansicht um */
function gtpViewToggle(btn){
  var wrap  = document.getElementById("gtp");
  var toYear = wrap ? wrap.dataset.view !== "year" : false;
  if(wrap) wrap.dataset.view = toYear ? "year" : "quartal";
  var label = btn.querySelector(".gtp-view-toggle-label");
  if(label){
    label.textContent = toYear
      ? btn.dataset.labelQuartal
      : btn.dataset.labelYear;
  }
  btn.classList.toggle("gtp-view-toggle-on", toYear);
  /* Heatmap-Zustand zurücksetzen wenn Jahresansicht verlassen */
  if(!toYear){
    var yearWrap=document.querySelector(".gtp-year-wrap");
    if(yearWrap)yearWrap.classList.remove("gtp-year-wrap--heatmap");
    var hmBtn=document.getElementById("gtp-heatmap-toggle");
    if(hmBtn){
      hmBtn.classList.remove("gtp-view-toggle-on");
      var hmLabel=hmBtn.querySelector(".gtp-view-toggle-label");
      if(hmLabel)hmLabel.textContent="Heatmap";
    }
  }
}

/* ════════════════════════════════════════════════════════
   HEATMAP (Mobile Jahresansicht – v4.4.0)
   ════════════════════════════════════════════════════════ */

function gtpHmColor(slug){
  return getComputedStyle(document.documentElement)
    .getPropertyValue("--c-"+slug+"-bd").trim()||"#94a3b8";
}

function gtpHmPaintTiles(){
  document.querySelectorAll(".gtp-yr-hm-sq[data-cats]").forEach(function(sq){
    var cats=sq.dataset.cats.trim().split(/\s+/);
    if(cats.length===1){
      sq.style.background=gtpHmColor(cats[0]);
    }else{
      var pct=100/cats.length;
      var stops=cats.map(function(c,i){
        return gtpHmColor(c)+" "+(i*pct)+"% "+((i+1)*pct)+"%";
      }).join(",");
      sq.style.background="linear-gradient(135deg,"+stops+")";
    }
  });
}

function gtpHmApplyFilter(){
  document.querySelectorAll(".gtp-yr-hm-sq[data-cats]").forEach(function(sq){
    var cats=sq.dataset.cats.trim().split(/\s+/);
    var anyVisible=cats.some(function(c){return !gtpSel[c];});
    sq.classList.toggle("gtp-yr-hm-sq--filtered",!anyVisible);
  });
}

function gtpHmExpand(sq){
  var date=sq.dataset.date;
  if(!date)return;
  var month=sq.closest(".gtp-yr-hm-month");
  var expand=month?month.querySelector(".gtp-yr-hm-expand"):null;
  if(!expand)return;
  var isActive=sq.classList.contains("gtp-yr-hm-sq--active");
  month.querySelectorAll(".gtp-yr-hm-sq--active").forEach(function(s){
    s.classList.remove("gtp-yr-hm-sq--active");
  });
  expand.hidden=true;
  expand.innerHTML="";
  if(isActive)return;
  var evArr=[];
  document.querySelectorAll(".gtp-yr-ev").forEach(function(ev){
    if((ev.getAttribute("data-date")||"").slice(0,10)===date)evArr.push(ev);
  });
  if(!evArr.length)return;
  var d=new Date(date+"T00:00:00");
  var wdays=["So","Mo","Di","Mi","Do","Fr","Sa"];
  var mons=["Jan","Feb","Mär","Apr","Mai","Jun","Jul","Aug","Sep","Okt","Nov","Dez"];
  var label=wdays[d.getDay()]+", "+d.getDate()+". "+mons[d.getMonth()]+" "+d.getFullYear();
  var html="<div class=\"gtp-yr-hm-expand-date\">"+label+"</div>";
  evArr.forEach(function(ev){
    var c=ev.getAttribute("data-c")||"";
    if(gtpSel[c])return;
    var summary=ev.getAttribute("data-summary")||ev.textContent.trim();
    html+="<div class=\"gtp-yr-hm-expand-ev gc-"+c+"\">"+summary+"</div>";
  });
  expand.innerHTML=html;
  expand.hidden=false;
  sq.classList.add("gtp-yr-hm-sq--active");
}

function gtpHeatmapToggle(btn){
  var yearWrap=document.querySelector(".gtp-year-wrap");
  if(!yearWrap)return;
  var isHeatmap=yearWrap.classList.toggle("gtp-year-wrap--heatmap");
  var label=btn.querySelector(".gtp-view-toggle-label");
  if(label)label.textContent=isHeatmap?"Tabelle":"Heatmap";
  btn.classList.toggle("gtp-view-toggle-on",isHeatmap);
}

document.addEventListener("click",function(e){
  var sq=e.target.closest(".gtp-yr-hm-sq");
  if(sq&&sq.dataset.valid!=="0"&&sq.dataset.cats){
    gtpHmExpand(sq);
  }
});

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

  var groupsAttr = el.getAttribute("data-groups");
  var groupOk = true;
  if(groupsAttr){
    groupOk = groupsAttr.split("|").some(function(g){ return !gtpGrpSel[g]; });
  }

  /* Kategorie-Filter: komplett verstecken wenn nicht passend */
  el.style.display = (categoryOk && groupOk) ? "" : "none";

  /* Suche: Focus-Mode – Nicht-Treffer abdimmen (nur bei aktiver Suche) */
  var inp = document.getElementById("gtp-search-input");
  var q   = inp ? inp.value.trim().toLowerCase() : "";

  if(categoryOk && groupOk && q){
    var txt   = (el.textContent  || "").toLowerCase();
    var title = (el.getAttribute("title") || "").toLowerCase();
    var searchOk = txt.indexOf(q) !== -1 || title.indexOf(q) !== -1;
    el.classList.toggle("ge-focus-dim", !searchOk);
  } else {
    el.classList.remove("ge-focus-dim");
  }
}

/**
 * Wendet die aktuellen Kategorie- und Gruppen-Filter auf alle Elemente an.
 * Aktualisiert die Filter-Button-Optik (aktiv/inaktiv), blendet Events
 * (.ge), lange Termine (.gn-long), Frist-Notizen (.gn) und Jahresraster-
 * Events (.gtp-yr-ev) passend ein/aus.
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

  /* Lange Termine + Frist-Notizen: Kategorie- + Gruppen-Filter */
  document.querySelectorAll(".gn-long[data-c], .gn[data-c]").forEach(function(el){
    var c = el.getAttribute("data-c");
    var groupsAttr = el.getAttribute("data-groups");
    var groupOk = true;
    if(groupsAttr){
      groupOk = groupsAttr.split("|").some(function(g){ return !gtpGrpSel[g]; });
    }
    el.style.display = (!gtpSel[c] && groupOk) ? "" : "none";
  });

  /* Jahresraster-Events: Kategorie- + Gruppen-Filter */
  document.querySelectorAll(".gtp-yr-ev[data-c]").forEach(function(el){
    var c = el.getAttribute("data-c");
    var groupsAttr = el.getAttribute("data-groups");
    var groupOk = true;
    if(groupsAttr){
      groupOk = groupsAttr.split("|").some(function(g){ return !gtpGrpSel[g]; });
    }
    el.style.display = (!gtpSel[c] && groupOk) ? "" : "none";
  });

  /* Reset-Button zeigen / verstecken */
  var resetBtn = document.getElementById("gtp-reset");
  if(resetBtn) resetBtn.style.display = hiddenCount > 0 ? "" : "none";
  /* Heatmap-Kacheln dimmen wenn alle Kategorien gefiltert */
  gtpHmApplyFilter();
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
    var offset = Math.max(0, top - gtpStickyH() - 8);
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
 * Setzt --gtp-thead-top und --gtp-scroll-margin anhand gemessener Höhen.
 * Läuft bei DOMContentLoaded, load und resize.
 */
function gtpUpdateStickyOffsets(){
  var tabs=document.querySelector(".gtp-tabs");
  var thead=document.querySelector(".gt thead");
  if(!tabs||!thead) return;
  var bar=document.getElementById("wpadminbar");
  var barH=bar?bar.offsetHeight:0;
  var tabsH=tabs.offsetHeight;
  var theadH=thead.offsetHeight;
  var theadTop=barH+tabsH;
  var root=document.documentElement;
  root.style.setProperty("--gtp-thead-top",theadTop+"px");
  root.style.setProperty("--gtp-scroll-margin",(theadTop+theadH+8)+"px");
}
document.addEventListener("DOMContentLoaded",gtpUpdateStickyOffsets);
window.addEventListener("load",gtpUpdateStickyOffsets);
window.addEventListener("resize",gtpUpdateStickyOffsets,{passive:true});

/**
 * Gibt die Gesamthöhe aller sticky-Elemente zurück (px): Admin-Bar + Tabs + Thead.
 */
function gtpStickyH(){
  var h=0;
  var bar=document.getElementById("wpadminbar");
  if(bar) h+=bar.offsetHeight;
  var tabs=document.querySelector(".gtp-tabs");
  if(tabs) h+=tabs.offsetHeight;
  var thead=document.querySelector(".gt thead");
  if(thead) h+=thead.offsetHeight;
  return h;
}

/**
 * Scrollt die heutige Tabellenzeile (.gt-today) klar unterhalb des Sticky-Headers.
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
    var offset=Math.max(0,top-gtpStickyH()-8);
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
  gtpHmPaintTiles();
  /* Filter-Zustand aus localStorage wiederherstellen */
  try{
    var saved = localStorage.getItem("gtp_filters");
    if(saved){ gtpSel = JSON.parse(saved); gtpApply(); }
    var savedG = localStorage.getItem("gtpGrpSel");
    if(savedG){
      gtpGrpSel = JSON.parse(savedG);
      document.querySelectorAll(".gtp-gb").forEach(function(b){
        var g = b.getAttribute("data-g");
        b.classList.toggle("gtp-gb-on", !gtpGrpSel[g]);
        b.setAttribute("aria-pressed", gtpGrpSel[g] ? "false" : "true");
      });
      gtpApply();
    }
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
 * Escaped HTML-Sonderzeichen für den Einsatz in String-Konkatenation
 * (Kopfzeilen-Text kommt aus einer Admin-Einstellung, nicht aus dem DOM).
 * @param {string} s
 * @return {string}
 */
function gtpEsc(s){
  return String(s).replace(/[&<>"']/g,function(c){
    return {"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c];
  });
}

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
  var safeTitle=docTitle.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
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
    ".gt-today .gdl{color:#00467D}",
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
  var schoolName=(gtpEl&&gtpEl.dataset.school)||"Gesamtschule Horst";
  var schoolParts=schoolName.split(" ");
  var schoolHtml=gtpEsc(schoolParts[0])
    +(schoolParts.length>1 ? "<br>"+gtpEsc(schoolParts.slice(1).join(" ")) : "");
  var HDR='<div class="hdr">'
    +'<div class="hdr-logo">'
    +'<span class="hdr-logo-mark">Curriculr</span>'
    +'<span class="hdr-logo-sep"></span>'
    +'<span class="hdr-logo-name">'+schoolHtml+'</span>'
    +'</div>'
    +'<div class="hdr-title">'
    +'<div class="hdr-main">Schuljahresterminplan\u00a02025\u202f/\u202f26</div>'
    +'<div class="hdr-sub">Quelle: IServ-Kalender</div>'
    +'</div>'
    +'</div>';

  /* ── Footer: fixed → erscheint auf jeder PDF-Seite ── */
  var FTR='<div class="pdf-ft">Stand: '+today+'\u2003|\u2003Erstellt \u00fcber das Curriculr-Dashboard</div>';

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

  /* Touch-Geräte (iPad/Handy): Hidden-iframe-Druck wird von iOS Safari
     verstümmelt (ignoriert @page landscape). Stattdessen das fertige
     A4-Querformat-Dokument in einem echten Tab öffnen – zoombar, und der
     Nutzer wählt selbst „Als PDF sichern" / Teilen. */
  var gtpTouch=(window.matchMedia&&window.matchMedia("(pointer:coarse)").matches)||window.innerWidth<=1024;
  if(gtpTouch){
    var fullDoc=[
      '<!DOCTYPE html>','<html lang="de">','<head>',
      '<meta charset="utf-8">',
      '<meta name="viewport" content="width=1100">',
      '<title>'+safeTitle+'</title>',
      '<style>'+CSS+'</style>','</head>','<body>',body,'</body>','</html>'
    ].join('');
    var win=window.open("","_blank");
    if(win){
      win.document.open();
      win.document.write(fullDoc);
      win.document.close();
      return;
    }
    /* Popup blockiert → unten auf den iframe-Pfad zurückfallen */
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
    '<meta charset="utf-8">','<title>'+safeTitle+'</title>',
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
function gtpPdfBtnState(btn,loading){
  if(!btn) return;
  btn.classList.toggle("gtp-pdf-loading",loading);
  btn.disabled=loading;
}

function gtpPdf(btn){
  var at=document.querySelector(".gtp-tab-on");
  var q=at ? parseInt(at.getAttribute("data-q")) : 1;
  var panel=document.getElementById("gtp-q"+q);
  var qtEl=panel ? panel.querySelector(".gtp-qt") : null;
  var title=qtEl
    ? "Terminplan Curriculr 2025-26 - "+qtEl.textContent
    : "Terminplan Curriculr 2025-26";
  gtpPdfBtnState(btn,true);
  gtpPdfHint();
  gtpPrint("single",title);
  setTimeout(function(){gtpPdfBtnState(btn,false);},3500);
}

/**
 * Exportiert alle vier Quartale als PDF (über den Browser-Druckdialog).
 */
function gtpPdfAll(btn){
  gtpPdfBtnState(btn,true);
  gtpPdfHint();
  gtpPrint("all","Terminplan Curriculr 2025-26 - Komplett");
  setTimeout(function(){gtpPdfBtnState(btn,false);},3500);
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

/* ── Feed-URL kopieren (Fallback für Apps ohne webcal://-Support) ── */

function gtpCopyFeed(btn) {
  var url = btn.getAttribute('data-feed-url');
  if (!url) return;
  var restore = function() {
    btn.classList.remove('gtp-btn-copied');
    btn.innerHTML = btn.dataset.gtpOrigLabel;
  };
  var onCopied = function() {
    if (!btn.dataset.gtpOrigLabel) btn.dataset.gtpOrigLabel = btn.innerHTML;
    btn.classList.add('gtp-btn-copied');
    btn.textContent = 'Kopiert!';
    setTimeout(restore, 1500);
  };
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(url).then(onCopied);
    return;
  }
  var ta = document.createElement('textarea');
  ta.value = url;
  ta.style.position = 'fixed';
  ta.style.opacity = '0';
  document.body.appendChild(ta);
  ta.select();
  try { document.execCommand('copy'); } catch (e) { /* noop */ }
  document.body.removeChild(ta);
  onCopied();
}

/* ── Hilfe-Overlay (seit 3.16.0, ersetzt Shepherd.js-Tour) ──────── */

function gtpHelpOpen() {
  var overlay = document.getElementById('gtp-help-overlay');
  if (!overlay) return;
  overlay.hidden = false;
  overlay.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
  var closeBtn = overlay.querySelector('.gtp-help-close');
  if (closeBtn) setTimeout(function(){ closeBtn.focus(); }, 50);
}

function gtpHelpClose() {
  var overlay = document.getElementById('gtp-help-overlay');
  if (!overlay) return;
  overlay.hidden = true;
  overlay.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
  var btn = document.getElementById('gtp-tour-btn');
  if (btn) btn.focus();
}

// ESC schließt das Hilfe-Overlay
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    var overlay = document.getElementById('gtp-help-overlay');
    if (overlay && !overlay.hidden) gtpHelpClose();
  }
});

// Klick auf Hintergrund (außerhalb des Panels) schließt das Overlay
(function() {
  var overlay = document.getElementById('gtp-help-overlay');
  if (overlay) {
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) gtpHelpClose();
    });
  }
})();

/* ── Feedback-Modal (wp_mail via AJAX, seit 3.12.0) ─────────── */

var gtpFeedbackSelectedType = '';

function gtpFeedbackOpen() {
  var overlay = document.getElementById('gtp-feedback-overlay');
  if (!overlay) return;
  gtpFeedbackSelectedType = '';
  document.querySelectorAll('.gtp-feedback-type').forEach(function(b) {
    b.classList.remove('gtp-feedback-type-active');
  });
  var ta  = document.getElementById('gtp-feedback-text');
  var cnt = document.getElementById('gtp-feedback-count');
  var sub = document.getElementById('gtp-feedback-submit');
  var st  = document.getElementById('gtp-feedback-status');
  if (ta)  ta.value           = '';
  var sender = document.getElementById('gtp-feedback-sender');
  if (sender) sender.value = '';
  if (cnt) cnt.textContent    = '0';
  if (sub) sub.disabled       = true;
  if (st)  { st.style.display = 'none'; st.textContent = ''; }
  overlay.style.display = 'flex';
  overlay.focus();
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
  gtpFeedbackSelectedType = btn.getAttribute('data-type') || '';
  gtpFeedbackCheck();
}

function gtpFeedbackCheck() {
  var ta  = document.getElementById('gtp-feedback-text');
  var sub = document.getElementById('gtp-feedback-submit');
  if (sub) sub.disabled = !(gtpFeedbackSelectedType && ta && ta.value.trim().length >= 3);
}

function gtpFeedbackSubmit() {
  var sub = document.getElementById('gtp-feedback-submit');
  var ta  = document.getElementById('gtp-feedback-text');
  var st  = document.getElementById('gtp-feedback-status');
  if (!sub || sub.disabled) return;
  var ajaxUrl = (document.getElementById('gtp-ajax-url')       || {}).value || '';
  var nonce   = (document.getElementById('gtp-feedback-nonce') || {}).value || '';
  sub.disabled    = true;
  sub.textContent = 'Wird gesendet\u2026';
  var body = new URLSearchParams();
  var hp     = document.getElementById('gtp-feedback-hp');
  var sender = document.getElementById('gtp-feedback-sender');
  body.append('action',    'gsh_tp_feedback');
  body.append('nonce',     nonce);
  body.append('type',      gtpFeedbackSelectedType);
  body.append('message',   ta ? ta.value.trim() : '');
  body.append('sender',    sender ? sender.value.trim() : '');
  body.append('gsh_tp_hp', hp ? hp.value : '');
  fetch(ajaxUrl, { method: 'POST', body: body })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!st) return;
      st.style.display = 'block';
      if (data.success) {
        st.className   = 'gtp-feedback-status gtp-feedback-status-ok';
        st.textContent = '\u2713 ' + (data.data.message || 'Feedback gesendet. Danke!');
        setTimeout(function() {
          gtpFeedbackClose();
          var fbBtn = document.getElementById('gtp-feedback-btn');
          if (fbBtn) {
            var orig = fbBtn.innerHTML;
            fbBtn.innerHTML = '&#10003; Danke!';
            fbBtn.disabled  = true;
            setTimeout(function() { fbBtn.innerHTML = orig; fbBtn.disabled = false; }, 3000);
          }
        }, 2000);
      } else {
        st.className    = 'gtp-feedback-status gtp-feedback-status-err';
        st.textContent  = '\u2717 ' + (data.data.message || 'Fehler beim Senden.');
        sub.disabled    = false;
        sub.textContent = 'Absenden';
      }
    })
    .catch(function() {
      if (st) {
        st.style.display = 'block';
        st.className     = 'gtp-feedback-status gtp-feedback-status-err';
        st.textContent   = '\u2717 Verbindungsfehler. Bitte erneut versuchen.';
      }
      sub.disabled    = false;
      sub.textContent = 'Absenden';
    });
}

// Textarea: Zeichenzähler + Submit-Check
document.addEventListener('DOMContentLoaded', function() {
  var ta = document.getElementById('gtp-feedback-text');
  if (ta) {
    ta.addEventListener('input', function() {
      var cnt = document.getElementById('gtp-feedback-count');
      if (cnt) cnt.textContent = this.value.length;
      gtpFeedbackCheck();
    });
  }
  // Klick auf Overlay-Hintergrund schließt Modal
  var overlay = document.getElementById('gtp-feedback-overlay');
  if (overlay) {
    overlay.addEventListener('click', function(e) {
      if (e.target === this) gtpFeedbackClose();
    });
  }
});

// Escape schließt Modal
document.addEventListener('keydown', function(e) {
  if (e.key !== 'Escape') return;
  var overlay = document.getElementById('gtp-feedback-overlay');
  if (overlay && overlay.style.display !== 'none') gtpFeedbackClose();
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
        'gsh_tp_draft_kiosk_token',
        'gsh_tp_iserv_domain',
        'gsh_tp_last_sync',
        GSH_TP_CACHE_KEY,          // permanente Daten-Option (seit 3.3.0)
        GSH_TP_BACKUP_KEY,
        'gsh_tp_curriculr_origin',
        'gsh_tp_curriculr_profile_map',
        'gsh_tp_curriculr_db_version',
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
    wp_clear_scheduled_hook( 'gsh_tp_curriculr_daily_backup' );
}
