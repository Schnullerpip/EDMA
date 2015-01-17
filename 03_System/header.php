<?php
require_once 'core/init.php';

$projekt = new Projekt();
if (!$projekt->isLoggedIn() && curPageName() !== 'login.php') {
    Redirect::to('logout.php');
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

        <!-- TODO bessere Lösung finden Julistuff -->
        <link href="css/julicss.css" rel="stylesheet">

        <!-- Bootstrap-JavaScript -->
        <script src="js/jquery.min.js"></script>
        <script src="js/bootstrap.min.js"></script>


        <!-- Unterstützung für Media Queries und HTML5-Elemente in IE8 über HTML5 shim und Respond.js -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
          <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->

        <!-- IE10-Anzeigefenster-Hack für Fehler auf Surface und Desktop-Windows-8 -->
        <script src="js/ie10-viewport-bug-workaround.js"></script>
        
        <!-- Dynatable für Suche/Filter bei Tabellen-->
        <script src="js/dynatable.min.js"></script>
    </head>

    <body>
        <?php
        if (Session::exists('error')) {
            ?>
            <div class="alert alert-top alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Schließen</span></button>
                <strong>Warnung!</strong> <?php echo Session::flash('error'); ?>
            </div>
            <?php
        }
        if (Session::exists('message')) {
            ?>
            <div class="alert alert-info alert-dismissible">
                <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Schließen</span></button>
                <strong>Warnung!</strong> <?php echo Session::flash('message'); ?>
            </div>
            <?php
        }
        ?>
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
                    <a class="navbar-brand" href="index.php">EDMA</a>
                </div>
                <div id="navbar" class="navbar-collapse collapse">
                    <?php if ($projekt->isLoggedIn()) : ?>
                        <ul class="nav navbar-nav">
                            <?php if ($projekt->isMaster()) : ?>
                                <li><a href="index.php">Startseite</a></li>
                                <li><a href="projekt.php">Projektverwaltung</a></li>
                                <li><a href="messreihen.php">Messreihenverwaltung</a></li>
                            <?php endif; ?>
                        </ul>
                        <ul class="nav navbar-nav navbar-right">
                            <li><a href="logout.php">Projekt wechseln</a></li>
                        </ul>
                    <?php endif; ?>
                </div><!--/.nav-collapse -->
            </div>
        </nav>

        <div class="container page-wrap">
            <!-- Content begins -->
