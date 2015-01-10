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
            <div id="response-box"></div>
            <hr>
            <div class="form-group">
                <div class="col-sm-5 col-sm-offset-4">
                    <button type="button" class="btn btn-default">Zurück zur Übersicht</button>
                </div>
            </div>
        </form>

        <script>
            $('#upload').click(function (event) {
                var f = $('#files')[0];
                var responseBox = $('#response-box');
                var button = $('#upload');
                var maxSize = $('#files').data('maxsize');
                var progressBar = $('.upload-progress');
                var projektID = $('#files').data('projektid');

                event.preventDefault();
                button.blur();

                var msg = checkMaxsize(maxSize, f);
                if (msg !== '') {
                    responseBox.show();
                    $('#upload-errors .alert-danger').html('');
                    $('#upload-errors .alert-danger').append(msg);
                    return false;
                } else {
                    responseBox.hide();
                }

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
                        progressBar.width(0);
                        // Fuege Element in Tabelle ein
                        responseBox.append('File importiert!');
                    },
                    error: function (data) {
                        responseBox.append('error');
                    }
                });
            });
        </script>
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
                        <button type="submit" class="btn btn-default">Speichern</button>
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
                <div class="input-group">
                    <input type="text" class="form-control" id="suche-messreihen" data-dynatable-query="suche-messreihen" placeholder="Suche">
                    <span class="input-group-btn">
                        <button class="btn btn-primary datepicker-init" type="button"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button>
                    </span>
                </div>
            </div>
            <div class="col-sm-2">
                <a href="?id=neu" class="btn btn-default" value="neu">Messreihe hinzufügen</a>
            </div>
        </div>
    </div>

    <?php
    $db->get('messreihe', array('projekt_id', '=', $projekt->data()->id));

    $messreihen = $db->results();
    ?>
    <div class="panel panel-default">
        <div class="table-responsive">
            <table class="table controlled-table" id="messreihen-tabelle">
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
    <script>
        $('#messreihen-tabelle')
                .bind('dynatable:init', function (e, dynatable) {
                    dynatable.queries.functions['suche-messreihen'] = function (record, queryValue) {
                        return record.messreihe.toLowerCase().indexOf(queryValue) >= 0;
                    };
                })
                .dynatable({
                    features: {
                        paginate: false,
                        search: false,
                        pushState: false,
                        recordCount: false,
                        perPageSelect: false,
                        sort: false
                    },
                    inputs: {
                        queries: $('#suche-messreihen'),
                        processingText: '',
                        queryEvent: 'keyup'
                    }
                });
    </script>
<?php endif; ?>

<?php
require_once 'footer.php';
