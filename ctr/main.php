<?php
declare(strict_types = 1);

namespace ctr;

use lib\Db;
use lib\Memcached;
use lib\Net;
use lib\Redis;
use lib\Session;
use lib\Sql;
use sys\Ctr;

class main extends Ctr {

    public function main() {

        $echo = [
            'Hello world! Welcome to use Mutton ' . VER,

            '<br><br>URI: ' . URI . '.',
            '<br>HTTPS: ' . (HTTPS ? 'true' : 'false') . '.',
            '<br>HTTP_BASE: ' . HTTP_BASE,
            '<br>PHP Verison: ' . PHP_VERSION,

            '<br><br><b>ROUTE(etc/set.php):</b>',
            '<br><br><a href="'.HTTP_PATH.'article/123">View "article/123"</a>',
            '<br><a href="'.HTTP_PATH.'article/456">View "article/456"</a>',

            '<br><br><b>AUTO ROUTE:</b>',
            '<br><br><a href="'.HTTP_PATH.'__Mutton__/index">View "__Mutton__/index"</a>',

            '<br><br><b>QUERY STRING:</b>',
            '<br><br><a href="'.HTTP_PATH.'main/qs?a=1&b=2">View "main/qs?a=1&b=2"</a>',

            '<br><br><b>RETURN JSON:</b>',
            '<br><br><a href="'.HTTP_PATH.'main/json?type=1">View "main/json?type=1"</a>',
            '<br><a href="'.HTTP_PATH.'main/json?type=2">View "main/json?type=2"</a>',
            '<br><a href="'.HTTP_PATH.'main/json?type=3">View "main/json?type=3"</a>',
            '<br><a href="'.HTTP_PATH.'main/json?type=4">View "main/json?type=4"</a>',
            '<br><a href="'.HTTP_PATH.'main/json?type=5">View "main/json?type=5"</a>',
            '<br><a href="'.HTTP_PATH.'main/json?type=6">View "main/json?type=6"</a>',

            '<br><br><b>Library test:</b>',

            '<br><br><b>Memcached:</b>',
            '<br><br><a href="'.HTTP_PATH.'main/memcached">View "main/memcached"</a>',

            '<br><br><b>Net:</b>',
            '<br><br><a href="'.HTTP_PATH.'main/net">View "main/net"</a>',

            '<br><br><b>Sql:</b>',
            '<br><br><a href="'.HTTP_PATH.'main/sql?type=insert">View "main/sql?type=insert"</a>',
            '<br><a href="'.HTTP_PATH.'main/sql?type=select">View "main/sql?type=select"</a>',
            '<br><a href="'.HTTP_PATH.'main/sql?type=update">View "main/sql?type=update"</a>',
            '<br><a href="'.HTTP_PATH.'main/sql?type=delete">View "main/sql?type=delete"</a>',
            '<br><a href="'.HTTP_PATH.'main/sql?type=where">View "main/sql?type=where"</a>',
            '<br><a href="'.HTTP_PATH.'main/sql?type=single-mode">View "main/sql?type=single-mode"</a>',

            '<br><br><b>Redis:</b>',
            '<br><br><a href="'.HTTP_PATH.'main/redis_simulator">View "main/redis_simulator"</a>',

            '<br><br><b>Session:</b>',
            '<br><br><a href="'.HTTP_PATH.'main/session_db">View "main/session_db"</a>',
            '<br><a href="'.HTTP_PATH.'main/session_redis">View "main/session_redis"</a>'
        ];
        $echo[] = '<br><br>'.$this->_getEnd();

        return implode('', $echo);

    }

    public function article() {

        return 'Article ID: ' . $this->param[0] . '<br><br>' . $this->_getEnd();

    }

    public function auto() {

        $rt = $this->getRunTime();
        $this->loadView('main/auto', [
            'rt' => $rt,
            'ms' => round($rt * 1000, 4),
            'me' => round($this->getMemoryUsage() / 1024, 2)
        ]);

    }

