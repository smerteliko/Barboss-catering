<?php
class VPhotoreport implements IPageView{
    private $item;
    public static function Create($item){
        if(!($item instanceof Photoreport)) return false;
        $x = new self;
        $x->item = $item;
        return $x;
    }
    public function Init(){}
    public function getDefaultSection(){return false;}
    public function getContent(){
        $item = $this->item;
        if(!$item->checkflags(Item::fEnabled)) return '<h3>Страница не доступна</h3>';
        if($item->getDate() > time()) return '<h3>Страница не доступна</h3>';
        $values = array(DImage::FileName=>VT::String,DImage::Alt=>VT::String,DImage::Comment=>VT::String);
        $req = ItemRequest::c_list($values);
        $req->parent = $item->id;
        $req->order = array(DImage::SortOrder=>0);
        $imgs = DImage::getlist($req);
        $img_cnt = '';
        foreach($imgs as $img){
            $min = $img->getminiature(500,280,DImage::gmmClosest);
            //$comment = $img->getComment();
            //if($comment != '') $comment = '<div class="comment">'.$comment.'</div>';
            $img_cnt.= '<div class="magnific"><a href="'.$img->getFileName().'" alt="'.$img->getAlt().'"><img src="'.$min->getFileName().'"></a></div>';
            //$img_cnt.= $comment;
        }
        $date = '<p class="date">'.$item->getDateFormat().'</p>';
        $cnt = '<div id="'.$item->id.'" class="photoreport">'.'<h2>&bull;&nbsp;'.$item->getTitle().'&nbsp;&bull;</h2>'.$date.$item->getText().$img_cnt.'</div>';
        return $cnt;
    }
    public function getPreview(){
        $item = $this->item;
        $cnt = Html::ref('page.php?id='.$item->id,'<h3>'.$item->getTitle().'</h3>');
        $cnt.= '<p class="date">'.$item->getDateFormat().'</p>';
        $req = ItemRequest::c_list(array(DImage::FileName=>VT::String));
        $req->parent = $item->id;
        $req->order = array(DImage::SortOrder=>0);
        $imgs = DImage::getlist($req);
        if(count($imgs) > 0){
            $min = $imgs[0]->getminiature(500,280,DImage::gmmClosest);
            $cnt.= Html::ref('page.php?id='.$item->id,'<img src="'.$min->getFileName().'" alt="'.$item->getTitle().'">');
        }
        $cnt = '<div id="'.$item->id.'" class="blog-photoreport">'.$cnt.'</div>';
        return $cnt;
    }
    public function getDescription(){return $this->item->getMetaDescription();}
    public function getKeywords(){return $this->item->getMetaKeywords();}
    public function getTimestamp(){return $this->item->dtupdate;}
    public function getTitle(){return $this->item->getName();}
    public static function content($item,$preview = false){

    }
}