<?php
/*------------------------------------------------------------------------
# mod_osmod
# ------------------------------------------------------------------------
# author    Martin Kröll
# copyright Copyright (C) 2012-2018 Martin Kröll. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
--------------------------------------------------------------------------
*/
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class ModOsmapHelper{

  private $popups ;
  private $pins;
  private $params;
  private $id;

  public function __construct($params,$id){
    $this->id = "map".$id;
    $this->params = $params;
    $this->popups = $this->mpPopups($this->params->get('popups',''));
    $this->pins = $this->mpPins($this->params->get('pins', ''));
  }

  public function formateTable($table){

    $exp = explode("</tr>",strip_tags($table,"<tr><a><td><p>"));

    return $this->connect($exp);

  }

  private function connect ($html){
    $out = [];
    $chorOrPlz = 1 ;
    $expPop = explode(";",$this->popups);
    foreach ($html as $key => $row):
      $match = false;
  	  $matchString = explode("</td>",strip_tags($row,"<td>"));
      $matchString = trim(explode("\n",trim(strip_tags($matchString[$chorOrPlz])))[0]);

      foreach ($expPop as $popKey => $popup):
        if (strpos($popup,$matchString) !== false && $matchString != ["Name","Plz"]):
          $match = true;
          $out[$this->getPopupVarName($popup)] = str_replace("$matchString","<button class='verein-finden-button' onclick='goToPopup(".$this->getPopupVarName($popup).")'>$matchString<i class='fas fa-search'></i><i class='fas fa-circle'></i></button>",$row);
        endif;
      endforeach;

      if (!$match && trim($row) != "")	$out[$key] = "<tr class='$matchString'>".strip_tags($row,"<td><a><p>")."</tr>";

    endforeach;
    return $out;
  }

  private function getPopupVarName($popup){
	$temp = explode(" ",$popup)[1];	
    $temp = substr($temp,0,-3);
  return str_replace("mpP","mpM",trim($temp));
  }

  private function mpPopups($parPopups){
       $ret = "";
       $popups = array();

       // Wenn keine Popups gegben sind, abbrechen
       if($parPopups == '') return '';

       // Einzelne Einträge trennen
       $exp = explode('};', $parPopups);

       // Popups parsen
       foreach($exp as $p){
           if($p != ''){
               preg_match('/#(?P<name>\w+)\s*\{\s*(?P<text>.*)\s*\}/', $p.'}', $treffer);

               $text = str_replace("'", "\\'", str_replace( array("\r\n", "\n", "\r") , "" , $treffer['text']));
               $ret .= "var mpP".$id."_".$treffer['name']." = '".$text."';\n";
           }
       }
       // Code zurückgeben
       return $ret;
   }

   //Multi-marker: Code for multiple markers
   private function mpPins($parPins){
       $ret = "";
       $markers = array();

       // Wenn keine Pins gegben sind, abbrechen
       if($parPins == '') return '';

       // Einzelne Einträge trennen
       $exp = explode(';', $parPins);

       // Pins parsen
       foreach($exp as $pin){
           if($pin != ''){
               preg_match('/#(?P<name>\w+)\s*\{\s*\(\s*(?P<coords>\-?\d+\.?\d*\s*\,\s*\-?\d+\.?\d*\s*)\)\s*\,\s*(?:#(?P<skin>\w+))?\s*\,\s*(?:\{\s*#(?P<popup>\w+)\s*\,\s*(?P<show>click|always|immediately)\s*\})?\s*\}/', $pin, $treffer);

               $ret .= "var mpK".$id."_".$treffer['name']."  = new L.LatLng(".$treffer['coords'].");\n";                // Koordinaten anlegen
               $cp   = ''; if($treffer['skin'] != '') $cp = ", {icon: new mpC".$id."_".$treffer['skin']."()}";          // Custom Icon verknüpfen
               $ret .= "var mpM".$id."_".$treffer['name']." = new L.Marker(mpK".$id."_".$treffer['name'].$cp.");\n";    // Marker anlegen
               $ret .= "myMap.addLayer(mpM".$id."_".$treffer['name'].");\n";                                       // Marker auf Karte setzen

               // Popup verknüpfen
               if($treffer['popup'] != ''){
                   $ret .= "mpM".$id."_".$treffer['name'].".bindPopup(mpP".$id."_".$treffer['popup'].");\n";
                   if($treffer['show'] == 'always' | $treffer['show'] == 'immediately') {
                       $ret .= "mpM".$id."_".$treffer['name'].".openPopup();\n";
                   }
               }
           }
       }
       // Code zurück geben
       return $ret;
   }

  public function getJS(){
    $js = "";
    $js .=  "var myMapId = document.getElementById('$this->id');"."\n";
    $js .= 'var myMap = L.map(myMapId).setView([48.8750498,9.6346291],12);'."\n";
    $js .= "L.tileLayer('https://{s}.tile.openstreetmap.de/tiles/osmde/{z}/{x}/{y}.png', { \n";
    $js .= "attribution: "."\"<a href='http://www.openstreetmap.de/'>Openstreetmap.de</a> (<a href='https://creativecommons.org/licenses/by-sa/3.0/'>CC-BY-SA</a>)\"".",\n";
    $js .= 'maxZoom: 18,'."\n";
    $js .= '}).addTo(myMap);'."\n";


    $js .= "// additional Pins\n";
    $js .= $this->popups; // Create Popup contents
    $js .= $this->pins; // Create pins and add Popups

    return $js;
  }
}
