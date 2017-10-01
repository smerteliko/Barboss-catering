<?php
include 'Common.php';
include 'libs/Item.php';
include 'libs/ItemEx.php';
include 'libs/User.php';
include 'libs/DImage.php';
include 'libs/SimpleLink.php';
include 'libs/Session.php';
include 'page/HtmlPage.php';
include 'views/IView.php';
include 'views/IPageView.php';
include 'views/ListPage.php';
include 'functions.php';
include 'YMetrika.php';

spl_autoload_register("myautoloader");
registeralltypes();

$section = getsectionbyname('Index');
$itemid = $section->getDefaultPage();
if (!$itemid) err(101);
$item = Item::read($itemid, Item::rvAll, Item::rfAutoType);/*@var $item IPage*/

/* Загрузка View */
$viewclassname = getPageView($item->type);
if (!$viewclassname)  err(103);
$view = call_user_func(array($viewclassname,'Create'),$item);
$view->Init();

HtmlPage::$js[] = 'scripts/jquery-1.11.1.min.js';
HtmlPage::$js[] = 'scripts/slider.js';
HtmlPage::$css[] = 'css/style.css';
HtmlPage::$css[] = 'css/index.css';
HtmlPage::Start();
if($view instanceof IPageView){
    //$title = $view->getTitle();
    $title = 'Barboss Catering - выездной бар';
    $meta = $view->getDescription();
    $keywords = $view->getKeywords();
}
else $title = $meta = $keywords = false;
HtmlPage::Head($title, $keywords, $meta);
$content = str_replace('##content##', $view->getContent(), $section->value(20));
if(strpos($content,'##menu##')) $content = getMenu($content);
if(strpos($content,'##slider##')) $content = str_replace('##slider##', '<div id="slider">'.Slider::go().'</div>', $content);
echo YMetrika().'<div id="wrapper">'.$content.'</div>';
echo "<script type=\"text/javascript\">(window.Image ? (new Image()) : document.createElement('img')).src = 'https://vk.com/rtrg?p=VK-RTRG-127025-gNrEQ';</script>";
HtmlPage::Stop();