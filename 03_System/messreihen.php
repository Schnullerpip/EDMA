<?php
require_once 'preHeader.php';

$db = DB::getInstance();

// Wurden Daten im bearbeiten Formular geändert?
if (Input::exists('post')) {
    if (Token::check(Input::get('token'))) {
        $errors = array();
        // Name geändert
        if ($name = Input::get('name')) {

            // Name schon vorhanden?
            $db->get('messreihe', array('messreihenname', '=', $name));
            $results = $db->results();

            if (sizeof($results) >= 1) {
                array_push($errors, 'Die Messreihe konnte nicht umbenannt werden! Der Messreihenname ist schon vorhanden!<br>');
            } else {
                if (!$db->update('messreihe', Input::get('messreihenid'), array('messreihenname' => $name))) {
                    array_push($errors, 'Die Messreihe konnte nicht umbenannt werden! Bitte versuchen Sie es erneut!<br>');
                }
            }
        }
        // Datum geändert
        if ($datum = Input::get('datum')) {
            $datum = Utils::convertDate($datum);
            if (!$db->update('messreihe', Input::get('messreihenid'), array('datum' => $datum))) {
                array_push($errors, 'Das Datum der Messreihe konnte nicht ge&auml;ndert werden! Bitte versuchen Sie es erneut!<br>');
            }
        }
        // Anzeigenamen bearbeitet
        if ($sensoren = Input::get('sensoren')) {
            foreach ($sensoren as $key => $sensorname) {
                if (!empty($sensorname)) {
                    $db->query("UPDATE messreihe_sensor SET anzeigename = ? WHERE messreihe_sensor.messreihe_id = ? AND messreihe_sensor.sensor_id = ?", array($sensorname, Input::get('messreihenid'), $key));
                    if ($db->error()) {
                        array_push($errors, 'Der Anzeoigename des Sensors "' . $key . '" konnte nicht ge&auml;ndert werden! Bitte versuchen Sie es erneut!<br>');
                    }
                }
            }
        }
        // Metadaten geändert
        if ($metadaten = Input::get('metaeintrag')) {
            foreach ($metadaten as $key => $metaeintrag) {
                if (!empty($metaeintrag)) {
                    $db->query("UPDATE messreihe_metainfo SET metawert = ? WHERE messreihe_metainfo.messreihe_id = ? AND messreihe_metainfo.metainfo_id = ?", array($metaeintrag, Input::get('messreihenid'), $key));
                    if ($db->error()) {
                        array_push($errors, 'Der Wert des Metafeldes "' . $key . '" konnte nicht ge&auml;ndert werden! Bitte versuchen Sie es erneut!<br>');
                    }
                }
            }
        }

        if (sizeof($errors) > 0) {
            $errorString = '';
            foreach ($errors as $error) {
                $errorString .= "<br>" . $error;
            }

            Session::flash('error', $errorString);
            Redirect::to('messreihen.php');
        }
    }
}

require_once 'header.php';
?>

