<?php
class VTextBlock implements IPageView{
    private $item;
    public static function Create($item){
        if(!($item instanceof TextBlock)) return false;
        $x = new self;
        $x->item = $item;
        return $x;
    }
    public function Init(){}
    public function getDefaultSection(){return false;}
    public function getContent(){return '<div id="t'.$this->item->id.'">'.$this->item->getShortText().'</div>';}
    public function getDescription(){return $this->item->getMetaDescription();}
    public function getKeywords(){return $this->item->getMetaKeywords();}
    public function getTimestamp(){return $this->item->dtupdate;}
    public function getTitle(){return $this->item->getName();}
}