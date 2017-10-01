<?php
include 'Common.php';
include_once 'libs/Item.php';
include 'libs/Post.php';
include 'libs/DForm.php';
include 'libs/Editor.php';
include 'libs/SimpleLink.php';
include 'libs/Session.php';
include 'page/HtmlPage.php';
include 'page/Icons.php';
include 'libs/UserUtils.php';
include 'views/ListEditorList.php';
include 'views/ListEditor.php';

function Apply() {
    $itemid = Post::int('itemid');
    $type = Post::int('itemtype');
    if ($itemid==-1) {
        $item = Item::create(Item::cfOnlyExistedTypes,$type);
        if (!$item) {DError::raise(Item::eTypeUnset, "type $type not existed");exit;}
    }
    else {
        $item = Item::read($itemid,Item::rvAll, Item::rfAutoType);
        if (!$item) {DError::raise(Item::eTypeUnset, "item $itemid not existed");exit;}
    }
    if ($item instanceof IModel) $item->Apply();
    if(Post::set('save-close')){
        if(Get::set('ref')) Header::redirect('admin.php?itemid='.Get::int('ref'));
        Header::redirect('admin.php?list='.$item->defType());
    }
    if(Post::set('save-self') && Get::set('add')){
        $id = ItemRequest::c_type($type,false)->getAgregation(array(0=>'max'));
        Header::redirect('admin.php?itemid='.$id);
    }
}

spl_autoload_register("myautoloader");
registeralltypes();

Session::load();

if (Post::set('loginapply')) {
    $userscnt = ItemRequest::c_type(0x10,false)->getAgregation(true);
    if ($userscnt==0) {
        $user = User::create(); /* @var $user User */
        $user->setEmail(Post::any('login'));
        $user->setPasswd(hash('sha256',Post::any('passwd')));
        $user->write();
    }
    else {
        UserLogin::$attempts = 3;
        UserLogin::$timeout = 60;
        $user = UserLogin::login(Post::any('login'), Post::any('passwd'));
        if (!$user->iserror()) Session::login ($user->id, 86400);
        else echo $user;
    }
}

if (Get::set('logout')) {
    Session::stop();
    Header::redirect('admin.php');
}

if(Post::set('itemid') && Post::set('itemtype')) Apply();
if(Post::set('bannerapply') || Post::set('newbannerapply')) Slider::ApplySlider();

if (!Session::$userid) {
    $userscnt = ItemRequest::c_type(0x10,false)->getAgregation(true);
    if ($userscnt==0) $t = 'Create user-admin<br>';
    else $t = '';
?>
<html>
    <head>
    </head>
    <body>
        <?=$t?>
        <div style="position:absolute;min-width:420px;top:33%;left:33%;">
        <form action="admin.php" method="post">
            <h3 style="text-align:center;margin-bottom:20px;">Вход в панель администрирования</h3>
            <table style="margin:auto;">
                <tr><td>Логин</td><td><input type="text" name="login"></td></tr>
                <tr><td>Пароль</td><td><input type="password" name="passwd"></td></tr>
                <tr><td colspan="2" style="text-align:center;"><input type="submit" value="Вход" name="loginapply"></td></tr>
            </table>
        </form>
        </div>
    </body>
</html>
<?php
exit;
}

function printmenu() {
    $listMenu = array(
        0x230=>'Текстовые блоки',
        0x23A=>'Фотоотчёты',
        0x200=>'Компоновка страниц',
        0x2A0=>'Секции',
        0x120=>'Слайдер');
    echo '<table>';
    foreach($listMenu as $k=>$v){
        $link = 'admin.php?list='.$k;
        echo '<tr><td>'.Html::ref($link,$v,'link_Blue').'</td></tr>';
    }
    echo '<tr><td>'.Html::ref('admin.php?showhidden', 'Корзина','link_Blue').'</td></tr>';
    echo '</table>';
    echo '<br>'.Html::ref('https://metrika.yandex.ru/dashboard?id=42207424','Статистика посещаемости','link_Blue',true).'<br>';
    echo '<br><b>Инструкции:</b><br>'.Html::ref('faq/faq.php','Работа с фотоотчётами','link_Blue',true).'<br>';
    echo '<br>'.Html::ref('admin.php?logout', 'Выйти', 'link_Blue');
}

