<?php
/**
 * User: JianSuoQiYue
 * Date: 2019-2-2 21:47
 * Last: 2019-2-2 22:16:45
 */
declare(strict_types = 1);

namespace lib\Comm;

use phpseclib\Crypt\RSA;

class Ssh2 {

    /* @var \phpseclib\Net\SSH2 $_link */
    private $_link;

    /**
     * @param array $opt
     * @return bool
     * @throws \Exception
     */
    public function connect(array $opt = []): bool {
        $host = isset($opt['host']) ? $opt['host'] : COMM_SSH_HOST;
        $port = isset($opt['port']) ? $opt['port'] : COMM_SSH_PORT;
        $user = isset($opt['user']) ? $opt['user'] : COMM_SSH_USERNAME;
        $pwd = isset($opt['pwd']) ? $opt['pwd'] : COMM_SSH_PASSWORD;
        $pub = isset($opt['pub']) ? $opt['pub'] : COMM_SSH_PUB;
        $prv = isset($opt['prv']) ? $opt['prv'] : COMM_SSH_PRV;

        $this->_link = new \phpseclib\Net\SSH2($host, $port, 5);
        if ($pwd !== '') {
            if (@$this->_link->login($user, $pwd)) {
                return true;
            } else {
                throw new \Exception('[Error][lib\\Comm\\Ssh2] Password failed.');
            }
        } else {
            $rsa = new RSA();
            $rsa->setPublicKey(file_get_contents($pub));
            $rsa->setPrivateKey(file_get_contents($prv));
            if ($this->_link->login($user, $rsa)) {
                return true;
            } else {
                throw new \Exception('[Error][lib\\Comm\\Ssh2] Rsa failed.');
            }
        }
    }

    /**
     * --- 执行一个命令行 ---
     * @param string $cmd 命令语句
     * @return string
     */
    public function exec(string $cmd): string {
        return $this->_link->exec($cmd);
    }

    /**
     * --- 关闭连接 ---
     */
    public function disconnect(): void {
        $this->_link->disconnect();
        $this->_link = NULL;
    }

}