<?php if (Input::exists('get')) : ?>
    <?php $inp = Input::get('id'); ?>
    <?php if ($inp === 'neu') : ?>
        <div class="row">
            <div class="col-sm-12">
                <h2 class="heading">Messreihe importieren</h2>
            </div>
        </div>
        <form class="form-horizontal" role="form" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="datei" class="col-sm-4 control-label">Datei auswählen</label>
                <div class="col-sm-5">
                    <input class="control-label" name="file" id="files" type="file" data-maxsize="<?php echo Utils::convertBytes(ini_get('post_max_size')); ?>" data-projektid="<?php echo $projekt->data()->id ?>">
                </div>
                <div class="col-sm-5 col-sm-offset-4">
                    <small>Max: <?php echo ini_get('post_max_size'); ?></small>
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-offset-4 col-sm-5">
                    <button type="button" id="upload" class="btn btn-default">Importieren</button>
                </div>
            </div>
            <div class="upload-progress"></div>

            <!-- Spinner waehrend Datei importiert wird -->
            <div class="loading-div" style="display: none">
                <div class="loading-spinner"></div>                
                <h4 class="text-center">Datei wird importiert, bitte warten...</h4>
            </div>

            <hr>
            <div class="form-group">
                <div class="col-sm-5 col-sm-offset-4">
                    <a href="messreihen" type="button" class="btn btn-default">Zurück zur Übersicht</a>
                </div>
            </div>
        </form>

        <script>
            $('#upload').click(function (event) {
                $('.loading-div, #upload').toggle();
                var ansehenButton = $('#infoModal').find('.modal-footer a');
                var f = $('#files')[0];
                var button = $('#upload');
                var maxSize = $('#files').data('maxsize');
                var progressBar = $('.upload-progress');
                var projektID = $('#files').data('projektid');

                ansehenButton.hide();
                event.preventDefault();
                button.blur();

                app.uploader({
                    files: f,
                    function: 'upload',
                    element: {
                        name: 'messreihe'
                    },
                    progress: progressBar,
                    maxsize: maxSize,
                    processor: 'ajaxHandler.php',
                    projektID: projektID,
                    finished: function (data) {
                        var new_href = "messreihen.php?id=" + data[0].messreiheID;
                        delete data[0].messreiheID;
                        var succMsg = convertArray(data);
                        progressBar.width(0);
                        modalTextSuccess(succMsg);
                        ansehenButton.attr("href", new_href).show();
                        $('#infoModal').modal();
                        $('.loading-div, #upload').toggle();
                    },
                    warning: function (data) {
                        var warnMsg = convertArray(data);
                        modalTextWarning(warnMsg);
                        $('#infoModal').modal();
                    },
                    error: function (data) {
                        var errorMsg = convertArray(data);
                        modalTextError(errorMsg);
                        $('#infoModal').modal();
                        $('.loading-div, #upload').toggle();
                    }
                });
            });
        </script>
    <?php elseif (is_numeric($inp)) : ?>
        <?php
        $messreihe = $db->get('messreihe', array('id', '=', $inp));

        if (!$messreihe->error()) :
            ?>
            <div class="row">
                <div class="col-sm-12">
                    <h2 class="heading">Messreihe "<?php echo escape($messreihe->first()->messreihenname) ?>" bearbeiten</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <h3>Metadaten</h3>
                </div>
            </div>
            <form class="form-horizontal" role="form" action="messreihen.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name" class="col-sm-4 control-label">Name<sup>*</sup></label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" name="name" id="name" placeholder="<?php echo ($messreiheName = $messreihe->first()->messreihenname) ? escape($messreiheName) : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="datum" class="col-sm-4 control-label">Datum<sup>*</sup></label>
                    <div class="col-sm-5">
                        <div class="input-group date">
                            <input type="text" class="form-control" name="datum" id="datum" placeholder="<?php echo escape($datum = $messreihe->first()->datum) ? escape(Utils::convertDate($datum)) : ''; ?>">
                            <span class="btn btn-primary input-group-addon"><i class="glyphicon glyphicon-calendar" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </div>
                <?php
                // Hole alle anderen Metadaten
                $sql = "SELECT metainfo.metaname, metainfo.id, messreihe_metainfo.metawert FROM metainfo INNER JOIN messreihe_metainfo ON metainfo.id = messreihe_metainfo.metainfo_id WHERE messreihe_metainfo.messreihe_id = ?";
                $db->query($sql, array($messreihe->first()->id));
                $metadaten = $db->results();
                ?>

                <?php if (!empty($metadaten)) : ?>
                    <?php foreach ($metadaten as $metaeintrag) : ?>
                        <div class="form-group">
                            <label for="metaeintrag[<?php echo escape($metaeintrag->id); ?>]" class="col-sm-4 control-label"><?php echo escape($metaeintrag->metaname); ?></label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" name="metaeintrag[<?php echo escape($metaeintrag->id); ?>]" id="metaeintrag[<?php echo escape($metaeintrag->id); ?>]" placeholder="<?php echo escape($metaeintrag->metawert) ?>" >
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif ?>

                <div class="row">
                    <div class="col-sm-12">
                        <h4>Anzeigenamen</h4>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-4 text-right">
                        Sensorname
                    </div>
                    <div class="col-sm-5">
                        Anzeigename
                    </div>
                </div>
                <?php
                $db->query('SELECT sensor.sensorname, sensor.id, messreihe_sensor.anzeigename FROM messreihe_sensor INNER JOIN sensor on messreihe_sensor.sensor_id = sensor.id WHERE messreihe_sensor.messreihe_id = ?', array($inp));
                $sensoren = $db->results();
                ?>
                <?php foreach ($sensoren as $sensor) : ?>
                    <div class="form-group">
                        <label for="sensoren[<?php echo $sensor->id; ?>]" class="col-sm-4 control-label"><?php echo escape($sensor->sensorname); ?></label>
                        <div class="col-sm-5">
                            <input type="text" class="form-control" name="sensoren[<?php echo $sensor->id; ?>]" id="sensoren[<?php echo $sensor->id; ?>]" placeholder="<?php echo $sensor->anzeigename; ?>" >
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="form-group">
                    <div class="col-sm-offset-4 col-sm-5">
                        <button data-toggle="modal" data-target="#delete-modal" class="btn btn-link no-padding" 
                                data-element="messreihe" data-redirect="messreihen" type="button"
                                data-id="<?php echo escape($inp); ?>"
                                title="Diese Messreihe l&ouml;schen">
                            Diese Messreihe löschen
                        </button>
                    </div>
                </div>

                <br>
                <input type="hidden" name="messreihenid" value="<?php echo $inp; ?>">
                <input type="hidden" name="token" value="<?php echo Token::generate(); ?>">
                <div class="form-group">
                    <div class="col-sm-offset-4 col-sm-5">
                        <button type="submit" class="btn btn-default">Speichern</button>
                        <a href="messreihen" class="btn btn-link">Abbrechen</a>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <p>Fehler beim Holen der Messreihendaten!</p>
        <?php endif; ?>
    <?php endif; ?>