if(Get::set('remove')){
    $item = Item::read(Get::int('remove'), false, Item::rfAutoType);
    SimpleLink::deleteparentlinks($item->id);
    $item->delete();
    Header::redirect(Server::$from);
}

HtmlPage::$css[] = 'css/admin.css';
HtmlPage::$js[] = 'scripts/jquery-1.11.1.min.js';
HtmlPage::$js[] = 'scripts/admin.js';

$item = false;
$error = false;
if (Get::set('add')) {
    $item = Item::create(Item::cfOnlyExistedTypes,Get::int('add'));
    if (!$item) $error = 'Такого типа не существует!';
}
elseif (Get::set('itemid')) {
    $itemid = Get::int('itemid');
    $item = Item::read($itemid,Item::rvAll,Item::rfAutoType);
    if (!$item) $error = 'Такого объекта не существует';
}
if (!$item) goto cont1;

$classname = getEditorView($item->type);
if ($classname) {
    $view = call_user_func(array($classname,'Create'),$item);
    $view->Init();
}else{
    $error = 'Редактор не доступен';
    goto cont1;
}

cont1:
echo HtmlPage::Start();
echo HtmlPage::Head('Админка', false, false, false);
echo '<body>';
echo '<div class="admmain">';
echo Html::ref('admin.php', 'В меню', 'link_Blue').'<br><br>';
    
if($error){
    echo $error;
    printmenu();
}elseif (Get::set('list')){
    $type = Get::int('list');
    $classname = getEditorListView($type);//ListEditorList.php
    if($classname) call_user_func(array($classname,'getList'),$type);
    else{
        echo "editorlist is not allowed in $classname";
        exit;
    }
}elseif(Get::set('add')){

    echo $view->getContent();
}elseif (Get::set('itemid')) {
    //echo Html::ref('admin.php', 'В меню', 'link_Blue').'<br><br>';
    echo $view->getContent();
}elseif (Get::set('extra')){
    //echo Html::ref('admin.php', 'В меню', 'link_Blue').'<br><br>';
    $classname = Item::getClassnameByType($type=Get::int('extra'));
    if(!$classname){
        echo "class of type $type doesnt exist";
        exit;
    }
    $editor = call_user_func(array($classname,'getEditorClass'));
    if(!$editor){
        echo "editor is not allowed in $classname";
        exit;
    }
    call_user_func(array($editor,'extra'));
}elseif (Get::set('showhidden')){
    include 'libs/DImage.php';
    $req = ItemRequest::c_list(array(10=>VT::String));
    $req->showdeletedmode = 1;
    $items = Item::getlist($req,false,Item::rfAutoType);
    foreach($items as $k=>$v) if($v->defType() == SimpleLink::TypeKey) unset($items[$k]);
    if(count($items) == 0) echo '<h2>Корзина пуста</h2>';
    else{
        echo '<h2>Корзина</h2>';
        echo '<table><tr><th>ID</th><th>Имя объекта</th><th>Тип</th></tr>';
        foreach($items as $item)
            echo '<tr id="il'.$item->id.'"><td>'.$item->id.'</td><td>'.$item->value(10).'</td><td>'.$item->getTypeName().'</td><td><a href="javascript:adm_recover('.$item->id.')">'.Icons::Restore.'</a></td><td><a href="javascript:adm_delete('.$item->id.')">Стереть</a></td></tr>';
        echo '</table>';
    }
}
else printmenu ();
echo '</div>';
echo HtmlPage::Stop();