<?php
class PageConstructor extends Item implements IModel {
    ///////////////////////////////////////
    // Страницы имеют коды 0x200-0x20F
    ///////////////////////////////////////
    const TypeKey = 0x200;

    const DefaultSection = 4;
    const MetaKeywords = 40;
    const MetaDescription = 41;
    
    /*------------ Item Overrides -------------*/
    public static function defType() { return self::TypeKey; }
    public static function defFlags() { return Item::fHasChild; }
    public static function getTypeName() {return 'Составная страница';}
    
    /*-------------- Accessors ----------------*/
    public function setPreview($text) { $this->setvalue(20,VT::StringLong,$text); }
    public function setMetaKeywords($kw) { $this->setvalue(40, VT::String, $kw); }
    public function getMetaKeywords() { return $this->value(40); }
    public function setMetaDescription($text) { $this->setvalue(41, VT::String, $text); }
    public function getMetaDescription() { return $this->value(41); }
    public function setDefaultSection($sectionid) { $this->v4 = $sectionid; }
    public function getDefaultSection() { return $this->v4; }

    public function Access($user) {
        return true;
    }

    public function Apply() {
        if (Post::set('linksapply')) return self::applylinks($this);
        if (Post::set('sortapply')) return self::applysort($this);
        if (Post::set('DefaultSection')) $this->setDefaultSection(Post::int('DefaultSection'));
        $this->setName(Post::any('title'));
        $this->setMetaKeywords(Post::html('keywords'));
        $this->setMetaDescription(Post::any('description'));
        $this->write();
    }
    public static function applylinks($page) {
        $linksset = Post::indexedarrayset('block');
        $reqcnt = ItemRequest::c_children($page->id, false);
        $maxsort = $reqcnt->getAgregation(array(1=>'max'));
        
        if ($linksset) foreach($linksset as $id=>$link){
            $link = SimpleLink::createlink($page->id, $id, ++$maxsort);
            $link->write();
        }
        return true;
    }
    
    public static function applysort($page) {
        $sortnew = Post::indexedarrayint('blockorder');
        $items = Item::getlist(ItemRequest::c_children($page->id, false),false,Item::rfAsKeyArray);
        if ($sortnew) foreach($sortnew as $id=>$value) {
            if (!isset($items[$id])) continue;
            if ($items[$id]->v1!=$value) {
                $items[$id]->v1 = $value;
                $items[$id]->write();
            }
        }
        $req = ItemRequest::c_children($page->id, array());
        $req->order = array(1=>0);
        $items = Item::getlist($req);
        $i = 0;
        foreach($items as $k=>$item){
            $i++;
            if($item->v1 != $i){
                $items[$k]->v1 = $i;
                $items[$k]->write();
            }
        }
    }
}

