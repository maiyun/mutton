<?php
declare(strict_types = 1);

namespace ctr;

use lib\Crypto;
use lib\Captcha;
use lib\Consistent;
use lib\Core;
use lib\Db;
use lib\Db\Stmt;
use lib\Jwt;
use lib\Kv;
use lib\Kv\IKv;
use lib\Net;
use lib\Scan;
use lib\Sql;
use lib\Text;
use mod\Mod;
use mod\Test as ModTest;
use mod\TestData;
use PDO;
use sys\Ctr;

class Test extends Ctr {

    private $_internalUrl = URL_FULL;

    public function onLoad() {
        if (HOSTNAME !== '127.0.0.1' && HOSTNAME !== '172.17.0.1' && HOSTNAME !== 'localhost' && HOSTNAME !== 'local-test.brc-app.com' && substr(HOSTNAME, 0, 8) !== '192.168.') {
            return [0, 'Please use 127.0.0.1 to access the file (' . HOST . ').'];
        }
        $realIp = Core::realIP();
        if ((HOSTNAME === '127.0.0.1' || HOSTNAME === 'localhost') && ($realIp === '172.17.0.1')) {
            $this->_internalUrl = 'http' . (HTTPS ? 's' : '') . '://' . $realIp . URL_BASE;
        }
        return true;
    }

    public function onUnload($rtn) {
        if (!is_array($rtn)) {
            return $rtn;
        }
        if ($rtn[0] !== -102) {
            return $rtn;
        }
        $rtn['test'] = 'unload';
        return $rtn;
    }

    public function notfound() {
        // --- Set on route.php ---
        $this->_httpCode = 404;
        return 'Custom 404 page.';
    }

    public function index() {
        $echo = [
            'Hello world! Welcome to use <strong>Mutton ' . VER . '</strong>!',
            
            '<br><br>PHP version: ' . PHP_VERSION,
            '<br>HOST: ' . HOST,
            '<br>HOSTNAME: ' . HOSTNAME,
            '<br>HOSTPORT: ' . HOSTPORT,
            '<br>PATH: ' . PATH,
            '<br>QS: ' . QS,
            '<br>HTTPS: ' . (HTTPS ? 'true' : 'false'),

            '<br><br>MOBILE: ' . (MOBILE ? 'true' : 'false'),
            '<br>Real IP: ' . Core::ip(),
            '<br>Client IP: ' . Core::realIP(),

            '<br><br>URL_BASE: ' . URL_BASE,
            '<br>URL_STC: ' . URL_STC,
            '<br>URL_FULL: ' . URL_FULL,
            '<br>STATIC_PATH: ' . STATIC_PATH,
            '<br>STATIC_PATH_FULL: ' . STATIC_PATH_FULL,
            '<br>$_internalUrl: ' . $this->_internalUrl,

            '<br><br>headers: ' . htmlspecialchars(json_encode($this->_headers)),

            '<br><br><b style="color: red;">Tips: The file can be deleted.</b>',

            '<br><br><b>Route (etc/set.php):</b>',
            '<br><br><a href="' . URL_BASE . 'article/123">View "article/123"</a>',
            '<br><a href="' . URL_BASE . 'article/456">View "article/456"</a>',

            '<br><br><b>Query string:</b>',
            '<br><br><a href="' . URL_BASE . 'test/qs?a=1&b=2">View "test/qs?a=1&b=2"</a>',

            '<br><br><b>View:</b>',
            '<br><br><a href="' . URL_BASE . 'test/view">View "test/view"</a>',

            '<br><br><b>Return json:</b>',
            '<br><br><a href="' . URL_BASE . 'test/json?type=1">View "test/json?type=1"</a>',
            '<br><a href="' . URL_BASE . 'test/json?type=2">View "test/json?type=2"</a>',
            '<br><a href="' . URL_BASE . 'test/json?type=3">View "test/json?type=3"</a>',
            '<br><a href="' . URL_BASE . 'test/json?type=4">View "test/json?type=4"</a>',
            '<br><a href="' . URL_BASE . 'test/json?type=5">View "test/json?type=5"</a>',
            '<br><a href="' . URL_BASE . 'test/json?type=6">View "test/json?type=6"</a>',
            '<br><a href="' . URL_BASE . 'test/json?type=7">View "test/json?type=7"</a>',
            '<br><a href="' . URL_BASE . 'test/json?type=8">View "test/json?type=8"</a>',
            '<br><a href="' . URL_BASE . 'test/json?type=9">View "test/json?type=9"</a>',

            '<br><br><b>Ctr:</b>',
            '<br><br><a href="' . URL_BASE . 'test/ctr-xsrf">View "test/ctr-xsrf"</a>',
            '<br><a href="' . URL_BASE . 'test/ctr-checkinput">View "test/ctr-checkinput"</a>',
            '<br><a href="' . URL_BASE . 'test/ctr-locale">View "test/ctr-locale"</a>',
            '<br><a href="' . URL_BASE . 'test/ctr-cachettl">View "test/ctr-cachettl"</a>',
            '<br><a href="' . URL_BASE . 'test/ctr-httpcode">View "test/ctr-httpcode"</a>',
            '<br><a href="' . URL_BASE . 'test/ctr-cross">View "test/ctr-cross"</a>',

            '<br><br><b>Middle:</b>',
            '<br><br><a href="' . URL_BASE . 'test/middle">View "test/middle"</a>',

            '<br><br><b>Model test:</b>',

            '<br><br><b style="color: red;">In a production environment, please delete "mod/test.php", "mod/testdata.php" files.</b>',
            '<br><a href="' . URL_BASE . 'test/mod-test">Click to see an example of a Test model</a>',
            '<br><a href="' . URL_BASE . 'test/mod-split">View "test/mod-split"</a>',

            '<br><br><b>Library test:</b>',

            '<br><br><b>Captcha:</b>',
            '<br><br><a href="' . URL_BASE . 'test/captcha-fastbuild">View "test/captcha-fastbuild"</a>',
            '<br><a href="' . URL_BASE . 'test/captcha-base64">View "test/captcha-base64"</a>',

            '<br><br><b>Core:</b>',
            '<br><br><a href="' . URL_BASE . 'test/core-random">View "test/core-random"</a>',
            '<br><a href="' . URL_BASE . 'test/core-rand">View "test/core-rand"</a>',
            '<br><a href="' . URL_BASE . 'test/core-convert62">View "test/core-convert62"</a>',
            '<br><a href="' . URL_BASE . 'test/core-purify">View "test/core-purify"</a>',
            '<br><a href="' . URL_BASE . 'test/core-muid">View "test/core-muid"</a>',

            '<br><br><b>Crypto:</b>',
            '<br><br><a href="' . URL_BASE . 'test/crypto">View "test/crypto"</a>',

            '<br><br><b>Db:</b>',
            '<br><br><a href="' . URL_BASE . 'test/db">View "test/db"</a>',

            '<br><br><b>Kv:</b>',
            '<br><br><a href="' . URL_BASE . 'test/kv?s=redis">View "test/kv?s=redis"</a>',
            '<br><a href="' . URL_BASE . 'test/kv?s=redis-simulator">View "test/kv?s=redis-simulator"</a>',

            '<br><br><b>Net:</b>',
            '<br><br><a href="' . URL_BASE . 'test/net">View "test/net"</a>',
            '<br><a href="' . URL_BASE . 'test/net-post">View "test/net-post"</a>',
            '<br><a href="' . URL_BASE . 'test/net-post-string">View "test/net-post-string"</a>',
            '<br><a href="' . URL_BASE .'test/net-open">View "test/net-open"</a>',
            '<br><a href="' . URL_BASE .'test/net-form-test">View "test/net-form-test"</a>',
            '<br><a href="' . URL_BASE . 'test/net-upload">View "test/net-upload"</a>',
            '<br><a href="' . URL_BASE . 'test/net-cookie">View "test/net-cookie"</a>',
            '<br><a href="' . URL_BASE . 'test/net-save">View "test/net-save"</a>',
            '<br><a href="' . URL_BASE . 'test/net-follow">View "test/net-follow"</a>',
            '<br><a href="' . URL_BASE . 'test/net-reuse">View "test/net-reuse"</a>',
            '<br><a href="' . URL_BASE . 'test/net-error">View "test/net-error"</a>',
            '<br><a href="' . URL_BASE . 'test/net-hosts">View "test/net-hosts"</a>',
            '<br><a href="' . URL_BASE . 'test/net-rproxy/dist/core.js">View "test/net-rproxy/dist/core.js"</a> <a href="' . URL_BASE . 'test/net-rproxy/package.json">View "package.json"</a>',
            '<br><a href="' . URL_BASE . 'test/net-mproxy">View "test/net-mproxy"</a>',

            '<br><br><b>Scan</b>',
            '<br><br><a href="' . URL_BASE . 'test/scan?s=db">View "test/scan?s=db"</a>',
            '<br><a href="' . URL_BASE . 'test/scan?s=kv">View "test/scan?s=kv"</a>',

            '<br><br><b>Session:</b>',
            '<br><br><a href="' . URL_BASE . 'test/session?s=db">View "test/session?s=db"</a>',
            '<br><a href="' . URL_BASE . 'test/session?s=kv">View "test/session?s=kv"</a>',
            '<br><a href="' . URL_BASE . 'test/session?s=db&auth=1">View "test/session?s=db&auth=1" Header Authorization</a>',
            '<br><a href="' . URL_BASE . 'test/session?s=kv&auth=1">View "test/session?s=kv&auth=1" Header Authorization</a>',

            '<br><br><b>Jwt:</b>',
            '<br><br><a href="' . URL_BASE . 'test/jwt">View "test/jwt"</a>',
            '<br><a href="' . URL_BASE . 'test/jwt?type=kv">View "test/jwt?type=kv"</a>',
            '<br><a href="' . URL_BASE . 'test/jwt?type=auth">View "test/jwt?type=auth" Header Authorization</a>',

            '<br><br><b>Sql:</b>',
            '<br><br><a href="' . URL_BASE . 'test/sql?type=insert">View "test/sql?type=insert"</a>',
            '<br><a href="' . URL_BASE . 'test/sql?type=select">View "test/sql?type=select"</a>',
            '<br><a href="' . URL_BASE . 'test/sql?type=update">View "test/sql?type=update"</a>',
            '<br><a href="' . URL_BASE . 'test/sql?type=delete">View "test/sql?type=delete"</a>',
            '<br><a href="' . URL_BASE . 'test/sql?type=where">View "test/sql?type=where"</a>',
            '<br><a href="' . URL_BASE . 'test/sql?type=having">View "test/sql?type=having"</a>',
            '<br><a href="' . URL_BASE . 'test/sql?type=by">View "test/sql?type=by"</a>',
            '<br><a href="' . URL_BASE . 'test/sql?type=field">View "test/sql?type=field"</a>',

            '<br><br><b>Consistent:</b>',
            '<br><br><a href="' . URL_BASE . 'test/consistent-hash">View "test/consistent-hash"</a>',
            '<br><a href="' . URL_BASE . 'test/consistent-distributed">View "test/consistent-distributed"</a>',
            '<br><a href="' . URL_BASE . 'test/consistent-migration">View "test/consistent-migration"</a>',
            '<br><a href="' . URL_BASE . 'test/consistent-fast">View "test/consistent-fast"</a>',

            '<br><br><b>Text:</b>',
            '<br><br><a href="' . URL_BASE . 'test/text">View "test/text"</a>'
        ];
        $echo[] = '<br><br>' . $this->_getEnd();

        return join('', $echo);
    }

    public function article() {
        return 'Article ID: ' . htmlspecialchars($this->_param[0]) . '<br><br>' . $this->_getEnd();
    }

    public function qs() {
        return 'json_encode($_GET):<br><br>' . htmlspecialchars(json_encode($_GET)) . '<br><br>' . $this->_getEnd();
    }

