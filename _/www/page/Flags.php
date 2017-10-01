<?php

class Flags {
    public static function Set(&$value,$flag) {
        $value |= $flag;
    }
    public static function Remove(&$value,$flag) {
        $value &= ~$flag;
    }
    public static function Check($value,$flag) {
        return ($value&$flag)==$flag;
    }
}

