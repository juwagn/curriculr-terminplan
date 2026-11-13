# Termine für Eltern auf der Website einbetten

## Sie brauchen
- Das Plugin **Curricu:lr Terminplan** ab Version 4.46.0.
- Termine, die im Curricu:lr Planer veröffentlicht sind.
- Zugang zum Bearbeiten der Seite, auf der die Termine erscheinen sollen.

## Gruppen und Kategorien
- **Gruppen** bestimmen, wer einen Termin sieht. Sie vergeben sie im Planer am Termin, zum Beispiel „Eltern“.
- **Kategorien** bestimmen nur Farbe und Legende. Sie filtern nicht.
- Eine Gruppenansicht zeigt nur Termine, denen diese Gruppe ausdrücklich zugewiesen ist. Groß- und Kleinschreibung spielen keine Rolle.
- Termine ohne Gruppe erscheinen in einer Gruppenansicht nicht.

## Schritte
1. Öffnen Sie im Planer jeden Termin, den Eltern sehen sollen.
2. Wählen Sie bei **Gruppen** den Eintrag „Eltern“. Veröffentlichen Sie anschließend neu.
3. Öffnen Sie die Seite in WordPress und fügen Sie einen Shortcode ein:
   - Terminliste: `[gsh_termine gruppe="Eltern" max="6"]`
   - Monatskalender mit Kalender-Abo: `[gsh_monat gruppe="Eltern" abo="ja"]`
4. Speichern Sie die Seite und leeren Sie den Seiten-Cache.

Die Attribute `kategorien` und `auch_kategorien` brauchen Sie nicht mehr. Sie werden ignoriert. Entfernen Sie sie aus älteren Shortcodes.

## Darstellung im Dunkelmodus
Eingebettete Kalender sind standardmäßig hell, auch wenn der Browser im Dunkelmodus ist. Mit dem Attribut `design` ändern Sie das:

| Wert | Wirkung |
|---|---|
| `hell` | Immer hell. Das ist der Standard. |
| `auto` | Folgt dem Dunkelmodus des Browsers. |
| `dunkel` | Immer dunkel. |

Verwenden Sie `auto` und `dunkel` nur, wenn Ihre Website selbst einen Dunkelmodus hat. Sonst steht ein dunkler Kalender auf hellem Grund.

Beispiel: `[gsh_monat gruppe="Eltern" design="auto"]`

## So prüfen Sie, ob es funktioniert
1. Öffnen Sie die Seite in einem Browser-Fenster ohne Anmeldung.
2. Prüfen Sie, dass nur Termine mit der Gruppe „Eltern“ erscheinen.
3. Prüfen Sie, dass ein Termin ohne Gruppe fehlt.
4. Schalten Sie den Dunkelmodus Ihres Geräts ein. Der Kalender bleibt lesbar.

## Häufige Probleme
- **Die Liste ist leer.** Kein Termin trägt die Gruppe „Eltern“. Vergeben Sie die Gruppe im Planer und veröffentlichen Sie neu.
- **Ein Termin fehlt.** Er hat keine Gruppe oder eine andere. Termine ohne Gruppe erscheinen in Eltern-Ansichten nicht.
- **Die Seite zeigt noch den alten Stand.** Leeren Sie den Seiten-Cache.
- **Das Kalender-Abo meldet „nicht gefunden“.** In der Abo-Adresse fehlt der Gruppenname.
