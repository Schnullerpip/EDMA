<?php
require_once 'preHeader.php';

$projekt->logout();

if (Input::exists()) {

    if (Token::check(Input::get('token'))) {

        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'password' => array('required' => true)
        ));

        if ($validation->passed()) {
            $projekt = new Projekt(Input::get('projekt'));

            $login = $projekt->login(Input::get('projekt'), Input::get('password'));

            if ($login) {
                if (Input::get('projekt') === 'new') {
                    Redirect::to('projekt');
                } else {
                    Redirect::to('index');
                }
            } else {
                Session::flash('error', 'Sie haben ein falsches Passwort eingegeben oder keine Berechtigungen!');
                Session::flash('inputProjekt', Input::get('projekt'));

                // Auskommentieren, um Weiterleitung zu verhindern und Fehler anzuzeigen
                Redirect::to('login');
            }
        } else {
            foreach ($validation->errors() as $error) {
                echo $error . "<br>";
            }
        }
    }
}

require_once 'header.php';
?>

<div class="row">
    <div class="col-sm-6 col-sm-offset-3 col-md-4 col-md-offset-4">
        <form role="form" action="" method="post">
            <h2>Bitte Projekt auswählen</h2>
            <label for="projekt" class="sr-only">Projekt</label>
            <div class="form-group">
                <select class="form-control" id="projekt" name="projekt">
                    <option value="new">Projekt hinzuf&uuml;gen</option>
                    <?php
                    $inputProjekt = null;
                    if (Session::exists('inputProjekt')) {
                        $inputProjekt = Session::flash('inputProjekt');
                    }

                    $db = DB::getInstance();

                    $db->query('SELECT projektname, id FROM projekt');
                    foreach ($db->results() as $projekt) {
                        if ($projekt->id === $inputProjekt) {
                            $selected = ' selected';
                        } else {
                            $selected = '';
                        }

                        echo '<option value="' . $projekt->id . '"' . $selected . '>' . escape($projekt->projektname) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <label for="password" class="sr-only">Passwort</label>
            <div class="form-group">
                <div class="input-group">
                    <input type="password" name="password" class="form-control" placeholder="Passwort" id="password" required>
                    <span class="input-group-btn">
                        <button class="btn btn-primary" type="button" data-html="true" data-toggle="popover" data-placement="right" title="Passwort vergessen?" data-content="<p>Wenn Sie ihr Passwort für ein Projekt vergessen haben, wenden Sie sich bitte telefonisch oder per E-Mail an das Kunstofflabor der HTWG-Konstanz.</p><p>Tel.: 202-555-0114</p><a href='mailto:fm.kienzler@htwg-konstanz.de' target='_top' title='Mail an Fabian Kienzler schreiben'>fm.kienzler@htwg-konstanz.de</a>">?</button>
                    </span>
                </div>
            </div>
            <input name="token" type="hidden" value="<?php echo Token::generate(); ?>">
            <input class="btn btn-default btn-block" type="submit" value="&Ouml;ffnen">
        </form>
    </div>
</div>

<?php
require_once 'footer.php';
