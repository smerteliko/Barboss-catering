<?php

class Session
{
    public static $sessionid=false;
    public static $userid=false;
    public static $expire = false;
    public static $flags=0;
    public static $v1=0;
    public static $v2=0;
    public static $v3=0;
    public static $v4=0;
    public static $ip;
    public static $lastip;
    public static $logintime = false;
    public static $lastlogin;
    public static $newexpire;
    //------------------ INTERNAL -------------------
    public static function tables_exists() {
        $db = DataBase::$db;
        $t = Tables::session;
        $q = $db->query("SHOW TABLES LIKE '$t'");
        $r = $q->fetch_row();
        $q->free();
        if ($r) return true;
        else return false;
    }
    public static function create_tables() {
        $db = DataBase::$db;
        $t = Tables::session;
        $db->query("CREATE TABLE IF NOT EXISTS $t (id INT primary key, flags int, logintime timestamp, expire timestamp, userid int, ip int, v1 int, v2 int, v3 int, v4 int)");
    }
    
    private static function generateid()
    {
        $db = DataBase::$db;
        $t = Tables::session;
        do {
            $rnd = mt_rand(0, 0x1FFFFFFF);
            $q = $db->query("SELECT id FROM $t WHERE id=$rnd");
            $r = $q->fetch_row();
            $q->free();
        } while ($r);
    //    $q->query("INSERT INTO $t(id) VALUES ($rnd)");
        return $rnd;
    }
    private static function _init() {
        self::$v1 = self::$v2 = self::$v3 = self::$v4 = 0;
    }
    private static function todb() {
        $userid = intval(self::$userid);
        $flags = intval(self::$flags);
        $v1 = intval(self::$v1);  $v2 = intval(self::$v2);
        $v3 = intval(self::$v3);  $v4 = intval(self::$v4);
        $db = DataBase::$db;      $t = Tables::session;
        $ip = intval(self::$ip);  $id = self::$sessionid;
        $logintime = intval(self::$logintime); $expire = intval(self::$newexpire);
        $db->query("UPDATE $t SET userid=$userid,logintime=FROM_UNIXTIME($logintime),expire=FROM_UNIXTIME($expire),flags=$flags,ip=$ip,v1=$v1,v2=$v2,v3=$v3,v4=$v4 WHERE id=$id");
    }
    public static function newdb() {
        $userid = intval(self::$userid);
        $flags = intval(self::$flags);
        $v1 = intval(self::$v1);  $v2 = intval(self::$v2);
        $v3 = intval(self::$v3);  $v4 = intval(self::$v4);
        $db = DataBase::$db;      $t = Tables::session;
        $ip = intval(self::$ip);  $id = self::generateid();
        $db->query("INSERT INTO $t (id,userid,flags,ip,v1,v2,v3,v4) VALUES ($id,$userid,$flags,$ip,$v1,$v2,$v3,$v4)");
        return $id;
    }
    private static function deletedb() {
        if (!Session::$sessionid) return;
        $db = DataBase::$db;
        $t = Tables::session;
        $id = self::$sessionid;
        $db->query("DELETE FROM $t WHERE id=$id");
    }
    private static function deleteallusersessions($userid) {
        if (!Session::$sessionid) return;
        $db = DataBase::$db;
        $t = Tables::session;
        $db->query("DELETE FROM $t WHERE userid={$userid}");
    }
    private static function _clearsession() {
        Session::_init();
        unset($_COOKIE['sessionid']);
        setcookie('sessionid', null, -1);
        self::$sessionid = false;
    }
    public static function onshutdown() {
        if (self::$sessionid)
            self::todb();
        elseif (baseclass::$debug)
            if (self::$userid>0||self::$v1>0||self::$v2>0||self::$v3>0||self::$v4>0) DError::raise(0, 'Not initialized session is not empty');
    }
    //--------------------- PUBLIC ----------------------
    public static function load($expire = false) {
        if (!$expire) $expire = 0;
        self::$ip = ip2long($_SERVER['REMOTE_ADDR']);
        $time = time();
        self::$logintime = $time;
        self::$newexpire = $time + $expire;
        register_shutdown_function('Session::onshutdown');
        if (!isset($_COOKIE['sessionid'])) return false;

        self::$sessionid = $id = intval($_COOKIE['sessionid']);
        $db = DataBase::$db;
        $t = Tables::session;
        $q = $db->query("SELECT flags,userid,UNIX_TIMESTAMP(logintime),UNIX_TIMESTAMP(expire),ip,v1,v2,v3,v4 FROM $t WHERE id=$id");
        $r = $q->fetch_row();
        if (!$r) { 
            self::_clearsession();
            return false;
        }
        
        list(self::$flags,self::$userid,self::$lastlogin,self::$expire,self::$lastip,self::$v1,self::$v2,self::$v3,self::$v4) = $r;
        /*if (self::$expire<$time) {
            self::stop();
            return false;
        }*/
        self::$newexpire = $expire?($time + $expire):self::$expire;
        setcookie('sessionid', self::$sessionid, self::$newexpire);
        return true;
    }
    public static function create() {
        if (self::$sessionid) return false;
        if (!self::$logintime) return DError::raise(0, 'Session is not loaded!');
        self::$sessionid = self::newdb();
        setcookie('sessionid', self::$sessionid, self::$newexpire);
        return true;
    }
    /*public static function write() {
        if (self::$sessionid)
            self::todb();
    }*/
    public static function stop() {
        if (self::$sessionid) {
            self::deletedb();
            self::_clearsession();
        }
    }
    public static function login($userid,$expiresecs) {
        self::deleteallusersessions($userid);
        self::$userid = $userid;
        self::$newexpire = self::$logintime+$expiresecs;
        self::restart();
    }
    public static function restart() {
        self::deletedb();
        self::$sessionid = self::newdb();
        setcookie('sessionid', self::$sessionid, self::$newexpire);
    }
    public static function dump() {
        return 'id='.self::$sessionid.'; lastlogin='.(self::$lastlogin).'; lastip='.long2ip(self::$lastip).'; expirein='.self::$newexpire.
                '; v1='.self::$v1.'; v2='.self::$v2.'; v3='.self::$v3.'; v4='.self::$v4;
    }
}