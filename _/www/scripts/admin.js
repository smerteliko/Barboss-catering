
function adm_remove(itemid){
    var r = confirm('В корзину?');
    if(r) $.post('ajaxapplier.php',{itemid:itemid,remove:1},onrecive);
}

function adm_delete(itemid){
    var r = confirm('Окончательно удалить?');
    if(r) $.post('ajaxapplier.php',{itemid:itemid,delete:1},onrecive);
}

function adm_unlink(itemid){
    var r = confirm('Удалить ссылку?');
    if(r) $.post('ajaxapplier.php',{itemid:itemid,unlink:1},onrecive);
}

function adm_recover(itemid){
    var r = confirm('Восстановить?');
    if(r) $.post('ajaxapplier.php',{itemid:itemid,recover:1},onrecive);
}

function onrecive(data){
    var t = $.parseJSON(data);
    alert(t[0]);
    if(t[1]) $(t[1]).remove();
}