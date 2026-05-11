# NAS-Fortführungsauftrag für Baulasten

PHP-Skript zur Erzeugung von NAS-Fortführungsaufträgen für Baulasten mit Umringsdaten aus GEOgraf-Out-Dateien der Firma HHK.

## Funktionen

Das Skript erzeugt aus eingegebenen Umringskoordinaten eine NAS-konforme XML-Datei (`AX_Fortfuehrungsauftrag`) zur Übernahme in AAA-/ALKIS-/NAS-Verfahren. 

Unterstützt werden:
- Gerade Liniensegmente
- Rechts- und Linksbögen
- automatische Berechnung von Bogenmittelpunkten

## Unterstützte Eingabedaten

Das Skript verarbeitet insbesondere Koordinatenlisten aus GEOgraf-Out-Dateien.

Erkannt werden:

### Koordinatenpaare

Beispiel:

```text
3325481.123 5778456.789
3325485.456,5778459.012
```

Dabei wird:
- der Hochwert vollständig übernommen
- beim Rechtswert die führende UTM-Zonennummer entfernt
- die Laufrichtung des Umrings (rechtsherum in GEOgraf, linksherum in ALKSI/GML) angepasst  

Vorausgesetzt wird, dass die Umringe 

### Kreisbögen

GEOgraf codiert Bögen über spezielle Kennungen:

```text
POS1: 1e+101,31.2552
POS2: 1e+102,12.5000
```

Dabei bedeutet:

| Kennung | Bedeutung |
|---|---|
| `1e+101` | Rechtsbogen |
| `1e+102` | Linksbogen |

Der Zahlenwert hinter dem Komma ist der Radius des Bogens. Die Radiusinformation wird dem jeweils zuletzt eingelesenen Punkt zugeordnet.

## Geometrieverarbeitung

Das Skript berechnet für Kreisbögen automatisch den Mittelpunkt auf dem Bogen. Damit können aus Startpunkt, Endpunkt, Radius und Krümmungsrichtung NAS-konforme `gml:Arc`-Elemente erzeugt werden.

## Ausgabe

Erzeugt wird eine NAS/XML-Datei mit:

- `gml:LineStringSegment`
- `gml:Arc`

inklusive:

- Fortführungsauftrag
- Dienststellenschlüssel
- Baulastinformationen
- Geometrie



