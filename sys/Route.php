<?php
declare(strict_types = 1);

namespace sys;

require ETC_PATH.'route.php';

class Route {

    public static function run(): void {

        $path = URI;
        // --- 如果为空则定义为 @ ---
        if ($path === '') {
            $path = "@";
        }
        // --- 检查路由表 ---
        $param = [];
        $match = NULL;
        $pathLeft = ''; $pathRight = '';
        foreach (ROUTE as $rule => $ruleVal) {
            $rule = str_replace('/', '\\/', $rule);
            preg_match('/^' . $rule . '$/', $path, $match);
            if (!empty($match)) {
                list($pathLeft, $pathRight) = self::_getPathLeftRight($ruleVal);
                $param = $match;
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
            echo '[Error] Controller not found.';
            return;
        }
        require SYS_PATH . 'Ctr.php';
        require $filePath;
        // --- 判断 action 是否存在 ---
        /** @var Ctr $ctr */
        $ctr = new $ctr($param, $pathRight);
        $ctr->param = $param;
        $ctr->action = $pathRight;
        if (!method_exists($ctr, $pathRight)) {
            echo '[Error] Action not found.';
            return;
        }
        // --- 强制 HTTPS ---
        if (MUST_HTTPS && !$ctr->mustHttps()) {
            return;
        }
        // --- 执行 action ---
        $rtn = $ctr->$pathRight();
        if (!isset($rtn)) {
            return;
        }
        if (is_string($rtn)) {
            echo $rtn;
        } else if (is_array($rtn)) {
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
        $pathLio = strrpos($path, "/");
        if ($pathLio === false) {
            return [$path, 'index'];
        } else {
            return [substr($path, 0, $pathLio), substr($path, $pathLio + 1)];
        }
    }

}

