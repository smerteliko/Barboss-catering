<?php

class ETextBlock extends BasePageView {
    private $isinit = false;

    public static function eName(){return'Список текстовых блоков';}

    public function getContent() {
        $page = $this->item;
        $content = DForm::form($_SERVER['REQUEST_URI']).Editor::preedit2($page).'<h2>Текстовый блок</h2>';
        $parentid = Get::int('parent');
        if ($parentid){
            $parent = Item::read($parentid,array(10=>VT::String),Item::rfAutoType);
            $pname = $parent->getName();
            $ptype = $parent->getTypeName();
            $content.= '<br><div class="icons">'.Icons::Parnt." Добавление текстового блока для \"<b>$pname</b>\" (тип: <i>$ptype</i>)</div>"
                        .DForm::Hidden('parent', $parentid);
        }
        $content .= '<br><b>Заголовок</b><br>'.DForm::Textarea('title',$page->getName(),'style="width:800px; height="16px;"').'<br>';
        if ($page->id>0) $content .= Html::ref('ImageGaleryEdit.php'.'?itemid='.$page->id,'Галерея','link_Blue');
        else $content .= '<em>Галерея будет достуна только после сохранения</em>';
        $content .= '<br>'.DForm::Submit('save-self', 'Сохранить').'  '.DForm::Submit('save-close', 'Сохранить и закрыть')
                .'<br><b>Текст</b><br>'.DForm::Textarea('shorttext', $page->getShortText(), 'class="tinymce"; style="height: 900px; width:800px;"')
                .'<br><b>Дополнительные стили (CSS)</b><br>'.DForm::Textarea('css', $page->getCSS(), 'style="height: 500px; width:800px;"')
                .'<br>'.DForm::Submit('save-self', 'Сохранить').'  '.DForm::Submit('save-close', 'Сохранить и закрыть')
                .'</form>';
        return $content;
    }
    
    public function Init() {
        if (!$this->isinit) {
            HtmlPage::$js[] = 'scripts/jquery-1.11.1.min.js';
            HtmlPage::$extra[] = TinyMce::getInitScript();
            $this->isinit = true;
        }
    }
}

