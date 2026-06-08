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

5. **Stage → Öffentlich schalten**: WP-Profil erhält automatisch die Feed-URL.

6. **IServ**: Neues Kalender-Abo mit der neuen Feed-URL anlegen.
   Die neue Feed-URL steht im Planner → Einstellungen → WordPress-Tab → Feed-URL.

7. **E2E-Test**: Einen Testtermin einfügen → WP-Anzeige + IServ prüfen → löschen.

## Unterjährige Änderung

1. Termin im Planner ändern.
2. Automatischer debounced Sync (alle 2–5 s nach letzter Änderung, falls Stage = Öffentlich).
3. WP-Anzeige aktualisiert sich automatisch. IServ beim nächsten Pull-Intervall.

## Stage-Workflow

| Stage | Bedeutung | Wer sieht es |
|---|---|---|
| Entwurf | Lokal + WP gespeichert, aber nicht angezeigt | Nur Schulleitung (via Planner) |
| Genehmigt | Intern geprüft, noch nicht öffentlich | Schulleitungsteam via Entwurf-Kiosk-Link |
| Öffentlich | Anzeige + IServ-Feed aktiv | Alle |

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
