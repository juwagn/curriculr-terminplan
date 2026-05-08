# Nachschreiber-System – Gestaffelte Featurespezifikation
## Für eine weiterführende Schule in NRW mit IServ-Integration

**Version:** 1.0  
**Datum:** 2026-05-07  
**Status:** Konzeption / Architekturvorgabe  

---

## 1. Vision & Leitprinzipien

> **Ziel:** Ein modernes, schlankes und rechtssicheres Nachschreiber-Management, das den kompletten Workflow von der Krankmeldung bis zur Aufsichts-Dokumentation automatisiert und dabei alle Beteiligten entlastet.

### Kernprinzipien
1. **Integration statt Insellösung:** IServ ist die Single Source of Truth für Benutzer, Gruppen, Räume, Kalender.
2. **Workflow-First:** Jeder Prozess hat einen definierten Status und klare Verantwortlichkeiten.
3. **Mobile-First für Aufsichten:** Die Aufsichts-UI muss auf einem Tablet im laufenden Betrieb funktionieren.
4. **Regeln konfigurierbar, nicht hartkodiert:** Schulrechtliche und interne Regeln müssen ohne Code-Änderung anpassbar sein.
5. **Transparent & auditfähig:** Jede Entscheidung und jede Änderung ist nachvollziehbar protokolliert.

---

## 2. Rollen & Berechtigungen (RBAC)

| Rolle | Beschreibung | Kernberechtigungen |
|-------|--------------|-------------------|
| **System-Admin** | IT-Admin, IServ-Admin | API-Keys, Integrationen, technische Konfiguration, Benutzer-Sync |
| **Schulleitung (SL)** | Schulleitung, Stellvertretung | Regelwerk definieren, Sonderfälle genehmigen, globale Reports, Löschung von Daten |
| **Orga-Team** | Oberstufenkoordination, Klausurorga | Slots verwalten, Schüler zuweisen, Termine bestätigen/ändern, Konflikte lösen |
| **Fachlehrkraft** | Lehrer:in mit Klausur | Fehlende Schüler melden, Nachschreibklausur hochladen, eigene Nachschreiber einsehen |
| **Aufsicht** | Aufsichtführende Lehrkraft | Anwesenheit erfassen, Bemerkungen hinzufügen, nur eigene Termine sehen |
| **Sekretariat** | Verwaltung | Atteste zuordnen, Fehlzeiten-Status pflegen, Bescheinigungen verwalten |
| **Schüler** | Betroffene Schüler:in | Eigene Termine einsehen, Status verfolgen, (optional) Begründung einreichen |
| **Eltern** | Optional | Benachrichtigungen erhalten, Termine einsehen (nur eigenes Kind) |

### Berechtigungsmatrix (Auszug)

| Aktion | Admin | SL | Orga | Fachlehrkraft | Aufsicht | Sekretariat | Schüler |
|--------|:-----:|:--:|:----:|:-------------:|:--------:|:-----------:|:-------:|
| Regelwerk konfigurieren | ✓ | ✓ | – | – | – | – | – |
| Slots anlegen/bearbeiten | ✓ | ✓ | ✓ | – | – | – | – |
| Prüfungsversäumnis melden | – | – | ✓ | ✓ | – | – | (✓)* |
| Schüler zu Slot zuweisen | – | ✓ | ✓ | – | – | – | – |
| Klausur hochladen | – | – | – | ✓ | – | – | – |
| Anwesenheit erfassen | – | – | – | – | ✓ | – | – |
| Attest zuordnen | – | – | – | – | – | ✓ | – |
| Eigene Termine einsehen | – | – | – | – | – | – | ✓ |
| Alle Termine einsehen | ✓ | ✓ | ✓ | (eigene) | (eigene) | – | (eigene) |
| Audit-Log einsehen | ✓ | ✓ | – | – | – | – | – |
| Export/Reports | ✓ | ✓ | ✓ | (eigene) | – | – | – |

\* Optional: Schüler können selbst ein Versäumnis melden (z. B. mit Attest-Upload), aber es wird nicht automatisch genehmigt.

---

## 3. Datenmodell (vereinfachte Entitäten)

### 3.1 Kerndomäne: Prüfungsversäumnis & Nachschreibtermin

