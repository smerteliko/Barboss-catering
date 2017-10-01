<?php
include_once 'libs/Item.php';
include_once 'page/IPageBlock.php';


class TextBlock extends Item implements IModel {
    ///////////////////////////////////////
    // Элементы страниц имеют коды 0x230-0x2FF
    ///////////////////////////////////////
    const TypeKey = 0x230;
    
    const Order = 1;
    const Parent = 2;
    const Timestamp  = 4;
    
    const Title = 10;
    const ShortText = 20;
    const FullText = 30;
    const MetaKeywords = 40;
    const MetaDescription = 41;
    const CSS = 42;
    
    ///////////////////////////////////////////////
    /*------------- Styles Info -----------------*/
    ///////////////////////////////////////////////
    //   TextBlock - for main block
    //   TextBlockPreview - for preview
    //      H2 - header
    //      P - body
    //////////////////////////////////////////////
    
    /*------------- Item Overrides --------------*/
    public static function defType() { return self::TypeKey; }
    public static function getTypeName() {return 'Блок текста';}
    /*------- Interface implementation ----------*/
    public static function getEditorClass() { return 'TextBlockEditor'; }
    /*---------------- Accessors ----------------*/
    public function setTitle($title) { $this->setvalue(10, VT::String, $title);}
    public function getTitle(){return $this->value(TextBlock::Title,VT::String);}
    public function setShortText($text) { $this->setvalue(20, VT::StringLong, $text);}
    public function getShortText() { return $this->value(20); }
    public function setFullText($text) { $this->setvalue(30, VT::StringLong, $text);}
    public function getFullText() { return $this->value(30); }
    public function setCSS($text) { $this->setvalue(TextBlock::CSS, VT::StringLong, $text);}
    public function getCSS() { return $this->value(TextBlock::CSS, VT::StringLong); }
    public function setTime($ts) { $this->v4 = $ts; }
    public function getTime() { return $this->v4; }

    public function setMetaKeywords($kw) { $this->setvalue(40, VT::String, $kw); }
    public function getMetaKeywords() { return $this->value(40); }
    public function setMetaDescription($text) { $this->setvalue(41, VT::String, $text); }
    public function getMetaDescription() { return $this->value(41); }


    public function Access($user) {
        return true;
    }

    public function Apply() {
        $parent = Post::int('parent');
        if($parent){
            $this->setOwner($parent);
            $req = ItemRequest::c_children($parent, false);
            $this->v1 = $req->getAgregation(array(1=>'max')) + 1;
        }
        if(Post::set('mkw')) $this->setMetaKeywords(Post::html('mkw'));
        if(Post::set('mdiscr')) $this->setMetaDescription(Post::html('mdiscr'));
        if(Post::set('datetime')) $this->setTime(DateTimeFormatter::RussianToTimestamp(Post::any('datetime')));
        if(Post::set('title')) $this->setTitle(Post::html('title'));
        if(Post::set('shorttext')) $this->setShortText(Post::any('shorttext'));
        if(Post::set('fulltext')) $this->setFullText(Post::any('fulltext'));
        if(Post::set('css')) $this->setCSS(Post::any('css'));
        $this->write();
        return true;
    }

}

Item::registerType(TextBlock::TypeKey, 'TextBlock');