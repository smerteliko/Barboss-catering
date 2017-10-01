<?php
///////////////////////////////////
//// (C) 2016, Dmitry Poletaev
///////////////////////////////////
//// Debug version
//// Version C
//// Revision 10.01.2016
///////////////////////////////////

class ItemType
{
    public $table;
    public $type;
    public $isindexed;
    public static function create($table,$type,$isindexed) {
        $x = new self;
        $x->table = $table; $x->type=$type; $x->isindexed = $isindexed;
        return $x;
    }
}

class ItemUtils
{
    public static function getallkeys() {
        $db = DataBase::$db;
        $t1 = Tables::items;
        $q = $db->query("SELECT distinct(type) FROM $t1");
        $out = array();
        while($r = $q->fetch_row()) {
            $out[] = $r[0];
        }
        return $out;
    }
    public static function tables_exists() {
        $db = DataBase::$db;
        $t1 = Tables::items;
        $q = $db->query("SHOW TABLES LIKE '$t1'");
        $r = $q->fetch_row();
        $q->free();
        if ($r) return true;
        else return false;
    }
    public static function create_tables() {
        $db = DataBase::$db;
        $titem = Tables::items;
        $tables = TableCreate::itemtables();
        
        $db->query("CREATE TABLE IF NOT EXISTS $titem (id INT primary key auto_increment,flags int,type int,v1 int, v2 int, v3 int, v4 int, dtupdate timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, dtcreate timestamp, INDEX(v1),INDEX(v2),INDEX(type))");
        foreach ($tables as $table) {
            $tname = $table->table;
            $ttype = $table->type;
            if($table->isindexed) $indexed = ',INDEX(value)';
            else $indexed = '';
            $db->query("CREATE TABLE IF NOT EXISTS $tname (itemid int, attrid smallint, value $ttype, PRIMARY KEY(itemid,attrid),INDEX(itemid){$indexed})");
        }
        foreach (TableCreate::arraytables() as $table) {
            $tname = $table->table;
            $ttype = $table->type;
            if($table->isindexed) $indexed = ',INDEX(value)';
            else $indexed = '';
            $db->query("CREATE TABLE IF NOT EXISTS $tname (itemid int, attrid smallint, `index` smallint, value $ttype, PRIMARY KEY(itemid,attrid,`index`),INDEX(itemid){$indexed})");
        }
        if (defined('Tables::itemlink')) {
            $table = Tables::itemlink;
            $db->query("CREATE TABLE IF NOT EXISTS $table (master INT, slave INT,flags SMALLINT,type SMALLINT, userdata INT, PRIMARY KEY(master,slave,type), INDEX(master,type))");
        }
    }
    public static function truncate() {
        $db = DataBase::$db;
        $titem = Tables::items;
        $tables = Tables::$itemtypes;
        
        $db->query("TRUNCATE TABLE $titem");
        foreach($tables as $table) $db->query("TRUNCATE TABLE $table");
        if (defined("Tables::itemlink")) $db->query("TRUNCATE TABLE ".Tables::itemlink);
    }
}