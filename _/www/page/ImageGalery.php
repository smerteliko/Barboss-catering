<?php
include_once 'libs/UImageFile.php';
include_once 'libs/DImage.php';
include_once 'libs/ImageResizer.php';
include_once 'libs/DForm.php';
include_once 'libs/Post.php';
include_once 'libs/FileUtils.php';

class ImageGaleryEditor{
    static $messages = '';
    
    public static function get(){
        if (Get::set('extend')) return self::getextend();
        $itemid = Get::int('itemid');
        if(!$itemid) $itemid = Post::int('owner');
        $req = ItemRequest::c_list(array(10=>VT::String,15=>VT::String,16=>VT::String,20=>VT::Int,21=>VT::Int,22=>VT::Int));
        if($itemid){
            $req->parent = $itemid;
            $req->order = array(1=>0);
        }
        $images = DImage::getlist($req);

        if ($itemid) echo Html::ref('admin.php?itemid='.$itemid, 'Назад','link_Blue');
        else echo Html::ref('admin.php', 'Меню', 'link_Blue');
        echo DForm::Form(Server::$self);
        if($itemid) echo DForm::Hidden('owner', $itemid);
        if (self::$messages) echo '<fieldset><legend>Результаты загрузки</legend>'.self::$messages.'</fieldset>';
        echo '<fieldset><legend>Загруженные</legend>';
        foreach($images as $image){/*@var $image DImage*/
            $id = $image->id;
            $filename = FileUtils::getnameonly($image->getFileName());
            $min = $image->getminiature(150, 150, DImage::gmmClosest);
            $link = Url::make($min->getFileName());
            $alt = $image->value(15);
            $comment = $image->value(16);
            echo '<div class="imageedit">'."<img src=\"$link\" alt=\"$alt\"><br>"
                    . "Файл:".Html::ref(Url::make($image->getFileName()),$filename)."<br>Alt<br>".DForm::Text_s("imgalt[$id]", $alt,'width:320px')
                    .'<br>Комментарий<br>'.DForm::Text_s("imgcom[$id]",$comment,'width:320px')
                    .'<br>Порядок вывода '.DForm::Text_s("imgso[$id]", $image->v1, 'width:50px;').'<br>'
                    .DForm::Checkbox("imgedit[$id]", false).' Сохранить изменения<br>'.DForm::Checkbox("imgmark[$id]", false).' На удаление<br>'
                    .Html::ref('ImageGaleryEdit.php?extend&amp;itemid='.$image->id, 'Расширенное редактирование').'</div>';
        }
        echo '<br>С отмеченными<br>';
        echo '<input type="radio" name="imgaction" value="remove"> Удалить '.DForm::Checkbox('imgremove', false).' подтвердить <b>удаление</b><br>';
        echo DForm::Submit('imgapply', 'Применить');
        echo '</fieldset>';
        echo '</form>';
        echo DForm::MultiPartForm(Server::$self);
        echo '<fieldset>';
        echo '<legend>Загрузка</legend>';
        if ($itemid) echo DForm::Hidden('owner', $itemid);
        echo DForm::File('newimgfile', true, false);
        echo DForm::Checkbox('newimgcut', false).' обрезать до '.DForm::Text_s('newimgcutw', '', 'width: 60px').'X'.DForm::Text_s('newimgcuth', '', 'width: 60px').'<br>';
        echo DForm::Submit('newimgapply', 'Добавить');
        echo '</fieldset>';
        echo '</form>';
        if($itemid){
            echo DForm::Form(Server::$self).'<fieldset><legend>Порядок вывода фотографий</legend>';
            echo DForm::Hidden('owner', $itemid);
            echo DForm::Submit('orderRecount', 'Пересчитать порядок').'</fieldset></form>';
        }
    }

    public static function getextend() {
        $itemid = Get::int('itemid');
        if (!$itemid) return;
        $image = DImage::read($itemid,DImage::getAllValues());  /*@var $image DImage*/
        if (!$image) return;
        DForm::MultiPartForm('ImageGaleryEdit.php?itemid='.$image->id);
        echo DForm::Hidden('imgexid', $image->id);
        echo Html::ref('ImageGaleryEdit.php?itemid='.$image->v2,'Назад','link_Blue').'<br>';
        echo "<img src=\"{$image->getName()}\"><br>";
        echo 'Заменить'.DForm::File('imgexfile');
        echo '<br>'.DForm::Checkbox('imgexresize', false).'Изменить размер<br>';
        echo DForm::Text_s('imgexw', $image->getImgWidth(), 'width:50px').' X ';
        echo DForm::Text_s('imgexw', $image->getImgHeight(), 'width:50px');
        $methods = array(ImageResizer::ftFit=>'Вписать',  ImageResizer::ftCrop=>'Обрезать', ImageResizer::ftDistortion=>'Исказить пропорции');
        echo DForm::ComboBox_s('imgexfittype', $methods, 1, 'width:100px');
        echo '<br>Alt<br>'.DForm::Text_s("imgexalt", $image->getAlt(),'width:320px')
                .'<br>Комментарий<br>'.DForm::Text_s("imgexcom",$image->getComment(),'width:320px')
                .'<br>Порядок вывода '.DForm::Text_s("imgexso", $image->v1, 'width:50px;').'<br>';
        $mins = DImageMin::getlist(ItemRequest::c_children($image->id, array(10=>11)));
        if (count($mins)>0) {
            echo '<br>Миниатюры<br>'.DForm::Checkbox('imgexminrm', false).' удалить все<br>';
            foreach($mins as $minimg) {
                echo "<img src=\"{$minimg->getName()}\"><br>";
                echo $minimg->getImgWidth().' X '.$minimg->getImgHeight().'<br>';
            }
        }
        echo DForm::Submit('imgapply', 'Сохранить');
        echo '</form>';
    }
    
