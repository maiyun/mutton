<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2018-6-17 23:29
 * Last: 2020-1-17 01:05:14, 2020-2-12 13:02:35, 2020-3-30 15:31:48, 2022-3-25 17:05:01, 2023-12-21 13:26:06
 */
declare(strict_types = 1);

namespace sys;

use lib\Core;
use lib\Db;
use lib\Kv\IKv;
use lib\Session;
use lib\Text;

class Ctr {

    /** @var array --- 路由参数序列数组 --- */
    protected $_param = [];

    /** @var string --- 当前的 action 名 --- */
    protected $_action = '';

    /** @var array --- 请求的 header 列表 --- */
    protected $_headers = [];

    /** @var array --- GET 数据 --- */
    protected $_get;

    /** @var array --- 原始 POST 数据 --- */
    protected $_rawPost;

    /** @var array --- POST 数据 --- */
    protected $_post;

    /** @var string --- 原始 input 字符串 --- */
    protected $_input;

    /** @var array --- 上传的文件列表 --- */
    protected $_files = [];

    /** @var array --- Cookie 数组 --- */
    protected $_cookie;

    /** @var array --- Jwt 数组 --- */
    protected $_jwt = [];

    /** @var array --- Session 数组 --- */
    protected $_session = [];

    /** @var Session --- Session|null 对象 --- */
    protected $_sess = null;

    /** @var int --- 页面浏览器客户端缓存 --- */
    protected $_cacheTTL = CACHE_TTL;

    /** @var string --- XSRF TOKEN 值 --- */
    protected $_xsrf = '';

    /** @var int --- 自定义 http code --- */
    protected $_httpCode = 0;

    /** --- 获取类内部的 prototype --- */
    public function &getPrototype($name) {
        return $this->{$name};
    }

    /** --- 设置类内部的 prototype --- */
    public function setPrototype($name, $val): void {
        $this->{$name} = $val;
    }

    /** --- 设置类内部的 prototype 并且是引用，Mutton: true, Kebab: false --- */
    public function setPrototypeRef($name, &$val): void {
        $this->{$name} = &$val;
    }

    /**
     * --- 实例化后会执行的方法，可重写此方法 ---
     * @return bool|array|string|null|void
     */
    public function onLoad() {
        return true;
    }

    /**
     * --- 整个结束前会执行本方法，可重写此方法对输出结果再处理一次 ---
     * @param $rtn 之前用户的输出结果
     * @return bool|array|string|null
     */
    public function onUnload($rtn) {
        return $rtn;
    }

    /**
     * --- 获取截止当前时间的总运行时间 ---
     * @param bool $ms 为 true 为毫秒，否则为秒
     * @return float
     */
    protected function _getRunTime(bool $ms = false): float {
        $t = microtime(true) - START_TIME;
        return $ms ? $t * 1000 : $t;
    }

    /**
     * --- 获取截止当前内存的使用情况 ---
     * @return int
     */
    protected function _getMemoryUsage(): int {
        return memory_get_usage() - START_MEMORY;
    }

    /**
     * --- 加载视图 ---
     * @param string $path_mtmp
     * @param array $data
     * @return string
     */
    protected function _loadView(string $path_mtmp, $data = []) {
        $data['_urlBase'] = URL_BASE;
        $data['_urlFull'] = URL_FULL;
        $data['_staticVer'] = STATIC_VER;
        $data['_staticPath'] = STATIC_PATH;
        extract($data);
        ob_start();
        require VIEW_PATH . $path_mtmp . '.php';
        $html = ob_get_clean();
        return Core::purify($html);
    }

