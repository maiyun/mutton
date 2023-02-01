<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2018-6-17 23:29
 * Last: 2020-1-17 01:09:39, 2020-3-22 19:31:51, 2021-8-12 12:36:13, 2022-3-17 15:29:29, 2022-08-30 12:56:07, 2022-09-13 14:40:45, 2023-1-17 02:54:37
 */
declare(strict_types = 1);

namespace sys;

use ctr\middle;
use lib\Text;

require ETC_PATH.'route.php';

class Route {

    public static function run(): void {
        header('expires: Mon, 26 Jul 1994 05:00:00 GMT');
        header('cache-control: no-store');
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
            if (isset(ROUTE['#404'])) {
                http_response_code(302);
                header('location: ' . Text::urlResolve(URL_BASE, ROUTE['#404']));
                return;
            }
            http_response_code(404);
            echo '[Error] Controller not found, path: ' . PATH . '.';
            return;
        }
        // --- 加载中间控制器 ---
        require SYS_PATH . 'ctr.php';
        require CTR_PATH . 'middle.php';
        /** @var Ctr $middle */
        $middle = new Middle();
        // --- 对信息进行初始化 ---
        // --- 路由定义的参数序列 ---
        $middle->setPrototypeRef('_param', $param);
        // --- action 名 ---
        $middle->setPrototype('_action', $pathRight);
        // --- 处理 headers ---
        $headers = [];
        foreach ($_SERVER as $key => $val) {
            if ($key === 'CONTENT_TYPE') {
                $headers['content-type'] = $val;
                continue;
            }
            if (substr($key, 0, 5) !== 'HTTP_') {
                continue;
            }
            $headers[str_replace('_', '-', strtolower(substr($key, 5)))] = $val;
        }
        if (!isset($headers['authorization'])) {
            $headers['authorization'] = '';
        }
        $middle->setPrototypeRef('_headers', $headers);

        // --- 原始 GET ---
        $middle->setPrototypeRef('_get', $_GET);
        // --- 处理 POST 的值 JSON 或 FILE ---
        if (!isset($_POST) || !$_POST) {
            $_POST = [];
        }
        // --- 原始 POST ---
        $middle->setPrototype('_rawPost', $_POST);
        // --- 原始 input ---
        $input = file_get_contents('php://input');
        $middle->setPrototype('_input', $input);
        // --- 文件 ---
        if (!isset($_FILES) || !$_FILES) {
            $_FILES = [];
        }
        $contentType = isset($headers['content-type']) ? strtolower($headers['content-type']) : '';
        if (strpos($contentType, 'json') !== false) {
            // --- POST 的数据是 JSON ---
            $_POST = json_decode($input, true);
            if (!$_POST) {
                $_POST = [];
            }
        }
        else if (strpos($contentType, 'form-data') !== false) {
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
            $middle->setPrototypeRef('_files', $_FILES);
        }
        // --- 格式化 post 数据 ---
        self::_trimPost($_POST);
        $middle->setPrototypeRef('_post', $_POST);

        // --- Cookie ---
        $middle->setPrototypeRef('_cookie', $_COOKIE);

