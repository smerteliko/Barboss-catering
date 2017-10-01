<?php
include_once 'libs/Item.php';
class Trash extends Item {
    const TypeKey = 0x020;
    const AdmName = 'Корзина';
    const AdmInfo = 'Список удалённых объектов для востановления или полного стирания';
    public static function defType()        { return self::TypeKey;}
    public static function admName()        { return self::AdmName;}
    public static function admInfo()        { return self::AdmInfo;}
    public static function getEditorClass() { return 'TrashEditor'; }
}
class TrashEditor{
    public static function getlist(){
        echo '<div class="cntnt">';
        echo '<h2>'.Trash::admName().'</h2>';
        $req = ItemRequest::c_list(array(10=>VT::String));
        $req->isshowdeleted = true;
        $items = Item::getlist($req);
        foreach($items as $item) echo $item->value(10)." <a href=\"javascript:adm_recover({$item->id})\">Восстановить</a><br>";
        echo '</div>';
        return true;
    }
}
Item::registerType(0x20, 'Trash');