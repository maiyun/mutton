<?php
declare(strict_types = 1);

namespace ctr;

use lib\Aes;
use lib\Captcha;
use lib\Db;
use lib\Kv;
use lib\Net;
use lib\Session;
use lib\Sql;
use lib\Text;
use sys\Ctr;

class test extends Ctr {

    public function index() {
        $echo = [
            'Hello world! Welcome to use <strong>Mutton ' . VER . '</strong>!',

            '<br><br>URI: ' . URI,
            '<br>HTTPS: ' . (HTTPS ? 'true' : 'false'),
            '<br>MOBILE: ' . (MOBILE ? 'true' : 'false'),
            '<br>HOST: ' . HOST,
            '<br>HOSTNAME: ' . HOSTNAME,
            '<br>PHP Verison: ' . PHP_VERSION,

            '<br><br>URL_BASE: ' . URL_BASE,
            '<br>URL_FULL: ' . URL_FULL,

            '<br><br><b style="color: red;">Tips: The file can be deleted.</b>',

            '<br><br><b>Route (etc/set.php):</b>',
            '<br><br><a href="'.URL_BASE.'article/123">View "article/123"</a>',
            '<br><a href="'.URL_BASE.'article/456">View "article/456"</a>',

            '<br><br><b>Automatic route:</b>',
            '<br><br><a href="'.URL_BASE.'__Mutton__">View "__Mutton__"</a>',

            '<br><br><b>Query string:</b>',
            '<br><br><a href="'.URL_BASE.'test/qs?a=1&b=2">View "test/qs?a=1&b=2"</a>',

            '<br><br><b>Return json:</b>',
            '<br><br><a href="'.URL_BASE.'test/json?type=1">View "test/json?type=1"</a>',
            '<br><a href="'.URL_BASE.'test/json?type=2">View "test/json?type=2"</a>',
            '<br><a href="'.URL_BASE.'test/json?type=3">View "test/json?type=3"</a>',
            '<br><a href="'.URL_BASE.'test/json?type=4">View "test/json?type=4"</a>',
            '<br><a href="'.URL_BASE.'test/json?type=5">View "test/json?type=5"</a>',
            '<br><a href="'.URL_BASE.'test/json?type=6">View "test/json?type=6"</a>',

            '<br><br><b>Library test:</b>',

            '<br><br><b>Db:</b>',
            '<br><a href="'.URL_BASE.'test/db?s=Mysql">View "test/db?s=Mysql"</a>',
            '<br><a href="'.URL_BASE.'test/db?s=Sqlite">View "test/db?s=Sqlite"</a>',

            '<br><br><b>Kv:</b>',
            '<br><a href="'.URL_BASE.'test/kv?s=Memcached">View "test/kv?s=Memcached"</a>',
            '<br><a href="'.URL_BASE.'test/kv?s=Redis">View "test/kv?s=Redis"</a>',
            '<br><a href="'.URL_BASE.'test/kv?s=RedisSimulator">View "test/kv?s=RedisSimulator"</a>',

            '<br><br><b>Net:</b>',
            '<br><br><a href="'.URL_BASE.'test/net">View "test/net"</a>',
            '<br><a href="'.URL_BASE.'test/net-post">View "test/net-post"</a>',
            '<br><a href="'.URL_BASE.'test/net-form-test">View "test/net-form-test"</a>',
            '<br><a href="'.URL_BASE.'test/net-upload">View "test/net-upload"</a>',
            '<br><a href="'.URL_BASE.'test/net-cookie">View "test/net-cookie"</a>',

            '<br><br><b>Sql:</b>',
            '<br><br><a href="'.URL_BASE.'test/sql?type=insert">View "test/sql?type=insert"</a>',
            '<br><a href="'.URL_BASE.'test/sql?type=select">View "test/sql?type=select"</a>',
            '<br><a href="'.URL_BASE.'test/sql?type=update">View "test/sql?type=update"</a>',
            '<br><a href="'.URL_BASE.'test/sql?type=delete">View "test/sql?type=delete"</a>',
            '<br><a href="'.URL_BASE.'test/sql?type=where">View "test/sql?type=where"</a>',

            '<br><br><b>Session:</b>',
            '<br><br><a href="'.URL_BASE.'test/session_db">View "test/session_db"</a>',
            '<br><a href="'.URL_BASE.'test/session_redis">View "test/session_redis"</a>',

            '<br><br><b>Captcha:</b>',
            '<br><br><a href="'.URL_BASE.'test/captcha_fastbuild">View "test/captcha_fastbuild"</a>',
            '<br><a href="'.URL_BASE.'test/captcha_base64">View "test/captcha_base64"</a>',

            '<br><br><b>Text:</b>',
            '<br><br><a href="'.URL_BASE.'test/text">View "test/text"</a>',

            '<br><br><b>Aes:</b>',
            '<br><br><a href="'.URL_BASE.'test/aes">View "test/aes"</a>'
        ];
        $echo[] = '<br><br>'.$this->_getEnd();

        return join('', $echo);
    }

