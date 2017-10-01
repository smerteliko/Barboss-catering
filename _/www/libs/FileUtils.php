<?php


class FileUtils {
    public static function delete($filename) {
        $fullname = FileSets::$root.$filename;
        if (file_exists($fullname)) return unlink($fullname);
        return false;
    }
    public static function exists($filename) {
        $fullname = FileSets::$root.$filename;
        return file_exists($fullname);
    }
    public static function move($filename,$destpath) {
        $t = strrpos($filename, '/');
        if ($t!==false) $z = $destpath.substr($filename, $t);
        else $z = $destpath.'/'.$filename;
        return rename($filename,$z);
    }
    public static function changeext($filename,$newext) {
        $x = strrpos($filename,'.');
        $y = strrpos($filename,'/');
        if (($x>0)&&((!$y)||($x>$y)))
            $newfilename = substr($filename, 0, $x+1).$newext;
        else $newfilename = $filename.'.'.$newext;
        return rename($filename, $newfilename);
    }
    public static function generatefilename($templatename) {
        $x = strrpos($templatename,'.');
        $y = strrpos($templatename,'/');
        if ($y!==false) $path = substr($templatename,0,$y+1);
        else $path = '';
        if ($x&&(!$y||$x>$y)) $ext = substr($templatename,$x);
        else $ext = '';
        do {
            $symbols = array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F','G','H','I','G','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
            $top = count($symbols)-1;
            $name = "00000000";
            for($i=0;$i<8;$i++)
                $name[$i] = $symbols[mt_rand(0, $top)];
        } while (file_exists($path.$name.$ext));
        return $path.$name.$ext;
    }
    public static function getnameonly($filename) {
        $t = strrpos($filename, '/');
        if ($t===false) return $filename;
        return substr($filename, $t+1);
    }
}
