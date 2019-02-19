<?php
/**
 * User: JianSuoQiYue
 * Date: 2019-2-2 21:47
 * Last: 2019-2-19 23:32:11
 */
declare(strict_types = 1);

namespace lib\Comm;

use phpseclib\Crypt\RSA;

class Ssh2 {

    /* @var \phpseclib\Net\SSH2 $_link */
    private $_link;

    /** @var int 当前 SSH 状态 */
    private $_state = self::__FIRST;

    const __FIRST = 0;
    const __NORMAL = 1;
    const __CMD = 2;

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

        $this->_link = new \phpseclib\Net\SSH2($host, $port, 1);
        $this->_link->setWindowColumns(2000);
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
     * --- 执行一个命令行并立即返回 ---
     * @param string $cmd 命令语句
     * @return string|bool
     */
    public function exec(string $cmd) {
        return $this->_link->exec($cmd);
    }

    /**
     * --- 开启 PTY 模式 ---
     */
    public function enablePTY(): void {
        $this->_link->enablePTY();
    }

    /**
     * --- 关闭 PTY 模式 ---
     */
    public function disablePTY(): void {
        $this->_link->disablePTY();
    }

    /**
     * --- 检测是否是 PTY 模式 ---
     * @return bool
     */
    public function isPTYEnabled(): bool {
        return $this->_link->isPTYEnabled();
    }

    /**
     * --- 设置等待时间 ---
     * @param int $time
     */
    public function setTimeout(int $time): void {
        $this->_link->setTimeout($time);
    }

    /**
     * --- 输入语句 ---
     * @param string $cmd
     * @return bool
     */
    public function write(string $cmd): bool {
        if ($this->_state === self::__FIRST) {
            $this->_link->read();
            $this->_state = self::__NORMAL;
        }
        return $this->_link->write($cmd);
    }

    /**
     * --- 输入语句并发送（并执行） ---
     * @param string $cmd
     * @return bool
     */
    public function writeLine(string $cmd): bool {
        if ($this->_state === self::__FIRST) {
            $this->_link->read();
            $this->_state = self::__NORMAL;
        }
        return $this->_link->write($cmd."\n");
    }

    /**
     * --- 发送中断 ---
     * @return bool
     */
    public function sendCtrlC(): bool {
        return $this->_link->write("\x03");
    }

    /**
     * --- 关闭通道（channel） ---
     * If read() timed out you might want to just close the channel and have it auto-restart on the next read() call
     */
    public function reset(): void {
        $this->_link->reset();
    }

    /**
     * --- 读出返回内容 ---
     * @param string $expect 是否匹配
     * @return string
     */
    public function read(string $expect = ''): string {
        return $this->_link->read($expect);
    }

    /**
     * --- 仅读取返回值 ---
     * @return string
     */
    public function readValue(): string {
        $value = $this->_link->read();
        if ($value !== '') {
            $value = str_replace("\r\n", "\n", $value);
            $value = str_replace("\r", "\n", $value);
            $last = substr($value, -3);
            if ($last === ']# ') {
                if ($this->_state === self::__NORMAL) {
                    preg_match('/^.+?\\n([\\s\\S]*?)\\n?\\[.+?\\]# $/', $value, $matches);
                } else {
                    $this->_state = self::__NORMAL;
                    preg_match('/^([\\s\\S]*?)\\n?\\[.+?\\]# $/', $value, $matches);
                }
                return $matches[1];
            } else {
                if ($this->_state === self::__NORMAL) {
                    $this->_state = self::__CMD;
                    preg_match('/^.+?\\n([\\s\\S]*?)$/', $value, $matches);
                    return $matches[1];
                } else {
                    return $value;
                }
            }
        } else {
            return '';
        }
    }

    /**
     * --- 读取返回值（全部） ---
     * @return string
     */
    public function readAll(): string {
        $read = [$this->readValue()];
        while (!$this->isDone()) {
            if (($r = $this->readValue()) !== '') {
                $read[] = $r;
            }
        }
        return implode('', $read);
    }

    /**
     * --- 当前是否可以输入命令 ---
     * @return bool
     */
    public function isDone(): bool {
        if ($this->_state === self::__NORMAL) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * --- 关闭连接 ---
     */
    public function disconnect(): void {
        $this->_link->disconnect();
        $this->_link = NULL;
    }

}

