<?php

class EPageConstructor implements IPageView {
    private $item;
    public static function eName(){return'Список страниц';}
    public function Init(){}
    public function getContent(){
        $item = $this->item;
        $content = DForm::Form($_SERVER['REQUEST_URI']).Editor::preedit2($item);
        if(isset($_GET['mode'])){
            if($_GET['mode'] == 'addlink') return $content.EPageConstructor::addexisted($item);
            if($_GET['mode'] == 'sorting') return $content.EPageConstructor::sorting($item);
        }
        $content.= '<h2>Компоновка страницы</h2>';
        $content.= 'Заголовок<br>'.DForm::Text_s('title', $item->getName(), 'width:200px').'<br><br>';
        $content.= self::getSections($item->getDefaultSection());
        $content.= 'Ключевые слова<br>'.DForm::Text_s('keywords', $item->getMetaKeywords(), 'width:200px').'<br>';
        $content.= 'Мета-описание<br>'.DForm::Text_s('description', $item->getMetaDescription(), 'width:200px').'<br><br>';
        $content.= '<br>'.DForm::Submit('save-self', 'Сохранить').'  '.DForm::Submit('save-close', 'Сохранить и закрыть').'</form>';
        $content.= self::showFields($item->id);
        return $content;
    }
    public function getTitle(){}
    public function getKeywords(){}
    public function getDescription(){}
    public function getDefaultSection(){}
    public function getTimeStamp(){}
    public static function Create($item){
        if(!($item instanceof PageConstructor)) return false;
        $x = new self;
        $x->item = $item;
        return $x;
    }
    private static function getSections($def){
        $items = Item::getlist(ItemRequest::c_type(Section::defType(),array(10=>VT::String)));
        if(count($items) == 0) return '';
        foreach($items as $v) $sections[$v->id] = $v->getName(); 
        return 'Секция<br>'.DForm::ComboBox('DefaultSection',$sections,$def).'<br><br>';
    }
    private static function showFields($id){
        if(Get::set('add')) return;
        $reqsel = ItemRequest::c_children($id, array(10=>VT::String));
        $reqsel->order = array(1=>0);
        $selblocks = Item::getlist($reqsel,false,Item::rfAutoType);
        /*Здесь проверка ссылок на fMarkDelete*/
        foreach($selblocks as $k=>$v) if($v->checkflags(Item::fMarkDelete)) unset($selblocks[$k]);
        SimpleLink::resolveLinks($selblocks, array(10=>VT::String));
        if(count($selblocks) == 0) $content ='<br><i>Пустая страница</i><br>';
        else $content = '<br><div class="icons">'.Icons::Watch.' '.Html::ref('/','просмотр','link_Blue',true).'</div>';
        $link = 'admin.php?itemid='.$id.'&amp;mode=sorting';
        if(count($selblocks) > 1)
            $content .= '<div class="icons">'.Icons::Order.' '.Html::ref($link,'порядок вывода','link_Blue').'</div>';
            $link = 'admin.php?itemid='.$id.'&mode=addlink';
        $content .= '<div class="icons">'.Icons::Link0.' '.Html::ref($link, 'Добавить элемент на страницу', 'link_Blue').'</div>';
        $content .= '<table class="pages"><caption>Существующие объекты</caption><tr><th></th><th>Название</th><th>Тип</th><th></th></tr>';
        $i = 0;
        foreach($selblocks as $v){/*@var $v Item*/
            $i++;
            $name0 = Editor::getCutName($v->getName());
            $link0 = Html::ref('admin.php?itemid='.$v->id.'&ref='.$id,$name0,'link_Blue');
            if($v->getLinkId()>0){
                $delet = Html::refjs("adm_unlink({$v->getLinkId()})",Icons::Unlnk);
                $trID = $v->getLinkId();
            }else{
                $delet = Html::refjs("adm_remove({$v->id})",Icons::Trash);
                $trID = $v->id;
            }
            $type0 = $v->getTypeName();
            $content .= "<tr id=\"il$trID\"><td>$i</td><td>$link0</td><td>$type0</td><td>$delet</td></tr>";
        }
        $content .= '</table>';
        return $content;
    }

    public static function sorting($page) {
        $req = ItemRequest::c_children($page->id, array(10=>VT::String));
        $req->order = array(1=>0);
        $items = Item::getlist($req,false,Item::rfAutoType);
        $objs = SimpleLink::resolvetoarray($items, array(10=>VT::String));
        
        $pLink = 'admin.php?itemid='.$page->id;
        $content = DForm::Form($_SERVER['REQUEST_URI']).Editor::preedit2($page)
                    .Icons::getDiv(Icons::Order.' сортировка объектов страницы <b>'.Html::ref($pLink,$page->getName(),'link_Blue').'</b>')
                    .'<table><tr><th>Порядок</th><th>Название</th><th>Тип</th></tr>';
        $i = 0;
        foreach($items as $block){
            $id = $block->id;
            if ($block->checkflags(Item::fLink)) $block = $objs[$block->v3];
            $name = $block->getName();
            $type = $block->getTypeName();
            $name = Editor::getCutName($name);
            $content .= '<tr><td>'.DForm::Text_s("blockorder[$id]",++$i,'width:40px;').'</td><td>'.$name.'</td><td>'.$type.'</td></tr>';
        }
        $content .= '</table>'
                 .DForm::Submit('sortapply', 'Применить')
                 .'</form>';
        return $content;
    }

    public static function addexisted($page) {
        $items = Item::getlist(ItemRequest::c_type(TextBlock::TypeKey,array(10=>VT::String)),false,Item::rfAutoType);
        $select = Item::getlist(ItemRequest::c_children($page->id, false),false,Item::rfAsKeyArray);
        SimpleLink::resolveLinks($select, false,true);
        foreach($items as $k=>$v) if(isset($select[$v->id])) unset($items[$k]);

        $pLink = 'admin.php?itemid='.$page->id;
        $c= Icons::Link0.' Добавить ссылку в страницу: <b>'.Html::ref($pLink,$page->getName(),'link_Blue').'</b><br><br>';
        
        if(count($items) == 0) return $c.= '<i>Нет объектов для добавления.</i>';

        $c.= '<table><tr><th></th><th>Название</th><th>Тип</th></tr>';
        foreach($items as $v)/*@var $v Item*/{
            $name = Editor::getCutName($v->getTitle());
            $type = $v->getTypeName();
            $chbx = DForm::Checkbox("block[$v->id]", false);
            $c.= "<tr><td>$chbx</td><td><i>$name</i></td><td>$type</td></tr>";
        }
        $c.= '</table>';
        $c.= DForm::Submit('linksapply', 'Применить');
        $c.= '</form>';
        return $c;
    }
}