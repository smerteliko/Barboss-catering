<?php

function getEditorListView($type) {
    switch($type){
        case 0x23A: return 'DefaultPhotoreport';
        case 0x120: return 'DefaultSlider';
        default: return 'DefaultList';
    }
}

