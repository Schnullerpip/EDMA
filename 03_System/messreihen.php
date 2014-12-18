<?php
require_once 'header.php';
$db = DB::getInstance();
?>

<?php if (Input::exists('get')) : ?>
    <?php $inp = Input::get('id'); ?>
    <?php if ($inp === 'neu') : ?>
        <div class="row">
            <div class="col-sm-12">
                <h2>Messreihe importieren</h2>
            </div>
        </div>
        <form class="form-horizontal" role="form" method="post" action="parser.php" nctype="multipart/form-data">
            <div class="form-group">
                <label for="datei" class="col-sm-4 control-label">Datei auswählen</label>
                <div class="col-sm-5">
                    <input class="control-label" name="datei" id="datei" type="file" data-maxsize="<?php echo Utils::convertBytes(ini_get('post_max_size')); ?>">
                </div>
            </div>
            <input type="hidden" name="token" value="<?php echo Token::generate(); ?>">
            <div class="form-group">
                <div class="col-sm-offset-4 col-sm-5">
                    <button type="submit" class="btn btn-primary">Import</button>
                    <a href="messreihen.php" class="btn btn-link">Abbrechen</a>
                </div>
            </div>
        </form>
    <?php elseif (is_numeric($inp)) : ?>
        <div class="row">
            <div class="col-sm-12">
                <h2>Messreihe bearbeiten</h2>
            </div>
        </div>
        <?php
        $messreihe = $db->get('messreihe', array('id', '=', $inp));
        if (!$messreihe->error()) :
            ?>
            <div class="row">
                <div class="col-sm-12">
                    <h3>Metadaten</h3>
                </div>
            </div>
            <form class="form-horizontal" role="form" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name" class="col-sm-4 control-label">Name<sup>*</sup></label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" name="name" id="name" placeholder="Name" value="<?php echo ($messreiheName = $messreihe->first()->messreihenname) ? $messreiheName : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="datum" class="col-sm-4 control-label">Datum<sup>*</sup></label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" name="datum" id="datum" placeholder="Datum">
                    </div>
                </div>
                <input type="hidden" name="token" value="<?php echo Token::generate(); ?>">
                <div class="form-group">
                    <div class="col-sm-offset-4 col-sm-5">
                        <button type="submit" class="btn btn-primary">Speichern</button>
                        <a href="messreihen.php" class="btn btn-link">Abbrechen</a>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    <?php endif; ?>
<?php else : ?>
    <div class="row">
        <div class="col-sm-12">
            <h2>Messreihen</h2>
        </div>
    </div>
    <div class="row mb-30">
        <div class="form-group">
            <div class="col-sm-2 col-sm-offset-8">
                <input type="text" class="form-control" id="beispielFeld1" placeholder="Suche">
            </div>
            <div class="col-sm-2">
                <a href="?id=neu" class="btn btn-primary" value="neu">Messreihe hinzufügen</a>
            </div>
        </div>
    </div>

    <?php
    $db->get('messreihe', array('projekt_id', '=', $projekt->data()->id));

    $messreihen = $db->results();
    ?>
    <div class="panel panel-default">
        <div class="table-responsive">
            <table class="table" id="projektbeschreibungen" data-count="3">
                <thead>
                    <tr>
                        <th>Messreihe</th>
                        <th>
                            <span class="pull-right">Datum</span>
                        </th>
                        <th class="table-controls"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messreihen as $messreihe) : ?>
                        <tr>
                            <td><?php echo escape($messreihe->messreihenname); ?></td>
                            <td>
                                <span class="pull-right"><?php echo date("d.m.Y", strtotime(escape($messreihe->datum))); ?></span>
                            </td>
                            <td class="controls-wrapper">
                                <div class="pull-right controls">
                                    <ul class="list-unstyled pull-right mb-0">
                                        <li>
                                            <a href="messreihen.php?id=<?php echo escape($messreihe->id); ?>" title="Messreihe &quot;<?php echo escape($messreihe->messreihenname); ?>&quot; bearbeiten">
                                                <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#" title="Messreihe &quot;<?php echo escape($messreihe->messreihenname); ?>&quot; löschen">
                                                <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php
require_once 'footer.php';
