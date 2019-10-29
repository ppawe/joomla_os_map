<?php

if(!$tableRightIsOn){
?>
<div class='col-md-7'>
  <table class="<?= $tableClass ?>">
    <?php foreach($tableRight as $key => $wert):
		echo $wert;
     endforeach; ?>
  </table>
</div>
<?php } ?>

<?php if(!$tableBottomIsOn): ?>
  <div class='no-gutters'>
    <div class='col-xs-12'>
      <div class='col-md-5'>
        <table class="<?= $tableClass ?>">
          <?php foreach($tableBottom as $wert):
				echo $wert;
               endforeach; ?>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>