```
┌─────────────────────┐       ┌─────────────────────┐       ┌─────────────────────┐
│  Prüfungsversäumnis │       │   Nachschreibslot   │       │   Nachschreibtermin │
├─────────────────────┤       ├─────────────────────┤       ├─────────────────────┤
│ id (UUID)           │       │ id (UUID)           │       │ id (UUID)           │
│ schueler_id (IServ) │──────>│ zielgruppe (JG/KG)  │<──────│ slot_id             │
│ klausur_id          │       │ datum (DATE)        │       │ slot_id             │
│ kurs_id             │       │ startzeit (TIME)    │       │ versäumnis_ids[]    │
│ fach_id             │       │ dauer_minuten (INT) │       │ raum_id (IServ)     │
│ urspr_datum         │       │ max_teilnehmer (INT)│       │ aufsicht_ids[]      │
│ gemeldet_von        │       │ raum_id (IServ)     │       │ klausur_datei_id    │
│ gemeldet_am         │       │ aufsicht_ids[]      │       │ status              │
│ fehlgrund           │       │ wiederholung (ENUM) │       │ erstellt_am         │
│ attest_status       │       │ aktiv (BOOL)        │       │ geaendert_am        │
│ status              │       │ schuljahr           │       │ erstellt_von        │
│ prioritaet          │       │ notiz               │       │                     │
│ notiz               │       └─────────────────────┘       └─────────────────────┘
└─────────────────────┘
```

### 3.2 Unterstützende Entitäten

```
┌─────────────────────┐       ┌─────────────────────┐       ┌─────────────────────┐
│    Klausur/Prüfung  │       │  Regelwerk (global) │       │   Audit-Log         │
├─────────────────────┤       ├─────────────────────┤       ├─────────────────────┤
│ id (UUID)           │       │ id (INT, singleton) │       │ id (BIGINT)         │
│ bezeichnung         │       │ max_klausuren_woche │       │ zeitstempel         │
│ fach_id             │       │ max_nachschreiben_woche│    │ akteur_id           │
│ kurs_id             │       │ nachmittag_nur_wenn │       │ akteur_rolle        │
│ datum               │       │   keine_vormittags  │       │ entity_type         │
│ startzeit           │       │ vormittag_uhrzeit_bis│      │ entity_id           │
│ dauer_minuten       │       │ nachmittag_ab_uhrzeit│      │ aktion              │
│ erstellt_von        │       │ vorauslauf_tage (INT)│      │ alte_werte (JSON)   │
│ jahrgang            │       │ attest_frist_tage (INT)│    │ neue_werte (JSON)   │
│ klausur_datei_id    │       │ automatisch_vorschlagen│    │ grund (TEXT)        │
└─────────────────────┘       │ schuljahr_start (DATE)│     └─────────────────────┘
                              └─────────────────────┘
```

### 3.3 Status-Maschine: Prüfungsversäumnis

```
[gemeldet]
    │
    ▼
[attest_prüfung] ──Attest fehlt──> [attest_nachgefordert]
    │                                    │
    │Attest vorhanden                    │Attest eingereicht
    ▼                                    ▼
[entschuldigt] <──Attest abgelehnt── [attest_vorhanden]
    │
    ▼
[zu_planen] ──Automatischer Vorschlag──> [slot_vorgeschlagen]
    │                                          │
    │Manuelle Zuweisung                        │Orga bestätigt
    ▼                                          ▼
[termin_zugewiesen] <────────────────────── [slot_bestätigt]
    │
    ▼
[nachgeschrieben] ──Anwesend──> [erledigt]
    │
    └──Nicht erschienen──> [nicht_erschienen] ──Attest?──> [nicht_entschuldigt]
                                                            │
                                                            └──> Konsequenz erforderlich
```

### 3.4 Status-Maschine: Nachschreibtermin

```
[geplant] → [veröffentlicht] → [läuft] → [abgeschlossen] → [archiviert]
                │
                └──> [abgesagt] (mit Begründung)
```

---

## 4. Features nach Phasen (gestaffelt)

---

### PHASE 0: Fundament & Integration (MVP-Voraussetzung)
**Ziel:** Technische Basis, IServ-Anbindung, Datenbank-Schema

