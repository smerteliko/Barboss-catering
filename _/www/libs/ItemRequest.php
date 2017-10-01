<?php
/******************************************/
/****   (C) Dmitry Poletaev, 2016      ****/
/****   Version C, Revision 25.03.16   ****/
/******************************************/


class ItemRequest{
    /*---------- FIELDS ---------*/
    public $values = false;
    public $limits = false;
    public $order = false;
    public $where = false;
    public $type = false;
    public $parent = false;
    public $isenabled = false;
    public $showdeletedmode = 0;
    public $printrequest = false;
//    public $issubtype = false;
    public $idlist = false;
//    public $hfield = false;
//    public $hvalues = false;
//    public $type = 0;
    public $agregation = false;
    public $linksonly = false;
    /*-------- SPEC VALUES ------*/
    const attrId = 0;
    const attrDTCreate = -1;
    const attrDTUpdate = -2;
    /*---- SHOW DELETED MODES ---*/
    const sdmNotDeleted  = 0;
    const sdmDeletedOnly = 1;
    const sdmAll         = 2;
    /*-------- ERRORS ------------*/
    const eWrongAttrId = 0x1201;
    
    static $attrs;
    
    private function _getwhere($db) {
        $flagsA = 0;
        $flagsB = 0x40;     //Item::fTemplate
        //$where = false;
        $where = array();
        if ($this->isenabled) $flagsA |= Item::fEnabled;
        switch($this->showdeletedmode) {
            case 0: $flagsB |= Item::fMarkDelete;break;
            case 1: $flagsA |= Item::fMarkDelete;break;
        }
        if ($this->linksonly) $flagsA |= Item::fLink;
        if ($this->type) {
            $type = $this->type;
            if (is_array($type)) $where[] = "t0.type>={$type[0]} AND t0.type<={$type[1]}";
            else $where[] = "t0.type={$type}";
        }
        if ($this->parent) {
            $where[] = "v2={$this->parent}";
            $flagsA |= Item::fV2Parent;
        }
        elseif ($this->parent===0) $flagsB |= Item::fV2Parent;
        if (is_array($this->idlist)) {
            $tid = false;
            foreach ($this->idlist as $id) {
                if (!$tid) $tid = intval($id);
                else $tid .= ','.intval($id);
            }
            $where[] = "id in ($tid)";
        }
        if ($flagsA>0) $where[] = "(t0.flags&$flagsA)=$flagsA"; 
        if ($flagsB>0) $where[] = "(t0.flags&$flagsB)=0";
        
        if ($this->where) foreach($this->where as $wid=>$wvv) {
            if ($wid>4) continue;
            if ($wid<-2) {DError::raise(self::eWrongAttrId, "attrid<0 in where clause");continue;}
            if (is_array($wvv)) {$x1=$wvv[0];$x2=$wvv[1];}
            else {$x1=$wvv;$x2='=';}
            $attrname = self::$attrs[$wid];
            $where[] = "{$attrname}{$x2}{$x1} ";
        }
        $count = count($where);
        if ($count==0) return '';
        $out = "WHERE {$where[0]} ";
        for($i=1;$i<$count;$i++) $out .= "AND {$where[$i]} ";
        return $out;
    }
    
    private function _getjoin($tables) {
        $out = '';
        if ($this->values)
        foreach ($this->values as $id => $type) {
                $tt = $tables[$type];
                if (isset($this->where[$id])) {
                    $where = $this->where[$id];
                    if (is_array($where)) {$dbv = VT::totypedb($type, $where[0]);$cond = $where[1];}
                        else {$dbv = VT::totypedb($type, $where);$cond = '=';}
                    $out .= "INNER JOIN $tt as t{$id} ON t{$id}.itemid=t0.id AND t{$id}.attrid=$id AND t{$id}.value{$cond}{$dbv} ";    
                } 
                else $out .= "LEFT OUTER JOIN $tt as t{$id} ON t{$id}.itemid=t0.id AND t{$id}.attrid=$id ";
            }
        return $out;
    }
    
    private function _getagregation() {
        if (!is_array($this->agregation)) return 'SELECT count(*)';
        
        $r = '';
        foreach($this->agregation as $id=>$func) {
            if ($r) $r .= ',';
            $r .= $func;
            if ($id==0) $r.= '(t0.id)';
            elseif ($id<5) $r .= "(t0.v{$id})";
            else $r .= "(t{$id}.value)";
        }
        return "SELECT {$r}";
    }
    
