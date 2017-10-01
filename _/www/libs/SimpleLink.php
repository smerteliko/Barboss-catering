<?php
class SimpleLink extends Item {
    const TypeKey = 0x1000;
    
    /*------- Item Overrides --------------*/
    public static function defType() { return self::TypeKey; }
    public static function defFlags() { return Item::fLink|Item::fV2Parent; }
    /*-------------------------------------*/
    public static function resolveLinks(array& $items,$values,$askeyarray=false) {
        $ids = array();
        foreach($items as $item /*@var $item self*/) {
            if ($item->checkflags(Item::fLink)) {
                $ids[] = $item->v3;
            }
        }
        if (count($ids)==0) return;
        $req = ItemRequest::c_list($values);
        $req->idlist = $ids;
        $objs = Item::getlist($req,false,Item::rfAutoType|Item::rfAsKeyArray);
        foreach($items as $id=>$item) {
            if ($item->checkflags(Item::fLink)) {
                if ($askeyarray) $tid = $item->v3;
                else $tid = $id;
                $items[$tid] = $objs[$item->v3];
                $items[$tid]->_tempflags |= Item::tfResolvedLink;
                $items[$tid]->addproperty('linkid', $item->id);
            }
        }
    }
    public static function resolvetoarray(array& $items,$values) {
         $ids = array();
        foreach($items as $item /*@var $item self*/) {
            if ($item->checkflags(Item::fLink)) {
                $ids[] = $item->v3;
            }
        }
        $out = array();
        if (count($ids)==0) return $out;
        $req = ItemRequest::c_list($values);
        $req->idlist = $ids;
        $objs = Item::getlist($req,false,Item::rfAutoType|Item::rfAsKeyArray);
        foreach($items as $id=>$item) {
            if ($item->checkflags(Item::fLink)) {
                $out[$item->v3] = $objs[$item->v3];
                $out[$item->v3]->_tempflags |= Item::tfResolvedLink;
                $out[$item->v3]->addproperty('linkid', $item->id);
            }
        }
        return $out;
    }
    /** @return self */
    public static function createlink($masterid,$slaveid,$order=0,$data=0) {
        $link = self::create();
        $link->v1 = $order;
        $link->v2 = $masterid;
        $link->v3 = $slaveid;
        $link->v4 = $data;
        return $link;
    }
    
    public static function applylinks($parentitem,$slavesid) {
        $req = ItemRequest::c_children($parentitem->id,false);
        $req->linksonly = true;
        $oldlinks = Item::get($req,false,Item::rfAsKeyArray);
        self::resolveLinks($oldlinks, false, true);
        foreach($slavesid as $id) {
            if (isset($oldlinks[$id])) {
                unset($oldlinks[$oldlinks[$id]->getLinkid()]);
                continue;
            }
            $link = self::createlink($parentitem->id, $id);
            $link->write();
        }
        foreach($oldlinks as $link) {
            if ($link->checkflags(Item::fLink)) $link->delete();
        }
    }
    public static function deleteparentlinks($childid) {
        $req00 = ItemRequest::c_type(self::TypeKey);
        $req00->where = array(3=>$childid);
        $links = Item::getlist($req00,false);
        if ($links) foreach($links as $link0) $link0->delete();
    }
}

Item::registerType(0x1000, 'SimpleLink');