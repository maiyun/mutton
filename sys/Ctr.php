<?php
declare(strict_types = 1);

namespace M {

    class Ctr {

        public $param = [];

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
            if ($this->isHttps()) {
                return true;
            } else {
                $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                header('HTTP/1.1 301 Moved Permanently');
                header('Location: ' . $redirect);
                return false;
            }
        }

        // --- 判断当前是否是 https ---
        protected function isHttps(): bool {
            if (isset($_SERVER['HTTPS'])) {
                if ($_SERVER['HTTPS'] === 1) {  //Apache
                    return true;
                } else if ($_SERVER['HTTPS'] === 'on') { //IIS
                    return true;
                }
            } else if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] === 443) { //其他
                return true;
            }
            return false;
        }

    }

}

