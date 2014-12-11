<?php
require_once 'header.php';

if (!$projekt->isLoggedIn()) {
    Redirect::to('logout.php');
}

?>
    <p>Projekt: <?php echo escape($projekt->data()->name); ?></p>
    <h1>Metadaten filtern</h1>
    <h1>Messreihe wählen</h1>
    <h1>Einstellungen</h1>

<?php
require_once 'footer.php';