    public function view() {
        return $this->_loadView('test', [
            'test' => 'ok'
        ]);
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
            case '7':
                return [1, 'success', 'list' => [1, 2, 3]];
            case '8':
                return [-101, 'Test middle onUnload'];
            case '9':
                return [-102, 'Test ctr onUnload'];
            default:
                return [];
        }
    }

    public function ctrXsrf() {
        $this->_enabledXsrf();
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

    public function ctrCheckinput() {
        $echo = ["rule:
<pre>[
    'he' => ['require', [0, 'The he param does not exist.']],
    'num' => ['> 10', [0, 'The num param must > 10.']],
    'reg' => ['/^[A-CX-Z5-7]+$/', [0, 'The reg param is incorrect.']],
    'arr' => [['a', 'x', 'hehe'], [0, 'The arr param is incorrect.']]
]</pre>"];

        $post = [
            [],
            [
                'he' => 'ok'
            ],
            [
                'he' => 'ok',
                'num' => '5'
            ],
            [
                'he' => 'ok',
                'num' => '12',
                'reg' => 'Hello'
            ],
            [
                'he' => 'ok',
                'num' => '12',
                'reg' => 'BBB6YYY6',
                'arr' => 'heihei'
            ],
            [
                'he' => 'ok',
                'num' => '12',
                'reg' => 'BBB6YYY6',
                'arr' => 'hehe'
            ]
        ];
        foreach ($post as $item) {
            $p = http_build_query($item);
            $echo[] = "<input type=\"button\" value=\"Post '" . $p . "'\" onclick=\"post('" . $p . "')\"><br>";
        }

        $echo[] = "<input type=\"button\" value=\"Post FormData (fd.append('he', 'ho'))\" onclick=\"postFd()\"><br>";

        $echo[] = "<script>
function post(p) {
    document.getElementById('result').innerText = 'Waiting...';
    fetch('" . URL_BASE . "test/ctr-checkinput1', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: p
    }).then(function(r) {
        return r.text();
    }).then(function(t) {
        document.getElementById('result').innerText = t;
    });
}

function postFd() {
    var fd = new FormData();
    fd.append('he', 'ho');
    document.getElementById('result').innerText = 'Waiting...';
    fetch('" . URL_BASE . "test/ctr-checkinput1', {
        method: 'POST',
        body: fd
    }).then(function(r) {
        return r.text();
    }).then(function(t) {
        document.getElementById('result').innerText = t;
    });
}
</script>
<br>Result:<pre id=\"result\">Nothing.</pre>";

        return join('', $echo) . $this->_getEnd();
    }
    public function ctrCheckinput1() {
        if (!$this->_checkInput($_POST, [
            'he' => ['require', [0, 'The he param does not exist.']],
            'num' => ['> 10', [0, 'The num param must > 10.']],
            'reg' => ['/^[A-CX-Z5-7]+$/', [0, 'The reg param is incorrect.']],
            'arr' => [['a', 'x', 'hehe'], [0, 'The arr param is incorrect.']]
        ], $return)) {
            return $return;
        }
        return [1, 'post' => $_POST];
    }

    public function ctrLocale() {
        if (!$this->_checkInput($_GET, [
            'lang' => [['en', 'sc', 'tc', 'ja'], [0, 'Wrong language.']]
        ], $rtn)) {
            return $rtn;
        }

        $echo = [
            '<a href="' . URL_BASE . 'test/ctr-locale">English</a> | ' .
            '<a href="' . URL_BASE . 'test/ctr-locale?lang=sc">简体中文</a> | ' .
            '<a href="' . URL_BASE . 'test/ctr-locale?lang=tc">繁體中文</a> | ' .
            '<a href="' . URL_BASE . 'test/ctr-locale?lang=ja">日本語</a> | ' .
            '<a href="' . URL_BASE . 'test">Return</a>'
        ];

        $r = $this->_loadLocale($_GET['lang'], 'test');
        $echo[] = "<pre>\$this->_loadLocale(\$_GET['lang'], 'test');</pre>" . ($r ? 'true' : 'false');

        $echo[] = "<pre>l('hello')</pre>" . l('hello');
        $echo[] = "<pre>l('copy')</pre>" . l('copy');
        $echo[] = "<pre>l('test', ['a1', 'a2'])</pre>" . l('test', ['a1', 'a2']);

        return join('', $echo) . '<br><br>' . $this->_getEnd();
    }

    public function ctrCachettl() {
        $this->_cacheTTL = 60;
        return 'This page is cache ttl is 60s.';
    }

    public function ctrHttpcode() {
        $this->_httpCode = 404;
        return 'This page is a custom httpcode (404).';
    }

    public function ctrCross() {
        return "<input type=\"button\" value=\"Fetch localhost\" onclick=\"document.getElementById('result').innerText='Waiting...';fetch('http://localhost" . URL_BASE . "test/ctr-cross1').then(function(r){return r.text();}).then(function(t){document.getElementById('result').innerText=t;}).catch(()=>{document.getElementById('result').innerText='Failed.';});\">
<input type='button' value=\"Fetch localhost with cross\" style=\"margin-left: 10px;\" onclick=\"document.getElementById('result').innerText='Waiting...';fetch('http://localhost" . URL_BASE . "test/ctr-cross2').then(function(r){return r.text();}).then(function(t){document.getElementById('result').innerText=t;});\">
<input type='button' value=\"Fetch local-test.brc-app.com with cross\" style=\"margin-left: 10px;\" onclick=\"document.getElementById('result').innerText='Waiting...';fetch('http://local-test.brc-app.com" . URL_BASE . "test/ctr-cross2').then(function(r){return r.text();}).then(function(t){document.getElementById('result').innerText=t;});\"><br><br>
Result:<pre id=\"result\">Nothing.</pre>" . $this->_getEnd();
    }
    public function ctrCross1() {
        return [1, 'value' => 'done'];
    }
    public function ctrCross2() {
        if (!$this->_cross()) {
            return [0];
        }
        return [1, 'value' => 'done'];
    }

    public function modTest() {
        if (!($this->_checkInput($_GET, [
            'action' => [['', 'remove'], [0, 'Error']]
        ], $return))) {
            return $return;
        }

        $echo = ['<b style="color: red;">In a production environment, please delete the "mod/test.php" file.</b>'];

        $db = Db::get();
        if (!($rtn = $db->connect())) {
            return [0 ,'Failed('.($rtn === null ? 'null' : 'false').').'];
        }

        if (!($stmt = $db->query('SELECT * FROM `m_test` WHERE `token` LIMIT 1;'))) {
            return [0 ,'Failed("m_test" not found).'];
        }

        Mod::setDb($db);

        if ($_GET['action'] === 'remove') {
            ModTest::removeByWhere([
                ['token', 'LIKE', 'test_%']
            ]);
            return $this->_location('test/mod-test');
        }
        else {
            $time = time();
            $test = ModTest::getCreate();
            $test->set([
                'point' => [ 'x' => rand(0, 99), 'y' => rand(0, 99) ],
                'polygon' => [
                    [
                        [ 'x' => 1, 'y' => 1 ],
                        [ 'x' => 2, 'y' => 2 ],
                        [ 'x' => 3, 'y' => 3 ],
                        [ 'x' => 1, 'y' => 1 ]
                    ]
                ],
                'json' => [ 'x' => [ 'y' => 'abc' ] ],
                'time_add' => $time
            ]);
            $result = $test->create();

            $echo[] = "<pre>Mod::setDb(\$db);
\$time = time();
\$test = ModTest::getCreate();
\$test->set([
    'point' => [ 'x' => rand(0, 99), 'y' => rand(0, 99) ],
    'polygon' => [
        [
            [ 'x' => 1, 'y' => 1 ],
            [ 'x' => 2, 'y' => 2 ],
            [ 'x' => 3, 'y' => 3 ],
            [ 'x' => 1, 'y' => 1 ]
        ]
    ],
    'json' => [ 'x' => [ 'y' => 'abc' ] ],
    'time_add' => $time
]);
\$result = \$test->create();
json_encode(\$result);</pre>" . json_encode($result);

            $echo[] = "<pre>json_encode(\$test->toArray());</pre>" . htmlspecialchars(json_encode($test->toArray()));

            $echo[] = "<br><br>Test table:";

            $stmt = $db->query('SELECT * FROM `m_test` WHERE `token` LIKE \'test_%\' ORDER BY `id` ASC;');
            $this->_dbTable($stmt, $echo);

            // --- explain ---

            $ls = ModTest::where([
                ['time_add', '>', $time - 60 * 5]
            ]);
            $r = $ls->explain();
            $echo[] = "<pre>\$ls = ModTest::where([
    ['time_add', '>', time() - 60 * 5]
]);
\$ls->explain();</pre>" . htmlspecialchars(json_encode($r));

            $r2 = $ls->explain(true);
            $echo[] = '<pre>$ls->explain(true);</pre>';
            if ($r2) {
                $echo[] = '<table style="width: 100%;">';
                foreach ($r2 as $k => $v) {
                    $echo[] = '<tr><th>' . htmlspecialchars($k) . '</th><td>' . ($v === null ? 'null' : htmlspecialchars($v . '')) . '</td></tr>';
                }
                $echo[] = '</table>';
            }
            else {
                $echo[] = '<div>false</div>';
            }

            $ft = ModTest::one([
                ['time_add', '>', '0']
            ]);
            $echo[] = "<pre>ModTest::one([
    ['time_add', '>', '0]
]);</pre>";
            if ($ft) {
                $echo[] = '<table style="width: 100%;">';

                $echo[] = '<tr><th>id</th><td>' . $ft->id . '</td></tr>';
                $echo[] = '<tr><th>token</th><td>' . $ft->token . '</td></tr>';
                $echo[] = '<tr><th>point</th><td>' . json_encode($ft->point) . '</td></tr>';
                $echo[] = '<tr><th>polygon</th><td>' . json_encode($ft->polygon) . '</td></tr>';
                $echo[] = '<tr><th>json</th><td>' . json_encode($ft->json) . '</td></tr>';
                $echo[] = '<tr><th>time_add</th><td>' . $ft->time_add . '</td></tr>';

                $echo[] = '</table>';

                // --- 修改 point 值 ---

                $ft->set('point', [
                    'x' => 20,
                    'y' => 20
                ]);
                $ft->save();
                $echo[] = "<pre>\$ft->set('point', [
    'x' => 20,
    'y' => 20
]);
\$ft->save();</pre>";

                $ft = ModTest::find($ft->id);
                if (!$ft) {
                    return '';
                }

                $echo[] = '<table style="width: 100%;">';

                $echo[] = '<tr><th>id</th><td>' . $ft->id . '</td></tr>';
                $echo[] = '<tr><th>token</th><td>' . $ft->token . '</td></tr>';
                $echo[] = '<tr><th>point</th><td>' . json_encode($ft->point) . '</td></tr>';
                $echo[] = '<tr><th>polygon</th><td>' . json_encode($ft->polygon) . '</td></tr>';
                $echo[] = '<tr><th>json</th><td>' . json_encode($ft->json) . '</td></tr>';
                $echo[] = '<tr><th>time_add</th><td>' . $ft->time_add . '</td></tr>';

                $echo[] = '</table>';

                // --- 再次修改 ---

                $ft->set([
                    'point' => [
                        'x' => 40,
                        'y' => 40
                    ],
                    'polygon' => [
                        [
                            [ 'x' => 5, 'y' => 1 ],
                            [ 'x' => 6, 'y' => 2 ],
                            [ 'x' => 7, 'y' => 3 ],
                            [ 'x' => 5, 'y' => 1 ]
                        ]
                    ],
                    'json' => [ 'x' => [ 'y' => 'def' ] ]
                ]);
                $ft->save();
                $ft->refresh();
                $echo[] = "<pre>\$ft->set([
    'point' => [
        'x' => 40,
        'y' => 40
    ],
    'polygon' => [
        [
            [ 'x' => 5, 'y' => 1 ],
            [ 'x' => 6, 'y' => 2 ],
            [ 'x' => 7, 'y' => 3 ],
            [ 'x' => 5, 'y' => 1 ]
        ]
    ],
    'json' => [ 'x' => [ 'y' => 'def' ] ]
]);
\$ft->save();
\$ft->refresh();</pre>";

                $echo[] = '<table style="width: 100%;">';

                $echo[] = '<tr><th>id</th><td>' . $ft->id . '</td></tr>';
                $echo[] = '<tr><th>token</th><td>' . $ft->token . '</td></tr>';
                $echo[] = '<tr><th>point</th><td>' . json_encode($ft->point) . '</td></tr>';
                $echo[] = '<tr><th>polygon</th><td>' . json_encode($ft->polygon) . '</td></tr>';
                $echo[] = '<tr><th>json</th><td>' . json_encode($ft->json) . '</td></tr>';
                $echo[] = '<tr><th>time_add</th><td>' . $ft->time_add . '</td></tr>';

                $echo[] = '</table>';
            }
            else {
                $echo[] = '<div>false</div>';
            }

            $echo[] = '<br><a href="' . URL_BASE . 'test/mod-test?action=remove">Remove all test data</a> | <a href="' . URL_BASE . 'test">Return</a>';

            return join('', $echo) . '<br><br>' . $this->_getEnd();
        }
    }

    public function modSplit() {
        $echo = ['<b style="color: red;">In a production environment, please delete "mod/test.php" and "mod/testdata.php" files.</b>'];

        $db = Db::get();
        if (!($rtn = $db->connect())) {
            return [0 ,'Failed('.($rtn === null ? 'null' : 'false').').'];
        }

        $echo[] = "<br><br>Test SQL:<pre>CREATE TABLE `m_test` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `token` CHAR(16) NOT NULL COLLATE 'ascii_bin',
    `point` POINT NOT NULL,
    `time_add` BIGINT NOT NULL,
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE INDEX `token` (`token`) USING BTREE,
    INDEX `time_add` (`time_add`) USING BTREE
) ENGINE=InnoDB COLLATE=utf8mb4_general_ci;
CREATE TABLE `m_test_data_0` (
    `id` bigint NOT NULL AUTO_INCREMENT,
    `test_id` bigint NOT NULL,
    `content` varchar(128) COLLATE ascii_bin NOT NULL,
    `time_add` bigint NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;</pre>m_test_data_0 - m_test_data_4<br><br>";

        // --- 操作按钮 ---

        $echo[] = "<input type=\"button\" value=\"Create user\" onclick=\"this.value='Waiting...';fetch('" . URL_BASE . "test/mod-split1',{method:'GET',headers:{'Content-Type':'application/x-www-form-urlencoded'}}).then(function(r){window.location.href=window.location.href})\">
<input type=\"button\" value=\"Random post\" onclick=\"this.value='Waiting...';fetch('" . URL_BASE . "test/mod-split2',{method:'GET',headers:{'Content-Type':'application/x-www-form-urlencoded'}}).then(function(r){return r.json()}).then(function(j){alert('TESTID:'+j.id+'\\nTABLEINDEX:'+j.index);window.location.href=window.location.href})\">";

        // --- 读取 test 和 test_data 表 ---

        $stmt = $db->query('SELECT * FROM `m_test` ORDER BY `id` DESC LIMIT 0, 20;');
        $echo[] = '<br><br><b>m_test</b> table:';
        $this->_dbTable($stmt, $echo);

        for ($i = 0; $i < 5; ++$i) {
            $stmt = $db->query('SELECT * FROM `m_test_data_' . $i . '` ORDER BY `id` DESC LIMIT 0, 20;');
            $echo[] = '<br><b>m_test_data_' . $i . '</b> table:';
            $this->_dbTable($stmt, $echo);
        }

        return join('', $echo) . '<br>' . $this->_getEnd();
    }

    public function modSplit1() {
        $db = Db::get();
        $db->connect();
        Mod::setDb($db);

        $test = ModTest::getCreate();
        $test->set([
            'token' => Core::random((int)Core::rand(8, 32)),
            'point' => [
                'x' => 10,
                'y' => 10
            ],
            'time_add' => time()
        ]);
        $test->create();
    }

    public function modSplit2() {
        $db = Db::get();
        $db->connect();
        Mod::setDb($db);

        $ids = [];
        $ls = ModTest::select(['id'])->by('time_add')->limit(0, 50)->all();
        if ($ls) {
            foreach ($ls as $item) {
                $ids[] = $item->id;
            }
        }
        $id = $ids[Core::rand(0, count($ids) - 1)];

        // --- 一致性 hash ---
        $index = Consistent::fast($id, ['0', '1', '2', '3', '4']);

        $testData = TestData::getCreate($index);
        $testData->set([
            'test_id' => $id,
            'content' => Core::random((int)Core::rand(8, 32)),
            'time_add' => time()
        ]);
        $testData->create();

        return [1, 'id' => $id, 'index' => $index];
    }

    public function captchaFastbuild() {
        return Captcha::get(400, 100)->getBuffer();
    }

    public function captchaBase64() {
        $echo = ["<pre>\$cap = Captcha::get(400, 100);
\$phrase = \$cap->getPhrase();
\$base64 = \$cap->getBase64();</pre>\$phrase:"];
        $cap = Captcha::get(400, 100);
        $phrase = $cap->getPhrase();
        $base64 = $cap->getBase64();
        $echo[] = '<pre>' . $phrase . '</pre>';

        $echo[] = '$base64:';
        $echo[] = '<pre style="white-space: pre-wrap; word-wrap: break-word; overflow-y: auto; max-height: 200px;">' . $base64 . '</pre>';

        $echo[] = '&lt;img src="&lt;?php echo $base64 ?&gt;" style="width: 200px; height: 50px;"&gt;';
        $echo[] = '<pre><img alt="captcha" src="' . $base64 . '" style="width: 200px; height: 50px;"></pre>';

        return join('', $echo) . $this->_getEnd();
    }

    public function coreRandom() {
        return '<pre>Core::random(16, Core::RANDOM_LUNS);</pre>' . htmlspecialchars(Core::random(16, Core::RANDOM_LUNS)) .
            '<pre>Core::random(4, Core::RANDOM_V);</pre>' . htmlspecialchars(Core::random(4, Core::RANDOM_V)) .
            '<pre>Core::random(8, Core::RANDOM_N, \'0349\');</pre>' . htmlspecialchars(Core::random(8, Core::RANDOM_N, '0349')) .
            '<pre>Core::random(8, Core::RANDOM_LNU);</pre>' . htmlspecialchars(Core::random(8, Core::RANDOM_LUN)) .
            '<pre>Core::random(16, Core::RANDOM_LNU);</pre>' . htmlspecialchars(Core::random(16, Core::RANDOM_LUN)) .
            '<br><br>' . $this->_getEnd();
    }

    public function coreRand() {
        return '<pre>Core::rand(1.2, 7.1, 1);</pre>' . Core::rand(1.2, 7.1, 1) .
            '<pre>Core::rand(1.2, 7.1, 5);</pre>' . Core::rand(1.2, 7.1, 5) .
            '<pre>Core::rand(1.298, 7.1891, 2);</pre>' . Core::rand(1.298, 7.1891, 2) .
            '<br><br>' . $this->_getEnd();
    }

    public function coreConvert62() {
        return '<pre>Core::convert62(10);</pre>' . Core::convert62(10) .
            '<pre>Core::convert62(100);</pre>' . Core::convert62(100) .
            '<pre>Core::convert62(1992199519982001);</pre>' . Core::convert62(1992199519982001) .
            '<pre>Core::convert62(9223372036854770000);</pre>' . Core::convert62(9223372036854770000) .
            '<pre>Core::convert62(9223372036854775807);</pre>' . Core::convert62(9223372036854775807) .

            '<pre>Core::unconvert62(\'a\');</pre>' . Core::unconvert62('a') .
            '<pre>Core::unconvert62(\'100\');</pre>' . Core::unconvert62('100') .
            '<pre>Core::unconvert62(\'zzz\');</pre>' . Core::unconvert62('zzz') .
            '<pre>Core::unconvert62(\'ZZZ\');</pre>' . Core::unconvert62('ZZZ') .
            '<pre>Core::unconvert62(\'97HMXKQql\');</pre>' . Core::unconvert62('97HMXKQql') .
            '<pre>Core::unconvert62(\'aZl8N0y57gs\');</pre>' . Core::unconvert62('aZl8N0y57gs') .
            '<pre>Core::unconvert62(\'aZl8N0y58M7\');</pre>' . Core::unconvert62('aZl8N0y58M7') .
            '<br><br>' . $this->_getEnd();
    }

    public function corePurify() {
        $html = "<html>
    <head>
        <title>Title</title>
    </head>
    <body>
        <!-- h1 -->
        <h1>Hello</h1>
        <h2>World</h2>
        <div>// abc</div>
        <script>
        // --- test ---
        if (a) {
           alert('zzz');
        }
        /* --- test 2 --- */
        if (b) {
           alert('zzz');
        }
        </script>
    </body>
</html>";
        return '<pre>Core::purify("' . htmlspecialchars($html) . '");</pre>' . htmlspecialchars(Core::purify($html)) . '<br><br>' . $this->_getEnd();
    }

    public function coreMuid() {
        $ac = isset($_GET['ac']) ? $_GET['ac'] : '';

        $echo = [
            '<a href="' . URL_BASE . 'test/core-muid">Default</a> | ' .
            '<a href="' . URL_BASE . 'test/core-muid?ac=big">Big</a> | ' .
            '<a href="' . URL_BASE . 'test">Return</a>'
        ];

        if ($ac === '') {
            $muid = Core::muid();
            $echo[] = '<pre>Core::muid();</pre>' . $muid . ' (' . strlen($muid) . ')';
    
            $muid = Core::muid();
            $echo[] = '<pre>Core::muid();</pre>' . $muid . ' (' . strlen($muid) . ')';

            $muid = Core::muid([ 'bin' => false ]);
            $echo[] = "<pre>Core::muid([ 'bin' => false ]);</pre>" . $muid . ' (' . strlen($muid) . ')';

            $muid = Core::muid([ 'len' => 16 ]);
            $echo[] = "<pre>Core::muid([ 'len' => 16 ]);</pre>" . $muid . ' (' . strlen($muid) . ')';

            $muid = Core::muid([ 'len' => 16, 'bin' => false ]);
            $echo[] = "<pre>Core::muid([ 'len' => 16, 'bin' => false ]);</pre>" . $muid . ' (' . strlen($muid) . ')';

            $muid = Core::muid([ 'insert' => 'Aa', 'len' => 32 ]);
            $echo[] = "<pre>Core::muid([ 'insert' => 'Aa', 'len' => 32 ]);</pre>" . $muid . ' (' . strlen($muid) . ')';

            $muid = Core::muid([ 'key' => 'M' ]);
            $echo[] = "<pre>Core::muid([ 'key' => 'M' ]);</pre>" . $muid . ' (' . strlen($muid) . ')';

            $echo[] = '<br><br>';
        }
        else {
            $parr = [];
            $oarr = [];
            for ($i = 0; $i < 30000; ++$i) {
                $muid = Core::muid([ 'insert' => '0' ]);
                $sp = array_search($muid, $oarr);
                if ($sp !== false) {
                    $parr[] = $muid . '[' . $sp . ']' . $oarr[$sp];
                    continue;
                }
                $oarr[] = $muid;
            }
            $echo[] = "<pre>
\$parr = [];
\$oarr = [];
for (\$i = 0; \$i < 30000; ++\$i) {
    \$muid = Core::muid([ 'insert' => '0' ]);
    \$sp = array_search(\$muid, \$oarr);
    if (\$sp !== false) {
        \$parr[] = \$muid . '[' . \$sp . ']' . \$oarr[\$sp];
        continue;
    }
    \$oarr[] = \$muid;
}</pre>parr length: " . count($parr) . "<br>oarr length: " . count($oarr) . "<br><br>parr:<pre>" . json_encode($parr) . "</pre>oarr:<pre>" . substr(json_encode(array_slice($oarr, 0, 100)), 0, -1) . "...</pre>";
        }

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
        $echo[] = "<pre>\$orig = Crypto::aesDecrypt(\$text, \$key, \$iv);
json_encode(\$orig);</pre>" . json_encode($orig);

        $orig = Crypto::aesDecrypt($text, $key, 'otherIv');
        $echo[] = "<pre>\$orig = Crypto::aesDecrypt(\$text, \$key, 'otherIv');
json_encode(\$orig) ? 'true' : 'false';</pre>" . (json_encode($orig) ? 'true' : 'false');

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
        $echo = ["<br><br>ms: " . round($this->_getRunTime() * 1000, 4)];

        $db = Db::get($_GET['s']);
        if (!($rtn = $db->connect())) {
            return [0 ,'Failed('.($rtn === null ? 'null' : 'false').').'];
        }

        // --- 先获取 test 表的情况 ---
        if (!($stmt = $db->query('SELECT * FROM `m_test` ORDER BY `id` DESC LIMIT 10;'))) {
            return [0 ,'Failed("m_test" not found)'];
        }

        $echo[] = "<pre>\$db = Db::get('" . $_GET['s'] . "');
if (!(\$rtn = \$db->connect())) {
    return [0 ,'Failed('.(\$rtn === null ? 'null' : 'false').').'];
}
\$stmt = \$db->query('SELECT * FROM `m_test` ORDER BY `id` DESC LIMIT 10;</pre>";

        $this->_dbTable($stmt, $echo);

        $echo[] = "<br>ms: " . round($this->_getRunTime() * 1000, 4);

        // --- 插入 test-token 的条目 ---
        $time = (string)time();
        $exec = $db->exec('INSERT INTO `m_test` (`token`, `point`, `time_add`) VALUES (\'test-token\', ST_POINTFROMTEXT(\'POINT(10 10)\'), \'' . $time . '\');');
        $ms = round($this->_getRunTime() * 1000, 4);
        $errorCode = $db->getErrorCode();
        $error = $db->getErrorInfo();
        if ($errorCode === '23000') {
            $insertId = $db->query('SELECT * FROM `m_test` WHERE `token` = \'test-token\';')->fetch(PDO::FETCH_ASSOC)['id'];
            $ms .= ', ' . round($this->_getRunTime() * 1000, 4);
        }
        else {
            $insertId = $db->getInsertID();
        }

        $echo[] = "<pre>\$exec = \$db->exec('INSERT INTO `m_test` (`token`, `point`, `time_add`) VALUES (\'test-token\', ST_POINTFROMTEXT(\'POINT(10 10)\'), \'' . $time . '\');');
\$errorCode = \$db->getErrorCode();
\$error = \$db->getErrorInfo();
if (\$errorCode === '23000') {
    \$insertId = \$db->query('SELECT * FROM `m_test` WHERE `token` = \'test-token\';')->fetch(PDO::FETCH_ASSOC)['id'];
}
else {
    \$insertId = \$db->getInsertID();
}</pre>
exec: " . json_encode($exec) . "<br>
insertId: " . json_encode($insertId) . "<br>
errorCode: " . json_encode($errorCode) . "<br>
error: " . json_encode($error) . "<br>
ms: " . $ms . "<br><br>";

        // --- 获取最近的一条 ---
        $stmt = $db->query('SELECT * FROM `m_test` ORDER BY `id` DESC LIMIT 1;');
        $this->_dbTable($stmt, $echo);

        // --- 再次插入 test-token 的条目 ---
        $exec = $db->exec('INSERT INTO `m_test` (`token`, `point`, `time_add`) VALUES (\'test-token\', ST_POINTFROMTEXT(\'POINT(10 10)\'), \'' . $time . '\');');
        $errorCode = $db->getErrorCode();
        $error = $db->getErrorInfo();
        $insertId = $db->getInsertID();
        $echo[] = "<pre>\$exec = \$db->exec('INSERT INTO `m_test` (`token`, `point`, `time_add`) VALUES (\'test-token\', ST_POINTFROMTEXT(\'POINT(10 10)\'), \'' . $time . '\');');
\$insertId = \$db->getInsertID();</pre>
exec: " . json_encode($exec) . "<br>
insertId: " . json_encode($insertId) . "<br>
errorCode: " . json_encode($errorCode) . "<br>
error: " . json_encode($error) . "<br>
ms: " . round($this->_getRunTime() * 1000, 4) . "<br>";

        // --- 依据唯一键替换值 ---
        $exec = $db->exec('REPLACE INTO `m_test` (`token`, `point`, `time_add`) VALUES (\'test-token\', ST_POINTFROMTEXT(\'POINT(20 20)\'), \'' . $time . '\');');
        $insertId = $db->getInsertID();
        $echo[] = "<pre>\$exec = \$db->exec('REPLACE INTO `m_test` (`token`, `point`, `time_add`) VALUES (\'test-token\', ST_POINTFROMTEXT(\'POINT(20 20)\'), \'' . $time . '\');');');
\$insertId = \$db->getInsertID();</pre>
exec: " . json_encode($exec) . "<br>
insertId: " . json_encode($insertId) . "<br>
errorCode: " . json_encode($db->getErrorCode()) . "<br>
error: ".json_encode($db->getErrorInfo())."<br>
ms: " . round($this->_getRunTime() * 1000, 4) . "<br><br>";

        // --- 显示近 10 条 ---
        $stmt = $db->query('SELECT * FROM `m_test` ORDER BY `id` DESC LIMIT 10;');
        $this->_dbTable($stmt, $echo);

        // --- explain 开始 ---
        $echo[] = "<pre>\$stmt = \$db->query('EXPLAIN SELECT * FROM `m_test` LIMIT 10;');</pre>";
        $stmt = $db->query('EXPLAIN SELECT * FROM `m_test` LIMIT 10;');
        $this->_dbTable($stmt, $echo);

        $echo[] = '<br>ms: ' . round($this->_getRunTime() * 1000, 4);

        // --- 删除测试添加的 token ---
        $exec = $db->exec('DELETE FROM `m_test` WHERE `token` = \'test-token\';');
        $echo[] = "<pre>\$exec = \$db->exec('DELETE FROM `m_test` WHERE `token` = \'test-token\';');</pre>
exec: " . json_encode($exec) . "<br><br>";

        $stmt = $db->query('SELECT * FROM `m_test` ORDER BY `id` DESC LIMIT 10;');
        $this->_dbTable($stmt, $echo);

        return join('', $echo) . '<br>queries: ' . $db->getQueries() . '<br>' . $this->_getEnd();
    }

    private function _dbTable(Stmt|bool $stmt, &$echo) {
        $echo[] = '<table style="width: 100%;"><tr>';
        if ($stmt && $stmt->getColumnMeta(0)) {
            $cc = $stmt->columnCount();
            for ($i = 0; $i < $cc; ++$i) {
                $echo[] = '<th>' . htmlspecialchars($stmt->getColumnMeta($i)['name']) . '</th>';
            }
            $echo[] = '</tr>';

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $echo[] = '<tr>';
                foreach ($row as $key => $val) {
                    $echo[] = '<td>' . ($val === null ? 'null' : htmlspecialchars(json_encode($val))) . '</td>';
                }
                $echo[] = '</tr>';
            }
        }
        else {
            $echo[] = '<th>No data</th></tr>';
        }
        $echo[] = '</table>';
    }

    public function kv() {
        if (!$this->_checkInput($_GET, [
            's' => ['require', ['redis', 'redis-simulator'], [0, 'Object not found.']]
        ], $return)) {
            return $return;
        }

        $kv = Kv::get($_GET['s']);
        $db = null;
        if ($_GET['s'] === 'redis-simulator') {
            $db = Db::get();
            if (!$db->connect()) {
                return [0, 'Failed, MySQL can not be connected.'];
            }
        }
        if (!($rtn = $kv->connect([
            'binary' => false,
            'db' => $db
        ]))) {
            return [0, 'Failed(' . ($rtn === null ? 'null' : 'false').').'];
        }
        $value = isset($_GET['value']) ? $_GET['value'] : '';
        $ac = isset($_GET['ac']) ? $_GET['ac'] : '';

        $kvGet = strtoupper($_GET['s']);
        $kvGet = str_replace('SS', 'S_S', $kvGet);

        $echo = ["<pre>\$kv = Kv::get(Kv::$kvGet);
if (!(\$rtn = \$kv->connect())) {
    return [0 ,'Failed('.(\$rtn === null ? 'null' : 'false').').'];
}
json_encode(\$kv->ping());</pre>" . json_encode($kv->ping())];

        if ($ac == 'delete') {
            $echo[] = "<pre>json_encode(\$kv->get('test'));</pre>" . json_encode($kv->get('test'));

            $echo[] = "<pre>json_encode(\$kv->delete('test'));</pre>" . json_encode($kv->del('test'));

            $echo[] = "<pre>json_encode(\$kv->get('test'));</pre>" . json_encode($kv->get('test'));
        }
        else if ($ac === 'ttl') {
            $echo[] = "<pre>json_encode(\$kv->ttl('test'));</pre>" . json_encode($kv->ttl('test'));
            $echo[] = "<pre>json_encode(\$kv->pttl('test'));</pre>" . json_encode($kv->pttl('test'));
            $echo[] = "<pre>json_encode(\$kv->set('test', 'ttl', 10));</pre>" . json_encode($kv->set('test', 'ttl', 10));
            $echo[] = "<pre>json_encode(\$kv->ttl('test'));</pre>" . json_encode($kv->ttl('test'));
            $echo[] = "<pre>json_encode(\$kv->pttl('test'));</pre>" . json_encode($kv->pttl('test'));
        }
        else if ($ac == 'incr-decr-replace') {
            $echo[] = "<pre>json_encode(\$kv->getLastError());</pre>" . json_encode($kv->getLastError());

            $echo[] = "<pre>json_encode(\$kv->del('test'));</pre>" . json_encode($kv->del('test'));

            $echo[] = "<pre>json_encode(\$kv->replace('test', 'QAQ'));</pre>" . json_encode($kv->replace('test', 'QAQ'));

            $echo[] = "<pre>json_encode(\$kv->getLastError());</pre>" . json_encode($kv->getLastError());

            $echo[] = "<pre>json_encode(\$kv->incr('test'));</pre>" . json_encode($kv->incr('test'));

            $echo[] = "<pre>json_encode(\$kv->getLastError());</pre>" . json_encode($kv->getLastError());

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

            $echo[] = "<pre>json_encode(\$kv->getLastError());</pre>" . json_encode($kv->getLastError());

            $echo[] = "<pre>json_encode(\$kv->get('test'));</pre>" . json_encode($kv->get('test'));

            $echo[] = "<pre>json_encode(\$kv->getLastError());</pre>" . json_encode($kv->getLastError());
        }
        else if ($ac === 'append-prepend') {
            $echo[] = "<pre>json_encode(\$kv->prepend('test', '0'));</pre>" . json_encode($kv->prepend('test', '0'));

            $echo[] = "<pre>json_encode(\$kv->get('test'));</pre>" . json_encode($kv->get('test'));

            $echo[] = "<pre>json_encode(\$kv->set('test', 'bbb'));</pre>" . json_encode($kv->set('test', 'bbb'));

            $echo[] = "<pre>json_encode(\$kv->append('test', 'end'));</pre>" . json_encode($kv->append('test', 'end'));

            $echo[] = "<pre>json_encode(\$kv->get('test'));</pre>" . json_encode($kv->get('test'));

            $echo[] = "<pre>json_encode(\$kv->prepend('test', 'pre'));</pre>" . json_encode($kv->prepend('test', 'pre'));

            $echo[] = "<pre>json_encode(\$kv->get('test'));</pre>" . json_encode($kv->get('test'));

            $echo[] = "<pre>json_encode(\$kv->add('test', 'aaa'));</pre>" . json_encode($kv->add('test', 'aaa'));

            $echo[] = "<pre>json_encode(\$kv->append('tmp_test', 'hehe'));</pre>" . json_encode($kv->append('tmp_test', 'hehe'));

            $echo[] = "<pre>json_encode(\$kv->getLastError());</pre>" . json_encode($kv->getLastError());

            $echo[] = "<pre>json_encode(\$kv->get('tmp_test'));</pre>" . json_encode($kv->get('tmp_test'));

            $echo[] = "<pre>json_encode(\$kv->del('tmp_test'));</pre>" . json_encode($kv->del('tmp_test'));
        }
        else if ($ac === 'hash') {
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

            $echo[] = "<pre>json_encode(\$kv->hMSet('hTest', [
    'ok1' => 'bye',
    'ok2' => [
        '1', '2', '5', '8', '0'
    ]
]));</pre>";
            $echo[] = json_encode($kv->hMSet('hTest', [
                'ok1' => 'bye',
                'ok2' => [
                    '1', '2', '5', '8', '0'
                ]
            ]));

            $echo[] = "<pre>json_encode(\$kv->hSet('hTest', 'ok1', ['a', 'b']));</pre>" . json_encode($kv->hSet('hTest', 'ok1', ['a', 'b']));

            $echo[] = "<pre>json_encode(\$kv->hGetAll('hTest'));</pre>" . json_encode($kv->hGetAll('hTest'));

            $echo[] = "<pre>json_encode(\$kv->hGetJson('hTest', 'ok1'));</pre>" . json_encode($kv->hGetJson('hTest', 'ok1'));

            $echo[] = "<pre>json_encode(\$kv->hKeys('hTest'));</pre>" . json_encode($kv->hKeys('hTest'));

            $echo[] = "<pre>json_encode(\$kv->hExists('hTest', 'age'));</pre>" . json_encode($kv->hExists('hTest', 'age'));

            $echo[] = "<pre>json_encode(\$kv->hMGet('hTest', ['age', 'sex', 'school']));</pre>" . json_encode($kv->hMGet('hTest', ['age', 'sex', 'school']));

            $echo[] = "<pre>json_encode(\$kv->del('hTest'));</pre>" . json_encode($kv->del('hTest'));

            $echo[] = "<pre>json_encode(\$kv->hGet('hTest', 'name'));</pre>" . json_encode($kv->hGet('hTest', 'name'));

            $echo[] = "<pre>json_encode(\$kv->hGetAll('hTest'));</pre>" . json_encode($kv->hGetAll('hTest'));
        }
        else if ($ac === 'other') {
            $echo[] = "<pre>for (\$i = 0; \$i < 50; ++\$i) {
    \$kv->add('t' . \$i, \$i, 10);
}
echo 'Added.';</pre>";
            for ($i = 0; $i < 50; ++$i) {
                $kv->add('t' . $i, $i, 10);
            }
            $echo[] = 'Added.';

            $echo[] = "<pre>json_encode(\$kv->keys('t*'));</pre>" . json_encode($kv->keys('t*'));

            $echo[] = '<pre>json_encode(\$kv->scan());</pre>' . json_encode($kv->scan());

            $echo[] = "<pre>\$cursor = null;
