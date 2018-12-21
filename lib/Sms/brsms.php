<?php

/*
 * v1.0
 */

class BrSms {

    private $_srv = '', $_usr = '', $_tok = '';

    public function __construct($srv, $usr, $tok) {

        $this->_srv = $srv;
        $this->_usr = $usr;
        $this->_tok = $tok;

    }

    public function sendWithOption($opt = []) {
        if (isset($opt['type'])) {
            if (is_array($opt['phone'])) {
                $phone = implode(',', $opt['phone']);
            } else {
                $phone = $opt['phone'];
            }
            // --- 先进行鉴权操作 ---
            $md5 = '';
            $body = '';
            if ($opt['type'] == '0') {
                if (isset($opt['body']) && isset($opt['template']) && isset($opt['sign']) && isset($opt['body'])) {
                    $md5 = md5(json_encode($opt['body']) . $phone . $_SERVER['REQUEST_TIME'] . $opt['template'] . $opt['sign'] . $this->_tok);
                    $body = $opt['body'];
                } else {
                    return -3;
                }
            } else {
                if (isset($opt['template']) && isset($opt['sign'])) {
                    $md5 = md5($phone . $_SERVER['REQUEST_TIME'] . $opt['template'] . $opt['sign'] . $this->_tok);
                } else {
                    return -2;
                }
            }
            $j = $this->_post($this->_srv . '/send', [
                'md5' => $md5,
                'phone' => $phone,
                'unix' => $_SERVER['REQUEST_TIME'],
                'accountId' => $this->_usr,
                'template' => $opt['template'],
                'sign' => $opt['sign'],
                'body' => $body,
                'type' => $opt['type']
            ]);
            $j = json_decode($j);
            if ($j->result == 1) {
                return true;
            } else {
                return $j->msg;
            }
        } else {
            return -1;
        }
    }

    public function send($phone, $body, $sign, $template) {
        return $this->sendWithOption([
            'phone' => $phone,
            'body' => $body,
            'sign' => $sign,
            'template' => $template,
            'type' => '0'
        ]);
    }

    public function sendMarket($phone, $sign, $template) {
        return $this->sendWithOption([
            'phone' => $phone,
            'sign' => $sign,
            'template' => $template,
            'type' => '1'
        ]);
    }

    // --- 提交任务异步群发，50000个号以内一般，但不在本地做判断防止服务端改需求或扩容 ---
    public function task($name, $phone, $sign, $template) {
        if (is_array($phone)) {
            $phone = implode(',', $phone);
        }
        $md5 = md5($name. $phone . $_SERVER['REQUEST_TIME'] . $template . $sign . $this->_tok);
        $j = $this->_post($this->_srv . '/task', [
            'md5' => $md5,
            'phone' => $phone,
            'unix' => $_SERVER['REQUEST_TIME'],
            'accountId' => $this->_usr,
            'template' => $template,
            'sign' => $sign,
            'name' => $name
        ]);
        $j = json_decode($j);
        if ($j->result == 1) {
            return true;
        } else {
            return $j->msg;
        }
    }

    private function _post($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        if (substr($url, 0, 6) == 'https:') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        $output = curl_exec($ch);
        curl_close($ch);
        if ($output !== false) {
            return $output;
        } else {
            return false;
        }

    }

}

