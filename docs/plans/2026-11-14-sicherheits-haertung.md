# Sicherheits-Härtung 4.46.2

## Ziel und Freigabe

Aufgabe 1 der Übergabe: SEC-01, SEC-04, SEC-06 und SEC-07.
Die Umsetzung ist freigegeben; Merge, Release und ZIP bleiben einer gesonderten Freigabe vorbehalten.
Der Betreiber hat bestätigt: Daten nur bei gesetztem Löschhaken entfernen, Standard ausgeschaltet.

## Ablauf

- [x] Regressionstests für fehlende Konfiguration, Abrufbegrenzung und Deinstallation zuerst ausführen.
- [x] Anmeldung ohne gültige HTTPS-Planer-Adresse sicher ablehnen; konfigurierte Anmeldung und CORS erhalten.
- [x] Gemeinsame Abrufbegrenzung für Anmeldung, Token-Austausch und ungültige Feed-Abrufe ergänzen.
- [x] Opt-in für vollständige Datenlöschung implementieren und testen; Deaktivierung und Standard-Deinstallation erhalten die Plandaten.
- [x] Datenschutz und Sicherheits-Meldeweg mit belegten Aufbewahrungsregeln dokumentieren.
- [x] Version 4.46.2 und Update-Hinweise ergänzen.
- [x] Gesamte PHP-Testreihe, Syntaxprüfung und Planer-Kompatibilität prüfen; Änderungen unabhängig durchsehen.
- [x] Pull Request mit Ergebnissen, Sicherheitsrisiken und offenen Betriebsprüfungen erstellen: [Entwurf #17](https://github.com/juwagn/curriculr-terminplan/pull/17).

## Betriebsschutz

Keine Zugriffe auf produktives WordPress oder IServ. Keine Datenmigration, keine neuen REST-Routen, keine Änderung erfolgreicher Antwortformate.
Öffentliche Kalender-Abrufe mit gültigem Token werden nicht begrenzt.
Vor Installation muss der Betreiber CURRICULR_SPA_URL auf eine gültige HTTPS-Adresse prüfen; ohne diese Angabe wird die Anmeldung künftig verweigert.
Ein lokaler Stub-Test ersetzt keine Prüfung auf einer WordPress-Testinstallation.

## Prüfergebnis

- Ausgangsstand: 34 PHP-Testdateien, keine Fehlschläge.
- Neue Tests zuerst rot: fehlende Konfiguration/Abrufbegrenzung, Deinstallation und leere Guard-Audience.
- Abschluss: 37 PHP-Testdateien, 909 erfolgreiche Einzelprüfungen, keine Fehlschläge; alle sieben Plugin-PHP-Dateien ohne Syntaxfehler.
- JavaScript-Syntax und `git diff --check` ohne Fehler.
- Planer unverändert: Typprüfung, Linter, 47 Testdateien mit 332 Tests und Produktionsbuild erfolgreich. Vite meldet den bestehenden großen Bundle-Chunk.
- Lokaler HTTP-Test mit synthetischer Konfiguration: fehlende Konfiguration entfernt Origin/Credentials; gültige Konfiguration liefert die Planer-Origin.
- Lokale Admin-Vorschau mit echtem Plugin-Markup: Löschhaken standardmäßig aus, beschriftet, ein- und ausschaltbar; Darstellung angesehen.
- Unabhängige Prüfung: fehlende Audience-Absicherung und globale Cache-Snapshots erkannt und korrigiert.

## Vor Freigabe zum Einsatz

- Der Betreiber muss die gültige HTTPS-Konstante `CURRICULR_SPA_URL` auf dem Server bestätigen. Keine Geheimnisse weitergeben.
- Anmeldung, Speichern und Kalender-Abos auf einer WordPress-Testinstallation prüfen; lokale Stubs sind kein vollständiger WordPress-/IServ-Test.
- Bei externem Objekt-Cache können nicht aufzählbare verwaiste Profil-Snapshots bestehen bleiben. Deren gezielte Bereinigung durch die Administration ist in DATENSCHUTZ.md beschrieben.
- GitHub muss vertrauliche Sicherheitsmeldungen anbieten; Aufbewahrungsregeln externer Maildienste, Logs und Sicherungen liegen beim Betreiber.
- Kein Merge, Tag, ZIP oder Release durch diese Umsetzung.
