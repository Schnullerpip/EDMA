<?php
require_once 'preHeader.php';

if ($projekt->isMaster() && Input::exists()) {
    if (Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'projektname' => array(
                'fieldname' => 'Projektname',
                'required' => true,
                'min' => 3,
                'max' => 100,
                'unique' => true
            )
        ));

        if ($validation->passed()) {
            $salt = Hash::salt(32);

            $db = DB::getInstance();
            $newProjekt = false;
            try {
                if (is_object($projekt->data()) and $projekt->data()->id > 0) {
                    // Projekt updaten
                    if (Input::get('projektname') !== $projekt->data()->projektname) {
                        $projektData = array(
                            'projektname' => Input::get('projektname')
                        );

                        $db->update('projekt', $projekt->data()->id, $projektData);

                        if ($db->error()) {
                            throw new Exception("Projektname konnte nicht aktualisiert werden.");
                        }
                        $projekt = new Projekt();
                    }

                    // Neues Passwort
                    if (Input::get('passwort')) {
                        $passwordData = array(
                            'salt' => $salt,
                            'hash' => Hash::make(Input::get('passwort'), $salt),
                            'projekt_id' => $projekt->data()->id
                        );

                        $db->insert('passwort', $passwordData);

                        if ($db->error()) {
                            throw new Exception("Passwort konnte nicht angelegt werden.");
                        }
                    }
                } else {
                    $newProjekt = true;
                    // Neues Projekt
                    $projektData = array(
                        'projektname' => Input::get('projektname'),
                    );

                    $projekt_id = $db->getIdBySelectOrInsert('projekt', $projektData);

                    if (!$projekt_id) {
                        throw new Exception("Projekt konnte nicht angelegt werden!");
                    }

                    Session::put(Config::get('session/session_name'), $projekt_id);

                    $passwordData = array(
                        'salt' => $salt,
                        'hash' => Hash::make(Input::get('passwort'), $salt),
                        'projekt_id' => $projekt_id
                    );

                    $db->insert('passwort', $passwordData);

                    if ($db->error()) {
                        throw new Exception("Passwort konnte nicht angelegt werden.");
                    }

                    $projekt = new Projekt($projekt_id);
                }

                // insert Projektbeschreibungen
                if (Session::exists(Config::get('session/upload_name'))) {
                    $uploadedFiles = Session::get(Config::get('session/upload_name'));
                    $errors = array();
                    foreach ($uploadedFiles as $fileName => $fileArray) {
                        $filePath = $fileArray['fileTemp'];
                        $fileType = $fileArray['fileType'];
                        $fileSize = $fileArray['fileSize'];


                        $fp = fopen($filePath, 'r');
                        $fileContent = fread($fp, filesize($filePath));
                        fclose($fp);

                        $fields = array(
                            'projekt_id' => $projekt->data()->id,
                            'dateiname' => $fileName,
                            'inhalt' => $fileContent,
                            'groesse' => $fileSize,
                            'dateityp' => $fileType
                        );

                        $serachFileds = array(
                            'projekt_id' => $projekt->data()->id,
                            'dateiname' => $fileName
                        );

                        if ($db->insertOrUpdate("anhang", $fields, $serachFileds) < 0) {
                            $errors[] = "Fehler beim Upload von Datei " . $fileName .
                                    "\n" . 'projekt_id ' . $projekt->data()->id . "\n" .
                                    'dateiname ' . $fileName . "\n" .
                                    'groesse ' . $fileSize . "\n" .
                                    'dateityp ' . $fileType . "\n" .
                                    'content_groesse' . strlen($fileContent);
                        }

                        unlink($filePath);
                    }
                    Session::delete(Config::get('session/upload_name'));
                    if (count($errors) > 0) {
                        throw new Exception(implode("\n", $errors));
                    }
                }

                // markierte Projektbeschreibungen loeschen
                if (Session::exists(Config::get('session/removed_name'))) {
                    $filesToDelete = Session::get(Config::get('session/removed_name'));
                    $errors = array();
                    foreach ($filesToDelete as $fileName => $anhangid) {
                        $deleteWhere = array("id", "=", $anhangid);
                        if (!$db->delete("anhang", $deleteWhere)) {
                            $errors[] = "Fehler beim Löschen von Datei " . $fileName;
                        }
                    }
                    Session::delete(Config::get('session/removed_name'));
                    if (count($errors) > 0) {
                        throw new Exception(implode("\n", $errors));
                    }
                }
            } catch (Exception $ex) {
                Session::flash('error', $ex->getMessage());
                Redirect::to('projekt');
            }

            if ($newProjekt) {
                Session::flash('success', 'Projekt erfolgreich angelegt!');
                Redirect::to('index');
            }
        } else {
            $message = "";
            foreach ($validation->errors() as $error) {
                $message .= $error . '<br>';
            }
            if (!Session::exists('error')) {
                Session::flash('error', $message);
                Redirect::to('projekt');
            }
        }
    }
}

