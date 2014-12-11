<?php
require_once 'header.php';

if (!$projekt->isLoggedIn()) {
    Redirect::to('logout.php');
}
?>

    <p>Projekt: <?php echo escape($projekt->data()->name); ?></p>
    <div class="row">
        <div class="col-xs-12">
            <div class="panel-group accordeon" role="tablist">
                <div class="panel panel-default">
                    <a class="btn btn-block panel-heading text-center collapsed" role="tab" href="#collapseListengruppe1" data-toggle="collapse" aria-expanded="true" aria-controls="collapseListengruppe1" id="collapseListengruppeÜberschrift1">
                        Letzte Messreihenimporte anzeigen<span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
                    </a>
                    <div id="collapseListengruppe1" class="panel-collapse collapse out" role="tabpanel" aria-labelledby="collapseListengruppeÜberschrift1" aria-expanded="true">
                        <ul class="list-group list-unstyled">
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-sm-2">
                                        Messreihe 1
                                    </div>
                                    <div class="col-sm-2">
                                        01.01.1970
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-sm-2">
                                        Messreihe 2
                                    </div>
                                    <div class="col-sm-2">
                                        02.01.1970
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-sm-2">
                                        Messreihe 3
                                    </div>
                                    <div class="col-sm-2">
                                        03.01.1970
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <h1>Metadaten filtern</h1>
    <h1>Messreihe wählen</h1>
    <h1>Einstellungen</h1>

<?php
require_once 'footer.php';
