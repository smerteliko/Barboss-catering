<?php
interface IFile {
    public function getFilename();
}


interface IImage extends IFile{
    public function getImgWidth();
    public function getImgHeight();
    public function getImgType();
    //public function getImgFilename();
}