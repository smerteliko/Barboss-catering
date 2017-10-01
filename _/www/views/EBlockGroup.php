<?php

class EBlockGroup extends BasePageView {
    public static function eName(){return'Список групп блоков';}
    
    public function getContent() {
        $page = $this->item;
        $content = DForm::Form($_SERVER['REQUEST_URI'])
                   .Editor::preedit2($page);
        if ($page->getOwner())
            $parent = Item::read($page->getOwner(),array(10=>VT::String));
        else $parent = 0;
        if ($parent) $pname = $parent->getName();
        else $pname = 'Нет';
        $content .= '<h2>Группа блоков</h2>'
            .'<b>Заголовок</b><br>'.DForm::Text_s('title', $page->getName(), 'width:200px').'<br><br>'
            .'Элементов на странице(контент):'.DForm::Text_s('contentperpage', $page->ppContent(), 'width:25px').'<br>'
            .'Элементов на странице(превью):'.DForm::Text_s('previewperpage', $page->ppPreview(), 'width:25px').'<br>'
            .DForm::Submit('save-self', 'Сохранить').'  '.DForm::Submit('save-close', 'Сохранить и закрыть')
            .'</form>';
        if ($page->isvalid()) {
            $reqsel = ItemRequest::c_children($page->id, array(10=>VT::String));
            $reqsel->order = array(1=>0);
            $selblocks = Item::getlist($reqsel,false,Item::rfAutoType);
            SimpleLink::resolveLinks($selblocks, array(10=>VT::String));
            if(count($selblocks) > 0){
                $content .= '<table class="group"><caption>Существующие объекты</caption>';
                $i = 0;
                foreach($selblocks as $block /*@var $block Item */){
                    $i++;
                    $name = Editor::getCutName($block->getName());
                    $link = Html::ref('admin.php?itemid='.$block->id,$name,'link_Blue',true);
                    if ($block->getLinkId()>0){
                        $delet = Html::refjs("adm_unlink({$block->getLinkId()})",Icons::Unlnk);
                        $trID = $block->getLinkId();
                    }else{
                        $delet = Html::refjs("adm_remove({$block->id})", Icons::Trash);
                        $trID = $block->id;
                    }
                    $content .= "<tr id=\"il$trID\"><td>$i</td><td>$link</td><td>$delet</td></tr>";
                }
                $content .= '</table>';
                $link = 'admin.php?extra='.BlockGroup::TypeKey.'&amp;mode=1&amp;pageid='.$page->id;
                if(count($selblocks) > 1) echo '<br>'.Icons::getDiv(Icons::Order.' '.Html::ref($link, 'Порядок вывода', 'link_Blue'));
            }else{echo 'В группе нет объектов<br>';}
            $content .= '<br>Добавить:<br>';
            $link = 'admin.php?add='.TextBlock::TypeKey.'&amp;parent='.$page->id;
            $content .= Icons::Parnt.Html::ref($link, ' текстовый блок', 'link_Blue').'<br>';
            $link = 'admin.php?extra='.BlockGroup::TypeKey.'&amp;mode=2&amp;pageid='.$page->id;
            $content .= Icons::Link0.Html::ref($link, ' ссылку', 'link_Blue').'<br>';
        }
        return $content;
    }

}

