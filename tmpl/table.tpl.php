<?php

if($tableRight):
    ?>
    <div class='col-md-7 col-sm-12 tableRight'>
        <table id="tRight" class="<?= $tableClass ?>">
            <thead>
            <tr>
                <?php foreach($tableRight["Head"] as $head): ?>
                    <th><?= $head ?></th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach($tableRight as $key => $row):
                if ($key != "Head"):?>
                    <tr>
                        <?php foreach ($row as $td): ?>
                            <td><?= $td ?></td>
                        <?php endforeach;?>
                    </tr>
                <?php endif;?>
            <?php endforeach;?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if($tableBottom): ?>
    <div class='col-xs-12 col-md-5 tableBot'>
        <table id="tBot" class="<?= $tableClass ?>">
            <thead>
            <tr>
                <?php foreach($tableBottom["Head"] as $head): ?>
                    <th><?= $head ?></th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach($tableBottom as $key => $row):
                if ($key != "Head"):?>
                    <tr>
                        <?php foreach ($row as $td): ?>
                            <td><?= $td ?></td>
                        <?php endforeach;?>
                    </tr>
                <?php endif;?>
            <?php endforeach;?>
            </tbody>
        </table>
    </div>
    </div>
<?php endif; ?>
