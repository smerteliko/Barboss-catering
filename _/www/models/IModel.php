<?php

interface IModel {
    public function Apply();
    public function Access($user);
}

class UserAccess {
    const None = 0;
    const Read = 1;
    const Write = 2;
    const ReadWrite = 3;
}