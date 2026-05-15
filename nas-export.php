<?
/** NAS-Generator für Baulasten v. 0.3
 **
 ** Copyright (c) 2026 Frank Reichert / BDVI Brandenburg
 **
 ** Diese Software ist unter der MIT-Lizenz lizenziert.
 ** Vollständiger Lizenztext unter: https://opensource.org
 **/
 
 // *** Konstanten *** //
const   // Erkennungsmuster Koordinatenpaar(mit Komma und/oder Leerzeichen getrennt)
	REGEX_KOO = '/([0-9]{7,8}\.[0-9]{3,4})\s*,?\s*([0-9]{7}\.[0-9]{3,4})/',
	REGEX_ARC = '/1e\+10([12])\s*,?\s*(-?[0-9]+\.[0-9]{3,4})/i',   // Radius; rechter/linker Bogen (1e+101/1e+102)
	UTM33 = ' srsName="urn:adv:crs:ETRS89_UTM33"';

// ***  Funktionen ***  //
function koo(array $P):string {                // auf 3 Nachkommastellen gerundete Koordinatenpaare für gml:posList
    return sprintf('%.3f %.3f',$P['rechts'],$P['hoch']);
}
function bogenmitte(array $A,array $E):string{ // Bogenmittenpunkt berechnen und ausgeben
    $D_h = $E['hoch']-$A['hoch'];              // Differenzvektor von ...
	$D_r = $E['rechts']-$A['rechts'];          // ... A(nfangs-) zu E(ndpunkt)
    $sek2 = ($sek = hypot($D_r,$D_h))/2;       // (halbe) Sekante
    if ($sek == 0 || abs($A['radius']) <= $sek2) return '';  // Test auf ungültige Geometrie (sek=0 → unerlaubter Sonderfall Kreis)
    $M_h = ($A['hoch']+$E['hoch'])/2;          // M(ittelpunkt) ...
	$M_r = ($A['rechts']+$E['rechts'])/2;      // ... der Sekante AE
	$lot = sqrt($A['radius']**2 - $sek2**2);   // Orthogonalabstand Kreismitte
	$pfeil = abs($A['radius']-$lot);           // Pfeilhöhe; abs() weil negativ bei überspanntem Bogen > 200 gon
    $N_r = -$D_h/$sek; $N_h = $D_r/$sek;       // Einheits-Normalvektor
    return koo(['rechts' => $M_r+($pfeil*$N_r*$A['dir']),'hoch' => $M_h+($pfeil*$N_h*$A['dir'])]);
}
function xmlsafe(?string $wert,string $default=''):string { // XML-Sicherheit & Standardwerte
    return htmlspecialchars(trim($wert ?? $default),ENT_XML1|ENT_QUOTES,'UTF-8');
}
function xml(string $tag,int $einzug=0):void { // XML-Einrückung
echo str_repeat('  ',$einzug),"<",$tag,">\n";  // gibt rohe XML-Tags inkl. Inhalt aus
}
$id=1;                                         // fortlaufende GML-ID über alle neuen Flächen und Linien 
function gmlid(int &$id):string {              // als Dummy-Identifikator
    return ' gml:id="BDVIBL'.str_pad($id++,4,'0',STR_PAD_LEFT).'"';
}
// ***  Formular einlesen *** //
$antrag = xmlsafe($_POST['antrag'],'0000000000');
$ubab = xmlsafe($_POST['ubab']);
$name = xmlsafe($_POST['typ'][0],'Baulast');
$bez = xmlsafe($_POST['bez'][0]);
$date = xmlsafe($_POST['date'][0] ?? '');
$umringe = [[]];          // Container zur Aufnahme mehrerer Umringe (für gml:MultiSurface)
$umring = &$umringe[0];   // Referenz auf jeweils aktuellen Umring (meist nur einer)
if (!empty($_POST['umring'][0])) {
    foreach (preg_split('/\R/',$_POST['umring'][0]) as $row) {   // in Zeilen zerlegen
        if (preg_match(REGEX_KOO, $row, $match)) {               // Koordinatenpaare erkennen (Trenner: Leerzeichen und/oder Komma)
            $umring[] = ['hoch' => (float)$match[2],             // als Dezimalzahl (zum rechnen & runden)
                       'rechts' => (float)substr($match[1],strpos($match[1],'.')-6)]; // 6-stellig ohne UTM-Zonennummer
        } elseif (preg_match(REGEX_ARC, $row, $match)) {         // Bogenradius und -richtung erkennen, aber
			if (($last = array_key_last($umring)) !== null)      // nur wenn Array schon mit einem Koordinatenpaar befüllt ist,
				$umring[$last] += ['radius' => (float)$match[2], // dann letzten Arrayeintrag um Radius ergänzen und  
                                   'dir' => 3-2*(int)$match[1]]; // Krümmungsrichtung 1 (aus 1e+101) → +1 bzw. 2 (aus 1e+102) → -1
		} elseif (!empty($umring)) { $umringe[] = [];            // weder Koordinate noch Bogen + aktueller Umring nicht leer → neuen Umring erzeugen
			$umring = &$umringe[array_key_last($umringe)];       // und Referenz auf diesen neuen $umring setzen
		} 
    } 
	if (empty(end($umringe))) array_pop($umringe);               // leeren letzten Umring entfernen
	unset($umring);                                              // Referenz lösen!
}
// *** NAS-Ausgabe *** //
header('Content-type: application/xml; charset=utf-8');          // Datei speichern
$safe = preg_replace('/[^\w %[\].()%&-]+/u','',$antrag);         // Header-Injection absichern, Umlaute erhalten
header('Content-Disposition: attachment; filename="vFE_'.$safe.'_001.xml"; filename*=UTF-8\'\''.rawurlencode("vFE_{$safe}_001.xml"));
echo '<?xml version="1.0" encoding="UTF-8"?>',"\n";
echo '<!-- BDVI-NAS-Generator für Baulasten 0.3, ',date('d.m.Y, H:i:s')," -->\n";
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
              <beginnt><?=gmdate('Y-m-d\TH:i:s\Z')?></beginnt>
            </AA_Lebenszeitintervall>
          </lebenszeitintervall>
          <modellart>
            <AA_Modellart>
              <advStandardModell>DLKM</advStandardModell>
            </AA_Modellart>
          </modellart>
          <position>
