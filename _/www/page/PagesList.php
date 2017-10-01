<?php

class PagesList {
    public static function get($itemid,$attrname) {
        $pages = Item::getlist(ItemRequest::c_type(array(0x200,0x22F), array(10=>VT::String)),false,Item::rfAutoType);
        foreach($pages as $page) {
            if (!($page instanceof IPageBlock)) continue;
            echo $page->getName().' '.Html::ref("admin.php?itemid=$itemid&amp;{$attrname}={$page->id}", 'выбрать').'<br>';
        }
    }
}