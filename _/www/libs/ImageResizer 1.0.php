<?php
include_once 'libs/IFile.php';
include_once 'libs/FileUtils.php';

class ImageResizer extends baseclass implements IImage{
    //----------------- Пользовательские константы -------------
    const memoryLimit = 100000000;
    static $bgcolor = array(255,255,255);
    //--------------------- Ошибки -----------------------------
    const eFileUpload   = 200;
    const eWrongFormat  = 201;
    const eMemoryLimit  = 202;
    const eNoFile       = 203;
    const eServerError  = 204;
    const eZeroSize     = 205;
    //-------------------- Варианты масштабирования -----------
    const ftFit         = 1;   //Сохранение пропорций, пустое место заполняется прозрачным
    const ftCrop        = 2;   //Сохранение пропорций по меньшей стороне, лишнее обрезается
    const ftDistortion  = 3;   //Отмасштабировать с искажением пропорций
    const ftGrow   = 0x0100;   //Если исходное изображение мешьше нового размера, то оно увеличивается
    const ftAutoWH = 0x1000;//Если одно измерений = 0, вычислить его автоматически
    //----------------------- Поля ----------------------------
    public $width=0;
    public $height=0;
    public $imagetype=0;
    public $filename = false;
    public $error=false;
    
    /*---------- INTERFACE IMPLEMENTATION ---------*/
    public function getImgWidth() {return $this->width;}
    public function getImgHeight() {return $this->height;}
    public function getImgType() {return $this->imagetype;}
    public function getFilename() {return $this->filename;}
    
    public static function geterror($errorcode) {
        if ($errorcode<200) return parent::geterror($errorcode);
        switch ($errorcode) {
            case self::eFileUpload:  return "Ошибка загрузки файла";
            case self::eWrongFormat: return "Неверный формат файла";
            case self::eMemoryLimit: return "Не хватило памяти для масштабирования";
            case self::eNoFile:      return "Файл не существует";
            case self::eServerError: return "Ошибка сервера";
        }
    }
        
    private function _getinfo() {
        $result = \getimagesize($this->filepath.$this->filename);
        if (!$result) return false;
        $this->width = $result[0];
        $this->height = $result[1];
        $this->imagetype = $result[2];
        return true;
    }
    
