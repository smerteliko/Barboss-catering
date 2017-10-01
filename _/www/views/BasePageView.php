<?php

abstract class BasePageView implements IPageView {
    protected $item;
    /* Implementation */
    public function Init() {
        
    }

    public function getDescription() {
        return '';
    }

    public function getKeywords() {
        return '';
    }

    public function getTimeStamp() {
        return $this->item->dtupdate;
    }

    public function getTitle() {
        return $this->getName();
    }

    public static function Create($item) {
        $classname = get_called_class();
        $x = new $classname;
        $x->item = $item;
        return $x;
    }
    public function getDefaultSection() {return false;}
    /* Abstract */
    public abstract function getContent();
}
