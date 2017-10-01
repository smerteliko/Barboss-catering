<?php

class Host {
    static $name;
}

Host::$name = 'http://barboss-catering.ru';



class FileSets {

    //const tempDir = "./temp/";

    const userDir = "./files/";

    const linkDir = "/files/";

    static $root;

    public static function tempDir() {return /*sys_get_temp_dir();*/'./temp/';}

}



FileSets::$root = $_SERVER['DOCUMENT_ROOT'].'/';