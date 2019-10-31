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
defined('_JEXEC') or die('Restricted access');

// JTableCategory is autoloaded in J! 3.0, so...
if (version_compare(JVERSION, '3.0', 'lt')) {
    JTable::addIncludePath(JPATH_PLATFORM . 'joomla/database/table');
}
JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');

class ModOsmapHelper
{
    private $popups;
    private $pins;
    private $params;
    private $id;
    private $category_id;
    private $db;
    private $associations = array();

    public function __construct($id)
    {
        $this->id = "map" . $id;
        $this->db =& JFactory::getDBO();
        $this->category_id = $this->_get_category();
        $this->associations = $this->_get_contacts();
        if ($this->associations) {
            $this->popups = $this->mpPopups();
            $this->pins = $this->mpPins();
        }
    }

    private function _get_category()
    {

        $sql = "select id from #__categories WHERE `alias` = 'vereine' AND `extension` = 'com_contact'";
        $this->db->setQuery($sql);
        $category = $this->db->loadResult();

        // Initialize a new category
        if (!$category) {
            // Set the extension. For content categories, use 'com_content'
            $extension = 'com_contact';

            // Set the title for the category
            $title = 'Vereine';

            // Type the description, this is also the 'body'. HTML allowed here.
            $desc = 'Verein';

            // Set the parent category. 1 is the root item.
            $parent_id = 1;

            $category = JTable::getInstance('Category');
            $category->extension = $extension;
            $category->title = $title;
            $category->description = $desc;
            $category->published = 1;
            $category->access = 1;
            $category->params = '{"target":"","image":""}';
            $category->metadata = '{}';
            $category->language = '*';
            // Set the location in the tree
            $category->setLocation($parent_id, 'last-child');

            // Check to make sure our data is valid
            if (!$category->check()) {
                JError::raiseNotice(500, $category->getError());
                return false;
            }

            // Now store the category
            if (!$category->store(true)) {
                JError::raiseNotice(500, $category->getError());
                return false;
            }

            // Build the path for our category
            $category->rebuildPath($category->id);
            $category = $category->id;
        }

        return $category;
    }

    private function getGeo($association){
        $long = "";
        $lat = "";
        $id = $association->id;
        $sql = "select * from #__fields_values WHERE `item_id` = $id";
        $this->db->setQuery($sql);
        $r = $this->db->loadObjectList();
        foreach ($r as $field){
            if ($field->field_id == "1") $long = $field->value;
            if ($field->field_id == "2") $lat = $field->value;
        }

        if (!$long || !$lat) {
                $query = str_replace(" ", "+",
                    "$association->address+$association->postcode+$association->suburb");
                $url = 'https://nominatim.openstreetmap.org/search.php?format=json&q=' . $query;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
                $content = curl_exec($ch);
                curl_close($ch);
                $content = json_decode($content);
                if ($content) {
                    foreach ($content as $place) {
                        $lat = $place->lat;
                        $long = $place->lon;
                        print $lat."<br>".$long."<br>-------------<br>";
                        break;
                    }

                    if ($long && $lat) {
                        $query = $this->db->getQuery(true);
                        $fieldsLat = array(
                            $this->db->quoteName('value') . ' = ' . $lat,
                        );
                        $fieldsLong = array(
                            $this->db->quoteName('value') . ' = ' . $long,
                        );
                        $conditionsLat = array(
                            $this->db->quoteName('item_id') . ' = ' . $id,
                            $this->db->quoteName('field_id') . ' = ' . '2'
                        );
                        $conditionsLong = array(
                            $this->db->quoteName('item_id') . ' = ' . $id,
                            $this->db->quoteName('field_id') . ' = ' . '1'
                        );

                        $query->update($this->db->quoteName('#__fields_values'))->set($fieldsLat)->where($conditionsLat);

                        $this->db->setQuery($query);

                        $result = $this->db->execute();

                        $query = $this->db->getQuery(true);

                        $query->update($this->db->quoteName('#__fields_values'))->set($fieldsLong)->where($conditionsLong);

                        $this->db->setQuery($query);

                        $result = $this->db->execute();
                    }
                }
                else{
                    var_dump($content);
                }
        }
        return [$lat,$long];
    }

    private function _get_contacts()
    {
        # gets all
        if (!$this->category_id) return null;

        $sql = "SELECT * FROM #__contact_details WHERE `catid` = $this->category_id";
        $this->db->setQuery($sql);
        return $this->db->loadObjectList();
    }

    private function convertName($name){
        $search = array("Ä", "Ö", "Ü", "ä", "ö", "ü", "ß", "´"," ",".","-","+","/",",",";");
        $replace = array("Ae", "Oe", "Ue", "ae", "oe", "ue", "ss", "","","","","","","","");
        return str_replace($search,$replace,$name);
    }

    private function getUser($id){
        $sql = "SELECT name FROM #__users WHERE `id` = $id";
        $this->db->setQuery($sql);
        return $this->db->loadResult();
    }

    public function getAssociationNameTable()
    {
        $table_rows = [];
        $table_rows['Head'] = ["Chor", "Name"];
        $odd = true;
        foreach ($this->associations as $association){
            $r = $odd?"odd":"even";
            $name = "mpM_". $this->convertName($association->name);
            $user = $this->getUser($association->user_id);
            $str = [
                    "<p>$association->name</p>",

                    "<button class='verein-finden-button' onclick='goToPopup(" . $name . ")'>".
                    "$user<i class='fas fa-search'></i><i class='fas fa-circle'></i></button>".
                    "</p><p>$association->email</p>"
                ];
            $table_rows[$name] = $str;
        }
        return $table_rows;

    }

