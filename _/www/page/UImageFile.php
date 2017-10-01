<?php
///////////////////////////////////
//// (C) 2016, Dmitry Poletaev
///////////////////////////////////
//// Debug version
//// Version C
//// Revision 10.01.2016
///////////////////////////////////
include_once 'libs/UFile.php';

class UImageFile extends UFile implements IImage{
    private static $addfunction;
    //--------------------- Ошибки -----------------------------
    const eFileUpload   = 200;
    const eWrongFormat  = 201;
    const eMemoryLimit  = 202;
    const eNoFile       = 203;
    const eServerError  = 204;
     //----------------------- Поля ----------------------------
    public $width=0;
    public $height=0;
    public $imagetype=0;
    /*-------------- FUNCTIONS ---------------*/
    public static function processfunction(UFile $file) {
        $filename = FileSets::$root.$file->filename;
        $result = \getimagesize($filename);
        if (!$result) {$file->error = DError::raise(self::eWrongFormat, 'Неверный формат файла',DError::levWarning, self::rfObjectOnly);return false;}
        $file->width = $result[0];
        $file->height = $result[1];
        $file->imagetype = $result[2];
        if ($file->imagetype>3) {$file->error = DError::raise(self::eWrongFormat, 'Неверный формат файла',DError::levWarning, self::rfObjectOnly);return false;}
        if (self::$addfunction) {$func = self::$addfunction; return $func($file);}
        return true;
    }
    
    public static function upload($postname,$multiple=false,$generatefilename=true,$classname=false,$processfunction=false) {
        if ($processfunction) self::$addfunction=$processfunction;
        else self::$addfunction = false;
        return parent::upload($postname, $multiple, $generatefilename, $classname, 'UImageFile::processfunction');
    }
    
    public static function uploadurl($postname,$multiple=false,$generatefilename=true,$classname=false,$processfunction=false) {
        if ($processfunction) self::$addfunction=$processfunction;
        else self::$addfunction = false;
        return parent::uploadurl($postname, $multiple, $generatefilename, $classname, 'UImageFile::processfunction');
    }
    
    public function getImgWidth() { return $this->width; }
    public function getImgHeight() { return $this->height; }
    public function getImgType() { return $this->imagetype; }
}