    public function article() {
        return 'Article ID: ' . $this->param[0] . '<br><br>' . $this->_getEnd();
    }

    public function qs() {
        $this->obStart();
        echo 'var_dump($_GET): <br><br>';
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

    public function db() {
        $this->checkInput($_GET, [
            's' => ['require', ['Mysql', 'Sqlite'], 0, 'Object not found.']
        ]);

        $db = Db::get($_GET['s']);
        if (!($rtn = $db->connect())) {
            return [0 ,'Failed('.($rtn === null ? 'null' : 'false').').'];
        }

        if (!($stmt = $db->query('SELECT * FROM `mu_session` LIMIT 10;'))) {
            return [0 ,'Failed("mu_session" not found).'];
        }

        $echo = ["<pre>\$db = Db::get('" . $_GET['s'] . "');
if (!(\$rtn = \$db->connect())) {
    return [0 ,'Failed('.(\$rtn === null ? 'null' : 'false').').'];
}

\$stmt = \$db->query('SELECT * FROM `mu_session` LIMIT 10;');</pre>"];

        $this->_dbTable($stmt, $echo);

        $exec = $db->exec('INSERT INTO `mu_session` (`token`, `data`, `time_update`, `time_add`) VALUES (\'test-token\', \'' . json_encode(['go' => 'ok']) . '\', \'' . time() . '\', \'' . time() . '\');');
        $insertId = $db->getInsertID();

        $echo[] = "<pre>\$exec = \$db->exec('INSERT INTO `mu_session` (`token`, `data`, `time_update`, `time_add`) VALUES (\'test-token\', \'' . json_encode(['go' => 'ok']) . '\', \'' . time() . '\', \'' . time() . '\');');
\$insertId = \$db->getInsertID();</pre>
exec: " . $exec . "<br>
insertId: " . $insertId . "<br>
error: ".json_encode($db->getErrorInfo())."<br><br>";

        $stmt = $db->query('SELECT * FROM `mu_session` LIMIT 10;');
        $this->_dbTable($stmt, $echo);

        $exec = $db->exec('DELETE FROM `mu_session` WHERE `id` = \'' . $insertId . '\';');
        $echo[] = "<pre>\$exec = \$db->exec('DELETE FROM `mu_session` WHERE `id` = \'$insertId\';');</pre>
exec: " . $exec . "<br><br>";

        $stmt = $db->query('SELECT * FROM `mu_session` LIMIT 10;');
        $this->_dbTable($stmt, $echo);

        return join('', $echo) . "<br>" . $this->_getEnd();
    }
    private function _dbTable(\PDOStatement $stmt, &$echo) {
        $echo[] = '<table width="100%"><tr>';
        $cc = $stmt->columnCount();
        for($i = 0; $i < $cc; ++$i) {
            $echo[] = '<th>' . htmlspecialchars($stmt->getColumnMeta($i)['name']) . '</th>';
        }
        $echo[] = "</tr>";

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $echo[] = '<tr>';
            foreach ($row as $key => $val) {
                $echo[] = '<td>' . htmlspecialchars($val) . '</td>';
            }
            $echo[] = '</tr>';
        }
        $echo[] = '</table>';
    }

    public function kv() {
        $this->checkInput($_GET, [
            's' => ['require', ['Memcached', 'Redis', 'RedisSimulator'], 0, 'Object not found.']
        ]);

        $kv = Kv::get($_GET['s']);
        if (!($rtn = $kv->connect([
            'binary' => false
        ]))) {
            return [0 ,'Failed('.($rtn === null ? 'null' : 'false').').'];
        }
        $value = isset($_GET['value']) ? $_GET['value'] : '';
        $ac = isset($_GET['ac']) ? $_GET['ac'] : '';

        $kvGet = strtoupper($_GET['s']);
        $kvGet = str_replace('SS', 'S_S', $kvGet);

        $this->obStart();

        echo "<pre>\$kv = Kv::get(Kv::$kvGet);
if (!(\$rtn = \$kv->connect())) {
    return [0 ,'Failed('.(\$rtn === null ? 'null' : 'false').').'];
}
var_dump(\$kv->getServerList());</pre>";
        var_dump($kv->getServerList());

        echo "<pre>var_dump(\$kv->isConnect());</pre>";
        var_dump($kv->isConnect());

        if ($ac == 'delete') {
            echo "<pre>var_dump(\$kv->get('test'));</pre>";
            var_dump($kv->get('test'));

            echo "<pre>var_dump(\$kv->delete('test'));</pre>";
            var_dump($kv->delete('test'));

            echo "<pre>var_dump(\$kv->get('test'));</pre>";
            var_dump($kv->get('test'));
        } else if ($ac == 'incr-decr-replace') {
            echo "<pre>var_dump(\$kv->getResultCode());\nvar_dump(\$kv->getResultMessage());\nvar_dump(\$kv->getLastError());</pre>";
            var_dump($kv->getResultCode());
            var_dump($kv->getResultMessage());
            var_dump($kv->getLastError());

            echo "<pre>var_dump(\$kv->delete('test'));</pre>";
            var_dump($kv->delete('test'));

            echo "<pre>var_dump(\$kv->replace('test', 'QAQ'));</pre>";
            var_dump($kv->replace('test', 'QAQ'));

            echo "<pre>var_dump(\$kv->incr('test'));</pre>";
            var_dump($kv->incr('test'));

            echo "<pre>var_dump(\$kv->get('test'));</pre>";
            var_dump($kv->get('test'));

            echo "<pre>var_dump(\$kv->set('test', 666));</pre>";
            var_dump($kv->set('test', 666));

            echo "<pre>var_dump(\$kv->incr('test'));</pre>";
            var_dump($kv->incr('test'));

            echo "<pre>var_dump(\$kv->get('test'));</pre>";
            var_dump($kv->get('test'));

            echo "<pre>var_dump(\$kv->decr('test', 10));</pre>";
            var_dump($kv->decr('test', 10));

            echo "<pre>var_dump(\$kv->get('test'));</pre>";
            var_dump($kv->get('test'));

            echo "<pre>var_dump(\$kv->replace('test', 111));</pre>";
            var_dump($kv->replace('test', 111));

            echo "<pre>var_dump(\$kv->get('test'));</pre>";
            var_dump($kv->get('test'));

            echo "<pre>var_dump(\$kv->replace('test', 'QAQ'));</pre>";
            var_dump($kv->replace('test', 'QAQ'));

            echo "<pre>var_dump(\$kv->incr('test', 10));</pre>";
            var_dump($kv->incr('test', 10));

            echo "<pre>var_dump(\$kv->getResultCode());\nvar_dump(\$kv->getResultMessage());\nvar_dump(\$kv->getLastError());</pre>";
            var_dump($kv->getResultCode());
            var_dump($kv->getResultMessage());
            var_dump($kv->getLastError());

            echo "<pre>var_dump(\$kv->get('test'));</pre>";
            var_dump($kv->get('test'));

            echo "<pre>var_dump(\$kv->getResultCode());\nvar_dump(\$kv->getResultMessage());\nvar_dump(\$kv->getLastError());</pre>";
            var_dump($kv->getResultCode());
            var_dump($kv->getResultMessage());
            var_dump($kv->getLastError());
        } else if ($ac === 'append-prepend') {
            echo "<pre>var_dump(\$kv->prepend('test', '0'));</pre>";
            var_dump($kv->prepend('test', '0'));

            echo "<pre>var_dump(\$kv->set('test', 'bbb'));</pre>";
            var_dump($kv->set('test', 'bbb'));

            echo "<pre>var_dump(\$kv->append('test', 'end'));</pre>";
            var_dump($kv->append('test', 'end'));

            echo "<pre>var_dump(\$kv->get('test'));</pre>";
            var_dump($kv->get('test'));

            echo "<pre>var_dump(\$kv->prepend('test', 'pre'));</pre>";
            var_dump($kv->prepend('test', 'pre'));

            echo "<pre>var_dump(\$kv->get('test'));</pre>";
            var_dump($kv->get('test'));

            echo "<pre>var_dump(\$kv->add('aaa'));</pre>";
            var_dump($kv->add('test', 'aaa'));

            echo "<pre>var_dump(\$kv->getResultCode());\nvar_dump(\$kv->getResultMessage());\nvar_dump(\$kv->getLastError());</pre>";
            var_dump($kv->getResultCode());
            var_dump($kv->getResultMessage());
            var_dump($kv->getLastError());
        } else if ($ac === 'other') {
            echo "<pre>for (\$i = 0; \$i < 50; ++\$i) {
    \$kv->add('t' . \$i, \$i, 10);
}
echo 'Added.';</pre>";
            for ($i = 0; $i < 50; ++$i) {
                $kv->add('t' . $i, $i, 10);
            }
            echo 'Added.';

            echo "<pre>var_dump(\$kv->getAllKeys());</pre>";
            var_dump($kv->getAllKeys());

            echo "<pre>var_dump(\$kv->keys('t*'));</pre>";
            var_dump($kv->keys('t*'));

            echo "<pre>var_dump(\$kv->scan());</pre>";
            var_dump($kv->scan());

            echo "<pre>var_dump(\$kv->scan('*2*'));</pre>";
            var_dump($kv->scan('*2*'));

            echo "<pre>var_dump(\$kv->scan('*', 3));</pre>";
            var_dump($kv->scan('*', 3));
        } else {
            echo "<pre>var_dump(\$kv->exists(['test', 'heheda']));</pre>";
            var_dump($kv->exists(['test', 'heheda']));

            echo "<pre>var_dump(\$kv->mget(['test', 'heheda']));</pre>";
            var_dump($kv->mget(['test', 'heheda']));

            echo "<pre>var_dump(\$kv->get('test'));</pre>";
            var_dump($kv->get('test'));

            echo "<pre>var_dump(\$kv->set('test', \$value ? \$value : 'ok'));</pre>";
            var_dump($kv->set('test', $value ? $value : 'ok'));

            echo "<pre>var_dump(\$kv->get('test'));</pre>";
            var_dump($kv->get('test'));
        }

        echo "<br><br>";

        return '<a href="'.URL_BASE.'test/kv?s='.$_GET['s'].'">Default</a> | ' .
            '<a href="'.URL_BASE.'test/kv?s='.$_GET['s'].'&value=aaa">Set "aaa"</a> | ' .
            '<a href="'.URL_BASE.'test/kv?s='.$_GET['s'].'&value=bbb">Set "bbb"</a> | ' .
            '<a href="'.URL_BASE.'test/kv?s='.$_GET['s'].'&ac=delete">Delete</a> | ' .
            '<a href="'.URL_BASE.'test/kv?s='.$_GET['s'].'&ac=incr-decr-replace">Incr/Decr/Replace</a> | ' .
            '<a href="'.URL_BASE.'test/kv?s='.$_GET['s'].'&ac=append-prepend">Append/Prepend</a> | ' .
            '<a href="'.URL_BASE.'test/kv?s='.$_GET['s'].'&ac=other">Other</a> | ' .
            '<a href="'.URL_BASE.'test">Return</a>' . $this->obEnd() . $this->_getEnd();
    }

