<?php
class DefaultSlider implements IListView {
    public static function getList($type){
        echo '<div class="cntnt">';
        echo '<h2>'.call_user_func(array(getEditorView($type),'eName')).'</h2>';//getEditorView() - ListEditor.php
        echo '<p>Оптимальный размер изображения: <b>800х380</b></p>';
        echo DForm::MultiPartForm(Server::$self.'?list='.Slider::defType());
        echo '<table>';
        echo '<tr><td>Выбрать изображение:</td>';
        echo '<td>'.DForm::File('newbannerimg',false).'</td></tr>';
        echo '<tr><td>Ссылка:</td><td>'.DForm::Text('newbannerlink', '').'</td></tr>';
        echo '</table>';
        echo DForm::Submit('newbannerapply', 'Добавить');
        echo '<br><br><br>';
        $imgs = Slider::getlist(ItemRequest::c_list(array(10=>VT::String,20=>VT::String),array(1=>0)));
        foreach($imgs as $img) { /*@var $img DFile*/
            echo DForm::Checkbox("bannerdel[{$img->id}]", false).' удалить<br>';
            echo '<table>';
            echo '<tr><td>Ссылка:</td><td>'.DForm::Text("bannerlink[{$img->id}]", $img->getLink()).'</td></tr>';
            echo '<tr><td>Порядок:</td><td>'.DForm::Text("bannerorder[{$img->id}]", $img->getOrder()).'</td></tr>';
            echo '</table>';
            echo '<img src="'.$img->getFileName().'" alt="image"><br>';
        }
        if(count($imgs)>0) echo DForm::Submit('bannerapply', 'Применить');
        echo '</form></div>';
        echo '</div>';
        return true;
    }
}