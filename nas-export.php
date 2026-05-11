<?php
// *** Konstanten *** //
const   // Erkennungsmuster Koordinatenpaar (mit Komma und/oder Leerzeichen getrennt)
	REGEX_KOO = '/([0-9]{7,8}\.[0-9]{3,4})\s*,?\s*([0-9]{7}\.[0-9]{3,4})/',
	REGEX_ARC = '/1e\+10([12])\s*,?\s*(-?[0-9]+\.[0-9]{3,4})/i';   // Radius; rechter/linker Bogen (1e+101/1e+102)

function koo(array $punkt):string {           // auf 3 Nachkommastellen gerundete Koordinatenpaare für gml:posList
    return sprintf('%.3f %.3f', $punkt['rechts'], $punkt['hoch']);
}
//***  Funktionen *** //
function bogenmitte(array $A,array $E):string{// Bogenmittenpunkt berechnen und ausgeben
    $D_h = $E['hoch']-$A['hoch'];             // Differenzvektor von ...
	$D_r = $E['rechts']-$A['rechts'];         // ... A(nfangs-) zu E(ndpunkt)
    $sek2 = ($sek = hypot($D_r,$D_h))/2;      // (halbe) Sekante
    if (abs($A['radius']) < $sek2) return ''; // Test auf ungültige Geometrie (ohne: $sek == 0 → Sonderfall Kreis) 
    $M_h = ($A['hoch']+$E['hoch'])/2;         // M(ittelpunkt) ...
	$M_r = ($A['rechts']+$E['rechts'])/2;     // ... der Sekante AE
	$lot = sqrt(pow($A['radius'],2)-pow($sek2,2)); // Orthogonalabstand Kreismitte
	$pfeil = abs($A['radius']-$lot);          // Pfeilhöhe (negativ bei überspanntem Bogen > 200 gon)
    $N_r = -$D_h/$sek; $N_h = $D_r/$sek;      // Einheits-Normalvektor
    return koo(['rechts' => $M_r+($pfeil*$N_r*$A['dir']),'hoch' => $M_h+($pfeil*$N_h*$A['dir'])]);
}
function xmlsafe($wert,$default=''):string {  // XML-Sicherheit & Standardwerte
    return htmlspecialchars(trim($wert ?? $default),ENT_XML1|ENT_QUOTES,'UTF-8');
}
//***  Formular einlesen *** //
$antrag = xmlsafe($_POST['antrag'],'0000000000');
$ubab = xmlsafe($_POST['ubab'],'0000');
$name = xmlsafe($_POST['name'],'Baulast');
$bez = xmlsafe($_POST['bez']);
$date = xmlsafe($_POST['date'] ?? '');
$umringe = [[]];          // Container zur Aufnahme mehrerer Umringe (für gml:MultiSurface)
$umring = &$umringe[0];   // Referenz auf jeweils aktuellen Umring (meist nur einer)
if (!empty($_POST['umring'])) {
    foreach (array_reverse(preg_split('/\R/',$_POST['umring'])) as $row) { // in Zeilen zerlegen; von hinten wegen umgedrehter Laufrichtung in GML
        if (preg_match(REGEX_KOO, $row, $match)) {               // Koordinatenpaare erkennen (Trenner: Leerzeichen und/oder Komma)
            $umring[] = ['hoch' => (float)$match[2],             // als Dezimalzahl (zum rechnen & runden)
                       'rechts' => (float)substr($match[1],strpos($match[1],'.')-6)]; // 6-stellig ohne UTM-Zonennummer
        } elseif (preg_match(REGEX_ARC, $row, $match)) {         // Bogenradius und -richtung erkennen, aber
			if (($last = array_key_last($umring)) !== null)      // nur wenn Array schon mit einem Koordinatenpaar befüllt ist,
				$umring[$last] += ['radius' => (float)$match[2], // dann letzten Arrayeintrag um Radius ergänzen und  
                                   'dir' => 2*(int)$match[1]-3]; // inverse Krümmungsrichtung 1 (aus 1e+101) → -1 bzw. 2 (aus 1e+102) → +1
		} elseif (!empty($umring)) { $umringe[] = [];            // weder Koordinate noch Bogen + aktueller Umring nicht leer → neuen Umring erzeugen
			$umring = &$umringe[array_key_last($umringe)];       // und Referenz auf diesen neuen $umring setzen
		} 
    }
	if (empty(end($umringe))) array_pop($umringe);               // leeren letzten Umring entfernen
} $umring = &$umringe[0]; // provisorisch bis zur Implementierung der Ausgabe von MultiSurface zunächst Referenz auf ersten (meist einzigen) Umring setzen