        // --- 执行中间控制器的 onLoad ---
        $rtn = $middle->onLoad();
        $cacheTTL = $middle->getPrototype('_cacheTTL');
        $httpCode = $middle->getPrototype('_httpCode');
        if (!isset($rtn) || $rtn === true) {
            // --- 只有不返回或返回 true 时才加载控制文件 ---
            // --- 判断真实控制器文件是否存在 ---
            $filePath = CTR_PATH . $pathLeft . '.php';
            if (!is_file($filePath)) {
                // --- 指定的控制器不存在 ---
                if (isset(ROUTE['#404'])) {
                    http_response_code(302);
                    header('location: ' . Text::urlResolve(URL_BASE, ROUTE['#404']));
                    return;
                }
                http_response_code(404);
                echo '[Error] Controller not found, path: ' . PATH . '.';
                return;
            }
            // --- 加载控制器文件 ---
            require $filePath;
            // --- 获取类名 ---
            $ctrName = '\\ctr\\' . str_replace('/', '\\', $pathLeft);
            $lio = strrpos($ctrName, '\\');
            if ($lio !== false) {
                $ctrName = substr($ctrName, 0, $lio + 1) . ucwords(substr($ctrName, $lio + 1));
            }
            /** @var Ctr $ctr */
            $ctr = new $ctrName();
            // --- 对信息进行初始化 ---
            // --- 路由定义的参数序列 ---
            $ctr->setPrototypeRef('_param', $param);
            $ctr->setPrototype('_action', $middle->getPrototype('_action'));
            $ctr->setPrototypeRef('_headers', $headers);

            $ctr->setPrototypeRef('_get', $_GET);
            $ctr->setPrototype('_rawPost', $middle->getPrototype('_rawPost'));
            $ctr->setPrototype('_input', $middle->getPrototype('_input'));
            $ctr->setPrototypeRef('_files', $_FILES);
            $ctr->setPrototypeRef('_post', $_POST);

            $ctr->setPrototypeRef('_cookie', $_COOKIE);
            $jwt = &$middle->getPrototype('_jwt');
            $ctr->setPrototypeRef('_jwt', $jwt);
            if (!$ctr->getPrototype('_sess') && $middle->getPrototype('_sess')) {
                $ctr->setPrototypeRef('_session', $_SESSION);
                $ctr->setPrototype('_sess', $middle->getPrototype('_sess'));
            }

            $ctr->setPrototype('_cacheTTL', $middle->getPrototype('_cacheTTL'));
            $ctr->setPrototype('_xsrf', $middle->getPrototype('_xsrf'));
            $ctr->setPrototype('_httpCode', $middle->getPrototype('_httpCode'));
            // --- 强制 HTTPS ---
            if (MUST_HTTPS && !HTTPS) {
                http_response_code(302);
                header('location: ' . 'https://' . HOST . $_SERVER['REQUEST_URI']);
                return;
            }
            // --- 检测 action 是否存在，以及排除内部方法 ---
            if ($pathRight[0] === '_' || $pathRight === 'onLoad' || $pathRight === 'setPrototype' || $pathRight === 'getPrototype' || $pathRight === 'getAuthorization') {
                // --- _ 开头的 action 是内部方法，不允许访问 ---
                if (isset(ROUTE['#404'])) {
                    http_response_code(302);
                    header('location: ' . Text::urlResolve(URL_BASE, ROUTE['#404']));
                    return;
                }
                http_response_code(404);
                echo '[Error] Action not found, path: ' . PATH . '.';
                return;
            }
            $pathRight = preg_replace_callback('/-([a-zA-Z0-9])/', function ($matches) {
                return strtoupper($matches[1]);
            }, $pathRight);
            if (!method_exists($ctr, $pathRight)) {
                if (isset(ROUTE['#404'])) {
                    http_response_code(302);
                    header('location: ' . Text::urlResolve(URL_BASE, ROUTE['#404']));
                    return;
                }
                http_response_code(404);
                echo '[Error] Action not found, path: ' . PATH . '.';
                return;
            }
            // --- 执行 onLoad 方法 ---
            $rtn = $ctr->onLoad();
            // --- 执行 action ---
            if (!isset($rtn) || $rtn === true) {
                $rtn = $ctr->$pathRight();
            }
            // --- 获取 ctr 设置的 cache 和 hcode ---
            $cacheTTL = $ctr->getPrototype('_cacheTTL');
            $httpCode = $ctr->getPrototype('_httpCode');
        }
        // --- 设置缓存 ---
        if ($cacheTTL > 0) {
            header('expires: ' . gmdate('D, d M Y H:i:s', $time + $cacheTTL) . ' GMT');
            header('cache-control: max-age=' . $cacheTTL);
        }
        // --- 设置自定义 hcode ---
        if ($httpCode > 0) {
            http_response_code($httpCode);
        }
        // --- 判断返回值 ---
        if (!isset($rtn) || is_bool($rtn) || $rtn === null) {
            return;
        }
        if (is_string($rtn)) {
            // --- 返回的是纯字符串，直接输出 ---
            echo $rtn;
        }
        else if (is_array($rtn)) {
            // --- 返回的是数组，那么代表是 JSON，以 JSON 形式输出 ---
            // 别用 JSON_UNESCAPED_UNICODE 啊，Android 可能解不了
            header('content-type: application/json; charset=utf-8');
            if (isset($rtn[0]) && is_int($rtn[0])) {
                // --- [0, 'xxx'] 模式 ---
                $json = ['result' => $rtn[0]];
                if (isset($rtn[1])) {
                    if (is_array($rtn[1])) {
                        // --- [0, ['xx' => 'xx']] ---
                        echo json_encode(array_merge($json, $rtn[1]));
                    }
                    else {
                        // --- [0, 'xxx'] ---
                        $json['msg'] = $rtn[1];
                        if (isset($rtn[2])) {
                            echo json_encode(array_merge($json, $rtn[2]));
                        }
                        else {
                            // --- Kebab 不会有这种情况 ---
                            unset($rtn[0], $rtn[1]);
                            echo json_encode(array_merge($json, $rtn));
                        }
                    }
                }
                else {
                    // --- Kebab 不会出现这种情况 ---
                    unset($rtn[0]);
                    echo json_encode(array_merge($json, $rtn));
                }
            }
            else {
                echo json_encode($rtn);
            }
        }
        else {
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
        }
        $right = substr($path, $pathLio + 1);
        return [substr($path, 0, $pathLio), $right === '' ? 'index' : $right];
    }

    /**
     * --- 将 POST 数据的值执行 trim ---
     * @param $post
     */
    private static function _trimPost(&$post) {
        foreach ($post as $key => $val) {
            if (is_string($val)) {
                $post[$key] = trim($val);
            }
            else if (is_array($val)) {
                self::_trimPost($post[$key]);
            }
        }
    }

}

