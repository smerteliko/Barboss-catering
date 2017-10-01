<?php
////////////////////////////////
// (C) 2014, Dmitry Poletaev
////////////////////////////////
Class Post
{
    public static function set($name){ return isset($_POST[$name]); }
    public static function int($name) {
        if (!isset($_POST[$name])) return false; 
        return intval($_POST[$name]);
    }
    public static function any($name) {
        if (!isset($_POST[$name])) return false; 
        return $_POST[$name];
    }
    public static function html($name) {
        if (!isset($_POST[$name])) return false; 
        return htmlspecialchars($_POST[$name], ENT_QUOTES, "UTF-8");
    }
    public static function double($name) {
        $t = str_replace(',', '.', $_POST[$name]);
        return floatval($t);
    }
    public static function indexedarrayint($name) {
        if (!isset($_POST[$name])) return false; 
        $array = $_POST[$name];
        if (!is_array($array)) return false;
        $out = array();
        foreach ($array as $key => $value) {
            if (!is_numeric($key)) continue;
            $out[$key] = intval($value);
        }
        return $out;
    }
    public static function indexedarraydouble($name) {
        if (!isset($_POST[$name])) return false; 
        $array = $_POST[$name];
        if (!is_array($array)) return false;
        $out = array();
        foreach ($array as $key => $value) {
            if (!is_numeric($key)) continue;
            $out[$key] = str_replace(',', '.', $value);
        }
        return $out;
    }
    public static function indexedarrayany($name) {
        if (!isset($_POST[$name])) return false; 
        $array = $_POST[$name];
        if (!is_array($array)) return false;
        $out = array();
        foreach ($array as $key => $value)  {
            if (!is_numeric($key)) continue;
            $out[$key] = $value;
        }
        return $out;
    }
    public static function indexedarrayhtml($name) {
        if (!isset($_POST[$name])) return false; 
        $array = $_POST[$name];
        if (!is_array($array)) return false;
        $out = array();
        foreach ($array as $key => $value) {
            if (!is_numeric($key)) continue;
            $out[$key] = htmlspecialchars($value,ENT_QUOTES, "UTF-8");
        }
        return $out;
    }
    public static function indexedarrayset($name) {
        if (!isset($_POST[$name])) return false; 
        $array = $_POST[$name];
        if (!is_array($array)) return false;
        $out = array();
        foreach ($array as $key => $value) {
            if (!is_numeric($key)) continue;
            $out[$key] = $key;
        }
        return $out;
    }    
}
