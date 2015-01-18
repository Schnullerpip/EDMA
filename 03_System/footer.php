        
            <!-- Content ends -->
        </div> <!-- /container -->
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
        
        <!-- Bootstrap Datepicker -->
        <script src="js/vendor/bootstrap-datepicker.min.js"></script>
        <script src="js/vendor/locales/bootstrap-datepicker.de.js"></script>
        
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
