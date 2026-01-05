# IBD Vertretungen WordPress Plugin

Interaktive Google Maps Karte mit Firmenvertretungen für IBD Wickeltechnik.

## Features

- Interaktive Google Maps Karte mit Marker-Clustering
- Moderne Card-UI für Vertretungen mit Akkordeon-Navigation
- Länder-Highlighting auf der Karte
- Live-Suche nach Ländern, Vertretungen und Ansprechpartnern
- PDF-Export (einzeln oder alle)
- vCard-Download für Kontakte
- Responsive Design (Desktop + Mobile)
- WPML/Polylang ready

## Requirements

- WordPress 6.0+
- PHP 8.0+
- ACF Pro (für Custom Fields)
- Google Maps API Key

## Installation

1. Plugin-Ordner nach `wp-content/plugins/ibd-vertretungen/` kopieren
2. Plugin im WordPress Admin aktivieren
3. ACF-Feldgruppen importieren (siehe unten)
4. Einstellungen unter **Einstellungen → IBD Vertretungen** konfigurieren
5. Google Maps API Key eintragen
6. Shortcode `[ibd_vertretungen]` auf einer Seite einfügen

## ACF Import

1. Gehe zu **ACF → Werkzeuge**
2. Importiere die Datei: `wp-content/plugins/ibd-vertretungen/acf/acf-export-2026-01-05.json`

Dies erstellt:
- Custom Post Type: `vertretung`
- Taxonomy: `land`
- Feldgruppe: Vertretungen

## Shortcode

```
[ibd_vertretungen]
```

### Parameter

| Parameter | Beschreibung | Standard |
|-----------|--------------|----------|
| `show_search` | Suchfeld anzeigen | `true` |
| `show_filter` | Filter anzeigen | `true` |

## Einstellungen

- **Google Maps API Key**: Erforderlich für die Kartenansicht
- **Kartenstart-Position**: Lat/Lng und Zoom-Level
- **Primärfarbe**: Standard: #BE1622 (IBD Rot)

## REST API Endpoints

```
GET /wp-json/ibd/v1/vertretungen        - Alle Vertretungen
GET /wp-json/ibd/v1/vertretungen/{id}   - Einzelne Vertretung
GET /wp-json/ibd/v1/countries           - Alle Länder
GET /wp-json/ibd/v1/geojson/{code}      - GeoJSON für Land
```

## Dateistruktur

```
ibd-vertretungen/
├── ibd-vertretungen.php      # Haupt-Plugin-Datei
├── includes/
│   ├── class-country-mapper.php
│   ├── class-data-handler.php
│   ├── class-rest-api.php
│   ├── class-shortcode.php
│   ├── class-vcard.php
│   └── class-pdf-export.php
├── assets/
│   ├── css/frontend.css
│   ├── js/app.js
│   └── geojson/
├── templates/
│   ├── map-container.php
│   └── card-template.php
├── admin/
│   ├── class-settings.php
│   ├── css/admin.css
│   └── js/admin.js
├── acf/
│   └── acf-export-2026-01-05.json
└── languages/
```

## GeoJSON für Länder-Highlighting

Für das Länder-Highlighting werden vereinfachte GeoJSON-Dateien benötigt:

1. GeoJSON-Dateien von [Natural Earth](https://www.naturalearthdata.com/) herunterladen
2. Mit [Mapshaper](https://mapshaper.org/) auf ~2% vereinfachen
3. Als `{ISO-CODE}.json` in `assets/geojson/` speichern (z.B. `DE.json`, `AT.json`)

## Entwicklung

### Hooks

```php
// Vertretungen-Daten filtern
add_filter('ibd_vertretungen_data', function($data) {
    return $data;
});

// Vor dem Rendern
do_action('ibd_before_render_map');

// Nach dem Rendern
do_action('ibd_after_render_map');
```

## Lizenz

Proprietär - Nur für IBD Wickeltechnik

## Support

Bei Fragen oder Problemen wenden Sie sich an den Entwickler.