<?
$tab=5;  // Einrückungsebene ab <position>
if ($multi=(count($umringe)>1))                           // mehrere Umringe für eine Baulast, Koordinatenreferenzsystem wird vererbt
	xml('gml:MultiSurface'.UTM33.gmlid($id),++$tab);
foreach ($umringe as $umring) {
    if ($multi) xml('gml:surfaceMember',++$tab);          // neuer Umring bei mehreren Umringen 
    xml('gml:Surface'.(!$multi ? UTM33 : '').gmlid($id),++$tab);
    xml('gml:patches',++$tab); xml('gml:PolygonPatch',++$tab);
    xml('gml:exterior',++$tab); xml('gml:Ring',++$tab);
                                                          // Ausgabe Linien- und Bogensegmente
    for ($i = ($count = count($umring))-1;$i >= 0;$i--) { // rückwärts durchlaufen wegen umgekehrter Laufrichtung in GML
		$A = $umring[($i + 1)%$count];                    // Kreisschluss durch Start beim letzten Punkt von hinten mit modulo
		$E = $umring[$i];
        xml('gml:curveMember',++$tab);                    // XML-Vorspann für Bogen oder Gerade gleich 
        xml('gml:Curve gml:id="BDVIBL'.str_pad($id++,4,'0',STR_PAD_LEFT).'"',++$tab);
        xml('gml:segments',++$tab);
        if (isset($E['radius'])) { // ***** Kreisbogen ***** //
            xml('gml:Arc',++$tab);
            xml('gml:posList>'.koo($A).' '.bogenmitte($E,$A).' '.koo($E).'</gml:posList',++$tab); // E→A erhält Krümmungsrichtung
            xml('/gml:Arc',--$tab);
        } else { // ***** Gerade ***** //
            xml('gml:LineStringSegment',++$tab);
            xml('gml:posList>'.koo($A).' '.koo($E).'</gml:posList',++$tab);
            xml('/gml:LineStringSegment',--$tab);
        }
        xml('/gml:segments',--$tab); // wieder für Bogen oder Gerade gleich
        xml('/gml:Curve',--$tab); xml('/gml:curveMember',--$tab);
		--$tab;  // Korrektur wegen gleicher Einrückungsebene und ++$tab bei neuer gml:Curve
    }
    xml('/gml:Ring', $tab);  // Surface schließen
    xml('/gml:exterior',--$tab); xml('/gml:PolygonPatch',--$tab);
    xml('/gml:patches',--$tab); xml('/gml:Surface',--$tab);
    if ($multi) { 
		xml('/gml:surfaceMember',--$tab); // aktuellen Umring schließen bei mehreren Umringen
		--$tab;  // Korrektur wegen gleicher Einrückungsebene und ++$tab bei neuem gml:surfaceMember
	}
}   if ($multi) xml('/gml:MultiSurface',$tab); ?>
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
