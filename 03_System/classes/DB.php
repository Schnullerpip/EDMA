<?php

require_once 'core/init.php';

/**
 * Klasse um die Datenbankverbindung zu managen
 *
 * @author satonon, julmayer
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
    
    public function beginTransaction() {
        $this->_pdo->beginTransaction();
    }
    
    public function commit() {
        $this->_pdo->commit();
    }
    
    public function rollback() {
        $this->_pdo->rollBack();
    }
    
    public function createStatement($sql) {
        return $this->_pdo->prepare($sql);
    }
    
    public function executeStatement(PDOStatement $statement, $values) {
        if ($statement->execute($values)) {
            $this->_results = $statement->fetchAll(PDO::FETCH_OBJ);
            $this->_count = $this->_query->rowCount();
        } else {
            $this->_error = true;
        }
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
    
    /**
     * So wie {@link query()} allerdings wird kein FetchAll ausgefuerht.
     * Die Daten muessen danach per {@link fetch()} Zeilenweise ausgelesen
     * werden.
     * @param type $sql Komplettes Statement mit ? statt Parameterwerte.
     * @param type $params array mit Parameterwerten, welche die ? ersetzen.
     * @return boolean True bei Erfolg, sonst False.
     */
    public function justquery($sql, $params = array()) {
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
                return true;
            } else {
                $this->_error = true;
            }
        } else {
            $this->_error = true;
        }
        return false;
    }
    
    /**
     * Gibt einen Datenstatz zurueck, der zuvor mit {@link justquery()} 
     * abgerufen wurde.
     * @return type Datensatz als Objekt falls vorhanden sonst False.
     */
    public function fetch() {
        return $this->_query->fetchObject();
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

    /**
     * Holt einen Eintrag aus der Datenbank.
     * 
     * @param string $table Tabelle aus der der Eintrag geholt werden muss
     * @param array $where array mit (spaltenname, operator, wert) Paaren
     * @return boolean ob erfolgreich oder nicht
     */
    public function get($table, $where) {
        return $this->action('SELECT *', $table, $where);
    }

    /**
     * Loescht einen Eintrag aus der Datenbank.
     * 
     * @param string $table die Tabelle in der gelöscht werden soll
     * @param array $where array mit (spaltenname, operator, wert) Paaren
     * @return boolean ob erfolgreich oder nicht
     */
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

    /**
     * Updated ein Eintrag in der Datenbank.
     * 
     * @param string $table Tabelle in der ein Wert geupdated werden muss
     * @param int $id ID des zu updatenden Eintrags
     * @param array $fields die Spalten die geupdated werden muessen
     * @return boolean ob Erfolg oder nicht
     */
    public function update($table, $id, $fields) {
        $set = $this->prepareArray($fields, ',');
        
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
     * @param type $table Name der Tabelle in welcher der Datensatz liegt.
     * @param type $fields Array mit Felder für WHERE oder INSERT,
     * wobei KEY = Feldname und VALUE = Wert z.B. array['metaname'] = 'Datum'.
     */
    public function getIdBySelectOrInsert($table, $fields) {
        $where = $this->prepareArray($fields, "and");
        
        $sql = "SELECT id FROM {$table} WHERE {$where}";
        $this->query($sql, $fields);
        if (!$this->error() and $this->count()) {
            return $this->first()->id;
        } else {
            if ($this->insert($table, $fields)) {
                return $this->_pdo->lastInsertId();
            }
        }
        
        return false;
    }
    
    /**
     * Legt einen Datensatz an oder updated ihn (falls schon vorhanden) mit den 
     * Daten aus $fields.
     * In $fields stehen dabei die Felder und dazugehoerigen Werte als array.
     * Per $serachFields kann die Existens des Datenstatzes mit andern Feldern
     * geprueft werden. Angelegt wird aber weiterhin mit $fields.
     * @param type $table Name der Tabelle in welcher der Datensatz eigefuegt/geupdatet werden soll.
     * @param type $fields Array mit Felder für INSERT oder UPDATE,
     * wobei KEY = Feldname und VALUE = Wert z.B. array['metaname'] = 'Datum'.
     * @param type $searchFields Aehnlich wie $fields nur das mit diesen Feldern
     * beim SELECT geprueft wird, ob der Datensatz bereits existiert.
     * @return type Gibt die ID des Datenstatz zurueck oder -1 im Fehlerfall.
     */
    public function insertOrUpdate($table, $fields, $searchFields = null) {
        $result = -1;
        if (is_null($searchFields)) {
            $searchFields = $fields;
        }
        
        $where = $this->prepareArray($searchFields, "and");
        $sql = "SELECT id FROM {$table} WHERE {$where}";
       
        $this->query($sql, $searchFields);
        if(!$this->error() and $this->count()) {
            $id = $this->first()->id;
            if ($this->update($table, $id, $fields)) {
                $result = $id;
            }
        } else {
            if ($this->insert($table, $fields)) {
                $result = $this->_pdo->lastInsertId();
            }
        }
        
        return $result;
    }
    
    /**
     * Bereitet ein Array als WHERE oder SET Bedingung vor.
     * Alle Key => Value Paare werden gesplitte in :
     * Key = ? $delimiter.
     * So koennen die Felder per 'and' oder ',' getrennt werden.
     * 
     * Beispiel:
     * prepareArray(array( 'name' => 'Test', 'id' => '5', 'a' => b), 'and')
     * wird zu
     * "name = ? and id = ? and a = ?"
     * 
     * @param type $array Array mit Feldern mit Feldname => Wert
     * @param type $delimiter Trennzeichen fuer die Felder
     * @return string Vorbereiteter String
     */
    private function prepareArray($array, $delimiter) {
        $result = "";
        foreach ($array as $fieldname => $value) {
            if (!empty($result)) {
                $result .= " " . $delimiter . " ";
            }
            $result .= "{$fieldname} = ?"; 
        }
        
        return $result;
    }

    /**
     * Gibt die letzten Query-Ergebnisse zurück.
     * 
     * @return array mit den Query-Ergebnissen.
     */
    public function results() {
        return $this->_results;
    }

    /**
     * Gibt die erste Zeile des letzten Query-Ergebnisses zurück.
     * 
     * @return array mit der Zeile
     */
    public function first() {
        $resultSet = $this->results();
        return $resultSet[0];
    }

    /**
     * Prüft ob in dem letzen Query ein Fehler aufgetreten ist.
     * 
     * @return boolean true wenn kein Fehler
     */
    public function error() {
        return $this->_error;
    }
    
    /**
     * Die Anzahl der letzten Query-Anfrage
     * 
     * @return int mit der Anzahl an zurueckgegebenen Zeilen
     */
    public function count() {
        return $this->_count;
    }
}
