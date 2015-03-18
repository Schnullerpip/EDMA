<?php
require_once 'core/init.php';

if (Input::exists()) {
    if (Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'neuespw' => array(
                'fieldname' => 'Neues Passwort',
                'required' => true,
                'min' => 3,
                'max' => 30
            ),
            'neuespwwdh' => array(
                'fieldname' => 'Neue Passwort wiederholen',
                'required' => true,
                'min' => 3,
                'max' => 30,
                'matches' => 'neuespw'
            ),
        ));

        if ($validation->passed()) {
            $salt = Hash::salt(32);
            $db = DB::getInstance();

            // Neues Passwort
            if (Input::get('dbpw') === Config::get('mysql/password')) {
                $params = array(Hash::make(Input::get('neuespwwdh'), $salt), $salt);
                $db->query("UPDATE passwort SET hash = ?, salt = ? WHERE projekt_id is NULL", $params);
                if ($db->error()) {
                    Session::flash('error', 'Passwort konnte nicht geändert werden. Bitte versuchen Sie es erneut');
                    Redirect::to('resetpw');
                } else {
                    Session::flash('success', 'Das Master-Passwort wurde erfolgreich geändert.');
                    Redirect::to('./');
                }
            } else {
                Session::flash('error', 'Das eingebene Datenbank-Passwort ist falsch!');
                Redirect::to('resetpw');
            }
        } else {
            $message = "";
            foreach ($validation->errors() as $error) {
                $message .= $error . '<br>';
            }
            if (!Session::exists('error')) {
                Session::flash('error', $message);
                Redirect::to('resetpw');
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">

        <title>EDMA - HTWG-Konstanz</title>

        <!-- Favicon -->
        <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
        <!-- Bootstrap-CSS -->
        <link href="css/bootstrap.min.css" rel="stylesheet">

        <!-- EDMA CSS -->
        <link href="css/bootstrap-theme.css" rel="stylesheet">


        <!-- Unterstützung für Media Queries und HTML5-Elemente in IE8 über HTML5 shim und Respond.js -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
          <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->

        <!-- IE10-Anzeigefenster-Hack für Fehler auf Surface und Desktop-Windows-8 -->
        <script src="js/ie10-viewport-bug-workaround.js"></script>
    </head>

    <body>
        <?php if (Session::exists('error')) : ?>
            <div class="alert alert-top alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Schließen</span></button>
                <strong>Warnung!</strong> <?php echo Session::flash('error'); ?>
            </div>
        <?php endif; ?>
        <!-- Fixierte Navbar -->
        <nav class="navbar navbar-default navbar-static-top" role="navigation">
            <div class="container">
                <div class="navbar-header">
                    <a class="navbar-brand" href="./">EDMA</a>
                </div>
            </div>
        </nav>

        <div class="container page-wrap">
            <div class="row form-group">
                <div class="col-xs-12">
                    <h1>Master-Passwort ändern</h1>
                </div>
            </div>

            <form class="form-horizontal" role="form" method="post">
                <div class="form-group">
                    <label for="dbpw" class="col-sm-4 control-label">Datenbank-Passwort</label>
                    <div class="col-sm-5">
                        <input type="password" class="form-control" name="dbpw" id="dbpw" placeholder="Datenbank-Passwort">
                    </div>
                </div>
                <div class="form-group">
                    <label for="neuespw" class="col-sm-4 control-label">Neues Passwort<sup>*</sup></label>
                    <div class="col-sm-5">
                        <input type="password" class="form-control" name="neuespw" id="neuespw" placeholder="Neues Passwort">
                    </div>
                </div>
                <div class="form-group">
                    <label for="neuespwwdh" class="col-sm-4 control-label">Neues Passwort wiederholen<sup>*</sup></label>
                    <div class="col-sm-5">
                        <input type="password" class="form-control" name="neuespwwdh" id="neuespwwdh" placeholder="Neues Passwort wiederholen">
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-offset-4 col-sm-5">
                        <input type="hidden" name="token" value="<?php echo Token::generate(); ?>">
                        <button type="submit" class="btn btn-default">Speichern</button>
                    </div>
                </div>
            </form>
        </div>

        <footer class="footer">
            <div class="container">
                <div class="row">
                    <div class="col-xs-12">
                        <img src="images/logo_de.png">
                        <ul class="list-unstyled pull-right">
                            <li>
                                <a href="http://www.htwg-konstanz.de/Impressum.17.0.html" target="_blank" title="Neue Seite: Das Impressum der HTWG-Konstanz">Impressum</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </footer>

    </body>
</html>
