<?php
include 'Common.php';
include_once 'page/HtmlPage.php';
include_once 'page/ImageGalery.php';

$apply = false;
if(Post::set('newimgapply')) $apply = true;
if(Post::set('imgapply')) $apply = true;
if(Post::set('orderRecount')) $apply = true;
if($apply) ImageGaleryEditor::apply();

HtmlPage::$css[] = 'css/admin.css';
HtmlPage::Start();
HtmlPage::Head('Галерея', false, false, false);
echo '<body><div class="admmain">';
ImageGaleryEditor::get();
echo '</div></body></html>';