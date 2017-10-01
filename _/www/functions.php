<?php
function getsectionbyname($v){
    $arr = array(10=>VT::String, 20=>VT::StringLong);
    $req = ItemRequest::c_type(Section::TypeKey, $arr);
    $req->where = array(10=>$v);
    $items = Section::getlist($req);
    if($items) return $items[0];
    return false;
}
function getMenu($c){
    $v_a = array(10=>VT::String,20=>VT::StringLong);
    $req = ItemRequest::c_list($v_a,false,array(10=>'##menu##'));
    $items = TextBlock::getlist($req);
    if(!$items) return $c;
    $menuV = call_user_func(array('VTextBlock','Create'),$items[0]);
    $menuV->Init();
    return str_replace('##menu##', $menuV->getContent(), $c);
}
function err($c){
    $r = 'UNKNOWN ERROR';
    switch ($c){
        case 101: $r = 'NO PAGE'; break;
        case 102: $r = 'CAN NOT FIND SECTION'; break;
        case 103: $r = 'NO VIEW'; break;
        case 104: $r = 'NO PAGE OR VIEW'; break;
        case 105: $r = 'NO ITEM ID'; break;
    }
    echo '<h1>'.$r.'!</h1>';
    exit();
}