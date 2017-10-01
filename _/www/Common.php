<?php
include 'settings/host.php';
include 'settings/errors.php';
include 'settings/database.php';
include 'settings/tables.php';
include 'libs/BaseClass.php';
include 'libs/Std.php';
include 'libs/Url.php';

function myautoloader($classname) {
    $lib = false;
    if(file_exists('models/'.$classname.'.php')) $lib = 'models/'.$classname.'.php';
    elseif (file_exists('views/'.$classname.'.php')) $lib = 'views/'.$classname.'.php';
    //elseif (file_exists('page/'.$classname.'.php')) $lib = 'page/'.$classname.'.php';
    else DError::raise(0, "Class $classname not found");
    if ($lib) include_once($lib);
}

function registeralltypes() {
    Item::registerType(0x010, 'User');
    Item::registerType(0x110, 'DImage');
    Item::registerType(0x111, 'DImageMin');
    Item::registerType(0x120, 'Slider');
    Item::registerType(0x200, 'PageConstructor');
    Item::registerType(0x210, 'BlockGroup');
    Item::registerType(0x211, 'BlogPhotoreport');
    Item::registerType(0x230, 'TextBlock');
    Item::registerType(0x23A, 'Photoreport');
    Item::registerType(0x2A0, 'Section');
}