    public function net() {
        $echo = [];

        $res = Net::get('https://cdn.jsdelivr.net/npm/deskrt/package.json');
        $echo[] = "<pre>Net::get('https://cdn.jsdelivr.net/npm/deskrt/package.json');</pre>
headers: <pre>" . json_encode($res->headers, JSON_PRETTY_PRINT) . "</pre>
content: <pre>" . $res->content . "</pre>
error: " . $res->error . "<br>
errno: " . $res->errno . "<br>
info: <pre>" . json_encode($res->info, JSON_PRETTY_PRINT) . "</pre>";

        return join('', $echo) . $this->_getEnd();
    }

    public function netPost() {
        $echo = [];

        $res = Net::post(URL_FULL . 'test/netPost1', ['a' => '1', 'b' => '2', 'c' => ['1', '2', '3']]);
        $echo[] = "<pre>Net::post('" . URL_FULL . "test/netPost1', ['a' => '1', 'b' => '2', 'c' => ['1', '2', '3']]);</pre>
headers: <pre>" . json_encode($res->headers, JSON_PRETTY_PRINT) . "</pre>
content: <pre>" . $res->content . "</pre>
error: " . $res->error . "<br>
errno: " . $res->errno . "<br>
info: <pre>" . json_encode($res->info, JSON_PRETTY_PRINT) . "</pre>";

        return join('', $echo) . $this->_getEnd();
    }

