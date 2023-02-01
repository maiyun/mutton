<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2023-1-29 15:35:42
 * Last: 2023-1-29 15:35:42
 */
declare(strict_types = 1);

namespace lib;

use lib\Kv\IKv;
use sys\Ctr;

require ETC_PATH . 'jwt.php';

class Jwt {

    /* @var $_link IKv */
    private $_link = null;

    /** @var string 在前端或 Kv 中储存的名前缀 */
    private $_name;
    /** @var int 有效期 */
    private $_ttl;
    /** --- cookie 模式时是否仅支持 SSL --- */
    private $_ssl;
    /** --- 验证密钥 --- */
    private $_secret;
    /** --- 是否从头部读取 --- */
    private $_auth;
    /** --- 主控制器 --- */
    private Ctr $_ctr;

    /**
     * @param Ctr $ctr 模型实例
     * @param array $opt name, ttl, ssl, secret, auth: false, true 则优先从头 Authorization 或 post _auth 值读取 token
     * @param IKv $link 实例
     */
    public function __construct(Ctr $ctr, array $opt = [], ?IKv $link = null) {
        $this->_ctr = $ctr;
        $this->_link = $link;
        $this->_name = isset($opt['name']) ? $opt['name'] : JWT_NAME;
        $this->_ttl = isset($opt['ttl']) ? $opt['ttl'] : JWT_TTL;
        $this->_ssl = isset($opt['ssl']) ? $opt['ssl'] : JWT_SSL;
        $this->_secret = isset($opt['secret']) ? $opt['secret'] : JWT_SECRET;
        $this->_auth = isset($opt['auth']) ? $opt['auth'] : JWT_AUTH;

        $jwt = '';
        if ($this->_auth) {
            $a = $this->_ctr->getAuthorization();
            if (!is_string($a)) {
                return;
            }
            $jwt = $a;
        }
        if (!$jwt) {
            if (!isset($_COOKIE[$this->_name])) {
                return;
            }
            $jwt = $_COOKIE[$this->_name];
        }

        $data = Jwt::decode($jwt, $this->_link, $this->_name, $this->_secret);
        if (!$data) {
            // --- 清除 cookie ---
            if (isset($_COOKIE[$this->_name])) {
                unset($_COOKIE[$this->_name]);
            }
            return;
        }
        $this->_ctr->setPrototypeRef('_jwt', $data);
    }

    /**
     * --- 将 _jwt 数据封装并返回（创建新的或者续期老的 token），默认会同时设置一个 cookie（data 值会自动设置 token、exp）， ---
     */
    public function renew() {
        $time = time();
        $data = &$this->_ctr->getPrototype('_jwt');
        $token = isset($data['token']) ? $data['token'] : Core::random(16, Core::RANDOM_LUN);
        $data['exp'] = $time + $this->_ttl;
        $data['token'] = $token;
        // --- 拼装 ---
        $header = base64_encode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT'
        ]));
        $payload = base64_encode(json_encode($data));
        $signature = base64_encode(hash_hmac('sha256', $header . '.' . $payload, $this->_secret, true));
        $jwt = $header . '.' . $payload . '.' . $signature;
        if (!$this->_auth) {
            Core::setCookie($this->_name, $jwt, [
                'ttl' => $this->_ttl,
                'ssl' => $this->_ssl
            ]);
        }
        return $jwt;
    }

    /**
     * --- 销毁 jwt，其实就是将 token block 信息写入 redis，如果没有 redis 则不能销毁，返回数组代表销毁成功的 token 和原 exp，否则失败返回 false ---
     */
    public function destory(): array | bool {
        if (!$this->_link) {
            return false;
        }
        $jwt = &$this->_ctr->getPrototype('_jwt');
        if (!isset($jwt['token'])) {
            return false;
        }
        $time = time();
        $token = $jwt['token'];
        $exp = $jwt['exp'];
        $ttl = $exp - $time;
        if ($ttl <= 0) {
            $jwt = [];
            return [
                'token' => $token,
                'exp' => $exp
            ];
        }
        $jwt = [];
        $this->_link->set($this->_name . '_block_' . $token, '1', $ttl + 1);
        return [
            'token' => $token,
            'exp' => $exp
        ];
    }

    /**
     * --- 获取 jwt 原始字符串，不保证有效 ---
     */
    public static function getOrigin(Ctr $ctr, string $name = '', $auth = false) {
        if (!$name) {
            $name = JWT_NAME;
        }
        $jwt = '';
        if ($auth) {
            $a = $ctr->getAuthorization();
            if (!is_string($a)) {
                return $jwt;
            }
            $jwt = $a;
        }
        if (!$jwt) {
            if (!isset($_COOKIE[$name])) {
                return $jwt;
            }
            $jwt = $_COOKIE[$name];
        }
        return $jwt;
    }

    /**
     * --- decode ---
     * 不传入 link 的话，将不做 block 有效校验，只做本身的 exp 有效校验
     */
    public static function decode(string $val, ?IKv $link = null, string $name = '', string $secret = ''): array | false {
        if (!$val) {
            return false;
        }
        if (!$secret) {
            $secret = JWT_SECRET;
        }
        if (!$name) {
            $name = JWT_NAME;
        }
        $jwtArray = explode('.', $val);
        if (!isset($jwtArray[2])) {
            return false;
        }
        // $jwtArray[1]: payload, $jwtArray[2]: signature
        // --- 判断是否合法 ---
        $nsignature = base64_encode(hash_hmac('sha256', $jwtArray[0] . '.' . $jwtArray[1], $secret, true));
        if ($nsignature !== $jwtArray[2]) {
            return false;
        }
        $payload = base64_decode($jwtArray[1]);
        if (!$payload) {
            return false;
        }
        $data = json_decode($payload, true);
        if (!$data) {
            return false;
        }
        // --- 检测 token ---
        if (!isset($data['token'])) {
            return false;
        }
        // --- 检测 exp ---
        if (!isset($data['exp'])) {
            return false;
        }
        $time = time();
        if ($data['exp'] < $time) {
            // --- 过期 ---
            return false;
        }
        // --- 检测 token 是否有效 ---
        if (!$link || !$link->get($name . '_block_' . $data['token'])) {
            return $data;
        }
        return false;
    }

    /**
     * --- 仅往 redis 写禁止相关 token 的数据，一般用于异步通知时在异处的服务器来调用的 ---
     */
    public static function block(string $token, int $exp, IKv $link, string $name = ''): bool {
        $time = time();
        if (!$name) {
            $name = JWT_NAME;
        }
        $ttl = $exp - $time;
        if ($ttl <= 0) {
            return true;
        }
        $link->set($name . '_block_' . $token, '1', $ttl + 1);
        return true;
    }

    /**
     * @param Ctr $ctr 模型实例
     * @param array $opt name, ttl, ssl, secret, auth: false, true 则优先从头 Authorization 或 post _auth 值读取 token
     * @param IKv $link 实例
     */
    public static function get(Ctr $ctr, array $opt = [], ?IKv $link = null) {
        return new Jwt($ctr, $opt, $link);
    }

}