    public function qs() {

        $this->obStart();
        echo '$_GET: <br><br>';
        var_dump($_GET);
        $rtn = $this->obEnd();

        return $rtn . '<br><br>' . $this->_getEnd();

    }

    public function json() {

        switch ($_GET['type']) {
            case '1':
                return [0];
            case '2':
                return [0, 'Error message.'];
            case '3':
                return [0, 'line' => '2'];
            case '4':
                return [1, 'Successful!'];
            case '5':
                return [1, 'list' => [['id' => '0'], ['id' => '1'], ['id' => '2']], 'total' => '3'];
            case '6':
                return ['oh' => 'yeah', 'sb' => 'is me'];
            default:
                return [];
        }

    }

    public function sql() {
        $rtn = '';

        $sql = Sql::get('test_');
        switch ($_GET['type']) {
            case 'insert':
                $s = $sql->insert('user', ['name', 'age'], [
                    ['Ah', '16'],
                    ['Bob', '24']
                ])->getSql();
                $sd = $sql->getData();

                $s2 = $sql->insert('user', ['name', 'age'], ['Ah', '16'])->getSql();
                $sd2 = $sql->getData();

                $s3 = $sql->insert('user', ['name' => 'Bob', 'age' => '24'])->getSql();
                $sd3 = $sql->getData();

                $rtn = "<pre>\$sql->insert('user', ['name', 'age'], [
['Ah', '16'],
['Bob', '24']
]);

<b>getSql() :</b> $s;
<b>getData():</b> ".print_r($sd, true)."

\$sql->insert('user', ['name', 'age'], ['Ah', '16']);

<b>getSql() :</b> $s2;
<b>getData():</b> ".print_r($sd2, true)."

\$sql->insert('user', ['name' => 'Bob', 'age' => '24']);

<b>getSql() :</b> $s3;
<b>getData():</b> ".print_r($sd3, true)."</pre>";
                break;
            case 'select':
                $s = $sql->select('*', 'user')->getSql();
                $sd = $sql->getData();

                $rtn = "<pre>\$sql->select('*', 'user');

<b>getSql() :</b> $s;
<b>getData():</b> ".print_r($sd, true)."</pre>";
                break;
            case 'update':
                try {
                    $s = $sql->update('user', ['name' => 'Serene', ['age', '+', '1']])->where(['name' => 'Ah'])->getSql();
                    $sd = $sql->getData();
                } catch (\Exception $ex) {
                    $s = '';
                    $sd = [];
                }

                $rtn = "<pre>\$sql->update('user', ['name' => 'Serene', ['age', '+', '1']])->where(['name' => 'Ah']);

<b>getSql() :</b> $s;
<b>getData():</b> ".print_r($sd, true)."</pre>";
                break;
            case 'delete':
                try {
                    $s = $sql->delete('user')->where(['id' => '1'])->getSql();
                    $sd = $sql->getData();
                } catch (\Exception $ex) {
                    $s = '';
                    $sd = [];
                }

                $rtn = "<pre>\$sql->delete('user')->where(['id' => '1']);

<b>getSql() :</b> $s;
<b>getData():</b> ".print_r($sd, true)."</pre>";
                break;
            case 'where':
                try {
                    $s = $sql->select('*', 'user')->where(['city' => 'la', ['age', '>', '10'], ['level', 'in', ['1', '2', '3']]])->getSql();
                    $sd = $sql->getData();

                    $s2 = $sql->update('order', ['state' => '1'])->where([
                        [
                            'list' => [
                                'type' => '1'
                            ]
                        ],
                        [
                            'bound' => 'or',
                            'list' => [
                                'type' => '2'
                            ]
                        ]
                    ])->getSql();
                    $sd2 = $sql->getData();

                    $s3 = $sql->update('order', ['state' => '1'])->where([
                        'user_id' => '2',
                        'state' => ['1', '2', '3'],
                        [
                            'list' => [
                                [
                                    'list' => [
                                        'type' => '1'
                                    ]
                                ],
                                [
                                    'bound' => 'or',
                                    'list' => [
                                        'type' => '2'
                                    ]
                                ]
                            ]
                        ]
                    ])->getSql();
                    $sd3 = $sql->getData();
                } catch (\Exception $ex) {
                    $s = '';
                    $sd = [];

                    $s2 = '';
                    $sd2 = [];

                    $s3 = '';
                    $sd3 = [];
                }

