<?php
include_once('page/Slider.php');

class SliderEdit {
    public static function get() {
        echo DForm::MultiPartForm(Server::$self);
        echo 'Оптимальный размер изображения: 1000х400<br><br>';
        echo 'Выбрать изображение:';
        echo DForm::File('newbannerimg',false).'<br><br>';
        echo 'Ссылка: '.DForm::Text('newbannerlink', '').'<br><br>';
        //echo DForm::Checkbox('imgresize', false).'Изменить размер до 1000х400<br>';
        echo DForm::Submit('newbannerapply', 'Добавить');
        echo '<br><br><br>';
        //$imgs = DFile::getchildren(BannerKey);
        $imgs = Slider::getlist(ItemRequest::c_list(array(10=>VT::String,20=>VT::String),array(1=>0)));
        foreach($imgs as $img) { /*@var $img DFile*/
            echo DForm::Checkbox("bannerdel[{$img->id}]", false).' удалить<br>';
            echo DForm::Text("bannerlink[{$img->id}]", $img->getLink()).'<br>';
            echo DForm::Text("bannerorder[{$img->id}]", $img->getOrder()).'<br>';
            //echo '<a href="admin.php?banner&amp;delfile='.$img->id.'">Удалить</a><br>';
            echo '<img src="'.$img->getFileName().'" alt="image"><br>';
        }
        echo DForm::Submit('bannerapply', 'Применить');
        echo '</form>';
    }
    
    public static function apply() {
        if (Post::set('bannerapply')) {
            $dels = Post::indexedarrayset('bannerdel');
            $links = Post::indexedarrayhtml('bannerlink');
            $orders = Post::indexedarrayint('bannerorder');
            foreach($dels as $id) {
                $item = Slider::read($id,false);
                $item->delete();
                unset($links[$id]);
            }
            foreach($links as $id=>$link) {
                $item = Slider::read($id,false);
                $item->setLink($link);
                if (isset($orders[$id])) $item->setOrder($orders[$id]);
                $item->write();
            }
        }
        if (Post::set('newbannerapply')) {
            $req = ItemRequest::c_type(Slider::Key,false);
            $last = $req->getAgregation(array(1=>'max'));
            $item = Slider::create();
            $item->setLink(Post::html('newbannerlink'));
            $item->setOrder($last+1);
            $file = UImageFile::upload('newbannerimg');
            if ($file) {
                ImageResizer::resize($file, Slider::width, Slider::height, ImageResizer::ftFit);
                $file->apply(FileSets::userDir);
                //var_dump($file);die();
                $item->setName($file->getFileName());
            }
            else echo DError::$lasterror;
            $item->write();
        }
    }
}