// *** NAS-Ausgabe *** //
header('Content-type: application/xml; charset=utf-8');          // Datei speichern
$safe = preg_replace('/[^\w %[\].()%&-]+/u','',$antrag);         // Header-Injection absichern, Umlaute erhalten
header('Content-Disposition: attachment; filename="vFE_'.$safe.'_001.xml"; filename*=UTF-8\'\''.rawurlencode("vFE_{$safe}_001.xml"));
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<!-- erzeugt mit bdvi-bb.de/baulast/ -->'."\n";
// NAS-Vorspann direkt ausgeben ?>
<AX_Fortfuehrungsauftrag xmlns="http://www.adv-online.de/namespaces/adv/gid/7.1" xmlns:adv="http://www.adv-online.de/namespaces/adv/gid/7.1" xmlns:gco="http://www.isotc211.org/2005/gco" xmlns:gmd="http://www.isotc211.org/2005/gmd" xmlns:gml="http://www.opengis.net/gml/3.2" xmlns:ogc="http://www.adv-online.de/namespaces/adv/gid/ogc" xmlns:fes="http://www.opengis.net/fes/2.0" xmlns:ows="http://www.opengis.net/ows/1.1" xmlns:wfs="http://www.opengis.net/wfs/2.0" xmlns:wfsext="http://www.adv-online.de/namespaces/adv/gid/wfsext/2.0" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.adv-online.de/namespaces/adv/gid/7.1 NAS-Operationen.xsd">
  <empfaenger>
    <AA_Empfaenger>
      <direkt>true</direkt>
    </AA_Empfaenger>
  </empfaenger>
  <ausgabeform>application/xml</ausgabeform>
  <koordinatenangaben>
    <AA_Koordinatenreferenzsystemangaben>
      <crs xlink:href="urn:adv:crs:ETRS89_UTM33"/>
      <anzahlDerNachkommastellen>3</anzahlDerNachkommastellen>
      <standard>true</standard>
    </AA_Koordinatenreferenzsystemangaben>
  </koordinatenangaben>
  <geaenderteObjekte>
    <wfs:Transaction version="2.0.0" service="WFS">
      <wfs:Insert>
        <AX_BauRaumOderBodenordnungsrecht gml:id="DE_BDVIBAULAST67">
          <gml:identifier codeSpace="http://www.adv-online.de/">urn:adv:oid:DE_BDVIBAULAST67</gml:identifier>
          <lebenszeitintervall>
            <AA_Lebenszeitintervall>
              <beginnt>9999-01-01T00:00:00Z</beginnt>
            </AA_Lebenszeitintervall>
          </lebenszeitintervall>
          <modellart>
            <AA_Modellart>
              <advStandardModell>DLKM</advStandardModell>
            </AA_Modellart>
          </modellart>
          <position>
            <gml:Surface srsName="urn:adv:crs:ETRS89_UTM33" gml:id="BDVI0000">
              <gml:patches>
                <gml:PolygonPatch>
                  <gml:exterior>
                    <gml:Ring>
<?php // Ausgabe Linien- und Bogensegmente
for ($i = 0, $count = count($umring);$i < $count;$i++) {
    $A = $umring[$i]; $E = $umring[($i + 1)%$count]; // Kreisschluss mit modulo = Sprung zum Anfang
    echo '                      <gml:curveMember>
                        <gml:Curve gml:id="BDVIBL'.str_pad($i+1,4,'0',STR_PAD_LEFT).'">
                          <gml:segments>
                            ';
    if (isset($A['radius'])) { // Kreisbogen
        echo '<gml:Arc>
                              <gml:posList>'.koo($A).' '.bogenmitte($A,$E).' '.koo($E).'</gml:posList>
                            </gml:Arc>';
    } else {  // Gerade
	echo '<gml:LineStringSegment>
                              <gml:posList>'.koo($A).' '.koo($E).'</gml:posList>
                            </gml:LineStringSegment>';
    }
    echo '
                          </gml:segments>
                        </gml:Curve>
                      </gml:curveMember>
';}
?>
                    </gml:Ring>
                  </gml:exterior>
                </gml:PolygonPatch>
              </gml:patches>
            </gml:Surface>
          </position>
          <artDerFestlegung>2610</artDerFestlegung>
          <ausfuehrendeStelle>
          <AX_Dienststelle_Schluessel>
            <land>12</land>          
            <stelle><?=$ubab?></stelle>
          </AX_Dienststelle_Schluessel>
          </ausfuehrendeStelle>
          <name><?=$name?></name>
          <bezeichnung><?=$bez?></bezeichnung>
          <datumRechtskraeftig><?=$date?></datumRechtskraeftig>
        </AX_BauRaumOderBodenordnungsrecht>
      </wfs:Insert>
    </wfs:Transaction>
  </geaenderteObjekte>
  <profilkennung/>
  <antragsnummer><?=$antrag?></antragsnummer>
  <auftragsnummer/>
  <geometriebehandlung>false</geometriebehandlung>
  <mitTemporaeremArbeitsbereich>false</mitTemporaeremArbeitsbereich>
  <mitObjektenImFortfuehrungsgebiet>false</mitObjektenImFortfuehrungsgebiet>
  <mitFortfuehrungsnachweis>false</mitFortfuehrungsnachweis>
</AX_Fortfuehrungsauftrag>
