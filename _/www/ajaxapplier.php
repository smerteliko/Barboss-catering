<?php
include 'Common.php';
include 'libs/Item.php';
include 'libs/Post.php';
include 'libs/SimpleLink.php';

$type = Post::int('itemtype');
$itemid = Post::int('itemid');
if(!$itemid || (!$type && $itemid==-1)) error(0,"apply without type or itemid");
/*---------- Первичный контроль доступа --------------*/
/*----------------------------------------------------*/

registeralltypes();
spl_autoload_register("myautoloader");

if($itemid==-1){
    $item = Item::create(Item::cfOnlyExistedTypes,$type);
    if(!$item) error(Item::eTypeUnset,"type $type not existed");
}else{
    $item = Item::read($itemid, Item::rvAll, Item::rfAutoType);
    if(!$item) error(Item::eTypeUnset,"item $itemid not existed");
}
$out[1] = '#il'.$item->id;

if(Post::set('unlink')){//Удаление ссылки
    if(!$item->isvalid()) error(Item::eTypeUnset, "try to delete unsaved item (link)");
    $item->delete();
    $out[0] = 'Ссылка удалена';
    goto TheEnd;
}
if(Post::set('remove')) $action = 'remove';
if(Post::set('delete')) $action = 'delete';
if(Post::set('recover')) $action = 'recover';
if(!$item->isvalid()) error(Item::eTypeUnset, "try to $action unsaved item");

$req01 = ItemRequest::c_type(SimpleLink::TypeKey,false,false,array(2=>$item->id));
$req02 = ItemRequest::c_type(SimpleLink::TypeKey,false,false,array(3=>$item->id));
if(Post::set('delete') || Post::set('recover')) $req01->showdeletedmode = $req02->showdeletedmode = 1;
$links = array_merge(array($item),Item::getlist($req01,false),Item::getlist($req02,false));

if(Post::set('remove')){//в корзину
    $chnum = $item->countchilderen();
    if($chnum > 1){
        $out[0] = 'Удаление невозможно! Есть подчинённые элементы: '.$chnum;
        unset($out[1]);
    }else{
        foreach($links as $v){
            $v->setflags(Item::fMarkDelete);
            $v->write();
        }
        $out[0] = 'Удалено';
    }
}elseif(Post::set('delete')){//окончательное стирание
    foreach($links as $v) $v->delete();
    $out[0] = 'Объект окончательно удалён';
}elseif(Post::set('recover')){//Восстановление
    $item->unsetflags(Item::fMarkDelete);
    $item->write();
    $out[0] = 'Восстановлено';
    unset($links[0]);
    if($links) foreach($links as $v){
        $vID = $v->v2;
        if($vID == $item->id) $vID = $v->v3;
        $itemLink = Item::read($vID, false, Item::rfAutoType);
        if(!$itemLink){
            $v->delete();
            continue;
        }
        if($itemLink->checkflags(Item::fMarkDelete)) continue;
        $v->unsetflags(Item::fMarkDelete);
        $v->write();
    }
}
TheEnd:
echo json_encode($out);

function error($code,$text){
    DError::raise($code, $text);
    exit;
}
/*------------------------- ToDo ------------------------------*/
// Добавить обработку ошибок: возврат на предыдущую страницу с информированием об ошибке
// Добавить возвращение ошибки при ajax - режиме