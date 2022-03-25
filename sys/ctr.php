<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2018-6-17 23:29
 * Last: 2020-1-17 01:05:14, 2020-2-12 13:02:35, 2020-3-30 15:31:48, 2022-3-17 15:29:33
 */
declare(strict_types = 1);

namespace sys;

use lib\Db;
use lib\Kv\IKv;
use lib\Session;
use lib\Text;

class Ctr {

    /** @var array 路由参数序列数组 */
    public $_param = [];
    /** @var string 当前的 action 名 */
    public $_action = '';
    /** @var array 请求的 header 列表 */
    public $_headers = [];

    /** @var array GET 数据 */
    public $_get;
    /** @var array 原始 POST 数据 */
    public $_rawPost;
    /** @var array POST 数据 */
    public $_post;
    /** @var string 原始 input 字符串 */
    public $_input;
    /** @var array 上传的文件列表 */
    public $_files = [];

    /** @var array Cookie 数组 */
    public $_cookie;
    /** @var array Session 数组 */
    public $_session = [];
    /** @var Session Session|null 对象 */
    public $_sess = null;

    /** @var int 页面浏览器客户端缓存 */
    public $_cacheTTL = CACHE_TTL;
    /** @var string XSRF TOKEN 值 */
    public $_xsrf = '';

    /**
     * --- 实例化后会执行的方法 ---
     * @return bool|array|string|null|void
     */
    public function _load() {
        return true;
    }

    /**
     * --- 获取截止当前时间的总运行时间 ---
     * @param bool $ms 为 true 为毫秒，否则为秒
     * @return float
     */
    public function _getRunTime(bool $ms = false): float {
        $t = microtime(true) - START_TIME;
        return $ms ? $t * 1000 : $t;
    }

    /**
     * --- 获取截止当前内存的使用情况 ---
     * @return int
     */
    public function _getMemoryUsage(): int {
        return memory_get_usage() - START_MEMORY;
    }

    /**
     * --- 加载视图 ---
     * @param string $path_mtmp
     * @param array $data
     * @return string
     */
    public function _loadView(string $path_mtmp, $data = []) {
        $data['_staticVer'] = STATIC_VER;
        $data['_staticPath'] = STATIC_PATH;
        extract($data);
        ob_start();
        require VIEW_PATH . $path_mtmp . '.php';
        return ob_get_clean();
    }

    /**
     * --- 获取页面内容方法 ---
     */
    public function _obStart(): void {
        ob_start();
    }
    public function _obEnd(): string {
        return ob_get_clean();
    }

