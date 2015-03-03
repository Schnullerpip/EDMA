<?php
require_once 'core/init.php';

$projekt = new Projekt();
if (!$projekt->isLoggedIn() && curPageName() !== 'login.php') {
    Redirect::to('logout.php');
}

if(!Input::itemExists('projekt_save')) {
    if (Session::exists(Config::get('session/upload_name'))) {
        $uploadedFiles = Session::get(Config::get('session/upload_name'));
        foreach ($uploadedFiles as $uploadedFile) {
            unlink($uploadedFile['fileTemp']);
        }
        Session::delete(Config::get('session/upload_name'));
    }
    if (Session::exists(Config::get('session/removed_name'))) {
        Session::delete(Config::get('session/removed_name'));
    }
}
if (Input::itemExists('projekt_cancel')) {
    if (Session::get(Config::get('session/session_name')) === 'Neues Projekt') {
        Redirect::to('logout.php');
    } else {
        Redirect::to('index.php');
    }
}
?>

<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="">
        <meta name="author" content="">

        <title>EDMA - HTWG-Konstanz</title>

        <!-- Favicon -->
        <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
        <!-- Bootstrap-CSS -->
        <link href="css/bootstrap.min.css" rel="stylesheet">
        
        <!-- Bootstrap-Datepicker -->
        <link href="css/vendor/datepicker.css" rel="stylesheet">

        <!-- EDMA CSS -->
        <link href="css/bootstrap-theme.css" rel="stylesheet">

        <!-- Vendor-JavaScript -->
        <script src="js/vendor/jquery.min.js"></script>
        <script src="js/vendor/bootstrap.min.js"></script>
        <script src="js/vendor/bootstrap-datepicker.min.js"></script>
        <script src="js/vendor/locales/bootstrap-datepicker.de.js"></script>
        <script src="js/vendor/string_score.min.js"></script>
        <script src="js/vendor/dynatable.min.js"></script>
        <script src="js/vendor/scrollbar.min.js"></script>


        <!-- Unterstützung für Media Queries und HTML5-Elemente in IE8 über HTML5 shim und Respond.js -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
          <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->

        <!-- IE10-Anzeigefenster-Hack für Fehler auf Surface und Desktop-Windows-8 -->
        <script src="js/ie10-viewport-bug-workaround.js"></script>

        <link rel="stylesheet" type="text/css" href="./css/vendor/jquery.jqChart.css" />
        <link rel="stylesheet" type="text/css" href="./css/vendor/jquery.jqRangeSlider.css" />
        <link rel="stylesheet" type="text/css" media="screen" 
              href="http://ajax.aspnetcdn.com/ajax/jquery.ui/1.8.21/themes/smoothness/jquery-ui.css" />
        <script src="./js/vendor/jqChart/jquery.jqChart.min.js" type="text/javascript"></script>
        <script src="./js/vendor/jqChart/jquery.jqRangeSlider.min.js" type="text/javascript"></script>
        <script src="./js/vendor/jqChart/jquery.mousewheel.js" type="text/javascript"></script>
        <!--[if IE]><script lang="javascript" type="text/javascript" src="./js/vendor/jqChart/excanvas.js"></script><![endif]-->
    </head>

    <body>
        <noscript>
            <div class="no-javascript">
                <img src="images/error.png" alt="Bild: ACHTUNG! JavaScript deaktiviert!" title="ACHTUNG! JavaScript deaktiviert!">
                <h1>ACHTUNG:</h1>
                <p>In Ihrem Browser ist JavaScript deaktiviert!</p>
                <p>Um diese Webseite nutzen zu k&ouml;nnen, aktivieren Sie bitte JavaScript!</p>
            </div>
        </noscript>
        
        <?php if (Session::exists('error')) : ?>
            <div class="alert alert-top alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Schließen</span></button>
                <strong>Warnung!</strong> <?php echo Session::flash('error'); ?>
            </div>
        <?php endif; ?>
        <?php if (Session::exists('warning')) : ?>
            <div class="alert alert-info alert-dismissible">
                <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Schließen</span></button>
                <strong>Warnung!</strong> <?php echo Session::flash('warning'); ?>
            </div>
        <?php endif; ?>
        <?php if (Session::exists('success')) : ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Schließen</span></button>
                <?php echo Session::flash('success'); ?>
            </div>
        <?php endif; ?>
        <!-- Fixierte Navbar -->
        <nav class="navbar navbar-default navbar-static-top" role="navigation">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                        <span class="sr-only">Navigation ein-/ausblenden</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="index">EDMA</a>
                </div>
                <div id="navbar" class="navbar-collapse collapse">
                    <?php if ($projekt->isLoggedIn()) : ?>
                        <ul class="nav navbar-nav">
                            <li><a href="index">Startseite</a></li>
                            <li><a href="projekt">Projektverwaltung</a></li>
                            <?php if ($projekt->isMaster()) : ?>
                                <li><a href="messreihen">Messreihenverwaltung</a></li>
                            <?php endif; ?>
                        </ul>
                        <ul class="nav navbar-nav navbar-right">
                            <li><a href="logout.php">Projekt wechseln</a></li>
                        </ul>
                    <?php endif; ?>
                </div><!--/.nav-collapse -->
            </div>
        </nav>

        <div class="container page-wrap" style="display: none;">
            <script>
                $('.page-wrap').show();
            </script>
            <!-- Content begins -->
