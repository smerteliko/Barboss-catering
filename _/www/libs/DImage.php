<?php
//include_once 'ImageFile.php';
include_once 'libs/IFile.php';
include_once 'libs/Item.php';

class DImage extends Item implements IImage
{
    const TypeKey = 0x110;
    const MiniatureDelta = 5;
    
    const SortOrder = 1;
    const FileName = 10;
    const Alt      = 15;
    const Comment  = 16;
    const Width    = 20;
    const Height   = 21;
    const ImgType  = 22;
    
    const gmmFind       = 0;
    const gmmCreateFit  = 1;
    const gmmCreateCrop = 2;
    const gmmClosest   = 10;

    public $miniatures;
    //public $file;
    private $_minread = false;
    
    /*----------- ITEM OVERRIDES ----------*/
    //public static function requestvalues() {
    //    return array(self::FileName=>VT::String256,self::Width=>VT::Int,self::Height=>VT::Int);
    //}
    public static function defType() {
        return self::TypeKey;
    }
    public static function defFlags() {
        return (Item::fHasChild);
    }
    public static function getAllValues() {
        return array(10=>11,15=>11,16=>11,20=>1,21=>1,22=>1);
    }
    public function onDelete() {
        $this->vstate = 1;
        $fullname = FileSets::$root.'/'.$this->value(10,VT::String);
        if (file_exists($fullname)) unlink($fullname);
        return true;
    }
    public static function getTypeName() { return 'Изображение'; }
    /*--------- INTERFACE IMPLEMENTATION -----*/
    public function getFileName() { return $this->value(10); }
    public function getImgWidth() { return $this->value(20); }
    public function getImgHeight() { return $this->value(21); }
    public function getImgType() { return $this->value(22); }
    /*---------- ACCESSORS --------*/
    public function setSortOrder($sortorder) {$this->v1 = $sortorder;}
    public function getSortOrder() {return $this->v1;}
    public function setWH($width,$height) { $this->setvalue(self::Width, VT::Int, $width);$this->setvalue(self::Height, VT::Int, $height);}
    public function setFileName($filename) {$this->setvalue(self::FileName, VT::String256, $filename);}
    public function setAlt($alt) {$this->setvalue(15, VT::String, $alt);}
    public function getAlt() {return $this->value(15);}
    public function setComment($comment) {$this->setvalue(16, VT::String, $comment);}
    public function getComment() {return $this->value(16);}
    /*---------- FROM IMAGE FILE --------*/
    private function _fromfile($file) {
        if ($file instanceof IImage) {
            $width = $file->getImgWidth(); $height = $file->getImgHeight();
            $type = $file->getImgType(); $filename = $file->getFilename();
        }
        elseif ($file instanceof IFile) {
            $filename = $file->getFilename();
            $r = \getimagesize($file->filename);
            $width = $r[0]; $height = $r[1];
            $type = $r[2];
        }
        else return DError::raise(baseclass::eWrongArgument, 'Неверный тип $file:'.get_class($file));
        $this->setvalue(20, VT::Int, $width);
        $this->setvalue(21, VT::Int, $height);
        $this->setvalue(22, VT::Int, $type);
        $this->setvalue(10, VT::String256, $filename);
        return true;
    }
    public static function fromfile($image) {
        $x = new self;
        $x->type = self::TypeKey;
        $x->flags = self::defFlags();
        if (!$x->_fromfile($image)) return false;
        return $x;
    }
    public function update($image) {
        $oldfullname = FileSets::$root.$this->value(10);
        $r = $this->_fromfile($image);
        if ($r) {
            if (file_exists($oldfullname)) unlink($oldfullname);
            return true;
        }
        else return false;
    }
    /*---------- MINIATURES ------------*/
    public function readminiatures() {
        if (!$this->isvalid()) return DError::raise(self::eItemUnset, 'Cant get miniatures of invalid image', DError::levError);
        if ($this->_minread) return $this->miniatures;
        $items = DImageMin::getlist(ItemRequest::c_children($this->id,array(10=>VT::String256)));
        $this->miniatures = $items;
        //foreach ($items as $item) {$this->miniatures[] = $item;}
        $this->_minread = true;
        return $this->miniatures;
    }
    public function getminiature($width,$height,$mode) {
        $w = intval($width);
        $h = intval($height);
        if ($this->readminiatures()===false) return false;
        $dlt = self::MiniatureDelta;
        $md = 0;
        $mdx = $this;
        $f1 = ($mode == self::gmmClosest);
        foreach($this->miniatures as $image) {
            /* @var $image DImage */
            //echo 'min:'.$image.'<br>';
            $x = abs($w - $image->getImgWidth());
            $y = abs($h - $image->getImgHeight());
            if ($f1 && (!$md || $x*$x+$y*$y<$md)) {$md = $x*$x+$y*$y; $mdx = $image;}
            if ($x<$dlt&&$y<$dlt) return $image;
        }
        if ($mode>0 && $mode<10) return DImageMin::createminiature($this, $width, $height, $mode);
        if ($mode == self::gmmClosest) return $mdx;
        return false;
    }
    public function deleteminiatures() {
        if (!$this->isvalid()) return false;
        $items = Item::getlist(ItemRequest::c_children($this->id, false));
        foreach($items as $item) $item->delete();
        //$this->deletechild(true);
    }
    public function fileexists() {
        $fullname = FileSets::$root.$this->value(10,VT::String);
        return file_exists($fullname);
    }
}

