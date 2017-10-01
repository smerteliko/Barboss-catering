<?php
class ItemFile extends Item {
    const Type = 0x10; /* Types 0x10 - 0x1F reserved for various files */
    
    const FileName = 10;
    const FileExt  = 20;
    const FileSize =  4;
    //---------------- readflags ----------
    const rfDelete  = 0x10;
    const rfNoCheck = 0x20;
    //---------------- errorcodes ---------
    const eNoFile         = 1;
    const eOperationFails = 2;

    public function defType() {
        return self::Type;
    }
    public function onDelete() {
        if (file_exists($this->value(10)))
            unlink($this->value(10));
        return true;
    }
    
    public function getFileName() {return $this->value(10);}
    public function setFileName($filename) {$this->setvalue(10, VT::String256, $filename);}
    public function getFileExt() {return $this->value(20);}
    public function setFileExt($ext) {$this->setvalue(20, VT::String256, $ext);}
    
    public function exists() {
        return file_exists($this->value(10));
    }
    public function getfullurl() {
        return Server::$host.$this->value(10);
    }
    public function move($destname) {
        if (!rename($this->value(10),$destname)) DError::raise(self::eOperationFails,'Rename fails',DError::levError);
        $this->setvalue(10, VT::String256, $destname);
        $this->delayedwrite();
        return true;
    }
    public static function fromfile($filename) {
        if (!file_exists($filename)) return DError::raise(self::eNoFile, 'File '.$path.$name.' not exists',DError::levError);
        $file = self::create();
        $file->setvalue(10,VT::String256,$filename);
        $t = strrpos($filename, '.');
        if ($t!==false) $ext = substr($filename,$t);
        else $ext = '';
        $this->setvalue(20, VT::String256, $ext);
        $file->v4 = filesize($filename);
        return $file;
    }
    public function changeext($newext) {
        $x = strrpos($this->filename,'.');
        $oldname = $this->value(10);
        $newname = substr($oldname, 0, $x).$newext;
        if (!rename($this->value(10),$newname)) DError::raise(self::eOperationFails,'Rename fails',DError::levError);
        $this->setvalue(10, VT::String256, $newname);
        $this->setvalue(20, VT::String256, $newext);
        $this->delayedwrite();
        return true;
    }
    /*public static function fromUFile(UFile $ufile) {
        $file = self::create();
        $file->fromfile();
    }*/
} 

Item::registerType(ItemFile::Type);