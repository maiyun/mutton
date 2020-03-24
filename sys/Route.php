<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2018-6-17 23:29
 * Last: 2020-1-17 01:09:39, 2020-3-22 19:31:51
 */
declare(strict_types = 1);

namespace sys;

use ctr\middle;

require ETC_PATH.'route.php';

class Route {

    public static function run(): void {
        $time = time();
        // --- PATH 是安全的，不会是 ../../ 来访问到了外层，Apache Nginx 都会做处理的（已经通过模拟请求验证） ---
        $path = PATH;
        // --- 如果为空则定义为 @ ---
        if ($path === '') {
            $path = "@";
        }
        // --- 检查路由表 ---
        $param = [];
        $match = null;
        $pathLeft = ''; $pathRight = '';
        foreach (ROUTE as $rule => $ruleVal) {
            preg_match('/^' . $rule . '$/', $path, $match);
            if (!empty($match)) {
                list($pathLeft, $pathRight) = self::_getPathLeftRight($ruleVal);
                $param = $match;
                array_splice($param, 0, 1);
                break;
            }
        }
        if (!$match) {
            list($pathLeft, $pathRight) = self::_getPathLeftRight(PATH);
        }
        // --- 若文件名为保留的 middle 将不允许进行 ---
        if (substr($pathLeft, -6) === 'middle') {
            http_response_code(404);
            echo '[Error] Controller not found, path: ' . PATH . '.';
            return;
        }
        // --- 加载中间控制器 ---
        require SYS_PATH . 'Ctr.php';
        require CTR_PATH . 'middle.php';
        /** @var Ctr $middle */
        $middle = new middle();
        // --- 对信息进行初始化 ---
        // --- 路由定义的参数序列 ---
        $middle->_param = $param;
        // --- action 名 ---
        $middle->_action = $pathRight;
        // --- 处理 headers ---
        foreach ($_SERVER as $key => $val) {
            if ($key === 'CONTENT_TYPE') {
                $middle->_headers['content-type'] = $val;
                continue;
            }
            if (substr($key, 0, 5) !== 'HTTP_') {
                continue;
            }
            $middle->_headers[str_replace('_', '-', strtolower(substr($key, 5)))] = $val;
        }
        if (!isset($middle->_headers['authorization'])) {
            $middle->_headers['authorization'] = '';
        }

        // --- 原始 GET ---
        $middle->_get = &$_GET;
        // --- 原始 POST ---
        $middle->_rawPost = $_POST;
        // --- 处理 POST 的值 JSON 或 FILE ---
        $contentType = isset($middle->_headers['content-type']) ? strtolower($middle->_headers['content-type']) : '';
        if (strpos($contentType, 'json') !== false) {
            // --- POST 的数据是 JSON ---
            $_POST = file_get_contents('php://input');
            if (($_POST = json_decode($_POST, true)) === false) {
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
            $middle->_files = &$_FILES;
        }
        // --- 格式化 post 数据 ---
        self::_trimPost($_POST);
        $middle->_post = &$_POST;

        // --- Cookie ---
        $middle->_cookie = &$_COOKIE;
        // --- 设置 XSRF 值 ---
        if (!isset($_COOKIE['XSRF-TOKEN'])) {
            $middle->_xsrf = $middle->_random(16, Ctr::RANDOM_LUN);
            setcookie('XSRF-TOKEN', $middle->_xsrf, 0, '/', '', false, true);
            $_COOKIE['XSRF-TOKEN'] = $middle->_xsrf;
        } else {
            $middle->_xsrf = $_COOKIE['XSRF-TOKEN'];
        }

        // --- 执行中间件的 _load ---
        $rtn = $middle->_load();
        if (!isset($rtn) || $rtn === true) {
            // --- 只有不返回或返回 true 时才加载控制文件 ---
            // --- 判断真实控制器文件是否存在 ---
            $filePath = CTR_PATH . $pathLeft . '.php';
            if (!is_file($filePath)) {
                // --- 指定的控制器不存在 ---
                http_response_code(404);
                echo '[Error] Controller not found.';
                return;
            }
            // --- 加载控制器文件 ---
            require $filePath;
            // --- 判断 action 是否存在 ---
            $ctrName = '\\ctr\\' . str_replace('/', '\\', $pathLeft);
            /** @var Ctr $ctr */
            $ctr = new $ctrName();
            // --- 强制 HTTPS ---
            if (MUST_HTTPS && !$ctr->_mustHttps()) {
                return;
            }
            // --- 检测 action 是否存在 ---
            if ($pathRight[0] === '_') {
                // --- _ 开头的 action 是内部方法，不允许访问 ---
                http_response_code(404);
                echo '[Error] Action not found, path: ' . PATH . '.';
                return;
            }
            $pathRight = preg_replace_callback('/-([a-zA-Z0-9])/', function ($matches) {
                return strtoupper($matches[1]);
            }, $pathRight);
            if (!method_exists($ctr, $pathRight)) {
                http_response_code(404);
                echo '[Error] Action not found, path: ' . PATH . '.';
                return;
            }
            // --- 对信息进行初始化 ---
            // --- 路由定义的参数序列 ---
            $ctr->_param = &$middle->_param;
            $ctr->_action = $middle->_action;
            $ctr->_headers = &$middle->_headers;

            $ctr->_get = &$middle->_get;
            $ctr->_post = &$middle->_post;
            $ctr->_rawPost = &$middle->_rawPost;
            $ctr->_files = &$middle->_files;

            $ctr->_cookie = &$middle->_cookie;
            $ctr->_session = &$middle->_session;

            $ctr->_cacheTTL = $middle->_cacheTTL;
            $ctr->_xsrf = $middle->_xsrf;
            // --- 检测是否有 onLoad，有则优先执行一下 ---
            if (method_exists($ctr, '_load')) {
                $rtn = $ctr->_load();
            }
            // --- 执行 action ---
            if (!isset($rtn) || $rtn === true) {
                $rtn = $ctr->$pathRight();
            }
            // --- 在返回值输出之前，设置缓存 ---
            if ($ctr->_cacheTTL > 0) {
                header('expires: ' . gmdate('D, d M Y H:i:s', $time + $ctr->_cacheTTL) . ' GMT');
                header('cache-control: max-age=' . $ctr->_cacheTTL);
            } else {
                header('expires: Mon, 26 Jul 1994 05:00:00 GMT');
                header('cache-control: no-store');
            }
        }
        // --- 判断返回值 ---
        if (!isset($rtn) || is_bool($rtn) || $rtn === null) {
            return;
        }
        if (is_string($rtn)) {
            // --- 返回的是纯字符串，直接输出 ---
            echo $rtn;
        } else if (is_array($rtn)) {
            // --- 返回的是数组，那么代表是 JSON，以 JSON 形式输出 ---
            // 别用 JSON_UNESCAPED_UNICODE 啊，Android 可能解不了
            header('content-type: application/json; charset=utf-8');
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
            $right = substr($path, $pathLio + 1);
            return [substr($path, 0, $pathLio), $right === '' ? 'index' : $right];
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

