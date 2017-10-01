<?php
class TableCreate {
    public static function itemtables() {
        $itemtables = array();
        $itemtables[1] = ItemType::create('item_int', 'int', false);
        $itemtables[2] = ItemType::create('item_double', 'double', false);
        $itemtables[10] = ItemType::create('item_string64', 'char(64)', false);
        $itemtables[11] = ItemType::create('item_string256', 'varchar(256)', false);
        $itemtables[12] = ItemType::create('item_stringlong', 'mediumtext', false);
        return $itemtables;
    }
    public static function arraytables() {
        $itemtables = array();
        $itemtables[1] = ItemType::create('item_intarray', 'int', false);
        return $itemtables;
    }
}