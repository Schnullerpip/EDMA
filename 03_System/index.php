<?php
require_once 'header.php';

if (!$projekt->isLoggedIn()) {
    Redirect::to('logout.php');
}
?>

    <p>Projekt: <?php echo escape($projekt->data()->projektname); ?></p>
    <div class="row">
        <div class="col-xs-12">
            <div class="panel-group accordeon" role="tablist">
                <div class="panel panel-default">
                    <a class="btn btn-block panel-heading text-center collapsed" role="tab" href="#collapseListengruppe1" data-toggle="collapse" aria-expanded="true" aria-controls="collapseListengruppe1" id="collapseListengruppeÜberschrift1">
                        Letzte Messreihenimporte anzeigen<span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
                    </a>
                    <!-- TODO: collapse out anstatt collapse in Klasse -->
                    <div id="collapseListengruppe1" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="collapseListengruppeÜberschrift1" aria-expanded="true">
                        <ul class="list-group list-unstyled">
                            <li class="">
                                <div class="row">
                                    <div class="col-sm-2">
                                        <div class="list-content">
                                            Messreihe 1
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="list-content">
                                            <!-- TODO: <data> -->
                                            01.01.1970
                                        </div>
                                    </div>
                                    <div class="col-sm-1 pull-right controls">
                                        <ul class="list-unstyled list-inline">
                                            <li>1</li>
                                            <li>2</li>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                            <li class="">
                                <div class="row">
                                    <div class="col-sm-2">
                                        Messreihe 2
                                    </div>
                                    <div class="col-sm-2">
                                        02.01.1970
                                    </div>
                                </div>
                            </li>
                            <li class="">
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
