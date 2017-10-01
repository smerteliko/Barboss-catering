<?php
include_once 'libs/Item.php';
include_once 'page/IPageBlock.php';

class Photoreport extends Item implements IModel{
    const TypeKey = 0x23A;
    const Date  = 1;
    const Title = 10;
    const Text = 20;
    const MetaKeywords = 40;
    const MetaDescription = 41;
    
    /*------------- Item Overrides --------------*/
    public static function defType() { return self::TypeKey; }
    public static function getTypeName() {return 'Фотоотчёт';}
    /*---------------- Accessors ----------------*/
    public function setDate($v){$this->v1 = $v;}
    public function setTitle($v){$this->setvalue(Photoreport::Title,VT::String,$v);}
    public function setText($v){$this->setvalue(Photoreport::Text,VT::StringLong,$v);}
    public function setMetaKeywords($v){$this->setvalue(Photoreport::MetaKeywords,VT::String,$v);}
    public function setMetaDescription($v){$v->setvalue(Photoreport::MetaDescription,VT::String,$v);}

    public function getDate(){return $this->v1;}
    public function getDateFormat(){
        $m = Array(1=>'Январь',2=>'Февраль',3=>'Март',4=>'Апрель',5=>'Май',6=>'Июнь',7=>'Июль',8=>'Август',9=>'Сентябрь',10=>'Октябрь',11=>'Ноябрь',12=>'Декабрь');
        $t = $this->getDate();
        $mK = (int)Date('n',$t);
        return Date('j',$t).' '.$m[$mK].' '.Date('Y',$t).' г.';
    }
    public function getTitle(){return $this->value(Photoreport::Title,VT::String);}
    public function getText(){return $this->value(Photoreport::Text);}
    public function getMetaKeywords(){return $this->value(Photoreport::MetaKeywords);}
    public function getMetaDescription(){return $this->value(Photoreport::MetaDescription);}

    public function Access($u){return true;}
    public function Apply(){
        $timestamp = Post::any('day').'.'.Post::any('month').'.'.Post::any('year');
        $timestamp = strtotime($timestamp);
        $this->setDate($timestamp);
        if(Post::any('fEnabled')) $this->setflags(Item::fEnabled);
        else $this->unsetflags(Item::fEnabled);
        if(Post::set('Title')) $this->setTitle(Post::html('Title'));
        if(Post::set('Text')) $this->setText(Post::any('Text'));
        if(Post::set('MetaKeywords')) $this->setMetaKeywords(Post::html('MetaKeywords'));
        if(Post::set('MetaDescription')) $this->setMetaDescription(Post::html('MetaDescription'));
        $this->write();
        return true;
    }
}
Item::registerType(Photoreport::TypeKey,'Photoreport');