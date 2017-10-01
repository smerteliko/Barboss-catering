<?php
class Email{
    const Host = "smtp.yandex.ru";
    const Port = 465;
    const email = 'webmaster@barboss-catering.ru';
    const passwd = '4EST7rW8qT';
    /*static function send($nFrom, $eFrom, $nSend, $eSend, $cFrom, $cSend, $subj0, $body0){
        $to000 = Email::encode($nSend, $cFrom, $cSend).' <'.$eSend.'>';
        $subj0 = Email::encode($subj0, $cFrom, $cSend);
        if($eFrom!='') $from0 = Email::encode($nFrom, $cFrom, $cSend).' <'.$eFrom.'>';
        if($cFrom != $cSend) $body0 = iconv($cFrom, $cSend, $body0);
        if($eFrom!='') $heads = "From: $from0\r\n";
        $heads .= "Content-type: text/html; charset=$cSend\r\n";
        return mail($to000, $subj0, $body0, $heads);
    }*/
    public static function send($nFrom, $eFrom, $nSend, $eSend, $cFrom, $cSend, $subj0, $body0) {
        date_default_timezone_set('Etc/UTC');
        include_once Server::$documentroot.'/../PHPMailer/PHPMailerAutoload.php';
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->CharSet = "utf-8";
        $mail->ContentType = 'text/html';
        $mail->SMTPDebug = 1;
        $mail->Debugoutput = 'html';
        $mail->Host = self::Host;
        $mail->Port = self::Port;
        $mail->SMTPSecure = 'ssl';
        $mail->SMTPAuth = true;
        $mail->Username = self::email;
        $mail->Password = self::passwd;
        $mail->setFrom(self::email, $nFrom);
        $mail->addAddress($eSend);
        $mail->Subject = $subj0;
        $mail->Body    = $body0;

        return $mail->send();
    }
    static function encode($str, $cFrom, $cSend) {
        if($cFrom != $cSend) $str = iconv($cFrom, $cSend, $str);
        return '=?' . $cSend . '?B?' . base64_encode($str) . '?=';
    }
}
