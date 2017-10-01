<?php
class Types
{
    public static function isEmail($src) {
        return filter_var($src, FILTER_VALIDATE_EMAIL);
    }
    public static function passEncode($password) {
        return hash('sha256',$password);
    }
}

class DateTimeFormatter {
    public static function TimestampToRussian($timestamp) {
        $date = new DateTime;
        $date->setTimestamp($timestamp);
        return $date->format('d.m.Y H:i');
    }
    public static function RussianToTimestamp($string) {
        $x = explode(' ', $string);
        $cnt = count($x);
        $h = $i = 0;
        if ($cnt==0) return false;
        elseif (count($x)>1) {
            $time = explode(':',$x[1]);
            if (count($time>1)) {
                $h = $time[0];
                $i = $time[1];
            }
        }
        $date = explode('.',$x[0]);
        if (count($date)<2) return false;
        if (count($date)>2) $y = $date[2];
        else $y = date("Y");
        $d = $date[0];
        $m = $date[1];
        return mktime($h, $i, 0, $m, $d, $y);
    }
}
