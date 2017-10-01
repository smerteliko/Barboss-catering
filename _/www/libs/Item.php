<?php
///////////////////////////////////
//// (C) 2016, Dmitry Poletaev
///////////////////////////////////
//// Debug version
//// Version C
//// Revision 25.03.2016
///////////////////////////////////
include_once 'libs/BaseClass.php';
include_once 'libs/ItemRequest.php';

class VT
{
    const None         = 0;
    const Int          = 1;
    const Double       = 2;
    const String64     = 10;
    const String256    = 11;
    const String       = 11;    //alias
    const StringLong   = 12;
    
    public static function isValid($type) {
        return ($type>=0 && $type<3)||($type>=10&&$type<13);
    }
    public static function isString($type) {
        return $type>=10 && $type<13;
    }
    public static function convertget($type,$value) {
        switch($type) {
            case 0: return null;
            case 1: return intval($value);
            case 2: return doubleval($value);
            default: return $value;
        }
    }
    public static function totypedb($typeid,$value) {
        switch($typeid) {
            case 0: return false;
            case 1: return intval($value);
            case 2: return doubleval($value);
            case 10:
            case 11:
            case 12:
                return DataBase::$db->escape($value);
            default: return DError::raise('Wrong atribute type', DError::levError);
        }
    }
}

class Item extends baseclass {
    protected static $items = array();
    public static $keys = array();
    public static $_statflags = 0;
    //----------------- fields -----------------
    public $id=0;
    public $flags=0;
    public $type=false;
    public $dtcreate;
    public $dtupdate;
    public $v1=0,$v2=0,$v3=0,$v4=0;
    //----------------- states -----------------
    //protected $sAVR = false;
    //-------------- state flags ---------------
    const fEnabled    = 0x01;
    const fV2Parent   = 0x02;
    const fMarkDelete = 0x04;
    const fHasChild   = 0x08;
    const fHasFiles   = 0x10;
    const fLocked     = 0x20;
    const fDraft      = 0x40;
    //const fLinkMaster = 0x100;
    //const fLinkSlave  = 0x200;
    const fLink       = 0x100;
    
    const tfValChange  = 0x001;
    const tfChanged    = 0x002;
    const tfAutoWrite  = 0x010;
    const tfAVR        = 0x020;
    //const tfLinkChange = 0x100;
    //const tfLinkReaded = 0x200;
    const tfResolvedLink = 0x100;
    //const tfDeleted   = 0x20;
    const vfValChanged  = 0x01;
    const vfTypeChanged = 0x02;
    const cfWrite     = 0x10;
    const cfAutoWrite = 0x20;
    const cfOnlyExistedTypes = 0x40;
    //------------------------
    const sfLink = 0x01;
    //----------------- values -----------------  
    protected $_values=array();
    protected $_valtypes=array();
    protected $_valflags=array();
    protected $_tempflags = 0;
    //----------------- links ------------------
    /*protected $_links = array();
    protected $_linksact = array();
    protected $_linkflags = array();*/
    //protected $_links = array();
    //protected $_linkflags = array();
    /* --------- READ FLAGS ---------*/
    const rfAsKeyArray = 0x10;
    const rfAutoWrite  = 0x20;
    const rfForceRead  = 0x40;
    const rfAutoType   = 0x80;
    /* ------------------------------*/
    const rvName = 0x01;
    const rvNameCache = 0x02;
    const rvAll  = 0x10;     // прочитать все значения
    /* --------- ERRORS -------------*/
    const eWrongItemType    = 0x1101;
    const eUnreadValue      = 0x1102;
    const eWrongValueType   = 0x1103;
    const eItemLocked       = 0x1104;
    const eTypeUnset        = 0x1105;
    //const eAborted          = 0x1005;
    /*----------- SOME DEFAULT VALUES ------*/
    const Name  =  10;
    const Cache = 100;
    /*------------ READ FLAGS -----------*/
    //const rfCheckType = 0x10;
    //---------------- FUNCTIONS ---------------
    /*-------------- FOR OVERRIDE -----------------*/
    public static function defType() {return false;}
    public static function defValues() {return false;}
    public static function defFlags() {return 0;}
    public static function getattrtype($attrtype) {
        switch($attrtype) {
            case  10: return 11;
            case 100: return 12;
            default : return 0;
        }
    }
    public static function getTypeName() {return 'Объект';}
    public static function getEditorClass() {return false;}

