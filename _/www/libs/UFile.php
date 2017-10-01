<?php
///////////////////////////////////
//// (C) 2016, Dmitry Poletaev
///////////////////////////////////
//// Debug version
//// Version C
//// Revision 10.01.2016
///////////////////////////////////
include_once 'libs/IFile.php';

class UFile extends baseclass implements IFile{
    protected static $tempfiles = array();
    //------------ fields --------------
    public $filename;
    public $srcfilename;
    public $error;
    //------------ error codes ---------
    const eOk           = 0;
    const eIniSize      = 1;
    const eFormSize     = 2;
    const ePartial      = 3;
    const eNoFile       = 4;
    const eNoTmpDir     = 6;
    const eCantWrite    = 7;
    const eExtension    = 8;
    const eServerError  = 100;
    const ePostUnset    = 101;
    const eDbError      = 102;
    const eDuplicateFileName   = 103;
    //const
    //------------ flags ---------------
    const ufGenerateFilename = 1;
    const ufTemporary        = 2;
    const ufMultiple         = 4;

    //------------ general -------------
        
    public static function geterror($errorcode) {
        switch ($errorcode) {
            case 0: return "Успех";
            case self::eIniSize:
            case self::eFormSize: return "Файл слишком большой";
            case self::ePartial: return "";
            case self::eNoFile: return "Не выбран файл";
            case self::eNoTmpDir: 
            case self::eCantWrite: 
            case self::eExtension: 
            case self::eServerError: return "Ошибка сервера";
            case self::ePostUnset: return "Post-параметр не установлен";
        }
    }
    public static function geterrorlevel($errorcode) {
        if ($errorcode==self::eNoFile||$errorcode==self::ePostUnset) return DError::levNotice;
        return DError::levError;
    }
    public static function GenerateFilename($destpath,$ext) {
        do {
            $symbols = array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F','G','H','I','G','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
            $top = count($symbols)-1;
            $name = "00000000";
            for($i=0;$i<8;$i++)
                $name[$i] = $symbols[mt_rand(0, $top)];
        } while (file_exists($destpath.$name.$ext));
        return $name.$ext;
    }
    public static function internaldelete($filename) {
        $fullname = FileSets::$root.$filename;
        if (file_exists($fullname)) unlink($fullname);
    }
    private static function _upload_file(UFile &$file,$tempname,$destpath,$flags) {
        if (($flags&self::ufGenerateFilename)>0) {
            $ext = strrchr($file->srcfilename, '.');
            if (!$ext) $ext = "";
            $destname = self::GenerateFilename($destpath,$ext);
        }
        elseif (file_exists($destpath.$file->srcfilename)) return DError::raise(self::eDuplicateFileName, "Файл $file->srcfilename уже существует", DError::levWarning,baseclass::rfObjectOnly);
        else {$destname = $file->srcfilename;}
        if (!move_uploaded_file($tempname, FileSets::$root.$destpath.$destname)) return DError::raise(self::eServerError, "Ошибка загрузки файла $file->srcfilename  : ошибка сервера", DError::levError,baseclass::rfObjectOnly);
        $file->filename = $destpath.$destname;
        self::$tempfiles[] = $file;
        return $file;
    }
    private static function _upload_file_url(UFile &$file,$url,$destpath,$flags) {
        if (($flags&self::ufGenerateFilename)>0) {
            $ext = strrchr($file->srcfilename, '.');
            if (!$ext) $ext = "";
            $destname = self::GenerateFilename($destpath,$ext);
        }
        elseif (file_exists($destpath.$file->srcfilename)) return DError::raise(self::eDuplicateFileName, "Файл $file->srcfilename уже существует", DError::levWarning,baseclass::rfObjectOnly);
        else {$destname = $file->srcfilename;}
        if (!copy($url, FileSets::$root.$destpath.$destname)) {
            return DError::raise(self::eServerError, "Ошибка rкопирования файла $file->srcfilename  : ошибка сервера", DError::levError,baseclass::rfObjectOnly);
        }
        $file->filename = $destpath.$destname;
        self::$tempfiles[] = $file;
        return $file;
    }
    public static function upload($postname,$multiple=false,$generatefilename=true,$classname=false,$processfunction=false) {
        $flags = $generatefilename?self::ufGenerateFilename:0;
        if (!isset($_FILES[$postname]["tmp_name"])) {
            return DError::raise(self::ePostUnset, 'Ошибка загрузки - отсутсвует поле формы', DError::levWarning);
        }
        if (!$classname) $classname = get_called_class();
        if (!is_array($_FILES[$postname]["tmp_name"])) {
            $file = new $classname();
            $file->srcfilename = $_FILES[$postname]["name"];
            if(!is_uploaded_file($_FILES[$postname]["tmp_name"])) {
                $posterror = $_FILES[$postname]["error"];
                $file->error = DError::raise($posterror, "Ошибка загрузки файла {$file->srcfilename}  : ".self::geterror($posterror).'('.$posterror.')', self::geterrorlevel($posterror),baseclass::rfObjectOnly);
            }
            else {
                self::_upload_file($file,$_FILES[$postname]["tmp_name"], FileSets::tempDir(), $flags);
                if ($processfunction) call_user_func($processfunction, $file);
            }
            if ($multiple) return array($file);
            return $file;
        }
        $out = array();
        foreach($_FILES[$postname]["tmp_name"] as $key=>$tempname) {
            $file = new $classname();
            $file->srcfilename = $_FILES[$postname]["name"][$key];
            if(!is_uploaded_file($tempname)) {
                $posterror = $_FILES[$postname]["error"][$key];
                $file->error = DError::raise($posterror, "Ошибка загрузки файла $file->srcfilename  : ".self::geterror($posterror)."($posterror)", self::geterrorlevel($posterror),baseclass::rfObjectOnly);
            }
            else {
                self::_upload_file($file,$tempname, FileSets::tempDir(), $flags);
                if ($processfunction) {
                    $ufr = call_user_func($processfunction, $file);
                    if (!$ufr) self::internaldelete ($file->filename);
                }
            }
            $out[$key] = $file;
        }
        return $out;
    }
    public static function uploadurl($url,$multiple=false,$generatefilename=true,$classname=false,$processfunction=false) {
        $flags = $generatefilename?self::ufGenerateFilename:0;
        if (!$classname) $classname = get_called_class();

        $file = new $classname();
        $file->srcfilename = $url;
        
        $headers=get_headers($url);
        if (strpos($headers[0],'200 OK')===false) {
                $error = $headers[0];
                $file->error = DError::raise(0, "Ошибка загрузки файла {$file->srcfilename}  : ".$error, DError::levWarning,baseclass::rfObjectOnly);
        }
        else {
            self::_upload_file_url($file,$url, FileSets::tempDir(), $flags);
            if ($processfunction) call_user_func($processfunction, $file);
        }
        if ($multiple) return array($file);
        return $file;
    }
    public function apply($path) {
        $pos = strrpos($this->filename, '/');
        $fn = substr($this->filename, $pos+1);
        $newname = $path.$fn;
        if (!rename($this->filename, $newname)) return DError::raise (self::eServerError, 'Ошибка перемещения в каталог '.$path);
        $this->filename = $newname;
        foreach(self::$tempfiles as $id=>$file) {
            if ($file==$this) unset(self::$tempfiles[$id]);
        }
        return true;
    }
    public static function Shutdown() {
        foreach(self::$tempfiles as $file) self::internaldelete($file->filename);
    }
    
    public function getFilename() { return $this->filename; }
}

register_shutdown_function("UFile::Shutdown");