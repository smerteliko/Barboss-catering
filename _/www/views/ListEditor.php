<?php

function getEditorView($type) {
    switch($type) {
        case 0x010: return 'EUser';
        case 0x110: return false;
        case 0x111: return false;
        case 0x120: return 'ESlider';
        case 0x200: return 'EPageConstructor';
        case 0x210: return 'EBlockGroup';
        case 0x230: return 'ETextBlock';
        case 0x23A: return 'EPhotoreport';
        case 0x2A0: return 'ESection';
        default: return false;
    }
}