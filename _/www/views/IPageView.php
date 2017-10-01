<?php

interface IPageView extends IView{
    public function getTitle();
    public function getKeywords();
    public function getDescription();
    public function getDefaultSection();
}

