<?php
declare(strict_types = 1);

namespace sys;

class Ctr {

    public $param = [];
    public $action = '';

    // --- 获取 POST 内容并解析为对象，POST 内容必须为 JSON ---
    protected function getJSONByPost(): object {
        $post = file_get_contents('php://input');
        if(($post = json_decode($post)) !== false) {
            return $post;
        } else {
            return json_decode('{}');
        }
    }

    // --- 获取截止当前时间的总运行时间 ---
    protected function getRunTime(bool $ms = false): float {
        $t = microtime(true) - START_TIME;
        return $ms ? $t * 1000 : $t;
    }

    // --- 获取截止当前内存的使用情况 ---
    protected function getMemoryUsage(): int {
        return memory_get_usage() - START_MEMORY;
    }

    // --- 加载视图 ---

    /**
     * @param string $path
     * @param array|bool $data
     * @param bool $return
     * @return string
     */
    protected function loadView(string $path, $data = [], bool $return = false) {
        // --- 重构 loadView(string $path, bool $return) ---
        if(is_array($data)) {
            extract($data);
        } else {
            $return = $data;
        }

        if($return) {
            ob_start();
        }

        require VIEW_PATH . $path . '.php';

        if ($return) {
            return ob_get_clean();
        } else {
            return '';
        }
    }

    // --- 获取页面内容方法 ---
    protected function obStart(): void {
        ob_start();
    }
    protected function obEnd(): string {
        return ob_get_clean();
    }

    // --- 获取 json 数据 ---
    protected function loadData(string $path, $assoc = false) {
        if ($f = file_get_contents(DATA_PATH . $path . '.json')) {
            return json_decode($f, $assoc);
        } else {
            return false;
        }
    }

    // --- 必须使用 https 访问 ---
    public function mustHttps(): bool {
        if (HTTPS) {
            return true;
        } else {
            $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . $redirect);
            return false;
        }
    }

    // --- 获取 GET 和 POST 的数据 ---

    /**
     * @param $key
     * @return string
     */
    protected function get(string $key) {
        return isset($_GET[$key]) ? trim($_GET[$key]) : '';
    }

    /**
     * @param $key
     * @return string
     */
    protected function post(string $key) {
        return isset($_POST[$key]) ? trim($_POST[$key]) : '';
    }

    // --- 跳转 ---
    protected function location(string $url): void {
        header('Location: '.$url);
    }
    protected function redirect(string $url = ''): void {
        header('Location: '.HTTP_BASE.$url);
    }

    /**
     * --- 深度创建文件夹并赋予权限，失败不会回滚 ---
     * @param string $path
     * @param int $mode
     * @return bool
     */
    protected function mkdir(string $path, int $mode = 0755): bool {
        $path = str_replace('\\', '/', $path);
        $dirs = explode('/', $path);
        $tpath = '';
        foreach ($dirs as $v) {
            if ($v === '') {
                continue;
            }
            $tpath .= $v . '/';
            if (!is_dir($tpath)) {
                if (!mkdir($tpath)) {
                    return false;
                }
                chmod($tpath, $mode);
            }
        }
        return true;
    }

    /**
     * --- 深度删除文件夹以及所有文件 ---
     * @param string $path
     * @return bool
     */
    protected function rmdir(string $path): bool {
        $path = str_replace('\\', '/', $path);
        if (substr($path, -1) !== '/') {
            $path .= '/';
        }
        if (!file_exists($path)) {
            return true;
        }
        $dir = dir($path);
        while (($name = $dir->read()) !== false) {
            if (($name === '.') || ($name === '..')) {
                continue;
            }
            if (is_file($path.$name)) {
                if (!@unlink($path.$name)) {
                    return false;
                }
            } else {
                if (!$this->rmdir($path.$name.'/')) {
                    return false;
                }
            }
        }
        $dir->close();
        return @rmdir($path);
    }

    /**
     * --- 检验文件或文件夹是否可写 ---
     * @param string $path
     * @return bool
     */
    protected function isWritable(string $path): bool {
        // If we're on a Unix server with safe_mode off we call is_writable
        if (DIRECTORY_SEPARATOR == '/' AND @ini_get("safe_mode") == false) {
            return is_writable($path);
        }
        // For windows servers and safe_mode "on" installations we'll actually
        // write a file then read it. Bah...
        if (is_dir($path)) {
            $file = rtrim($path, '/') . '/' . md5(mt_rand(1, 100).mt_rand(1, 100));
            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }
            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return true;
        } elseif (!is_file($path) or ($fp = @fopen($path, 'ab')) === false) {
            return false;
        }
        fclose($fp);
        return true;
    }

    // --- 国际化 ---
    private $_localePkg = [];

    /**
     * --- 根据当前设定语言加载语言包 ---
     * @param string $locale 要加载的目标语言
     * @param string $pkg 包名，为空自动填充为 default
     * @return bool
     */
    protected function setLocale(string $locale, string $pkg = ''): bool {
        global $__LOCALE, $__LOCALE_OBJ;

        if ($pkg === '') {
            $pkg = "default";
        }
        $lName = $locale . '.' . $pkg;
        if (!in_array($lName, $this->_localePkg)) {
            if (($loc = $this->loadData('locale/'.$lName, true)) === false) {
                return false;
            }
            if (!isset($__LOCALE_OBJ[$locale])) {
                $__LOCALE_OBJ[$locale] = [];
            }

            $__LOCALE_OBJ[$locale] = array_merge($__LOCALE_OBJ[$locale], $this->_setLocaleDeep($loc));

            $this->_localePkg[] = $lName;
        }
        $__LOCALE = $locale;
        return true;
    }
    private function _setLocaleDeep(array $loc, string $pre = '') {
        $arr = [];
        foreach ($loc as $k => $v) {
            if (is_array($v)) {
                $arr = array_merge($arr, $this->_setLocaleDeep($v, $pre . $k . '.'));
            } else {
                $arr[$pre . $k] = $v;
            }
        }
        return $arr;
    }

    /**
     * --- 获取当前 i18n 语言字符串 ---
     * @return string
     */
    protected function getLocale(): string {
        global $__LOCALE;
        return $__LOCALE;
    }

}