| # | Feature | Beschreibung | Akzeptanzkriterien |
|---|---------|--------------|-------------------|
| 0.1 | **IServ-SSO & Auth** | Login über IServ-OAuth2/OpenID Connect. Gruppen-/Rollen-Mapping aus IServ-Gruppen. | Benutzer kann sich mit IServ-Zugang einloggen. Rollen werden korrekt aus IServ-Gruppen abgeleitet. |
| 0.2 | **Benutzer-/Gruppen-Sync** | Nächtlicher oder event-getriebener Sync von IServ-Benutzern, Klassengruppen, Jahrgangsgruppen. | Neue Schüler/Lehrer erscheinen innerhalb von 24h im System. Inaktive Benutzer werden als `inaktiv` markiert. |
| 0.3 | **Raum-Sync** | Übernahme der IServ-Raumverwaltung (oder eigene Raumliste als Fallback). | Räume sind im System verfügbar und können Slots zugewiesen werden. |
| 0.4 | **Datenbank-Schema** | Migrationen für alle Entitäten (siehe 3.1–3.2). SQLite für MVP, PostgreSQL für Produktion. | `doctrine:migrations:migrate` läuft fehlerfrei. Schema ist vollständig. |
| 0.5 | **API-Grundgerüst** | Symfony-Backend mit API-Platform oder plain Symfony-Controller + JSON-API. Basis-CRUD. | Alle Entitäten sind über REST erreichbar. Swagger/OpenAPI-Dokumentation vorhanden. |
| 0.6 | **Audit-Log-System** | Zentraler Event-Listener, der alle relevanten Änderungen in `audit_log` schreibt. | Jede Status-Änderung, Zuweisung und Konfigurationsänderung ist protokolliert und einsehbar. |

---

### PHASE 1: MVP – Kernworkflow (Minimal Viable Product)
**Ziel:** Ein vollständiger Durchstich vom Versäumnis bis zur Nachschrift. Fokus auf Orga-Team und Aufsicht.
**Zeithorizont:** 4–6 Wochen Entwicklung

#### 1.1 Prüfungsversäumnis erfassen
| # | Feature | Beschreibung | Akzeptanzkriterien |
|---|---------|--------------|-------------------|
| 1.1.1 | **Fehlende melden (Fachlehrkraft)** | In einer Liste der eigenen Kurse/Klausuren kann die Lehrkraft Schüler als „fehlend bei Klausur“ markieren. Optional: Grund (Krankheit, unentschuldigt, sonstiges). | Lehrkraft sieht nur eigene Kurse. Markierung ist mit einem Klick möglich. |
| 1.1.2 | **Import aus Klausurplan** | Optional: Automatischer Import aus dem bestehenden Klausurplan-System (Excel/iCal/CSV) oder manuelles Anlegen der Prüfung. | Prüfungsdaten (Fach, Datum, Kurs) stehen zur Verfügung. |
| 1.1.3 | **Sekretariat: Attest zuordnen** | Sekretariat sieht Liste der offenen Versäumnisse und kann Attest-Status setzen: „Attest vorhanden“, „Attest fehlt“, „Attest unzureichend“. | Statusänderung triggert Audit-Log. Attest-Status ist im Workflow sichtbar. |

#### 1.2 Nachschreibslots verwalten
| # | Feature | Beschreibung | Akzeptanzkriterien |
|---|---------|--------------|-------------------|
| 1.2.1 | **Slots anlegen** | Orga-Team legt wiederkehrende Slots an: „Dienstag 8:00–10:00, Jahrgang EF, Raum A123, Aufsicht Mustermann“. | Slots sind im System gespeichert und können aktiviert/deaktiviert werden. |
| 1.2.2 | **Slot-Kapazität** | Jedem Slot kann eine maximale Teilnehmerzahl zugewiesen werden. | Bei Erreichen der Maximalzahl ist der Slot nicht mehr verfügbar (optisch ausgegraut). |
| 1.2.3 | **Slot-Übersicht (Kalenderansicht)** | Wochen-/Monatskalender mit allen Slots, farblich codiert nach Kapazität (grün = frei, gelb = halbvoll, rot = voll). | Orga-Team erkennt auf einen Blick verfügbare Kapazitäten. |

#### 1.3 Schüler zuweisen
| # | Feature | Beschreibung | Akzeptanzkriterien |
|---|---------|--------------|-------------------|
| 1.3.1 | **Manuelle Zuweisung** | Orga-Team wählt einen offenen Fall aus und weist ihm einen Slot zu. | Schüler erhält automatisch den Termin zugewiesen. Status wechselt zu „termin_zugewiesen“. |
| 1.3.2 | **Konfliktprüfung (Basis)** | Beim Zuweisen wird geprüft, ob der Schüler bereits eine andere Prüfung im selben Zeitfenster hat. | Bei Konflikt wird ein Warn-Dialog angezeigt. Zuweisung ist trotzdem möglich (mit Begründung). |
| 1.3.3 | **Bulk-Zuweisung** | Mehrere Schüler (z. B. aus demselben Kurs) können gleichzeitig einem Slot zugewiesen werden. | Checkbox-Liste → „Ausgewählte zuweisen an…“ funktioniert in einem Schritt. |

