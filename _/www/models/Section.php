<?php
class Section extends Item implements IModel{
    const TypeKey = 0x2A0;
    const AdmName = 'Секции';
    const AdmInfo = 'Формирование шаблона для страницы';
    const DefaulPage = 4;
    const Content = 20;
    /*--------- Item Overrides ---------*/
    public static function defType() { return self::TypeKey; }
    public static function admName(){return self::AdmName;}
    public static function admInfo(){return self::AdmInfo;}
    public static function getTypeName() {return 'Секция страниц';}
    /*------------ Accessors -----------*/
    public function setContent($v){$this->setvalue(self::Content,VT::StringLong,$v);}
    public function getContent(){return $this->value(self::Content);}
    public function setDefaultPage($v){$this->v4 = $v;}
    public function getDefaultPage(){return $this->v4;}
    public function Access($user){return true;}
    public function Apply(){
        if(Post::set('defsection')) $this->setDefaultPage(Post::int('defsection'));
        if(Post::set('name')) $this->setName(Post::html('name'));
        $this->setContent(Post::any('content'));
        $this->write();
        return true;
    }
}
Item::registerType(0x2A0,'Section');