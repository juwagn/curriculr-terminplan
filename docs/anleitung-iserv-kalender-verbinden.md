# Anleitung: IServ-Kalender direkt mit dem Terminplan verbinden

> Für alle, die den Terminplan auf der Schulwebsite betreiben wollen, ohne den
> Curriculr-Planer zu benutzen. Die Termine kommen dann weiterhin aus einem
> IServ-Kalender, so wie vor Version 4.24.0.

Ab Plugin-Version **4.36.0** gibt es das Eingabefeld für die IServ-Kalenderadresse
wieder. Zwischen 4.24.0 und 4.35.1 war es aus der Oberfläche verschwunden — das war
ein Versehen beim Umbau auf die neue Schuljahr-Verwaltung, kein bewusster Schnitt.

---

## Inhalt

1. [Was sich geändert hat](#1-was-sich-geändert-hat)
2. [Plugin aktualisieren](#2-plugin-aktualisieren)
3. [Kalenderadresse in IServ holen](#3-kalenderadresse-in-iserv-holen)
4. [Kalender im Plugin eintragen](#4-kalender-im-plugin-eintragen)
5. [Schulwochenstart und Quartalsgrenzen setzen](#5-schulwochenstart-und-quartalsgrenzen-setzen)
6. [Schuljahr aktiv schalten](#6-schuljahr-aktiv-schalten)
7. [Wenn keine Termine ankommen](#7-wenn-keine-termine-ankommen)
8. [Verhältnis zum Planer](#8-verhältnis-zum-planer)

---

## 1. Was sich geändert hat

In den Versionen bis 4.23 gab es pro Schuljahr ein Feld „iCal-Feed-URL". Dort trug
man die Adresse eines IServ-Kalenders ein, und das Plugin holte sich die Termine von
dort ab.

Mit 4.24.0 kam der Curriculr-Planer dazu. Seitdem läuft es normalerweise andersherum:
der Planer schickt den fertigen Plan an WordPress, WordPress erzeugt daraus einen
eigenen ICS-Feed, und IServ abonniert diesen Feed. Beim Umbau wurde dasselbe Feld für
diese neue, ausgehende Adresse weiterverwendet und auf schreibgeschützt gestellt. Damit
war der alte Weg zwar technisch noch vorhanden, aber nicht mehr bedienbar.

Seit 4.36.0 gibt es beide Wege nebeneinander. Jeder Kalender hat jetzt eine Quelle:
entweder den Planer oder einen externen IServ-Kalender.

---

## 2. Plugin aktualisieren

Im WordPress-Backend unter **Plugins → Installierte Plugins** das alte Plugin
deaktivieren und löschen, dann `curriculr-terminplan-4.36.0.zip` über
**Plugins → Installieren → Plugin hochladen** einspielen und aktivieren.

Einstellungen, Schuljahre und Kategorien überstehen das, weil sie in der Datenbank
liegen und nicht im Plugin-Ordner.

Beim ersten Aufruf des Backends nach dem Update prüft das Plugin einmalig alle
vorhandenen Kalender. Adressen, die nicht auf den eigenen Feed zeigen, werden
automatisch als extern markiert. Ein Schuljahr, in dem früher eine IServ-Adresse
stand, funktioniert dadurch wieder wie vorher.

---

## 3. Kalenderadresse in IServ holen

Du brauchst die ICS-Adresse des Kalenders, der die Termine enthält. In IServ findest
du sie beim jeweiligen Kalender unter der Freigabe- beziehungsweise Abonnieren-Funktion.
Je nach IServ-Version heißt der Punkt „Kalender abonnieren", „Freigabe" oder
„Veröffentlichen".

Zwei Dinge sind wichtig:

- Die Adresse muss mit `https://` anfangen. Kopierst du eine `webcal://`-Adresse,
  ersetze `webcal` durch `https`.
- Der Kalender muss so freigegeben sein, dass die Adresse ohne Anmeldung funktioniert.
  Der Server der Schulwebsite meldet sich nicht bei IServ an, er ruft die Adresse
  einfach auf.

Zum Prüfen die Adresse in einem privaten Browserfenster öffnen. Wenn eine Datei
heruntergeladen wird oder Text erscheint, der mit `BEGIN:VCALENDAR` beginnt, passt es.
Landest du auf der IServ-Anmeldeseite, ist der Kalender noch nicht offen genug
freigegeben.

---

## 4. Kalender im Plugin eintragen

Gehe auf **Einstellungen → Schul-Terminplan → Schuljahre** und suche die Karte des
Schuljahres, um das es geht.

Im Bereich **IServ-Kalender direkt verbinden** die Adresse einfügen und auf
**Kalender verbinden** klicken.

Das Plugin ruft die Adresse sofort ab und meldet zurück, wie viele Termine es gefunden
hat. Ab jetzt holt es sich die Termine stündlich selbst. Wenn du in IServ etwas änderst
und nicht warten willst, klickst du auf **Jetzt neu abrufen**.

Ein so verbundener Kalender wird in der Liste darunter mit dem Hinweis „IServ" geführt.
Übertragungen aus dem Planer lassen ihn in Ruhe.

Willst du die Verbindung wieder lösen, leerst du das Feld und speicherst erneut.

---

## 5. Schulwochenstart und Quartalsgrenzen setzen

Diese beiden Angaben kommen sonst aus dem Planer. Ohne Planer trägst du sie von Hand
ein, sonst bleibt die Quartalsansicht leer.

Im selben Schuljahr-Bereich, etwas weiter oben:

**Start Schulwoche 01** — der Montag der ersten Schulwoche. Auch dann, wenn der
Unterricht erst am Mittwoch anfängt.

**Quartalsgrenzen** — pro Zeile ein Quartal, Start und Ende durch einen senkrechten
Strich getrennt:

```
2026-08-12|2026-10-10
2026-10-26|2026-12-18
2027-01-05|2027-03-26
2027-04-13|2027-07-01
```

**Cache-Dauer** steht auf 3600 Sekunden, also eine Stunde. So lange kann es dauern,
bis eine Änderung aus IServ auf der Website ankommt. Kleiner als 300 geht nicht.

Danach auf **Einstellungen speichern**.

---

## 6. Schuljahr aktiv schalten

Auf der Schulwebsite wird immer das aktive Schuljahr angezeigt. In der Kopfzeile der
Schuljahr-Karte klickst du dafür auf **Als aktives Schuljahr setzen**. Das bisher
aktive Schuljahr wird dabei abgelöst.

Anschließend die Seite mit dem Shortcode `[gsh_terminplan]` aufrufen und nachsehen, ob
die Termine in den Quartalen stehen. Die Druckansicht arbeitet mit denselben Daten,
die musst du nicht getrennt einrichten.

---

## 7. Wenn keine Termine ankommen

**„Adresse gespeichert, aber der Abruf hat nicht geklappt"**
Fast immer ist der Kalender in IServ nicht offen genug freigegeben. Mach den Test aus
Abschnitt 3 im privaten Browserfenster.

**„Nur HTTPS erlaubt"**
Die Adresse fängt mit `webcal://` oder `http://` an. Vorne auf `https://` ändern.

**Termine da, aber Quartale leer**
Dann fehlen Schulwochenstart oder Quartalsgrenzen, oder die Termine liegen außerhalb
der eingetragenen Zeiträume. Abschnitt 5.

**Alles eingetragen, Seite zeigt trotzdem nichts**
Vermutlich ist ein anderes Schuljahr aktiv. Abschnitt 6.

**Änderungen aus IServ kommen nicht an**
Auf **Jetzt neu abrufen** klicken. Kommt die Änderung dann, war es nur der Cache.
Unter **System & Logs** stehen die letzten Abrufversuche mit Fehlermeldung.

---

## 8. Verhältnis zum Planer

Beide Wege lassen sich mischen, aber nicht für denselben Kalender. Ein Schuljahr, das
mit einem IServ-Kalender verbunden ist, wird nicht mehr aus dem Planer befüllt — auch
dann nicht, wenn jemand dort auf Senden drückt. Umgekehrt genauso.

Was der Planer zusätzlich kann und über den IServ-Weg nicht geht: Anmerkungen in der
eigenen Spalte, Versionsstände mit Autor, die Stufen Entwurf / Intern / Öffentlich und
die Gruppen-Filter. Wer nur die Quartalsübersicht und das PDF braucht, kommt ohne aus.

Falls du später doch umsteigen willst: der Planer kann eine ICS-Datei einlesen
(**Aus ICS-Datei importieren** auf dem Startbildschirm). Du exportierst den Kalender
aus IServ einmal als Datei und hast den Plan drin, ohne alles neu zu tippen.