while (true) {
    \$echo[] = 'WHILE (' . json_encode(\$cursor) . ')&lt;br&gt;';
    \$r = \$kv->scan(\$cursor, '*2*', 5);
    if (\$r === false) {
        \$echo[] = 'DONE&lt;br&gt;';
        break;
    }
    \$echo[] =  json_encode(\$r) . '&lt;br&gt;';
}
\$echo[count(\$echo) - 1] = substr(\$echo[count(\$echo) - 1], 0, -4);</pre>";
            $cursor = null;
            while (true) {
                $echo[] = 'WHILE (' . json_encode($cursor) . ')<br>';
                $r = $kv->scan($cursor, '*2*', 5);
                if ($r === false) {
                    $echo[] = 'DONE<br>';
                    break;
                }
                $echo[] = json_encode($r) . '<br>';
            }
            $echo[count($echo) - 1] = substr($echo[count($echo) - 1], 0, -4);
        }
        else {
            // --- default ---
            $echo[] = "<pre>json_encode(\$kv->exists(['test', 'heheda']));</pre>" . json_encode($kv->exists(['test', 'heheda']));

            $echo[] = "<pre>json_encode(\$kv->mGet(['test', 'heheda']));</pre>" . json_encode($kv->mGet(['test', 'heheda']));

            $echo[] = "<pre>json_encode(\$kv->get('test'));</pre>" . json_encode($kv->get('test'));

            $echo[] = "<pre>json_encode(\$kv->set('test', \$value ? \$value : 'ok'));</pre>" . json_encode($kv->set('test', $value ? $value : 'ok'));

            $echo[] = "<pre>json_encode(\$kv->get('test'));</pre>" . json_encode($kv->get('test'));
        }

        return '<a href="' . URL_BASE . 'test/kv?s=' . $_GET['s'] . '">Default</a> | ' .
            '<a href="' . URL_BASE . 'test/kv?s=' . $_GET['s'] . '&value=aaa">Set "aaa"</a> | ' .
            '<a href="' . URL_BASE . 'test/kv?s=' . $_GET['s'] . '&value=bbb">Set "bbb"</a> | ' .
            '<a href="' . URL_BASE . 'test/kv?s=' . $_GET['s'] . '&ac=delete">Delete</a> | ' .
            '<a href="' . URL_BASE . 'test/kv?s=' . $_GET['s'] . '&ac=ttl">ttl</a> | ' .
            '<a href="' . URL_BASE . 'test/kv?s=' . $_GET['s'] . '&ac=incr-decr-replace">Incr/Decr/Replace</a> | ' .
            '<a href="' . URL_BASE . 'test/kv?s=' . $_GET['s'] . '&ac=append-prepend">Append/Prepend</a> | ' .
            '<a href="' . URL_BASE . 'test/kv?s=' . $_GET['s'] . '&ac=hash">Hash</a> | ' .
            '<a href="' . URL_BASE . 'test/kv?s=' . $_GET['s'] . '&ac=other">Other</a> | ' .
            '<a href="' . URL_BASE . 'test">Return</a>' . join('', $echo) . '<br><br>' . $this->_getEnd();
    }

    public function net() {
        $echo = [];

        $res = Net::get('https://cdn.jsdelivr.net/npm/deskrt@2.0.10/package.json');
        $echo[] = "<pre>Net::get('https://cdn.jsdelivr.net/npm/deskrt@2.0.10/package.json');</pre>
headers: <pre>" . json_encode($res->headers, JSON_PRETTY_PRINT) . "</pre>
content: <pre>" . $res->content . "</pre>
error: " . json_encode($res->error) . "<br>
errno: " . json_encode($res->errno) . "<br>
info: <pre>" . json_encode($res->info, JSON_PRETTY_PRINT) . "</pre>";

        return join('', $echo) . $this->_getEnd();
    }

    public function netPost() {
        $echo = [];

        $res = Net::post($this->_internalUrl . 'test/netPost1', ['a' => '1', 'b' => '2', 'c' => ['1', '2', '3']]);
        $echo[] = "<pre>Net::post('" . $this->_internalUrl . "test/netPost1', ['a' => '1', 'b' => '2', 'c' => ['1', '2', '3']]);</pre>
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

    public function netPostString() {
        $echo = [];

        $res = Net::post($this->_internalUrl . 'test/netPostString1', 'HeiHei');
        $echo[] = "<pre>Net::post('" . $this->_internalUrl . "test/netPostString1', 'HeiHei');</pre>
headers: <pre>" . json_encode($res->headers, JSON_PRETTY_PRINT) . "</pre>
content: <pre>" . $res->content . "</pre>
error: " . json_encode($res->error) . "<br>
errno: " . json_encode($res->errno) . "<br>
info: <pre>" . json_encode($res->info, JSON_PRETTY_PRINT) . "</pre>";

        return join('', $echo) . $this->_getEnd();
    }

    public function netPostString1() {
        return [1, $this->_input];
    }

    public function netOpen() {
        $echo = [];

        $res = Net::open($this->_internalUrl . 'test/netPost1')->post()->data(['a' => '2', 'b' => '0', 'c' => ['0', '1', '3']])->request();
        $echo[] = "<pre>Net::open('" . $this->_internalUrl . "test/netPost1')->post()->data(['a' => '2', 'b' => '0', 'c' => ['0', '1', '3']])->request();</pre>
headers: <pre>" . json_encode($res->headers, JSON_PRETTY_PRINT) . "</pre>
content: <pre>" . $res->content . "</pre>
error: " . json_encode($res->error) . "<br>
errno: " . json_encode($res->errno) . "<br>
info: <pre>" . json_encode($res->info, JSON_PRETTY_PRINT) . "</pre>";

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

        $res = Net::post($this->_internalUrl . 'test/net-upload1', [
            'a' => '1',
            'file' => curl_file_create(LIB_PATH . 'Net/cacert.pem'),
            'multiple' => [
                curl_file_create(LIB_PATH . 'Net/cacert.pem'),
                curl_file_create(LIB_PATH . 'Net/cacert.pem')
            ]
        ]);
        $echo[] = "<pre>Net::post('" . $this->_internalUrl . "test/net-upload1', [
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
        $res = Net::get($this->_internalUrl . 'test/net-cookie1', [], $cookie);
        $echo[] = "<pre>\$cookie = [];
Net::get('" . $this->_internalUrl . "test/net-cookie1', [], \$cookie);</pre>
headers: <pre>" . json_encode($res->headers, JSON_PRETTY_PRINT) . "</pre>
content: <pre>" . $res->content . "</pre>
cookie: <pre>" . json_encode($cookie, JSON_PRETTY_PRINT) . "</pre><hr>";

        $res = Net::get($this->_internalUrl . 'test/net-cookie2', [], $cookie);
        $echo[] = "<pre>Net::get('" . $this->_internalUrl . "test/net-cookie2', [], \$cookie);</pre>
headers: <pre>" . json_encode($res->headers, JSON_PRETTY_PRINT) . "</pre>
content: <pre>" . $res->content . "</pre>";

        Net::setCookie($cookie, 'custom1', 'abc1', HOST);
        Net::setCookie($cookie, 'custom2', 'abc2', '172.17.0.1');
        $res = Net::get($this->_internalUrl . 'test/net-cookie2', [], $cookie);
        $echo[] = "<pre>Net::setCookie($cookie, 'custom1', 'abc1', '" . HOST . "');
Net::setCookie($cookie, 'custom2', 'abc2', '172.17.0.1');
Net::get('" . $this->_internalUrl . "test/net-cookie2', [], \$cookie);</pre>
headers: <pre>" . json_encode($res->headers, JSON_PRETTY_PRINT) . "</pre>
content: <pre>" . $res->content . "</pre>";

        return join('', $echo) . $this->_getEnd();
    }

    public function netCookie1() {
        setcookie('test0', 'session');
        setcookie('test1', 'normal', $_SERVER['REQUEST_TIME'] + 10);
        setcookie('test2', 'baidu.com', $_SERVER['REQUEST_TIME'] + 20, '/', 'baidu.com');
        setcookie('test3', HOSTNAME, $_SERVER['REQUEST_TIME'] + 30, '/', HOSTNAME);
        setcookie('test4', '/ok/', $_SERVER['REQUEST_TIME'] + 40, '/ok/');
        setcookie('test5', 'secure', $_SERVER['REQUEST_TIME'] + 50, '', '', true);
        setcookie('test6', '0.1', $_SERVER['REQUEST_TIME'] + 40, '/', '0.1');
        setcookie('test7', 'localhost', $_SERVER['REQUEST_TIME'] + 30, '/', 'localhost');
        setcookie('test8', 'com', $_SERVER['REQUEST_TIME'] + 20, '/', 'com');
        setcookie('test9', 'com.cn', $_SERVER['REQUEST_TIME'] + 10, '/', 'com.cn');
        setcookie('test10', 'httponly', $_SERVER['REQUEST_TIME'] + 60, '', '', false, true);
        return "setcookie('test0', 'session');
setcookie('test1', 'normal', \$_SERVER['REQUEST_TIME'] + 10);
setcookie('test2', 'baidu.com', \$_SERVER['REQUEST_TIME'] + 20, '/', 'baidu.com');
setcookie('test3', '" . HOSTNAME .  "', \$_SERVER['REQUEST_TIME'] + 30, '/', '" . HOSTNAME . "');
setcookie('test4', '/ok/', \$_SERVER['REQUEST_TIME'] + 40, '/ok/');
setcookie('test5', 'secure', \$_SERVER['REQUEST_TIME'] + 50, '', '', true);
setcookie('test6', '0.1', \$_SERVER['REQUEST_TIME'] + 40, '/', '0.1');
setcookie('test7', 'localhost', \$_SERVER['REQUEST_TIME'] + 30, '/', 'localhost');
setcookie('test8', 'com', \$_SERVER['REQUEST_TIME'] + 20, '/', 'com');
setcookie('test9', 'com.cn', \$_SERVER['REQUEST_TIME'] + 10, '/', 'com.cn');
setcookie('test10', 'httponly', \$_SERVER['REQUEST_TIME'] + 60, '', '', false, true);";
    }

    public function netCookie2() {
        return "\$_COOKIE: \n\n" . json_encode($_COOKIE, JSON_PRETTY_PRINT);
    }

    public function netSave() {
        $echo = [];

        $res = Net::get('https://cdn.jsdelivr.net/npm/deskrt@2.0.10/package.json', [
            'follow' => 5,
            'save' => LOG_PATH . 'test-must-remove.json'
        ]);
        $echo[] = "<pre>Net::get('https://cdn.jsdelivr.net/npm/deskrt@2.0.10/package.json', [
    'follow' => 5,
    'save' => LOG_PATH . 'test-must-remove.json'
]);</pre>
headers: <pre>" . json_encode($res->headers, JSON_PRETTY_PRINT) . "</pre>
content: <pre>" . $res->content . "</pre>
error: " . json_encode($res->error) . "<br>
errno: " . json_encode($res->errno) . "<br>
info: <pre>" . json_encode($res->info, JSON_PRETTY_PRINT) . "</pre>";

        return join('', $echo) . $this->_getEnd();
    }

    public function netFollow() {
        $echo = [];

        $res = Net::post($this->_internalUrl . 'test/net-follow1', [
            'a' => '1',
            'b' => '2'
        ], [
            'follow' => 5
        ]);
        $echo[] = "<pre>Net::post('" . $this->_internalUrl . "test/net-follow1', [
    'a' => '1',
    'b' => '2
], [
    'follow' => 5
]);</pre>
headers: <pre>" . json_encode($res->headers, JSON_PRETTY_PRINT) . "</pre>
content: <pre>" . $res->content . "</pre>
error: " . $res->error . "<br>
errno: " . $res->errno . "<br>
info: <pre>" . json_encode($res->info, JSON_PRETTY_PRINT) . "</pre>";

        return join('', $echo) . $this->_getEnd();
    }

    public function netFollow1() {
        $this->_location('test/net-follow2');
    }

    public function netFollow2() {
        return [1, 'post' => $this->_post['a'] . ',' . $this->_post['b']];
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

    public function netError() {
        $echo = [];

        $res = Net::get('https://192.111.000.222/xxx.zzz');
        $echo[] = "<pre>Net::get('https://192.111.000.222/xxx.zzz');</pre>
headers: <pre>" . json_encode($res->headers, JSON_PRETTY_PRINT) . "</pre>
content: <pre>" . $res->content . "</pre>
error: " . json_encode($res->error) . "<br>
errno: " . json_encode($res->errno) . "<br>
info: <pre>" . json_encode($res->info, JSON_PRETTY_PRINT) . "</pre>";

        return join('', $echo) . $this->_getEnd();
    }

    public function netHosts() {
        $echo = [];

        $res = Net::get('http://nodejs.org:' . HOSTPORT . URL_BASE . 'test', [
            'hosts' => [
                'nodejs.org' => Core::ip()
            ]
        ]);
        $echo[] = "<pre>Net::get('http://nodejs.org:" . HOSTPORT . URL_BASE . "test', [
    'hosts' => [
        'nodejs.org' => '" . Core::ip() . "'
    ]
]);</pre>
headers: <pre>" . json_encode($res->headers, JSON_PRETTY_PRINT) . "</pre>
content: <pre>" . htmlspecialchars($res->content) . "</pre>
error: " . json_encode($res->error) . "<br>
errno: " . json_encode($res->errno) . "<br>
info: <pre>" . json_encode($res->info, JSON_PRETTY_PRINT) . "</pre>";

        return join('', $echo) . $this->_getEnd();
    }

    public function netRproxy() {
        if (Net::rproxy($this, [
            'test/net-rproxy/' => 'https://cdn.jsdelivr.net/npm/deskrt@2.0.10/'
        ])) {
            return false;
        }
        return 'Nothing';
    }

    public function netMproxy() {
        $echo = [];
        $res = Net::get('https://cdn.jsdelivr.net/npm/deskrt@2.0.10/package.json', [
            'mproxy' => [
                'url' => $this->_internalUrl . 'test/net-mproxy1',
                'auth' => '123456',
                'data' => [ 'test' => '123' ]
            ]
        ]);
        $echo[] = "<pre>Net::get('https://cdn.jsdelivr.net/npm/deskrt@2.0.10/package.json', [
    'mproxy' => [
        'url' => '" . $this->_internalUrl . "test/net-mproxy1',
        'auth' => '123456',
        'data' => [ 'test' => '123' ]
    ]
]);</pre>
headers: <pre>" . json_encode($res->headers, JSON_PRETTY_PRINT) . "</pre>
content: <pre>" . htmlspecialchars($res->content) . "</pre>
error: " . json_encode($res->error) . "<br>
errno: " . json_encode($res->errno) . "<br>
info: <pre>" . json_encode($res->info, JSON_PRETTY_PRINT) . "</pre>";

        return join('', $echo) . $this->_getEnd();
    }

    public function netMproxy1() {
        $data = Net::mproxyData();
        // --- data 是用户追加的额外数据，不要太长 ---
        $rtn = Net::mproxy($this, '123456');
        if ($rtn > 0) {
            return false;
        }
        return 'Nothing(' . $rtn . ')';
    }

    public function scan() {
        $link = $this->_scanLink();
        if (!$link) {
            return [0, 'Failed, link can not be connected.'];
        }
        $s = isset($this->_get['s']) ? $this->_get['s'] : 'db';

        $echo = [];
        $scan = Scan::get($link, null, [ 'ttl' => 30 ]);
        $token = $scan->getToken();
        $echo[] = "<pre>\$scan = Scan::get(\$link, null, [ 'ttl' => 30 ]);
\$token = \$scan->getToken();</pre>
token: " . ($token ? $token : 'null') . "<br><br>
Scan status: <b id=\"status\" style=\"color: red;\">Waiting...</b><br>
Poll count: <span id=\"count\">0</span>, expiration date: <span id=\"exp\"></span><br><br>
Simulated scan URL: http://www.test.simu/scan?token=" . ($token ? $token : 'null') . " (QR Code can be generated)<br><br>
<input type=\"button\" value=\"Visit the simulated URL\" onclick=\"this.disabled=true;document.getElementById('url').innerText='http://www.test.simu/scan?token=" . ($token ? $token : 'null') . "';visit();\"><br><br>
<div style=\"border: solid 1px rgba(0,0,0,.3); box-shadow: 0 5px 20px rgba(0, 0, 0, .25); width: 90%; margin: auto;\">
    <div id=\"url\" style=\"background: rgba(0,0,0,.07); border-bottom: solid 1px rgba(0,0,0,.3); padding: 10px;\">about:blank</div>
    <div id=\"content\" style=\"height: 200px; font-size: 16px; display: flex; justify-content: center; align-items: center; flex-direction: column;\"></div>
</div>
<script>
var token = '" . ($token ? $token : 'null') . "';
var count = 0;
function poll() {
    fetch('" . URL_BASE . "test/scan1?s=" . $s . "', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'token=" . ($token ? $token : 'null') . "'
    }).then(function(r) {
        return r.json();
    }).then(function(j) {
        ++count;
        document.getElementById('status').innerText = j.msg;
        document.getElementById('count').innerText = count;
        if (j.result > 0) {
            document.getElementById('exp').innerText = j.exp;
            setTimeout(poll, 1000);
        }
    }).catch(function(e) {
        ++count;
        document.getElementById('status').innerText = 'Network error.';
        document.getElementById('count').innerText = count;
        setTimeout(poll, 1000);
    });
}
poll();