    private static function _rescalecrop($srcwidth,$srcheight,$newwidth,$newheight,$fittype,$grow) {
        //if ($newwidth==0||$newheight==0) return DError::raise($this, 'Ошибка масштабирования - один из размеров равен нулю', DError::levWarning);
        $kw = 1. * $srcwidth / $newwidth;
        $kh = 1. * $srcheight / $newheight;
        if ($fittype==self::ftFit) $k = $kw>$kh?$kw:$kh;
        else $k = $kw<$kh?$kw:$kh;
        if (!$grow&&$k<1) $k = 1.;
        $vw = $newwidth * $k;   // Ширина окна, куда надо вместить, приведённая к размеру рисунка
        $vh = $newheight * $k;  // Высота окна, куда надо вместить, приведённая к размеру рисунка
        $dw = $srcwidth - $vw;
        $dh = $srcheight - $vh;
        if (abs($dw)<2) {$src_x = 0; $dst_x = 0; $src_w = $srcwidth; $dst_w = $newwidth;}
        elseif ($dw>0) {$src_x = $dw / 2; $dst_x = 0; $src_w = $vw;$dst_w = $newwidth;}
        else {$src_x = 0; $dst_x = -$dw / 2 / $k; $src_w = $srcwidth;$dst_w = $srcwidth/$k;}
        if (abs($dh)<2) {$src_y = 0; $dst_y = 0; $src_h = $srcheight; $dst_h = $newheight;}
        elseif ($dh>0) {$src_y = $dh / 2; $dst_y = 0; $src_h = $vh;$dst_h = $newheight;}
        else {$src_y = 0; $dst_y = -$dh / 2 / $k; $src_h = $srcheight;$dst_h = $srcheight/$k;}
        //echo "dst_x = $dst_x, dst_y = $dst_y, src_x = $src_x, src_y = $src_y, dst_w = $dst_w, dst_h = $dst_h, src_w = $src_w, src_h = $src_h<br>";
        return array($dst_x,$dst_y,$src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
    }
    
    private function _getresized($pic,$newwidth,$newheight,$fitflags) {
        if ($this->width==0||$this->height==0) {return DError::raise(self::eZeroSize,'Исходный размер равен нулю', DError::levWarning, baseclass::rfObjectOnly);}
        $auto = ($fitflags & self::ftAutoWH)>0;
        if ($auto) {
            if ($newwidth==0&&$newheight==0) {return DError::raise(self::eZeroSize,'Конечный размер равен нулю', DError::levWarning, baseclass::rfObjectOnly); }
            if ($newwidth==0) $newwidth = intval(1.*$newheight/$this->height*$this->width);
            elseif ($newheight==0) $newheight = intval(1.*$newwidth/$this->width*$this->height);
        }
        else {
            if ($newwidth==0||$newheight==0) {return DError::raise(self::eZeroSize,'Конечный размер равен нулю', DError::levWarning, baseclass::rfObjectOnly);}
        }
        $grow = ($fitflags & self::ftGrow)>0;
        $fittype = $fitflags & 0xFF;
        $newpic = imagecreatetruecolor($newwidth, $newheight);
        $trcolor = imagecolorallocate($newpic, self::$bgcolor[0], self::$bgcolor[1], self::$bgcolor[2]);
        imagefilledrectangle($newpic, 0, 0, $newwidth, $newheight, $trcolor);
        //imagecolortransparent($newpic, $trcolor);
        if ($fittype==self::ftFit||$fittype==self::ftCrop) 
            $dims = self::_rescalecrop($this->width, $this->height, $newwidth, $newheight, $fittype, $grow);
        else
            $dims = array(0,0,0,0,$newwidth,$newheight,$this->width,$this->height);
        imagecopyresampled($newpic, $pic, $dims[0], $dims[1], $dims[2], $dims[3], $dims[4], $dims[5], $dims[6], $dims[7]);
        //$newpic = imagerotate($newpic, 180, 0);
        return array($newpic,$newwidth,$newheight);
    }
    
    public function _resize($newwidth,$newheight,$fitflags,$makecopy,$makeerrors=false) {
        $filename = $this->filename;        
        if (!file_exists($filename)) {return DError::raise(self::eNoFile, "Файл $filename не существует", DError::levError, $makeerrors?baseclass::rfObjectOnly:0);}
        if ($this->imagetype==0) $this->_getinfo();
        if ($this->imagetype<1 || $this->imagetype>3) return DError::raise(self::eNoFile, 'Ошибка масштабирования - неверный формат файла', DError::levWarning, $makeerrors?baseclass::rfObjectOnly:0);
        if ($this->width*$this->height*4>intval(ini_get('memory_limit'))*1024*1024) 
            {return DError::raise(self::eMemoryLimit, 'Недостаточно памяти для обработки изображения - файл слишком большой', DError::levWarning, $makeerrors?baseclass::rfObjectOnly:0);}
        switch ($this->imagetype)
        {
            case 1/*gif*/:$pic = imagecreatefromgif($filename);
                break;
            case 2/*jpg*/:$pic = imagecreatefromjpeg($filename);
                break;
            case 3/*png*/:$pic = imagecreatefrompng($filename);
                break;
        }
        if (!isset($pic)) {return DError::raise(self::eNoFile, 'Не удалось открыть исходное изображение', DError::levError, $makeerrors?baseclass::rfObjectOnly:0);}
        $xx = $this->_getresized($pic, $newwidth, $newheight, $fitflags);
        if (!is_array($xx)) {
            if ($makeerrors) return $xx;
            else return false;
        }
        list($newpic,$nw,$nh) = $xx;
        imagedestroy($pic);
        if ($makecopy) {
            $newfilename = FileUtils::GenerateFilename($this->filename);
            $this->filename = $newfilename;
        }
        else {
            $newfilename = $this->filename;
        }

        if (!imagepng($newpic,$newfilename)) 
                return DError::raise(self::eServerError,'Невозможно создать/перезаписать файл',DError::levError,$makeerrors?baseclass::rfObjectOnly:0);
        imagedestroy($newpic);    
        $this->width = $nw;
        $this->height = $nh;
        $this->imagetype = 3;
        return $this;
    }
    
    public static function resize($image,$newwidth,$newheight,$fitflags) {
        $x = new self;
        if ($image instanceof IImage) {
            $x->width = $image->getImgWidth();$x->height=$image->getImgHeight();
            $x->imagetype = $image->getImgType();$x->filename = $image->getFilename();
        }
        elseif ($image instanceof IFile) {
            $x->filename = $image->getFilename();
            $x->_getinfo();
        }
        $r = $x->_resize($newwidth, $newheight, $fitflags, false,true);
        if ($r instanceof DError) $x->error = $r;
        return $x;
    }
    public static function resizecopy($image,$newwidth,$newheight,$fitflags) {
        $x = new self;
        if ($image instanceof IImage) {
            $x->width = $image->getImgWidth();$x->height=$image->getImgHeight();
            $x->imagetype = $image->getImgType();$x->filename = $image->getFilename();
        }
        elseif ($image instanceof UFile) {
            $x->filename = $image->filename;
            $x->_getinfo();
        }
        $r = $x->_resize($newwidth, $newheight, $fitflags, true,true);
        if ($r instanceof DError) $x->error = $r;
        return $x;
    }
}

