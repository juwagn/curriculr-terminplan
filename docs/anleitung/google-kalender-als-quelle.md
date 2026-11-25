# Google-Kalender als Quelle für Terminplan und Kiosk

Der Terminplan kann seine Termine aus einem Google-Kalender holen. Die Kiosk-Seite zeigt dann diese Termine. Den Curricu:lr Planer brauchen Sie dafür nicht.

## Sie brauchen
- Einen Google-Kalender mit den Schulterminen.
- Ein Google-Konto mit dem Recht, die Einstellungen dieses Kalenders zu ändern.
- Zugang zu WordPress mit der Rolle Administrator/in.
- Eine eingerichtete Kiosk-Seite. Siehe [Kiosk-Ansichten einrichten](../anleitung-kiosk-einrichtung.md).

## Bevor Sie beginnen: Datenschutz
Der Server Ihrer Schulwebsite ruft den Kalender stündlich bei Google ab. Diese Verbindung nach außen verantwortet Ihre Schule.

- Nehmen Sie den Abruf in die Datenschutzerklärung der Schulwebsite auf.
- Tragen Sie in den Google-Kalender nur Termine ein, die im Kiosk erscheinen dürfen.
- Verzichten Sie auf Schülernamen in Titeln und Beschreibungen.

## Schritt 1: Adresse in Google holen
1. Öffnen Sie Google Kalender im Browser.
2. Zeigen Sie links unter **Meine Kalender** auf den Kalender.
3. Klicken Sie auf die drei Punkte und wählen Sie **Einstellungen und Freigabe**.
4. Scrollen Sie zum Abschnitt **Kalender integrieren**.
5. Kopieren Sie eine der beiden Adressen im iCal-Format.

Welche Adresse passt?

| Adresse | Wann verwenden | Beispiel |
|---|---|---|
| **Privatadresse im iCal-Format** | Der Kalender bleibt privat. Nur wer die Adresse kennt, sieht die Termine. Empfohlen. | `https://calendar.google.com/calendar/ical/termine.muster-gesamtschule%40gmail.com/private-1a2b3c4d5e6f/basic.ics` |
| **Öffentliche Adresse im iCal-Format** | Der Kalender ist ohnehin öffentlich freigegeben. | `https://calendar.google.com/calendar/ical/termine.muster-gesamtschule%40gmail.com/public/basic.ics` |

Die öffentliche Adresse funktioniert nur, wenn unter **Zugriffsberechtigungen für Termine** das Feld **Öffentlich freigeben** angehakt ist.

Behandeln Sie die Privatadresse wie ein Passwort. Geben Sie sie nicht weiter.

## Schritt 2: Adresse prüfen
1. Öffnen Sie ein privates Browserfenster.
2. Fügen Sie die Adresse ein und öffnen Sie sie.
3. Es wird eine Datei geladen oder Text mit `BEGIN:VCALENDAR` angezeigt. Dann passt die Adresse.

Erscheint eine Anmeldeseite oder ein Fehler, ist die Adresse falsch oder der Kalender nicht freigegeben.

## Schritt 3: Kalender im Terminplan verbinden
1. Öffnen Sie in WordPress **Einstellungen → Schul-Terminplan → Schuljahre**.
2. Suchen Sie die Karte des Schuljahres.
3. Fügen Sie die Adresse im Bereich **IServ-Kalender direkt verbinden** ein. Das Feld nimmt auch Google-Adressen an.
4. Klicken Sie auf **Kalender verbinden**.
5. Lesen Sie die Meldung. Sie nennt die Zahl der gefundenen Termine.

Tragen Sie ohne Planer auch **Start Schulwoche 01** und die **Quartalsgrenzen** ein. Wie das geht, steht in [IServ-Kalender direkt verbinden](../anleitung-iserv-kalender-verbinden.md), Abschnitt 5.

Beispiel für die Quartalsgrenzen, eine Zeile je Quartal:

```
2026-08-12|2026-10-10
2026-10-26|2026-12-18
2027-01-05|2027-03-26
2027-04-13|2027-07-01
```

## Schritt 4: Schuljahr aktiv schalten
1. Klicken Sie in der Kopfzeile der Schuljahr-Karte auf **Als aktives Schuljahr setzen**.
2. Öffnen Sie die Kiosk-Seite. Sie zeigt jetzt die Google-Termine.

## Farben: Kategorien über Stichwörter
Google übergibt keine Kategorien. Der Terminplan färbt Google-Termine deshalb über Stichwörter im Titel.

Beispiele mit den Standard-Stichwörtern:

| Titel in Google | Kategorie im Terminplan |
|---|---|
| `Zeugniskonferenz` | Konferenzen |
| `Elternabend Klasse 5` | Jahrgang 5/6 |
| `Abiturprüfung Deutsch` | Oberstufe |
| `Herbstferien` | Feiertage |
| `Ausflug` | keine Farbe |

Die Stichwörter ändern Sie unter **Einstellungen → Schul-Terminplan → Kategorien**.

Kurze Stichwörter wie `ef` oder `db` treffen auch mitten in Wörtern. Schreiben Sie deshalb eindeutige Titel.

## Grenzen
- **Serientermine:** Der Terminplan liest keine Wiederholungen. Von einem wöchentlichen Termin erscheint nur der erste. Legen Sie wiederkehrende Termine in Google als Einzeltermine an.
- **Uhrzeiten:** Google speichert Uhrzeiten oft in Weltzeit (UTC). Der Terminplan zeigt dann eine bis zwei Stunden zu früh an. Prüfen Sie einen Termin mit Uhrzeit nach dem Verbinden. Ganztägige Termine sind nicht betroffen.
- **Verzögerung:** Änderungen in Google erscheinen nach bis zu einer Stunde. Google aktualisiert die Adresse zusätzlich mit eigener Verzögerung.
- **Planer und Google:** Ein Schuljahr hat genau eine Quelle. Ein mit Google verbundenes Schuljahr übernimmt keine Termine aus dem Planer.

## So prüfen Sie, ob es funktioniert
1. Legen Sie in Google einen ganztägigen Testtermin für morgen an, Titel `Test Konferenz`.
2. Klicken Sie in WordPress auf **Jetzt neu abrufen**.
3. Öffnen Sie die Kiosk-Seite.
4. Der Termin steht am richtigen Tag und trägt die Farbe der Kategorie Konferenzen.
5. Löschen Sie den Testtermin in Google.

## Häufige Probleme

**„Nur HTTPS erlaubt“**
Die Adresse beginnt mit `webcal://` oder `http://`. Ersetzen Sie den Anfang durch `https://`.

**Abruf schlägt fehl**
Die öffentliche Adresse ist ohne Freigabe nicht erreichbar. Nutzen Sie die Privatadresse oder geben Sie den Kalender frei. Machen Sie den Test aus Schritt 2.

**Termine fehlen**
Prüfen Sie, ob es Serientermine sind. Prüfen Sie, ob die Termine innerhalb der Quartalsgrenzen liegen.

**Uhrzeit falsch**
Siehe **Grenzen → Uhrzeiten**.

**Kiosk zeigt alte Termine**
Klicken Sie auf **Jetzt neu abrufen**. Hilft das nicht, ist ein anderes Schuljahr aktiv.

**Privatadresse ist bekannt geworden**
Klicken Sie in Google unter **Kalender integrieren** auf **Zurücksetzen**. Tragen Sie die neue Adresse in WordPress ein.
