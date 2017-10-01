<?php

class HtmlPage {
    public static $css = array();
    public static $js = array();
    public static $extra = array();
    public static function Start() {
        echo '<!DOCTYPE HTML>'.chr(10);
        echo '<html>'.chr(10);
    }
    public static function Head($title,$keywords,$meta) {
        echo " <head>\n  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n  <meta charset=\"utf-8\">\n";
        echo '  <meta name="apple-mobile-web-app-capable" content="yes" >'.chr(10)
            .'  <meta name="viewport" content="width=device-width, user-scalable=yes">'.chr(10)
            .'  <meta name="HandheldFriendly" content="true">'.chr(10)
            .'  <meta name="MobileOptimized" content="550">'.chr(10);
        if ($title) echo "  <title>$title</title>\n";
        foreach(self::$css as $css) {
            echo "  <link rel=\"stylesheet\" href=\"{$css}\" type=\"text/css\">\n";
        }
        echo "  <link rel=\"icon\" type=\"image/png\" href=\"img/favicon.ico\">\n";
        if ($keywords) echo "  <meta name=\"Keywords\" content=\"$keywords\">\n";
        if ($meta) echo "  <meta name=\"Description\" content=\"$meta\">\n";
        
        foreach(self::$js as $js) {
            echo "  <SCRIPT  type=\"text/javascript\" src=\"{$js}\"></SCRIPT>\n";
        }
        foreach (self::$extra as $extra) {
            echo $extra.chr(10);
        }
        //echo '  <SCRIPT>$(document).ready(function(){sethostname("'.\hostName.'");onload();});$(window).resize(onresize);</SCRIPT>'.chr(10);
        echo ' </head>'.chr(10);
    }
    public static function Stop() {
        echo ' </body>'.chr(10);
        echo '</html>';
    }
    
}
