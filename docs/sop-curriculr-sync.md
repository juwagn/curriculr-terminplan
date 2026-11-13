# SOP: Curriculr-Sync — Schuljahreswechsel und Ersteinrichtung

## Voraussetzungen

- WordPress-Plugin curriculr-terminplan ≥ v4.8.0 aktiv
- Curriculr Planner unter https://juwagn.github.io/curriculr-planner/ erreichbar
- WP Application Password für Planner-User generiert

## Neues Schuljahr einrichten

1. **WP-Admin → Terminplan → Einstellungen → Schuljahr-Profile**:
   Neues Schuljahr-Profil anlegen (z.B. `sj_2027_28`), iCal-URL zunächst leer lassen.

2. **WP-Admin → System-Tab → Profil-Zuordnung**:
   Schuljahr-Schlüssel `sj_2027_28` dem neuen Profil zuordnen. Speichern.

3. **Planner → Einstellungen → WordPress-Tab**:
   Schuljahr-Schlüssel auf `sj_2027_28` setzen. Verbindung prüfen.

4. **Planner → neues Schuljahr per Wizard anlegen** → Termine eingeben.

5. **Stage → Öffentlich schalten**: Erst jetzt erhält das WP-Profil die Feed-URL und den Kalender-Cache. Website-Anzeige und Feed liefern den Plan erst ab dieser Stufe.

6. **IServ**: Neues Kalender-Abo mit der neuen Feed-URL anlegen.
   Die neue Feed-URL steht im Planner → Einstellungen → WordPress-Tab → Feed-URL.

7. **E2E-Test**: Einen Testtermin einfügen → WP-Anzeige + IServ prüfen → löschen.

## Unterjährige Änderung

1. Termin im Planner ändern.
2. Im Planner **Meinen Stand senden** wählen. Der Planner überträgt nur auf Klick, es gibt keinen automatischen Sync. Gesendet wird die zuletzt gewählte Stufe.
3. Ist die Stufe „Öffentlich“, aktualisiert sich die WP-Anzeige sofort. IServ folgt beim nächsten Pull-Intervall.

## Stage-Workflow

Ablauf im Planner: Entwurf → Intern (Genehmigt) → Öffentlich. Eine Rückstufung gibt es nicht. Der Server prüft die Stufe bei jedem öffentlichen Zugriff.

| Stage | Bedeutung | Wer sieht es |
|---|---|---|
| Entwurf | Lokal + WP gespeichert, aber nicht angezeigt | Schulleitung via Planner; WP-Admin (Stufen-Hinweis) |
| Genehmigt (Intern) | Gespeichert, noch nicht öffentlich | wie Entwurf; Entwurf-Kiosk-Link nur, wenn ein Kalender in WP als „Entwurf“ markiert ist |
| Öffentlich | Website-Anzeige + Feed aktiv | Alle |

Was bei Entwurf und Intern gilt:
- Feed und Gruppen-Feed antworten mit 404 (wie bei falschem Token).
- `[gsh_termine]` und `[gsh_monat]` bleiben für Besucher leer, `[gsh_terminplan]` und der Kiosk zeigen „Dieser Terminplan ist noch nicht freigegeben.“
- Der zuletzt veröffentlichte Kalender-Cache bleibt unverändert, wird aber nicht angezeigt, solange das Dokument nicht öffentlich ist.
- Kalender, die in WP als „Entwurf“ markiert sind, erhalten den neuen Stand weiterhin für die Entwurf-Vorschau (Entwurf-Kiosk-Link).
- Kalender mit externer Quelle (IServ-Feed) bleiben sichtbar; nur die Anmerkungen aus dem Planner entfallen.

## Troubleshooting

**Anzeige aktualisiert sich nicht nach „Öffentlich schalten":**
- WP-Admin → Terminplan → Synchronisierung → „Jetzt aktualisieren" für das betroffene Profil.
- Prüfen: WP-Admin → System → Profil-Zuordnung korrekt gesetzt?

**IServ zeigt keine neuen Termine:**
- IServ-Admin → Kalender → Abo manuell synchronisieren.
- Prüfen: Feed-URL im IServ-Abo identisch mit der URL im Planner-Tab?

**Planner zeigt „Verbindungsfehler":**
- WP-Admin → Einstellungen → Permanentlinks → Speichern (erneuert REST-Routen).
- CORS: Erlaubte Planner-Adresse im System-Tab korrekt?
- Application Password nicht abgelaufen/widerrufen?
