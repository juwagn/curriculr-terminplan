# Kiosk-Ansichten einrichten — Plugin v4.20.0

Das Plugin liefert zwei fertige Seitenvorlagen mit. Du musst **keine Dateien in den Theme-Ordner kopieren** — das Plugin registriert die Vorlagen automatisch im WordPress-Seiten-Editor.

---

## Überblick: zwei Kiosk-Seiten

| Seite | Vorlage | Für wen | Zugang |
|-------|---------|---------|--------|
| z. B. „Terminplan Live" | **Terminplan Kiosk** | Alle (IServ-Kachel, Schüler, Eltern) | Token-URL |
| z. B. „Terminplan Entwurf" | **Terminplan Entwurf-Vorschau** | Schulleitungsteam | Token-URL (separater Token) |

Beide Seiten sind ohne WordPress-Login erreichbar — der Zugang wird ausschließlich über den Token in der URL kontrolliert.

---

## Schritt 1 — Plugin aktualisieren

1. WordPress-Admin → **Plugins → Installierte Plugins**
2. „Schul-Terminplan Dashboard" deaktivieren
3. Plugin löschen *(Einstellungen und Daten bleiben erhalten)*
4. **Plugins → Neu hinzufügen → Plugin hochladen** → `curriculr-terminplan-4.20.0.zip` auswählen → Installieren → Aktivieren

---

## Schritt 2 — Seite „Terminplan Live" anlegen

1. **Seiten → Neu hinzufügen**
2. Titel: z. B. `Terminplan Live` *(nur intern sichtbar, kein Theme-Header erscheint)*
3. Rechte Seitenleiste → **Seitenattribute → Vorlage** → **„Terminplan Kiosk"** wählen
4. Seite **veröffentlichen**

> Die Vorlage erscheint im Dropdown nur wenn das Plugin aktiv ist. Falls sie fehlt: Browser-Cache leeren und Seite neu laden.

---

## Schritt 3 — Seite „Terminplan Entwurf" anlegen

1. **Seiten → Neu hinzufügen**
2. Titel: z. B. `Terminplan Entwurf`
3. Rechte Seitenleiste → **Seitenattribute → Vorlage** → **„Terminplan Entwurf-Vorschau"** wählen
4. Seite **veröffentlichen**

---

## Schritt 4 — Kiosk-Einstellungen konfigurieren

**Plugin-Admin → Terminplan → Tab „Kiosk & System"**

### Abschnitt „IServ-Einbettung (Kiosk-Modus)"

| Feld | Was eintragen |
|------|---------------|
| **Kiosk-Token** | Button „Neuen Token generieren" klicken → Token wird automatisch eingetragen |
| **IServ-Domain** | Vollständige URL eures IServ-Servers, z. B. `https://meine-schule.de` |

Nach dem Speichern wird die **Kiosk-URL** automatisch angezeigt, z. B.:
```
https://eure-schule.de/terminplan-live/?token=abc123...
```

Diese URL in IServ als iframe-Kachel hinterlegen.

### Abschnitt „Entwurf-Vorschau"

| Feld | Was eintragen |
|------|---------------|
| **Entwurf-Token** | Button „Neuen Token generieren" klicken |

Die **Entwurf-URL** wird ebenfalls automatisch angezeigt und kann direkt an das Schulleitungsteam weitergegeben werden.

---

## Schritt 5 — Bisherige Behelfslösung entfernen

Falls du den Shortcode bisher auf einer regulären Seite platziert hattest: diese Seite kann gelöscht oder auf eine der neuen Vorlagen-Seiten umgestellt werden. Die Shortcode-Variante funktioniert zwar, zeigt aber das volle Theme-Design und hat keinen Token-Schutz.

---

## Häufige Probleme

**„Terminplan Kiosk" erscheint nicht im Vorlage-Dropdown**
→ Plugin muss aktiv sein. Seite neu laden. Im Classic-Editor: rechte Spalte „Seitenattribute" aufklappen.

**Kiosk-URL wird im Admin nicht angezeigt**
→ Token fehlt oder Seite mit der richtigen Vorlage fehlt. Beide Fehlermeldungen werden direkt im Admin angezeigt.

**Seite zeigt „403 Forbidden"**
→ Token in der URL fehlt oder ist falsch. Kiosk-URL aus dem Plugin-Admin kopieren, nicht selbst zusammenstellen.

**Seite wird in IServ nicht eingebettet**
→ IServ-Domain im Plugin-Admin eintragen (Schritt 4). Ohne diese Einstellung blockiert der Browser die Einbettung.
