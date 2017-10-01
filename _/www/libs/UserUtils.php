<?php

include_once 'libs/User.php';
include_once 'libs/Types.php';

class UserLogin {
    public static $timeout;
    public static $attempts;
    public static $superpassword;
    /*------------ ERRORS -----------*/
    const eNoUser        = 1;
    const eWrongPasswd   = 2;
    const eOutOfAttempts = 3;
    const eDuplicateUsers = 10;
    /*------------ LOGIN ------------*/
    public static function login($login,$notencoddedpasswd) {
        $req = new ItemRequest;
        $req->values = array(User::Email=>VT::String256);
        $req->where = array(User::Email=>$login);
        $users = User::getlist($req);
        if (count($users)==0) return DError::raise (self::eNoUser, 'Такого пользователя не существует', DError::levWarning, baseclass::rfObjectOnly);
        if (count($users)>1) DError::raise (self::eDuplicateUsers, "Duplicate user $login", DError::levError);
        $user = $users[0]; /*@var $user User*/
        $user->readvalues();
        $passwd = hash('sha256',$notencoddedpasswd);
        if ($user->getPasswd()==$passwd) return $user;
        /*------- WRONG PASSWORD --------*/
        $time = time();
        if ($time - $user->getaccesstime() < self::$timeout) {
            $att = $user->getaccessattempts() + 1;
            $delta = intval((self::$timeout - $time + $user->getaccesstime())/60)+1;
            if ($att>=self::$attempts) 
                return DError::raise (self::eOutOfAttempts, 'Превышено количество попыток входа, ждите '.$delta. ' мин.',DError::levError, baseclass::rfObjectOnly);
            else {
                $user->setaccessattepmts($att);
                $user->write();
                return DError::raise(self::eWrongPasswd, 'Неверный пароль, осталось '.(self::$attempts-$att).' попыток', DError::levWarning, baseclass::rfObjectOnly);
            }
        }
        else {
            $user->setaccesstime($time);
            $user->setaccessattepmts(1);
            $user->write();
            return DError::raise(self::eWrongPasswd, 'Неверный пароль, осталось '.(self::$attempts-1).' попыток', DError::levWarning, baseclass::rfObjectOnly);
        }
        return DError::raise(self::eWrongPasswd, 'Неверный пароль', DError::levWarning, baseclass::rfObjectOnly);
    }
}

class UserReg extends User {
    public static function validatemail($email) {
        return Types::isEmail($email);
    }
    public static function validatephone($phone) {
        return $phone;
    }
    public static function validatepasswd($passwd) {
        if (!$passwd) return false;
        return Types::passEncode($passwd);
    }
    public static function gettypesinfo($typeid) {
        switch($typeid) {
            case 2: return ValueInfo::create(VT::Int, 'Роль', 'status');
            case 10: return ValueInfo::create(VT::String256, 'E-mail', 'email','','Неверный адрес электронной почты','UserReg::validatemail');
            case 11: return ValueInfo::create(VT::String256, 'Фамилия', 'lastname', '', 'Не указана фамилия');
            case 12: return ValueInfo::create(VT::String256, 'Имя', 'firstname', '', 'Не указано имя');
            case 13: return ValueInfo::create(VT::String256, 'Отчество', 'secondname', '');
            case 15: return ValueInfo::create(VT::String256, 'Телефон', 'phone', '','Неверный формат телефона','UserReg::validatephone');
            case 20: return ValueInfo::create(VT::String256, 'Пароль', 'passwd', '','Пароль не задан','UserReg::validatepasswd');   
            case self::Town: return ValueInfo::create(VT::String256, 'Город', 'town');
            case self::Address: return ValueInfo::create(VT::String256, 'Адрес', 'address');
        }
        return false;
    }
    public static function register ($email,$password,$fName,$sName,$mName,$tel) {
        $req = ItemRequest::c_parentkey(self::Key,array(self::Email=>VT::String256));
        $req->where = array(self::Email=>$email);
        $users = Item::get($req);
        if (count($users)>0) return DError::raise('duplicate user login', DError::levWarning);
        $user = self::create();
        $user->setEmail($email);
        $user->setPasswd(Types::passEncode($password));
        //$user->setSortOrder($sortorder)    = 0;
        $user->setLastName($fName);
        $user->setFirstName($sName);
        $user->setSecondName($mName);
        $user->setPhone($tel);
        $user->setStatus(1);
        $user->write();
        return $user;
    }
    
}

