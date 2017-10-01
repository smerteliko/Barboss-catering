<?php
class Icons{
    const Parnt = '<img src="./img/MenusButtnParnt.png" height="16">';
    const Link0 = '<img src="./img/MenusButtnLink0.png" height="16">';
    const Delet = '<img src="./img/Delete.png" title="Удалить" height="16">';
    const Trash = '<img src="./img/Trash.png" title="В корзину" height="16">';
    const Unlnk = '<img src="./img/Unlink.png" title="Открепить" height="16">';
    const Watch = '<img src="./img/Watch.png" title="Просмотр" height="16">';
    const Order = '<img src="./img/Order.png" title="Порядок" height="16">';
    const Restore = '<img src="./img/Restore.png" title="Восстановить" height="16">';
    const Uread = '<img src="./img/Uread.png" title="Не прочитано" height="8">';
    const Answr = '<img src="./img/Answr.png" title="Отвечено" height="16">';
    public static function getDiv($string){
        return "<div class=\"icons\">$string</div>";
    }
    public static function getInfo($string){
        $img = '<img class="img-info" src="./img/Info.png" alt="info" title="'.$string.'" height="16">';
        return Icons::getDiv($img);
    }
}