    public function getAssociationLocationTable()
    {
        $table_rows = [];
        $odd = true;
        if (!$this->associations) return "";
        foreach ($this->associations as $association){
            $r = $odd?"odd":"even";
            $name = "mpM_". $this->convertName($association->name);
            $str =[
                    "<p>$association->postcode $association->suburb</p>",
                    "<p>".
                    "<button class='verein-finden-button' onclick='goToPopup(" . $name . ")'>".
                    "$association->name<i class='fas fa-search'></i><i class='fas fa-circle'></i></button>".
                    "</p>"
                ];
            $table_rows[$name] = $str;
        }
        $table_rows['Head'] = ["PLZ", "Verein"];
        return $table_rows;

    }

    private function createDescription($association){
        if (!$association) return "";
        $desc = ["<h4>$association->name</h4>"];
        if ($association->misc) array_push($desc,"$association->misc</br>");
        if ($association->postcode || $association->suburb) array_push($desc,
            "$association->postcode $association->suburb</br>");
        if ($association->address) array_push($desc, "$association->address</br>");
        if ($association->user_id) array_push($desc, $this->getUser($association->user_id)."</br>");
        if ($association->email_to) array_push($desc, "$association->email_to</br>");
        if ($association->webpage) array_push($desc,
            "<a href='$association->webpage' target='_blank'>Zur Webseite</a>");
        return implode("",$desc);
    }

    private function mpPopups()
    {
        $ret = "";
        $pop = "";
        if (!$this->associations) return "";
        foreach ($this->associations as $association){
            $name = $this->convertName($association->name)."Pop";
            $desc = $this->createDescription($association);
            $pop .= "#".$name."{".$desc."};";
        }

        $exp = explode('};', $pop);
        array_pop($exp);
        // Popups parsen
        foreach ($exp as $p) {
            if ($p != '') {
                preg_match('/#(?P<name>\w+)\s*\{\s*(?P<text>.*)\s*\}/', $p . '}', $treffer);
                $text = str_replace("'", "\\'", str_replace(array("\r\n", "\n", "\r"), "", $treffer['text']));
                $ret .= "var mpP" . $id . "_" . $treffer['name'] . " = '" . $text . "';\n";
            }
        }

        // Code zurückgeben
        return $ret;
    }

    //Multi-marker: Code for multiple markers
    private function mpPins()
    {
        $ret = "";
        $pins = "";
        foreach ($this->associations as $association){
            $name = $this->convertName($association->name);
            $name_pop = $name."Pop";
            $cords = $this->getGeo($association);
            $long = $cords[1];
            $lat = $cords[0];

            if (!$cords) continue;
            $pins .= "#$name{($lat,$long),,{#$name_pop,click}};\n";
        }

        // Wenn keine Pins gegben sind, abbrechen
        if ($pins == '') return '';

        // Einzelne Einträge trennen
        $exp = explode(';', $pins);
        array_pop($exp);
        // Pins parsen
        foreach ($exp as $pin) {
            if ($pin != '') {
                preg_match('/#(?P<name>\w+)\s*\{\s*\(\s*(?P<coords>\-?\d+\.?\d*\s*\,\s*\-?\d+\.?\d*\s*)\)\s*\,\s*(?:#(?P<skin>\w+))?\s*\,\s*(?:\{\s*#(?P<popup>\w+)\s*\,\s*(?P<show>click|always|immediately)\s*\})?\s*\}/', $pin, $treffer);

                $ret .= "var mpK" . $id . "_" . $treffer['name'] . "  = new L.LatLng(" . $treffer['coords'] . ");\n";                // Koordinaten anlegen
                $cp = '';
                if ($treffer['skin'] != '') $cp = ", {icon: new mpC" . $id . "_" . $treffer['skin'] . "()}";          // Custom Icon verknüpfen
                $ret .= "var mpM" . $id . "_" . $treffer['name'] . " = new L.Marker(mpK" . $id . "_" . $treffer['name'] . $cp . ");\n";    // Marker anlegen
                $ret .= "myMap.addLayer(mpM" . $id . "_" . $treffer['name'] . ");\n";                                       // Marker auf Karte setzen

                // Popup verknüpfen
                if ($treffer['popup'] != '') {
                    $ret .= "mpM" . $id . "_" . $treffer['name'] . ".bindPopup(mpP" . $id . "_" . $treffer['popup'] . ");\n";
                    if ($treffer['show'] == 'always' | $treffer['show'] == 'immediately') {
                        $ret .= "mpM" . $id . "_" . $treffer['name'] . ".openPopup();\n";
                    }
                }
            }
        }
        // Code zurück geben
        return $ret;
    }

    public function getJS()
    {
        $js = "";
        $js .= "var myMapId = document.getElementById('$this->id');" . "\n";
        $js .= 'var myMap = L.map(myMapId).setView([48.8750498,9.6346291],12);' . "\n";
        $js .= "L.tileLayer('https://{s}.tile.openstreetmap.de/tiles/osmde/{z}/{x}/{y}.png', { \n";
        $js .= "attribution: " . "\"<a href='http://www.openstreetmap.de/'>Openstreetmap.de</a> (<a href='https://creativecommons.org/licenses/by-sa/3.0/'>CC-BY-SA</a>)\"" . ",\n";
        $js .= 'maxZoom: 18,' . "\n";
        $js .= '}).addTo(myMap);' . "\n";


        $js .= "// additional Pins\n";
        $js .= $this->popups; // Create Popup contents
        $js .= $this->pins; // Create pins and add Popups

        return $js;
    }
}
