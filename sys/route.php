<?php

namespace C {

	class Route {

		public static function run() {

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

            require SYS_PATH . 'ctr.php';

            $routeArray = explode('/', $routeVal);
            // $filePath = '';
            // $className = '';
            $routeCount = count($routeArray);
            if($routeCount == 2) {
                $filePath = $routeArray[0];
                $className = '\\main\\' . $routeArray[0];
                $action = $routeArray[1];
            } else {
                $filePath = implode('/', array_slice($routeArray, 0, $routeCount - 1));
                $className = '\\' . implode('\\', array_slice($routeArray, 0, $routeCount - 1));
                $action = $routeArray[$routeCount - 1];
            }

            if (is_file(CTR_PATH . $filePath . '.php')) {
                require CTR_PATH . $filePath . '.php';

                $ctr = new $className;
                $ctr->param = $param;

                // --- 强制 HTTPS ---

                if ((MUST_HTTPS && $ctr->mustHttps()) || !MUST_HTTPS) {

                    if (method_exists($ctr, $action)) {
                        $ctr->$action();
                    } else {
                        echo '【错误】指定的控制器 action 不存在';
                    }

                }
            } else {
                // --- 指定的控制器不存在 ---
                echo '【错误】指定的控制器不存在';
            }

        }

	}

}

