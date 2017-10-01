<?php

class DSearch
{
    public static function tables_exists()
    {
        $db = DataBase::$db;
        $t1 = Tables::searchwords;
        $q = $db->query("SHOW TABLES LIKE '$t1'");
        $r = $q->fetch_row();
        $q->free();
        if ($r) return true;
        else return false;
    }
    public static function create_tables()
    {
        $db = DataBase::$db;
        $t1 = Tables::searchwords;
        $t2 = Tables::searchitems;
        $db->query("CREATE TABLE IF NOT EXISTS $t1 (id INT primary key auto_increment, word varchar(64), INDEX (word))");
        $db->query("CREATE TABLE IF NOT EXISTS $t2 (wordid INT, itemid INT, PRIMARY KEY (wordid,itemid))");
    }
    
    private static function extract_words($string)
    {
        $out = array();
        $request = preg_replace('/[^a-zA-ZА-Яа-яЁё0-9]/u', ' ', html_entity_decode($string,ENT_QUOTES,'utf-8'));
        $words = explode(' ', $request);
        foreach($words as $word)
        {
            $out[] = mb_convert_case($word,  MB_CASE_UPPER, 'utf-8');
        }
        return $out;
    }
    public static function push($itemid,$text)  {
        $words = self::extract_words($text);
        $db = DataBase::$db;
        $t1 = Tables::searchwords;
        $t2 = Tables::searchitems;
        $names = false;
        foreach ($words as $word) {
            if (mb_strlen($word,'utf-8')<3) continue;
            if (!$names) $names = $db->escape($word);
            else $names .= ','.$db->escape($word);
        }
        $q0 = $db->query("SELECT id,word FROM $t1 WHERE word in ($names)");
        while($r0 = $q0->fetch_row()) {
            $exists[$r0[1]] = $r0[0];
        }
        foreach($words as $word) {
            if (mb_strlen($word,'utf-8')<3) continue;
            if (isset($exists[$word])) $wordid = $exists[$word];
            else {
                $dbword = $db->escape($word);
                $wordid = $db->insert("INSERT INTO $t1(word) VALUES($dbword)");
            }
            $db->query("INSERT IGNORE INTO $t2(wordid,itemid) VALUES($wordid,$itemid)");
        }
    }
    public static function pushindivisible($itemid,$word) {
        $db = DataBase::$db;
        $t1 = Tables::searchwords;
        $t2 = Tables::searchitems;
        $dbword = $db->escape($word);
        $q0 = $db->query("SELECT id,word FROM $t1 WHERE word=$dbword");
        $r0 = $q0->fetch_row();
        if ($r0) $wordid = $r0[0];
        else $wordid = $db->insert("INSERT INTO $t1(word) VALUES($dbword)");
        $db->query("INSERT IGNORE INTO $t2(wordid,itemid) VALUES($wordid,$itemid)");
    }
    public static function search($text)
    {
        $words = self::extract_words($text);
        $db = DataBase::$db;
        $t1 = Tables::searchwords;
        $t2 = Tables::searchitems;
        $names = false;
        foreach ($words as $word) {
            if (!$names) $names = $db->escape($word);
            else $names .= ','.$db->escape($word);
        }
        $q0 = $db->query("SELECT id FROM $t1 WHERE word in ($names)");
        $wordids = false;
        while($r0 = $q0->fetch_row()) {
            if (!$wordids) $wordids = "$r0[0]";
            else $wordids .= ",$r0[0]";
        }
        if (!$wordids) return array();
        $q1 = $db->query("SELECT itemid FROM $t2 WHERE wordid in ($wordids)");
        $out = array();
        while($r1 = $q1->fetch_row())
                $out[$r1[0]] = 1;
        return $out;
    }
    public static function delete($itemid)
    {
        $db = DataBase::$db;
        $t2 = Tables::searchitems;
        $db->query("DELETE FROM $t2 WHERE itemid=$itemid");
    }
}