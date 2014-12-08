<?php
require_once 'header.php';

$projekt = new Projekt();
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
                    Redirect::to('projekt.php');
                } else {
                    Redirect::to('index.php');
                }
            } else {
                Session::flash('error', 'Sie haben ein falsches Passwort eingegeben oder keine Berechtigungen!');
                Redirect::to('login.php');
            }
        } else {
            foreach ($validation->errors() as $error) {
                echo $error . "<br>";
            }
        }
    }
}
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
                    $db = DB::getInstance();

                    $db->query('SELECT name, id FROM projekte');
                    foreach ($db->results() as $projekt) {
                        echo '<option value="' . $projekt->id . '">' . escape($projekt->name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <label for="password" class="sr-only">Passwort</label>
            <div class="form-group no-margin-bottom">
                <div class="input-group">
                    <input type="password" name="password" class="form-control" placeholder="Passwort" id="password" required>
                    <span class="input-group-addon"><span class="glyphicon glyphicon-lock" aria-hidden="true"></span></span>
                </div>
            </div>
            <div class="form-group">
                <a href="#" tabindex="0" id="password-popover" data-html="true" role="button" data-toggle="popover" data-placement="left" title="Passwort vergessen?" data-trigger="focus" data-content="Wenn Sie ihr Passwort für ein Projekt vergessen haben, wenden Sie sich bitte telefonisch oder per E-Mail an das Kunstofflabor der HTWG-Konstanz.<br><br>Tel.: 202-555-0114<br><br><a href='mailto:bla@mail.com'>mail@bla.com</a>">Passwort vergessen?</a>
            </div>
            <input name="token" type="hidden" value="<?php echo Token::generate(); ?>">
            <input class="btn btn-lg btn-primary btn-block" type="submit" value="&Ouml;ffnen">
        </form>
    </div>
</div>

<?php
require_once 'footer.php';
