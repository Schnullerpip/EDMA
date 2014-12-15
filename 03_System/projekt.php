<?php
require_once 'header.php';

if (!$projekt->isMaster()) {
    Redirect::to('login.php');
}

if (Input::exists()) {
    if (Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'projektname' => array(
                'fieldname' => 'Projektname',
                'required' => true,
                'min' => 3,
                'max' => 20,
                'unique' => true
            )
        ));

        if ($validation->passed()) {
            $salt = Hash::salt(32);

            if ($projekt->data()->id > 0) {
                // Projekt updaten
            } else {
                // Neues Projekt
                try {
                    /*
                      $projekt->create(array(
                      'name' => Input::get('projektname'),
                      'passwort' => Hash::make(Input::get('passwort'), $salt),
                      'salt' => $salt,
                      'erstellt' => date('Y-m-d H:i:s'),
                      ));
                     */

                    // Session::flash('message', 'Projekt erfolgreich angelegt!');
                    // Redirect::to('index.php');
                } catch (Exception $ex) {
                    die($ex->getMessage());
                }
            }
        } else {
            $message = "";
            foreach ($validation->errors() as $error) {
                $message .= $error . '<br>';
            }
            if (!Session::exists('error')) {
                // Session::flash('error', $message);
                // Redirect::to('projekt.php');
            }
        }
    }
}
?>

<h2><?php echo ($projekt->data() ? $projekt->data()->projektname . ' bearbeiten' : 'Neues Projekt anlegen'); ?></h2>
<form class="form-horizontal" role="form" method="post" enctype="multipart/form-data">
    <div class="form-group">
        <label for="projektname" class="col-sm-4 control-label">Projektname<sup>*</sup></label>
        <div class="col-sm-5">
            <input type="text" class="form-control" name="projektname" id="projektname" placeholder="Projektname">
        </div>
    </div>
    <div class="form-group">
        <label for="eingabefeldPasswort3" class="col-sm-4 control-label">Passwort für externe Besucher</label>
        <div class="col-sm-5">
            <input type="password" class="form-control" name="passwort" id="eingabefeldPasswort3" placeholder="Passwort">
        </div>
    </div>
    <div class="form-group">
        <label for="projektbeschreibung" class="col-sm-4 control-label">Projektbeschreibung</label>
        <div class="col-sm-5">
            <div class="panel panel-default">
                <!-- Standard-Panel-Inhalt -->
                <div class="panel-heading">Vorhandene Projektbeschreibungen</div>

                <!-- Tabelle -->
                <table class="table" id="projektbeschreibungen" data-count="3">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Dateiname</th>
                            <th class="text-right">Datum</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Mark</td>
                            <td class="text-right">01.01.1970</td>
                            <td class="hidden-close"><span class="glyphicon glyphicon-remove" aria-hidden="true" data-id="0"></span></td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Jacob</td>
                            <td class="text-right">01.01.1970</td>
                            <td class="hidden-close"><span class="glyphicon glyphicon-remove" aria-hidden="true" data-id="1"></span></td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>Larry</td>
                            <td class="text-right">01.01.1970</td>
                            <td class="hidden-close"><span class="glyphicon glyphicon-remove" aria-hidden="true" data-id="2"></span></td>
                        </tr>
                    </tbody>
                </table>

                <div class="panel-body">
                    <div class="row form-group">
                        <label class="col-xs-12" for="files">Projektbeschreibung hochladen <small>(Max: <?php echo ini_get('post_max_size'); ?>)</small></label>
                        <div class="form-horizontal" role="form">
                            <input class="col-md-9 control-label" name="file[]" id="files" type="file" id="projektbeschreibung" multiple="multiple" data-maxsize="<?php echo Utils::convertBytes(ini_get('post_max_size')); ?>">
                            <div class="col-md-3">
                                <button type="button" name="upload" id="upload" class="btn btn-primary btn-sm pull-right form-control">Upload</button>
                            </div>
                            <div class="col-md-3">
                                <div id="progress-circle" style="display: none;">
                                    <div class="progress-circle-bar">
                                        <canvas id="activeProgress" class="progress-active"  height="55px" width="55px"></canvas>
                                        <p>0%</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="upload-errors" style="display: none;">
                        <div class="alert alert-danger"></div>
                    </div>
                    <p><strong>Achtung:</strong> Wenn der Name der Datei schon vorhanden ist, wird die existierende Datei überschrieben.</p>

                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="token" value="<?php echo Token::generate(); ?>">
    <div class="form-group">
        <div class="col-sm-offset-4 col-sm-5">
            <button type="submit" class="btn btn-primary">Speichern</button>
            <a href="index.php" class="btn btn-link">Abbrechen</a>
        </div>
    </div>

    <script>
        $('#upload').click(function (event) {
            var f = $('#files')[0];
            var pie = $('#progress-circle');
            var errorBox = $('#upload-errors');
            var button = $(this);
            var maxSize = $('#files').data('maxsize');

            event.preventDefault();

            var msg = checkMaxsize(maxSize, f);
            if (msg !== '') {
                errorBox.show();
                $('#upload-errors .alert-danger').html('');
                $('#upload-errors .alert-danger').append(msg);
                button.blur();
                return false;
            } else {
                errorBox.hide();
            }
            button.toggle();
            pie.toggle();

            app.uploader({
                files: f,
                function: 'upload',
                element: {
                    name: 'projektbeschreibung'
                },
                pCaption: $('.progress-circle-bar p'),
                aProgress: $('#activeProgress'),
                maxsize: maxSize,
                processor: 'ajaxHandler.php',
                finished: function (data) {
                    pie.toggle();
                    button.toggle();
                    // Fuege Element in Tabelle ein
                    var count = parseInt($('#projektbeschreibungen').data('count'));
                    $.each(data.succeeded, function (i) {
                        $('#projektbeschreibungen').append(
                                '<tr><td>' + 
                                (count + (i + 1)) + 
                                '</td><td>' 
                                + data.succeeded[i].name + 
                                '</td><td>' + 
                                data.succeeded[i].date + 
                                '</td><td>' +
                                '<span class="glyphicon glyphicon-remove" aria-hidden="true" data-id="' + data.succeeded[i].id + '"></span>' +
                                '</td></tr>'
                                );
                    });
                },
                error: function (data) {
                    console.log(data);
                    $('#upload-errors').append('error');
                }
            });
        });
        
        $('.glyphicon-remove').on('click', function() {
            $.ajax({
                type: 'post',
                url: 'ajaxHandler.php',
                data: {
                    function: "delete",
                    element: {
                        name: "projektbeschreibung",
                        id: $(this).data('id')
                    }
                }
            })
            .done(function() {
              console.log("success");
              // TODO: Tabelleneintrag löschen
            })
            .fail(function() {
              // TODO: Fehler in $('#upload-errors') anzeigen
            });
        });
    </script>
</form>

<?php
require_once 'footer.php';