    public function onDelete() {return true;}
    public function onSessionStop() {}
    /**************************************************/
    /*------------------ CREATE ----------------------*/
    /*------------------------------------------------*/
    /** @return Item */
    public static function create($cflags=0,$type=false) {
        if ($type&&isset(self::$keys[$type])) $classname = self::$keys[$type];
        elseif (($cflags&self::cfOnlyExistedTypes)>0) return DError::raise(0, "type $type not existed");
        else $classname = get_called_class();
        $item = new $classname(); /*@var $item self */
        if (!$type) $type = call_user_func(array($classname, 'defType'));
        if (!$type) return DError::raise (self::eTypeUnset, 'Type undefined');
        $flags = call_user_func(array($classname, 'defFlags'));
        $item->type = $type;
        $item->flags = $flags;
        $item->dtcreate = time();
        $item->_tempflags |= self::tfAVR;
        if (($cflags&self::cfWrite)>0) {$item->write();}
        if (($cflags&self::cfAutoWrite)>0) {
            if ($item->vstate==0) $item->write();
            $item->_tempflags |= self::tfAutoWrite;
        }
        return $item;
    }
    /************************************************/
    /*------------------ READ ----------------------*/
    /*----------------------------------------------*/
    /** @return Item **/
    public static function read($itemid,$values,$flags=0,$type=false) {
        if ($type&&isset(self::$keys[$type])) $classname = self::$keys[$type];
        else $classname = get_called_class();
        $itemid = intval($itemid);
        if (is_array($values)) $vls = $values;
        else {
            if ($values==self::rvName) $vls = array(10=>11);
            elseif($values==self::rvNameCache) $vls = array(10=>11,100=>12);
            else $vls = false;
        }
        if (($flags&self::rfForceRead)!=0||!isset(self::$items[$itemid])) {
            $item = self::requestsingle($itemid,$vls,$classname,$flags); /*@var $item self */
            if (!($item instanceof Item)) return $item;
            self::$items[$itemid] = $item;
        }
        else $item = self::$items[$itemid];
        if ($values == self::rvAll) $item->readvalues();
        if (($flags&self::rfAutoWrite)>0) $item->_tempflags |= self::tfAutoWrite;
        return $item;
    }
//    public static function readautotype($itemid,$values,$flags=0) {
//        if (is_array($values)) $vls = $values;
//        else $vls = false;
//        if (($flags&self::rfForceRead)!=0||!isset(self::$items[$itemid])) {
//            $item = self::getsingle($itemid,$vls,'Item',$flags); /*@var $item self */
//            if (!($item instanceof Item)) return $item;
//            self::$items[$itemid] = $item;
//        }
//        else $item = self::$items[$itemid];
//        if ($values == self::rvAll) $item->readvalues();
//        if (($flags&self::rfAutoWrite)>0) $item->_tempflags |= self::tfWrite;
//        return $item;
//    }
    public static function getlist(ItemRequest $request,$classname=false,$flags=0) {
        if (self::$debug) {$request->check();}
        
        $autotype = ($flags&self::rfAutoType)>0;
        $db = DataBase::$db;        
        $out = array();
        if (!$autotype) {
            if (!$classname) $classname = get_called_class();
            $type = call_user_func(array($classname, 'defType'));
        }
        else $type = false;
        if ($request->idlist&&($flags&self::rfForceRead)==0) foreach($request->idlist as $key=>$id) {
            if (isset(self::$items[$id])) {
                $x = self::$items[$id];
                if (!$autotype&&$type&&$x->type!=$type) $out[$id] =  DError::raise(self::eWrongItemType, 'Wrong type', DError::levError,$flags);
                else $out[$id] = $x;
                unset($request->idlist[$key]);
            }
        }
        if (!$autotype&&$type) {$request->type=$type;}
        $req = $request->get();
        if ($req===false) return $out;
        $q = $db->query($req);
        $types = $request->values;
        $fl1 = ($flags&self::rfAsKeyArray)>0;
        $fl2 = $fl1 && $request->idlist;
        if ($fl2) foreach($request->idlist as $id) $ids[$id] = 1;
        while ($r = $q->fetch_row()) {
            $type = intval($r[2]); /* @var $x Item */
            if ($autotype) {
                if (isset(self::$keys[$type])) {$cn = self::$keys[$type]; $x = new $cn();}
                else $x = new $classname();
            }
            else $x = new $classname(); 
            $x->id=intval($r[0]);$x->flags=intval($r[1]);$x->type=$type;$x->dtupdate=intval($r[3]);
            $x->dtcreate = intval($r[4]);$x->v1=intval($r[5]);$x->v2=intval($r[6]);$x->v3=intval($r[7]);$x->v4=intval($r[8]);
            $x->vstate = self::vsSet;
            if ($types) {
                $i = 9;
                foreach($types as $id=>$type) {
                    $x->_values[$id] = VT::convertget($type, $r[$i]);
                    $x->_valtypes[$id] = $type;
                    $i++;
                }
            }
            if (($flags&self::rfAutoWrite)>0) $x->_tempflags |= self::tfAutoWrite;
            if ($fl2) unset($ids[$x->id]);
            if ($fl1) $out[$x->id] = $x;
            else $out[] = $x;
            self::$items[$x->id] = $x;
        }
        $q->free();
        if ($fl2&&$ids) foreach ($ids as $id) 
            $out[$id] = self::onEmpty($flags, $classname);
        return $out;
    }
//    public static function getsingle($id,$values=false,$classname=false,$flags=0) {
//        DError::raise(self::eDeprecated, "Deprecated", 5);
//        
//        $tables = Tables::$itemtypes;
//        $t0 = Tables::items;
//        $db = DataBase::$db;
//        
//        $req = "SELECT t0.id,t0.flags,type,UNIX_TIMESTAMP(dtupdate),UNIX_TIMESTAMP(dtcreate),v1,v2,v3,v4";
//        if ($values) foreach($values as $tid=>$type) $req .= ",t{$tid}.value";
//        $req .= " FROM $t0 as t0 ";
//        if ($values) 
//            foreach ($values as $tid => $type) {
//                $tt = $tables[$type];
//                $req .= "LEFT OUTER JOIN $tt as t{$tid} ON t{$tid}.itemid=t0.id AND t{$tid}.attrid=$tid ";
//            }
//        $req .= " WHERE id={$id} LIMIT 1";
//        $q = $db->query($req);
//        $r = $q->fetch_row();
//        if (!$r) return self::onEmpty($flags, $classname);
//        if (!$classname) $classname = get_called_class();
//        $type = call_user_func(array($classname, 'defType'));
//        if (!$type&&isset(self::$keys[$type])) {$cn = self::$keys[$type]; $x = new $cn();}
//        else $x = new $classname(); /* @var $x Item */
//        $x->type = intval($r[2]);
//        if ($type&&$x->type!=$type) return DError::raise (self::eWrongItemType, 'Wrong item type, id='.$id);
//        $x->id=intval($r[0]);$x->flags=intval($r[1]);$x->dtupdate=intval($r[3]);
//        $x->dtcreate = intval($r[4]);$x->v1=intval($r[5]);$x->v2=intval($r[6]);$x->v3=intval($r[7]);$x->v4=intval($r[8]);
//        $x->vstate = self::vsSet;
//        $i = 9;
//        if ($values) foreach($values as $id=>$type) {
//            $x->_values[$id] = VT::convertget($type, $r[$i]);
//            $x->_valtypes[$id] = $type;
//            $i++;
//        }
//        if (($flags&self::rfAutoWrite)>0) $x->_tempflags |= self::tfAutoWrite;
//        self::$items[$x->id] = $x;
//        $q->free();
//        return $x;
//    }
    
