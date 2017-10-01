<?php
abstract class BaseView implements IView {
    protected $item;
    
    /* Implementation */
    public function Init() {
        
    }
    public function getTimeStamp() {
        return $this->item->dtupdate;
    }

    public static function Create($item) {
        $classname = get_called_class();
        $x = new $classname;
        $x->item = $item;
        return $x;
    }
    
    /* Abstract */
    public abstract function getContent();
}
