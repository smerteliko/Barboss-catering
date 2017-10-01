<?php

class EUser extends BasePageView {
    public static function eName(){return'Список пользователей';}
    public function getContent() {
        $user = $this->item;
        $content = DForm::Form($_SERVER['REQUEST_URI'])
            .Editor::preedit2($user)
            .'Логин:'.DForm::Text('login', $user->getEmail()).'<br><br>';
        if ($user->isvalid())
            $content .= DForm::Checkbox('chpasswd', false).' сменить пароль<br><br>';
        else $content .= DForm::Hidden ('chpasswd', '1');
        $content .= 'Пароль:'.DForm::TextPwd('passwd', '').'<br>'
            .'Шифр пароля:'.$user->getPasswd().'<br>'
            .DForm::Submit('applyuser', 'Сохранить')
            .'</form>';
        return $content;
    }
}