    private function _getorder() {
        $ro = false;
        foreach($this->order as $oid=>$oo) {
            if ($oo==1) $dbo = 'DESC';
            else $dbo = 'ASC';
            if ($oid<5) {
                $rt = self::$attrs[$oid];
                $rx = "t0.{$rt} $dbo";
            }
            else $rx = "t{$oid}.value $dbo";
            if (!$ro) $ro = $rx;
            else $ro .= ','.$rx;
        }
        return " ORDER BY {$ro}";
    }
    public function get() {
        if (is_array($this->idlist)&&(count($this->idlist)==0)) return false;
        if ($this->values && !is_array($this->values)) {
            if ($this->values==Item::rvName) $this->values = array(10=>11);
            elseif ($this->values==Item::rvNameCache) $this->values = array(10=>11,100=>12);
            else $this->values = false;
        }
        
        $tables = Tables::$itemtypes;
        $t0 = Tables::items;
        $db = DataBase::$db;
        
        if ($this->agregation) $r = $this->_getagregation();
        else {
            $r = 'SELECT t0.id,t0.flags,t0.type,UNIX_TIMESTAMP(t0.dtupdate),UNIX_TIMESTAMP(t0.dtcreate),t0.v1,t0.v2,t0.v3,t0.v4';
            if ($this->values) foreach($this->values as $id=>$type) $r .= ",t{$id}.value";
        }
        $r .= " FROM $t0 as t0 ";
        $r .= $this->_getjoin($tables);    
        $r .= $this->_getwhere($db);
        if ($this->order) $r .= $this->_getorder();
        if ($this->limits) $r .= " LIMIT {$this->limits[0]},{$this->limits[1]}";
        if ($this->printrequest) echo $r;
        return $r;
    }
    public function req_values() {
        //if (count($this->values)!=1) throw Exception ('ItemRequest::getvalues : can get 1 attrtype');
        $tables = Tables::$itemtypes;
        $t0 = Tables::items;
        $db = DataBase::$db;
        
        $r = "SELECT id,value";
        $r .= " FROM $t0 as t0 ";
        if ($this->values) $r .= $this->_getjoin($tables);
            
        $r .= $this->_getwhere($db);
        if ($this->order) $r .= $this->_getorder();
        if ($this->limits) $r .= " LIMIT {$this->limits[0]},{$this->limits[1]}";
        return $r;
    }
    
    public function query() {
        $db = VT::$db;
        return $db->query($this->get());
    }
    
    //--------------------- CONSTRUCTORS ----------------------------
    public static function c_type($type,$values,$order=false,$where=false) {
        $request = new self;
        $request->type = $type;
        $request->values = $values;
        $request->order = $order;
        $request->where = $where;
        return $request;
    }
    public static function c_list($values,$order=false,$where=false) {
        $request = new self;
        $request->values = $values;
        $request->order = $order;
        $request->where = $where;
        return $request;
    }
    public static function c_single($id,$values = false) {
        $x = new self;
        $x->values = $values;
        $x->where = array(0=>$id);
        $x->limits = array(0,1);
        return $x;
    }
    public static function c_children($parentid, $values) {
        $x = new self;
        $x->values = $values;
        $x->parent = $parentid;
        return $x;
    }
    /*public static function c_count($itemtype,$where = false) {
        $x = new self;
        $x->key = $itemtype;
        $x->where = $where;
        $x->type = self::tCount;
        return $x;
    }*/
    public static function c_values($itemtype,$attrid,$attrtype) {
        $x = new self;
        //$x->type = self::;
        $x->type = $itemtype;
        $x->values = array($attrid=>$attrtype);
        return $x;
    }
    /*--------------- UTILS ------------*/
    public function check() {
        $t = true;
        if ($this->values&&!is_array($this->values)) $t = DError::raise(0x1004, '$values - check falis');
        if ($this->limits&&!is_array($this->limits)) $t = DError::raise(0x1004, '$limits - check falis');
        if ($this->order&&!is_array($this->order)) $t = DError::raise(0x1004, '$order - check falis');
        if ($this->where&&!is_array($this->where)) $t = DError::raise(0x1004, '$where - check falis');
        if ($this->idlist&&!is_array($this->idlist)) $t = DError::raise(0x1004, '$idlist - check falis');
        return $t;
    }
    /*----------- DO --------------*/
    public function getAgregation($agregation) {
        $this->agregation = $agregation;
        
        $q = DataBase::$db->query($this->get());
        $r = $q->fetch_row();
        $q->free();
        $this->agregation = false;
        if (!is_array($agregation) || count($agregation)==1) {
            return $r[0];
        }
        else return $r;
    }
    public function getValues() {
        $out = array();
        $ids = array();
        $f1 = false;
        if (is_array($this->idlist))
            if (count($this->idlist)==0) return array();
            else $f1 = true;
        if ($f1) foreach($this->idlist as $id) $ids[$id] = 1;
        $q = DataBase::$db->query($this->req_values());
        while ($r = $q->fetch_row()) {
            $out[$r[0]] = $r[1];
            if ($f1) unset($ids[$r[0]]);
        }
        $q->free();
        if ($f1) foreach($ids as $id) $out[$id] = false;
        return $out;
    }
}

ItemRequest::$attrs = array(-2=>'dtupdate',-1=>'dtcreate',0=>'id',1=>'v1',2=>'v2',3=>'v3',4=>'v4');