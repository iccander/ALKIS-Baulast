<?php
// Startwerte
$antrag ='0000000000'; $ubab = '0000'; $name = 'Baulast'; $bez = ''; $date = ''; $umring = [];

// Formatierte Koordinatenpaare für gml:posList
function koo($umring, $i) { return
        number_format($umring[$i]['rechts'],3,'.','').' '.
        number_format($umring[$i]['hoch'],3,'.','');
}
// Formular auslesen & Leerzeichen entfernen
if(isset($_REQUEST['antrag'])) {$antrag = trim($_REQUEST['antrag']);}
if(isset($_REQUEST['ubab'])) {$ubab = trim($_REQUEST['ubab']);}
if(isset($_REQUEST['name'])) {$name = trim($_REQUEST['name']);}
if(isset($_REQUEST['bez'])) {$bez = trim($_REQUEST['bez']);}
if(isset($_REQUEST['date'])) {$date = $_REQUEST['date'];}
if(isset($_REQUEST['umring'])){
    foreach (preg_split('/\r\n|\r|\n/', $_REQUEST['umring']) as $row) {    //in Zeilen zerlegen
        // Koordinatenpaare erkennen (Trenner: Leerzeichen und/oder Komma)
        if (preg_match('/([0-9]{7,8}\.[0-9]{3,4})\s*,?\s*([0-9]{7}\.[0-9]{3,4})/', $row, $match)) {
            $umring[] = ['hoch' => round((float)$match[2], 3), // Hochwert auf mm runden
				// Rechtswert 6stellig ohne UTM-Zonennummer
                'rechts' => round((float)substr($match[1],strpos($match[1],'.')-6),3)];
        }
    }
}
//NAS-Datei senden
header('Content-type: application/xml; charset=utf-8'); //UTF-8!
header('Content-Disposition: attachment; filename=vFE_'.$antrag.'_001.xml');
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
for ($i = 0; $i < count($umring); $i++) { 
	echo '                      <gml:curveMember>
                        <gml:Curve gml:id="BDVI'.str_pad($i+1,4,'0',STR_PAD_LEFT).'">
                          <gml:segments>
                            <gml:LineStringSegment>
                              <gml:posList>'.koo($umring, $i).' '.koo($umring,($i+1< count($umring))?$i+1:0).'</gml:posList>
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
            <stelle><?php if (strlen($ubab)>0) { echo $ubab;}?></stelle>
          </AX_Dienststelle_Schluessel>
          </ausfuehrendeStelle>
          <name><?php if (strlen($name)>0) { echo $name;}?></name>
          <bezeichnung><?php if (strlen($bez)>0) { echo $bez;}?></bezeichnung>
          <datumRechtskraeftig><?php if (strlen($date)>0) { echo $date;}?></datumRechtskraeftig>
        </AX_BauRaumOderBodenordnungsrecht>
      </wfs:Insert>
    </wfs:Transaction>
  </geaenderteObjekte>
  <profilkennung/>
  <antragsnummer><?php if (strlen($antrag)>0) { echo $antrag;}?></antragsnummer>
  <auftragsnummer/>
  <geometriebehandlung>false</geometriebehandlung>
  <mitTemporaeremArbeitsbereich>false</mitTemporaeremArbeitsbereich>
  <mitObjektenImFortfuehrungsgebiet>false</mitObjektenImFortfuehrungsgebiet>
  <mitFortfuehrungsnachweis>false</mitFortfuehrungsnachweis>
</AX_Fortfuehrungsauftrag>