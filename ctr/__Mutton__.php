<?php
declare(strict_types = 1);

namespace ctr;

use sys\Ctr;

class __Mutton__ extends Ctr {

    // --- Index page ---
    public function index() {

        $fileList = [
            ['ctr/'],
            ['data/index.html', true],
            ['etc/const.php', true],
            ['etc/db.php'],
            ['etc/mailer.php'],
            ['etc/memcached.php'],
            ['etc/redis.php'],
            ['etc/route.php'],
            ['etc/session.php'],
            ['etc/set.php'],
            ['etc/sms.php'],
            ['etc/sql.php'],
            ['etc/ssh.php'],
            ['etc/wechat.php'],

            ['lib/Aes.php', true],
            ['lib/Captcha.php', true],
            ['lib/Db.php', true],
            ['lib/Mailer.php', true],
            ['lib/Memcached.php', true],
            ['lib/Net.php', true],
            ['lib/Redis.php', true],
            ['lib/Session.php', true],
            ['lib/Sms.php', true],
            ['lib/Sql.php', true],
            ['lib/Ssh.php', true],
            ['lib/Text.php', true],
            ['lib/Wechat.php', true]
        ];

        $this->loadView('__Mutton__/index');

    }

    // --- API ---

    

}

