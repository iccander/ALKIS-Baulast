<?php
const   // Erkennungsmuster Koordinatenpaar (mit Komma und/oder Leerzeichen getrennt)
	REGEX_KOO = '/([0-9]{7,8}\.[0-9]{3,4})\s*,?\s*([0-9]{7}\.[0-9]{3,4})/',
	REGEX_ARC = '/1e\+101\s*,?\s*([0-9]+\.[0-9]{3,4})/i'; // und Bogen ("1e+101")

// Formatierte Koordinatenpaare für gml:posList
function koo(array $umring, int $i): string {
	return sprintf('%.3f %.3f',$umring[$i]['rechts'],$umring[$i]['hoch']);
}
// XML-Sicherheit & Standardwerte
function xml($wert, $default = ''): string {
    return htmlspecialchars(trim($wert ?? $default),ENT_XML1|ENT_QUOTES,'UTF-8');
}
// Formular XML-safe auslesen 
$antrag = xml($_POST['antrag'],'0000000000');
$ubab = xml($_POST['ubab'],'0000');
$name = xml($_POST['name'],'Baulast');
$bez = xml($_POST['bez']);
$date = $_POST['date'] ?? '';
$umring = [];
if (!empty($_POST['umring'])) {
    foreach (preg_split('/\r\n|\r|\n/', $_POST['umring']) as $row) {    //in Zeilen zerlegen
        // Koordinatenpaare erkennen (Trenner: Leerzeichen und/oder Komma)
        if (preg_match(REGEX_KOO, $row, $match)) {
            $umring[] = ['hoch' => round((float)$match[2],3), // Hochwert auf mm runden
              'rechts' => round((float)substr($match[1],strpos($match[1],'.')-6),3)]; // Rechtswert 6-stellig ohne UTM-Zonennummer
        }
        elseif (preg_match(REGEX_ARC, $row, $match)) {  //Bogenradius erkennen
			if (($last = array_key_last($umring)) !== null) // nur wenn Array schon mit einem Koordinatenpaar befüllt ist
				$umring[$last]['radius'] = (float)$match[1]; // letzten Arrayeintrag um 'radius' ergänzen 
		}
    }
}
//NAS-Datei speichern
header('Content-type: application/xml; charset=utf-8'); //UTF-8!
header('Content-Disposition: attachment; filename="vFE_'.preg_replace('/[^A-Za-z0-9_-]/','',$antrag).'_001.xml"'); //Header-Injection absichern
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";

// NAS-XML-Vorspann direkt ausgeben
?>
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
        <AX_BauRaumOderBodenordnungsrecht gml:id="DE_BDVI000000000">
          <gml:identifier codeSpace="http://www.adv-online.de/">urn:adv:oid:DE_BDVI000000000</gml:identifier>
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
<?php // Ausgabe Liniensegmente
for ($i = 0, $count = count($umring); $i < $count; $i++) {
	echo '                      <gml:curveMember>
                        <gml:Curve gml:id="BDVI'.str_pad($i+1,4,'0',STR_PAD_LEFT).'">
                          <gml:segments>
                            <gml:LineStringSegment>
                              <gml:posList>'.koo($umring, $i).' '.koo($umring,($i+1)%$count).'</gml:posList>
                            </gml:LineStringSegment>
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