    public static function apply(){
        if(Post::set('orderRecount'))  ImageGaleryEditor::orderRecount();
        if(Post::set('newimgapply')) ImageGaleryEditor::$messages = ImageGaleryEditor::newimgapply();
        if (Post::set('imgapply')){
                $action = Post::any('imgaction');
                $changes = Post::indexedarrayset('imgedit');
                $alts = Post::indexedarrayhtml('imgalt');
                $coms = Post::indexedarrayany('imgcom');
                $orders = Post::indexedarrayint('imgso');
                switch($action) {
                    case 'remove':
                        if (post::set('imgremove')) {
                            $ids = Post::indexedarrayset('imgmark');
                            if ($ids) foreach($ids as $id) {
                                $img = DImage::read($id,array(10=>VT::String));
                                if ($img) $img->delete();
                                if (isset($changes[$id])) unset($changes[$id]);
                            }
                        }
                        break;
                }
                if ($changes) foreach($changes as $id){
                    $img = DImage::read($id,Item::rvAll);   /* @var $img DImage */
                    if (isset($alts[$id])&&$alts[$id]) $img->setAlt($alts[$id]);
                    if (isset($coms[$id])&&$coms[$id]) $img->setComment($coms[$id]);
                    if (isset($orders[$id])) $img->v1 = $orders[$id];
                    $img->write();
                }
        }
    }
    public static function applyex(){
        $itemid = Post::int('imgexid');
        if (!$itemid) return;
        $image = DImage::read($itemid); /*@var $image DImage*/
        if (!$image) return;
        
        
        $newfile = UImageFile::upload('imgexfile');
        if (Post::set('imgexresize')) {
            $w = Post::int('imgexw');
            $h = Post::int('imgexh');
            $ft = Post::int('imgexfittype');
            ImageResizer::resize($newfile, $w, $h, $ft);
        }
        if (Post::set('imgexcom')) $image->setComment (Post::any('imgexcom'));
        if (Post::set('imgexalt')) $image->setComment (Post::html('imgexalt'));
        if (Post::set('imgexso'))  $image->setComment (Post::int('imgexso'));
    }

    private static function newimgapply(){
        $files = UImageFile::upload('newimgfile', true);
        $msg = '';
        $order = $owner = false;
        if(Post::set('owner')){
            $owner = Post::int('owner');
            $req00 = ItemRequest::c_list(array());
            $req00->where = array(2=>$owner);
            $order = count(DImage::getlist($req00));
        }
        foreach($files as $file/*@var $file UImageFile*/){
            $msg.= $file->srcfilename.' - ';
            if($file->error) $msg.= $file->error->errortext;
            else{
                $msg.= 'успешно';
                $order++;
                $msg.= ImageGaleryEditor::newimg($file,$owner,$order);
            }
            $msg.= '<br>';
        }
        return $msg;
    }
    private static function newimg($file,$owner,$order){
        $size = ImageGaleryEditor::imgPropertions($file->height,$file->width);
        $file->apply(FileSets::userDir);
        $img = DImage::fromfile($file);
        if($owner){
            $img->setOwner($owner);
            $img->v1 = $order;
        }
        $img->setComment($file->srcfilename);
        $img->write();
        $img->getminiature($size['wm'],$size['hm'],DImage::gmmCreateFit);
        ImageResizer::resize($file,$size['w'],$size['h'],false);
        $img->setWH($size['w'],$size['h']);
        $img->write();
        return ' - '.$size['w'].'x'.$size['h'];
    }
    private static function imgPropertions($h,$w){
        if($w > 1280 || $h > 720){
            $h = (int) (1280*$h/$w);
            $w = 1280;
            if($h > 720){
                $w = (int) (720*$w/$h);
                $h = 720;
            }
        }
        $hm = (int) (500*$h/$w);
        return array('w'=>$w,'h'=>$h,'wm'=>500,'hm'=>$hm);
    }
    private static function orderRecount(){
        $itemid = Get::int('itemid');
        if(!$itemid) $itemid = Post::int('owner');
        $req = ItemRequest::c_list(array());
        $req->parent = $itemid;
        $req->order = array(1=>0);
        $images = DImage::getlist($req);
        $i = 0;
        foreach($images as $image){/*@var $image DImage*/
            $i++;
            if($image->getSortOrder() == $i) continue;
            $image->setSortOrder($i);
            $image->write();
        }
    }
}