<?php else : ?>
    <div class="row">
        <div class="col-sm-12">
            <h2 class="heading">Messreihen</h2>
        </div>
    </div>
    <div class="row mb-30">
        <div class="form-group">
            <div class="col-sm-2 col-sm-offset-6">
                <input type="text" class="form-control" id="suche-messreihen-name" data-dynatable-query-event="input" data-dynatable-query="suche-messreihen-name" placeholder="Messreihen suchen">
            </div>
            <div class="col-sm-2">
                <div class="input-group date">
                    <input type="text" class="form-control" name="datum" id="suche-messreihen-datum" data-dynatable-query="suche-messreihen-datum" placeholder="Datum">
                    <span class="btn btn-primary input-group-addon"><i class="glyphicon glyphicon-calendar" aria-hidden="true"></i></span>
                </div>
            </div>
            <div class="col-sm-2">
                <a href="?id=neu" class="btn btn-default" value="neu">Messreihe importieren</a>
            </div>
        </div>
    </div>

    <?php
    $db->get('messreihe', array('projekt_id', '=', $projekt->data()->id));
    $messreihen = $db->results();
    ?>
    <?php if (sizeof($messreihen) !== 0) : ?>
        <div class="panel panel-default">
            <div class="table-responsive">
                <table class="table controlled-table" id="messreihen-tabelle">
                    <thead>
                        <tr>
                            <th data-dynatable-column="name">Messreihe</th>
                            <th data-dynatable-column="datum" class="text-right">Datum</th>
                            <th class="table-controls"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messreihen as $messreihe) : ?>
                            <tr>
                                <td><?php echo escape($messreihe->messreihenname); ?></td>
                                <td class="text-right"><?php echo escape(Utils::convertDate($messreihe->datum)); ?></td>
                                <td class="controls-wrapper">
                                    <div class="pull-right controls">
                                        <ul class="list-unstyled pull-right mb-0">
                                            <li>
                                                <a href="messreihen.php?id=<?php echo escape($messreihe->id); ?>" title="Messreihe &quot;<?php echo escape($messreihe->messreihenname); ?>&quot; bearbeiten">
                                                    <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                                                </a>
                                            </li>
                                            <li>
                                                <button data-toggle="modal" data-target="#delete-modal" class="btn btn-link" 
                                                        data-element="messreihe" data-redirect="" type="button"
                                                        data-id="<?php echo escape($messreihe->id); ?>"
                                                        title="Messreihe &quot;<?php echo escape($messreihe->messreihenname); ?>&quot; l&ouml;schen">
                                                    <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
                                                </button>
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
    <?php else : ?>
        <br>
        <p class="text-center">Bisher wurden noch keine Messreihen importiert. Um eine Messreihe zu importieren, klicken Sie bitte auf "Messreihe hinzufügen".</p>
    <?php endif; ?>
<?php endif; ?>

<?php
require_once 'footer.php';