function visit() {
    document.getElementById('content').innerText = 'Loading...';
    fetch('" . URL_BASE . "test/scan2?s=" . $s . "', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'token=" . ($token ? $token : 'null') . "'
    }).then(function(r) {
        return r.json();
    }).then(function(j) {
        if (j.result > 0) {
            document.getElementById('content').innerHTML = 'Are you sure logged in the computer?<br><br><button id=\"confirm\" style=\"padding: 10px 20px;\" onclick=\"this.disabled=true;confirm()\">Confirm</button>';
        }
        else {
            document.getElementById('content').innerText = j.msg;
        }
    });
}

function confirm() {
    fetch('" . URL_BASE . "test/scan3?s=" . $s . "', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'token=" . ($token ? $token : 'null') . "'
    }).then(function(r) {
        return r.json();
    }).then(function(j) {
        if (j.result > 0) {
            document.getElementById('content').innerText = 'Finish the operation!';
        }
        else {
            document.getElementById('content').innerText = j.msg;
        }
    });
}
</script>";

        return '<a href="' . URL_BASE . 'test/scan?s=db">db</a> | ' .
        '<a href="' . URL_BASE . 'test/scan?s=kv">kv</a> | ' .
        '<a href="' . URL_BASE . 'test">Return</a>' . join('', $echo) . '<br>' . $this->_getEnd();
    }

    public function scan1() {
        $link = $this->_scanLink();
        if (!$link) {
            return [0, 'Failed, link can not be connected.'];
        }

        $scan = Scan::get($link, $_POST['token']);
        $rtn = $scan->poll();
        switch ($rtn) {
            case -3: {
                return [0, 'System error.'];
            }
            case -2: {
                return [0, 'Token has expired.'];
            }
            case -1: {
                return [1, 'Waiting...', 'exp' => $scan->getTimeLeft()];
            }
            case 0: {
                return [1, 'Scanned, waiting for confirmation...', 'exp' => $scan->getTimeLeft()];
            }
        }
        return [0, 'Scan result: ' . json_encode($rtn)];
    }

    public function scan2() {
        $link = $this->_scanLink();
        if (!$link) {
            return [0, 'Failed, link can not be connected.'];
        }
        if (!Scan::scanned($link, $_POST['token'])) {
            return [0, 'Token has expired.'];
        }
        return [1];
    }

    public function scan3() {
        $link = $this->_scanLink();
        if (!$link) {
            return [0, 'Failed, link can not be connected.'];
        }
        if (!Scan::setData($link, $_POST['token'], [
            'uid' => '5'
        ])) {
            return [0, 'Token has expired.'];
        }
        return [1];
    }

    private function _scanLink(): Db|IKv|false {
        $s = isset($this->_get['s']) ? $this->_get['s'] : 'db';
        if ($s === 'db') {
            $db = Db::get();
            if (!$db->connect()) {
                return false;
            }
            $link = $db;
        }
        else {
            $kv = Kv::get();
            if (!$kv->connect()) {
                return false;
            }
            $link = $kv;
        }
        return $link;
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
            $link = Db::get();
            if (!$link->connect()) {
                return [0, 'Failed, MySQL can not be connected.'];
            }
            $echo[] = "\$link = Db::get();\n";
        }
        else {
            $link = Kv::get();
            if (!$link->connect()) {
                return [0, 'Failed, Redis can not be connected.'];
            }
            $echo[] = "\$link = Kv::get();\n";
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
        }
        else {
            // --- AUTH 模式 ---
            $this->_startSession($link, true, ['ttl' => 60]);
            if (count($_POST) > 0) {
                if (!isset($_SESSION['count'])) {
                    $_SESSION['count'] = 1;
                }
                else {
                    ++$_SESSION['count'];
                }
                return [1, 'txt' => "\$_SESSION: " . json_encode($_SESSION) . "\nToken: " . $this->_sess->getToken(), 'token' => $this->_sess->getToken(), '_auth' => $this->_getBasicAuth('token', $this->_sess->getToken())];
            }
            else {
                $echo[] = "\$this->_startSession(\$link, true, ['ttl' => 60]);
json_encode(\$_SESSION);</pre>" . htmlspecialchars(json_encode($_SESSION));

                $_SESSION['value'] = date('H:i:s');
                $echo[] = "<pre>\$_SESSION['value'] = '" . date('H:i:s') . "';
json_encode(\$_SESSION);</pre>" . htmlspecialchars(json_encode($_SESSION));

                $echo[] = "<br><br><input type=\"button\" value=\"Post with header\" onclick=\"document.getElementById('result').innerText='Waiting...';fetch('" . URL_BASE . "test/session?s=" . $_GET['s'] . "&auth=1',{method:'POST',credentials:'omit',headers:{'Authorization':document.getElementById('_auth').innerText,'content-type':'application/x-www-form-urlencoded'},body:'key=val'}).then(function(r){return r.json();}).then(function(j){document.getElementById('result').innerText=j.txt;document.getElementById('token').innerText=j.token;document.getElementById('_auth').innerText=j._auth;});\"><input type='button' value=\"Post without header\" style=\"margin-left: 10px;\" onclick=\"document.getElementById('result').innerText='Waiting...';fetch('" . URL_BASE . "test/session?s=" . $_GET['s'] . "&auth=1',{method:'POST',credentials:'omit',headers:{'content-type':'application/x-www-form-urlencoded'},body:'key=val'}).then(function(r){return r.json();}).then(function(j){document.getElementById('result').innerText=j.txt;});\"><br><br>
Token: <span id=\"token\">" . $this->_sess->getToken() . "</span><br>
Post Authorization header: <span id=\"_auth\">" . $this->_getBasicAuth('token', $this->_sess->getToken()) . "</span><br><br>
Result:<pre id=\"result\">Nothing.</pre>";

                return '<a href="' . URL_BASE . 'test">Return</a>' . join('', $echo) . $this->_getEnd();
            }
        }
    }

    public function jwt() {
        if (!$this->_checkInput($_GET, [
            'type' => [['', 'kv', 'auth'], [0, 'Bad request.']]
        ], $return)) {
            return $return;
        }

        $echo = ['<pre>'];
        $link = null;
        if ($_GET['type'] === 'kv') {
            $link = Kv::get();
            if (!$link->connect()) {
                return [0, 'Failed, Reids can not be connected.'];
            }
            $echo[] = "\$link = Kv::get();
\$link->connect();\n";
        }

        $origin = Jwt::getOrigin($this);
        $echo[] = "\$origin = Jwt::getOrigin(\$this);
json_encode(\$origin);</pre>";
        $echo[] = json_encode($origin);

        // --- 创建 jwt 对象 ---
        $jwt = Jwt::get($this, [], $link);
        $echo[] = "<pre>\$jwt = Jwt::get(\$this, [], " . ($link ? '$link' : 'null') . ");
json_encode(\$this->_jwt);</pre>";
        $echo[] = json_encode($this->_jwt);

        $this->_jwt['test'] = 'a';
        $value = $jwt->renew();
        $echo[] = "<pre>\$this->_jwt['test'] = 'a';
\$value = \$jwt->renew();
json_encode(\$this->_jwt);</pre>";
        $echo[] = json_encode($this->_jwt);

        $echo[] = "<pre>json_encode(\$value);</pre>";
        $echo[] = json_encode($value);

        $token = $this->_jwt['token'];
        $rtn = $jwt->destory();
        $echo[] = "<pre>\$token = \$this->_jwt['token'];
\$rtn = \$jwt->destory();
json_encode(\$rtn);</pre>";
        $echo[] = json_encode($rtn);

        $echo[] = "<pre>json_encode(\$this->_jwt);</pre>";
        $echo[] = json_encode($this->_jwt);

        $rtn2 = Jwt::decode($origin, $link);
        $echo[] = "<pre>\$rtn = Jwt::decode(\$origin, " . ($link ? '$link' : 'null') . ");
json_encode(\$rtn);</pre>";
        $echo[] = json_encode($rtn2);

        if ($_GET['type'] === 'auth') {
            $echo[] = "<br><br><input type=\"button\" value=\"Post with header\" onclick=\"document.getElementById('result').innerText='Waiting...';fetch('" . URL_BASE . "test/jwt1',{method:'POST',credentials:'omit',headers:{'Authorization':document.getElementById('_auth').innerText,'content-type':'application/x-www-form-urlencoded'},body:'key=val'}).then(function(r){return r.json();}).then(function(j){document.getElementById('result').innerText=j.txt;});\"><input type='button' value=\"Post without header\" style=\"margin-left: 10px;\" onclick=\"document.getElementById('result').innerText='Waiting...';fetch('" . URL_BASE . "test/jwt1',{method:'POST',credentials:'omit',headers:{'content-type':'application/x-www-form-urlencoded'},body:'key=val'}).then(function(r){return r.json();}).then(function(j){document.getElementById('result').innerText=j.txt;});\"><br><br>
Token: <span id=\"token\">" . $token . "</span><br>
Post Authorization header: <span id=\"_auth\">Bearer " . $origin . "</span><br><br>
Result:<pre id=\"result\">Nothing.</pre>";
        }
        else {
            $echo[] = '<br><br>';
        }

        return join('', $echo) . $this->_getEnd();
    }

    public function jwt1() {
        Jwt::get($this, [
            'auth' => true
        ]);
        return [1, 'txt' => json_encode($this->_jwt)];
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

                $s = $sql->insert('order')->notExists('order', ['name' => 'Amy', 'age' => '16', 'time_add' => time(), 'point' => ['POINT(?)', ['20']]], ['name' => 'Amy'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->insert('user')->notExists('order', ['name' => 'Amy', 'age' => '16', 'time_add' => time(), 'point' => ['POINT(?)', ['20']]], ['name' => 'Amy']);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . '<hr>';

                $s = $sql->insert('verify')->values(['token' => 'abc', 'time_update' => '10'])->duplicate(['time_update' => ['CONCAT(`time_update`, ?)', ['01']]])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->insert('verify')->values(['token' => 'abc', 'time_update' => '10'])->duplicate(['time_update' => ['CONCAT(`time_update`, ?)', ['01']]]);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . '<hr>';

                // --- insert 中使用函数 ---

                $s = $sql->insert('geo')->values(['point' => ['ST_POINTFROMTEXT(?)', ['POINT(122.147775 30.625014)']]])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->insert('geo')->values(['point' => ['ST_POINTFROMTEXT(?)', ['POINT(122.147775 30.625014)']]]);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . '<hr>';

                $s = $sql->insert('geo')->values(['name', 'point', 'point2', 'polygon', 'json'], [
                    [
                        'POINT A', ['ST_POINTFROMTEXT(?)', ['POINT(122.147775 30.625015)']], [ 'x' => 1, 'y' => 1 ], [
                            [
                                [ 'x' => 1, 'y' => 1 ],
                                [ 'x' => 2, 'y' => 2 ],
                                [ 'x' => 3, 'y' => 3 ],
                                [ 'x' => 1, 'y' => 1 ]
                            ],
                            [
                                [ 'x' => 6, 'y' => 1 ],
                                [ 'x' => 7, 'y' => 2 ],
                                [ 'x' => 8, 'y' => 3 ],
                                [ 'x' => 6, 'y' => 1 ]
                            ]
                        ],
                        [ 'x' => [ 'y' => 'ghi' ] ]
                    ],
                    [
                        'POINT B', ['ST_POINTFROMTEXT(?)', ['POINT(123.147775 30.625016)']], [ 'x' => 1, 'y' => 1 ], NULL, NULL
                    ]
                ])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->insert('geo')->values(['name', 'point', 'point2', 'polygon', 'json'], [
    [
        'POINT A', ['ST_POINTFROMTEXT(?)', ['POINT(122.147775 30.625015)']], [ 'x' => 1, 'y' => 1 ], [
            [
                [ 'x' => 1, 'y' => 1 ],
                [ 'x' => 2, 'y' => 2 ],
                [ 'x' => 3, 'y' => 3 ],
                [ 'x' => 1, 'y' => 1 ]
            ],
            [
                [ 'x' => 6, 'y' => 1 ],
                [ 'x' => 7, 'y' => 2 ],
                [ 'x' => 8, 'y' => 3 ],
                [ 'x' => 6, 'y' => 1 ]
            ]
        ],
        [ 'x' => [ 'y' => 'ghi' ] ]
    ],
    [
        'POINT B', ['ST_POINTFROMTEXT(?)', ['POINT(123.147775 30.625016)']], [ 'x' => 1, 'y' => 1 ], NULL, NULL
    ]
]);</pre>
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

                $s = $sql->select(['order.no', 'user.nick'], ['order'])->leftJoin('user', ['order.user_id' => '#user.id', 'state' => '1'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->select(['order.no', 'user.nick'], ['order'])->leftJoin('user', ['order.user_id' => '#user.id', 'state' => '1']);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . "<hr>";

                $s = $sql->select(['o.*', 'u.nick as unick'], ['order o'])->leftJoin('`user` AS u', ['o.user_id' => '#u.id', 'state' => '1'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->select(['o.*', 'u.nick as unick'], ['order o'])->leftJoin('user AS u', ['o.user_id' => '#u.id', 'state' => '1']);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . "<hr>";

                $s = $sql->select(['SUM(user.age) age', 'UTC_TIMESTAMP', 'FROM_UNIXTIME(user.time, \'%Y-%m\') as time'], 'order')->leftJoin('user', ['order.user_id' => '#user.id'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->select(['SUM(user.age) age', 'UTC_TIMESTAMP', 'FROM_UNIXTIME(user.time, \'%Y-%m\') as time'], 'order')->leftJoin('user', ['order.user_id' => '#user.id']);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . "<hr>";

                $s = $sql->select('*', 'order')->leftJoin('user', [ 'order.user_id' => '#user.id' ], '_0')->leftJoin('group a', [ 'order.group_id' => '#a.id' ], '_0')->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->select('*', 'order')->leftJoin('user', [ 'order.user_id' => '#user.id' ], '_0')->leftJoin('group a', [ 'order.group_id' => '#a.id' ], '_0')->getSql();</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd);
                break;
            }
            case 'update': {
                // --- 1, 2 ---

                $s = $sql->update('user', [['age', '+', '1'], 'name' => 'Serene', 'nick' => '#name', ['year', '+', '#age']])->where(['name' => 'Ah'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->update('user', [['age', '+', '1'], 'name' => 'Serene', 'nick' => '#name', ['year', '+', '#age']]).where(['name' => 'Ah']);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . "<hr>";

                // --- 3 ---

                $s = $sql->update('user', ['name' => 'Serene', 'type' => ['(CASE `id` WHEN 1 THEN ? WHEN 2 THEN ? END)', ['val1', 'val2']]])->where(['name' => 'Ah'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->update('user', ['name' => 'Serene', 'type' => ['(CASE `id` WHEN 1 THEN ? WHEN 2 THEN ? END)', ['val1', 'val2']]])->where(['name' => 'Ah']);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . "<hr>";

                // --- # ---

                $s = $sql->update('user', ['age' => '#age_verify', 'date' => '##', 'he' => ['he2']])->where(['date_birth' => '2001'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->update('user', ['age' => '#age_verify', 'date' => '##', 'he' => ['he2']])->where(['date_birth' => '2001']);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd);

                // --- update order limit ---

                $s = $sql->update('user', [ 'he' => 'he2' ])->where([ ['birth', '>', '2001'] ])->by('birth')->limit(0, 10)->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->update('user', [ 'he' => 'he2' ])->where([ ['birth', '>', '2001'] ])->by('birth')->limit(0, 10);</pre>
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
                    '$or' => [['type' => '1', 'find' => '0'], ['type' => '2', 'find' => '1'], [['type', '<', '-1']]]
                ])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->update('order', ['state' => '1'])->where([
    'user_id' => '2',
    'state' => ['1', '2', '3'],
    '\$or' => [['type' => '1', 'find' => '0'], ['type' => '2', 'find' => '1'], [['type', '<', '-1']]]
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
<b>format() :</b> " . $sql->format($s, $sd) . "<hr>";

                $s = $sql->delete('user')->where([
                    [['MATCH(name_sc, name_tc) AGAINST(?)', ['search']], '>=', '0.9']
                ])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->delete('user')->where([
    [['MATCH(name_sc, name_tc) AGAINST(?)', ['search']], '>=', '0.9']
]);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . "<hr>";

                $s = $sql->select('*', 'user')->where(['city' => 'la', 'area' => null, ['age', '>', '10'], ['soft', '<>', null], ['ware', 'IS', null]])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->select('*', 'user')->where(['city' => 'la', 'area' => null, ['age', '>', '10'], ['soft', '<>', null], ['ware', 'IS', null]]);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd);
                break;
            }
            case 'having': {
                $s = $sql->select(['id', 'name', '(6371 * ACOS(COS(RADIANS(31.239845)) * COS(RADIANS(`lat`)) * COS(RADIANS(`lng`) - RADIANS(121.499662)) + SIN(RADIANS(31.239845)) * SIN(RADIANS(`lat`)))) AS distance'], 'location')->having([
                    ['distance', '<', '2']
                ])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->select(['id', 'name', '(6371 * ACOS(COS(RADIANS(31.239845)) * COS(RADIANS(`lat`)) * COS(RADIANS(`lng`) - RADIANS(121.499662)) + SIN(RADIANS(31.239845)) * SIN(RADIANS(`lat`)))) AS distance'], 'location')->having([
    ['distance', '<', '2']
]);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . '<hr>';

                $s = $sql->select(['id', 'name',
                    [
                        '(6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(`lat`)) * COS(RADIANS(`lng`) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(`lat`)))) AS distance',
                        ['31.239845', '121.499662', '31.239845']
                    ]
                ], 'location')->having([
                    ['distance', '<', '2']
                ])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->select(['id', 'name',
    [
        '(6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(`lat`)) * COS(RADIANS(`lng`) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(`lat`)))) AS distance',
        ['31.239845', '121.499662', '31.239845']
    ]
], 'location')->having([
    ['distance', '<', '2']
]);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd);
                break;
            }
            case 'by': {
                $s = $sql->select('*', 'test')->by('id')->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->select('*', 'test')->by('id');</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . "<hr>";

                $s = $sql->select('*', 'test')->by(['index', 'id'])->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->select('*', 'test')->by(['index', 'id']);</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd) . "<hr>";

                $s = $sql->select('*', 'test')->by(['index', ['id', 'ASC']], 'DESC')->getSql();
                $sd = $sql->getData();
                $echo[] = "<pre>\$sql->select('*', 'test')->by(['index', ['id', 'ASC']], 'DESC');</pre>
<b>getSql() :</b> {$s}<br>
<b>getData():</b> <pre>" . json_encode($sd, JSON_PRETTY_PRINT) . "</pre>
<b>format() :</b> " . $sql->format($s, $sd);
                break;
            }
            case 'field': {
                $echo[] = "<pre>\$sql->field('abc');</pre>" . $sql->field('abc');
                $echo[] = "<pre>\$sql->field('abc', 'a_');</pre>" . $sql->field('abc', 'a_');
                $echo[] = "<pre>\$sql->field('x.abc');</pre>" . $sql->field('x.abc');
                $echo[] = "<pre>\$sql->field('def f');</pre>" . $sql->field('def f');
                $echo[] = "<pre>\$sql->field('def `f`', 'a_');</pre>" . $sql->field('def `f`', 'a_');
                $echo[] = "<pre>\$sql->field('x.def f');</pre>" . $sql->field('x.def f');
                $echo[] = "<pre>\$sql->field('x.def as f');</pre>" . $sql->field('x.def as f');
                $echo[] = "<pre>\$sql->field('SUM(num) all');</pre>" . $sql->field('SUM(num) all');
                $echo[] = "<pre>\$sql->field('SUM(x.num) all');</pre>" . $sql->field('SUM(x.num) all');
                $echo[] = "<pre>\$sql->field('SUM(x.`num`) all');</pre>" . $sql->field('SUM(x.`num`) all');
                $echo[] = "<pre>\$sql->field('FROM_UNIXTIME(time, \'%Y-%m-%d\') time');</pre>" . $sql->field('FROM_UNIXTIME(time, \'%Y-%m-%d\') time');
                $echo[] = "<pre>\$sql->field('(6371 * ACOS(COS(RADIANS(31.239845)) * COS(RADIANS(lat)) * COS(RADIANS(`lng`) - RADIANS(121.499662)) + SIN(RADIANS(31.239845)) * SIN(RADIANS(`lat`))))');</pre>" . $sql->field('(6371 * ACOS(COS(RADIANS(31.239845)) * COS(RADIANS(lat)) * COS(RADIANS(`lng`) - RADIANS(121.499662)) + SIN(RADIANS(31.239845)) * SIN(RADIANS(`lat`))))');
                $echo[] = "<pre>\$sql->field('MATCH(name_sc, name_tc) AGAINST(\"ok\") tmp');</pre>" . $sql->field('MATCH(name_sc, name_tc) AGAINST("ok") tmp');
                $echo[] = "<pre>\$sql->field('a\'bc');</pre>" . $sql->field('a\'bc');
                $echo[] = "<pre>\$sql->field('`a`WHERE`q` = SUM(0) AND `b` = \"abc\" LEFT JOIN `abc`');</pre>" . $sql->field('`a`WHERE`q` = SUM(0) AND `b` = "abc" LEFT JOIN `abc`');
                $echo[] = "<pre>\$sql->field('TEST(UTC_TIMESTAMP)');</pre>" . $sql->field('TEST(UTC_TIMESTAMP)');
                break;
            }
        }
        return join('', $echo) . '<br><br>' . $this->_getEnd();
    }
    
    public function consistentHash() {
        $echo = [];

        $echo[] = "<pre>Consistent::hash('abc');</pre>" . Consistent::hash('abc');
        $echo[] = "<pre>Consistent::hash('thisisnone');</pre>" . Consistent::hash('thisisnone');
        $echo[] = "<pre>Consistent::hash('haha');</pre>" . Consistent::hash('haha');

        return join('', $echo) . '<br><br>' . $this->_getEnd();
    }

    public function consistentDistributed() {
        $echo = [];

        $servers = ['srv-sh.test.simu', 'srv-cd.test.simu', 'srv-tk.test.simu'];
        $files = [8, 12, 18, 32, 89, 187, 678, 1098, 3012, 8901, 38141, 76291, 99981];
        $map = [];
        $cons = Consistent::get();
        $cons->add($servers);
        foreach ($files as $file) {
            $map[$file] = $cons->find($file);
        }
        $echo[] = "<pre>\$servers = ['srv-sh.test.simu', 'srv-cd.test.simu', 'srv-tk.test.simu'];
\$files = [8, 12, 18, 32, 89, 187, 678, 1098, 3012, 8901, 38141, 76291, 99981];
\$map = [];
\$cons = Consistent::get();
\$cons->add(\$servers);
foreach (\$files as \$file) {
    \$map[\$file] = \$cons->find(\$file);
}</pre>";
        $echo[] = '<table style="width: 100%;">';
        foreach ($map as $k => $v) {
            $echo[] = '<tr><th>' . htmlspecialchars($k . '') . '</th><td>' . htmlspecialchars($v ? $v : 'null') . ' (' . Consistent::hash($k) . ')</td></tr>';
        }
        $echo[] = '</table>';

        $cons->add('srv-sg.test.simu');
        $file = $files[Core::rand(0, count($files) - 1)];
        // $file = $files[3];
        $oldSrv = $map[$file];
        $newSrv = $cons->find($file);
        $echo[] = "<pre>\$cons->add('srv-sg.test.simu');
\$file = \$files[Core::rand(0, count(\$files) - 1)];
\$oldSrv = \$map[\$file];
\$newSrv = \$cons->find(\$file);</pre>";
        $echo[] = "<table style=\"width: 100%;\">
    <tr><th>File</th><td>$file</td></tr>
    <tr><th>Old</th><td>" . ($oldSrv ? $oldSrv : 'null') .  "</td></tr>
    <tr><th>New</th><td>" . ($newSrv ? $newSrv : 'null') . "</td></tr>
    <tr><th>State</th><td>" . (($oldSrv === $newSrv) ? '<b>Hit</b>' : 'Miss') . "</td></tr>
</table>";

        return join('', $echo) . '<br>' . $this->_getEnd();
    }

    public function consistentMigration() {
        $echo = [];
        // --- 生成初始数据，5000 条数据分 5 长表 ---
        $tables = ['table-0', 'table-1', 'table-2', 'table-3', 'table-4'];
        $rows = [];
        for ($i = 1; $i <= 5000; ++$i) {
            $rows[] = $i;
        }
        $cons = Consistent::get();
        $cons->add($tables);

        $echo[] = 'Row length: ' . count($rows) . '<br>Old tables:';
        foreach ($tables as $table) {
            $echo[] = ' ' . $table;
        }

        $echo[] = "<pre>\$newTables = ['table-5', 'table-6', 'table-7', 'table-8', 'table-9'];
\$rtn = \$cons->migration(\$rows, \$newTables);</pre>";

        // --- 即将增长到 10000 条数据，然后先模拟 5 表拆分为 10 表，再查看要迁移哪些数据，迁移量有多少 ---
        $newTables = ['table-5', 'table-6', 'table-7', 'table-8', 'table-9'];
        $rtn = $cons->migration($rows, $newTables);

        $count = count($rtn);
        $echo[] = 'Migration length: ' . $count . '<br>Added new tables: ';
        foreach ($newTables as $table) {
            $echo[] = ' ' . $table;
        }
        $echo[] = '<br><br>';

        $i = 0;
        $echo[] = '<table style="width: 100%;">';
        foreach ($rtn as $key => $item) {
            $echo[] = '<tr><th>' . $key . '</th><td>' . $item['old'] . '</td><td>' . $item['new'] . '</td></tr>';
            if ($i === 199) {
                break;
            }
            ++$i;
        }
        $echo[] = '</table>';

        return join('', $echo) . '... More ' . ($count - 200) . ' ...<br><br>' . $this->_getEnd();
    }

    public function consistentFast(): string {
        $echo = [];

        $rtn = Consistent::fast('a', Consistent::getRange(0, 30));
        $echo[] = "<pre>Consistent::fast('a', Consistent::getRange(0, 30))</pre>";
        $echo[] = json_encode($rtn);

        $rtn = Consistent::fast('b', Consistent::getRange(0, 30));
        $echo[] = "<pre>Consistent::fast('b', Consistent::getRange(0, 30))</pre>";
        $echo[] = json_encode($rtn);

        return join('', $echo) . '<br><br>' . $this->_getEnd();
    }

    public function text() {
        $echo = "<pre>Text::sizeFormat(2005)</pre>
" . Text::sizeFormat(2005, '') . "
<pre>json_encode(Text::parseUrl('HtTp://uSer:pAss@sUBDom.TopdOm23.CoM:29819/Adm@xw2Ksiz/dszas?Mdi=KdiMs1&a=JDd#hehHe'))</pre>
" . htmlspecialchars(json_encode(Text::parseUrl('HtTp://uSer:pAss@sUBDom.TopdOm23.CoM:29819/Adm@xw2Ksiz/dszas?Mdi=KdiMs1&a=JDd#hehHe'))) . "
<pre>json_encode(Text::parseUrl('HtTp://uSer@sUBDom.TopdOm23.CoM/Admx%20w2Ksiz/dszas'))</pre>
" . htmlspecialchars(json_encode(Text::parseUrl('HtTp://uSer@sUBDom.TopdOm23.CoM/Admx%20w2Ksiz/dszas'))) . "
<pre>json_encode(Text::parseUrl('C:\Windows\Mi@sc'))</pre>
" . htmlspecialchars(json_encode(Text::parseUrl('C:\Windows\Mi@sc'))) . "
<pre>json_encode(Text::parseUrl('../../abc?q=e'))</pre>
" . htmlspecialchars(json_encode(Text::parseUrl("../../abc?q=e"))) . "
<pre>Text::urlResolve('/', 'path?id=1');</pre>
" . htmlspecialchars(Text::urlResolve('/', 'path?id=1')) . "
<pre>Text::urlResolve('https://www.url.com/view/path', 'find');</pre>
" . htmlspecialchars(Text::urlResolve('https://www.url.com/view/path', 'find')) . "
<pre>Text::urlResolve('https://www.url.com/view/path', '/');</pre>
" . htmlspecialchars(Text::urlResolve('https://www.url.com/view/path', '/')) . "
<pre>Text::urlResolve('https://www.url.com/view/path/oh', '../ok/./index.js');</pre>
" . htmlspecialchars(Text::urlResolve('https://www.url.com/view/path/oh', '../ok/./index.js')) . "
<pre>Text::urlResolve('https://www.url.com/view/path/oh', '../hah/../dodo/../112/666/777/../en');</pre>
" . htmlspecialchars(Text::urlResolve('https://www.url.com/view/path/oh', '../hah/../dodo/../112/666/777/../en')) . "
<pre>Text::urlResolve('/hehe/ooo/', '../../../../../index.html');</pre>
" . htmlspecialchars(Text::urlResolve('/hehe/ooo/', '../../../../../index.html')) . "
<pre>Text::urlResolve('https://www.url.com/view/path', '/xxx/yyy');</pre>
" . htmlspecialchars(Text::urlResolve('https://www.url.com/view/path', '/xxx/yyy')) . "
<pre>Text::urlResolve('/', '//www.url.com/path');</pre>
" . htmlspecialchars(Text::urlResolve('/', '//www.url.com/path')) . "
<pre>Text::urlResolve('http://www.url.com/path', 'hTtps://www.url.com/path');</pre>
" . htmlspecialchars(Text::urlResolve('http://www.url.com/path', 'hTtps://www.url.com/path')) . "
<pre>Text::urlResolve('hTtp://www.url.com/path?ok=b', '?do=some');</pre>
" . htmlspecialchars(Text::urlResolve('hTtp://www.url.com/path?ok=b', '?do=some')) . "
<pre>Text::urlResolve('/', 'C:\\Windows\\Boot');</pre>
" . htmlspecialchars(Text::urlResolve('/', 'C:\\Windows\\Boot')) . "
<pre>Text::urlResolve('C:\\Windows\\Misc', '/');</pre>
" . htmlspecialchars(Text::urlResolve('C:\\Windows\\Misc', '/')) . "
<pre>Text::urlResolve('C:\\Windows\\Misc', '/xxx/yyy');</pre>
" . htmlspecialchars(Text::urlResolve('C:\\Windows\\Misc', '/xxx/yyy')) . "
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

    /**
     * --- END ---
     */
    private function _getEnd(): string {
        $rt = $this->_getRunTime();
        return 'Processed in ' . $rt . ' second(s), ' . round($rt * 1000, 4) . 'ms, ' . round($this->_getMemoryUsage() / 1024, 2) . ' K.<style>*{font-family:Consolas,"Courier New",Courier,FreeMono,monospace;line-height: 1.5;font-size:12px;}pre{padding:10px;background-color:rgba(0,0,0,.07);white-space:pre-wrap;word-break:break-all;}hr{margin:20px 0;border-color:#000;border-style:dashed;border-width:1px 0 0 0;}td,th{padding:5px;border:solid 1px #000;}</style><meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no">';
    }

}

