<?php

/**
 * Hilfsklasse um schnell weiter zu leiten.
 *
 * @author Sandro
 */
class Redirect {

    /**
     * Leitet PHP zu der Seite $location weiter.
     * 
     * @param string $location Seite zu der weitergeleitet werden soll.
     * Falls $location numerisch ist, wird zu der entsprechenden Fehlerseite
     * in 'includes/errors/$location.php' weitergeleitet.
     * Ansonsten zu der Seite $location (z.B. index.php).
     */
    public static function to($location = null) {
        if ($location) {
            if (is_numeric($location)) {
                switch ($location) {
                    case 404:
                        header('HTTP/1.0 404 Not Found');
                        include 'includes/errors/404.php';
                        exit();
                        break;
                }
            }
            header('Location: ' . $location);
            exit();
        }
    }

}
