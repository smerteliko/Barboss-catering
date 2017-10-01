<?php
include_once 'libs/UImageFile.php';
include_once 'libs/ImageResizer.php';

class Slider extends Item implements IModel{
    const Key = 0x120;
    const width = 800;
    const height = 380;
    const Order = 1;
    const Link = 20;
    /*--------------- Item Overrides ---------------------*/
    public static function defType(){return self::Key;}
    public function onDelete(){
        $this->vstate = 1;
        $fullname = FileSets::$root.'/'.$this->value(10,VT::String);
        if (file_exists($fullname)) unlink($fullname);
        return true;
    }
    /*-?-*/
    public function getFilename(){return $this->value(10);}
    public function getImgHeight(){return self::height;}
    public function getImgType(){return 3;}
    public function getImgWidth(){return self::width;}
    public function Access($u){return true;}
    public function Apply(){}
    public static function ApplySlider(){
        if(Post::set('bannerapply')){
            $dels = Post::indexedarrayset('bannerdel');
            $links = Post::indexedarrayhtml('bannerlink');
            $orders = Post::indexedarrayint('bannerorder');
            if($dels) foreach($dels as $id){
                $item = Slider::read($id,false);
                $item->delete();
                unset($links[$id]);
            }
            if($links) foreach($links as $id=>$link){
                $item = Slider::read($id,false);
                $item->setLink($link);
                if (isset($orders[$id])) $item->setOrder($orders[$id]);
                $item->write();
            }
        }
        if(Post::set('newbannerapply')){
            $req = ItemRequest::c_type(Slider::Key,false);
            $last = $req->getAgregation(array(1=>'max'));
            $item = Slider::create();
            $item->setLink(Post::html('newbannerlink'));
            $item->setOrder($last+1);
            $file = UImageFile::upload('newbannerimg');
            if ($file){
                ImageResizer::resize($file, Slider::width, Slider::height, ImageResizer::ftDistortion);
                $file->apply(FileSets::userDir);
                $item->setName($file->getFileName());
            }
            else echo DError::$lasterror;
            $item->write();
        }
    }
    /*-------------- Accessors ----------------------------*/
    public function setFileName($filename){$this->setvalue(10, VT::String, $filename);}
    public function getLink(){return $this->value(20);}
    public function setLink($link){$this->setvalue(20, VT::String, $link);}
    public function setOrder($order){$this->v1 = $order;}
    public function getOrder(){return $this->v1;}
    /*---------------- Utils ------------------------------*/
    public function printSlide($style,$num){
        return "<img id=\"im{$num}\" style=\"$style\" src=\"{$this->value(10)}\">";
    }
    
    public static function go(){
        $out = '';
        $banners = Slider::getlist(ItemRequest::c_list(array(10=>VT::String256,20=>VT::String256),array(1=>0)));
        if (count($banners)>1){
            $n = 1;
            $t = false;
            $h = false;
            foreach($banners as $banner){ /*@var $banner Slider */
                $link = $banner->getLink();
                if ($link) $link = "'".$link."'";
                else $link = 'null';
                if (!$t){
                    //$t = "['".$banner->printSlide('position: absolute; filter: alpha(opacity=0);',$n)."'";
                    $t = "['".$banner->getFilename()."'";
                    $h = "[".$link;
                }
                    else{
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
            $out .= '<a id="bannerref"'.$link.'><canvas id="banner" width="'.self::width.'" height="'.self::height.'"></canvas>';
            //$out .= $banners[0]->printSlide('width:100%',1);
            $out .= '</a>';
        }
        elseif (count($banners)==1){
            $link = $banners[0]->getLink();
            $out = '';
            if ($link) $out .= '<a href="'.$link.'">';
            $out .= '<div id="banner" style="width:100%">'.$banners[0]->printSlide('width:100%',1).'</div>';
            if ($link) $out .= '</a>';
        }
        return $out;
    }
}