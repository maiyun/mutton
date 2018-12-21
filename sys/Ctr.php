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

        // --- 重构 loadView(string $path, boolen $return) ---
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
    protected function loadData(string $path): object {
        return json_decode(file_get_contents(DATA_PATH . $path . '.json'));
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

    // --- HTTP 方法 ---

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

}

