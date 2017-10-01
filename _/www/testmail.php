<?php
include 'Common.php';
include 'libs/Email.php';


$res = EMail::send('Барбос', 'webmaster@barboss-catering.ru', 'Дмитрий', 'poletaev.d@itpsk.ru', '', '', 'тест тест', '<html><body><b>тест тест</b></body></html>');
echo $res;


