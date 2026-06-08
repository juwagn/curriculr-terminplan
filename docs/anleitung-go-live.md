# Go-Live-Checkliste: Curriculr-Sync freischalten

Alle Code-Arbeiten (SP1–SP4) sind abgeschlossen. Diese Anleitung beschreibt die
verbleibenden manuellen Schritte bis zum vollständigen Betrieb.

---

## Schritt 1 — SP4-PR auf GitHub mergen

SP4 (Revisions + Nacht-Backup, v4.9.0) liegt als offener PR vor.

- [ ] GitHub öffnen: https://github.com/juwagn/curriculr-terminplan/pull/4
- [ ] PR **mergen** (Merge commit oder Squash — egal)
- [ ] Lokal aufräumen:
  ```bash
  cd curriculr-terminplan
  git checkout main && git pull origin main
  ```

---

## Schritt 2 — Plugin v4.9.0 auf WordPress hochladen

Die Dateien `plugin/gsh-terminplan.php` und `plugin/curriculr-data-layer.php`
müssen auf den WP-Server.

- [ ] Beide Dateien via SFTP / WP-Dateimanager in das Plugin-Verzeichnis
  (`wp-content/plugins/curriculr-terminplan/plugin/`) hochladen
- [ ] Plugin reaktivieren (WP-Admin → Plugins → deaktivieren → aktivieren)  
  **Oder** via WP-CLI:
  ```bash
  wp plugin deactivate curriculr-terminplan
  wp plugin activate curriculr-terminplan
  ```
- [ ] Erwartetes Ergebnis: Plugin zeigt Version **4.9.0** in der Plugins-Liste

> Bei der Reaktivierung legt `dbDelta` automatisch die neue Tabelle
> `wp_curriculr_doc_revisions` an. Keine manuelle DB-Migration nötig.

---

## Schritt 3 — WP-Admin konfigurieren

**WP-Admin → Terminplan → Einstellungen → System-Tab → Curriculr Planner-Sync**

- [ ] **Erlaubte Planner-Adresse** prüfen:  
  Muss `https://juwagn.github.io` enthalten (Standard, keine Änderung nötig)
- [ ] **Profil-Zuordnung** setzen:
  - Schuljahr-Feld: `sj_2026_27`
  - Profil-Dropdown: das Profil wählen, das aktuell die Terminplan-Anzeige zeigt
    (i.d.R. das aktive Profil, erkennbar an „(aktiv)" im Dropdown)
  - **„Curriculr-Sync speichern"** klicken → grüne Erfolgsmeldung
- [ ] REST-Schnittstelle testen — im Browser aufrufen:
  ```
  https://<deine-schul-domain>/wp-json/curriculr/v1/health
  ```
  Erwartet: `401 Unauthorized` (ohne Auth — das ist korrekt)

---

## Schritt 4 — WP Application Password erstellen

- [ ] WP-Admin → **Benutzer → Profil** (dein Admin-Benutzer)
- [ ] Abschnitt **Anwendungspasswörter** → Name `Curriculr Planner` eingeben →
  **„Neues Anwendungspasswort hinzufügen"**
- [ ] Passwort notieren (wird nur einmal angezeigt):
  ```
  Format: XXXX XXXX XXXX XXXX XXXX XXXX
  ```

---

## Schritt 5 — Planner konfigurieren

Planner öffnen: **https://juwagn.github.io/curriculr-planner/**

- [ ] Schuljahr-Dokument öffnen
- [ ] **Einstellungen → Tab „WordPress"** öffnen
- [ ] Felder ausfüllen:
  | Feld | Wert |
  |------|------|
  | WordPress-URL | `https://<deine-schul-domain>` (ohne Pfad, ohne Slash am Ende) |
  | Benutzername | dein WP-Admin-Benutzername |
  | Application Password | das Passwort aus Schritt 4 |
  | Schuljahr-Schlüssel | `sj_2026_27` |
- [ ] **„Verbindung prüfen"** klicken → grüner Chip **„Verbunden"**

---

## Schritt 6 — Initialer PUT + Öffentlich schalten

- [ ] Im Planner-Editor: **Stage-Chip → „Senden"**  
  Erwartet: Chip wechselt zu „Entwurf", Toast „Gespeichert"
- [ ] **Stage-Chip → „Veröffentlichen" → „Öffentlich schalten"** bestätigen  
  Erwartet: Chip wechselt zu „Öffentlich", WP-Terminplan-Anzeige wird sofort
  aktualisiert
- [ ] **Feed-URL ablesen**: Einstellungen → WordPress-Tab → Feed-URL kopieren  
  Format: `https://<domain>/wp-json/curriculr/v1/feed/sj_2026_27/<token>.ics`  
  Diese URL wird in Schritt 7 benötigt.

---

## Schritt 7 — IServ-Kalender-Abo einrichten

- [ ] IServ-Admin → **Kalender → Kalender-Abonnements**  
  (oder: Gruppen-Kalender → Externen Kalender hinzufügen)
- [ ] Neues Abo anlegen:
  - **URL**: Feed-URL aus Schritt 6
  - **Name**: `Curriculr Schuljahr 2026/27`
  - Aktualisierungsintervall: Standard übernehmen
  - Speichern
- [ ] Im IServ-Kalender prüfen, ob Termine erscheinen  
  Falls nicht: IServ-Admin → Kalender → **„Jetzt synchronisieren"**
- [ ] Auf einem Lehrer-Endgerät prüfen, ob die Termine nach der nächsten
  Sync-Runde erscheinen

---

## Schritt 8 — E2E-Smoke-Test

Bestätigt den vollständigen Datenfluss: Planner → WP → ICS-Feed → IServ.

- [ ] Im Planner einen **Test-Termin** anlegen:
  - Titel: `E2E-Test Go-Live`
  - Datum: ein Datum in der Zukunft
- [ ] **„Öffentlich schalten"** → Header zeigt „Synchron"
- [ ] WP-Terminplan-Seite im Browser aufrufen → Test-Termin sichtbar?
- [ ] Feed-URL direkt prüfen:
  ```bash
  curl "https://<domain>/wp-json/curriculr/v1/feed/sj_2026_27/<token>.ics" | grep "E2E-Test"
  ```
  Erwartet: `SUMMARY:E2E-Test Go-Live` im Output
- [ ] Test-Termin wieder **löschen** → „Öffentlich schalten" → WP-Anzeige prüfen
  → Termin verschwunden

---

## Fertig ✓

Wenn alle Checkboxen gesetzt sind, ist der Curriculr-Sync vollständig in Betrieb:

- Schulleitung editiert Termine im Planner
- Änderungen landen automatisch in WordPress (Terminplan-Anzeige)
- IServ-Kalender zieht den Feed regelmäßig → Geräte-Kalender aktuell

Für künftige Schuljahre: siehe [sop-curriculr-sync.md](sop-curriculr-sync.md)
