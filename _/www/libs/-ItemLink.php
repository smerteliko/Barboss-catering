<?php

class ItemLink /*extends Item*/ {
    /*---------- Info / ToDo --------------*/
    // Пока есть конфликты между чтением и записью, и пока не ясно, как их легко разрешить
    // Используется отложенная запись, чтобы незаписанные item, которые пока без id, были бы записаны до записи ссылок
    //
    /*---------- Static fields ------------*/
    protected static $newlinks = array();
    protected static $removelinks = array();
    /*---------- Link properties ----------*/
    public $type;
    public $flags = 0;
    public $userdata = 0;
    public $masterid;
    public $slaveid;
    
    const typeAny = -1; /* любой тип */
    
    public static function getlistbymaster($masterid,$type) {
        $db = DataBase::$db;
        $table = Tables::itemlink;  
        $req = "SELECT slave,flags,userdata FROM $table WHERE master={$masterid}";
        if ($type!=-1) $req .= " AND type=$type";
        $q = $db->query($req);
        $out = array();
        while($r = $q->fetch_row()) {
            $x = new self;
            $x->type = $type;
            $x->masterid = $masterid;
            $x->slaveid = $r[0];
            $x->flags = $r[1];
            $x->userdata = $r[2];
            $out[$r[0]] = $x;
        }
        $q->free();
        return $out;
    }
    
    public static function getlistbyslave($slaveid,$type) {
        $db = DataBase::$db;
        $table = Tables::itemlink;
        $req = "SELECT master,flags,userdata FROM $table WHERE slave={$slaveid}";
        if ($type!=-1) $req .= " AND type=$type";
        $q = $db->query($req);
        $out = array();
        while($r = $q->fetch_row()) {
            $x = new self;
            $x->type = $type;
            $x->masterid = $r[0];
            $x->slaveid = $slaveid;
            $x->flags = $r[1];
            $x->userdata = $r[2];
            $out[$r[0]] = $x;
        }
        $q->free();
        return $out;
    }
    
    public static function getallmastersintype($type) {
        $db = DataBase::$db;
        $table = Tables::itemlink;
        $req = "SELECT disctinct master FROM $table WHERE type=$type";
        $q = $db->query($req);
        $out = array();
        while($r = $q->fetch_row()) {
            $out[] = $r[0];
        }
        $q->free();
        return $out;
    }
    
    public static function link($master, $slave,$type,$userdata,$flags) {
        self::$newlinks[] = array($master,$slave,$type,$userdata,$flags);
        if ($master instanceof  Item) $master->flags |= Item::fLinkMaster;
        if ($slave instanceof Item) $slave->flags |= Item::fLinkSlave;
        Item::$_statflags |= Item::sfLink;
        return true;
    }
    public static function unlink($masterid,$slaveid,$type) {
        self::$removelinks[] = array($masterid,$slaveid,$type);
        Item::$_statflags |= Item::sfLink;
    }
    public static function update($masterid,$slaveid,$type,$userdata,$flags) {
        $db = DataBase::$db;
        $table = Tables::itemlink;
        $db->query("UPDATE $table SET userdata={$userdata},flags={$flags} WHERE master={$masterid} AND slave={$slaveid} AND type={$type}");
    }
    
    public static function writeall() {
        $db = DataBase::$db;
        $table = Tables::itemlink;
        foreach(self::$removelinks as $remove) {
            $db->query("DELETE FROM $table WHERE master={$remove[0]} AND slave={$remove[1]} AND type={$remove[2]}");
        }
        foreach(self::$newlinks as $link) {
            if ($link[0] instanceof Item) $masterid = $link[0]->id;
            elseif (is_numeric ($link[0])) $masterid = $link[0];
            else {DError::raise(0, 'wrong master type!');continue;}
            if (!$masterid) {DError::raise(0, 'master unset!');continue;}
            if ($link[1] instanceof Item) $slaveid = $link[1]->id;
            elseif (is_numeric ($link[1])) $slaveid = $link[1];
            else {DError::raise(0, 'wrong slave type!');continue;}
            if (!$slaveid) {DError::raise(0, 'slave unset!');continue;}
            $db->query("INSERT INTO $table(master,slave,type,userdata,flags) VALUES({$masterid},{$slaveid},{$link[2]},{$link[3]},{$link[4]})"
            ."ON DUPLICATE KEY UPDATE userdata={$link[3]},flags={$link[4]}");
        }
    }
    
    public static function updateflags(Item $item) {
        if (!$item->isvalid()) return DError::raise (Item::eUnset, "Item unset");
        $db = DataBase::$db;
        $table = Tables::itemlink;  
        $q1 = $db->query("SELECT count(*) FROM $table WHERE master={$item->id}");
        $r1 = $q1->fetch_row();
        $item->setflags(Item::fLinkMaster, $r1[0]>0);
        $q1->free();
        $q2 = $db->query("SELECT count(*) FROM $table WHERE slave={$item->id}");
        $r2 = $q2->fetch_row();
        $item->setflags(Item::fLinkSlave, $r2[0]>0);
        $q2->free();
    }
}

//register_shutdown_function("ItemLink::writeall");