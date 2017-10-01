<?php
include_once 'libs/Item.php';
include_once 'page/IPageBlock.php';

class BlogPhotoreport extends Item implements IModel{
    const TypeKey = 0x211;
    const Title = 10;
    const Text = 20;
    const MetaKeywords = 40;
    const MetaDescription = 41;
    
    /*------------- Item Overrides --------------*/
    public static function defType(){return self::TypeKey;}
    public static function getTypeName(){return 'Блог фотоотчётов';}
    /*---------------- Accessors ----------------*/
    public function setTitle($v){$this->setvalue(BlogPhotoreport::Title,VT::String,$v);}
    public function setText($v){$this->setvalue(BlogPhotoreport::Text,VT::StringLong,$v);}
    public function setMetaKeywords($v){$this->setvalue(BlogPhotoreport::MetaKeywords,VT::String,$v);}
    public function setMetaDescription($v){$v->setvalue(BlogPhotoreport::MetaDescription,VT::String,$v);}

    public function getTitle(){return $this->value(BlogPhotoreport::Title,VT::String);}
    public function getText(){return $this->value(BlogPhotoreport::Text);}
    public function getMetaKeywords(){return $this->value(BlogPhotoreport::MetaKeywords);}
    public function getMetaDescription(){return $this->value(BlogPhotoreport::MetaDescription);}

    public function Access($u){return true;}
    public function Apply(){
        if(Post::set('Title')) $this->setTitle(Post::html('Title'));
        if(Post::set('Text')) $this->setText(Post::any('Text'));
        if(Post::set('MetaKeywords')) $this->setMetaKeywords(Post::html('MetaKeywords'));
        if(Post::set('MetaDescription')) $this->setMetaDescription(Post::html('MetaDescription'));
        $this->write();
        return true;
    }
}
Item::registerType(BlogPhotoreport::TypeKey,'BlogPhotoreport');