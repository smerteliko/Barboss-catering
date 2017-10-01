<?php
class BrowserCheck
{
    const MSIE = 0x10;
    const Chrome = 0x20;
    const Firefox = 0x30;
    const Opera = 0x40;
    
    public static $browser=false;
    public static function get() {
        self::$browser = $_SERVER['HTTP_USER_AGENT'];
    }
    public static function MSIEVer() {
        if (!self::$browser) self::get();
        $from = strpos(self::$browser, 'MSIE')+4;
        if ($from===false) return false;
        while(self::$browser[$from]==' ')$from++;
        $to = $from;
        while(true) {
            $t = self::$browser[$to];
            if ($t!='.'&&($t<'0'||$t>'9')) break;
            $to++;
        }
        return floatval(substr(self::$browser, $from, $to-$from));
    }
    public static function HtmlVer() {
        if (!self::$browser) self::get();
        $from = strpos(self::$browser, 'Mozilla/')+8;
        if ($from===false) return false;
        return floatval(substr(self::$browser,$from,3));
    }
    public static function IsOpera() {
        if (!self::$browser) self::get();
        return strpos(self::$browser,'Opera')!==false;
    }
    public static function IsMobile() {
        if (!self::$browser) self::get();
        return strpos(self::$browser,'Mobile')!==false;
    }
}