    private static function requestsingle($id,$values,$classname,$flags) {
        $tables = Tables::$itemtypes;
        $t0 = Tables::items;
        $db = DataBase::$db;
        
        $req = "SELECT t0.id,t0.flags,type,UNIX_TIMESTAMP(dtupdate),UNIX_TIMESTAMP(dtcreate),v1,v2,v3,v4";
        if ($values) foreach($values as $tid=>$type) $req .= ",t{$tid}.value";
        $req .= " FROM $t0 as t0 ";
        if ($values) 
            foreach ($values as $tid => $type) {
                $tt = $tables[$type];
                $req .= "LEFT OUTER JOIN $tt as t{$tid} ON t{$tid}.itemid=t0.id AND t{$tid}.attrid=$tid ";
            }
        $req .= " WHERE id={$id} LIMIT 1";
        $q = $db->query($req);
        $r = $q->fetch_row();
        if (!$r) return self::onEmpty($flags, $classname);
        $type = intval($r[2]);
        if (($flags&self::rfAutoType)>0) {
            if (isset(self::$keys[$type])) {
                $cn = self::$keys[$type]; $x = new $cn();
            }
            else DError::raise(0, "Type $type not found");
        }
        else {
            $checktype = call_user_func(array($classname, 'defType'));
            if ($checktype&&$checktype!=$type) return DError::raise (self::eWrongItemType, "Wrong item type ($type!=$checktype), item id=$id");
            $x = new $classname();
        }
        $x->id=intval($r[0]);$x->flags=intval($r[1]);$x->type=$type;$x->dtupdate=intval($r[3]);
        $x->dtcreate = intval($r[4]);$x->v1=intval($r[5]);$x->v2=intval($r[6]);$x->v3=intval($r[7]);$x->v4=intval($r[8]);
        $x->vstate = self::vsSet;
        $i = 9;
        if ($values) foreach($values as $id=>$type) {
            $x->_values[$id] = VT::convertget($type, $r[$i]);
            $x->_valtypes[$id] = $type;
            $i++;
        }
        if (($flags&self::rfAutoWrite)>0) $x->_tempflags |= self::tfAutoWrite;
        self::$items[$x->id] = $x;
        $q->free();
        return $x;
    }
    
