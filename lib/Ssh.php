<?php
/**
 * User: JianSuoQiYue
 * Date: 2018-7-29 13:25:44
 * Last: 2018-12-12 12:32:38
 */
declare(strict_types = 1);

namespace lib;

require ETC_PATH.'ssh.php';

class Ssh {

    /* @var resource $_link */
    private $_link;
    private $_sftp;
    private $_dir;

    /**
     * @param null|array $opt
     * @return Ssh
     * @throws \Exception
     */
    public static function get(?array $opt = []): Ssh {
        $ssh = new Ssh();
        $ssh->connect($opt);
        return $ssh;
    }

    /**
     * @param array $opt
     * @return bool
     * @throws \Exception
     */
    public function connect(array $opt = []): bool {
        $host = isset($opt['host']) ? $opt['host'] : SSH_HOST;
        $port = isset($opt['port']) ? $opt['port'] : SSH_PORT;
        $user = isset($opt['user']) ? $opt['user'] : SSH_USERNAME;
        $pwd = isset($opt['pwd']) ? $opt['pwd'] : SSH_PASSWORD;
        $pub = isset($opt['pub']) ? $opt['pub'] : SSH_PUB;
        $prv = isset($opt['prv']) ? $opt['prv'] : SSH_PRV;

        if (@($this->_link = ssh2_connect($host, $port))) {
            if ($pwd !== '') {
                if (@ssh2_auth_password($this->_link, $user, $pwd)) {
                    return true;
                } else {
                    $err = error_get_last();
                    throw new \Exception('[Error][Ssh] ' . $err['message'], $err['type']);
                }
            } else {
                if (@ssh2_auth_pubkey_file($this->_link, $user, $pub, $prv)) {
                    return true;
                } else {
                    $err = error_get_last();
                    throw new \Exception('[Error][Ssh] ' . $err['message'], $err['type']);
                }
            }
        } else {
            $err = error_get_last();
            throw new \Exception('[Error][Ssh] '.$err['message'], $err['type']);
        }
    }

    // --- 直接执行一条命令并立刻返回结果 ---
    public function exec(string $cmd): string {
        $stream = ssh2_exec($this->_link, $cmd);
        stream_set_blocking($stream, true);
        return stream_get_contents($stream);
    }

    /**
     * Initialize SFTP subsystem
     */
    private function _getSftp() {
        if(!$this->_sftp) {
            $this->_sftp = ssh2_sftp($this->_link);
        }
        return $this->_sftp;
    }

    /**
     * Get absolute path
     * @param string $file
     * @return string
     */
    private function _getFilename(string $file): string {
        if ($file[0] !== '/') {
            if ($this->_dir) {
                return $this->_dir . '/' . $file;
            } else {
                return '/' . $file;
            }
        } else {
            return $file;
        }
    }

    /**
     * --- Get a file content ---
     * @param $remoteFile
     * @return string
     */
    public function getFile(string $remoteFile): string {
        $file = $this->_getFilename($remoteFile);
        $data = file_get_contents('ssh2.sftp://' . $this->_getSftp() . $file);
        return $data;
    }

    public function putFile(string $remoteFile, mixed $data): bool {
        $file = $this->_getFilename($remoteFile);
        if(file_put_contents('ssh2.sftp://' . $this->_getSftp() . $file, $data) === false) {
            return false;
        }
        return true;
    }

    public function downloadFile(string $remoteFile, string $localFile): bool {
        $file = $this->_getFilename($remoteFile);
        return ssh2_scp_recv($this->_link, $file, $localFile);
    }

    public function uploadFile(string $localFile, string $remoteFile): bool {
        $file = $this->_getFilename($remoteFile);
        return ssh2_scp_send($this->_link, $localFile, $file);
    }

    // --- 关闭链接 ---
    public function disconnect(): void {
        ssh2_disconnect($this->_link);
        $this->_link = NULL;
    }

}

