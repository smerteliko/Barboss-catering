<?php
class DataBase
{
    public $mysqli; /*@var $mysqli mysqli*/
    public $error;
    public $dbname;
    public $generateexceptions;
    public static $db; /*var $db self*/
    public function connect($server,$user,$dbname,$dbpass,$charset) {
        $this->dbname = $dbname;
        $mysqli = new mysqli($server, $user, $dbname, $dbpass);
        if ($mysqli->connect_errno>0)
            if ($this->generateexceptions) throw new Exception ('db error: '.$mysqli->connect_error);
            else {$this->error = $mysqli->connect_error;return false;}
        $mysqli->set_charset($charset);
        $this->mysqli = $mysqli;
        return true;
    }
    public function query($query) {
        $result = $this->mysqli->query($query);
        if (!$result) {
            if ($this->generateexceptions) throw new Exception ("\ndb error: {$this->mysqli->error}\n\"$query\"");
            else $this->error = $this->mysqli->error;
        }
        else $this->error = '';
        return $result;
    }
    public function multiquery($query) {
        $this->mysqli->multi_query($query);
        $result = array();
        $n = 0;
        do {
            if ($n>0) $this->mysqli->next_result(); 
            $t = $this->mysqli->store_result();
            if ($t) {
                $x = array();
                while($r = $t->fetch_row()) {$x[] = $r;}
                $t->free();
                $result[] = $x;
            }
            else $result[] = false;
            $n++;
        } while($this->mysqli->more_results());
        return $result;
    }
    public function insert($query) {
        if (!$this->mysqli->query($query)) {
            if ($this->generateexceptions) throw new Exception ("\ndb error: {$this->mysqli->error}\n\"$query\""); 
            else { $this->error = $this->mysqli->error; return false;}
        }
        return $this->mysqli->insert_id;
    }
    public function escape($src) {
        return '"'.$this->mysqli->escape_string($src).'"';
    }   
}

