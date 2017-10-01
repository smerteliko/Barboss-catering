<?php
function getDefaultView($type){
    switch($type){
        case 0x010: return false;
        case 0x110: return false;
        case 0x111: return false;
        case 0x200: return 'VPageConstructor';
        case 0x210: return 'VBlockGroup';
        case 0x211: return 'VBlogPhotoreport';
        case 0x230: return 'VTextBlock';
        case 0x23A: return 'VPhotoreport';
        default: return false;
    }
}
