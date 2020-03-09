<?php
declare(strict_types = 1);

namespace ctr;

use lib\Crypto;
use lib\Captcha;
use lib\Db;
use lib\Kv;
use lib\Net;
use lib\Sql;
use lib\Text;
use mod\Mod;
use mod\Session;
use PDO;
use PDOStatement;
use sys\Ctr;

class test extends Ctr {

    public function _load() {
        if (HOST !== '127.0.0.1' && HOST !== 'local-test.brc-app.com' && HOST !== 'local-test.brc-app.com.cn') {
            return [0, 'Please use 127.0.0.1 to access the file.'];
        }
    }

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
            '<br>URI: ' . URI,
            '<br>URL_FULL: ' . URL_FULL,

            '<br><br>headers: ' . htmlspecialchars(json_encode($this->_headers)),

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

            '<br><br><b>Ctr:</b>',
            '<br><br><a href="'.URL_BASE.'test/ctr-random">View "test/ctr-random"</a>',
            '<br><a href="'.URL_BASE.'test/ctr-xsrf">View "test/ctr-xsrf"</a>',

            '<br><br><b>Middle:</b>',
            '<br><br><a href="'.URL_BASE.'test/middle">View "test/middle"</a>',

            '<br><br><b>Model test:</b>',

            '<br><br><b style="color: red;">In a production environment, please delete the "Mod/Session.php" file.</b>',
            '<br><a href="'.URL_BASE.'test/mod">Click to see an example of a Session model</a>',

            '<br><br><b>Library test:</b>',

            '<br><br><b>Captcha:</b>',
            '<br><br><a href="'.URL_BASE.'test/captcha-fastbuild">View "test/captcha-fastbuild"</a>',
            '<br><a href="'.URL_BASE.'test/captcha-base64">View "test/captcha-base64"</a>',

            '<br><br><b>Crypto:</b>',
            '<br><br><a href="'.URL_BASE.'test/crypto">View "test/crypto"</a>',

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
            '<br><a href="'.URL_BASE.'test/net-open">View "test/net-open"</a>',
            '<br><a href="'.URL_BASE.'test/net-form-test">View "test/net-form-test"</a>',
            '<br><a href="'.URL_BASE.'test/net-upload">View "test/net-upload"</a>',
            '<br><a href="'.URL_BASE.'test/net-cookie">View "test/net-cookie"</a>',
            '<br><a href="'.URL_BASE.'test/net-save">View "test/net-save"</a>',
            '<br><a href="'.URL_BASE.'test/net-reuse">View "test/net-reuse"</a>',

            '<br><br><b>Session:</b>',
            '<br><br><a href="'.URL_BASE.'test/session?s=db">View "test/session?s=db"</a>',
            '<br><a href="'.URL_BASE.'test/session?s=kv">View "test/session?s=kv"</a>',
            '<br><a href="'.URL_BASE.'test/session?s=db&auth=1">View "test/session?s=db&auth=1" Header Authorization</a>',
            '<br><a href="'.URL_BASE.'test/session?s=kv&auth=1">View "test/session?s=kv&auth=1" Header Authorization</a>',

            '<br><br><b>Sql:</b>',
            '<br><br><a href="'.URL_BASE.'test/sql?type=insert">View "test/sql?type=insert"</a>',
            '<br><a href="'.URL_BASE.'test/sql?type=select">View "test/sql?type=select"</a>',
            '<br><a href="'.URL_BASE.'test/sql?type=update">View "test/sql?type=update"</a>',
            '<br><a href="'.URL_BASE.'test/sql?type=delete">View "test/sql?type=delete"</a>',
            '<br><a href="'.URL_BASE.'test/sql?type=where">View "test/sql?type=where"</a>',

