<?php
class DefaultPhotoreport implements IListView {
    public static function getList($type){
        echo '<div class="cntnt">';
        echo '<h2>'.call_user_func(array(getEditorView($type),'eName')).'</h2>';//getEditorView() - ListEditor.php
        echo '<div class="addIt">'.Html::ref('admin.php?add='.$type, 'Добавить','link_Green').'</div>';
        $req = ItemRequest::c_list(array(10=>VT::String));
        $req->order = array(1=>1);
        $items = Photoreport::getlist($req);
        echo '<table class="admTb">';
        foreach($items as $item){
            $name = Editor::getCutName($item->getName());
            if(!$name) $name = '<Без имени>';
            $color = 'link_Gray';
            $date = $item->getDateFormat();
            if($item->getDate() > time()) $date = '<span style="color:orange;">'.$date.'</span>';
            if($item->checkflags(Item::fEnabled)) $color = 'link_Blue';
            echo '<tr id="il'.$item->id.'"><td>'.Html::ref('admin.php?itemid='.$item->id,$name,$color)
                .'</td><td>'.$date.'</td><td>'."<a href=\"javascript:adm_remove({$item->id})\">".Icons::Trash.'</a></td></tr>';
        }
        echo '</table></div>';
        return true;
    }
}