#### 1.4 Aufsichts-Frontend (Tablet-optimiert)
| # | Feature | Beschreibung | Akzeptanzkriterien |
|---|---------|--------------|-------------------|
| 1.4.1 | **Tagesübersicht Aufsicht** | Aufsicht loggt sich ein und sieht nur ihre heutigen Termine. Große Kacheln: Zeit, Raum, Fach, Anzahl Schüler. | Keine überflüssigen Informationen. UI ist auf Touch optimiert. |
| 1.4.2 | **Check-In-Liste** | Pro Termin: Liste aller zugewiesenen Schüler mit großen Checkboxen „Anwesend“ / „Abwesend“. | Ein Klick = Status gespeichert. Kein Neuladen der Seite nötig (SPA/HTMX). |
| 1.4.3 | **Bemerkungen** | Feld für Freitext-Bemerkungen pro Schüler (z. B. „zu spät, 15 Min. nachgelassen“, „Attest abgegeben“). | Bemerkung wird gespeichert und ist im Audit-Log sichtbar. |

#### 1.5 Schüler-Übersicht
| # | Feature | Beschreibung | Akzeptanzkriterien |
|---|---------|--------------|-------------------|
| 1.5.1 | **Meine Nachschreibtermine** | Schüler sieht eine chronologische Liste aller seiner Nachschreibtermine mit Status, Datum, Raum, Fach. | Nur eigene Daten sind sichtbar. Termine sind farblich nach Status codiert. |
| 1.5.2 | **IServ-Startseite & Kalender** | Termine werden über IServ-API in den persönlichen Kalender und auf die Startseite gespiegelt (analog bestehendem Modul). | Termin erscheint in IServ-Kalender mit korrektem Zeitfenster. |

#### 1.6 Benachrichtigungen
| # | Feature | Beschreibung | Akzeptanzkriterien |
|---|---------|--------------|-------------------|
| 1.6.1 | **Termin-Zuweisung (E-Mail)** | Bei Zuweisung zu einem Slot erhält der Schüler eine E-Mail über IServ-Mail mit Termindetails. | E-Mail enthält Datum, Uhrzeit, Raum, Fach und Hinweise. |
| 1.6.2 | **Erinnerung (E-Mail)** | 24h vor dem Termin automatische Erinnerungs-E-Mail. | Erinnerung wird automatisch versendet, sofern Termin nicht abgesagt. |

---

### PHASE 2: Intelligenz & Automatisierung
**Ziel:** System entlastet das Orga-Team durch intelligente Vorschläge und automatisierte Regeln.
**Zeithorizont:** 3–4 Wochen nach MVP

#### 2.1 Regelengine
| # | Feature | Beschreibung | Akzeptanzkriterien |
|---|---------|--------------|-------------------|
| 2.1.1 | **Konfigurierbares Regelwerk** | SL/Orga kann Regeln im Admin-Panel definieren: max. Klausuren/Woche, max. Nachschreiben/Woche, Vormittag bevorzugt, Nachmittag nur wenn nötig, Mindest-Vorlaufzeit (z. B. 48h). | Regeln sind in der Datenbank gespeichert. Änderungen sind sofort wirksam. |
| 2.1.2 | **Attest-Fristen-Regel** | Automatische Eskalation, wenn Attest nach X Tagen nicht vorliegt. | Schüler/Eltern erhalten Erinnerung. Orga-Team sieht „Attest fehlt“-Liste. |
| 2.1.3 | **Belastungsgrenzen** | System prüft, wie viele Klausuren (inkl. Nachschreiben) ein Schüler in einer Woche hat. | Bei Überschreitung wird der Slot rot markiert und eine Warnung angezeigt. |

#### 2.2 Automatische Terminvorschläge
| # | Feature | Beschreibung | Akzeptanzkriterien |
|---|---------|--------------|-------------------|
| 2.2.1 | **Intelligente Slot-Empfehlung** | Für einen offenen Fall schlägt das System 1–3 geeignete Slots vor, sortiert nach: Passendem Jahrgang, freier Kapazität, geringer Belastung, kein Konflikt. | Vorschläge werden mit Begründung angezeigt („Beste Wahl: Keine Konflikte, nur 2. Klausur diese Woche“). |
| 2.2.2 | **Automatische Zuweisung (opt-in)** | Orga-Team kann pro Versäumnis oder global die automatische Zuweisung aktivieren. System weist den besten Slot zu und informiert den Schüler. | Automatische Zuweisung erfolgt nur bei Konfidenz > 90%. Bei Konflikten wird Orga-Team informiert. |
| 2.2.3 | **Optimierungs-Algorithmus (Basis)** | Graph-basierte Konfliktprüfung: Schüler × Termine als bipartiter Graph. Ziel: Minimierung von Kollisionen und gleichmäßige Verteilung. | Algorithmus läuft in < 2 Sekunden für ≤ 200 offene Fälle. |

