<?php
class VBlogPhotoreport implements IPageView{
    private $item;
    public static function Create($item){
        if(!($item instanceof BlogPhotoreport)) return false;
        $x = new self;
        $x->item = $item;
        return $x;
    }
    public function Init(){}
    public function getDefaultSection(){return false;}
    public function getContent($cnt = ''){
        $req = ItemRequest::c_list(array(10=>VT::String));
        $req->order = array(1=>1);
        $items = Photoreport::getlist($req);
        foreach($items as $item){
            if(!$item->checkflags(Item::fEnabled)) continue;
            if($item->getDate() > time()) continue;
            $viewclassname = getPageView(Photoreport::defType());
            $view = call_user_func(array($viewclassname,'Create'),$item);
            $view->Init();
            $cnt.= $view->getPreview();//см. model/Photoreport.php
        }
        return $cnt;
    }
    public function getDescription(){return $this->item->getMetaDescription();}
    public function getKeywords(){return $this->item->getMetaKeywords();}
    public function getTimestamp(){return $this->item->dtupdate;}
    public function getTitle(){return $this->item->getName();}
}