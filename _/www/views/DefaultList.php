<?php
class DefaultList implements IListView {
    public static function getList($type){
        echo '<div class="cntnt">';
        echo '<h2>'.call_user_func(array(getEditorView($type),'eName')).'</h2>';//getEditorView() - ListEditor.php
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
}