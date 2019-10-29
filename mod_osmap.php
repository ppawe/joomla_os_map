<?php
/*------------------------------------------------------------------------
# mod_osmod
# ------------------------------------------------------------------------
# author    Martin Kröll
# copyright Copyright (C) 2012-2015 Martin Kröll. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
-------------------------------------------------------------------------*/
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

require_once(dirname(__FILE__).'/helper.php');

// include scripts/styles to the header
$document = JFactory::getDocument();
$document->addStyleSheet("https://unpkg.com/leaflet@1.4.0/dist/leaflet.css");
$document->addScript('https://unpkg.com/leaflet@1.4.0/dist/leaflet.js');
$document->addScript(JURI::root(true) . '/media/mod_osMap/script.js');

// create javascript
$helper = new ModOsmapHelper($params,$module->id);
$js = $helper->getJS();
$tableRightIsOn = $params->get('tableRightOn');
$tableBottomIsOn = $params->get('tableBottomOn');
$tableRight = !$tableRightIsOn ? $helper->formateTable($params->get('Table_right')) : null;
$tableBottom = !$tableBottomIsOn ? $helper->formateTable($params->get('Table_bottom')) : null;
$mapHeight = sprintf("height: %dpx;",$params->get('myHeight') > 0 ? $params->get('myHeight') : 200);
$tableClass = "table table-striped table-hover table-condensed";
$moduleclass_sfx = htmlspecialchars($params->get('moduleClass'));

// Modell einbinden
require(JModuleHelper::getLayoutPath('mod_osmap'));
?>
