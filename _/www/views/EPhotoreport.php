<?php
class EPhotoreport extends BasePageView{
    private $isinit = false;
    public static function eName(){return'Список фотоотчётов';}

    public function getContent(){
        $item = $this->item;
        $cnt = DForm::form($_SERVER['REQUEST_URI']).Editor::preedit2($item).'<h2>Фотоотчёт</h2>';
        $cnt.= '<h3>Заголовок</h3>'.DForm::Text('Title',$item->getTitle(),'style="width:800px;"').'<br>';
        $cnt.= '<h3>Опубликовано: '.DForm::Checkbox('fEnabled',$item->checkflags(Item::fEnabled)).'</h3>';
        $cnt.= '<h3>Дата</h3>'.EPhotoreport::Date($item->getDate());
        if($item->id>0) $cnt.= Html::ref('ImageGaleryEdit.php'.'?itemid='.$item->id,'<h3>Галерея</h3>','link_Blue');
        else $cnt .= '<h3>Галерея</h3><em>Галерея будет достуна только после сохранения</em><br>';
        $cnt.= DForm::Submit('save-self', 'Сохранить').'  '.DForm::Submit('save-close', 'Сохранить и закрыть')
                .'<h3>Текст</h3>'.DForm::Textarea('Text', $item->getText(), 'class="tinymce"')
                .'<br>'.DForm::Submit('save-self', 'Сохранить').'  '.DForm::Submit('save-close', 'Сохранить и закрыть')
                .'</form>';
        return $cnt;
    }
    
    public function Init(){
        if (!$this->isinit){
            HtmlPage::$js[] = 'scripts/jquery-1.11.1.min.js';
            HtmlPage::$extra[] = TinyMce::getInitScript();
            $this->isinit = true;
        }
    }
    private static function Date($t,$c=''){
        if($t != 0) $dN = (int)Date('j',$t);
        else $dN = (int)Date('j');
        for($i = 1; $i < 32; $i++) $d[$i] = $i;
        $c.= DForm::ComboBox('day',$d,$dN);
        $m = Array(1=>'Январь',2=>'Февраль',3=>'Март',4=>'Апрель',5=>'Май',6=>'Июнь',7=>'Июль',8=>'Август',9=>'Сентябрь',10=>'Октябрь',11=>'Ноябрь',12=>'Декабрь');
        if($t != 0) $mN = (int)Date('n',$t);
        else $mN = (int)Date('n');
        $c.= DForm::ComboBox('month',$m,$mN);
        $yN = (int)Date('Y');
        for($y1 = 2016; $y1 <= $yN; $y1++) $y[$y1] = $y1;
        if($mN > 6) $y[$y1] = $y1;
        if($t != 0) $yN = (int)Date('Y',$t);
        $c.= DForm::ComboBox('year',$y,$yN);
        return $c;
    }
}