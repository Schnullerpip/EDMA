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
                        $fileContent = addslashes(fread($fp, filesize($filePath)));
                        fclose($fp);

                        if(!get_magic_quotes_gpc())
                        {
                            $fileName = addslashes($fileName);
                        }
        
                        $fields = array(
                            'projekt_id' => $projekt->data()->id,
                            'dateiname' => $fileName,
                            'inhalt' => $fileContent,
                            'groesse' => $fileSize,
                            'dateityp' => $fileType
                        );
        
                        if ($db->insertOrUpdate("anhang", $fields) < 0) {
                            $errors[] = "Fehler beim Upload von Datei " . $fileName;
                        }
                        
                        unlink($filePath);
                    }
                    Session::delete(Config::get('session/upload_name'));
                    if (count($errors) > 0) {
                        throw new Exception(implode("\n", $errors));
                    }
                }
            } catch (Exception $ex) {
                Session::flash('error', $ex->getMessage());
                Redirect::to('projekt.php');
            }
            
            if ($newProjekt) {
                Session::flash('success', 'Projekt erfolgreich angelegt!');
                Redirect::to('index.php');
            }
        } else {
            $message = "";
            foreach ($validation->errors() as $error) {
                $message .= $error . '<br>';
            }
            if (!Session::exists('error')) {
                Session::flash('error', $message);
                Redirect::to('projekt.php');
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
                <table class="table" id="projektbeschreibungen" data-count="3">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Dateiname</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="projektbeschreibungen_body">
                        
                        <?php
                        $db = DB::getInstance();

                        $db->query('SELECT dateiname, id FROM anhang WHERE projekt_id = ' . $projekt->data()->id);
                        foreach ($db->results() as $index => $anhang) {
                            printf(
                            '<tr>
                                <td>%d</td>
                                <td>%s</td>
                                <td class="hidden-close"><span class="glyphicon glyphicon-remove" aria-hidden="true" data-id="%d"></span></td>
                            </tr>', $index+1, strlen($anhang->dateiname) > 49 ? substr($anhang->dateiname, 0, 49) . "..." : $anhang->dateiname, $anhang->id);                            
                        }
                        ?>
                    </tbody>
                </table>
                
                <div class="upload-progress"></div>

                <div class="panel-body">
                    <div class="row form-group">
                        <label class="col-xs-12" for="files">Projektbeschreibung hochladen <small>(Max: <?php echo ini_get('post_max_size'); ?>)</small></label>
                        <div class="form-horizontal" role="form">
                            <input class="col-md-9 control-label" name="file[]" id="files" type="file" multiple="multiple" data-maxsize="<?php echo Utils::convertBytes(ini_get('post_max_size')); ?>" data-projektid="<?php echo $projekt->data()->id ?>">
                            <div class="col-md-3">
                                <button type="button" name="upload" id="upload" class="btn btn-default btn-sm btn-block">Upload</button>
                            </div>
                        </div>
                    </div>
                    <div id="upload-errors" style="display: none;" class="alert alert-danger"></div>
                    <p><strong>Achtung:</strong> Wenn der Name der Datei schon vorhanden ist, wird die existierende Datei überschrieben.</p>

                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="token" value="<?php echo Token::generate(); ?>">
    <div class="form-group">
        <div class="col-sm-offset-4 col-sm-5">
            <button type="submit" class="btn btn-default">Speichern</button>
            <a href="reset.php" class="btn btn-link">Abbrechen</a>
        </div>
    </div>

    <script>
        $('#upload').click(function (event) {
            var f = $('#files')[0];
            var errorBox = $('#upload-errors');
            var button = $('#upload');
            var maxSize = $('#files').data('maxsize');
            var progressBar = $('.upload-progress');
            var projektID = $('#files').data('projektid');

            event.preventDefault();
            button.blur();
            errorBox.empty();
            errorBox.hide();

            var msg = checkMaxsize(maxSize, f);
            if (msg !== '') {
                errorBox.show();
                errorBox.append(msg);
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
                    var count = $('#projektbeschreibungen_body tr').length;
                            parseInt($('#projektbeschreibungen').data('count'));
                    $.each(data, function (i) {
                        var name = data[i].name;
                        if (name.length > 49) {
                            name = name.substr(0,49) + "..."; 
                        }
                        
                        $('#projektbeschreibungen').append(
                                '<tr><td>' + 
                                (count + (i + 1)) + 
                                '</td><td>' 
                                + name + 
                                '</td><td>' +
                                '<span class="glyphicon glyphicon-remove" aria-hidden="true" data-id="' + data[i].id + '"></span>' +
                                '</td></tr>'
                                );
                    });
                    reset($('#files'));
                },
                error: function (data) {
                    var errorMsg = convertArray(data);
                    progressBar.width(0);
                    errorBox.show();
                    errorBox.append(errorMsg);
                    reset($('#files'));
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
        
        window.reset = function (e) {
            e.wrap('<form>').closest('form').get(0).reset();
            e.unwrap();
        }
    </script>
</form>

<?php
require_once 'footer.php';
