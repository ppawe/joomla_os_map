<?php
/*------------------------------------------------------------------------
# mod_osmod
# ------------------------------------------------------------------------
# author    Martin Kröll
# copyright Copyright (C) 2012-2015 Martin Kröll. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
-------------------------------------------------------------------------*/
// no direct access
defined('_JEXEC') or die('Resricted Access');
?>
<div class="custom custom-table no-gutters">
<div class="osmap <?= $moduleclass_sfx ?>" id="map<?= $module->id; ?>" style="<?= $mapHeight ?>"></div>
<script type="text/javascript"><?= $js; ?></script>
<?php 
require 'table.tpl.php';
?>
</div>