    public function netPost1() {
        return json_encode($_POST);
    }

    public function netFormTest() {
        $echo = [
            "<pre>",
            json_encode($_POST, JSON_PRETTY_PRINT),
            "\n-----\n",
            json_encode($_FILES, JSON_PRETTY_PRINT),
            "</pre>"
        ];
        $echo[] = <<<CODE
<form enctype="multipart/form-data" method="post">
    text a: <input type="text" name="a" value="a1"> <input type="text" name="a" value="a2"><br>
    file b: <input type="file" name="b"><br>
    file c: <input type="file" name="c"><input type="file" name="c"><br>
    fi d[]: <input type="file" name="d[]"><input type="file" name="d[]"><br>
    <input type="submit" value="Upload">
</form>
<hr>
<form method="post">
    name a: <input type="text" name="a" value="a&1"> <input type="text" name="a" value="a&2"><br>
    na b[]: <input type="text" name="b[]" value="b1"> <input type="text" name="b[]" value="b2"><br>
    name d: <input type="text" name="d" value="d"><br>
    <input type="submit" value="Default post">
</form>
CODE;

        return join('', $echo) . $this->_getEnd();
    }

    public function netUpload() {
        $echo = [];

        $res = Net::post(URL_FULL . 'test/net-upload1', [
            'a' => '1',
            'file' => curl_file_create(LIB_PATH . 'Net/cacert.pem'),
            'multiple' => [
                curl_file_create(LIB_PATH . 'Net/cacert.pem'),
                curl_file_create(LIB_PATH . 'Net/cacert.pem')
            ]
        ]);
        $echo[] = "<pre>Net::post('" . URL_FULL . "test/net-upload1', [
    'a' => '1',
    'file' => curl_file_create(LIB_PATH . 'Net/cacert.pem'),
    'multiple' => [
        curl_file_create(LIB_PATH . 'Net/cacert.pem'),
        curl_file_create(LIB_PATH . 'Net/cacert.pem')
    ]
]);</pre>
headers: <pre>" . json_encode($res->headers, JSON_PRETTY_PRINT) . "</pre>
content: <pre>" . $res->content . "</pre>
error: " . $res->error . "<br>
errno: " . $res->errno . "<br>
info: <pre>" . json_encode($res->info, JSON_PRETTY_PRINT) . "</pre>";

        return join('', $echo) . $this->_getEnd();
    }

    public function netUpload1() {
        return json_encode($_POST, JSON_PRETTY_PRINT) . "\n\n" . json_encode($_FILES, JSON_PRETTY_PRINT);
    }

    public function netCookie() {
        $echo = [];

        $cookie = [];
        $res = Net::get(URL_FULL.'test/net-cookie1', [], $cookie);
        $echo[] = "<pre>\$cookie = [];
Net::get('".URL_FULL."test/net-cookie1', [], \$cookie);</pre>
headers: <pre>" . json_encode($res->headers, JSON_PRETTY_PRINT) . "</pre>
content: <pre>" . $res->content . "</pre>
cookie: <pre>" . json_encode($cookie, JSON_PRETTY_PRINT) . "</pre><hr>";

        $res = Net::get(URL_FULL.'test/net-cookie2', [], $cookie);
        $echo[] = "<pre>Net::get('".URL_FULL."test/net-cookie2', [], \$cookie);</pre>
headers: <pre>" . json_encode($res->headers, JSON_PRETTY_PRINT) . "</pre>
content: <pre>" . $res->content . "</pre>";

        return join('', $echo) . $this->_getEnd();
    }
    public function netCookie1() {
        setcookie('test1', '123', $_SERVER['REQUEST_TIME'] + 10);
        setcookie('test2', '456', $_SERVER['REQUEST_TIME'] + 20, '/', 'baidu.com');
        setcookie('test3', '789', $_SERVER['REQUEST_TIME'] + 30, '/', HOSTNAME);
        setcookie('test4', '012', $_SERVER['REQUEST_TIME'] + 40, '/ok/');
        setcookie('test5', '345', $_SERVER['REQUEST_TIME'] + 10, '', '', true);
        return "setcookie('test1', '123', \$_SERVER['REQUEST_TIME'] + 10);
setcookie('test2', '456', \$_SERVER['REQUEST_TIME'] + 20, '/', 'baidu.com');
setcookie('test3', '789', \$_SERVER['REQUEST_TIME'] + 30, '/', '".HOSTNAME."');
setcookie('test4', '012', \$_SERVER['REQUEST_TIME'] + 40, '/ok/');
setcookie('test5', '345', \$_SERVER['REQUEST_TIME'] + 10, '', '', true);";
    }
    public function netCookie2() {
        return "\$_COOKIE: \n\n" . json_encode($_COOKIE, JSON_PRETTY_PRINT);
    }

    public function sql() {
        $echo = [];
        $sql = Sql::get([
            'pre' => 'test_'
        ]);
        switch ($_GET['type']) {
            case 'insert': {
                $s = $sql->insert('user', ['name', 'age'], [
                    ['Ah', '16'],
                    ['Bob', '24']
                ])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->insert('user', ['name', 'age'], [
    ['Ah', '16'],
    ['Bob', '24']
]);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) ."</pre>
<b>format() :</b> " . $sql->format($s, $sd) . '<hr>';

                $s = $sql->insert('user', ['name', 'age'], ['Ah', '16'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->insert('user', ['name', 'age'], ['Ah', '16']);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . '<hr>';

                $s = $sql->insert('user', ['name' => 'Bob', 'age' => '24'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->insert('user', ['name' => 'Bob', 'age' => '24'});</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . '<hr>';

                $s = $sql->insert('verify', ['token' => 'abc', 'time_update' => '10'])->onDuplicate(['time_update' => '20'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->insert('verify', ['token' => 'abc', 'time_update' => '10'})->onDuplicate(['time_update' => '20']);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd);
                break;
            }
            case 'select': {
                $s = $sql->select('*', 'user')->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->select('*', 'user');</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . "<hr>";

                $s = $sql->select(['id', 'name'], 'user')->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->select(['id', 'name'], 'user');</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . "<hr>";

                $s = $sql->select('*', ['user', 'order'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->select('*', ['user', 'order']);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . "<hr>";

                $s = $sql->select('*', ['db1.user', 'db2.user'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->select('*', ['db1.user', 'db2.user']);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . "<hr>";

                $s = $sql->select(['o.no', 'u.nick'], ['order o'])->leftJoin('user AS u', ['o.user_id' => '#u.id', 'state' => '1'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->select(['o.no', 'u.nick'], ['order o'])->leftJoin('user AS u', ['o.user_id' => '#u.id', 'state' => '1'])</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd);
                break;
            }
            case 'update': {
                // --- 1, 2 ---

                $s = $sql->update('user', [['age', '+', '1'], 'name' => 'Serene'])->where(['name' => 'Ah'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->update('user', [['age', '+', '1'], 'name' => 'Serene']).where(['name' => 'Ah']);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . "<hr>";

                // --- 3 ---

                $s = $sql->update('user', ['name' => 'Serene', 'type' => ['(CASE `id` WHEN \'1\' THEN ? ELSE ? END)', ['a', 'b']]])->where(['name' => 'Ah'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->update('user', ['name' => 'Serene', 'type' => ['(CASE `id` WHEN \\'1\\' THEN ? ELSE ? END)', ['a', 'b']]])->where(['name' => 'Ah']);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . "<hr>";

                // --- # ---

                $s = $sql->update('user', ['age' => '#age_verify', 'date' => '##'])->where(['date_birth' => '2001'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->update('user', ['age' => '#age_verify', 'date' => '##'])->where(['date_birth' => '2001']);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd);

                break;
            }
            case 'delete': {
                $s = $sql->delete('user')->where(['id' => '1'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->delete('user')->where(['id' => '1']);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd);
                break;
            }
            case 'where': {
                $s = $sql->select('*', 'user')->where(['city' => 'la', ['age', '>', '10'], ['level', 'in', ['1', '2', '3']]])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->select('*', 'user')->where(['city' => 'la', ['age', '>', '10'], ['level', 'in', ['1', '2', '3']]]);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . "<hr>";

                $s = $sql->update('order', ['state' => '1'])->where([
                    '$or' => [[
                        'type' => '1'
                    ], [
                        'type' => '2'
                    ]]
                ])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->update('order', ['state' => '1'])->where([
    '\$or' => [[
        'type' => '1'
    ], [
        'type' => '2'
    ]]
]);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . "<hr>";

                $s = $sql->update('order', ['state' => '1'])->where([
                    'user_id' => '2',
                    'state' => ['1', '2', '3'],
                    '$or' => [['type' => '1', 'find' => '0'], ['type' => '2', 'find' => '1'], ['type', '<', '-1']]
                ])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->update('order', ['state' => '1'])->where([
    'user_id' => '2',
    'state' => ['1', '2', '3'],
    '\$or' => [['type' => '1', 'find' => '0'], ['type' => '2', 'find' => '1'], ['type', '<', '-1']]
]);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . "<hr>";

                $s = $sql->select('*', 'user')->where([
                    'time_verify' => '#time_add'
                ])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->update('*', 'user')->where([
    'time_verify' => '#time_add'
]);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd);
                break;
            }
        }
        return join('', $echo) . '<br><br>' . $this->_getEnd();
    }

    public function session_db() {
        $this->obStart();
        echo '<pre>';
        try {
            $db = Db::get();
            echo "\$db = Db::get();\n\n";

            Session::start($db, [
                'exp' => '60'
            ]);
            echo "Session::start(\$db, ['exp' => '60']);\n\n";

            echo "<b>var_dump(\$_SESSION);</b>\n";
            var_dump($_SESSION);

            echo "\n\$_SESSION['value'] = '" . (isset($_GET['value']) ? $_GET['value'] : 'ok') . "';\n\n";
            $_SESSION['value'] = isset($_GET['value']) ? $_GET['value'] : 'ok';

            echo "<b>var_dump(\$_SESSION);</b>\n";
            var_dump($_SESSION);

            echo "\n<b>var_dump(Session::get('temp'));</b>\n";
            var_dump(Session::get('temp'));

            if (isset($_GET['temp'])) {
                echo "\nSession::set(\"temp\", " . $_GET['temp'] . ", 5);\n\n";
                Session::set("temp", $_GET['temp'], 5);

                echo "<b>Click other link to view the example.</b>";
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        echo '</pre>';

        return '<a href="'.URL_BASE.'test/session_db">Default</a> | <a href="'.URL_BASE.'test/session_db?value=aaa">Set "aaa"</a> | <a href="'.URL_BASE.'test/session_db?value=bbb">Set "bbb"</a> | <a href="'.URL_BASE.'test/session_db?temp=bye">Set "temp" is "bye", expire is 5 seconds.</a> | <a href="'.URL_BASE.'test">Return</a>' . $this->obEnd() . $this->_getEnd();
    }

    public function session_redis() {
        $this->obStart();
        echo '<pre>';
        try {
            $rd = Redis::get();
            echo "\$rd = Redis::get();\n\n";

            Session::start($rd, ['exp' => '60']);
            echo "Session::start(\$rd, ['exp' => '60']);\n\n";

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

        return '<a href="'.URL_BASE.'test/session_redis">Default</a> | <a href="'.URL_BASE.'test/session_redis?value=aaa">Set "aaa"</a> | <a href="'.URL_BASE.'test/session_redis?value=bbb">Set "bbb"</a> | <a href="'.URL_BASE.'test">Return</a>' . $this->obEnd() . $this->_getEnd();
    }

    public function captcha_fastbuild() {
        Captcha::get(400, 100)->output();
    }

    public function captcha_base64() {
        $this->obStart();

        echo '$cap = Captcha::get(400, 100);<br>$phrase = $cap->getPhrase();<br>$base64 = $cap->getBase64();<br>echo $phrase;';
        $cap = Captcha::get(400, 100);
        $phrase = $cap->getPhrase();
        $base64 = $cap->getBase64();
        echo '<pre>'.$phrase.'</pre>';

        echo 'echo $base64;';
        echo '<pre style="white-space: pre-wrap; word-wrap: break-word; overflow-y: auto; max-height: 200px;">'.$base64.'</pre>';

        echo '&lt;img src="&lt;?php echo $base64 ?&gt;" style="width: 200px; height: 50px;"&gt;';
        echo '<pre><img src="'.$base64.'" style="width: 200px; height: 50px;"></pre>';

        return $this->obEnd() . $this->_getEnd();
    }

    public function text() {
        $this->obStart();
        echo 'var_dump(Text::random(16, Text::RANDOM_LUNS)):<br><br>';
        echo 'string(16) "' . htmlspecialchars(Text::random(16, Text::RANDOM_LUNS)) .  '"';
        $rtn = $this->obEnd();
        return $rtn . '<br><br>' . $this->_getEnd();
    }

    public function aes() {
        $this->obStart();

        echo '<b>AES-256-ECB:</b>';

        $key = 'testkeyatestkeyatestkeyatestkeya';
        $text = Aes::encrypt('Original text', $key);
        echo '<pre>';
        echo "\$key = 'estkeyatestkeyatestkeyatestkeya';\n\$text = Aes::encrypt('Original text', \$key);\nvar_dump(\$text);";
        echo '</pre>';
        var_dump($text);

        $orig = Aes::decrypt($text, $key);
        echo '<pre>';
        echo "\$orig = Aes::decrypt(\$text, \$key);\nvar_dump(\$orig);";
        echo '</pre>';
        var_dump($orig);

        $orig = Aes::decrypt($text, 'otherKey');
        echo '<pre>';
        echo "\$orig = Aes::decrypt(\$text, 'otherKey');\nvar_dump(\$orig);";
        echo '</pre>';
        var_dump($orig);

        // ----------

        echo '<br><br><b>AES-256-CFB:</b>';

        $iv = 'iloveuiloveuilov';
        $text = Aes::encrypt('Original text', $key, $iv);
        echo '<pre>';
        echo "\$key = 'testkeyatestkeyatestkeyatestkeya';\n\$iv = 'iloveuiloveuilov';\n\$text = Aes::encrypt('Original text', \$key, \$iv);\nvar_dump(\$text);";
        echo '</pre>';
        var_dump($text);

        $orig = Aes::decrypt($text, $key, $iv);
        echo '<pre>';
        echo "\$orig = Aes::decrypt(\$text, \$key, \$iv);\nvar_dump(\$orig);";
        echo '</pre>';
        var_dump($orig);

        $orig = Aes::decrypt($text, $key, 'otherIv');
        echo '<pre>';
        echo "\$orig = Aes::decrypt(\$text, \$key, 'otherIv');\nvar_dump(\$orig);";
        echo '</pre>';
        var_dump($orig);

        // ----------

        echo '<br><br><b>AES-256-CBC:</b>';

        $text = Aes::encrypt('Original text', $key, $iv, Aes::AES_256_CBC);
        echo '<pre>';
        echo "\$key = 'testkeyatestkeyatestkeyatestkeya';\n\$iv = 'iloveuiloveuilov';\n\$text = Aes::encrypt('Original text', \$key, \$iv, Aes::AES_256_CBC);\nvar_dump(\$text);";
        echo '</pre>';
        var_dump($text);

        $orig = Aes::decrypt($text, $key, $iv, Aes::AES_256_CBC);
        echo '<pre>';
        echo "\$orig = Aes::decrypt(\$text, \$key, \$iv, Aes::AES_256_CBC);\nvar_dump(\$orig);";
        echo '</pre>';
        var_dump($orig);

        $orig = Aes::decrypt($text, $key, 'otherIv', Aes::AES_256_CBC);
        echo '<pre>';
        echo "\$orig = Aes::decrypt(\$text, \$key, 'otherIv', Aes::AES_256_CBC);\nvar_dump(\$orig);";
        echo '</pre>';
        var_dump($orig);

        $rtn = $this->obEnd();
        return $rtn . '<br><br>' . $this->_getEnd();
    }

    // --- END ---
    private function _getEnd(): string {
        $rt = $this->getRunTime();
        return 'Processed in ' . $rt . ' second(s), ' . round($rt * 1000, 4) . 'ms, ' . round($this->getMemoryUsage() / 1024, 2) . ' K.<style>*{font-family:Consolas,"Courier New",Courier,FreeMono,monospace;line-height: 1.5;font-size:12px;}pre{padding: 10px;background-color:rgba(0,0,0,.07);}hr{margin:20px 0;border-color:#000;border-style:dashed;border-width:1px 0 0 0;}td,th{padding:5px;border:solid 1px #000;}</style>';
    }

}

