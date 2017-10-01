<?php
include_once 'views/ListSmall.php';

class VBlockGroup implements IPageView {
    //private $subitems=false;
    //private $pagefooter;
    private $item;
    private $timestamp;
    private $content=false;
    /*------- Interface implementation -----*/
    public function getContent() {
        if ($this->content===false) $this->Init();
        return $this->content;
    }
    public function getTitle() { return $this->value(10); }

    public function getTimestamp() {
        return $this->timestamp;
    }
    public function Init() {
        $req = ItemRequest::c_children($this->item->id, array(10=>VT::String,20=>VT::StringLong));
        $req->order = array(1=>0); 
        $perpage = $this->item->ppContent();
        //$contenttype = intval($this->v3%100);
        $pb = new PaginalBlock();
        $pb->run($req,$perpage);
        $content = '';
        $timestamp = $this->item->dtupdate;
        foreach($pb->items as $item) {
            $view = getSmallView($item->type);
            if (!$view) $view = getDefaultView($item->type); /* @var $view IView */
            if (!$view) {
                DError::raise(0, "View for type {$item->type} doesn't exists");
                continue;
            }
            $view->Init();
            if ($view->getTimeStamp()>$timestamp) $timestamp = $view->getTimeStamp();
            $content .= $view->getContent();
        }
        $content .= '<div class="pagefooter">'.$pb->pagefooter.'</div>';
        $this->content = $content;
        $this->timestamp = $timestamp;
    }

    public function getDescription() {
        $this->item->value(41);
    }

    public function getKeywords() {
        return $this->item->value(40);
    }

    public static function Create($item) {
        if (!($item instanceof BlockGroup)) return false;
        $x = new self;
        $x->item = $item;
        return $x;
    }

    public function getDefaultSection() {
        return $this->item->v4;
    }

}

