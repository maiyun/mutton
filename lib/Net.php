<?php
/**
 * Created by PhpStorm.
 * User: yunbo
 * Date: 2015/10/26
 * Time: 14:23
 */

namespace C\lib {

	class Net {

		public static function get($url) {

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			$output = curl_exec($ch);
			curl_close($ch);
			if ($output) return $output;
			else return false;

		}

		public static function post($url, $data = []) {

            $upload = false;
            foreach ($data as $i) {
                if (isset($i[0]) && ($i[0] === '@')) {
                    $upload = true;
                    break;
                }
            }
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS,
                $upload ? $data : http_build_query($data));
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $output = curl_exec($ch);
            curl_close($ch);
            if ($output) {
                return $output;
            } else {
                return false;
            }

		}

		public static function getIP() {

			if (getenv('HTTP_CLIENT_IP')) {
				return getenv('HTTP_CLIENT_IP');
            } else if (getenv('HTTP_X_FORWARDED_FOR')) {
                return getenv('HTTP_X_FORWARDED_FOR');
            } else
				return getenv('REMOTE_ADDR');

		}

		public static function mail($server, $from, $nickname, $to, $title, $content) {

            if (!preg_match('/\w[a-zA-Z0-9\.\-\+]*\@(\w+[a-zA-Z0-9\-\.]+\w)/i', $to, $ms)) {
                return ['success' => false, 'error' => 'Email address invalid'];
            }
            if (!getmxrr($ms[1], $mx)) {
                return ['success' => false, 'error' => 'MX record of host not found!'];
            }
            $mx = $mx[0];

            $commands = ['HELO '.$server, 'MAIL FROM:<'.$from.'@'.$server.'>', 'RCPT TO:<'.$to.'>', 'DATA', 'content', 'QUIT'];
            $contents = [
                'MIME-Version: 1.0',
                'Delivered-To: '.$to,
                'Subject: =?UTF-8?B?'.base64_encode($title).'?=',
                'From: =?UTF-8?B?'.base64_encode($nickname).'?= <'.$from.'@'.$server.'>',
                'To: '.$to,
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
                '',
                base64_encode($content)
            ];
            $fp = fsockopen($mx, 25);
            foreach($commands as $c) {
                if ($c == 'content') {
                    $content = join("\r\n", $contents)."\r\n.\r\n";
                    fwrite($fp, $content);
                } else {
                    fwrite($fp, $c."\r\n");
                }
                $r = fgets($fp);
            }
            fclose($fp);
            return ['success' => true];

        }

	}

}

