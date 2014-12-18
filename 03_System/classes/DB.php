<?php

require_once 'core/init.php';

/**
 * Description of DB
 *
 * @author Sandro
 */
class DB {

    private static $_instance = null;
    private $_pdo,
            $_query,
            $_error = false,
            $_results,
            $_count = 0;

    private function __construct() {
        try {
            $this->_pdo = new PDO('mysql:host=' . Config::get('mysql/host') . ';dbname=' . Config::get('mysql/db') . ';charset=UTF8', Config::get('mysql/username'), Config::get('mysql/password'));
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     * Gibt die aktuelle Instanz des Singelton zurück.
     * @return type
     */
    public static function getInstance() {
        if (!isset(self::$_instance)) {
            self::$_instance = new DB();
        }
        return self::$_instance;
    }

    /**
     * Führt eine Query auf der Datanbank aus.
     * Dazu wird der SELECT in $sql übergeben. Alle Parameterwerte für 
     * die WHERE-Bedinung werden durch ein Fragezeichen ersetzt. 
     * Die eigentlich Werte werden seperat in einem array übergeben. 
     * Der erste Eintrag erstzt dabei das erste Fragezeichen
     * in der WHERE-Bedingung.
     * 
     * Im Fehlerfall wird das Error-Flag gesetzt.
     * @param type $sql Komplettes Statement mit ? statt Parameterwerte
     * @param type $params array mit Parameterwerten, welche die ? ersetzen.
     * @return \DB Gibt die Referenz auf sich selber zurück.
     */
    public function query($sql, $params = array()) {
        // Felder zurücksetzen
        $this->_error = false;
        $this->_count = 0;
        $this->_results = array();
        
        if ($this->_query = $this->_pdo->prepare($sql)) {
            $x = 1;
            if (count($params)) {
                foreach ($params as $param) {
                    $this->_query->bindValue($x, $param);
                    $x++;
                }
            }

            if ($this->_query->execute()) {
                $this->_results = $this->_query->fetchAll(PDO::FETCH_OBJ);
                $this->_count = $this->_query->rowCount();
            } else {
                $this->_error = true;
            }
        } else {
            $this->_error = true;
        }
        return $this;
    }

    private function action($action, $table, $where = array()) {
        if (count($where) === 3) {
            $operators = array('=', '>', '<', '>=', '<=');

            $field = $where[0];
            $operator = $where[1];
            $value = $where[2];

            if (in_array($operator, $operators)) {
                $sql = "{$action} FROM {$table} WHERE {$field} {$operator} ?";
                if (!$this->query($sql, array($value))->error()) {
                    return $this;
                }
            }
        }
        return false;
    }

    public function get($table, $where) {
        return $this->action('SELECT *', $table, $where);
    }

    public function delete($table, $where) {
        return $this->action('DELETE', $table, $where);
    }
    
    /**
     * Wrapper-Funktion für INSERT-Statements.
     * Alle Felder und ihre neuen Werte werden als array übergeben.
     * Dazu dient der Feldname als Key und der neue Wert als Value.
     * Für Beispiele siehe auch 
     * @see getIdBySelectOrInsert
     * @param type $table Tabelle in die Datan eingefügt werden.
     * @param type $fields Array mit Spaltennamen und dem neuen Inhalt. 
     * @return boolean Gibt True zurück, wenn der INSERT erfolgreich war.
     */
    public function insert($table, $fields = array()) {
        $keys = array_keys($fields);
        $values = '';
        $x = 1;

        foreach ($fields as $field) {
            $values .= '?';
            if ($x < count($fields)) {
                $values .= ', ';
            }
            $x++;
        }

        $sql = "INSERT INTO {$table} (`" . implode('`,`', $keys) . "`) VALUES ({$values})";

        if (!$this->query($sql, $fields)->error()) {
            return true;
        }
        return false;
    }

    public function update($table, $id, $fields) {
        $set = '';
        $x = 1;

        foreach ($fields as $name => $value) {
            $set .= "{$name} = ?";
            if ($x < count($fields)) {
                $set .= ', ';
            }
            $x++;
        }

        $sql = "UPDATE {$table} SET {$set} WHERE id = {$id}";

        if (!$this->query($sql, $fields)->error()) {
            return true;
        }
        return false;
    }
    
    /**
     * Gibt die ID von einem Datensatz zurück.
     * Ist der Datensatz noch nicht vorhanden, wird er per INSERT eingefügt.
     * In $fields müssen alle Felder angegeben werden, die für einen 
     * möglichen INSERT erforderlich sind.
     * 
     * Ein Aufruf könnte wie folgt aussehen:
     * $messreihenFelder = array (
     *     "messreihenname" => "Trocknungslauf kont. Förderung",
     *     "datum" => "2014-10-14",
     *     "projekt_id" => $projektId,
     * );
     * $id = $db->getIdBySelectOrInsert("messreihe", $messreihenFelder);
     * 
     * @param type $table Name der Tabell in welcher der Datensatz liegt.
     * @param type $fields Array mit Felder für WHERE oder INSERT,
     * wobei KEY = Feldname und VALUE = Wert z.B. array['metaname'] = 'Datum'.
     */
    public function getIdBySelectOrInsert($table, $fields) {
        $where = "";
        foreach ($fields as $fieldname => $value) {
            if (!empty($where)) {
                $where .= " and ";
            }
            $where .= "{$fieldname} = ?"; 
        }
        
        $sql = "SELECT id FROM {$table} WHERE {$where}";
        $this->query($sql, $fields);
        if (!$this->error() and $this->count()) {
            return $this->first()->id;
        } else {
            if ($this->insert($table, $fields)) {
                return $this->getIdBySelectOrInsert($table, $fields);
            }
        }
        
        return false;
    }

    public function results() {
        return $this->_results;
    }

    public function first() {
        return $this->results()[0];
    }

    public function error() {
        return $this->_error;
    }

    public function count() {
        return $this->_count;
    }
}