class DImageMin extends Item implements IImage
{
    const TypeKey = 0x111;
    
    const WH = 1;
    const FileName = 10;
    
    /*---------------- UTILS --------------*/
    public static function towh($w,$h) {
        return $h*10000+$w;
    }
    public static function fromwh($wh) {
        $x = intval($wh);
        return array(intval($x%10000),intval($x/10000));
    }
    /*------------ ITEM OVERRIDES ---------*/
    public static function defType() {
        return self::TypeKey;
    }
    public function onDelete() {
        $this->vstate = 1;
        $fullname = FileSets::$root.'/'.$this->value(10,VT::String);
        if (file_exists($fullname)) unlink($fullname);
        return true;
    }
    public static function getTypeName() { return 'миниатюра изображения'; }
    /*----------- INTERFACE IMPLEMENTATION ----------*/
    public function getFileName() { return $this->value(10); }
    public function getImgWidth() { $t = self::fromwh($this->v1);return $t[0]; }
    public function getImgHeight() { $t = self::fromwh($this->v1);return $t[1]; }
    public function getImgType() { return 3;}
    /*------------- ACCESSORS -----------------------*/
    public function setWH($width,$height) {$this->v1 = self::towh($width, $height);}

    public static function createminiature(DImage $src,$width,$height,$fittype) {
        $minfile = ImageResizer::resizecopy($src, $width, $height, $fittype);
        if ($minfile->error) return false;
        $minimg = new self;
        $minimg->type = self::TypeKey;
        $minimg->setOwner($src->id);
        $minimg->setWH($minfile->width, $minfile->height);
        $minimg->setvalue(self::FileName, VT::String256, $minfile->filename);
        $src->miniatures[] = $minimg;
        $minimg->write();
        return $minimg;
    }
}

class DImageInterface
{
    public static function printimage($img,$alt,$errortext) {
        if (!($img instanceof IImage)) return $errortext;
        return "<img src=\"{$img->getFileName()}\" width=\"{$img->getImgWidth()}\" height=\"{$img->getImgHeight()}\" alt=\"$alt\">";
    }
//    public static function printminiature($img,$alt,$errortext) {
//        if (!($img instanceof DImageMin)) return $errortext;
//        return "<img src=\"{$img->getFileName()}\" width=\"{$img->getWidth()}\" height=\"{$img->getHeight()}\" alt=\"$alt\">";
//    }
    public static function upload($postname,$multiple,$owner,$makeerrors) {
        $files = UImageFile::upload($postname, $multiple);
        $out = array();
        if (is_array($files)) {
            foreach($files as $file) {  /* @var $file UImageFile */
                if ($file->error) {
                    if ($makeerrors) $out[] = $file->error;
                    continue;
                }
                $file->apply(FileSets::userDir);
                $img = DImage::fromfile($file);
                //$img->setComment($file->srcfilename);
                if ($owner) $img->setOwner($owner);
                $img->write();
                $out[] = $img;
            }
            return $out;
        }
        else {
            if ($files->error) return $files->error;
            $img = DImage::fromfile($file);
            if (!$img) return false;
            if ($owner) $img->setOwner ($owner);
            $img->write();
            return $img;
        }
    }
    public static function reload($imgid,$postname,$makeerrors) {
        $img = DImage::getsingle($imgid,array(10=>VT::String)); /*@var $img DImage */
        if (!$img) DError::raise (0, 'Cant read image from DB');
        $file = UImageFile::upload($postname,false);
        if ($file->error) { if ($makeerrors) return  $file->error; else return false;}
        $img->update($file);
        $img->write();
        return $img;
    }
    public static function getminiature($src,$width,$height,$fittype) {
        if (!($src instanceof DImage)) return false;
        if (!$src->isvalid()) return false;
        $min = $src->getminiature($width, $height);
        if (!$min) $min = DImageMin::createminiature($src, $width, $height, $fittype);
        return $min;
    }
}

Item::registerType(DImage::TypeKey,'DImage');
Item::registerType(DImageMin::TypeKey, 'DImageMin');