            '<br><br><b>Text:</b>',
            '<br><br><a href="'.URL_BASE.'test/text">View "test/text"</a>'
        ];
        $echo[] = '<br><br>'.$this->_getEnd();

        return join('', $echo);
    }

    public function article() {
        return 'Article ID: ' . htmlspecialchars($this->_param[0]) . '<br><br>' . $this->_getEnd();
    }

    public function qs() {
        return 'json_encode($_GET): <br><br>' . htmlspecialchars(json_encode($_GET)) . '<br><br>' . $this->_getEnd();
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

    public function ctrRandom() {
        return "<pre>\$this->_random(16, Ctr::RANDOM_LUNS);</pre>" . htmlspecialchars($this->_random(16, Ctr::RANDOM_LUNS)) . "
<pre>\$this->_random(4, Ctr::RANDOM_V);</pre>" . htmlspecialchars($this->_random(4, Ctr::RANDOM_V)). "<br><br>" . $this->_getEnd();
    }

    public function ctrXsrf() {
        return "XSRF-TOKEN: " . $this->_xsrf . "<br><br>
<input type=\"button\" value=\"Post with xsrf token\" onclick=\"document.getElementById('result').innerText='Waiting...';fetch('" . URL_BASE . "test/ctr-xsrf1',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'key=val&_xsrf=".$this->_xsrf."'}).then(function(r){return r.text();}).then(function(t){document.getElementById('result').innerText=t;});\">
<input type='button' value=\"Post without xsrf token\" style=\"margin-left: 10px;\" onclick=\"document.getElementById('result').innerText='Waiting...';fetch('" . URL_BASE . "test/ctr-xsrf1',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'key=val'}).then(function(r){return r.text();}).then(function(t){document.getElementById('result').innerText=t;});\"><br><br>
Result:<pre id=\"result\">Nothing.</pre>" . $this->_getEnd();
    }
    public function ctrXsrf1() {
        if (!$this->_checkXInput($_POST, [], $return)) {
            return $return;
        }
        return [1, 'post' => $_POST];
    }

    public function mod() {
        if (!($this->_checkInput($_GET, [
            'action' => [['', 'remove'], [0, 'Error']]
        ], $return))) {
            return $return;
        }

        $echo = ['<b style="color: red;">In a production environment, please delete the "Mod/Session.php file.</b>'];

        $db = Db::get(Db::MYSQL);
        if (!($rtn = $db->connect())) {
            return [0 ,'Failed('.($rtn === null ? 'null' : 'false').').'];
        }

        if (!($stmt = $db->query('SELECT * FROM `mu_session` WHERE `token` LIMIT 1;'))) {
            return [0 ,'Failed("mu_session" not found).'];
        }

        Mod::setDb($db);

        if ($_GET['action'] === 'remove') {
            Session::removeByWhere([
                ['token', 'LIKE', 'test_%']
            ]);
            return $this->_location('test/mod');
        } else {

            $time = time();
            $session = Session::getCreate();
            $session->set([
                'data' => json_encode(['test' => $this->_random(4)]),
                'time_update' => $time,
                'time_add' => $time
            ]);
            $result = $session->create();

            $echo[] = "<pre>Mod::setDb(\$db);
\$time = time();
\$session = \mod\Session::getCreate();
\$session->set([
    'data' => json_encode(['ok' => '1']),
    'time_update' => \$time,
    'time_add' => \$time
]);
\$result = \$session->create();
json_encode(\$result);</pre>" . json_encode($result);

            $echo[] = "<pre>json_encode(\$session->toArray());</pre>" . htmlspecialchars(json_encode($session->toArray()));

            $echo[] = "<br><br>Session table:";

            $stmt = $db->query('SELECT * FROM `mu_session` WHERE `token` LIKE \'test_%\' ORDER BY `id` ASC;');
            $this->_dbTable($stmt, $echo);

            $echo[] = "<br><a href=\"".URL_BASE."test/mod?action=remove\">Remove all test data</a> | <a href=\"".URL_BASE."test\">Return</a>";

            return join('', $echo) . '<br><br>' . $this->_getEnd();
        }
    }

    public function captchaFastbuild() {
        return Captcha::get(400, 100)->getStream();
    }

    public function captchaBase64() {
        $echo = ["<pre>\$cap = Captcha::get(400, 100);
\$phrase = \$cap->getPhrase();
\$base64 = \$cap->getBase64();
echo \$phrase;</pre>"];
        $cap = Captcha::get(400, 100);
        $phrase = $cap->getPhrase();
        $base64 = $cap->getBase64();
        $echo[] = '<pre>'.$phrase.'</pre>';

        $echo[] = 'echo $base64;';
        $echo[] = '<pre style="white-space: pre-wrap; word-wrap: break-word; overflow-y: auto; max-height: 200px;">'.$base64.'</pre>';

        $echo[] = '&lt;img src="&lt;?php echo $base64 ?&gt;" style="width: 200px; height: 50px;"&gt;';
        $echo[] = '<pre><img alt="captcha" src="'.$base64.'" style="width: 200px; height: 50px;"></pre>';

        return join('', $echo) . $this->_getEnd();
    }

    public function crypto() {
        $echo = ['<b>AES-256-ECB:</b>'];

        $key = 'testkeyatestkeyatestkeyatestkeya';
        $text = Crypto::aesEncrypt('Original text', $key);
        $echo[] = "<pre>\$key = 'testkeyatestkeyatestkeyatestkeya';
\$text = Crypto::aesEncrypt('Original text', \$key);
json_encode(\$text);</pre>" . json_encode($text);

        $orig = Crypto::aesDecrypt($text, $key);
        $echo[] = "<pre>\$orig = Crypto::aesDecrypt(\$text, \$key);
json_encode(\$orig);</pre>" . json_encode($orig);

        $orig = Crypto::aesDecrypt($text, 'otherKey');
        $echo[] = "<pre>\$orig = Crypto::aesDecrypt(\$text, 'otherKey');
json_encode(\$orig);</pre>" . json_encode($orig);

        // ----------

        $echo[] = '<br><br><b>AES-256-CFB:</b>';

        $iv = 'iloveuiloveuilov';
        $text = Crypto::aesEncrypt('Original text', $key, $iv);
        $echo[] = "<pre>\$iv = 'iloveuiloveuilov';
\$text = Crypto::aesEncrypt('Original text', \$key, \$iv);
json_encode(\$text);</pre>" . json_encode($text);

        $orig = Crypto::aesDecrypt($text, $key, $iv);
        $echo[] = "<pre>\$orig = Crypto::aesDecrypt(\$text, \$key, \$iv);\njson_encode(\$orig);</pre>" . json_encode($orig);

        $orig = Crypto::aesDecrypt($text, $key, 'otherIv');
        $echo[] = "<pre>\$orig = Crypto::aesDecrypt(\$text, \$key, 'otherIv');
json_encode(\$orig);</pre>" . json_encode($orig);

        // ----------

        $echo[] = '<br><br><b>AES-256-CBC:</b>';

        $text = Crypto::aesEncrypt('Original text', $key, $iv, Crypto::AES_256_CBC);
        $echo[] = "<pre>\$key = 'testkeyatestkeyatestkeyatestkeya';
\$iv = 'iloveuiloveuilov';
\$text = Crypto::aesEncrypt('Original text', \$key, \$iv, Aes::AES_256_CBC);
json_encode(\$text);</pre>" . json_encode($text);

        $orig = Crypto::aesDecrypt($text, $key, $iv, Crypto::AES_256_CBC);
        $echo[] = "<pre>\$orig = Crypto::aesDecrypt(\$text, \$key, \$iv, Aes::AES_256_CBC);
json_encode(\$orig);</pre>" . json_encode($orig);

        $orig = Crypto::aesDecrypt($text, $key, 'otherIv', Crypto::AES_256_CBC);
        $echo[] = "<pre>\$orig = Crypto::aesDecrypt(\$text, \$key, 'otherIv', Aes::AES_256_CBC);
json_encode(\$orig);</pre>" . json_encode($orig);

        return join('', $echo) . '<br><br>' . $this->_getEnd();
    }

    public function db() {
        if (!$this->_checkInput($_GET, [
            's' => ['require', ['Mysql', 'Sqlite'], [0, 'Object not found.']]
        ], $return)) {
            return $return;
        }

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
exec: " . json_encode($exec) . "<br>
insertId: " . json_encode($insertId) . "<br>
errorCode: " . json_encode($db->getErrorCode()) . "<br>
error: ".json_encode($db->getErrorInfo())."<br><br>";

        $stmt = $db->query('SELECT * FROM `mu_session` LIMIT 1;');
        $this->_dbTable($stmt, $echo);

        $exec = $db->exec('INSERT INTO `mu_session` (`token`, `data`, `time_update`, `time_add`) VALUES (\'test-token\', \'' . json_encode(['go' => 'ok']) . '\', \'' . time() . '\', \'' . time() . '\');');
        $echo[] = "<pre>\$exec = \$db->exec('INSERT INTO `mu_session` (`token`, `data`, `time_update`, `time_add`) VALUES (\'test-token\', \'' . json_encode(['go' => 'ok']) . '\', \'' . time() . '\', \'' . time() . '\');');
\$insertId = \$db->getInsertID();</pre>
exec: " . json_encode($exec) . "<br>
errorCode: " . json_encode($db->getErrorCode()) . "<br>
error: ".json_encode($db->getErrorInfo())."<br><br>";

        $exec = $db->exec('REPLACE INTO `mu_session` (`token`, `data`, `time_update`, `time_add`) VALUES (\'test-token\', \'' . json_encode(['go2' => 'ok2']) . '\', \'' . time() . '\', \'' . time() . '\');');
        $echo[] = "<pre>\$exec = \$db->exec('REPLACE INTO `mu_session` (`id`, `token`, `data`, `time_update`, `time_add`) VALUES (\'" . $insertId . "\', \'test2-token\', \'' . json_encode(['go' => 'ok2']) . '\', \'' . time() . '\', \'' . time() . '\');');
\$insertId = \$db->getInsertID();</pre>
exec: " . json_encode($exec) . "<br>
" . ($exec ? "insertId: " . json_encode($db->getInsertID()) . "<br>" : "") . "
errorCode: " . json_encode($db->getErrorCode()) . "<br>
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
    private function _dbTable(PDOStatement $stmt, &$echo) {
        $echo[] = '<table style="width: 100%;"><tr>';
        if ($stmt->getColumnMeta(0)) {
            $cc = $stmt->columnCount();
            for ($i = 0; $i < $cc; ++$i) {
                $echo[] = '<th>' . htmlspecialchars($stmt->getColumnMeta($i)['name']) . '</th>';
            }
            $echo[] = "</tr>";

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $echo[] = '<tr>';
                foreach ($row as $key => $val) {
                    $echo[] = '<td>' . htmlspecialchars($val) . '</td>';
                }
                $echo[] = '</tr>';
            }
        } else {
            $echo[] = '<th>No data</th></tr>';
        }
        $echo[] = '</table>';
    }

    public function kv() {
        if (!$this->_checkInput($_GET, [
            's' => ['require', ['Memcached', 'Redis', 'RedisSimulator'], [0, 'Object not found.']]
        ], $return)) {
            return $return;
        }

        $kv = Kv::get($_GET['s']);
        $db = null;
        if ($_GET['s'] === 'RedisSimulator') {
            $db = Db::get(Db::MYSQL);
            if (!$db->connect()) {
                return [0, 'Failed, MySQL can not be connected.'];
            }
        }
        if (!($rtn = $kv->connect([
            'binary' => false,
            'db' => $db
        ]))) {
            return [0 ,'Failed('.($rtn === null ? 'null' : 'false').').'];
        }
        $value = isset($_GET['value']) ? $_GET['value'] : '';
        $ac = isset($_GET['ac']) ? $_GET['ac'] : '';

        $kvGet = strtoupper($_GET['s']);
        $kvGet = str_replace('SS', 'S_S', $kvGet);

        $echo[] = "<pre>\$kv = Kv::get(Kv::$kvGet);
if (!(\$rtn = \$kv->connect())) {
    return [0 ,'Failed('.(\$rtn === null ? 'null' : 'false').').'];
}
json_encode(\$kv->getServerList());</pre>" . json_encode($kv->getServerList());

        $echo[] = "<pre>json_encode(\$kv->isConnect());</pre>";
        $echo[] = json_encode($kv->isConnect());

        if ($ac == 'delete') {
            $echo[] = "<pre>json_encode(\$kv->get('test'));</pre>" . json_encode($kv->get('test'));

            $echo[] = "<pre>json_encode(\$kv->delete('test'));</pre>" . json_encode($kv->delete('test'));

            $echo[] = "<pre>json_encode(\$kv->get('test'));</pre>" . json_encode($kv->get('test'));
        } else if ($ac == 'incr-decr-replace') {
            $echo[] = "<pre>json_encode(\$kv->getResultCode());
json_encode(\$kv->getResultMessage());
json_encode(\$kv->getLastError());</pre>";
            $echo[] = json_encode($kv->getResultCode()) . '<br>' . json_encode($kv->getResultMessage()) . '<br>' . json_encode($kv->getLastError());

            $echo[] = "<pre>json_encode(\$kv->delete('test'));</pre>" . json_encode($kv->delete('test'));

            $echo[] = "<pre>json_encode(\$kv->replace('test', 'QAQ'));</pre>" . json_encode($kv->replace('test', 'QAQ'));

            $echo[] = "<pre>json_encode(\$kv->getResultCode());
json_encode(\$kv->getResultMessage());
json_encode(\$kv->getLastError());</pre>";
            $echo[] = json_encode($kv->getResultCode()) . '<br>' . json_encode($kv->getResultMessage()) . '<br>' . json_encode($kv->getLastError());

            $echo[] = "<pre>json_encode(\$kv->incr('test'));</pre>" . json_encode($kv->incr('test'));

            $echo[] = "<pre>json_encode(\$kv->getResultCode());
json_encode(\$kv->getResultMessage());
json_encode(\$kv->getLastError());</pre>";
            $echo[] = json_encode($kv->getResultCode()) . '<br>' . json_encode($kv->getResultMessage()) . '<br>' . json_encode($kv->getLastError());

            $echo[] = "<pre>json_encode(\$kv->get('test'));</pre>" . json_encode($kv->get('test'));

            $echo[] = "<pre>json_encode(\$kv->set('test', 666));</pre>" . json_encode($kv->set('test', 666));

            $echo[] = "<pre>json_encode(\$kv->incr('test'));</pre>" . json_encode($kv->incr('test'));

            $echo[] = "<pre>json_encode(\$kv->get('test'));</pre>" . json_encode($kv->get('test'));

            $echo[] = "<pre>json_encode(\$kv->decr('test', 10));</pre>" . json_encode($kv->decr('test', 10));

            $echo[] = "<pre>json_encode(\$kv->get('test'));</pre>" . json_encode($kv->get('test'));

            $echo[] = "<pre>json_encode(\$kv->replace('test', 111));</pre>" . json_encode($kv->replace('test', 111));

            $echo[] = "<pre>json_encode(\$kv->get('test'));</pre>" . json_encode($kv->get('test'));

            $echo[] = "<pre>json_encode(\$kv->replace('test', 'QAQ'));</pre>" . json_encode($kv->replace('test', 'QAQ'));

            $echo[] = "<pre>json_encode(\$kv->incr('test', 10));</pre>" . json_encode($kv->incr('test', 10));

            $echo[] = "<pre>json_encode(\$kv->getResultCode());
json_encode(\$kv->getResultMessage());
json_encode(\$kv->getLastError());</pre>";
            $echo[] = json_encode($kv->getResultCode()) . '<br>' . json_encode($kv->getResultMessage()) . '<br>' . json_encode($kv->getLastError());

            $echo[] = "<pre>json_encode(\$kv->get('test'));</pre>" . json_encode($kv->get('test'));

            $echo[] = "<pre>json_encode(\$kv->getResultCode());
json_encode(\$kv->getResultMessage());
json_encode(\$kv->getLastError());</pre>";
            $echo[] = json_encode($kv->getResultCode()) . '<br>' . json_encode($kv->getResultMessage()) . '<br>' . json_encode($kv->getLastError());
        } else if ($ac === 'append-prepend') {
            $echo[] = "<pre>json_encode(\$kv->prepend('test', '0'));</pre>" . json_encode($kv->prepend('test', '0'));

            $echo[] = "<pre>json_encode(\$kv->get('test'));</pre>" . json_encode($kv->get('test'));

            $echo[] = "<pre>json_encode(\$kv->set('test', 'bbb'));</pre>" . json_encode($kv->set('test', 'bbb'));

            $echo[] = "<pre>json_encode(\$kv->append('test', 'end'));</pre>" . json_encode($kv->append('test', 'end'));

            $echo[] = "<pre>json_encode(\$kv->get('test'));</pre>" . json_encode($kv->get('test'));

            $echo[] = "<pre>json_encode(\$kv->prepend('test', 'pre'));</pre>" . json_encode($kv->prepend('test', 'pre'));

            $echo[] = "<pre>json_encode(\$kv->get('test'));</pre>" . json_encode($kv->get('test'));

            $echo[] = "<pre>json_encode(\$kv->add('test', 'aaa'));</pre>" . json_encode($kv->add('test', 'aaa'));

            $echo[] = "<pre>json_encode(\$kv->append('tmp_test', 'hehe'));</pre>" . json_encode($kv->append('tmp_test', 'hehe'));

            $echo[] = "<pre>json_encode(\$kv->getResultCode());
json_encode(\$kv->getResultMessage());
json_encode(\$kv->getLastError());</pre>";
            $echo[] = json_encode($kv->getResultCode()) . '<br>' . json_encode($kv->getResultMessage()) . '<br>' . json_encode($kv->getLastError());

            $echo[] = "<pre>json_encode(\$kv->get('tmp_test'));</pre>" . json_encode($kv->get('tmp_test'));

            $echo[] = "<pre>json_encode(\$kv->delete('tmp_test'));</pre>" . json_encode($kv->delete('tmp_test'));
        } else if ($ac === 'hash') {
            $echo[] = "<pre>json_encode(\$kv->hSet('hTest', 'name', 'Cheng Xin'));</pre>" . json_encode($kv->hSet('hTest', 'name', 'Cheng Xin'));

            $echo[] = "<pre>json_encode(\$kv->hSet('hTest', 'age', '16', 'nx'));</pre>" . json_encode($kv->hSet('hTest', 'age', '16', 'nx'));

            $echo[] = "<pre>json_encode(\$kv->hMSet('hTest', [
    'age' => '16',
    'sex' => 'female'
]));</pre>";
            $echo[] = json_encode($kv->hMSet('hTest', [
                'age' => '16',
                'sex' => 'female'
            ]));

            $echo[] = "<pre>json_encode(\$kv->hSet('hTest', 'age', '16', 'nx'));</pre>" . json_encode($kv->hSet('hTest', 'age', '16', 'nx'));

            $echo[] = "<pre>json_encode(\$kv->hGet('hTest', 'name'));</pre>" . json_encode($kv->hGet('hTest', 'name'));

            $echo[] = "<pre>json_encode(\$kv->hDel('hTest', 'name'));</pre>" . json_encode($kv->hDel('hTest', 'name'));

            $echo[] = "<pre>json_encode(\$kv->hGetAll('hTest'));</pre>" . json_encode($kv->hGetAll('hTest'));

            $echo[] = "<pre>json_encode(\$kv->hKeys('hTest'));</pre>" . json_encode($kv->hKeys('hTest'));

            $echo[] = "<pre>json_encode(\$kv->hExists('hTest', 'age'));</pre>" . json_encode($kv->hExists('hTest', 'age'));

            $echo[] = "<pre>json_encode(\$kv->hMGet('hTest', ['age', 'sex', 'school']));</pre>" . json_encode($kv->hMGet('hTest', ['age', 'sex', 'school']));

            $echo[] = "<pre>json_encode(\$kv->delete('hTest'));</pre>" . json_encode($kv->delete('hTest'));

            $echo[] = "<pre>json_encode(\$kv->hGet('hTest', 'name'));</pre>" . json_encode($kv->hGet('hTest', 'name'));

            $echo[] = "<pre>json_encode(\$kv->hGetAll('hTest'));</pre>" . json_encode($kv->hGetAll('hTest'));
        } else if ($ac === 'other') {
            $echo[] = "<pre>for (\$i = 0; \$i < 50; ++\$i) {
    \$kv->add('t' . \$i, \$i, 10);
}
echo 'Added.';</pre>";
            for ($i = 0; $i < 50; ++$i) {
                $kv->add('t' . $i, $i, 10);
            }
            $echo[] = 'Added.';

            $echo[] = "<pre>json_encode(\$kv->getAllKeys());</pre>" . json_encode($kv->getAllKeys());

            $echo[] = "<pre>json_encode(\$kv->keys('t*'));</pre>" . json_encode($kv->keys('t*'));

            $echo[] = "<pre>json_encode(\$kv->scan());</pre>" . json_encode($kv->scan());

            $echo[] = "<pre>json_encode(\$kv->scan('*2*'));</pre>" . json_encode($kv->scan('*2*'));

            $echo[] = "<pre>json_encode(\$kv->scan('*'));</pre>" . json_encode($kv->scan('*'));
        } else {
            $echo[] = "<pre>json_encode(\$kv->exists(['test', 'heheda']));</pre>" . json_encode($kv->exists(['test', 'heheda']));

            $echo[] = "<pre>json_encode(\$kv->mget(['test', 'heheda']));</pre>" . json_encode($kv->mget(['test', 'heheda']));

            $echo[] = "<pre>json_encode(\$kv->getMulti(['test', 'heheda']));</pre>" . json_encode($kv->getMulti(['test', 'heheda']));

            $echo[] = "<pre>json_encode(\$kv->get('test'));</pre>" . json_encode($kv->get('test'));

            $echo[] = "<pre>json_encode(\$kv->set('test', \$value ? \$value : 'ok'));</pre>" . json_encode($kv->set('test', $value ? $value : 'ok'));

            $echo[] = "<pre>json_encode(\$kv->get('test'));</pre>" . json_encode($kv->get('test'));
        }

        return '<a href="'.URL_BASE.'test/kv?s='.$_GET['s'].'">Default</a> | ' .
            '<a href="'.URL_BASE.'test/kv?s='.$_GET['s'].'&value=aaa">Set "aaa"</a> | ' .
            '<a href="'.URL_BASE.'test/kv?s='.$_GET['s'].'&value=bbb">Set "bbb"</a> | ' .
            '<a href="'.URL_BASE.'test/kv?s='.$_GET['s'].'&ac=delete">Delete</a> | ' .
            '<a href="'.URL_BASE.'test/kv?s='.$_GET['s'].'&ac=incr-decr-replace">Incr/Decr/Replace</a> | ' .
            '<a href="'.URL_BASE.'test/kv?s='.$_GET['s'].'&ac=append-prepend">Append/Prepend</a> | ' .
            '<a href="'.URL_BASE.'test/kv?s='.$_GET['s'].'&ac=hash">Hash</a> | ' .
            '<a href="'.URL_BASE.'test/kv?s='.$_GET['s'].'&ac=other">Other</a> | ' .
            '<a href="'.URL_BASE.'test">Return</a>' . join('', $echo) . '<br><br>' . $this->_getEnd();
    }

    public function net() {
        $echo = [];

        $res = Net::get('https://cdn.jsdelivr.net/npm/deskrt/package.json');
        $echo[] = "<pre>Net::get('https://cdn.jsdelivr.net/npm/deskrt/package.json');</pre>
headers: <pre>" . json_encode($res->headers, JSON_PRETTY_PRINT) . "</pre>
content: <pre>" . $res->content . "</pre>
error: " . json_encode($res->error) . "<br>
errno: " . json_encode($res->errno) . "<br>
info: <pre>" . json_encode($res->info, JSON_PRETTY_PRINT) . "</pre>";

        return join('', $echo) . $this->_getEnd();
    }

    public function netPost() {
        $echo = [];

        $res = Net::post(URL_FULL . 'test/netPost1', ['a' => '1', 'b' => '2', 'c' => ['1', '2', '3']]);
        $echo[] = "<pre>Net::post('" . URL_FULL . "test/netPost1', ['a' => '1', 'b' => '2', 'c' => ['1', '2', '3']]);</pre>
headers: <pre>" . json_encode($res->headers, JSON_PRETTY_PRINT) . "</pre>
content: <pre>" . $res->content . "</pre>
error: " . json_encode($res->error) . "<br>
errno: " . json_encode($res->errno) . "<br>
info: <pre>" . json_encode($res->info, JSON_PRETTY_PRINT) . "</pre>";

        return join('', $echo) . $this->_getEnd();
    }

    public function netPost1() {
        return "\$_POST:\n\n" . json_encode($_POST) . "\n\nRequest headers:\n\n" . json_encode($this->_headers, JSON_PRETTY_PRINT) . "\n\nIP: " . $_SERVER['REMOTE_ADDR'];
    }

    public function netOpen() {
        $echo = [];

        $res = Net::open(URL_FULL . 'test/netPost1')->post()->data(['a' => '2', 'b' => '0', 'c' => ['0', '1', '3']])->request();
        $echo[] = "<pre>Net::open(URL_FULL . 'test/netPost1')->post()->data(['a' => '2', 'b' => '0', 'c' => ['0', '1', '3']])->request();</pre>
headers: <pre>" . json_encode($res->headers, JSON_PRETTY_PRINT) . "</pre>
content: <pre>" . $res->content . "</pre>";

        return join('', $echo) . $this->_getEnd();
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
        setcookie('test1', 'normal', $_SERVER['REQUEST_TIME'] + 10);
        setcookie('test2', 'baidu.com', $_SERVER['REQUEST_TIME'] + 20, '/', 'baidu.com');
        setcookie('test3', HOSTNAME, $_SERVER['REQUEST_TIME'] + 30, '/', HOSTNAME);
        setcookie('test4', '/ok/', $_SERVER['REQUEST_TIME'] + 40, '/ok/');
        setcookie('test5', 'secure', $_SERVER['REQUEST_TIME'] + 50, '', '', true);
        setcookie('test6', '0.1', $_SERVER['REQUEST_TIME'] + 40, '/', '0.1');
        setcookie('test7', 'localhost', $_SERVER['REQUEST_TIME'] + 30, '/', 'localhost');
        setcookie('test8', 'com', $_SERVER['REQUEST_TIME'] + 20, '/', 'com');
        setcookie('test9', 'com.cn', $_SERVER['REQUEST_TIME'] + 10, '/', 'com.cn');
        return "setcookie('test1', 'normal', \$_SERVER['REQUEST_TIME'] + 10);
setcookie('test2', 'baidu.com', \$_SERVER['REQUEST_TIME'] + 20, '/', 'baidu.com');
setcookie('test3', '" . HOSTNAME .  "', \$_SERVER['REQUEST_TIME'] + 30, '/', '" . HOSTNAME . "');
setcookie('test4', '/ok/', \$_SERVER['REQUEST_TIME'] + 40, '/ok/');
setcookie('test5', 'secure', \$_SERVER['REQUEST_TIME'] + 50, '', '', true);
setcookie('test6', '0.1', \$_SERVER['REQUEST_TIME'] + 40, '/', '0.1');
setcookie('test7', 'localhost', \$_SERVER['REQUEST_TIME'] + 30, '/', 'localhost');
setcookie('test8', 'com', \$_SERVER['REQUEST_TIME'] + 20, '/', 'com');
setcookie('test9', 'com.cn', \$_SERVER['REQUEST_TIME'] + 10, '/', 'com.cn');";
    }
    public function netCookie2() {
        return "\$_COOKIE: \n\n" . json_encode($_COOKIE, JSON_PRETTY_PRINT);
    }

    public function netSave() {
        $echo = [];

        $res = Net::get('https://github.com/MaiyunNET/Mutton/raw/master/README.md', [
            'follow' => true,
            'save' => LOG_PATH . 'test-must-remove.md'
        ]);
        $echo[] = "<pre>Net::get('https://github.com/MaiyunNET/Mutton/raw/master/README.md', [
    'follow' => true,
    'save' => LOG_PATH . 'test-must-remove.md'
]);</pre>
headers: <pre>" . json_encode($res->headers, JSON_PRETTY_PRINT) . "</pre>
content: <pre>" . $res->content . "</pre>
error: " . json_encode($res->error) . "<br>
errno: " . json_encode($res->errno) . "<br>
info: <pre>" . json_encode($res->info, JSON_PRETTY_PRINT) . "</pre>";

        return join('', $echo) . $this->_getEnd();
    }

    public function netReuse() {
        $echo = [];

        $echo[] = '<strong>Normal:</strong>';

        $time0 = microtime(true);
        Net::get('https://cdn.jsdelivr.net/npm/deskrt@2.0.10/package.json');
        $time1 = microtime(true);
        $echo[] = "<pre>Net::get('https://cdn.jsdelivr.net/npm/deskrt@2.0.10/package.json');</pre>" . round(($time1 - $time0) * 1000, 4) . 'ms.';

        $time0 = microtime(true);
        Net::get('https://cdn.jsdelivr.net/npm/deskrt@2.0.10/README.md');
        $time1 = microtime(true);
        $echo[] = "<pre>Net::get('https://cdn.jsdelivr.net/npm/deskrt@2.0.10/README.md');</pre>" . round(($time1 - $time0) * 1000, 4) . 'ms.';

        $time0 = microtime(true);
        Net::get('https://cdn.jsdelivr.net/npm/deskrt@2.0.10/LICENSE');
        $time1 = microtime(true);
        $echo[] = "<pre>Net::get('https://cdn.jsdelivr.net/npm/deskrt@2.0.10/LICENSE');</pre>" . round(($time1 - $time0) * 1000, 4) . 'ms.<hr>';

        $echo[] = '<strong>Reuse:</strong>';

        $time0 = microtime(true);
        Net::get('https://cdn.jsdelivr.net/npm/deskrt@2.0.10/package.json', ['reuse' => true]);
        $time1 = microtime(true);
        $echo[] = "<pre>Net::get('https://cdn.jsdelivr.net/npm/deskrt@2.0.10/package.json', ['reuse' => true]);</pre>" . round(($time1 - $time0) * 1000, 4) . 'ms.';

        $time0 = microtime(true);
        Net::get('https://cdn.jsdelivr.net/npm/deskrt@2.0.10/README.md', ['reuse' => true]);
        $time1 = microtime(true);
        $echo[] = "<pre>Net::get('https://cdn.jsdelivr.net/npm/deskrt@2.0.10/README.md', ['reuse' => true]);</pre>" . round(($time1 - $time0) * 1000, 4) . 'ms.';

        $time0 = microtime(true);
        Net::get('https://cdn.jsdelivr.net/npm/deskrt@2.0.10/LICENSE', ['reuse' => true]);
        $time1 = microtime(true);
        $echo[] = "<pre>Net::get('https://cdn.jsdelivr.net/npm/deskrt@2.0.10/LICENSE', ['reuse' => true]);</pre>" . round(($time1 - $time0) * 1000, 4) . 'ms.';

        Net::closeAll();
        $echo[] = "<pre>Net::closeAll();</pre>";

        return join('', $echo) . $this->_getEnd();
    }

    public function session() {
        if (!$this->_checkInput($_GET, [
            's' => ['require', ['db', 'kv'], [0, 'Object not found.']],
            'auth' => [['', '1'], [0, 'Bad request.']],
            'value' => []
        ], $return)) {
            return $return;
        }

        $echo = ['<pre>'];

        $link = null;
        if ($_GET['s'] === 'db') {
            $link = Db::get(Db::MYSQL);
            if (!$link->connect()) {
                return [0, 'Failed, MySQL can not be connected.'];
            }
            $echo[] = "\$link = Db::get(Db::MYSQL);\n";
        } else {
            $link = Kv::get(Kv::REDIS);
            if (!$link->connect()) {
                return [0, 'Failed, Redis can not be connected.'];
            }
            $echo[] = "\$link = Kv::get(Kv::REDIS);\n";
        }

        if ($_GET['auth'] === '') {
            $this->_startSession($link, false, ['ttl' => 60]);
            $echo[] = "\$this->_startSession(\$link, false, ['ttl' => 60]);
json_encode(\$_SESSION);</pre>" . htmlspecialchars(json_encode($_SESSION));

            $_SESSION['value'] = $_GET['value'] ? $_GET['value'] : 'ok';
            $echo[] = "<pre>\$_SESSION['value'] = '" . ($_GET['value'] ? $_GET['value'] : 'ok') . "';
json_encode(\$_SESSION);</pre>" . htmlspecialchars(json_encode($_SESSION));

            return '<a href="' . URL_BASE . 'test/session?s=' . $_GET['s'] . '">Default</a> | ' .
                '<a href="' . URL_BASE . 'test/session?s=' . $_GET['s'] . '&value=aaa">Set "aaa"</a> | ' .
                '<a href="' . URL_BASE . 'test/session?s=' . $_GET['s'] . '&value=bbb">Set "bbb"</a> | ' .
                '<a href="' . URL_BASE . 'test">Return</a>' . join('', $echo) . '<br><br>' . $this->_getEnd();
        } else {
            // --- AUTH 模式 ---
            $session = $this->_startSession($link, true, ['ttl' => 60]);
            if (count($_POST) > 0) {
                if (!isset($_SESSION['count'])) {
                    $_SESSION['count'] = 1;
                } else {
                    ++$_SESSION['count'];
                }
                return [1, 'txt' => "\$_SESSION: " . json_encode($_SESSION) . "\nToken: " . $session->getToken(), 'token' => $session->getToken(), '_auth' => $this->_getBasicAuth('token', $session->getToken())];
            } else {
                $echo[] = '<script>document.write((typeof fetch !== "function") ? "<script src=\\"https://cdn.jsdelivr.net/npm/whatwg-fetch@3.0.0/dist/fetch.umd.min.js\\">" : "")</script>';

                $echo[] = "\$this->_startSession(\$link, true, ['ttl' => 60]);
json_encode(\$_SESSION);</pre>" . htmlspecialchars(json_encode($_SESSION));

                $_SESSION['value'] = date('H:i:s');
                $echo[] = "<pre>\$_SESSION['value'] = '" . date('H:i:s') . "';
json_encode(\$_SESSION);</pre>" . htmlspecialchars(json_encode($_SESSION));

                $echo[] = "<br><br><input type=\"button\" value=\"Post with header\" onclick=\"document.getElementById('result').innerText='Waiting...';fetch('" . URL_BASE . "test/session?s=" . $_GET['s'] . "&auth=1',{method:'POST',headers:{'Authorization':document.getElementById('_auth').innerText,'Content-Type':'application/x-www-form-urlencoded'},body:'key=val'}).then(function(r){return r.json();}).then(function(j){document.getElementById('result').innerText=j.txt;document.getElementById('token').innerText=j.token;document.getElementById('_auth').innerText=j._auth;});\"><input type='button' value=\"Post without header\" style=\"margin-left: 10px;\" onclick=\"document.getElementById('result').innerText='Waiting...';fetch('" . URL_BASE . "test/session?s=" . $_GET['s'] . "&auth=1',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'key=val'}).then(function(r){return r.json();}).then(function(j){document.getElementById('result').innerText=j.txt;});\"><br><br>
Token: <span id=\"token\">" . $session->getToken() . "</span><br>
Post Authorization header: <span id=\"_auth\">" . $this->_getBasicAuth('token', $session->getToken()) . "</span><br><br>
Result:<pre id=\"result\">Nothing.</pre>";

                return '<a href="' . URL_BASE . 'test">Return</a>' . join('', $echo) . $this->_getEnd();
            }
        }
    }

    public function sql() {
        $echo = [];
        $sql = Sql::get('test_');
        switch ($_GET['type']) {
            case 'insert': {
                $s = $sql->insert('user')->values(['name', 'age'], [
                    ['Ah', '16'],
                    ['Bob', '24']
                ])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->insert('user')->values(['name', 'age'], [
    ['Ah', '16'],
    ['Bob', '24']
]);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) ."</pre>
<b>format() :</b> " . $sql->format($s, $sd) . '<hr>';

                $s = $sql->insert('user')->values(['name', 'age'], ['Ah', '16'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->insert('user')->values(['name', 'age'], ['Ah', '16']);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . '<hr>';

                $s = $sql->insert('user')->values(['name' => 'Bob', 'age' => '24'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->insert('user')->values(['name' => 'Bob', 'age' => '24']);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . '<hr>';

                $s = $sql->replace('user')->values(['token' => '20200202', 'name' => 'Bob'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->replace('user')->values(['token' => '20200202', 'name' => 'Bob']);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . '<hr>';

                $s = $sql->insert('order')->notExists('order', ['name' => 'Amy', 'age' => '16', 'time_add' => time()], ['name' => 'Amy'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->insert('user')->notExists('order', ['name' => 'Amy', 'age' => '16', 'time_add' => time()], ['name' => 'Amy']);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . '<hr>';

                $s = $sql->insert('verify')->values(['token' => 'abc', 'time_update' => '10'])->duplicate(['time_update' => '#CONCAT(`time_update`, ' . Sql::data('01') . ')'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->insert('verify')->values(['token' => 'abc', 'time_update' => '10'})->duplicate(['time_update' => '#CONCAT(`time_update`, ' . Sql::data('01') . ')']);</pre>
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

                $s = $sql->select(['o.no', 'u.nick'], ['order o'])->leftJoin('`user` AS u', ['o.user_id' => '#u.id', 'state' => '1'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->select(['o.no', 'u.nick'], ['order o'])->leftJoin('`user` AS u', ['o.user_id' => '#u.id', 'state' => '1'])</pre>
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

                $s = $sql->update('user', ['name' => 'Serene', 'type' => '#(CASE `id` WHEN 1 THEN ' . $sql->data('val1') . ' WHEN 2 THEN ' . $sql->data('val2') . ' END)'])->where(['name' => 'Ah'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->update('user', ['name' => 'Serene', 'type' => '#(CASE `id` WHEN 1 THEN ' . \$sql->data('val1') . ' WHEN 2 THEN ' . \$sql->data('val2') . ' END)'])->where(['name' => 'Ah']);</pre>
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

    public function text() {
        $echo = "<pre>json_encode(Text::parseUrl('HtTp://uSer:pAss@sUBDom.TopdOm23.CoM:29819/Admxw2Ksiz/dszas?Mdi=KdiMs1&a=JDd#hehHe'))</pre>
" . htmlspecialchars(json_encode(Text::parseUrl('HtTp://uSer:pAss@sUBDom.TopdOm23.CoM:29819/Admxw2Ksiz/dszas?Mdi=KdiMs1&a=JDd#hehHe'))) . "
<pre>json_encode(Text::parseUrl('HtTp://uSer@sUBDom.TopdOm23.CoM/Admxw2Ksiz/dszas'))</pre>
" . htmlspecialchars(json_encode(Text::parseUrl('HtTp://uSer@sUBDom.TopdOm23.CoM/Admxw2Ksiz/dszas'))) . "
<pre>Text::urlResolve('/', 'path?id=1');</pre>
" . htmlspecialchars(Text::urlResolve('/', 'path?id=1')) . "
<pre>Text::urlResolve('https://www.url.com/view/path', 'find');</pre>
" . htmlspecialchars(Text::urlResolve('https://www.url.com/view/path', 'find')) . "
<pre>Text::urlResolve('/', '//www.url.com/path');</pre>
" . htmlspecialchars(Text::urlResolve('/', '//www.url.com/path')) . "
<pre>Text::urlResolve('http://www.url.com/path', 'https://www.url.com/path');</pre>
" . htmlspecialchars(Text::urlResolve('http://www.url.com/path', 'https://www.url.com/path')) . "
<pre>Text::urlResolve('http://www.url.com/path?ok=b', '?do=some');</pre>
" . htmlspecialchars(Text::urlResolve('http://www.url.com/path?ok=b', '?do=some')) . "
<pre>Text::urlResolve('/abc/def/', '');</pre>
" . htmlspecialchars(Text::urlResolve('/abc/def/', '')) . "
<pre>Text::isEMail('test@gmail.com');</pre>
" . json_encode(Text::isEMail('test@gmail.com')) . "
<pre>Text::isEMail('test@x');</pre>
" . json_encode(Text::isEMail('test@x')) . "
<pre>Text::isIPv4('192.168.0.1');</pre>
" . json_encode(Text::isIPv4('192.168.0.1')) . "
<pre>Text::isIPv4('192.168.0');</pre>
" . json_encode(Text::isIPv4('192.168.0')) . "
<pre>Text::isIPv6(':');</pre>
" . json_encode(Text::isIPv6(':')) . "
<pre>Text::isIPv6('::');</pre>
" . json_encode(Text::isIPv6('::')) . "
<pre>Text::isIPv6('::1');</pre>
" . json_encode(Text::isIPv6('::1')) . "
<pre>Text::isIPv6('::FFFF:C0A8:0201');</pre>
" . json_encode(Text::isIPv6('::FFFF:C0A8:0201')) . "
<pre>Text::isIPv6('2031:0000:1F1F:0000:0000:0100:11A0:ADDF');</pre>
" . json_encode(Text::isIPv6('2031:0000:1F1F:0000:0000:0100:11A0:ADDF')) . "
<pre>Text::isIPv6('2031:0000:1F1F:0000:0000:0100:11A0:ADDF:AZ');</pre>
" . json_encode(Text::isIPv6('2031:0000:1F1F:0000:0000:0100:11A0:ADDF:AZ')) . "
<pre>Text::isIPv6('::FFFF:192.168.0.1');</pre>
" . json_encode(Text::isIPv6('::FFFF:192.168.0.1')) . "
<pre>Text::isDomain('::FFFF:192.168.0.1');</pre>
" . json_encode(Text::isDomain('::FFFF:192.168.0.1')) . "
<pre>Text::isDomain('www.xxx.com.cn');</pre>
" . json_encode(Text::isDomain('www.xxx.com.cn')) . "
<pre>Text::isDomain('com');</pre>
" . json_encode(Text::isDomain('com')) . "
<pre>Text::parseDomain('www.xxx.com.cn');</pre>
" . json_encode(Text::parseDomain('www.xxx.com.cn')) . "
<pre>Text::parseDomain('www.xxx.us');</pre>
" . json_encode(Text::parseDomain('www.xxx.us')) . "
<pre>Text::parseDomain('xxx.co.jp');</pre>
" . json_encode(Text::parseDomain('xxx.co.jp')) . "
<pre>Text::parseDomain('js.cn');</pre>
" . json_encode(Text::parseDomain('js.cn')) . "
<pre>Text::parseDomain('xxx.cn');</pre>
" . json_encode(Text::parseDomain('xxx.cn'));
        return $echo . '<br><br>' . $this->_getEnd();
    }

    // --- END ---
    private function _getEnd(): string {
        $rt = $this->_getRunTime();
        return 'Processed in ' . $rt . ' second(s), ' . round($rt * 1000, 4) . 'ms, ' . round($this->_getMemoryUsage() / 1024, 2) . ' K.<style>*{font-family:Consolas,"Courier New",Courier,FreeMono,monospace;line-height: 1.5;font-size:12px;}pre{padding: 10px;background-color:rgba(0,0,0,.07);}hr{margin:20px 0;border-color:#000;border-style:dashed;border-width:1px 0 0 0;}td,th{padding:5px;border:solid 1px #000;}</style>';
    }

}

