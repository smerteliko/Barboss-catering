<?php
include 'Common.php';
include 'libs/Item.php';
include 'libs/User.php';
include 'libs/DImage.php';
include 'libs/SimpleLink.php';
include 'libs/Post.php';
include 'page/HtmlPage.php';
include 'views/IView.php';
include 'views/IPageView.php';
include 'views/ListPage.php';
include 'functions.php';
include 'YMetrika.php';

spl_autoload_register("myautoloader");
registeralltypes();

$itemid = Get::int('id');
$sectionid = isset($_GET['section'])?$_GET['section']:false;
$section = false;
/*Если вообще ничего нет, то выведем главную*/
if(!$itemid && !$sectionid) $sectionid = 'Index';
/* Если есть секция */
if($sectionid){
    $section = getsectionbyname($sectionid);
    if(!$itemid) $itemid = $section->getDefaultPage();
}
/* Получаем страницу */
if(!$itemid) err(105);
$item = Item::read($itemid, Item::rvAll, Item::rfAutoType);/*@var $item IPage*/

if(Post::set('apply')) $item->Apply();

/*Если есть секция, но страница не прочиталась, попробуем прочитать страницу по умолчанию*/
if($section && !$item){
    $itemid = $section->getDefaultPage();
    $item = Item::read($itemid, Item::rvAll, Item::rfAutoType);
    if(!$item) err(101);
}
/*Если есть страница, но нет секции, читаем секцию по умолчанию*/
if(!$section && $item){
    if(method_exists($item, 'getDefaultSection')){
        $sectionid = $item->getDefaultSection();
        if($sectionid) $section = Section::read($sectionid, array(10=>VT::String,20=>VT::StringLong));
    }
    if(!$section) $section = getsectionbyname('Page');
    if(!$section) err(102);
}
if (!$item || !$section) err(104);

$viewclassname = getPageView($item->type);
if(!$viewclassname) err(103);
$redirect = true;
$viewArray = array('VPageConstructor','VPhotoreport','VBlogPhotoreport');
if(in_array($viewclassname,$viewArray)) $redirect = false;
if($redirect) Header::redirect('/');
$view = call_user_func(array($viewclassname,'Create'),$item);
$view->Init();

HtmlPage::$css[] = 'css/style.css';
HtmlPage::$css[] = 'css/page.css';

$menuLR = '';
if($viewclassname == 'VPhotoreport' || $viewclassname == 'VBlogPhotoreport'){
    $menuLR = 'right';
    $menuNm = 1;
}elseif($itemid == 13){
    $menuLR = 'right';
    $menuNm = 2;
}elseif($itemid == 89){
    $menuLR = 'left';
    $menuNm = 4;
}elseif($itemid == 17){
    $menuLR = 'left';
    $menuNm = 3;
}elseif(in_array($itemid,array(22,26,29,32,35,38,41,44,48,50,275,277,281))){
    $menuLR = 'left';
    $menuNm = 2;
}
if($menuLR != '') HtmlPage::$extra[] = '<style type="text/css">#menu-'.$menuLR.' .menu-item:nth-child('.$menuNm.') a{color:#ffb15f;}</style>';

$content = str_replace('##content##', $view->getContent(), $section->value(20));
if(strpos($content,'##menu##')) $content = getMenu($content);

if(strpos($content,'magnific')){
    HtmlPage::$css[] = 'css/magnificpopup.css';
    HtmlPage::$js[] = 'scripts/jquery-1.11.1.min.js';
    HtmlPage::$js[] = 'scripts/magnificpopup.js';
    HtmlPage::$js[] = 'scripts/magnificinit.js';
}
HtmlPage::Start();
$title = 'BarBoss Catering - '.$view->getTitle();
if($view instanceof BaseView){
    $title .= ' - '.$view->getTitle();
    $meta = $view->getDescription();
    $keywords = $view->getKeywords();
}
else $meta = $keywords = false;
HtmlPage::Head($title, $keywords, $meta);
echo YMetrika().'<div id="wrapper">'.$content.'</div>';
echo "<script type=\"text/javascript\">(window.Image ? (new Image()) : document.createElement('img')).src = 'https://vk.com/rtrg?p=VK-RTRG-127025-gNrEQ';</script>";
HtmlPage::Stop();