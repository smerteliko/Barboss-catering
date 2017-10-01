<?php
include 'libs/Database.php';
DataBase::$db = new DataBase;
DataBase::$db->generateexceptions = true;
DataBase::$db->connect(/*host:*/'localhost', /*username:*/'barboss', /*password:*/'p9vCfBrb', /*database:*/'barboss', 'utf-8');
