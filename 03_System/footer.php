        
            <!-- Content ends -->
        </div> <!-- /container -->
        <!-- Modal -->
        <div class="modal fade" id="infoModal" tabindex="-1" role="dialog" aria-labelledby="modalUeberschrift" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Schließen</span></button>
                        <h4 class="modal-title" id="modalUeberschrift">Info!</h4>
                    </div>
                    <div class="modal-body">
                        <section class="warning">
                            <div class="alert alert-warning">
                                <h4>Warnung!</h4>
                                <div class="content"></div>
                            </div>
                        </section>
                        <section class="error">
                            <div class="alert alert-danger">
                                <h4>Fehler!</h4>
                                <div class="content"></div>
                            </div>
                        </section>
                        <section class="success">
                            <div class="alert alert-success">
                                <h4>Info!</h4>
                                <div class="content"></div>
                            </div>
                        </section>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-primary">Details ansehen</a>
                        <button type="button" class="btn btn-link" data-dismiss="modal">Schließen</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Delete-Modal -->
        <div class="modal fade" id="delete-modal" tabindex="-1" role="dialog" aria-labelledby="confirmModalUeberschrift" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Schließen</span></button>
                        <h4 class="modal-title" id="confirmModalUeberschrift">Achtung!</h4>
                    </div>
                    <div class="modal-body">
                        <section class="error mb-30">
                            <div class="alert alert-danger">
                                <h4>Fehler!</h4>
                                <div class="content"></div>
                            </div>
                        </section>
                        <p class="mb-15">Soll <span class="type"></span> wirklich gelöscht werden?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="confirm-delete">Löschen</button>
                        <button type="button" class="btn btn-link" data-dismiss="modal">Abbrechen</button>
                    </div>
                </div>
            </div>
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
        
        <!-- EDMA Custom-Scripts -->
        <script src="js/custom-scripts.js"></script>
        
        <?php 
        if (isset($includes)) {
            foreach ($includes as $include) {
                echo "<script src='js/{$include}.js'></script>";
            }
        }
        ?>
    </body>
</html>
