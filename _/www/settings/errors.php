<?php
ini_set('display_errors',1);
ini_set('log_errors',1);
set_error_handler("errorsLog");

function errorsLog($errno, $errmsg, $file, $line)
{
    $date = date('r');
    $errorstring = "$date : $errmsg ($errno) [$file; line: $line]\n\n";
    /*$fp=fopen('errors.log', 'a');
    fwrite($fp, $errorstring);
    fclose($fp);*/
    echo $errorstring;
}