    /**
     * --- 检测提交的数据类型 ---
     * @param array $input 要校验的输入项
     * @param array $rule 规则
     * @param array $rtn 返回值
     * @return bool
     */
    public function _checkInput(array &$input, array $rule, &$rtn) {
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
                $v = $val[$k];
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
                            if ($input[$key] === null) {
                                $rtn = $val[$lastK];
                                return false;
                            }
                            break;
                        }
                        case 'num':
                        case 'number': {
                            if ($input[$key] !== null && !is_numeric($input[$key])) {
                                $rtn = $val[$lastK];
                                return false;
                            }
                            break;
                        }
                        default: {
                            if ($input[$key] !== null) {
                                if ($v[0] === '/') {
                                    // --- 正则 ---
                                    if (!preg_match($v, $input[$key])) {
                                        $rtn = $val[$lastK];
                                        return false;
                                    }
                                }
                                else if (preg_match('/^([><=]+) *([0-9]+)$/', $input[$key], $match)) {
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
    public function _checkXInput(array &$input, array $rule, &$rtn) {
        if (!isset($rule['_xsrf'])) {
            $rule['_xsrf'] = ['require', $this->_cookie['XSRF-TOKEN'], [0, 'Bad request, no permission.']];
        }
        return $this->_checkInput($input, $rule, $rtn);
    }

    /**
     * --- 获取 Auth 字符串，用于客户端提交 ---
     * @param string $user 用户名
     * @param string $pwd 密码
     * @return string
     */
    public function _getBasicAuth(string $user, string $pwd): string {
        return 'Basic ' . base64_encode($user . ':' . $pwd);
    }

    /** @var array --- auth 对象，user, pwd --- */
    private $_authorization = null;

    /**
     * --- 通过 header 或 _auth 获取鉴权信息 ---
     * @return array|false user, pwd
     */
    public function _getAuthorization() {
        if ($this->_authorization !== null) {
            return $this->_authorization;
        }
        $auth = '';
        if (isset($this->_headers['authorization']) && $this->_headers['authorization']) {
            $auth = $this->_headers['authorization'];
        } else if (isset($this->_post['_auth'])) {
            $auth = $this->_post['_auth'];
        }
        $authArr = explode(' ', $auth);
        if (!isset($authArr[1])) {
            return false;
        }
        if (!($auth = base64_decode($authArr[1]))) {
            return false;
        }
        $authArr = explode(':', $auth);
        $this->_authorization = ['user' => $authArr[0], 'pwd' => $authArr[1]];
        return $this->_authorization;
    }

    /**
     * --- 获取 data 数据 ---
     * @param string $path 文件路径（不含扩展名）
     * @return mixed|null
     */
    public function _loadData(string $path) {
        if ($f = file_get_contents(DATA_PATH . $path . '.json')) {
            return json_decode($f, true);
        } else {
            return null;
        }
    }

    /**
     * --- 强制 https 下访问 ---
     * @return bool
     */
    public function _mustHttps(): bool {
        if (HTTPS) {
            return true;
        } else {
            $this->_location('https://' . HOST . $_SERVER['REQUEST_URI']);
            return false;
        }
    }

    /**
     * --- 跳转（302临时跳转），支持相对本项目根路径的路径或绝对路径 ---
     * @param string $location 相对或绝对网址
     * @return false
     */
    public function _location(string $location) {
        http_response_code(302);
        header('location: ' . Text::urlResolve(URL_BASE, $location));
        return false;
    }

    /**
     * --- 设置当前时区 ---
     * @param string $timezone_identifier
     */
    public function _setTimezone(string $timezone_identifier): void {
        date_default_timezone_set($timezone_identifier);
    }

    /**
     * --- 设置 cookie ---
     * @param string $name 名
     * @param string $value 值
     * @param array $opt 选项 ttl, path, domain, ssl, httponly
     */
    public  function _setCookie(string $name, string $value, array $opt = []): void {
        $ttl = !isset($opt['ttl']) ? 0 : $opt['ttl'];

        setcookie($name, $value, time() + $ttl, isset($opt['path']) ? $opt['path'] : "/", isset($opt['domain']) ? $opt['domain'] : "", isset($opt['ssl']) ? $opt['ssl'] : true, isset($opt['httponly']) ? $opt['httponly'] : true);
    }

    /**
     * --- 开启 Session ---
     * @param IKv|Db $link Kv 或 Db 实例
     * @param bool $auth 设为 true 则从头 Authorization 或 post _auth 值读取 token
     * @param array $opt name, ttl, ssl, sqlPre
     */
    public function _startSession($link, bool $auth = false, array $opt = []): void {
        $this->_sess = new Session($this, $link, $auth, $opt);
    }

    // --- 本地化 ---

    /**
     * --- 根据当前设定语言加载语言包 ---
     * @param string $loc 要加载的目标语言
     * @param string $pkg 包名，为空自动填充为 default
     * @return bool
     */
    public function _loadLocale(string $loc, string $pkg = 'default'): bool {
        global $_localeData, $_localeFiles, $_locale;

        /** @var string $lName 语言名.包名 */
        $lName = $loc . '.' . $pkg;
        if (!in_array($lName, $_localeFiles)) {
            if (($locData = $this->_loadData('locale/' . $lName)) === null) {
                return false;
            }
            if (!isset($_localeData[$loc])) {
                $_localeData[$loc] = [];
            }
            $_locale = $loc;
            $this->_loadLocaleDeep($locData);
            $_localeFiles[] = $lName;
        } else {
            $_local = $loc;
        }
        return true;
    }
    private function _loadLocaleDeep(array $locData, string $pre = '') {
        global $_localeData, $_locale;

        foreach ($locData as $k => $v) {
            if (is_array($v)) {
                $this->_loadLocaleDeep($v, $pre . $k . '.');
            } else {
                $_localeData[$_locale][$pre . $k] = $v;
            }
        }
    }

    /**
     * --- 根据当前后台语言包设置情况获取 JSON 字符串传输到前台 ---
     * @return string
     */
    public function _getLocaleJsonString(): string {
        global $_localeData, $_locale;

        if (isset($_localeData[$_locale])) {
            return json_encode($_localeData[$_locale]);
        } else {
            return '{}';
        }
    }

    /**
     * --- 获取当前语言名 ---
     * @return string
     */
    public function _getLocale(): string {
        global $_locale;
        return $_locale;
    }

    /**
     * --- 显示 PHP 错误到页面 ---
     */
    public function _displayErrors(): void {
        ini_set('display_errors', 'On');
        error_reporting(E_ALL);
    }

    // --- 随机 ---
    const RANDOM_N = '0123456789';
    const RANDOM_U = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const RANDOM_L = 'abcdefghijklmnopqrstuvwxyz';

    const RANDOM_UN = self::RANDOM_U . self::RANDOM_N;
    const RANDOM_LN = self::RANDOM_L . self::RANDOM_N;
    const RANDOM_LU = self::RANDOM_L . self::RANDOM_U;
    const RANDOM_LUN = self::RANDOM_L . self::RANDOM_U . self::RANDOM_N;
    const RANDOM_V = 'ACEFGHJKLMNPRSTWXY34567';
    const RANDOM_LUNS = self::RANDOM_LUN . '()`~!@#$%^&*-+=_|{}[]:;\'<>,.?/]';

    /**
     * --- 生成随机字符串 ---
     * @param int $length 长度
     * @param string $source 采样值
     * @return string
     */
    public function _random(int $length = 8, string $source = self::RANDOM_LN, string $block = ''): string {
        return self::_getRandom($length, $source, $block);
    }

    /**
     * --- 生成随机字符串 ---
     * @param int $length 长度
     * @param string $source 采样值
     * @return string
     */
    public static function _getRandom(int $length = 8, string $source = self::RANDOM_LN, string $block = ''): string {
        // --- 剔除 block 字符 ---
        $len = strlen($block);
        if ($len > 0) {
            for ($i = 0; $i < $len; ++$i) {
                $source = str_replace($block[$i], '', $source);
            }
        }
        $len = strlen($source);
        if ($len === 0) {
            return '';
        }
        $temp = '';
        for ($i = 0; $i < $length; ++$i) {
            $temp .= $source[rand(0, $len - 1)];
        }
        return $temp;
    }

    /**
     * --- 生成范围内的随机数，带小数点 ---
     * @param float $min 最小数
     * @param float $max 最大数
     * @param int $prec 保留几位小数
     * @return float
     */
    public function _rand(float $min, float $max, int $prec): float {
        return self::_getRand($min, $max, $prec);
    }

    /**
     * --- 生成范围内的随机数，带小数点 ---
     * @param float $min 最小数
     * @param float $max 最大数
     * @param float $prec 保留几位小数
     * @return float
     */
    public static function _getRand(float $min, float $max, int $prec): float {
        if ($prec < 0) {
            $prec = 0;
        }
        $p = pow(10, $prec);
        return rand((int)($min * $p), (int)($max * $p)) / $p;
    }

    /**
     * --- 获取 MUID ---
     * @param string $key 多样性混合 key，可留空
     * @return string
     */
    public function _muid($key = ''): string {
        return self::_getMuid($key);
    }

    /**
     * --- 获取 MUID ---
     * @param string $key 多样性混合 key，可留空
     * @return string
     */
    public static function _getMuid($key = ''): string {
        if ($key === '') {
            $key = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'muid');
        }
        $key = hash_hmac('md5', $key, 'muid');
        $date = explode('-', date('Y-m-d-H-i'));
        $y = base_convert($date[0], 10, 36); // --- 3 位数，从 1296 到 46655 年 ---
        $m = base_convert($date[1], 10, 36);
        $d = base_convert($date[2], 10, 36);
        $h = base_convert($date[3], 10, 36);
        $rand = self::_getRandom(10);
        $last = hash_hmac('md5', $rand, $key);
        // ---    1       1      1         1           3             3           4              1       1   ---
        return $rand[0] . $h . $rand[1] . $m . substr($rand, 2, 3) . $y . substr($last, 5, 4) . $d . $last[0];
    }

}

