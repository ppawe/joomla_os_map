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
/**
 *  gets the lat and long from the db or the api
 *
 *   @param object $association: one com_contact object (with category 'Vereine')
 *   @return: Array [$lat, $long]
 *   @rtype: Array
 */
        #get the lat and long field from the db
        $customFields = FieldsHelper::getFields('com_contact.contact', $association, true);
        $long = $customFields[0]->value;
        $lat = $customFields[1]->value;

        # if lat/long not saved in db call api to get the lat long
        if (!$long || !$lat) {
                $query = str_replace(" ", "+",
                    "$association->address+$association->postcode+$association->suburb");
                $url = 'https://nominatim.openstreetmap.org/search.php?format=json&q=' . $query;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
                $content = curl_exec($ch);
                $resultStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                var_dump(curl_getinfo($ch, CURLINFO_HEADER_OUT));
                curl_close($ch);

                #if the api cant find the address make a new call (only with postcode and city)
                if ($resultStatus != 429) {
                    if (!$content[0] || !property_exists($content[0], "lon")) {
                        $query = str_replace(" ", "+",
                            "$association->postcode+$association->suburb");
                        $url = 'https://nominatim.openstreetmap.org/search.php?format=json&q=' . $query;
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
                        $content = curl_exec($ch);
                        $resultStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                    }
                    $content = json_decode($content);
                    if (property_exists($content[0], "lon")) {
                        foreach ($content as $place) {
                            $lat = $place->lat;
                            $long = $place->lon;
                            break;
                        }

                        # update the lat long fields (db)
                        if ($long && $lat) {
                            $customFields[0]->value = $long;
                            $customFields[1]->value = $lat;
                            $this->db->updateObject('#__fields_values', $customFields[0], 'field_id', 'item_id');
                            $this->db->updateObject('#__fields_values', $customFields[1], 'field_id', 'item_id');
                        }

                    }
                }
        }
        return [$lat,$long];
    }

    private function _get_contacts()
    {
        # gets all contacts with category 'Vereine'
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
        #gets user id
        $sql = "SELECT name FROM #__users WHERE `id` = $id";
        $this->db->setQuery($sql);
        return $this->db->loadResult();
    }

    public function getAssociationNameTable()
    {
        /**
         *  creates the Chor/Name table
         *
         *   @return: Array ['Head' => [Table Head], '$name' => [Table rows], ...]
         *   @rtype: Array
         */
        #creates table
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
                    "</p><p>$association->email_to</p>"
                ];
            $table_rows[$name] = $str;
        }
        return $table_rows;

    }

    public function getAssociationLocationTable()
    {
        /**
         *  creates the PLZ/Verein table
         *
         *   @return: Array ['Head' => [Table Head], '$name' => [Table rows], ...]
         *   @rtype: Array
         */
        #creates table
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
        /**
         *  creates the text for the popup
         *
         *   @param object $association: one com_contact object (with category 'Vereine')
         *   @return: String
         *   @rtype: String
         */
        #creates popup text
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
        #creates popups
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
                $ret .= "var mpP" . "_" . $treffer['name'] . " = '" . $text . "';\n";
            }
        }

        return $ret;
    }

    //Multi-marker: Code for multiple markers
    private function mpPins()
    {
        #creates pins
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

        // when no pins
        if ($pins == '') return '';

        $exp = explode(';', $pins);
        array_pop($exp);
        // parse pins
        foreach ($exp as $pin) {
            if ($pin != '') {
                if (preg_match('/#(?P<name>\w+)\s*\{\s*\(\s*(?P<coords>\-?\d+\.?\d*\s*\,\s*\-?\d+\.?\d*\s*)\)\s*\,\s*(?:#(?P<skin>\w+))?\s*\,\s*(?:\{\s*#(?P<popup>\w+)\s*\,\s*(?P<show>click|always|immediately)\s*\})?\s*\}/', $pin, $treffer)){
                    $ret .= "var mpK" . "_" . $treffer['name'] . "  = new L.LatLng(" . $treffer['coords'] . ");\n";                // Koordinaten anlegen
                    $cp = '';
                    if ($treffer['skin'] != '') $cp = ", {icon: new mpC" . "_" . $treffer['skin'] . "()}";          // Custom Icon verknüpfen
                    $ret .= "var mpM" . "_" . $treffer['name'] . " = new L.Marker(mpK" . "_" . $treffer['name'] . $cp . ");\n";    // Marker anlegen
                    $ret .= "myMap.addLayer(mpM" . "_" . $treffer['name'] . ");\n";                                       // Marker auf Karte setzen

                    // Popup verknüpfen
                    if ($treffer['popup'] != '') {
                        $ret .= "mpM" . "_" . $treffer['name'] . ".bindPopup(mpP" . "_" . $treffer['popup'] . ");\n";
                        if ($treffer['show'] == 'always' | $treffer['show'] == 'immediately') {
                            $ret .= "mpM" . "_" . $treffer['name'] . ".openPopup();\n";
                        }
                    }
                }
            }
        }
        return $ret;
    }

    public function getJS()
    {
        #creates the js for the map
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
