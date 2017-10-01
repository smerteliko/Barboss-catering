<?php
class Server {
    static $self;
    static $from;
    static $host;
    static $documentroot;
    //static $query;
}

Server::$documentroot = $_SERVER['DOCUMENT_ROOT'];
Server::$self = $_SERVER['PHP_SELF'];
Server::$host = $_SERVER['SERVER_NAME'];
Server::$from = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:Server::$host;


class Html {
    public static function tohtml($src) {
        return htmlspecialchars($src,ENT_QUOTES, "UTF-8");
    }
    public static function ref($url,$text, $style = false, $blank = false){
        $x = Url::make($url);
        if($style) $style = ' class="'.$style.'"';
        if($blank) $blank = ' target="_blank"';
        return '<a href="'.$x.'"'.$style.$blank.'>'.$text.'</a>';
    }
    public static function refjs($js,$text){
        return '<a href="javascript:'.$js.'">'.$text.'</a>';
    }
}

class Header {
    public static function modified($timestamp) {
        $LastModified = gmdate("D, d M Y H:i:s \G\M\T", $timestamp);
        $IfModifiedSince = false;
        if (isset($_ENV['HTTP_IF_MODIFIED_SINCE']))
            $IfModifiedSince = strtotime(substr($_ENV['HTTP_IF_MODIFIED_SINCE'], 5));  
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
            $IfModifiedSince = strtotime(substr($_SERVER['HTTP_IF_MODIFIED_SINCE'], 5));
        if ($IfModifiedSince && $IfModifiedSince >= $timestamp) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
            exit;
        }
        header('Last-Modified: '. $LastModified);
    }
    public static function redirect($url,$stopscript=true) {
        header('location: '.Url::make($url));
        flush();
        if ($stopscript) exit;
    }
}

class Get {
    public static function int($name) {return isset($_GET[$name])?intval($_GET[$name]):false;}
    public static function set($name) {return isset($_GET[$name]);}
}