#### 2.3 Erweitertes Konfliktmanagement
| # | Feature | Beschreibung | Akzeptanzkriterien |
|---|---------|--------------|-------------------|
| 2.3.1 | **Konflikt- Dashboard** | Zentrale Ansicht aller aktuellen Konflikte: Doppelbuchungen, Aufsichts-Überschneidungen, Raum-Kollisionen, Belastungsgrenzen. | Konflikte sind nach Schweregrad sortiert und mit Lösungsvorschlägen verknüpft. |
| 2.3.2 | **Aufsichts-Lastverteilung** | System zeigt an, wie viele Aufsichtsstunden jede Lehrkraft bereits innehat. Warnung bei Überlastung. | Aufsichtszuweisung schlägt automatisch unterbelastete Lehrkräfte vor. |
| 2.3.3 | **Raum-Kollisionen** | Warnung, wenn ein Raum doppelt belegt ist. Alternative Räume vorschlagen. | Kollision wird in Echtzeit erkannt (beim Slot-Anlegen/Zuweisen). |

#### 2.4 Klausur-Dokumenten-Management
| # | Feature | Beschreibung | Akzeptanzkriterien |
|---|---------|--------------|-------------------|
| 2.4.1 | **Klausur-Upload bei Prüfungsanlage** | Fachlehrkraft lädt die Originalklausur hoch und markiert „Für Nachschreiber verwenden“. | Nachschreibtermin erbt automatisch die Klausurdatei. |
| 2.4.2 | **Versionierung** | Falls die Klausur geändert wird, wird eine neue Version angelegt. Nachschreiber bekommen immer die zum Zeitpunkt der Zuweisung aktuelle Version. | Versionen sind im Audit-Log nachvollziehbar. |
| 2.4.3 | **Druckvorbereitung** | Orga-Team kann für einen Slot alle Klausuren als ZIP oder PDF-Sammlung exportieren (mit Deckblatt: Name, Kurs, Datum). | Export enthält korrekt zugeordnete Klausuren für alle Teilnehmer. |

---

### PHASE 3: Polishing, Erweiterte UX & Integrationen
**Ziel:** System fühlt sich „fertig“ an, ist für alle Rollen ein echter Gewinn und vollständig in die Schul-IT integriert.
**Zeithorizont:** 4–6 Wochen nach Phase 2

#### 3.1 Erweiterte Schüler-/Eltern-Features
| # | Feature | Beschreibung | Akzeptanzkriterien |
|---|---------|--------------|-------------------|
| 3.1.1 | **Timeline-Ansicht** | Schüler sieht seine Nachschreibtermine als vertikale Timeline (ähnlich GitHub Contributions oder Medikamenten-App). | Timeline zeigt vergangene und zukünftige Termine, farblich nach Status. |
| 3.1.2 | **Selbstmeldung (opt-in)** | Schüler kann ein Versäumnis selbst melden und ein Attest hochladen. Sekretariat muss freigeben. | Meldung erscheint im Sekretariat-Dashboard zur Freigabe. |
| 3.1.3 | **Eltern-Portal** | Eltern erhalten Lesezugriff auf Termine ihres Kindes (wenn in IServ als Eltern verknüpft). | Eltern-Account sieht nur Daten des eigenen Kindes. |
| 3.1.4 | **Push-Benachrichtigungen** | Optional: Browser-Push oder IServ-Push bei neuem Termin/Änderung. | Benachrichtigung wird innerhalb von 5 Minuten nach Änderung zugestellt. |

#### 3.2 Erweiterte Berichte & Exports
| # | Feature | Beschreibung | Akzeptanzkriterien |
|---|---------|--------------|-------------------|
| 3.2.1 | **Prüfungsakten-Export** | PDF-Liste aller Nachschreibtermine eines Schülers für die Prüfungsakte (z. B. Oberstufe). | PDF enthält Fach, Datum, Grund, Attest-Status, Ergebnis. |
| 3.2.2 | **Konferenz-Listen** | Export: „Alle unentschuldigten Nicht-Teilnahmen im Schuljahr“ für Klassenkonferenzen. | Liste ist nach Jahrgang/Klasse filterbar. |
| 3.2.3 | **Statistiken** | Dashboard für SL: Anzahl Nachschreiben pro Monat, häufigste Fächer, Aufsichts-Lastverteilung, Attest-Quote. | Diagramme sind als PNG/PDF exportierbar. |