    public static function getdraft($owner = false,$classname = false) {
        $db = DataBase::$db;
        $table = Tables::items;
        if (!$classname) $classname = get_called_class();
        $type = call_user_func(array($classname,'defType'));
        
        $flags = self::fTemplate;
        if ($owner) $flags |= self::fV2Parent;
        $req = "SELECT id,flags,type,UNIX_TIMESTAMP(dtupdate),UNIX_TIMESTAMP(dtcreate),v1,v2,v3,v4 FROM {$table} WHERE flags&{$flags}={$flags}";
        if ($type) $req .= " AND type={$type}";
        if ($owner) $req .= " AND v2={$owner}";
        $req .= ' ORDER BY id DESC LIMIT 1';
        $q = $db->query($req);
        $r = $q->fetch_row();
        if ($r) {
            $x = new $classname();
            $x->type = intval($r[2]);
            $x->id=intval($r[0]);$x->flags=intval($r[1]);$x->dtupdate=intval($r[3]);
            $x->dtcreate = intval($r[4]);$x->v1=intval($r[5]);$x->v2=intval($r[6]);$x->v3=intval($r[7]);$x->v4=intval($r[8]);
            $x->vstate = self::vsSet;
            $x->readvalues();
        }
        else {
            $x = call_user_func(array($classname,'create'));
            $x->flags |= self::fTemplate;
            $x->write();
        }
        return $x;
    }
    /***********************************************************/
    /************** READ VALUES AND STATES *********************/
    /***********************************************************/
    public function readvalues() {
        if ($this->vstate==0) {$this->_tempflags |= self::tfAVR;return;}
        $db = DataBase::$db;
        $tt = Tables::$itemtypes;
        $req = false;
        foreach($tt as $key=>$t) { 
            if ($req===false) $req = "SELECT attrid,value,$key as `type` FROM $t WHERE itemid={$this->id}";
            $req .= " UNION ALL SELECT attrid,value,$key as `type` FROM $t WHERE itemid={$this->id}";
        }
        $q = $db->query($req);
        while($r = $q->fetch_row()) {
            $this->_values[$r[0]] = VT::convertget($r[2], $r[1]);
            $this->_valtypes[$r[0]] = $r[2];
        }
        $q->free();
        $this->_tempflags |= self::tfAVR;
    }
    private function _readvalue($attrid,$attrtype) {
        if ($this->id==0) return DError::raise(self::eUnset, 'Try to read value from DB on unset object');
        $db = DataBase::$db;
        $tts = Tables::$itemtypes;$tt = $tts[$attrtype];
        $q = $db->query("SELECT value FROM $tt WHERE itemid={$this->id} AND attrid={$attrid}");
        $r = $q->fetch_row();
        $q->free();
        if (!$r) return false;
        $this->_values[$attrid] = $r[0];
        $this->_valtypes[$attrid] = $attrtype;
        return VT::convertget($attrtype,$r[0]);
    }
    public function value($attrid,$attrtype = false) {
        if (self::$debug) {
            if (!is_numeric($attrid)) return DError::raise(self::eSyntaxError, 'Wrong attrid format', DError::levError);
        }
        switch($attrid) {
            case 0: return $this->id;
            case 1: return $this->v1;
            case 2: return $this->v2;
            case 3: return $this->v3;
            case 4: return $this->v4;
        }
        if (!isset($this->_valtypes[$attrid])&&($this->_tempflags&self::tfAVR)==0) {
            if ($attrtype) return $this->_readvalue($attrid, $attrtype);
            /*------- try to recover -------*/
            DError::raise(self::eUnreadValue,"Value $attrid unread (itemid=".$this->id.')', DError::levError);
            $classname = get_called_class();
            $at2 = call_user_func(array($classname,'getattrtype'),$attrid);
            if ($at2>0) return $this->_readvalue ($attrid, $at2);
            return false;
        }
        if (!isset($this->_values[$attrid])) return false;
        return $this->_values[$attrid];
    }
    public function hasvalue($attrid) {return isset($this->_valtypes[$attrid]);}
    public function checkflags($flags) {return ($this->flags&$flags)==$flags;}
    public function getid() {return $this->id;}
    public function getLinkId() {
        if (($this->_tempflags&self::tfResolvedLink)==0) return false;
        return $this->linkid;
    }
    /*------------ SOME DEFAULT VALUES ----------*/
    public function setName($name) {$this->setvalue(10, VT::String256, $name);}
    public function getName() {return $this->value(10);}
    public function setCache($cache) {$this->setvalue(100, VT::StringLong, $cache);}
    public function getCache() {return $this->value(100);}
    //-------------------- UPDATE ------------------------
    public function setvalue($attrid,$type,$value) {
        if (self::$debug) {
            if ($attrid<1) return DError::raise(self::eWrongArgument,'Wrong attrid',DError::levError);
            if (!VT::isValid($type)) return DError::raise(self::eWrongValueType,'Wrong type',DError::levError);
        }
        if ($this->checkflags(self::fLocked)) return DError::raise (self::eItemLocked, "Item {$this->id} is locked");
        if ($value===null) return DError::raise(0,'Try to write null - skipped', DError::levNotice);
        if ($attrid<5) {
            if ($type!=VT::Int) return DError::raise(self::eWrongValueType,'Wrong type for id 1-4',DError::levError);
            $dbv = intval($value);
            $this->_tempflags |= self::tfChanged;
            switch($attrid) {
                case 1: $this->v1 = $dbv; return true;
                case 2: $this->v2 = $dbv; return true;
                case 3: $this->v3 = $dbv; return true;
                case 4: $this->v4 = $dbv; return true;
            }
        }
        if (isset($this->_valtypes[$attrid])&&$this->_valtypes[$attrid]!=$type) {
            $this->_values[$attrid] = $value;
            $this->_valtypes[$attrid] = $type;
            $this->_valflags[$attrid] = self::vfTypeChanged;
            $this->_tempflags |= 0x03;
        }
        elseif (!isset($this->_values[$attrid])||$this->_values[$attrid]!=$value) {
            $this->_values[$attrid] = $value;
            $this->_valtypes[$attrid] = $type;
            $this->_valflags[$attrid] = self::vfValChanged;
            $this->_tempflags |= 0x03;
        }
        return true;
    }
    public function setflags($flags,$set=true) {
        if ($set) $this->flags |= $flags;
        else $this->flags &= ~($flags);
        $this->_tempflags |= self::tfChanged;
    }
    public function unsetflags($flags) {
        $this->flags &= ~($flags);
        $this->_tempflags |= self::tfChanged;
    }
    public function markdelete() {
        $this->flags |= self::fMarkDelete;
        $this->_tempflags |= self::tfChanged;
    }
    public function setOwner($itemid) {
        if (!$itemid) return DError::raise (self::eWrongArgument, 'Item id is 0');
        $this->flags |= self::fV2Parent;
        $this->v2 = $itemid;
        return true;
    }
    public function getOwner() {
        if (($this->flags&self::fV2Parent)==0) return false;
        return $this->v2;
    }
    public function changed() {
        $this->_tempflags |= self::tfChanged;
    }
    // ----------------- WRITE FUNCTONS --------------
    public function check() {
        $t = true;
        if (!is_numeric($this->id)) $t = DError::raise(self::eCheckFails,'$id is not int', DError::levError);
        if (!is_numeric($this->flags)) $t = DError::raise(self::eCheckFails,'$flags is not int', DError::levError);
        if (!is_numeric($this->v1)) $t = DError::raise(self::eCheckFails,'$v1 is not int', DError::levError);
        if (!is_numeric($this->v2)) $t = DError::raise(self::eCheckFails,'$v2 is not int', DError::levError);
        if (!is_numeric($this->v3)) $t = DError::raise(self::eCheckFails,'$v3 is not int', DError::levError);
        if (!is_numeric($this->v4)) $t = DError::raise(self::eCheckFails,'$v4 is not int', DError::levError);
        return $t;
    }
    public function write() {
        if (self::$debug) {
            if (!$this->check()) return false;
        }
        if (!$this->type) return DError::raise(self::eTypeUnset,'Type unset',DError::levError);
        $db = DataBase::$db; /* @var $db DataBase */
        $t1 = Tables::items;
        if ($this->id>0) {
            $db->query("UPDATE $t1 SET flags=$this->flags,type=$this->type,v1=$this->v1,v2=$this->v2,v3=$this->v3,v4=$this->v4 WHERE id=$this->id");
        }
        else {
            $this->id = $db->insert("INSERT INTO $t1(flags,type,v1,v2,v3,v4,dtcreate)VALUES($this->flags,$this->type,$this->v1,$this->v2,$this->v3,$this->v4,CURRENT_TIMESTAMP)");
            self::$items[$this->id] = $this;
            $this->vstate = 1;
        }
        //if ($rewritevalues) $query = $this->_q_rewritevalues($query);
        //else
        if (($this->_tempflags&self::tfValChange)>0) $this->_writevalues();
        $this->_tempflags = $this->_tempflags&~(self::tfChanged|self::tfValChange);
        return true;
    }
    public static function updatemultiple(array $items) {
        $out = array();
        $itemtable = Tables::items;
        $tables = Tables::$itemtypes;
        $delete = array();
        $insert = array();
        $db = DataBase::$db;
        foreach($tables as $id=>$table) $insert[$id] = false;
        $query = '';
        foreach($items as $id=>$item/*@var $item Item*/) {
            if ($item->type==0 || $item->vstate==0) {$out[$id] = false;continue;}
            $query .= "UPDATE $itemtable SET flags=$item->flags,type=$item->type,v1=$item->v1,v2=$item->v2,v3=$item->v3,v4=$item->v4 WHERE id=$item->id;";
            if (($item->_tempflags&self::tfValChange)>0) $item->_preparewritevalues($delete,$insert);
            $out[$id] = true;
        }
        foreach($delete as $id=>$del) {
            foreach($tables as $table)
                $query .= "DELETE FROM $table WHERE itemid=$id AND attrid in ($del);";
        }
        foreach($tables as $id=>$table)
            if ($insert[$id]) $query .= "INSERT INTO $table (itemid,attrid,value) VALUES {$insert[$id]} ON DUPLICATE KEY UPDATE value=VALUES(value);";
        //echo $query;
        $db->multiquery($query);
        
    }
    public function delayedwrite() {
        $this->_tempflags |= self::tfAutoWrite;
    }
    public function _preparewritevalues(&$delete,&$insert) {
        foreach ($this->_valflags as $attrid => $flags) {
            if ($flags==0) continue;
            if ($flags==self::vfTypeChanged) {
                    if (!isset($delete[$this->id])) $delete[$this->id] = ''.$attrid;
                    else $delete[$this->id] .= ','.$attrid;
            }
            $this->_valflags[$attrid] = 0;
            $attrtype = $this->_valtypes[$attrid];
            if ($attrtype==VT::None) continue;
            else {
                $dbv = VT::totypedb($attrtype, $this->_values[$attrid]);
                if ($insert[$attrtype]===false) $insert[$attrtype] = "($this->id, $attrid, $dbv)";
                else $insert[$attrtype] .= ",($this->id, $attrid, $dbv)";
            }
        }
    }
    public function _writevalues() {
        $tables = Tables::$itemtypes;
        $delete = array();
        $insert = array();
        foreach($tables as $id=>$table) $insert[$id] = false;
        $this->_preparewritevalues($delete,$insert);
        $query = '';
        if (isset($delete[$this->id])) {
            $id = $this->id;
            $del = $delete[$this->id];
            foreach($tables as $table)
                $query .= "DELETE FROM $table WHERE itemid=$id AND attrid in ($del);";
        }
        foreach($tables as $id=>$table)
            if ($insert[$id]) $query .= "INSERT INTO $table (itemid,attrid,value) VALUES {$insert[$id]} ON DUPLICATE KEY UPDATE value=VALUES(value);";
        DataBase::$db->multiquery($query);
        return true;
    }
    /** @todo rewrite rewrite */
    public function _q_rewritevalues($query) {
        if (($this->_tempflags&self::tfAVR)==0) {
            DError::raise (0,'Values not readed',DError::levError);
            return $this->_writevalues();
        }
        $tables = Tables::$itemtypes;
        
        foreach($tables as $table)
            $query .= "DELETE FROM $table WHERE itemid={$this->id};";
        
        foreach ($this->_valtypes as $attrid => $attrtype) {
            $table = $tables[$attrtype];
            $dbv = VT::totypedb($attrtype, $this->_values[$attrid]);
            $query .= "INSERT INTO $table (itemid,attrid,value) VALUES($this->id, $attrid, $dbv) ON DUPLICATE KEY UPDATE value=VALUES(value);";
            $this->_valflags[$attrid] = 0;
        }
        return $query;
    }
    /*------------------ DELETE ---------------------*/
    public function _preparedelete() {
        $t1 = Tables::items;
        $ids = array();
        $db = DataBase::$db;
        $flag = self::fV2Parent;
        $items = array($this);
        while($item = array_pop($items)) {
            $ids[] = $item->id;
            $item->onDelete();
            if (($item->flags&self::fHasChild)>0) {
                $q = $db->query("SELECT id,flags,type FROM $t1 WHERE v2={$item->id} AND (flags&$flag)>0;");
                while($r = $q->fetch_row()) {
                    if (isset(self::$keys[$r[2]])) {
                        $cn = self::$keys[$r[2]]; $x = new $cn();
                    }
                    else $x = new self;
                    $x->id = intval($r[0]);$x->flags = intval($r[1]);$x->type = intval($r[2]);
                    array_push($items,$x);
                }
            }
        }
        return $ids;
    }
    public function delete() {
        if ($this->vstate==0) return false;
        if ($this->checkflags(self::fLocked)) return DError::raise (self::eItemLocked, "Try to delete locked item");
        $t1 = Tables::items;
        $tt = Tables::$itemtypes;
        $ids = $this->_preparedelete();
        if (!$ids) return false;
        $dbids = implode(',',$ids);
        $query = '';
        foreach($tt as $t) $query .= "DELETE FROM $t WHERE itemid in ($dbids);";
        $query .= "DELETE FROM $t1 WHERE id in ($dbids);";
        
        DataBase::$db->multiquery($query);
        $this->id = 0;
        $this->vstate = self::vsUnset;
        
        return true;
    }
//    public static function deletemultiple(array $ids) {
//        $dbids = implode(',',$ids);
//        $query = '';
//        foreach($tt as $t) $query .= "DELETE FROM $t WHERE itemid in ($dbids);";
//        $query .= "DELETE FROM $t1 WHERE id in ($dbids);";
//        DataBase::$db->multiquery($query);
//        foreach($ids as $id)
//            if (isset(self::$items[$id])) unset(self::$items[$id]);
//    }
    /*--------------------- DELAYED ACTIONS -----------*/
    public static function onShutdown(){
        $db = DataBase::$db;
        //$table = Tables::items;
        $witems = array();
        $fl = self::tfAutoWrite|self::tfChanged;
        foreach(self::$items as $item) {
            if (($item->_tempflags&$fl)>0) $witems[] = $item; 
        }
        self::updatemultiple($witems);
        if ((self::$_statflags&Item::sfLink)>0) ItemLink::writeall();
    }
    
    /*************** UTILS *******************/
    public function getattrs() {return $this->_valtypes;}
    
    public static function registerType($type,$classname) {
        if (isset(self::$keys[$type])) return false/*DError::raise (0, 'Duplicate type key')*/;
        self::$keys[$type] = $classname;
        return true;
    }
    public static function getClassnameByType($type) {
        if (isset(self::$keys[$type])) return self::$keys[$type];
        return false;
    }
    public function countchilderen() {
        $db = DataBase::$db;
        $t = Tables::items;
        $fl = Item::fV2Parent;
        $q = $db->query("SELECT count(*) FROM $t WHERE v2={$this->id} AND flags&$fl>0");
        $r = $q->fetch_row();
        if (!$r) return DError::raise(0, "query fails");
        return $r[0];
    }
    /***************** EDITOR *****************/
    /*public function edit() {

    }*/
    /**************** DEPRECATED ***************/
}

register_shutdown_function('Item::onShutdown');