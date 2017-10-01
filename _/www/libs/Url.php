<?php
class Url {
    public static $page = '';
    public static $params=array();
    public static $isinit = false;
    public static function make($url) {
        //$xurl = urlencode($url);              /* Нельзя, т.к. будут ? и прочее кодироваться в  %хх */
        $xurl = $url;                           /* Заглушка */
        if (strstr($url,'://')) return $xurl;
        elseif ($url[0]=='/') return Host::$name.$xurl;
        else return Host::$name.'/'.$xurl;
    }
    public static function parse($allow = false) {
        if (self::$isinit) return;
        self::$page = $_SERVER['PHP_SELF'];
        if (is_array($allow)) {
            foreach ($allow as $param) {
                if (isset($_GET[$param])) self::$params[$param] = $_GET[$param];
                else self::$params[$param] = null;
            }
        }
        else {
            foreach($_GET as $key => $value) {
                self::$params[$key] = $value;
            }
        }
        self::$isinit = true;
    }
    public static function href($text,$add,$remove=false) {
        $ref = Url::make(self::$page);
        $params = false;
        if (!is_array($remove)) $remove = array($remove=>1);
        foreach(self::$params as $key=>$values) {
            if ($remove&&isset($remove[$key])) continue;
            $ia = is_array($values);
            if ($add&&isset($add[$key])) {
                if ($ia) $values[] = $add[$key];
                //elseif ($values) $values .= ','.$add[$key];
                else $values = $add[$key];
                unset($add[$key]);
            }
            if (!$values) continue;
            if ($ia) $vx = implode(',', $values);
            else $vx = $values;
            if ($params===false) $params = '?'.$key.'='.$vx;
            else $params .= '&'.$key.'='.$vx;
        }
        if ($add) foreach($add as $key=>$value) {
            if ($params===false) $params = '?'.$key.'='.$value;
            else $params .= '&'.$key.'='.$value;
        }
        return '<a href="'.$ref.$params.'">'.$text.'</a>';
    }
}