#### 3.3 IServ-Vertiefung & API
| # | Feature | Beschreibung | Akzeptanzkriterien |
|---|---------|--------------|-------------------|
| 3.3.1 | **IServ-Aufgaben-Integration** | Nachschreibtermine erscheinen als spezieller Aufgabentyp in IServ (optisch differenziert von normalen Aufgaben). | Schüler erkennt Nachschreiben auf den ersten Blick. |
| 3.3.2 | **Zwei-Wege-Kalender-Sync** | Änderungen im Nachschreiber-System aktualisieren den IServ-Termin. Änderungen in IServ (z. B. Löschung) werden zurückgespiegelt. | Konsistenz ist innerhalb von 5 Minuten gegeben. |
| 3.3.3 | **IServ-Gruppen-Merkmal** | Automatische Vergabe des Gruppenmerkmals „Nachschreiber“ über IServ-API (analog bestehendem Modul). | Gruppenmitgliedschaft wird bei Termin-Zuweisung gesetzt. |

#### 3.4 Mobile App / PWA
| # | Feature | Beschreibung | Akzeptanzkriterien |
|---|---------|--------------|-------------------|
| 3.4.1 | **Progressive Web App** | System ist als PWA installierbar (Manifest, Service Worker). | Auf Android/iOS erscheint App auf Homescreen, Offline-Cache für heutige Termine. |
| 3.4.2 | **Offline-Modus Aufsicht** | Aufsicht kann Anwesenheit auch ohne Internet erfassen; Sync erfolgt bei Wiederverbindung. | Daten werden lokal im Browser gespeichert (IndexedDB) und synchronisiert. |

#### 3.5 Integration Schul-IT-Plattform (dein Terminplaner)
| # | Feature | Beschreibung | Akzeptanzkriterien |
|---|---------|--------------|-------------------|
| 3.5.1 | **Shared Entity: Prüfung** | Das Nachschreiber-System und der Terminplaner teilen sich die Entität „Prüfung/Klausur“ (gemeinsame API oder Datenbank). | Klausur wird einmal angelegt, ist in beiden Systemen sichtbar. |
| 3.5.2 | **Terminplaner-Widget** | Auf der Startseite des Terminplaners erscheint ein Widget „Offene Nachschreibfälle“ mit Quick-Link. | Widget zeigt aktuelle Zahlen und springt ins Nachschreiber-System. |
| 3.5.3 | **Einheitliche UI-Komponenten** | Beide Systeme nutzen dasselbe Design-System (Tailwind/shadcn/ui, Farben, Typografie). | Nutzer empfindet beide Systeme als zusammengehörig. |

---

## 5. UX-Design-Prinzipien pro Rolle

### 5.1 Aufsicht: „Zero-Friction Check-In"
- **Gerät:** Tablet im Querformat
- **Schriftgröße:** Mindestens 16px, idealerweise 18–20px für Namen
- **Interaktion:** Touch-optimierte Checkboxen (min. 48×48 px Touch-Target)
- **Farbcodierung:**
  - 🟢 Grün = Anwesend
  - 🔴 Rot = Abwesend
  - ⚪ Grau = Noch nicht erfasst
- **Layout:** Große Zeilen, keine verschachtelten Menüs. Pro Termin eine Seite.
- **Ablauf:**
  1. Aufsicht wählt ihren Termin aus der Tagesliste.
  2. Liste der Schüler wird angezeigt (alphabetisch).
  3. Nach und nach werden Schüler abgehakt.
  4. „Speichern“-Button prominent unten (sticky).

### 5.2 Orga-Team: „Effiziente Listenverwaltung"
- **Primäre Ansicht:** Daten-Tabelle mit Filtern und Sortierung
- **Spalten:** Schüler | Fach | Urspr. Datum | Attest-Status | Offen seit | Vorgeschlagener Slot | Aktionen
- **Bulk-Actions:** Checkbox pro Zeile + Dropdown „Ausgewählte… zuweisen / Attest-Status setzen / Exportieren"
- **Quick-Edit:** Inline-Editing für Status und Zuweisung, ohne Seitenwechsel
- **Warnungen:** Rote Badges bei Konflikten, gelbe bei Attest-Fristablauf

### 5.3 Schüler: „Klarheit & Ruhe"
- **Primäre Ansicht:** Timeline oder Karten (1 Karte = 1 Termin)
- **Karte enthält:** Fach, Datum, Uhrzeit, Raum, Status-Badge, Countdown ("in 3 Tagen")
- **Farben:**
  - 🔵 Blau = Bestätigt / Zukünftig
  - 🟡 Gelb = Beantragt / Warte auf Bestätigung
  - 🟢 Grün = Erledigt
  - 🔴 Rot = Abgesagt / Nicht erschienen
- **Keine Ablenkung:** Keine überflüssigen Menüs, keine Werbung, keine komplexen Interaktionen.

