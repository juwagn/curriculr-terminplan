# Anleitung: Git-History von schulinternen Daten bereinigen

> **Wichtig:** Diese Anleitung richtet sich an IT-Beauftragte, die das Repository
> regelmäßig auf schulspezifische oder datenschutzkritische Inhalte prüfen und
> diese bei Bedarf aus der Git-History entfernen möchten.

---

## Inhalt

1. [Wann ist diese Anleitung nötig?](#1-wann-ist-diese-anleitung-nötig)
2. [Vorbereitung: Backup erstellen](#2-vorbereitung-backup-erstellen)
3. [Begriffe finden, die bereinigt werden müssen](#3-begriffe-finden-die-bereinigt-werden-müssen)
4. [Tool installieren](#4-tool-installieren)
5. [History bereinigen](#5-history-bereinigen)
6. [Ergebnis prüfen](#6-ergebnis-prüfen)
7. [Zurück zu GitHub pushen](#7-zurück-zu-github-pushen)
8. [Checkliste für regelmäßige Prüfung](#8-checkliste-für-regelmäßige-prüfung)

---

## 1. Wann ist diese Anleitung nötig?

Du musst die Git-History bereinigen, wenn:

- **Schulname, Domain oder interne Kennungen** versehentlich in Commits gelandet sind
- **Personendaten** (Lehrernamen, E-Mail-Adressen) im Code oder in Commit-Messages stehen
- Das Repository **öffentlich** gemacht werden soll
- Du **regelmäßig prüfen** möchtest, ob neue Commits sensible Daten enthalten

> **Achtung:** Ein History-Rewrite ändert alle Commit-Hashes. Alle Mitarbeitenden
> müssen das Repository danach **neu klonen**.

---

## 2. Vorbereitung: Backup erstellen

**Niemals ohne Backup starten!**

### Variante A: Schnelles Ordner-Backup (empfohlen für Windows)

Öffne PowerShell und führe aus:

```powershell
$src = '"C:\Pfad\zu\deinem\Projekt"'
$dest = '"C:\Pfad\zu\BACKUP_Terminplan_$(Get-Date -Format yyyy-MM-dd)"'
robocopy $src $dest /E /XD node_modules .claude Backup __pycache__ .venv .git\objects\pack | Out-Null
Write-Host "Backup erstellt unter: $dest" -ForegroundColor Green
```

### Variante B: Git-Bare-Repository (professionell)

```powershell
cd ..  # Ein Ordner höher
git clone --mirror "Pfad\zu\deinem\Projekt" BACKUP_$(Get-Date -Format yyyy-MM-dd).git
```

> **Prüfung:** Der Backup-Ordner muss alle Dateien und den `.git`-Ordner enthalten.

---

## 3. Begriffe finden, die bereinigt werden müssen

Bevor du ersetzt, musst du wissen, **was** ersetzt werden soll.

### 3.1 Commit-Nachrichten durchsuchen

```powershell
cd "Pfad\zu\deinem\Projekt"

# Nach Schulname suchen
git log --all --oneline --grep="GSH"
git log --all --oneline --grep="Horst"
git log --all --oneline --grep="gelsenkirchen"

# Nach E-Mail-Adressen suchen
git log --all --format="%H %ae %ce" | findstr "@gesamtschule-horst.de"
```

### 3.2 Datei-Inhalte durchsuchen

```powershell
# Nach Domain suchen (alle Branches, alle Commits)
git grep -i "gesamtschule-horst" $(git rev-list --all)

# Nach alten Dateinamen suchen
git log --all --name-only --pretty=format: | Sort-Object -Unique | findstr "GSH"
```

### 3.3 Suchbegriffe dokumentieren

Erstelle eine Liste mit allen Begriffen, die ersetzt werden sollen:

| Begriff (alt) | Ersetzen durch (neutral) | Wo vorkommend |
|---------------|--------------------------|---------------|
| `GSH` | `Schul` oder `Terminplan` | Dateinamen, Commits |
| `gesamtschule-horst.de` | `example-school.de` | Code, Config |
| `gsh-gelsenkirchen.de` | `example-school.de` | Code, Config |
| `Max Mustermann` | `Admin` | Commits, Code |
| `max.mustermann@schule.de` | `admin@example-school.de` | Commits, Git-Config |

> **Tipp:** Notiere dir die Begriffe in einer Datei `SANITIZE_WORDLIST.md` im Projekt.

---

## 4. Tool installieren

Das Tool `git-filter-repo` ist das moderne Standard-Werkzeug für History-Rewrites.

```powershell
# Prüfen, ob Python installiert ist
python --version

# git-filter-repo installieren
pip install git-filter-repo

# Installation prüfen
git filter-repo --version
```

> **Hinweis:** Falls `pip` nicht funktioniert, lade `git-filter-repo` als einzelne
> Python-Datei herunter: https://github.com/newren/git-filter-repo/releases

---

## 5. History bereinigen

### Schritt 5.1: Ins Projektverzeichnis wechseln

```powershell
cd "Y:\Pfad\zu\deinem\Projekt"
```

### Schritt 5.2: Commit-Nachrichten ersetzen

Ersetze `GSH` durch einen neutralen Begriff in **allen** Commit-Messages:

```powershell
git filter-repo --force --replace-text <<EOF
GSH==>Schul
EOF
```

Oder für mehrere Begriffe gleichzeitig:

```powershell
git filter-repo --force --replace-text <<EOF
GSH==>Schul
gesamtschule-horst.de==>example-school.de
gsh-gelsenkirchen.de==>example-school.de
EOF
```

### Schritt 5.3: Datei-Inhalte ersetzen

Falls schulinterne URLs auch im Code stehen (z. B. in alten Versionen):

```powershell
git filter-repo --force --replace-text <<EOF
literal:gesamtschule-horst.de==>example-school.de
literal:gsh-gelsenkirchen.de==>example-school.de
EOF
```

### Schritt 5.4: Datei-Pfade umbenennen

Falls Dateien mit alten Namen existieren (z. B. `GSH_Terminplan_...`):

```powershell
git filter-repo --path-rename GSH_Terminplan_:Terminplan_
```

### Schritt 5.5: Autoren/E-Mail-Adressen bereinigen

Falls Commit-E-Mail-Adressen schulintern sind:

```powershell
git filter-repo --email-callback '
    return email.replace(b"@gesamtschule-horst.de", b"@example-school.de")
'
```

---

## 6. Ergebnis prüfen

Nach dem Rewrite **immer** prüfen!

### 6.1 Commit-Nachrichten

```powershell
# Sollte 0 Ergebnisse liefern:
git log --all --oneline --grep="GSH"
git log --all --oneline --grep="gesamtschule-horst"
```

### 6.2 Datei-Inhalte

```powershell
# Sollte 0 Ergebnisse liefern:
git grep -i "gesamtschule-horst" $(git rev-list --all)
git grep -i "gsh-gelsenkirchen" $(git rev-list --all)
```

### 6.3 Datei-Pfade

```powershell
git log --all --name-only --pretty=format: | Sort-Object -Unique | findstr "GSH"
```

> **Wenn alle drei Prüfungen 0 Ergebnisse liefern:** ✅ Bereinigung erfolgreich.

---

## 7. Zurück zu GitHub pushen

### Schritt 7.1: Alle Branches aktualisieren

Nach einem Rewrite haben alle Commits neue Hashes. Du musst **forciert** pushen:

```powershell
# Alle lokalen Branches pushen
git push --all --force origin

# Alle Tags pushen (falls vorhanden)
git push --tags --force origin
```

> **⚠️ Warnung:** `--force` überschreibt die History auf GitHub. Alle anderen
> Mitarbeitenden müssen das Repository neu klonen!

### Schritt 7.2: Mitwirkende informieren

Nach dem Force-Push müssen alle, die am Projekt arbeiten, folgendes tun:

```bash
# Altes Repository löschen und neu klonen
cd ..
rm -rf "alter-ordner-name"
git clone https://github.com/juwagn/gsh-terminplan.git
```

---

## 8. Checkliste für regelmäßige Prüfung

> **Empfohlene Häufigkeit:** Vor jedem Release, vor Public-Release, nach neuen Features.

| Nr. | Prüfung | Befehl | Soll-Ergebnis |
|-----|---------|--------|---------------|
| 1 | Commit-Nachrichten | `git log --all --oneline \| findstr -i "GSH\|Horst\|gelsenkirchen"` | Keine Treffer |
| 2 | Aktueller Code | `git grep -i "gesamtschule-horst" HEAD` | Keine Treffer |
| 3 | Alle Commits | `git grep -i "gsh-gelsenkirchen" $(git rev-list --all)` | Keine Treffer |
| 4 | Dateinamen | `git log --all --name-only \| findstr -i "GSH"` | Keine Treffer |
| 5 | Git-Config | `git log --all --format="%ae %ce" \| findstr -i "@gesamtschule"` | Keine Treffer |

### Automatisierung (Optional)

Speichere als `scripts/check_sanitization.ps1`:

```powershell
# check_sanitization.ps1
$terms = @("GSH", "gesamtschule-horst", "gsh-gelsenkirchen", "Gelsenkirchen")
$found = $false

foreach ($term in $terms) {
    $commits = git log --all --oneline --grep="$term" 2>$null
    if ($commits) {
        Write-Host "WARNUNG: '$term' in Commit-Nachrichten gefunden!" -ForegroundColor Red
        $found = $true
    }
    $files = git grep -i "$term" HEAD 2>$null
    if ($files) {
        Write-Host "WARNUNG: '$term' in aktuellem Code gefunden!" -ForegroundColor Red
        $found = $true
    }
}

if (-not $found) {
    Write-Host "✅ Keine schulspezifischen Begriffe gefunden." -ForegroundColor Green
}
```

Ausführung:
```powershell
.\scripts\check_sanitization.ps1
```

---

## Schnellreferenz: Einzeiler

| Aufgabe | Befehl |
|---------|--------|
| Backup erstellen | `robocopy Projekt Ordner BACKUP /E /XD node_modules .claude` |
| Installieren | `pip install git-filter-repo` |
| Commits bereinigen | `git filter-repo --force --replace-text < replacements.txt` |
| Dateien bereinigen | `git filter-repo --force --replace-text < replacements.txt` |
| Prüfen (Commits) | `git log --all --oneline \| findstr -i "GSH"` |
| Prüfen (Code) | `git grep -i "GSH" HEAD` |
| Push nach Rewrite | `git push --all --force origin` |

---

## Support

Falls etwas schiefgeht:

1. **Abbruch vor dem Push?** Kein Problem — einfach `git clone` vom Backup machen.
2. **Falsch ersetzt?** `git filter-repo` erzeugt ein Backup unter `.git/filter-repo/`
3. **Unsicher?** Frage in der Schulleitung oder einem IT-Kollegen nach.

> **Goldene Regel:** Immer Backup → Dann testen → Dann pushen.
