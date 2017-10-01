<?php
interface IView {
    public static function Create($item);
    public function Init();
    public function getContent();
    public function getTimeStamp();
}

