<?php
include_once 'views/ListSmall.php';

class VPageConstructor implements IPageView {
    private $item; /* @var $item PageConstructor */
    private $content;
    private $timestamp;
    // Constructor
    public static function Create($item) {
        if (!($item instanceof PageConstructor)) return false;
        $x = new self;
        $x->item = $item;
        return $x;
    }
    // Interface implementation
    public function Init() {
        $req = ItemRequest::c_children($this->item->id, array(10=>VT::String,20=>VT::StringLong));
        $req->order = array(1=>0);
        $items = Item::getlist($req,false,Item::rfAutoType);
        SimpleLink::resolveLinks($items, array(10=>VT::String,13=>VT::String,20=>VT::StringLong));
        $this->content = '';
        $this->timestamp = 0;
        foreach($items as $item){/*@var $item IPageBlock*/
            $classname = getSmallView($item->type);
            if (!$classname) $classname = getDefaultView($item->type);
            if (!$classname) {
                DError::raise(0, 'View for class '.get_class($item).' does not exist');
                continue;
            }
            $view = call_user_func(array($classname, 'Create'),$item);
            if (!($view instanceof IView)) {
                DError::raise(0, "Class $classname in not IView");
                continue;
            }
            $view->Init();
            if ($item->dtupdate>$this->timestamp) $this->timestamp = $item->dtupdate;
            $this->content .= $view->getContent();
        }
    }
    public function getContent() {
        return $this->content;
    }
    public function getTimestamp() {
        return $this->timestamp;
    }
    public function getTitle() {
        return $this->item->getName();
    }
    public function getDescription() {
        return $this->item->getMetaKeywords();
    }

    public function getKeywords() {
        return $this->item->getMetaDescription();
    }

    public function getDefaultSection() {
        return $this->item->v4;
    }

}
