# Datenschutz – Curricu:lr Terminplan

Diese Beschreibung erläutert die technische Datenverarbeitung des WordPress-Plugins ab Version 4.46.2.
Verantwortlich für den Einsatz und das Datenschutzkonzept ist die jeweilige Schule.
Die Beschreibung ersetzt nicht die Datenschutzerklärung der Schule.

## Datenklasse und Zweck

Das Plugin verarbeitet **Datenklasse 1**: Namen oder Kürzel von Lehrkräften, Gruppen und Termine.
Es dient der gemeinsamen Terminplanung, der Anzeige auf der Schul-Website und der Bereitstellung von Kalender-Abos.
Schülerlisten, Noten, Gesundheits- und Beratungsdaten sind nicht für dieses Produkt vorgesehen.

## Welche Daten wo gespeichert werden

| Daten | Speicherort und Zweck |
|---|---|
| Jahrespläne mit Terminen, Gruppen, Kategorien und Anmerkungen | WordPress-Datenbank der Schule, Tabelle `<präfix>curriculr_docs` |
| Frühere Planstände mit IServ-Kennung (`author_sub`), Anzeigename (`author_name`) und Zeitpunkt | Tabelle `<präfix>curriculr_doc_revisions`, zur Wiederherstellung und Zuordnung von Änderungen |
| Einstellungen, Schuljahre, Profile, Kalender-Adressen und Feed-Token | WordPress-Optionen der Schule |
| Kalenderdaten, letzter Vergleichsstand und Änderungshinweise | WordPress-Optionen und Transients, für Anzeige und Zwischenspeicherung |
| Tägliche Sicherungen der Pläne als JSON und ICS | `uploads/curriculr-backups/` innerhalb des WordPress-Upload-Verzeichnisses |
| Synchronisationsstatus, Zeitpunkt, Ereignisanzahl, Laufzeit und Fehlermeldung | WordPress-Optionen, zur technischen Diagnose |
| Feedback: optionaler Absendername, Nachricht, Kategorie, Zeitpunkt, Versandstatus und gesalzener IP-Hash | WordPress-Option `gsh_tp_feedback_log`; außerdem Versand per E-Mail |
| Anmelde-Zwischenstände und Zähler zur Abrufbegrenzung | Zeitlich begrenzte WordPress-Transients; je nach WordPress-Konfiguration in Datenbank oder Objekt-Cache |

IServ liefert Kennung, Anzeigename und Gruppenzugehörigkeiten zur Prüfung der Zugriffsberechtigung.
Das Plugin übernimmt nur die für die jeweilige App freigegebenen Gruppen in das App-Token.
IServ-Zugriffs- und ID-Token werden bei der Anmeldung verarbeitet, aber vom Plugin nicht dauerhaft gespeichert.
Das App-Token wird für den einmaligen Abruf kurzzeitig serverseitig zwischengespeichert.
Der Planer hält es anschließend im Arbeitsspeicher und im tabgebundenen `sessionStorage`.
Konfigurationsgeheimnisse bleiben in `wp-config.php`; sie gehören nicht in Planexporte.

## Aufbewahrung und Bereinigung

Die folgenden Werte stammen aus dem Code. Eine Ablaufzeit garantiert keine sofortige physische Löschung aus allen Sicherungen.

| Daten | Verhalten |
|---|---|
| Aktueller Jahresplan und Einstellungen | Keine automatische zeitliche Löschung |
| Revisionen | Nach dem Speichern werden höchstens 50 Revisionen je Schuljahr behalten; keine zusätzliche Altersgrenze |
| JSON-/ICS-Sicherungen | Der tägliche Sicherungslauf entfernt Dateien mit erkanntem Dateidatum, wenn sie älter als 30 Tage sind |
| Feedback-Protokoll | Bei neuem Feedback werden Einträge älter als 90 Tage entfernt und höchstens 200 Einträge behalten |
| Synchronisationsprotokoll | Höchstens 50 Einträge je Profil; keine automatische Altersgrenze. Die manuelle Bereinigung entfernt Einträge älter als 30 Tage |
| Kalender-Zwischenspeicher und letzter Vergleichsstand | Bleiben bis zum Ersetzen oder Löschen erhalten; die Aktualitätsfrist ist keine Löschfrist |
| Änderungshinweise | Höchstens 50 Änderungsstände; Ablauf sieben Tage nach der letzten Aktualisierung |
| Anmelde-Zwischenstand (`state`, `nonce`, App-Zuordnung) | Gültig für zehn Minuten; beim Callback einmalig verbraucht |
| Einmaliger Übergabecode mit App-Token | Gültig für 60 Sekunden; nach erfolgreichem Abruf gelöscht |
| App-Token | Standardmäßig 30 Minuten gültig; über `CURRICULR_APP_TOKEN_TTL` konfigurierbar |
| Abrufbegrenzung für Anmeldung, Token-Abruf und falsche Feed-Token | Festes Zeitfenster von zehn Minuten; weitere Versuche verlängern es nicht |
| Feedback-Abrufbegrenzung | Zehn Minuten ab dem letzten gezählten Versand |
| Kiosk-/Entwurfs-Abrufbegrenzung | Eine Stunde ab dem letzten gezählten Fehlversuch |

Die Sicherungsbereinigung setzt einen ausgeführten WordPress-Cron-Lauf und vorhandene Plan-Datensätze voraus.
Ohne neues Feedback erfolgt keine altersabhängige Bereinigung des Feedback-Protokolls.
Abgelaufene Transients können bis zur Bereinigung durch WordPress physisch gespeichert bleiben.

