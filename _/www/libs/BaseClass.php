<?php
include_once 'settings/errors.php';
class baseclass {
    public static $debug = true;
    public static $lockprops = true;
    /*----------- STATE -----------*/
    protected $vstate=0;
    private $data = array();
    private $functions = array();
    const vsUnset = 0;
    const vsSet   = 1;
    public function isvalid() {return $this->vstate==self::vsSet;}
    /*----------- TYPES -----------*/
    public function iserror() {return false;}
    /*----------- RETURN FLAGS ------------*/
    const rfObjectOnly    = 0x01;
    const rfCreateDefault = 0x02;
    /*----------- ERRORS ------------------*/
    const eSyntaxError    = 0x1001;
    const eNoObject       = 0x1002;
    const eWrongArgument  = 0x1003;
    const eCheckFails     = 0x1004;
    const eNotImplemented = 0x1005;
    const eUnset          = 0x1006;
    const eDeprecated     = 0x1007;
    
    public function dump() {
        $vars = get_object_vars($this);
        $t = get_class($this).':';
        foreach($vars as $name=>$value) $t .= $name.'='.$value.';';
        return $t;
    }
    //public function getprivatefields() 
    public static function onEmpty($flags,$classname,$errorlev = DError::levWarning) {
        $error = DError::raise(self::eNoObject, 'Object unexisted', $errorlev,$flags);
        if (($flags&self::rfCreateDefault)>0) return call_user_func(array($classname,'create'));
        return $error;
    }
    public function addproperty($name,$value) {
        $this->data[$name] = $value;
    }
    public function addfunction($name,callable $function) {
        $this->functions[$name] = $function;
    }
    public function hasproperty($name) {
        if (isset($this->data[$name])) return true;
        $t = get_object_vars($this);
        if (isset($t[$name])) return true;
        return false;
    }
    public function hasfunction($name,$useronly=true) {
        if (isset($this->functions[$name])) return true;
        if ($useronly) return false;
        $funcs = get_class_methods($this);
        foreach ($funcs as $func) if ($func===$name) return true;
        return false;
    }
    public static function fromobject($object) {
        $classname = get_called_class();
        $x = call_user_func(array($classname,'create'));
        $attrto = get_class_vars($classname);
        $attrsfrom = get_object_vars($object);
        foreach($attrsfrom as $id=>$value) {
            if (isset($attrto[$id])) $x->{$id} = $value;
        } 
        return $x;
    }
    /*------------ PREVENT DEFAULTS ------*/
    public function __set($name, $value) {
        if (array_key_exists($name, $this->data)) {return $this->data[$name] = $value;}
        return DError::raise(0x1005, 'Try to set unexisted property '.$name.'='.$value, DError::levError);
    }
    public function __get ($name) {
        if (array_key_exists($name, $this->data)) return $this->data[$name];
        return DError::raise(0x1005, 'Try to get unexisted property '.$name,DError::levError);
    }
    public function __call ($name, $arguments) {
        if (isset($this->functions[$name])) return call_user_func_array($this->functions[$name],$arguments);
        return DError::raise(0x1005, 'Try to call unexisted function '.$name,DError::levError);
    }
    /*------------ FOR OVERRIDE ----------*/
    public static function create() {
        $classname = get_called_class();
        return new $classname();
    }
    public function check() {
        $t = true;
        if (!is_numeric($this->vstate)||$this->vstate>1||$this->vstate<0) $t = DError::raise(self::eCheckFails, '$vstate - тест не пройден');
        return $t;
    }
    public function __toString() {
         return $this->dump();
    }
}

class DEmpty extends baseclass {
    /*------------- show yourself -------------------------*/
    public function __toString()
    {
         return "EMPTY";
    }
}

class ObjectArray extends baseclass {
    public $array;
    public static function create($array=false) {
        $x = new self;
        if (!$array) $x->array = array();
        else $x->array = $array;
        return $x;
    }
}

class DError extends baseclass {
    /*-------- LEVELS ----------*/
    const levNotice = 1;
    const levWarning = 5;
    const levError = 10;
    const levFatal = 20;
    /*-------- CONFIG -----------*/
    static $loglevel = 5;
    static $lasterror = null;
    static $muteoutput = false;
    /*------ baseclass override ------*/
    public function iserror() {return true;}
    /*------ fields --------*/
    public $errorcode=-1;
    public $errortext;
    public $debuginfo;
    public $level;
    /*------ constructors --*/
    private static function _leveltostring($level) {
        switch($level) {case 0: return 'Notice'; case 5: return 'Warning';case 10:return 'Error';case 20:return 'Fatal';}
    }
    private static function _printdebug($dbg) {
        if (!is_array($dbg)) {return 'error in debug info';}
        $place = '';
        if (isset($dbg['class'])&&isset($dbg['function'])) $place .= $dbg['class'].'::'.$dbg['function'];
        elseif (isset($dbg['function'])) $place .= $dbg['function'];
        if (isset($dbg['args'])) {
            $impl = false;
            foreach($dbg['args'] as $arg) {
                if (is_object($arg)) $t = get_class($arg);
                elseif (is_array($arg)) $t = 'array';
                elseif (is_string($arg)) $t = '\''.$arg.'\'';
                else $t = $arg;
                if ($impl===false) $impl = $t;
                else $impl .= ', '.$t;
            }
            $place .= '('.$impl.')';
        }
        if (isset($dbg['file'])&&isset($dbg['line'])) $place .= ' ['.$dbg['file'].','.$dbg['line'].']';
        elseif (isset($dbg['file'])) $place .= ' ['.$dbg['file'].']';
        return $place;
    }
    private static function _make($debug,$errortext,$level) {
        $outtext = self::_leveltostring($level).': '.$errortext;
        if (isset($debug[1])) $outtext .= ' in '.self::_printdebug ($debug[1]);
        for ($i=2;$i<count($debug);$i++) $outtext .= '//'.self::_printdebug($debug[$i]);
        return $outtext;
    }
    public static function raise($errorcode,$errortext,$level=10,$flags=0) {
        $x = new self;
        $x->errortext = $errortext;
        $x->errorcode = $errorcode;
        $x->debuginfo=debug_backtrace();
        $x->level = $level;
        self::$lasterror = $x;
        if ($level>=self::$loglevel) {
            $text = self::_make($x->debuginfo,$errortext, $level);
            if (!self::$muteoutput) trigger_error($text);
        }
        if (($flags&self::rfObjectOnly)>0) return $x;
        else return false;
    }
    /*------------- show yourself -------------------------*/
    public function __toString() {
         return self::_make($this->debuginfo, $this->errortext, $this->level);
    }
}