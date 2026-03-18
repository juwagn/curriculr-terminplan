# GSH Terminplan Dashboard

WordPress-Plugin für die Gesamtschule Horst zur Anzeige des Schulkalenders
aus IServ-iCal-Feeds.

## Features

- Quartalweise Kalenderansicht (Desktop-Tabelle + Mobile-Agenda)
- Kategorie-Filter und Volltextsuche
- Dark Mode mit System-Erkennung
- PDF-Export (Quartal oder Gesamtjahr)
- Onboarding-Tour für neue Nutzer\*innen
- Feedback-Funktion (direkt per E-Mail)
- Kiosk-Modus für IServ-Einbettung
- Stale-While-Revalidate-Caching via WP-Cron

## Aktuelle Version

v3.12.0

## Anforderungen

- WordPress 6.0 oder höher
- PHP 8.0 oder höher
- IServ-iCal-Feed (HTTPS)

## Installation

1. Plugin-Ordner nach `wp-content/plugins/gsh-terminplan/` kopieren
2. Plugin in WordPress aktivieren
3. Einstellungen unter `Einstellungen → GSH Terminplan` konfigurieren
4. Shortcode `[gsh_terminplan]` auf einer Seite einfügen

## Entwicklung

Implementierungs-Prompts für alle Features liegen im Ordner `prompts/`.
Jeder Prompt ist eigenständig und enthält alle notwendigen Anweisungen
für eine Änderung.

## Lizenz

Privates Schulprojekt – Gesamtschule Horst