    /**
     * --- 检测提交的数据类型 ---
     * @param array $input 要校验的输入项
     * @param array $rule 规则
     * @param array $rtn 返回值
     * @return bool
     */
    protected function _checkInput(array &$input, array $rule, &$rtn) {
        // --- 遍历规则 ---
        // --- $input, ['xx' => ['require', '> 6', [0, 'xx 必须大于 6']], 'yy' => [], '_xsrf' => []], $rtn ---
        foreach ($rule as $key => $val) {
            // --- $key 就是上面的 xx ---
            if (!isset($input[$key])) {
                // --- 原值不存在则设定为 null ---
                $input[$key] = null;
            }
            // --- 判断是否需要遍历 val ---
            $c = count($val);
            if ($c === 0) {
                continue;
            }
            // --- ['require', '> 6', [0, 'xx 必须大于 6']] ---
            $lastK = $c - 1;
            if (!isset($val[$lastK][0]) || !isset($val[$lastK][1]) || !is_int($val[$lastK][0]) || !is_string($val[$lastK][1])) {
                $rtn = [0, 'Param error'];
                return false;
            }
            for ($k = 0; $k < $lastK; ++$k) {
                $v = $val[$k] ? $val[$k] : '';
                if (is_array($v)) {
                    if (count($v) === 0) {
                        $rtn = $val[$lastK];
                        return false;
                    }
                    // --- 判断提交的数据是否在此 array 之内，若没有提交数据，则自动设置为第一个项 ---
                    if ($input[$key] === null) {
                        $input[$key] = $v[0];
                    }
                    else if (!in_array($input[$key], $v)) {
                        // --- 不在 ---
                        $rtn = $val[$lastK];
                        return false;
                    }
                }
                else {
                    switch ($v) {
                        case 'require': {
                            if ($input[$key] === null || $input[$key] === '') {
                                $rtn = $val[$lastK];
                                return false;
                            }
                            break;
                        }
                        case 'num':
                        case 'number': {
                            if ($input[$key] && !is_numeric($input[$key])) {
                                $rtn = $val[$lastK];
                                return false;
                            }
                            break;
                        }
                        case 'array': {
                            if ($input[$key] !== null && !is_array($input[$key])) {
                                $rtn = $val[$lastK];
                                return false;
                            }
                            break;
                        }
                        case 'bool':
                        case 'boolean': {
                            if ($input[$key] !== null && !is_bool($input[$key])) {
                                // --- 如果不是 bool 直接失败，字符串的 true, false 也会失败 ---
                                $rtn = $val[$lastK];
                                return false;
                            }
                            break;
                        }
                        case 'string': {
                            if ($input[$key] !== null && !is_string($input[$key])) {
                                // --- 如果不是 string 直接失败 ---
                                $rtn = $val[$lastK];
                                return false;
                            }
                            break;
                        }
                        default: {
                            if ($input[$key]) {
                                if ($v[0] === '/') {
                                    // --- 正则 ---
                                    if (!preg_match($v, $input[$key])) {
                                        $rtn = $val[$lastK];
                                        return false;
                                    }
                                }
                                else if (preg_match('/^([><=]+) *([0-9]+)$/', $v, $match)) {
                                    // --- 判断表达式 ---
                                    $needReturn = false;
                                    $inputNum = (float)$input[$key];
                                    $num = (float)$match[2];
                                    switch ($match[1]) {
                                        case '>': {
                                            if ($inputNum <= $num) {
                                                $needReturn = true;
                                            }
                                            break;
                                        }
                                        case '<': {
                                            if ($inputNum >= $num) {
                                                $needReturn = true;
                                            }
                                            break;
                                        }
                                        case '>=': {
                                            if ($inputNum < $num) {
                                                $needReturn = true;
                                            }
                                            break;
                                        }
                                        case '<=': {
                                            if ($inputNum > $num) {
                                                $needReturn = true;
                                            }
                                            break;
                                        }
                                        case '=':
                                        case '==':
                                        case '===': {
                                            if ($inputNum !== $num) {
                                                $needReturn = true;
                                            }
                                            break;
                                        }
                                        case '!=':
                                        case '<>': {
                                            if ($inputNum === $num) {
                                                $needReturn = true;
                                            }
                                            break;
                                        }
                                    }
                                    if ($needReturn) {
                                        $rtn = $val[$lastK];
                                        return false;
                                    }
                                }
                                else {
                                    if ($input[$key] !== $v) {
                                        $rtn = $val[$lastK];
                                        return false;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * --- 检测提交的数据类型（会检测 XSRF） ---
     * @param array $input 要校验的输入项
     * @param array $rule 规则
     * @param array $rtn 返回值
     * @return bool
     */
    protected function _checkXInput(array &$input, array $rule, &$rtn) {
        if (!isset($rule['_xsrf'])) {
            $rule['_xsrf'] = ['require', $this->_cookie['XSRF-TOKEN'], [0, 'Bad request, no permission.']];
        }
        return $this->_checkInput($input, $rule, $rtn);
    }
    
    /**
     * --- 当前页面开启 XSRF 支持（主要检测 cookie 是否存在） ---
     * --- 如果当前页面有 CDN，请不要使用 ---
     */
    protected function _enabledXsrf() {
        // --- 设置 XSRF 值 ---
        if (!isset($_COOKIE['XSRF-TOKEN'])) {
            $xsrf = Core::random(16, Core::RANDOM_LUN);
            $this->_xsrf = $xsrf;
            setcookie('XSRF-TOKEN', $xsrf, [
                'expires' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true
            ]);
            $_COOKIE['XSRF-TOKEN'] = $xsrf;
        }
        else {
            $this->_xsrf = $_COOKIE['XSRF-TOKEN'];
        }
    }

    /**
     * --- 获取 Auth 字符串，用于客户端提交 ---
     * @param string $user 用户名
     * @param string $pwd 密码
     * @return string
     */
    protected function _getBasicAuth(string $user, string $pwd): string {
        return 'Basic ' . base64_encode($user . ':' . $pwd);
    }

    /** @var array --- auth 对象，user, pwd --- */
    private $_authorization = null;

    /**
     * --- 通过 header 或 _auth 获取鉴权信息或 JWT 信息（不解析） ---
     * @return array|false|string user, pwd
     */
    public function getAuthorization() {
        if ($this->_authorization !== null) {
            return $this->_authorization;
        }
        $auth = '';
        if (isset($this->_headers['authorization']) && $this->_headers['authorization']) {
            $auth = $this->_headers['authorization'];
        }
        else if (isset($this->_get['_auth'])) {
            $auth = $this->_get['_auth'];
        }
        else if (isset($this->_post['_auth'])) {
            $auth = $this->_post['_auth'];
        }
        $authArr = explode(' ', $auth);
        if (!isset($authArr[1])) {
            return false;
        }
        if (strpos($authArr[1], '.') !== false) {
            // --- 不解析，解析使用 JWT 类解析 ---
            return $authArr[1];
        }
        if (!($auth = base64_decode($authArr[1]))) {
            return false;
        }
        $authArr = explode(':', $auth);
        $this->_authorization = ['user' => $authArr[0], 'pwd' => isset($authArr[1]) ? $authArr[1] : ''];
        return $this->_authorization;
    }

    /**
     * --- 获取 data 数据 ---
     * @param string $path 文件路径（不含扩展名）
     * @return mixed|null
     */
    protected function _loadData(string $path) {
        if ($f = file_get_contents(DATA_PATH . $path . '.json')) {
            return json_decode($f, true);
        }
        else {
            return null;
        }
    }

    /**
     * --- 跳转（302临时跳转），支持相对本项目根路径的路径或绝对路径 ---
     * @param string $location 相对或绝对网址
     * @return false
     */
    protected function _location(string $location) {
        http_response_code(302);
        header('location: ' . Text::urlResolve(URL_BASE, $location));
        return false;
    }

    /**
     * --- 设置当前时区，Mutton: true, Kabeb: false ---
     * @param string $timezone_identifier
     */
    protected function _setTimezone(string $timezone_identifier): void {
        date_default_timezone_set($timezone_identifier);
    }

    /**
     * --- 开启 Session ---
     * @param IKv|Db $link Kv 或 Db 实例
     * @param bool $auth 设为 true 则从头 Authorization 或 post _auth 值读取 token
     * @param array $opt name, ttl, ssl, sqlPre
     */
    protected function _startSession($link, bool $auth = false, array $opt = []): void {
        $this->_sess = new Session($this, $link, $auth, $opt);
    }

    // --- 本地化 ---

    /**
     * --- 设定语言加载语言包 ---
     * @param string $loc 要加载的目标语言
     * @param string $pkg 包名，为空自动填充为 default
     * @return bool
     */
    protected function _loadLocale(string $loc, string $pkg = 'default'): bool {
        global $_localeData, $_localeFiles, $_locale;

        /** @var string $lName 语言名.包名 */
        $lName = $loc . '.' . $pkg;
        if (!in_array($lName, $_localeFiles)) {
            if (($locData = $this->_loadData('locale/' . $lName)) === null) {
                return false;
            }
            $_locale = $loc;
            if (!isset($_localeData[$loc])) {
                $_localeData[$loc] = [];
            }
            $this->_loadLocaleDeep($locData);
            $_localeFiles[] = $lName;
        }
        else {
            $_locale = $loc;
        }
        return true;
    }
    private function _loadLocaleDeep(array $locData, string $pre = '') {
        global $_localeData, $_locale;

        foreach ($locData as $k => $v) {
            if (is_array($v)) {
                $this->_loadLocaleDeep($v, $pre . $k . '.');
            }
            else {
                $_localeData[$_locale][$pre . $k] = $v;
            }
        }
    }

    /**
     * --- 根据当前后台语言包设置情况获取 JSON 字符串传输到前台 ---
     * @return string
     */
    protected function _getLocaleJsonString(): string {
        global $_localeData, $_locale;

        if (isset($_localeData[$_locale])) {
            return json_encode($_localeData[$_locale]);
        }
        else {
            return '{}';
        }
    }

    /**
     * --- 获取当前语言名 ---
     * @return string
     */
    protected function _getLocale(): string {
        global $_locale;
        return $_locale;
    }

    /**
     * --- 开启跨域请求 ---
     * 返回 true 接续执行，返回 false 需要中断用户本次访问（options请求）
     */
    protected function _cross(): bool {
        header('access-control-allow-origin: *');
        header('access-control-allow-headers: *');
        header('access-control-allow-methods: *');
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('access-control-max-age: 3600');
            return false;
        }
        return true;
    }

    /**
     * --- 显示 PHP 错误到页面, Mutton: true, Kebab: false ---
     */
    protected function _displayErrors(): void {
        ini_set('display_errors', 'On');
        error_reporting(E_ALL);
    }

}

