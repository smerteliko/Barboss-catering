<?php
include_once 'libs/Interfaces.php';

class Slider extends Item implements IImage {
    const Key = 0x100100;
    
    const width = 600;
    const height = 400;
    
    const Order = 1;
    const Link = 20;
    /*--------------- Item Overrides ---------------------*/
    public static function defType() {
        return self::Key;
    }
    public function onDelete() {
        $this->vstate = 1;
        $fullname = FileSets::$root.'/'.$this->value(10,VT::String);
        if (file_exists($fullname)) unlink($fullname);
        return true;
    }

    /*--------------- Interface Implementation -----------*/
    public function getFilename() {
        return $this->value(10);
    }
    public function getImgHeight() {
        return self::height;
    }
    public function getImgType() {
        return 3;
    }
    public function getImgWidth() {
        return self::width;
    }
    /*-------------- Accessors ----------------------------*/
    public function setFileName($filename) {$this->setvalue(10, VT::String, $filename);}
    public function getLink() {return $this->value(20);}
    public function setLink($link) {$this->setvalue(20, VT::String, $link);}
    public function setOrder($order) {$this->v1 = $order;}
    public function getOrder() {return $this->v1;}
    /*---------------- Utils ------------------------------*/
    public function printSlide($style,$num) {
        return "<img id=\"im{$num}\" style=\"$style\" src=\"{$this->value(10)}\">";
    }
    
    public static function go() {
        $banners = Slider::getlist(ItemRequest::c_list(array(10=>VT::String256,20=>VT::String256),array(1=>0)));
        if (count($banners)>1) {
            $n = 1;
            $t = false;
            $h = false;
            foreach($banners as $banner) { /*@var $banner Slider */
                $link = $banner->getLink();
                if ($link) $link = "'".$link."'";
                else $link = 'null';
                if (!$t) {
                    //$t = "['".$banner->printSlide('position: absolute; filter: alpha(opacity=0);',$n)."'";
                    $t = "['".$banner->getFilename()."'";
                    $h = "[".$link;
                }
                    else {
                        //$t .= ",'".$banner->printSlide('position: absolute;opacity: 0; filter: alpha(opacity=0);',$n)."'";
                        $t .= ",'".$banner->getFilename()."'";
                        $h .= ",".$link;
                    }
                $n++;
            }
            $t .= ']';
            $h .= ']';
            $sw = self::width;
            $sh = self::height;
            $out = "<script type=\"text/javascript\">SliderStart($sw,$sh,$t,$h)</script>";
            $link = $banners[0]->getLink();
            if ($link) $link = ' href="'.$link.'"';
            else $link = '';
            //$link = '';
            $out .= '<a id="bannerref"'.$link.'><canvas id="banner" width="'.self::width.'" height="'.self::height.'"></canvas>';
            //$out .= $banners[0]->printSlide('width:100%',1);
            //$out .= '</div></a>';
            $out .= '</a>';
        }
        elseif (count($banners)==1) {
            $link = $banners[0]->getLink();
            $out = '';
            if ($link) $out .= '<a href="'.$link.'">';
            $out .= '<div id="banner" style="width:100%">'.$banners[0]->printSlide('width:100%',1).'</div>';
            if ($link) $out .= '</a>';
        }
        return $out;
    }
}