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

$document = JFactory::getDocument();
$document->addStyleSheet("https://unpkg.com/leaflet@1.4.0/dist/leaflet.css");
$document->addStyleSheet(JURI::root(true) . '/media/mod_osMap/dataTables.min.css');

$document->addScript('https://unpkg.com/leaflet@1.4.0/dist/leaflet.js');
$document->addScript('https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js');
$document->addScript('https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap.min.js');
$document->addScript(JURI::root(true) . '/media/mod_osMap/script.js');

// create javascript
$helper = new ModOsmapHelper($module->id);
$js = $helper->getJS();
$tableBottom = !$params->get('tableBottomOn') ? $helper->getAssociationLocationTable() : null;
$tableRight = !$params->get('tableRightOn') ? $helper->getAssociationNameTable() : null;
$mapHeight = sprintf("height: %dpx;",$params->get('myHeight') > 0 ? $params->get('myHeight') : 200);
$tableClass = "table table-striped table-hover table-condensed";
$moduleclass_sfx = htmlspecialchars($params->get('moduleClass'));

// Modell einbinden
require(JModuleHelper::getLayoutPath('mod_osmap'));
?>
