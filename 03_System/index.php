<?php
require_once 'header.php';

$projekt = new Projekt();
if (!$projekt->isLoggedIn()) {
    Redirect::to('logout.php');
}

?>
    <p>Projekt: <?php echo escape($projekt->data()->projektname); ?></p> 

    <ul>
        <li><a href="logout.php">Ausloggen</a></li>
        <li><a href="update.php">Profildaten aktualisieren</a></li>
        <li><a href="test.php">Testseite</a></li>
        <li><a href="projekt.php">Projektdaten bearbeiten</a></li>
    </ul>

<?php
require_once 'footer.php';