                $rtn = "<pre>\$sql->select('*', 'user')->where(['city' => 'la', ['age', '>', '10'], ['level', 'in', ['1', '2', '3']]]);

<b>getSql() :</b> $s;
<b>getData():</b> ".print_r($sd, true)."

\$sql->update('order', ['state' => '1'])->where([
[
    'list' => [
        'type' => '1'
    ]
],
[
    'bound' => 'or',
    'list' => [
        'type' => '2'
    ]
]
]);

<b>getSql() :</b> $s2;
<b>getData():</b> ".print_r($sd2, true)."

\$sql->update('order', ['state' => '1'])->where([
'user_id' => '2',
'state' => ['1', '2', '3'],
[
    'list' => [
        [
            'list' => [
                'type' => '1'
            ]
        ],
        [
            'bound' => 'or',
            'list' => [
                'type' => '2'
            ]
        ]
    ]
]
]);

<b>getSql() :</b> $s3;
<b>getData():</b> ".print_r($sd3, true)."</pre>";
                break;
            case 'single-mode':
                $sql->setSingle(true);
                try {
                    $s = $sql->update('user', ['name' => 'Serene', ['age', '+', '1']])->where(['name' => 'Ah'])->getSql();
                } catch (\Exception $ex) {
                    $s = '';
                }

