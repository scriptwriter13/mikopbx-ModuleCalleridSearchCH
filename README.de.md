# Über
Dies ist ein Modul für MikoPBX, das bei eingehenden Anrufen den Anrufernamen aus dem Schweizer Telefonverzeichnis [tel.search.ch](https://tel.search.ch) anzeigt.

Bei jedem eingehenden Anruf wird die Nummer im Verzeichnis nachgeschlagen und der CallerID-Name auf `Name F. Ort, Strasse 1` gesetzt. Nummern können als `044…`, `+4144…`, `004144…` oder `4144…` ankommen. Nummern außerhalb der Schweiz und Nummern, die im Verzeichnis fehlen, bleiben unverändert, sodass ein vom Provider übermittelter Name erhalten bleibt.

Das Modul wurde 2026 von scriptwriter13 erstellt und unter der GPL V3 veröffentlicht.
[englische Version](README.md)
[Русская версия](README.ru.md)


# Installation

**Upgrade von 1.0:** Entferne zuerst das alte Modul `CalleridSearchCH`. Die Modul-ID hat nun das von MikoPBX geforderte Präfix `Module`, sodass eine Installation über 1.0 hinaus beide Module aktiviert lässt (zwei Abfragen bei jedem Anruf und das alte Skript läuft weiter).

1. Erstelle das Modul-Archiv (siehe unten) oder lade es von der Releases-Seite herunter.
2. Öffne im MikoPBX-Admin-Panel **Module** → **Installiert** → **Neues Modul hochladen** und wähle die ZIP-Datei aus.
3. Aktiviere das Modul.
4. Öffne die Modulseite, füge deinen API-Key ein und klicke auf Speichern.

Erfordert MikoPBX 2025.1.1 oder neuer.

# API-Key

Einen Schlüssel erhältst du unter https://tel.search.ch/api/getkey. Bei moderater Nutzung ist er kostenlos.
Ohne Schlüssel liefert das Verzeichnis nur den Namen, ohne Ort und Strasse.

Auf der Modulseite es möglich, die Häkchen auszuwählen, ob Anonyme Anrufe oder Calls der Kategorie Callcenter abgewiesen werden sollen.

# Datenschutz

Die Anrufernummer jedes eingehenden Schweizer Anrufs wird per HTTPS an tel.search.ch gesendet.
Das Modul speichert keine Anrufdaten und loggt keine Namen oder Adressen.

# Fehlerbehebung

- Das Verzeichnis wird mit einem harten Timeout von 2 Sekunden für die gesamte Anfrage abgefragt. Antwortet es nicht rechtzeitig, läuft der Anruf mit der ursprünglichen CallerID weiter.
- Fehler werden nie verschwiegen und brechen nie einen Anruf ab: Der Grund wird über AGI verbose mit dem Präfix `CalleridSearchCH:` in das Asterisk-Log geschrieben. Ein abgelehnter oder erschöpfter Schlüssel erscheint dort. Anrufernamen und -adressen werden nicht geloggt.
- Es wird kein Name angezeigt: Prüfe zuerst das Asterisk-Log und danach, ob die Nummer einen öffentlichen Eintrag bei tel.search.ch hat.
- Selbsttest ohne PBX und Netzwerk ausführen: `php tests/LookupTest.php`

# Build

`zip -r ../ModuleCalleridSearchCH.zip . -x '.git/*'`
