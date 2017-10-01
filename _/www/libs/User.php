<?php
include_once 'libs/Item.php';

class User extends Item {
    const TypeKey = 0x010;
    
    const usNone  =  0;
    const usUser  =  1;
    const usAdmin = 10;
    
    const Status    =  1;
    const Email     = 10;
    const FName     = 11;
    const SName     = 12;
    const MName     = 13;
    const Phone     = 15;
    const Passwd    = 20;
    const Town      = 25;
    const Address   = 26;
    
    /*----------- Item Overrides --------------*/
    public static function defType() {return self::TypeKey;}
    public static function defFlags() {return Item::fEnabled;}
    public static function getEditorClass() { return 'UserEditor'; }
    
    public function getEmail() {return $this->value(self::Email);}
    public function setEmail($email) {$this->setvalue(self::Email, VT::String256, $email);}
    public function getPasswd() {return $this->value(self::Passwd);}
    public function setPasswd($passwd) {$this->setvalue(self::Passwd, VT::String256, $passwd);}
    public function getSortOrder() {return $this->v1;}
    public function setSortOrder ($sortorder) {$this->v1 = $sortorder;}
    public function getStatus() {return $this->v2;}
    public function setStatus($status) {$this->v2 = $status;}
    public function getLastName() {return $this->value(11);}
    public function setLastName($lastname) {$this->setvalue(11, VT::String256, $lastname);}
    public function getFirstName() {return $this->value(12);}
    public function setFirstName($firstname) {$this->setvalue(12, VT::String256, $firstname);}
    public function getSecondName() {return $this->value(13);}
    public function setSecondName($secondname) {$this->setvalue(13, VT::String256, $secondname);}
    public function getPhone() {return $this->value(self::Phone);}
    public function setPhone($phone) {$this->setvalue(self::Phone, VT::String256, $phone);}
    public function getTown() {return $this->value(self::Town);}
    public function setTown($town) {$this->setvalue(self::Town, VT::String256, $town);}
    public function getAddress() {return $this->value(self::Address);}
    public function setAddress($address) {$this->setvalue(self::Address, VT::String256, $address);}
    
    public function setaccesstime($time) {$this->setvalue(201, VT::Int, $time);}
    public function getaccesstime() {return $this->value(201);}
    public function setaccessattepmts($att) {$this->setvalue(202, VT::Int, $att);}
    public function getaccessattempts() {return $this->value(202);}
    public function afterlogin() {
        $this->setvalue(201, VT::None, 0);
        $this->setvalue(202, VT::None, 0);
    }
    public function addfavorite($prodid) {
        if (!$this->isvalid()) return false;
        ItemLink::add($this->id, $prodid, self::linkFavorite);
        return true;
    }
    public function removefavorite($prodid) {
        if (!$this->isvalid()) return false;
        ItemLink::remove($this->id, $prodid, self::linkFavorite);
        return true;
    }
    public function getfavorite() {
        if (!$this->isvalid()) return false;
        return ItemLink::getall($this->id, self::linkFavorite);
    }
    
    /******************* UTILS *****************/
    public function ispriveleged() { return $this->v1==10; }
}

Item::registerType(0x010, 'User');