<?php
declare(strict_types = 1);

namespace sys;

require ETC_PATH.'route.php';

class Route {

    public static function run(): void {

        $routeKey = ''; // --- 如果在 ROUTE 表中匹配到，则以 ROUTE 表为准 ---
        $param = []; // --- 传入的参数 ---
        if (URI === '') {
            $routeKey = '@';
        } else {
            foreach (ROUTE as $key => $val) {
                if ($key !== '@') {
                    $reg = str_replace('/', '\\/', $key);
                    preg_match('/^' . $reg . '$/', URI, $matches);
                    if (!empty($matches)) {
                        array_splice($matches, 0, 1);
                        $routeKey = $key;
                        $param = $matches;
                        break;
                    }
                }
            }
        }

        // --- 如果没有在路由表中找到相对应的解析，那么，则将 URI 直接当作值来处理 ---
        $routeVal = ($routeKey !== '') ? ROUTE[$routeKey] : URI;

        require SYS_PATH . 'Ctr.php';

        $routeArray = explode('/', $routeVal);
        // $filePath = '';
        // $className = '';
        $routeCount = count($routeArray);

        if($routeCount == 2) {
            $filePath = $routeArray[0];
            $className = '\\ctr\\' . $routeArray[0];
            $action = $routeArray[1];
        } else {
            $filePath = implode('/', array_slice($routeArray, 0, $routeCount - 1));
            $className = '\\' . implode('\\', array_slice($routeArray, 0, $routeCount - 1));
            $action = $routeArray[$routeCount - 1];
        }

        if (is_file(CTR_PATH . $filePath . '.php')) {
            require CTR_PATH . $filePath . '.php';

            /* @var Ctr $ctr */
            $ctr = new $className($param, $action);
            $ctr->param = $param;
            $ctr->action = $action;

            // --- 强制 HTTPS ---

            if ((MUST_HTTPS && $ctr->mustHttps()) || !MUST_HTTPS) {

                if (method_exists($ctr, $action)) {
                    $rtn = $ctr->$action();
                    if (isset($rtn)) {
                        // --- return 返回值输出出来 ---
                        if (is_string($rtn)) {
                            echo $rtn;
                        } else if (is_array($rtn)) {
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
                            // 别用 JSON_UNESCAPED_UNICODE 啊，Android 可能解不了
                        } else {
                            echo '[Error] Return type is wrong.';
                        }
                    }
                } else {
                    echo '[Error] Action not found.';
                }

            }
        } else {
            // --- 指定的控制器不存在 ---
            echo '[Error] Controller not found.';
        }

    }

}