                $rtn = "<pre>\$sql->setSingle(true);
\$sql->update('user', ['name' => 'Serene', ['age', '+', '1']])->where(['name' => 'Ah']);

<b>getSql():</b> $s;</pre>";
                break;
        }
        return $rtn . $this->_getEnd();
    }

    public function memcached() {

        $this->obStart();

        echo '<pre>';
        try {
            $mc = Memcached::get();
            echo '$mc = Memcached::get();';

            echo "\n\n<b>\$mc->getServerList();</b>\n";
            var_dump($mc->getServerList());

            echo "\n<b>\$mc->getValue('test');</b>\n";
            var_dump($mc->getValue('test'));

            echo "\n\$mc->setValue('test', isset(\$_GET['value']) ? \$_GET['value'] : 'ok');";
            $mc->setValue('test', isset($_GET['value']) ? $_GET['value'] : 'ok');

            echo "\n<b>\$mc->getValue('test');</b>\n";
            var_dump($mc->getValue('test'));
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        echo '</pre>';

        return '<a href="'.HTTP_PATH.'main/memcached">Default</a> | <a href="'.HTTP_PATH.'main/memcached?value=aaa">Set "aaa"</a> | <a href="'.HTTP_PATH.'main/memcached?value=bbb">Set "bbb"</a> | <a href="'.HTTP_PATH.'">Return</a>' . $this->obEnd() . $this->_getEnd();
    }

    public function net() {
        $this->obStart();

        echo '<pre>';

        try {
            $result = Net::get('https://cdn.jsdelivr.net/npm/deskrt/package.json');
            echo "Net::get('https://cdn.jsdelivr.net/npm/deskrt/package.json');\n\n";
            var_dump($result);
            echo "\nError: ".Net::getError();
            echo "\n\nErrno: ".Net::getErrno();
            echo "\n\nInfo:\n";
            var_dump(Net::getInfo());
        } catch (\Exception $e) {
            echo 'Error: '.$e->getMessage().'.';
        }

        return $this->obEnd() . '</pre>' . $this->_getEnd();
    }

    public function redis_simulator() {
        $this->obStart();
        echo '<pre>';
        try {
            $db = Db::get();
            echo "\$db = Db::get();\n\n";

            $rd = Redis::get([
                'simulator' => 'true'
            ]);
            $rd->setSimulatorDb($db);
            echo "\$rd = Redis::get('main', ['simulator' => 'true']);\n\$rd->setSimulatorDb(\$db);\n";

            echo "\n<b>\$rd->getValue('test');</b>\n";
            var_dump($rd->getValue('test'));

            echo "\n\$rd->setValue('test', isset(\$_GET['value']) ? \$_GET['value'] : 'ok', 60);\n";
            $rtn = $rd->setValue('test', isset($_GET['value']) ? $_GET['value'] : 'ok', 60);
            var_dump($rtn);

            echo "\n<b>\$rd->getValue('test');</b>\n";
            var_dump($rd->getValue('test'));
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        echo '</pre>';

        return '<a href="'.HTTP_PATH.'main/redis_simulator">Default</a> | <a href="'.HTTP_PATH.'main/redis_simulator?value=aaa">Set "aaa"</a> | <a href="'.HTTP_PATH.'main/redis_simulator?value=bbb">Set "bbb"</a> | <a href="'.HTTP_PATH.'">Return</a>' . $this->obEnd() . $this->_getEnd();
    }

    public function session_db() {
        $this->obStart();
        echo '<pre>';
        try {
            $db = Db::get();
            echo "\$db = Db::get();\n\n";

            Session::start($db);
            echo "Session::start(\$db);\n\n";

            echo "<b>var_dump(\$_SESSION);</b>\n";
            var_dump($_SESSION);

            echo "\n\$_SESSION['value'] = isset(\$_GET['value']) ? \$_GET['value'] : 'ok';\n\n";
            $_SESSION['value'] = isset($_GET['value']) ? $_GET['value'] : 'ok';

            echo "<b>var_dump(\$_SESSION);</b>\n";
            var_dump($_SESSION);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        echo '</pre>';

        return '<a href="'.HTTP_PATH.'main/session_db">Default</a> | <a href="'.HTTP_PATH.'main/session_db?value=aaa">Set "aaa"</a> | <a href="'.HTTP_PATH.'main/session_db?value=bbb">Set "bbb"</a> | <a href="'.HTTP_PATH.'">Return</a>' . $this->obEnd() . $this->_getEnd();
    }

    public function session_redis() {
        $this->obStart();
        echo '<pre>';
        try {
            $rd = Redis::get();
            echo "\$rd = Redis::get();\n\n";

            Session::start($rd);
            echo "Session::start(\$rd);\n\n";

            echo "<b>var_dump(\$_SESSION);</b>\n";
            var_dump($_SESSION);

            echo "\n\$_SESSION['value'] = isset(\$_GET['value']) ? \$_GET['value'] : 'ok';\n\n";
            $_SESSION['value'] = isset($_GET['value']) ? $_GET['value'] : 'ok';

            echo "<b>var_dump(\$_SESSION);</b>\n";
            var_dump($_SESSION);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        echo '</pre>';

        return '<a href="'.HTTP_PATH.'main/session_redis">Default</a> | <a href="'.HTTP_PATH.'main/session_redis?value=aaa">Set "aaa"</a> | <a href="'.HTTP_PATH.'main/session_redis?value=bbb">Set "bbb"</a> | <a href="'.HTTP_PATH.'">Return</a>' . $this->obEnd() . $this->_getEnd();
    }

    // --- END ---
    private function _getEnd(): string {
        $rt = $this->getRunTime();
        return 'Processed in ' . $rt . ' second(s), ' . round($rt * 1000, 4) . 'ms, ' . round($this->getMemoryUsage() / 1024, 2) . ' K.';
    }

}