Die neue Abrufbegrenzung speichert Zähler und Ablaufzeit unter einem gesalzenen Hash des gekürzten IP-Netzes.
Verwendet werden IPv4-/24- beziehungsweise IPv6-/64-Netze; Klartext-IP-Adressen werden dafür nicht gespeichert.
Gültige Feed-Abrufe werden durch diese Begrenzung weder gezählt noch gesperrt.

Quellen: `gsh_tp_curriculr_prune_revisions()`, `gsh_tp_curriculr_backup_cron()` und `gsh_tp_curriculr_backup_is_expired()`
in [curriculr-data-layer.php](plugin/curriculr-data-layer.php);
`gsh_tp_feedback_log()`, `gsh_tp_log_sync_attempt()`, `gsh_tp_clear_old_logs()` und `gsh_tp_do_refresh()`
in [gsh-terminplan.php](plugin/gsh-terminplan.php);
Anmelde-Handler in [curriculr-auth.php](plugin/curriculr-auth.php).

## Verbindungen und Empfänger

- **IServ:** Der Browser wird zur Anmeldung weitergeleitet. WordPress tauscht den Autorisierungscode gegen Token aus und ruft Benutzerinformationen ab.
  Dabei erhält IServ unter anderem Client-Kennung, Rücksprungadresse und die für den Token-Austausch erforderlichen Zugangsdaten.
- **Konfigurierte iCal-Quelle:** WordPress ruft Kalenderdaten von der hinterlegten Adresse ab.
  Der Quellenbetreiber erhält die Server-IP und die angeforderte Adresse, gegebenenfalls einschließlich deren Zugriffstoken.
- **Planer und weitere registrierte Apps:** Berechtigte Browser übertragen Planungsdaten über die WordPress-REST-Schnittstelle und erhalten sie zurück.
  Das Hosting der jeweiligen App wird durch ihre Konfiguration bestimmt.
  Beim Laden einer extern gehosteten App erhält deren Hosting-Anbieter die üblichen Verbindungsdaten des Browsers.
- **Website und Kalender-Abonnenten:** Das Plugin liefert Termine an Besucher und an Kalender-Apps, einschließlich IServ, aus.
  Kalender-Abos erzeugen Kopien bei den Empfängern. Feed-Adressen enthalten ein Zugriffstoken und können auf der Website sichtbar sein.
  Behandeln Sie Kalender-Abos daher nicht als vertraulichen Übertragungsweg.
- **Feedback-E-Mail:** Das Plugin sendet Absendername, Nachricht, Kategorie sowie Website-Adresse und Zeitpunkt über `wp_mail()`.
  Empfänger ist die konfigurierte Feedback-Adresse, ersatzweise die WordPress-Administratoradresse.
  Der verwendete Maildienst und dessen Aufbewahrung richten sich nach der WordPress-Konfiguration.

Das Plugin bindet keine Analyse-Dienste oder externen Schrift-CDNs ein.
Andere WordPress-Plugins, Themes, Webserver-Protokolle und IServ unterliegen eigenen Einstellungen und Aufbewahrungsregeln.

## Daten löschen

**Updates und Deaktivierung löschen keine gespeicherten Pläne oder Einstellungen.**
Auch beim vollständigen Löschen des Plugins bleiben die Daten standardmäßig erhalten.
Damit ist eine spätere Neuinstallation mit den vorhandenen Daten möglich.

Für die vollständige Bereinigung der vom Plugin verwalteten Daten:

1. Sichern Sie vorher benötigte Pläne und Einstellungen.
2. Öffnen Sie **Einstellungen → Schul-Terminplan → System & Logs**.
3. Aktivieren Sie **Beim Deinstallieren alle Daten löschen** und speichern Sie die Einstellung.
4. Deaktivieren Sie das Plugin unter **Plugins** und wählen Sie anschließend **Löschen**.

Mit aktiviertem Haken entfernt die Deinstallation die Plan- und Revisionstabellen, Plugin-Optionen, datenbankgespeicherte Transients und das Verzeichnis `curriculr-backups`.
Ohne diesen Haken erfolgt diese Datenbereinigung nicht. Das bloße Aktivieren des Hakens löscht noch nichts.

Bei einem persistenten externen Objekt-Cache entfernt das Plugin zusätzlich die bekannten globalen und aktuellen Profil-Schlüssel.
Dynamische Cache-Schlüssel ohne Datenbankeintrag lassen sich über die WordPress-Schnittstelle nicht vollständig aufzählen.
Anmelde- und Abrufbegrenzungswerte laufen nach ihrer jeweiligen Frist ab.
Verwaiste Vergleichsstände früherer Profile können dagegen ohne Ablaufzeit im externen Cache verbleiben.
Lassen Sie bei einem solchen Cache die verbliebenen `gsh_tp_`-Einträge durch die Administration gezielt entfernen.
Das Plugin leert nicht den gemeinsamen Cache anderer Plugins.

Bereits versandte E-Mails, heruntergeladene Exporte, abonnierte Kalenderkopien und externe Sicherungen werden dadurch nicht gelöscht.
Browserdaten des Planers und Zugangsdaten in `wp-config.php` werden ebenfalls nicht durch die Plugin-Deinstallation entfernt.
Löschen oder widerrufen Sie solche Daten bei Bedarf gesondert.

Prüfen Sie nach der Deinstallation, ob die vorgesehenen Datenbanktabellen, Optionen und Sicherungsdateien tatsächlich entfernt wurden.
Dateiberechtigungen, Objekt-Caches und externe Sicherungssysteme müssen dabei durch die zuständige Administration berücksichtigt werden.

Curricu:lr ist ein ehrenamtliches Open-Source-Projekt ohne Supportvertrag.
