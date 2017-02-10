<?php

namespace C {

	class Route {

		public static function run() {

            $routeKey = '';
            $param = [];
            if (URI === '') {
                $routeKey = '@';
            } else {
                foreach (ROUTE as $key => $val) {
                    if ($key !== '@') {
                        $tmpKey = preg_quote($key, '/');
                        $reg = str_replace('\*', '(.+?)', $tmpKey);
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

            if ($routeKey !== '') {
                require SYS_PATH . 'ctr.php';

                if (is_file(CTR_PATH . ROUTE[$routeKey]['path'] . '.php')) {
                    require CTR_PATH . ROUTE[$routeKey]['path'] . '.php';

                    $ctrName = ROUTE[$routeKey]['class'];
                    $ctr = new $ctrName;
                    $ctr->param = $param;

                    // --- 强制 HTTPS ---

                    if ((MUST_HTTPS && $ctr->mustHttps()) || !MUST_HTTPS) {

                        if (method_exists($ctr, ROUTE[$routeKey]['action'])) {
                            $act = ROUTE[$routeKey]['action'];
                            $ctr->$act();
                        } else {
                            echo '【错误】指定的控制器不存在';
                        }

                    }
                } else {
                    // --- 指定的控制器不存在 ---
                    echo '【错误】指定的控制器不存在';
                }
            } else {
                header('Location: ' . SITE_PATH);
            }

        }

	}

}

