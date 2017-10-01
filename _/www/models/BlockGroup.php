<?php
include_once 'libs/Item.php';
//include_once 'libs/SimpleLink.php';
include_once 'page/IPageBlock.php';
include_once 'page/PaginalBlock.php';

class BlockGroup extends Item implements IModel{
    ///////////////////////////////////////
    // Блоки имеют коды 0x210-0x22F
    ///////////////////////////////////////
    const TypeKey = 0x210;
    const Editor = 'BlockGroupEdit';
    const LinkPage = 10;
    
    const Order = 1;
    const Parent = 2;
    const Flags = 3;
    const DefaultSection = 4;
    
    const MetaKeywords = 40;
    const MetaDescription = 41;
    /*--------------- Utils -------------------*/
    function ppPreview() { return $this->v3%100;}
    function ppContent() { return intval($this->v3/100);}
    /*--------- Item Overrides ---------*/
    public static function defType() { return self::TypeKey; }
    public static function defFlags() { return Item::fHasChild; }
    public static function getTypeName() {return 'Группа блоков';}
    public static function getEditorClass() {
        return 'BlockGroupEditor';
    }
    /*------------ Accessors ----------------*/
    public function setTitle($title) { $this->setvalue(10, VT::String, $title);}
    public function setShortText($text) { $this->setvalue(20, VT::String, $text);}
    public function getShortText() { return $this->value(20); }
    public function setFullText($text) { $this->setvalue(30, VT::StringLong, $text);}
    public function getFullText() { return $this->value(30); }
    public function setTimestamp($ts) { $this->setvalue(15, VT::Int, $ts); }

    public function setMetaKeywords($kw) { $this->setvalue(40, VT::String, $kw); }
    public function getMetaKeywords() { return $this->value(40); }
    public function setMetaDescription($text) { $this->setvalue(41, VT::String, $text); }
    public function getMetaDescription() { return $this->value(41); }
   
    public function setPerPage($preview, $content){
        $this->v3 = $content*100 + $preview;
    }

    public function Access($user) {
        return true;
    }

    public function Apply() {
        if (Post::set('linksapply')) return self::applylinks ($page);
        if (Post::set('sortapply')) return self::applysort ($page);
        $this->setName(Post::any('title'));
        $this->setPerPage(Post::int('previewperpage'), Post::int('contentperpage'));
        $this->write();
        return true;
    }
    
    public static function applylinks($page){
        $linksset = Post::indexedarrayset('block');        
        $reqcnt = ItemRequest::c_children($page->id, false);
        $maxsort = $reqcnt->getAgregation(array(1=>'max'));
        if($linksset) foreach($linksset as $id=>$v){
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

Item::registerType(BlockGroup::TypeKey, 'BlockGroup');