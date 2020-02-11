<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2018-6-17 23:29
 * Last: 2020-1-17 01:05:14
 */
declare(strict_types = 1);

namespace sys;

use lib\Text;

class Ctr {

    /** @var array 路由参数序列数组 */
    public $param = [];
    /** @var string 当前的 action 名 */
    public $action = '';
    /** @var array 原始 POST 数据 */
    public $rawPost = [];
    /** @var int 页面浏览器客户端缓存 */
    public $cacheTTL = CACHE_TTL;

    /**
     * --- 获取截止当前时间的总运行时间 ---
     * @param bool $ms
     * @return float
     */
    protected function getRunTime(bool $ms = false): float {
        $t = microtime(true) - START_TIME;
        return $ms ? $t * 1000 : $t;
    }

    /**
     * --- 获取截止当前内存的使用情况 ---
     * @return int
     */
    protected function getMemoryUsage(): int {
        return memory_get_usage() - START_MEMORY;
    }

    /**
     * --- 加载视图 ---
     * @param string $path
     * @param array $data
     * @return string
     */
    protected function loadView(string $path, $data = []) {
        // --- 重构 loadView(string $path, bool $return) ---
        extract($data);
        ob_start();
        require VIEW_PATH . $path . '.php';
        return ob_get_clean();
    }

    /**
     * --- 获取页面内容方法 ---
     */
    protected function obStart(): void {
        ob_start();
    }
    protected function obEnd(): string {
        return ob_get_clean();
    }

    /**
     * --- 检测提交的数据类型 ---
     * @param array $input 要校验的输入项
     * @param array $rule
     * @param array $return 返回值
     * @return bool
     */
    protected function checkInput(array &$input, array $rule, &$return) {
        // --- 遍历规则 ---
        // --- $input, ['xx' => ['require', '> 6', [0, 'xx 必须大于 6']], 'yy' => []], $return ---
        foreach ($rule as $key => $val) {
            // --- $key 就是上面的 xx ---
            if (!isset($input[$key])) {
                // --- 原值不存在则设定为空 ---
                $input[$key] = '';
            }
            // --- ['require', '> 6', [0, 'xx 必须大于 6']] ---
            $lastK = count($val) - 1;
            for ($k = 0; $k <= $lastK; ++$k) {
                if ($k === $lastK) {
                    break;
                }
                $v = $val[$k];
                if (is_array($v)) {
                    if ($input[$key] !== '' && !in_array($input[$key], $v)) {
                        $return = $val[$lastK];
                        return false;
                    }
                } else {
                    switch ($v) {
                        case 'require': {
                            if ($input[$key] == '') {
                                $return = $val[$lastK];
                                return false;
                            }
                            break;
                        }
                        case 'num':
                        case 'number': {
                            if ($input[$key] !== '' && !is_numeric($input[$key])) {
                                $return = $val[$lastK];
                                return false;
                            }
                            break;
                        }
                        default: {
                            if ($input[$key] !== '') {
                                if ($v[0] === '/') {
                                    // --- 正则 ---
                                    if (!preg_match($v, $input[$key])) {
                                        $return = $val[$lastK];
                                        return false;
                                    }
                                } else if (preg_match('/^([><=]+) *([0-9]+)$/', $input[$key], $match)) {
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
                                        $return = $val[$lastK];
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
     * --- 获取 data 数据 ---
     * @param string $path
     * @return mixed|null
     */
    protected function loadData(string $path) {
        if ($f = file_get_contents(DATA_PATH . $path . '.json')) {
            return json_decode($f);
        } else {
            return null;
        }
    }

    /**
     * --- 强制 https 下访问 ---
     * @return bool
     */
    public function mustHttps(): bool {
        if (HTTPS) {
            return true;
        } else {
            $redirect = 'https://' . HOST . $_SERVER['REQUEST_URI'];
            http_response_code(301);
            header('Location: ' . $redirect);
            return false;
        }
    }

    /**
     * --- 跳转（302临时跳转），支持相对和绝对路径 ---
     * @param string $url
     */
    protected function location(string $url): void {
        http_response_code(302);
        header('Location: '.Text::urlResolve(URL_BASE, $url));
    }

    /**
     * --- 设置当前时区 ---
     * @param string $timezone_identifier
     */
    protected function setTimezone(string $timezone_identifier): void{
        date_default_timezone_set($timezone_identifier);
    }

    // --- 国际化 ---

    /**
     * --- 根据当前设定语言加载语言包 ---
     * @param string $locale 要加载的目标语言
     * @param string $pkg 包名，为空自动填充为 default
     * @return bool
     */
    protected function loadLocale(string $locale, string $pkg = ''): bool {
        global $__LOCALE, $__LOCALE_OBJ, $__LOCALE_OVER;

        if ($pkg === '') {
            $pkg = "default";
        }
        /** @var string $lName 语言名.包名 */
        $lName = $locale . '.' . $pkg;
        if (!in_array($lName, $__LOCALE_OVER)) {
            if (($locData = $this->loadData('locale/'.$lName)) === false) {
                return false;
            }
            if (!isset($__LOCALE_OBJ[$locale])) {
                $__LOCALE_OBJ[$locale] = [];
            }
            $__LOCALE = $locale;
            $this->_loadLocaleDeep($locData);
            $__LOCALE_OVER[] = $lName;
        } else {
            $__LOCALE = $locale;
        }
        return true;
    }
    private function _loadLocaleDeep(array $locData, string $pre = '') {
        global $__LOCALE, $__LOCALE_OBJ;

        foreach ($locData as $k => $v) {
            if (is_array($v)) {
                $this->_loadLocaleDeep($v, $pre . $k . '.');
            } else {
                $__LOCALE_OBJ[$__LOCALE][$pre . $k] = $v;
            }
        }
    }

    /**
     * --- 获取当前语言名 ---
     * @return string
     */
    protected function getLocale(): string {
        global $__LOCALE;
        return $__LOCALE;
    }

}

