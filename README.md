# Lexico Hören Education – WordPress Plugin

WordPress Plugin für gestaffelte Institutionspreise, Preisrechner und Bestellformular für **Lexico Hören Education** – die Custom App für Schulen und Organisationen.

---

## Funktionsübersicht

- **Preisrechner** mit interaktivem Slider (1–100 Lizenzen)
- **Gleitende Preisstruktur** ohne Preissprünge: CHF 349.– (Einzellizenz) → CHF 100.– (100 Lizenzen)
- **Bestellformular** mit Feldern für Apple School / Business Manager
- **E-Mail-Benachrichtigung** an Administrator und Bestätigung an Kunde
- **Admin-Bereich** mit Bestellübersicht und CSV-Export
- **Einstellungsseite** für alle Texte, Preise und E-Mails
- **Mobiloptimiert** (responsive Layout)

---

## Installation

1. [Plugin herunterladen](https://github.com/robertjeanhuber/lexico-education/archive/refs/heads/main.zip)
2. Im WordPress-Admin: **Plugins → Installieren → Plugin hochladen**
3. ZIP-Datei auswählen und installieren
4. Plugin aktivieren

---

## Verwendung

Shortcode auf einer beliebigen Seite oder in einem Beitrag einfügen:

```
[lexico_education]
```

Das Plugin rendert automatisch:
1. Den optionalen Einleitungstext (konfigurierbar)
2. Den Preisrechner mit Slider
3. Das Bestellformular

---

## Einstellungen

Unter **Lexico Education → Einstellungen** können alle Inhalte angepasst werden:

### Allgemein

| Einstellung | Beschreibung | Standard |
|---|---|---|
| Benachrichtigungs-E-Mail | Adresse, an die neue Bestellungen gemeldet werden | `mail@pappy.ch` |
| App Store Einzelpreis | Regulärer Einzelpreis – Basis für die Rabattberechnung | `349` |

### Texte (Frontend)

| Einstellung | Beschreibung |
|---|---|
| Einleitungstext | Text oberhalb des Preisrechners (Leerzeilen = neue Absätze) |
| Formular-Titel | Überschrift des Bestellformulars |
| Beschriftung Senden-Button | Text auf dem Absenden-Button |
| Erfolgsmeldung | Meldung nach erfolgreichem Absenden |

### E-Mails

| Einstellung | Beschreibung |
|---|---|
| Betreff Benachrichtigung (intern) | Betreff der Admin-Benachrichtigung |
| Betreff Bestätigung (Kunde) | Betreff der Bestätigungsmail an den Kunden |
| Text Bestätigung (Kunde) | Vollständiger Text der Bestätigungsmail |

---

## Platzhalter für E-Mail-Texte

In Betreff- und Textfeldern der E-Mails können folgende Platzhalter verwendet werden:

| Platzhalter | Inhalt |
|---|---|
| `{org_name}` | Organisations-Name |
| `{org_id}` | Organisations-ID (Apple School/Business Manager) |
| `{n}` | Anzahl Lizenzen |
| `{price_per}` | Preis pro Lizenz (z. B. `145.–`) |
| `{price_total}` | Gesamtpreis (z. B. `7'250.–`) |
| `{contact_name}` | Name der Kontaktperson |
| `{contact_email}` | E-Mail der Kontaktperson |

**Beispiel Betreff:**
```
Neue Lexico Education Anfrage: {org_name} ({n} Lizenzen)
```

**Beispiel E-Mail-Text:**
```
Guten Tag {contact_name},

vielen Dank für Ihre Anfrage über {n} Lizenzen für {org_name}.
Gesamtpreis: CHF {price_total}

Wir melden uns in Kürze.
```

---

## Preisstruktur

Die Preisberechnung folgt einer linearen Formel ohne Preissprünge:

| Lizenzen | Preis pro Lizenz | Gesamtpreis |
|---:|---:|---:|
| 1 | CHF 349.– | CHF 349.– |
| 10 | CHF 269.– | CHF 2'690.– |
| 20 | CHF 175.– | CHF 3'490.– |
| 30 | CHF 143.– | CHF 4'290.– |
| 50 | CHF 118.– | CHF 5'900.– |
| 75 | CHF 107.– | CHF 7'975.– |
| 100 | CHF 100.– | CHF 10'000.– |

> Ab 20 Lizenzen entspricht der Stückpreis dem offiziellen Apple-Mengenrabatt (50 %). Der Rabatt gilt **pro Bestellung** – Lizenzen müssen in einer einzigen Bestellung bezogen werden.

---

## Bestellungen verwalten

Unter **Lexico Education → Bestellungen** sind alle eingegangenen Anfragen als Tabelle einsehbar mit:

- Datum, Organisations-Name, Organisations-ID
- Anzahl Lizenzen, Preis pro Lizenz, Gesamtpreis
- Kontaktname und E-Mail

**CSV-Export** (UTF-8 mit BOM, Semikolon-getrennt, direkt in Excel öffenbar) über den Button «↓ CSV exportieren».

---

## Voraussetzungen

- WordPress 5.8 oder neuer
- PHP 7.4 oder neuer
- Schreibrechte auf die WordPress-Datenbank (für die Bestelltabelle)

---

## Lizenz

Entwickelt von [Pappy GmbH](https://www.pappy.ch) für [Lexico](https://www.lexico.ch).
