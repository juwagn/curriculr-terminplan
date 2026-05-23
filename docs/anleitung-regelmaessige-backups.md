# Anleitung: Regelmäßige Backups des Projekts

> Diese Anleitung zeigt dir, wie du dein Terminplan-Projekt regelmäßig und
> zuverlässig sicherst — sowohl manuell als auch automatisiert.

---

## Übersicht

| Methode | Aufwand | Automatisierbar | Für wen |
|---------|---------|-----------------|---------|
| **A. ZIP-Archiv** (manuell) | Sehr gering | Nein | Gelegentliche Sicherung |
| **B. Robocopy-Spiegel** (manuell) | Gering | Nein | Schneller Klon auf USB/Netzlaufwerk |
| **C. Git-Bundle** (manuell) | Gering | Ja (Script) | Nur Git-History, sehr kompakt |
| **D. Windows-Aufgabenplanung** | Einmalig einrichten | Ja | Automatisch täglich/wöchentlich |
| **E. Cloud-Sync** (OneDrive/Dropbox) | Einmalig einrichten | Ja | Schutz gegen Festplatten-Crash |

---

## A. ZIP-Archiv (schnell & einfach)

**Wann:** Vor großen Änderungen, vor dem History-Rewrite, vor Releases.

`powershell
$projekt = "Y:\Schule\Projekte Schul-IT\Wordpress Plugin Terminplaner"
$datum   = Get-Date -Format "yyyy-MM-dd_HH-mm"
$ziel    = "Y:\Schule\Projekte Schul-IT\BACKUP_Terminplan_$datum.zip"

Compress-Archive -Path "$projekt\*" -DestinationPath $ziel -Force
Write-Host "Backup erstellt: $ziel" -ForegroundColor Green
`

### Mit Ausschluss von Temp-Dateien

`powershell
$projekt = "Y:\Schule\Projekte Schul-IT\Wordpress Plugin Terminplaner"
$datum   = Get-Date -Format "yyyy-MM-dd"
$ziel    = "Y:\Schule\Projekte Schul-IT\BACKUP_Terminplan_$datum"

robocopy "$projekt" "$ziel" /E 
    /XD "node_modules" ".claude" "__pycache__" ".venv" "Backup" 
    /XF "*.zip" "*.bak" "*.tmp" | Out-Null

Write-Host "Ordner-Backup erstellt: $ziel" -ForegroundColor Green
`

---

## B. Git-Bare-Repository (komplett & kompakt)

`powershell
$projekt = "Y:\Schule\Projekte Schul-IT\Wordpress Plugin Terminplaner"
$datum   = Get-Date -Format "yyyy-MM-dd"
$ziel    = "Y:\Schule\Projekte Schul-IT\BACKUP_Terminplan_Git_$datum.git"

git clone --mirror "$projekt" "$ziel"
Write-Host "Git-Backup erstellt" -ForegroundColor Green
`

**Wiederherstellen:**
`powershell
git clone "BACKUP_Terminplan_Git_2026-05-09.git" "Wiederhergestellt"
`

---

## C. Git-Bundle (portabel & einzelne Datei)

`powershell
cd "Y:\Schule\Projekte Schul-IT\Wordpress Plugin Terminplaner"
git bundle create "terminplan_$(Get-Date -Format 'yyyy-MM-dd').bundle" --all
`

**Wiederherstellen:**
`powershell
git clone "terminplan_2026-05-09.bundle" "Wiederhergestellt"
`

---

## D. Automatisiert: Windows-Aufgabenplanung

### Schritt 1: PowerShell-Script erstellen

Speichere als scripts/backup.ps1:

`powershell
$projekt = "Y:\Schule\Projekte Schul-IT\Wordpress Plugin Terminplaner"
$backupRoot = "Y:\Schule\Projekte Schul-IT\Backups"
$datum = Get-Date -Format "yyyy-MM-dd"
$ziel = "$backupRoot\Terminplan_$datum"

# Alte Backups löschen (> 30 Tage)
Get-ChildItem -Path $backupRoot -Directory | Where-Object {
    $_.CreationTime -lt (Get-Date).AddDays(-30)
} | Remove-Item -Recurse -Force

# Neues Backup
robocopy "$projekt" "$ziel" /E 
    /XD "node_modules" ".claude" "__pycache__" ".venv" "Backup" 
    /XF "*.zip" "*.bak" "*.tmp" | Out-Null

"Backup erstellt: $ziel" | Out-File -Append "$backupRoot\backup.log"
`

### Schritt 2: Aufgabe einrichten

1. Win + R -> 	askschd.msc -> Enter
2. Rechtsklick **Aufgabenplanungsbibliothek** -> **Einfache Aufgabe erstellen**
3. Name: Terminplan Backup
4. Trigger: **Wöchentlich** -> Freitag -> 17:00 Uhr
5. Aktion: **Programm starten**
6. Programm: powershell.exe
7. Argumente: -ExecutionPolicy Bypass -File "Y:\Schule\Projekte Schul-IT\Wordpress Plugin Terminplaner\scripts\backup.ps1"
8. Fertig stellen

---

## E. Cloud-Backup (OneDrive)

`powershell
robocopy "Y:\Schule\Projekte Schul-IT\Wordpress Plugin Terminplaner" 
         "$env:OneDrive\Terminplan_Backup" /E /MIR
`

OneDrive synchronisiert automatisch.

---

## F. Kombinierte Strategie (empfohlen)

| Frequenz | Methode | Wo |
|----------|---------|-----|
| **Jeder Commit** | GitHub | Online |
| **Täglich** | OneDrive | Online |
| **Wöchentlich** | Windows-Aufgabe | USB / Netzlaufwerk |
| **Vor großen Änderungen** | ZIP manuell | USB-Stick |

---

## Schnellbefehle

**Backup jetzt:**
`powershell
$projekt = "Y:\Schule\Projekte Schul-IT\Wordpress Plugin Terminplaner"
$ziel = "Y:\Schule\Projekte Schul-IT\BACKUP_Terminplan_$(Get-Date -Format 'yyyy-MM-dd_HH-mm')"
robocopy "$projekt" "$ziel" /E /XD node_modules .claude __pycache__ .venv Backup | Out-Null
`

**Git-Bundle:**
`powershell
cd "Y:\Schule\Projekte Schul-IT\Wordpress Plugin Terminplaner"
git bundle create "terminplan_$(Get-Date -Format 'yyyy-MM-dd').bundle" --all
`

**Alte Backups aufräumen (> 30 Tage):**
`powershell
Get-ChildItem -Path "Y:\Schule\Projekte Schul-IT\Backups" -Directory | Where-Object {
    $_.CreationTime -lt (Get-Date).AddDays(-30)
} | Remove-Item -Recurse -Force
`

---

## Checkliste

- [ ] Backup-Ordner enthält Dateien
- [ ] .git-Ordner ist enthalten
- [ ] orlagen/, plugin/, konverter/ sind enthalten
- [ ] Keine Fehlermeldung
- [ ] Wiederherstellung wurde getestet

---

> **Goldene Regel:** Ein Backup, das nicht getestet wurde, ist kein Backup.
