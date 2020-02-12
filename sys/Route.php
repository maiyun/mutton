<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2018-6-17 23:29
 * Last: 2020-1-17 01:09:39
 */
declare(strict_types = 1);

namespace sys;

require ETC_PATH.'route.php';

class Route {

    public static function run(): void {
        // --- URI 是安全的，不会是 ../../ 来访问到了外层，Apache Nginx 都会做处理的 ---
        $path = URI;
        // --- 如果为空则定义为 @ ---
        if ($path === '') {
            $path = "@";
        }
        // --- 检查路由表 ---
        $param = [];
        $match = null;
        $pathLeft = ''; $pathRight = '';
        foreach (ROUTE as $rule => $ruleVal) {
            $rule = str_replace('/', '\\/', $rule);
            preg_match('/^' . $rule . '$/', $path, $match);
            if (!empty($match)) {
                list($pathLeft, $pathRight) = self::_getPathLeftRight($ruleVal);
                $param = $match;
                array_splice($param, 0, 1);
                break;
            }
        }
        if (!$match) {
            list($pathLeft, $pathRight) = self::_getPathLeftRight(URI);
        }
        // --- 加载控制器 ---
        $ctr = '\\ctr\\' . str_replace('/', '\\', $pathLeft);
        $filePath = CTR_PATH . $pathLeft . '.php';
        if (!is_file($filePath)) {
            // --- 指定的控制器不存在 ---
            http_response_code(404);
            echo '[Error] Controller not found.';
            return;
        }
        require SYS_PATH . 'Ctr.php';
        // --- 加载控制文件 ---
        require $filePath;
        // --- 判断 action 是否存在 ---
        /** @var Ctr $ctr */
        $ctr = new $ctr();
        // --- 强制 HTTPS ---
        if (MUST_HTTPS && !$ctr->_mustHttps()) {
            return;
        }
        // --- 检测 action 是否存在 ---
        $pathRight = preg_replace_callback('/-([a-zA-Z0-9])/', function ($matches) {
            return strtoupper($matches[1]);
        }, $pathRight);
        if (!method_exists($ctr, $pathRight)) {
            http_response_code(404);
            echo '[Error] Action not found.';
            return;
        }
        // --- 对信息进行初始化 ---
        // --- 路由定义的参数序列 ---
        $ctr->_param = $param;
        // --- action 名 ---
        $ctr->_action = $pathRight;
        // --- 原始 POST ---
        $ctr->_rawPost = $_POST;
        // --- 原始 GET ---
        $ctr->_get = $_GET;
        // --- 处理 headers ---
        foreach ($_SERVER as $key => $val) {
            if ($key === 'CONTENT_TYPE') {
                $ctr->_headers['content-type'] = $val;
                continue;
            }
            if (substr($key, 0, 5) !== 'HTTP_') {
                continue;
            }
            $ctr->_headers[str_replace('_', '-', strtolower(substr($key, 5)))] = $val;
        }
        // --- 处理 POST 的值 JSON 或 FILE ---
        $contentType = isset($ctr->_headers['content-type']) ? strtolower($ctr->_headers['content-type']) : '';
        if (strpos($contentType, 'json') !== false) {
            // --- POST 的数据是 JSON ---
            $_POST = file_get_contents('php://input');
            if(($_POST = json_decode($_POST, true)) === false) {
                $_POST = [];
            }
        } else if (strpos($contentType, 'form-data') !== false) {
            // --- 上传文件简单处理 ---
            foreach ($_FILES as $key => $val) {
                if (is_string($_FILES[$key]['name'])) {
                    continue;
                }
                $files = [];
                foreach ($_FILES[$key]['name'] as $k => $v) {
                    $files[$k] = [
                        'name' => $_FILES[$key]['name'][$k],
                        'type' => $_FILES[$key]['type'][$k],
                        'tmp_name' => $_FILES[$key]['tmp_name'][$k],
                        'error' => $_FILES[$key]['error'][$k],
                        'size' => $_FILES[$key]['size'][$k]
                    ];
                }
                $_FILES[$key] = $files;
            }
            $ctr->_files = $_FILES;
        }
        // --- 格式化 post 数据 ---
        self::_trimPost($_POST);
        $ctr->_post = $_POST;
        // --- 检测是否有 onLoad，有则优先执行一下 ---
        if (method_exists($ctr, '_load')) {
            $rtn = $ctr->_load();
        }
        if (!isset($rtn)) {
            // --- 执行 action ---
            $rtn = $ctr->$pathRight();
        }
        // --- 在返回值输出之前，设置缓存 ---
        if ($ctr->_cacheTTL > 0) {
            header('Expires: ' . gmdate('D, d M Y H:i:s', $_SERVER['REQUEST_TIME'] + $ctr->_cacheTTL) . ' GMT');
            header('Cache-Control: max-age=' . $ctr->_cacheTTL);
        } else {
            header('Expires: Mon, 26 Jul 1994 05:00:00 GMT');
            header('Cache-Control: no-store');
        }
        // --- 判断返回值 ---
        if (!isset($rtn)) {
            return;
        }
        if (is_string($rtn)) {
            // --- 返回的是纯字符串，直接输出 ---
            echo $rtn;
        } else if (is_array($rtn)) {
            // --- 返回的是数组，那么代表是 JSON，以 JSON 形式输出 ---
            // 别用 JSON_UNESCAPED_UNICODE 啊，Android 可能解不了
            header('Content-type: application/json; charset=utf-8');
            if (isset($rtn[0]) && is_int($rtn[0])) {
                $json = ['result' => $rtn[0]];
                if (isset($rtn[1])) {
                    if (is_array($rtn[1])) {
                        echo json_encode(array_merge($json, $rtn[1]));
                    } else {
                        if (count($rtn) == 2) {
                            $json['msg'] = $rtn[1];
                            echo json_encode($json);
                        } else {
                            echo '[Error] Return value is wrong.';
                        }
                    }
                } else {
                    unset($rtn[0]);
                    echo json_encode(array_merge($json, $rtn));
                }
            } else {
                echo json_encode($rtn);
            }
        } else {
            echo '[Error] Return type is wrong.';
        }

    }

    /**
     * --- 获取控制器 left 和 action ---
     * @param string $path 相对路径
     * @return array
     */
    private static function _getPathLeftRight($path) {
        $pathLio = strrpos($path, '/');
        if ($pathLio === false) {
            return [$path, 'index'];
        } else {
            return [substr($path, 0, $pathLio), substr($path, $pathLio + 1)];
        }
    }

    /**
     * --- 将 POST 数据的值执行 trim ---
     * @param $post
     */
    private static function _trimPost(&$post) {
        foreach ($post as $key => $val) {
            if (is_string($val)) {
                $post[$key] = trim($val);
            } else if (is_array($val)) {
                self::_trimPost($post[$key]);
            }
        }
    }

}