### 5.4 Schulleitung: „Kontrolle & Überblick"
- **Dashboard:** KPI-Kacheln (offene Fälle, heutige Termine, Konflikte, Attest-Quote)
- **Regelwerk:** Formular-basiert, mit Erklärungstooltips für jede Regel
- **Audit-Log:** Filterbare Tabelle mit Zeitstrahl, Akteur, Aktion, Begründung

---

## 6. Technische Architektur-Vorschlag

### 6.1 Stack-Empfehlung

| Schicht | Technologie | Begründung |
|---------|-------------|------------|
| **Backend** | Symfony 6/7 (PHP) | IServ ist PHP-basiert; Symfony bietet robustes API-Framework, gute ORM (Doctrine), ausgereiftes Ökosystem. |
| **API** | API-Platform oder Symfony + serializer | Schnelle CRUD-API, OpenAPI-Generierung, Validierung. |
| **Datenbank** | PostgreSQL (Prod) / SQLite (Dev) | JSONB für flexible Regeln/Logs, transaktionale Integrität. |
| **Frontend** | Symfony Twig + HTMX + Alpine.js | Für schnelle, servergerenderte UIs mit moderner Interaktivität ohne JS-Build-Step. Für SPA-Bereiche: Vue.js oder React. |
| **Auth** | IServ OpenID Connect | Single Sign-On, Rollen-Mapping über IServ-Gruppen. |
| **Queue** | Symfony Messenger + Redis | Für E-Mails, Benachrichtigungen, Hintergrund-Jobs (z. B. Sync). |
| **Assets** | Tailwind CSS + shadcn/ui-Komponenten | Modern, konsistent, gut wartbar. |

### 6.2 Micro-Module-Strategie
Da du bereits ein Schul-IT-System (Terminplaner) hast, empfehle ich eine **monolithische Symfony-Applikation mit klar getrennten Bounded Contexts**:

```
src/
├── Shared/              # Domain Events, Auth, Audit-Log
├── Nachschreiber/       # Alles rund um Versäumnis, Slot, Termin
├── Klausurplan/         # Dein bestehender Terminplaner (oder geteilt)
├── IservIntegration/    # IServ-API-Client, Sync-Jobs, SSO
└── Reporting/           # Exports, PDF, Statistiken
```

### 6.3 IServ-Integration (Detail)

| IServ-Feature | Integrationsart | Datenfluss |
|---------------|-----------------|------------|
| **Benutzer/Gruppen** | IServ-API (REST) oder LDAP | Nächtlicher Sync oder Webhook bei Änderung |
| **Single Sign-On** | OpenID Connect (OAuth2) | Login-Button leitet zu IServ, Token enthält Gruppen |
| **Kalender** | IServ-Kalender-API oder iCal-Feed | Termin wird als Event im IServ-Kalender des Schülers angelegt |
| **Startseite** | IServ-Widget-API oder iFrame | Widget auf IServ-Startseite zeigt anstehende Nachschreiben |
| **E-Mail** | IServ-Mail-API oder SMTP | System sendet über IServ-Mail-Adresse |
| **Räume** | IServ-Raumverwaltung-API oder manuelle Liste | Sync der Raumliste oder manuelle Pflege als Fallback |

### 6.4 Schnittstellen zu deiner Schul-IT-Plattform

| Schnittstelle | Protokoll | Nutzung |
|---------------|-----------|---------|
| **Shared Database** (empfohlen) | PostgreSQL | Beide Systeme lesen/schreiben in dieselbe DB (getrennte Schemas). |
| **REST API** | JSON/HTTPS | Terminplaner fragt Nachschreiber-API ab (z. B. für Dashboard-Widget). |
| **Event Bus** | Redis Pub/Sub oder Symfony Messenger | Domain Events (z. B. „Klausur angelegt“ → Nachschreiber-System erstellt Slot-Vorlage). |

---

## 7. Schulrechtliche & organisatorische Anforderungen (NRW)

### 7.1 Konfigurierbare Regeln (nicht hartkodiert)

| Regel | Standardwert | Beschreibung |
|-------|-------------|--------------|
| `attest_frist_tage` | 3 | Tage nach Versäumnis, innerhalb derer ein Attest vorliegen muss |
| `max_klausuren_pro_woche` | 3 | Max. Anzahl an Klausuren (inkl. Nachschreiben) pro Woche |
| `vorauslaufzeit_stunden` | 48 | Mindestzeit zwischen Zuweisung und Termin |
| `nachmittag_nur_bei_bedarf` | true | Nachmittagsslots nur verwenden, wenn kein Vormittag verfügbar |
| `nachmittag_beginn_uhrzeit` | 14:00 | Ab wann gilt ein Slot als „Nachmittag“ |
| `sonderfaelle_sl_genehmigung` | true | Nachmittagsnachschriften/Terminänderungen erfordern SL-Freigabe |
| `max_nachschreiben_pro_schueler_pro_monat` | 2 | Limitierung zur Vermeidung von Missbrauch |

