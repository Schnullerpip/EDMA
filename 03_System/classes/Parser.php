<?php
/**
 * Description of Parser
 *
 * @author Philipp
 */
class Parser {
    
    private $_file;
    private $db;
    
    public function __construct($file) {
        $this->_file = $file;
        $this->db = new DB();
        $this->parse();
    }
    
    private function parse() {
        $stringFile = file_get_contents($this->_file);
        $stringFile = str_replace("\r", "", $stringFile);
        $file_array = explode("###", $stringFile);
        $metadaten = $file_array[0];
        $messdaten = $file_array[1];
        
        $this->parseMetaDaten($metadaten);
        $this->parseMessDaten($messdaten);
        
        $metaNamePattern = "([\p{L}\p{N} \x{00B0}\x{0025}\-]+)_([dns])";
        $stringPattern = "((?<=s:\t)[\p{L}\p{N}\. ,/\-]+)";
        $datePattern = "((?<=d:\t)\p{N}{2}\.\p{N}{2}\.\p{N}{4})";
        $numberPattern = "((?<=n:\t)[,\p{N}\.-]+)";
        $pattern = "#^" . $metaNamePattern . ":\t(" . $stringPattern . "|" . $datePattern . "|" . $numberPattern . ")$#um";
    }
    
    private function parseMetaDaten($metadaten) {
        $meta_array = explode(PHP_EOL, $metadaten);
        $meta_spalten = array();
        $elem_row = count($meta_array);
        for ($i = 0; $i < $elem_row; $i++) {
                $tmp_row = preg_split("/\t+/", $meta_array[$i]);
                array_push($meta_spalten, $tmp_row);
        }
        
        $query = "INSERT INTO messreihe (messreihenname, datum, projekt_id) "
                . "VALUES ('','','(SELECT id FROM projekt WHERE projektname='Testprojekt zum Testen')')";
        
        $table = "messreihe";
        $fields = null;
        $this->db->insert($table, $fields);
    }
    
    private function parseMessDaten($messdaten) {
        $mess_array = explode(PHP_EOL, $messdaten);
        $mess_array = array_slice($mess_array, 1);	// array_slice() deletes the first element, because it is empty
        $mess_spalten = array();
        $spaltennamen = preg_split("/\t+/", $mess_array[0]);
        $elem_col = count($spaltennamen);
        for ($i = 0; $i < $elem_col; $i++) {
                array_push($mess_spalten, array($spaltennamen[$i]));
        }
        $mess_array = array_slice($mess_array, 1);	// delete row of names, do not need anymore
        foreach ($mess_array as $mess_row) {
                $tmp_row = preg_split("/\t+/", $mess_row);
                for ($i = 0; $i < $elem_col; $i++) {
                        array_push($mess_spalten[$i], $tmp_row[$i]);
                }
        }
        
        
    }
    
    private function check() {
        
    }


    public function errors() {
        return true;
    }
}
