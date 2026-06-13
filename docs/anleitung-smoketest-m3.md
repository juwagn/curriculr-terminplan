# Smoke-Test M3 — IServ-SSO-Anmeldung (SPA v1.6.0)

Vor dem ersten produktiven Einsatz mit echten Nutzern. Schritt für Schritt durchgehen,
jeden Haken setzen.

---

## Voraussetzungen

- [ ] WP-Plugin v4.11.0 ist auf dem Server aktiv (ZIP hochgeladen, reaktiviert)
- [ ] IServ-SSO eingerichtet nach `docs/anleitung-iserv-sso.md`  
  (alle 4 Konstanten in `wp-config.php`, Redirect-URI im IServ-Client registriert)
- [ ] Du hast Zugang zu **zwei** IServ-Konten:  
  – eines **in** der erlaubten Gruppe (z. B. `Schulleitung`)  
  – eines **außerhalb** der Gruppe (zum Testen der Ablehnung)

---

## Schritt 1 — SPA deployen

```bash
cd /Users/julian.wagner/curriculr-planner/curriculr-planner
npm run build
```

Dann den Inhalt von `dist/` auf GitHub Pages pushen (wie bisher per `gh-pages`-Branch
oder GitHub Actions). Warten bis der Build durchgelaufen ist.

URL: `https://juwagn.github.io/curriculr-planner/`

---

## Schritt 2 — Datenbank-Migration prüfen

1. WP-Admin → **Curriculr → System-Tab**
2. Dort steht die aktuelle DB-Version. Muss **v5** anzeigen  
   (M3 hat `author_sub`/`author_name` in die Revisions-Tabelle aufgenommen).
3. Falls noch v4: einmal auf **„DB aktualisieren"** klicken (sofern vorhanden),  
   oder einfach einen Plan pushen — `dbDelta` läuft bei der nächsten REST-Anfrage.

---

## Schritt 3 — Login-Flow (erlaubtes Konto)

1. Planner öffnen: `https://juwagn.github.io/curriculr-planner/`
2. **Einstellungen → WordPress-Tab** öffnen
3. WordPress-Adresse eintragen (falls noch nicht gesetzt), Sync aktivieren
4. Button **„Mit IServ anmelden"** klicken → Browser leitet zu IServ um
5. Mit dem Konto aus der erlaubten Gruppe einloggen
6. Browser landet wieder auf dem Planner (mit `?exchange=...` in der URL,
   verschwindet sofort)

**Erwartetes Ergebnis:**
- WordPress-Tab zeigt **„Angemeldet als [Anzeigename]"** + Gruppen
- Keine Fehlermeldung

---

## Schritt 4 — Token prüfen (DevTools)

1. DevTools öffnen (F12) → **Application → Local Storage**
2. Prüfen: **kein** Eintrag mit `token`, `auth`, `jwt` o. ä.  
   (Token darf nur im RAM sein, nicht im localStorage)
3. **Session Storage** ebenso prüfen — dort auch kein Token
4. **Cookies** prüfen — kein `auth`-Cookie

---

## Schritt 5 — Verbindungstest

Im WordPress-Tab: **„Verbindung testen"** klicken.

**Erwartetes Ergebnis:**  
`Verbunden (Plugin 4.11.0)` (o. ä. Versionsangabe)

---

## Schritt 6 — Plan pushen

1. Im Planner einen Plan öffnen (oder im Wizard einen anlegen)
2. **Einstellungen → WordPress-Tab**: Schuljahr-Schlüssel + Profil-ID eintragen
3. Im Editor: **„Senden"** / WP-Sync-Button klicken → Status muss auf **„Entwurf"** wechseln

**In WP-Admin prüfen:**  
Curriculr → Revisionen → neueste Revision hat `author_sub` befüllt  
(nicht leer wie vor M3)

---

## Schritt 7 — Abmelden

Im WordPress-Tab: **„Abmelden"** klicken.

**Erwartetes Ergebnis:**
- Tab zeigt wieder **„Mit IServ anmelden"**
- EditorHeader: Name-Badge verschwunden
- DevTools → Network: POST an `/auth/logout` mit `Authorization: Bearer ...`
  (Status 200 oder 204 — fire-and-forget, Fehler egal)

---

## Schritt 8 — Nicht-berechtigtes Konto (Gruppenfilter)

1. Im Browser privates Fenster öffnen (oder anderen Browser)
2. Planner → Einstellungen → „Mit IServ anmelden"
3. Mit dem Konto **außerhalb** der erlaubten Gruppe einloggen

**Erwartetes Ergebnis:**
- IServ zeigt **„Zugriff verweigert"** (Gruppenfilter #1 am IServ-Client)  
  — oder WP gibt 403 zurück und der Planner zeigt Fehlermeldung  
  **„Anmeldung fehlgeschlagen — bitte erneut versuchen."**
- Kein Token ausgestellt

---

## Schritt 9 — Seiten-Reload nach Anmeldung

1. Mit berechtigtem Konto einloggen (Schritt 3)
2. Seite neu laden (F5)

**Erwartetes Ergebnis:**
- Nach Reload: **nicht mehr angemeldet** (Token war nur im RAM)
- WordPress-Tab zeigt „Mit IServ anmelden" — das ist korrekt so

---

## Schritt 10 — CSP-Header prüfen

1. DevTools → **Network** → Planner-Seite neu laden
2. Auf die `document`-Anfrage klicken → **Response Headers**
3. Kein `Content-Security-Policy`-Header dort  
   (CSP kommt als `<meta>`-Tag, nicht als HTTP-Header)
4. DevTools → **Elements** → `<head>` → `<meta http-equiv="Content-Security-Policy">`  
   muss vorhanden sein mit den 7 Direktiven

---

## Schritt 11 — Privacy-Tab prüfen

1. Einstellungen öffnen → **Datenschutz**-Tab
2. Inhalt prüfen: Abschnitte „Verarbeitete Daten", „Speicherorte", Vibecoding-Hinweis

---

## Abschluss

Alle Haken gesetzt? → **Go-live freigegeben.**

Bei Problemen in Schritt 3/4 (kein Token): WP-Admin → System-Tab → alle SSO-Konstanten
auf „gesetzt" prüfen. Redirect-URI im IServ-Client mit dem angezeigten Wert vergleichen.

Bei Problemen in Schritt 5 (Verbindungstest schlägt fehl): Bearer-Token abgelaufen?
Neu einloggen. CORS-Fehler in DevTools? WP-Plugin CORS-Filter prüfen
(Schritt 5 in `anleitung-iserv-sso.md`).