### 7.2 Datenschutz (DSGVO / Schulrecht NRW)

- **Rechtsgrundlage:** Art. 6 DSGVO (berechtigtes Interesse / Erfüllung öffentlicher Aufgabe)
- **Speicherdauer:** Nachschreibdaten werden nach Abschluss des Schuljahres + 1 Jahr aufbewahrt, dann anonymisiert oder gelöscht (konfigurierbar).
- **Einwilligung:** Benachrichtigungen an Eltern nur mit Einwilligung oder wenn das Kind minderjährig ist (gesetzliche Vertretung).
- **Auskunft:** Schüler können über das System jederzeit einsehen, welche Daten zu ihnen gespeichert sind (Self-Service).
- **Löschung:** Automatisierte Löschung nach definierter Frist (konfigurierbar).

### 7.3 Audit & Beweissicherung

- Jede Statusänderung ist protokolliert (Wer? Wann? Warum?).
- Atteste werden als Datei abgelegt (Scan/Upload) und revisionssicher verknüpft.
- Änderungen an Terminen erzeugen eine neue Version im Audit-Log.
- Exporte für Prüfungsakten sind unveränderlich (PDF mit Zeitstempel).

---

## 8. Entwicklungs-Roadmap (Empfehlung)

| Phase | Dauer | Liefergegenstände |
|-------|-------|-------------------|
| **Phase 0** | 2 Wochen | IServ-SSO, Datenbank, API-Grundgerüst, Audit-Log |
| **Phase 1 (MVP)** | 4–6 Wochen | Versäumnis erfassen, Slots verwalten, Zuweisen, Aufsichts-UI, Schüler-Übersicht, Basis-Benachrichtigungen |
| **Phase 2** | 3–4 Wochen | Regelengine, automatische Vorschläge, Konfliktmanagement, Klausur-Upload, Erweiterte Konfliktdashboards |
| **Phase 3** | 4–6 Wochen | Timeline, Eltern-Portal, PWA, Offline-Modus, Statistiken, IServ-Vertiefung, Schul-IT-Integration |
| **Gesamt** | **13–18 Wochen** | Vollständiges System |

### MVP-Definition (Phase 1)
> Ein Schulleitungsmitglied kann eine Nachschrift planen, ein Schüler wird zugewiesen, die Aufsicht erfasst die Anwesenheit, und der Schüler sieht seinen Termin in IServ. Alles andere ist „Nice to have“.

---

## 9. Erfolgskriterien (Definition of Done)

1. **Funktional:** Alle Features der Phase sind implementiert und getestet.
2. **Integration:** IServ-SSO funktioniert, Kalender-Sync ist aktiv, Benutzer werden synchronisiert.
3. **UX:** Jede Rolle kann ihre Kernaufgabe in ≤ 3 Klicks/Klicks erledigen (Aufsicht: Check-In in einem Schritt pro Schüler).
4. **Performance:** Seitenladung < 2 Sekunden, API-Antwort < 500ms, Bulk-Zuweisung für 50 Schüler < 5 Sekunden.
5. **Rechtssicherheit:** Audit-Log ist vollständig, DSGVO-Konformität ist dokumentiert, Attest-Fristen sind konfigurierbar.
6. **Mobil:** Aufsichts-Frontend funktioniert auf einem 10-Zoll-Tablet ohne Zoomen.
7. **Deployment:** Update-fähig ohne Datenverlust, Rollback möglich.

---

## 10. Nächste Schritte

1. **Stakeholder-Review:** Dieses Dokument mit Schulleitung, Orga-Team und ggf. IServ-Admin abstimmen.
2. **MVP-Scope fixieren:** Welche Features aus Phase 1 sind absolut notwendig für den ersten Echtbetrieb?
3. **IServ-API-Dokumentation prüfen:** Welche IServ-Version läuft? Welche APIs sind verfügbar?
4. **Technischen Stack finalisieren:** Symfony-Version, Datenbank, Hosting (bestehender Server?)
5. **Claude/Codex-Prompt erstellen:** Auf Basis dieser Spezifikation einen strukturierten Implementierungs-Prompt für das MVP generieren.

---

*Dieses Dokument ist ein lebendes Dokument. Bei Änderungen an Anforderungen oder nach Rückmeldungen aus der Praxis sollten die entsprechenden Abschnitte aktualisiert werden.*
