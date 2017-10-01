<?php
//---------------------------------------------------------
//--------------- (C) Dmitry Poletaev 2015 ----------------
//---------------------------------------------------------
class DList
{
    public static function getcat($cat) {
        $db = DataBase::$db;
        $t1 = Tables::lists;
        
        $out = array();
        $q = $db->query("SELECT key,value FROM $t1 WHERE cat=$cat");
        while($r = $q->fetch_row())
        {
            $out[$r[0]] = $r[1];
        }
        $q->free();
        return $out;
    }
    private static function _getarray($db,$table,$ids,$cat) {
        $cnt = count($ids);
        if ($cnt==0) return array();
        $dbids = implode(',', $ids);
        foreach($ids as $id) $ids2[$id] = 1;
        $q = $db->query("SELECT `key`,`value` FROM $table WHERE `key` IN ($dbids) AND cat=$cat");
        $out = array();
        while($r = $q->fetch_row()) {
            $out[$r[0]] = $r[1];
            unset($ids2[$r[0]]);
        }
        foreach($ids2 as $key=>$value) $out[$key] = '';
        $q->free();
        return $out;
    }
    public static function get($cat,$keys) { 
        $db = DataBase::$db;
        $t1 = Tables::lists;
        if (is_array($keys)) return self::_getarray ($db,$t1,$keys,$cat);
        $q = $db->query("SELECT `value` FROM $t1 WHERE cat=$cat AND `key`=$keys LIMIT 1");
        $r = $q->fetch_row();
        $q->free();
        if (!$r) return '';
        return $r[0];
    }
    public static function update($cat,$key,$value) {
        $db = DataBase::$db;
        $t1 = Tables::lists;
        $dbvalue = $db->escape($value);
        $db->query("INSERT INTO $t1(cat,`key`,`value`) VALUES($cat,$key,$dbvalue) ON DUPLICATE KEY UPDATE `value`=VALUES(value)");
    }
}

class DListUtils 
{
    public static function tables_exists() {
        $db = DataBase::$db;
        $t1 = Tables::lists;
        $q = $db->query("SHOW TABLES LIKE '$t1'");
        $r = $q->fetch_row();
        $q->free();
        if ($r) return true;
        else return false;
    }
    public static function create_tables() {
        $db = DataBase::$db;
        $t1 = Tables::lists;
        $db->query("CREATE TABLE IF NOT EXISTS $t1 (cat smallint unsigned, `key` int, `value` varchar(512),primary key(cat,`key`),index(cat))");
    }   
}