<?php
/**
 * For PHPMailer 6.0.0-rc5
 */

namespace C\lib {

    use PHPMailer\PHPMailer\PHPMailer;

    require LIB_PATH . 'PHPMailer/PHPMailer.php';
    require LIB_PATH . 'PHPMailer/SMTP.php';
    require LIB_PATH . 'PHPMailer/Exception.php';

    class Mailer {

        private static $link = NULL;

        // --- 连接服务器 ---
        public static function connect($host = NULL, $user = NULL, $pwd = NULL) {

            $host = $host ? $host : MAIL_HOST;
            $user = $user ? $user : MAIL_USER;
            $pwd = $pwd ? $pwd : MAIL_PWD;

            self::$link = new PHPMailer();
            self::$link->isSMTP();
            self::$link->Host = $host;
            self::$link->SMTPAuth = true;
            self::$link->Username = $user;
            self::$link->Password = $pwd;
            self::$link->SMTPSecure = 'ssl';
            self::$link->Port = 465;
            self::$link->CharSet = 'UTF-8';

        }

        // --- 发送 ---
        public static function send($from, $nickname, $to, $title, $content, $altContent = '') {

            self::$link->setFrom($from, $nickname);
            self::$link->addAddress($to);
            self::$link->isHTML(true);

            self::$link->Subject = $title;
            self::$link->Body = $content;
            // self::$link->AltBody = 'This is the body in plain text for non-HTML mail clients';

            if(self::$link->send()) {
                return true;
            } else {
                echo 'Message could not be sent.';
                echo 'Mailer Error: ' . self::$link->ErrorInfo;
                return false;
            }

        }

    }

}

