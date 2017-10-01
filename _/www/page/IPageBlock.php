<?php
interface IPageBlock {
    public function getTitle();
    public function getMeta();
    public function getPreview($type=0);
    public function getContent();
    public function getTimestamp();
}

////////////////////////////////////////////////////
// Должны поддерживаться соглашения: 
// v1 - порядок сортировки
// v2 - parent
// 10 - title
// 20 - ShortText
// Страницы имеют коды 0x200-0x20F
// Блоки имеют коды 0x210-0x22F
////////////////////////////////////////////////////