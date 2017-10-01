<?php

class ESection extends BasePageView {
    public static function eName(){return'Список секций';}
    
    public function getContent() {
        $section = $this->item;
        $content = DForm::Form($_SERVER['REQUEST_URI'])
            .Editor::preedit2($section)
            .'Название секции<br />'.DForm::Text('name',$section->getName())
            .'<br />ID страницы по умолчанию<br />'.DForm::Text('defsection',$section->getDefaultPage())
            .'<br />'.DForm::Submit('save-self', 'Сохранить').'  '.DForm::Submit('save-close', 'Сохранить и закрыть').'<br>'
            .'Содержимое (наполнение соответствует ##content##)<br />'
            .DForm::Textarea('content', $section->value(20), 'style="width:100%;height:600px"')
            .'</form>';
        return $content;
    }

}

