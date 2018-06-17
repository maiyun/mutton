<?php
/**
 * For PHPMailer 6.0.0-rc5
 * Url: https://github.com/PHPMailer/PHPMailer
 * User: JianSuoQiYue
 * Date: 2017/03/16 14:20
 * Last: 2018/06/14
 */
declare(strict_types = 1);

namespace M\lib {

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require ETC_PATH.'mailer.php';
    require LIB_PATH . 'Mailer/src/Exception.php';
    require LIB_PATH . 'Mailer/src/PHPMailer.php';
    require LIB_PATH . 'Mailer/src/SMTP.php';

    class Mailer {

        private static $_poll = [];

        /* @var $link PHPMailer */
        private $_link;

        public static function get(string $name = 'main', array $opt = []): Mailer {
            if (isset(self::$_poll[$name])) {
                return self::$_poll[$name];
            } else {
                $mailer = new Mailer();
                $mailer->connect($opt);
                self::$_poll[$name] = $mailer;
                return self::$_poll[$name];
            }
        }

        // --- 连接服务器 ---
        public function connect(array $opt = []): void {

            $host = isset($opt['host']) ? $opt['host'] : MAIL_HOST;
            $user = isset($opt['user']) ? $opt['user'] : MAIL_USER;
            $pwd = isset($opt['pwd']) ? $opt['pwd'] : MAIL_PWD;
            $secure = isset($opt['secure']) ? $opt['secure'] : MAIL_SECURE;
            $port = isset($opt['port']) ? $opt['port'] : MAIL_PORT;

            $this->_link = new PHPMailer();
            $this->_link->isSMTP();
            $this->_link->Host = $host;
            $this->_link->SMTPAuth = true;
            $this->_link->Username = $user;
            $this->_link->Password = $pwd;
            $this->_link->SMTPSecure = $secure;
            $this->_link->Port = $port;
            $this->_link->CharSet = 'UTF-8';

        }

        /**
         * --- 发送 ---
         * @param string $from
         * @param string $nickname
         * @param string $to
         * @param string $title
         * @param string $content
         * @param string $altContent
         * @return bool
         * @throws Exception
         */
        public function send(string $from, string $nickname, string $to, string $title, string $content, string $altContent = ''): bool {

            try {
                $this->_link->setFrom($from, $nickname);
                $this->_link->addAddress($to);

                $this->_link->isHTML(true);
                $this->_link->Subject = $title;
                $this->_link->Body = $content;
                $this->_link->AltBody = $altContent;

                if ($this->_link->send()) {
                    return true;
                } else {
                    // echo $this->_link->ErrorInfo;
                    return false;
                }
            } catch (Exception $e) {
                throw $e;
            }

        }

    }

}

