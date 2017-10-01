<?php
include_once 'libs/Item.php';

class ItemEx extends Item {
    const laInsert =  1;
    const laDelete = -1;
    const laUpdate =  2;
    
    const flImplicit = 0x02;
    
    protected $_arrayvalues = array();
    protected $_arraytypes = array();
    protected $_arrayflags = array();
    protected $_linksact = array();
    protected $_linkflags = array();

    public static function getparents($ids) {
        $db = DataBase::$db;
        $t1 = Tables::items;
        $where = false;
        if (!is_array($ids)) $where = intval($ids);
        else
        {
            foreach ($ids as $id) {
                if ($where===false) $where = "$id";
                else $where .= ",$id";
            }
        }
        $q = $db->query("SELECT id,parentid FROM $t1 WHERE id IN ($where)");
        $out = array();
        while($r = $q->fetch_row()) {
            $out[$r[0]] = $r[1];
            unset($ids[$r[0]]);
        }
        foreach($ids as $id) $out[$id] = false;
        $q->free();
        return $out;
    }
    public function getarray($attrid,$attrtype=false) {
        if (isset($this->_arraytypes[$attrid])) return $this->_arrayvalues[$attrid];
        if (!$attrtype) return DError::raise (self::eUnreadValue, "arrayvalue $attrid unread");
        $db = DataBase::$db;
        $tta = Tables::$itemtypesarray;
        $req = "SELECT `index`,value FROM {$tta[$attrtype]} WHERE itemid={$this->id} AND attrid={$attrid}";
        $q = $db->query($req);
        $this->_arraytypes[$attrid] = $attrtype;
        $this->_arrayvalues[$attrid] = array();
        while($r = $q->fetch_row()) {
            $this->_arrayvalues[$attrid][$r[0]] = VT::convertget($attrtype, $r[1]);
        }
        $q->free();
        return $this->_arrayvalues[$attrid];
    }
    public function setarray($attrid,$type,$value) {
        if (!is_array($value)) return DError::raise (self::eWrongArgument, '$value is not array');
        if (!isset($this->_arraytypes[$attrid])) {
            $this->_arrayflags[$attrid] = Item::vfTypeChanged;
            $this->_arraytypes[$attrid] = $type;
            $this->_arrayvalues[$attrid] = $value;
        }
        if ($this->_arraytypes[$attrid]!=$type) {
            $this->_arrayflags[$attrid] = Item::vfTypeChanged;
            $this->_arraytypes[$attrid] = $type;
            $this->_arrayvalues[$attrid] = $value;
        }
        elseif ($this->_arrayvalues[$attrid] != $value) {
            $this->_arrayflags[$attrid] = Item::vfValChanged;
            $this->_arrayvalues[$attrid] = $value;
        }
        return true;
    }
    private function _updatearray_db($attrid,$attrtype,$db) {
        $table = Tables::$itemtypesarray[$attrtype];
        $db->query("DELETE FROM $table WHERE itemid={$this->id} AND attrid={$attrid}");
        foreach ($this->_arrayvalues[$attrid] as $key=>$value) {
            $dbv = VT::totypedb($attrtype, $value);
            if (!is_numeric($key)) continue;
            $db->query("INSERT INTO $table (itemid,attrid,`index`,value) VALUES($this->id, $attrid, $key, $dbv)");
        }
    }
    // ToDo override write()
    public function write($rewritevalues=false) {
        $db = DataBase::$db;
        /*if (($this->_tempflags&self::tfLinkChange)>0) {
            $linkcnt = $this->linkcount();
            if ($linkcnt>0) $this->flags |= self::fLinkMaster;
            else $this->flags &= ~self::fLinkMaster;
        }*/
        if (!parent::write($rewritevalues)) return false;
        foreach ($this->_arrayflags as $attrid => $flag) {
            if ($flag==self::vfTypeChanged||$flag==self::vfValChanged) $this->_updatearray_db($attrid, $this->_arraytypes[$attrid], $db);
        }
        //if (($this->_tempflags&self::tfLinkChange)>0) $this->_linkwrite();
        return true;
    }
    /*********************************************************************/
    /*----------------------------- LINKS -------------------------------*/
    /*********************************************************************/
    public function readlinks($types,$orderbydata=false) {
        if (isset($this->_links[$types])) return;
        $db = DataBase::$db;
        $table = Tables::itemlink;
        if (!is_array($types)) $types = array($types);
        $wtype = 'type in ('.implode(',', $types).')';
        $req = "SELECT type,slave,flags,userdata FROM $table WHERE master={$this->id} AND {$wtype}";
        if ($orderbydata) $req .= ' ORDER BY userdata';
        $q = $db->query($req);
        while($r = $q->fetch_row()) {
            $this->_links[$r[0]][$r[1]] = $r[3];
            $this->_linkflags[$r[0]][$r[1]] = $r[2];
        }
        foreach($types as $type) 
            if (!isset($this->_links[$type])) $this->_links[$type] = array();
        $this->_tempflags |= self::tfLinkReaded;
        return;
    }
    public static function linkgetmsters(Item $item,$type,$implicit=false) {
        if (!$item->id) return DError::raise (Item::eUnset, 'Item unset');
        $table = Tables::itemlink;
        $req = "SELECT master,userdata FROM $table WHERE slave={$item->id} AND type=$type";
        if (!$implicit) $req .= ' AND flags&2=0';
        //echo '<br><br>Request:'.$req.'<br>'; /********/
        $q = DataBase::$db->query($req);
        $out = array();
        while($r = $q->fetch_row()) $out[$r[0]] = $r[1];
        return $out;
    }
    private function _linkcheckslaves() {
        $ids = array();
        foreach($this->_links as $slaves) {
            foreach($slaves as $slaveid=>$act) {
                $ids[$slaveid] = 1;
            }
        }
        if (count($ids)==0) return $ids;
        $dbids = implode(',', array_keys($ids));
        $db = DataBase::$db;
        $itemtable = Tables::items;
        $q = $db->query($t = "SELECT id FROM $itemtable WHERE id in ($dbids)");
        while($r = $q->fetch_row()) $ids[$r[0]] = 2;
        return $ids;
    }
    public function linkadd($type,$slaveid,$userdata,$implicit=false) {
        if ($slaveid==0) return DError::raise(0, 'Slave unset');
        if (($this->_tempflags&self::tfLinkReaded)==0) return DError::raise (self::eUnreadValue, 'links unreaded');
        if (isset($this->_links[$type][$slaveid])&&($this->_links[$type][$slaveid]!=$userdata))
            $this->_linksact[$type][$slaveid] = self::laUpdate;
        else
            $this->_linksact[$type][$slaveid] = self::laInsert;
        $this->_links[$type][$slaveid] = $userdata;
        $this->_linkflags[$type][$slaveid] = $implicit?2:0;
        $this->_tempflags |= self::tfLinkChange;
        return true;
    }
    public function linkremove($type,$ids) {
        if (($this->_tempflags&self::tfLinkReaded)==0) return DError::raise(self::eUnreadValue, 'links unreaded');
        if (!is_array($ids)) $ids = array($ids);
        foreach($ids as $id) 
            if (isset($this->_links[$type][$id])) {
                $this->_linksact[$type][$id] = self::laDelete;
                unset($this->_links[$type][$id]);
            }
        $this->_tempflags |= self::tfLinkChange;
    }
    public function linksget($type) {
        return $this->_links[$type];
    }
    public function linkisset($slaveid,$type) {
        if (($this->_tempflags&self::tfLinkReaded)==0) return DError::raise(self::eUnreadValue, 'links unreaded');
        return isset($this->_links[$type][$slaveid])&&($this->_links[$type][$slaveid]==1);
    }
    public function linkcount() {
        if (($this->_tempflags&self::tfLinkReaded)==0) return DError::raise(self::eUnreadValue, 'links unreaded');
        $cnt = 0;
        foreach($this->_links as $slaves)
            foreach($slaves as $action)
                if ($action>0) $cnt++;
        return $cnt;
    }
    private function _linkwrite() {
        /*********** DEBUG START ************/
//        echo '<br>_links:<br>';
//        var_dump($this->_links);
//        echo '<br><br>_linksact:<br>';
//        var_dump($this->_linksact);
        /*********** DEBUG END **************/
        if (($this->_tempflags&self::tfLinkReaded)==0) return DError::raise(self::eUnreadValue, 'links unreaded');
        //if ($this->id<=0) return DError::raise (self::eUnset, );
        $db = DataBase::$db;
        $table = Tables::itemlink;
        $itemtable = Tables::items;
        //$exists = $this->_linkcheckslaves();
        foreach($this->_linksact as $type=>$slaves) {
            $dbi = false;
            $dbd = false;
            $dbu = '';
            $dbs = false;
            foreach($slaves as $slave=>$action) {
                if ($action==self::laInsert) {
                    $data = $this->_links[$type][$slave];
                    $flags = $this->_linkflags[$type][$slave];
                    if (!$dbi) $dbi = "({$this->id},$slave,$flags,$type,$data)";
                    else $dbi .= ",({$this->id},$slave,0,$type,$data)";
                    if (!$dbs) $dbs = "$slave";
                    else $dbs .= ",$slave";
                }
                elseif($action==self::laUpdate) {
                    $data = $this->_links[$type][$slave];
                    $flags = $this->_linkflags[$type][$slave];
                    $dbu .= "UPDATE $table SET userdata={$data},flags={$flags} WHERE master={$this->id} AND type={$type} AND slave={$slave};";
                }
                elseif($action==self::laDelete) {
                    if (!$dbd) $dbd = "(slave=$slave)";
                    else $dbd .= "OR(slave=$slave)";
                }
            }
            $flslave = self::fLinkSlave;
            //$t1=$t2=$t3='';
            if ($dbs) $db->query($t1 = "UPDATE $itemtable SET flags = flags | $flslave WHERE id in ($dbs)");
            if ($dbi) $db->query($t2 = "INSERT IGNORE INTO $table(master,slave,flags,type,userdata) VALUES {$dbi}");
            if ($dbd) $db->query($t3 = "DELETE FROM $table WHERE master={$this->id} AND type=$type AND({$dbd})");
            if ($dbu) $db->multiquery($dbu);
            //echo "<br><br>Queries:$t1<br>$t2<br>$t3<br>$dbu<br>";
        }
    }
}

class ItemHierachy {
    public $id;
    public $name;
    public $child = false;
    private static function _getHierarchyRec($type,$pid,$order) {
        $req = ItemRequest::c_type($type, array(10=>VT::String), $order?array(1=>0):false);
        $req->parent = $pid;
        $vals = $req->getValues();
        if (count($vals)==0) return false;
        $out = array();
        foreach($vals as $id=>$val) {
            $x = new self;
            $x->id = $id;
            $x->name = $val;
            $x->child = self::_getHierarchyRec($type, $id, $order);
            $out[] = $x;
        }
        return $out;
    }
    public static function getHierachy($type,$order=false) {
        return self::_getHierarchyRec($type, 0, $order);
    }
}