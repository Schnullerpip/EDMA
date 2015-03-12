<?php

require_once 'core/init.php';

$von = intval(Input::get("from"));
$bis = intval(Input::get("to"));
$step = intval(Input::get("step"));
$modus = Input::get("mode");

// bei CSV wird pair als JSON uebermittelt und muss erst konvertiert werden
if ($modus === "CSV") {
    $paare = json_decode(Input::get("pair"), true);
} else {
    $paare = Input::get("pair");
}

$db = DB::getInstance();

$whereSensoren = "(";
$first = true;
// baue WHERE Bedinung aus paare: 0 = sensor_id, 1 = messreihe_id
foreach ($paare as $paar) {
    if ($first) {
        $first = false;
    } else {
        $whereSensoren .= " OR ";
    }

    $whereSensoren .= "(sensor_id = " . $paar[0] . " AND messreihe_id = " . $paar[1] . ")";
}
$whereSensoren .= ")";



$whereZeitpunkt = "";
if ($step === 1) {
    if ($von === 0 && $bis === 0) {
        // Zeitpunkt spielt keine rolle
        $whereZeitpunkt = "TRUE";
    } else if ($von === 0) {
        $whereZeitpunkt = "messung.zeitpunkt < {$bis}";
    } else if ($bis === 0) {
        $whereZeitpunkt = "messung.zeitpunkt > {$von}";
    } else {
        // Wenn jeder Zeitpunkt benutzt, kann BETWEEN genutzt werden.
        $whereZeitpunkt = "messung.zeitpunkt BETWEEN {$von} AND {$bis}";
    }
} else {
    if($bis === 0){
        $db->query("SELECT MAX(zeitpunkt) as max_zeitpunkt FROM `messung` WHERE {$whereSensoren}");//hol max wert der selectedSensors  
        $bis = intval($db->first()->max_zeitpunkt);
    }
    // zeitpunkte (n,n,....,n) als string fuer IN Operator
    $zeitpunkte = "(";
    $first = true;
    for ($i = $von; $i <= (int)$bis; $i += $step) {
        if ($first) {
            $first = false;
        } else {
            $zeitpunkte .= ",";
        }
        $zeitpunkte .= $i;
    }
    $zeitpunkte .= ")";

    $whereZeitpunkt = "messung.zeitpunkt IN {$zeitpunkte}";
}



$db->query("SELECT messreihe_sensor.anzeigename, messreihe.messreihenname " .
        "FROM messreihe_sensor " .
        "INNER JOIN messreihe ON messreihe_sensor.messreihe_id = messreihe.id " .
        "WHERE {$whereSensoren}" .
        "ORDER BY messreihe_sensor.messreihe_id, messreihe_sensor.sensor_id");

if ($db->error()) {
    echo "FEHLER NAMEN!";
    echo "SELECT messreihe_sensor.anzeigename, messreihe.messreihenname " .
    "FROM messreihe_sensor " .
    "INNER JOIN messreihe ON messreihe_sensor.messreihe_id = messreihe.id " .
    "WHERE {$whereSensoren}" .
    "ORDER BY messreihe_sensor.messreihe_id, messreihe_sensor.sensor_id";
    die();
}
$results = $db->results();
$line = "";
$first = true;
// baue erste Zeile mit Namen aus messreihenname - anzeigename
foreach ($results as $namen) {
    if ($first) {
        $first = false;
    } else {
        if ($modus === "CSV") {
            $line .= "\t";
        } else {
            $line .= ",";
        }
    }
    $line .= "{$namen->messreihenname} - {$namen->anzeigename}";
}

$line .= "\n";
echo $line;

$sql = "SELECT * FROM messung "
        . "WHERE {$whereZeitpunkt} AND {$whereSensoren} "
        . "ORDER BY zeitpunkt, messreihe_id, sensor_id";
        
if (!$db->justquery($sql)) {
    echo "FEHLER MESSUNG!";
    echo $sql;
    die();
}

$count = count($paare);
$line = "";
$index = 0;
$positions = array();
$sollPosition = 0;
// echo pro Zeile, eine Zeile entspricht einem Zeitpunkt
while ($row = $db->fetch()) {
    // baue id fuer paar
    $id = "{$row->messreihe_id}{$row->sensor_id}";

    if (array_key_exists($id, $positions)) {
        // pruefe ob id an ihrer sollPosition vorkommt, falls nicht hat eine
        // frueheres Paar keine Datensaetz mehr und muss "simuliert" werden
        $delta = $positions[$id] - $sollPosition;
        if ($delta < 0) {
            $delta += $count;
        }
        if ($delta !== 0) {
            // korrektur: fuelle fehlende Daten auf, sodass alle paare gleich
            // viele datensaetze haben
            $line .= correctData($delta, $count, $sollPosition);
            $sollPosition = $positions[$id];
            $index += $delta;
        }
    } else {
        // weise jedem paar eine zielposition (=Spalte) im resultat zu
        // else darf nur beim 1. durchlauf pro Paar eintreten
        $positions[$id] = count($positions);
    }
    
    // fuer CSV export nur messwerte ausgeben, sonst messwert und datum_uhrzeit
    if ($modus === "CSV") {
        $line .= number_format($row->messwert, 6, ",", ".");
    } else {
        $line .= "{$row->messwert};{$row->datum_uhrzeit}.{$row->mikrosekunden}";
    }

    // wenn mehr als ein paar existiert, darf index nicht 0 sein ansonsten
    // wuerde nach dem 1. Datensatz eine falscher Zeilenumbruch ausgegeben
    if (($count === 1 || $index !== 0) && $index % $count === $count-1) {
        $line .= "\n";
        echo $line;
        $line = "";
    } else {
        if ($modus === "CSV") {
            $line .= "\t";
        } else {
            $line .= ",";
        }
    }
    $index++;
    $sollPosition++;
    $sollPosition = $sollPosition % $count;
}

if ($line) {
    // Wenn line nicht leer ist haben die Paare unterschiedlich viele Datensaetze
    for (; $sollPosition < $count; ++$sollPosition) {
        $line .= "";
        if ($sollPosition !== $count-1) {
            $line .= ",";
        } else {
            $line .= "\n";
        }
    }
    echo $line;
}

function correctData($delta, $count, $sollPosition) {
    $result = "";
    for ($i = 0; $i < $delta; ++$i) {
        $result .= "";
        if ($sollPosition === $count-1) {
            $result .= "\n";
        } else {
            $result .= ",";
        }
        $sollPosition++;
    }
    
    return $result;
}