$db = DB::getInstance();
if (is_object($projekt->data())) {
    $db->query('SELECT dateiname, id FROM anhang WHERE projekt_id = ' . $projekt->data()->id);
    $projektbeschreibungen = $db->results();
} else {
    $projektbeschreibungen = array();
}
require_once 'header.php';
?>

<?php if (!$projekt->isMaster()) : ?>
    <h2>Projektbeschreibungen</h2>
    <div class="col-sm-offset-3 col-sm-6">
        <div class="panel panel-default">
            <table class="table" id="projektbeschreibungen">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Dateiname</th>
                    </tr>
                </thead>
                <tbody id="projektbeschreibungen_body">

                    <?php
                    foreach ($projektbeschreibungen as $index => $anhang) {
                        printf(
                                '<tr>
                                <td>%d</td>
                                <td><a href="download.php?id=%d">%s</a></td>
                            </tr>', $index + 1, $anhang->id, strlen($anhang->dateiname) > 49 ? substr($anhang->dateiname, 0, 49) . "..." : $anhang->dateiname, $anhang->id);
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else : ?>
    <h2><?php echo ($projekt->data() ? $projekt->data()->projektname . ' bearbeiten' : 'Neues Projekt anlegen'); ?></h2>
    <form class="form-horizontal" role="form" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="projektname" class="col-sm-4 control-label">Projektname<sup>*</sup></label>
            <div class="col-sm-5">
                <input type="text" class="form-control" name="projektname" id="projektname" placeholder="Projektname" value="<?php echo is_object($projekt->data()) ? $projekt->data()->projektname : ''; ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="eingabefeldPasswort3" class="col-sm-4 control-label">Passwort für externe Besucher</label>
            <div class="col-sm-5">
                <input type="password" class="form-control" name="passwort" id="eingabefeldPasswort3" placeholder="Passwort">
            </div>
            <div class="col-sm-5 col-sm-offset-4">
                <small>Wenn Sie ein Passwort in dieses Feld eingeben und auf "Speichern" klicken, wird ein neues Passwort zu den bestehenden Passwörtern für externe Besucher hinzugefügt.</small>
            </div>
        </div>
        <div class="form-group">
            <label for="files" class="col-sm-4 control-label">Projektbeschreibung</label>
            <div class="col-sm-5">
                <div class="panel panel-default">
                    <!-- Standard-Panel-Inhalt -->
                    <div class="panel-heading">Vorhandene Projektbeschreibungen</div>

                    <!-- Tabelle -->
                    <table class="table" id="projektbeschreibungen" data-count="<?php echo $db->count(); ?>">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Dateiname</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="projektbeschreibungen_body">

                            <?php
                            foreach ($projektbeschreibungen as $index => $anhang) {
                                printf(
                                        '<tr>
                                <td>%d</td>
                                <td><a href="download.php?id=%d">%s</a></td>
                                <td class="hidden-close"><span class="glyphicon glyphicon-remove" aria-hidden="true" data-id="%d" data-filename="%s"></span></td>
                            </tr>', $index + 1, $anhang->id, strlen($anhang->dateiname) > 49 ? substr($anhang->dateiname, 0, 49) . "..." : $anhang->dateiname, $anhang->id, $anhang->dateiname);
                            }
                            ?>
                        </tbody>
                    </table>

                    <div class="upload-progress"></div>

                    <div class="panel-body">
                        <div class="row form-group">
                            <label class="col-xs-12" for="files">Projektbeschreibung hochladen <small>(Max: <?php echo ini_get('post_max_size'); ?>)</small></label>
                            <div class="form-horizontal" role="form">
                                <input class="col-md-9 control-label" name="file[]" id="files" type="file" multiple="multiple" data-maxsize="<?php echo Utils::convertBytes(ini_get('post_max_size')); ?>" data-projektid="<?php echo is_object($projekt->data()) ? $projekt->data()->id : 0 ?>">
                                <div class="col-md-3">
                                    <button type="button" name="upload" id="upload" class="btn btn-default btn-sm btn-block">Upload</button>
                                </div>
                            </div>
                        </div>
                        <p><strong>Achtung:</strong> Wenn der Name der Datei schon vorhanden ist, wird die existierende Datei überschrieben.</p>

                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" name="token" value="<?php echo Token::generate(); ?>">
        <div class="form-group">
            <div class="col-sm-offset-4 col-sm-5">
                <?php if (is_object($projekt->data())) : ?>
                <button data-toggle="modal" data-target="#delete-modal"class="btn btn-link no-padding" 
                        data-element="projekt" data-redirect="logout" type="button"
                        data-id="<?php echo escape($projekt->data()->id); ?>"
                        title="Projekt &quot;<?php echo escape($projekt->data()->projektname); ?>&quot; l&ouml;schen">
                    Dieses Projekt l&ouml;schen
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-4 col-sm-5">
                <button name="projekt_save" type="submit" class="btn btn-default">Speichern</button>
                <button name="projekt_cancel" type="submit" class="btn btn-link">Abbrechen</button>
            </div>
        </div>

        <script>
            $('#upload').click(function (event) {
                var f = $('#files')[0];
                var button = $('#upload');
                var maxSize = $('#files').data('maxsize');
                var progressBar = $('.upload-progress');
                var projektID = $('#files').data('projektid');

                event.preventDefault();
                button.blur();

                var msg = checkMaxsize(maxSize, f);
                if (msg !== '') {
                    modalTextError(msg);
                    $('#infoModal').modal();
                    reset($('#files'));
                    return false;
                }

                app.uploader({
                    files: f,
                    function: 'upload',
                    element: {
                        name: 'projektbeschreibung'
                    },
                    progress: progressBar,
                    maxsize: maxSize,
                    processor: 'ajaxHandler.php',
                    projektID: projektID,
                    finished: function (data) {
                        progressBar.width(0);
                        // Fuege Element in Tabelle ein
                        var count = parseInt($('#projektbeschreibungen').data('count'));
                        $.each(data, function (i) {
                            var name = data[i].name;
                            if (name) {
                                if (name.length > 49) {
                                    name = name.substr(0, 49) + "...";
                                }
                                if (data[i].id == 'refreshed') {
                                    // TODO makiere aktualisierte Listenitems
                                    var tr = $("span[data-filename='" + name + "'").closest('tr');
                                    var a = tr.find('a');
                                    tr.find('td').wrapInner("<strong></strong>");
                                    a.attr("href", "#");
                                } else {

                                    $('#projektbeschreibungen').append(
                                            '<tr><td><strong>' +
                                            (++count) +
                                            '</strong></td><td><strong>'
                                            + name +
                                            '</strong></td><td><strong>' +
                                            '<span class="glyphicon glyphicon-remove" aria-hidden="true" data-id="' + data[i].id + '" data-filename="' +
                                            data[i].name + '"></span>' +
                                            '</strong></td></tr>'
                                            );
                                }
                            }
                        });
                        $('#projektbeschreibungen').data('count', count);
                        reset($('#files'));
                    },
                    error: function (data) {
                        var errorMsg = convertArray(data);
                        progressBar.width(0);
                        modalTextError(errorMsg);
                        $('#infoModal').modal();
                        reset($('#files'));
                    }
                });
            });

            $("#projektbeschreibungen").on("click", ".glyphicon-remove", function (e) {
                $.ajax({
                    type: 'post',
                    url: 'ajaxHandler.php',
                    data: {
                        function: "delete",
                        element: "projektbeschreibung",
                        id: $(this).data('id'),
                        filename: $(this).data('filename'),
                        ajax: true
                    }
                })
                        .done(function (ee) {
                            console.log(ee);
                            console.log("success");
                            $(e.target).closest('tr').fadeOut("slow");
                        })
                        .fail(function () {
                            // TODO: Fehler in $('#upload-errors') anzeigen
                        });
            });

            window.reset = function (e) {
                e.wrap('<form>').closest('form').get(0).reset();
                e.unwrap();
            }
        </script>
    </form>
<?php endif; ?>

<?php
require_once 'footer.php';
