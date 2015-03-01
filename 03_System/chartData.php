<?php

require_once 'core/init.php';

$von = intval(Input::get("from"));
$bis = intval(Input::get("to"));
$step = intval(Input::get("step"));
$paare = Input::get("pair");

$db = DB::getInstance();

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
    // zeitpunkte (n,n,....,n) als string fuer IN Operator
    $zeitpunkte = "(";
    $first = true;
    for ($i = $von; $i <= $bis; $i += $step) {
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
// baue erste Zeile mit Namen
foreach ($results as $namen) {
    if ($first) {
        $first = false;
    } else {
        $line .= ",";
    }
    $line .= "{$namen->messreihenname} - {$namen->anzeigename}";
}

$line .= "\n";
echo $line;
//fwrite($myfile, $line);

$sql = "SELECT * FROM messung "
        . "WHERE {$whereZeitpunkt} AND {$whereSensoren} "
        . "ORDER BY zeitpunkt, messreihe_id, sensor_id";
//$db->query($sql);
        
//if ($db->error()) {
if (!$db->justquery($sql)) {
    echo "FEHLER MESSUNG!";
    die();
}

//$results = $db->results();
$count = count($paare);
$line = "";
$index = 0;
$positions = array();
$sollPosition = 0;
// echo pro Zeile, eine Zeile entspricht einem Zeitpunkt
while ($row = $db->fetch()) {
    $id = "{$row->messreihe_id}{$row->sensor_id}";
    if (array_key_exists($id, $positions)) {
        $delta = $positions[$id] - $sollPosition;
        if ($delta < 0) {
            $delta += $count;
        }
        if ($delta !== 0) {
            // korrektur;
//            fwrite($myfile, "Korrektur index, soll, delta:{$index},{$sollPosition}, {$delta}\n");
            $line .= correctData($delta, $count, $sollPosition);
            $sollPosition = $positions[$id];
            $index += $delta;
        }
    } else {
        $positions[$id] = count($positions);
    }
    
    
//foreach ($results as $index => $messung) {
    $line .= $row->messwert;
//    fwrite($myfile, "index, count, Wert:{$index},{$count}, " . $row->messwert . "\n");
    if (($count === 1 || $index !== 0) && $index % $count === $count-1) {
        $line .= "\n";
        echo $line;
//        fwrite($myfile, $line);
        $line = "";
    } else {
        $line .= ",";
    }
    $index++;
    $sollPosition++;
    $sollPosition = $sollPosition % $count;
}

if ($line) {
    for (; $sollPosition < $count; ++$sollPosition) {
        $line .= "";
        if ($sollPosition !== $count-1) {
            $line .= ",";
        } else {
            $line .= "\n";
        }
    }
    echo $line;
//    fwrite($myfile, $line);
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