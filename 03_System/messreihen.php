<?php
require_once 'header.php';
?>

<?php
$db = DB::getInstance();
$db->get('messreihe', array('projekt_id', '=', $projekt->data()->id));

$messreihen = $db->results();
?>

<h2>Messreihen bearbeiten</h2>
<div class="panel panel-default">
    <div class="table-responsive">
        <table class="table" id="projektbeschreibungen" data-count="3">
            <thead>
                <tr>
                    <th>Messreihe</th>
                    <th>Datum</th>
                    <th>Verwaltung</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messreihen as $messreihe) : ?>
                    <tr>
                        <td><?php echo escape($messreihe->messreihenname); ?></td>
                        <td><?php echo escape($messreihe->datum); ?></td>
                        <td>Test</td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td>Testreihe</td>
                    <td>Date</td>
                    <td>Test</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>


<?php
require_once 'footer.php';
