<?php

class Editor {
    const applier = 'applier.php';
    
    public static function preedit($item, $backLink = false, $list = false) { /*@var $item Item*/
        if (!$item) return DError::raise(0, 'null value');
        echo DForm::Hidden('itemtype',$item->type);
        if ($item->isvalid()) echo DForm::Hidden('itemid', $item->id);
        else echo DForm::Hidden('itemid', -1);
        if(Get::set('tiny') && Get::int('tiny') == 0) echo DForm::Hidden ('tiny', Get::int('tiny'));
        if (Get::set('parent')) echo DForm::Hidden ('parent', Get::int('parent'));
        if(!$backLink) $backLink = Server::$from;
        echo DForm::Hidden('backlink', $backLink);
        if($list) {echo DForm::Hidden('list', $list);}
    }
    
    public static function preedit2($item) {
        if (!$item) return DError::raise(0, 'null value');
        $t = DForm::Hidden('itemtype',$item->type);
        if ($item->isvalid()) $t .= DForm::Hidden('itemid', $item->id);
        else $t .= DForm::Hidden('itemid', -1);
        if (Get::set('parent')) $t .= DForm::Hidden ('parent', Get::int('parent'));
        return $t;
    }
    
    public static function getlist($type) {
        $classname = Item::getClassnameByType($type);
        $editor = call_user_func(array($classname,'getEditorClass'));
        if (!class_exists($editor)) return DError::raise(0, "class $editor not found");
        if (method_exists ($editor, 'getlist'))
            return call_user_func(array($editor,'getlist'));
        else
            return DError::raise(0, 'getlist not supported by '.$editor);
    }
    
    public static function edit($item) {
        $classname = get_class($item);
        $editor = call_user_func(array($classname,'getEditorClass'));
        if (!class_exists($editor)) return DError::raise(0, "class $editor not found");
        if (!class_exists($editor)) return false;
        if (method_exists ($editor, 'edit'))
            return call_user_func(array($editor,'edit'),$item);
        else
            return DError::raise(0, 'edit not supported by '.$editor);
    }
    
    public static function defaultlist($type,$name=false) {
        echo '<div class="cntnt">';
        if($name) echo "<h2>$name</h2>";
        echo '<div class="addIt">'.Html::ref('admin.php?add='.$type, 'Добавить','link_Green').'</div>';
        $items = Item::getlist(ItemRequest::c_type($type,array(10=>VT::String)));
        echo '<table class="admTb">';
        $html = '';
        foreach($items as $item){
            $name = Editor::getCutName($item->getName());
            if(!$name) $name = '<Без имени>';
            echo '<tr id="il'.$item->id.'"><td>'.Html::ref('admin.php?itemid='.$item->id,$name,'link_Blue')
                .'</td><td>'.$html.'</td><td>'."<a href=\"javascript:adm_remove({$item->id})\">".Icons::Trash.'</a></td></tr>';
        }
        echo '</table></div>';
        return true;
    }
    
    public static function getNextChildOrder($parentid){
        $req = ItemRequest::c_children($parentid, false);
        $order = $req->getAgregation(array(1=>'max')) + 1;
        return $order;
    }
    
    public static function getCutName($name){
        if(strlen($name) > 120){
            $name =substr($name,0,120);
            $name =substr($name,0,strrpos($name, ' ')).'...';
        }elseif(strlen($name) == 0) $name = '<i>Без названия</i>';
        return $name;
        
    }
}