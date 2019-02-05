<?php
/**
 * User: JianSuoQiYue
 * Date: 2019-2-2 22:11
 * Last: 2019-2-5 22:47:39
 */

declare(strict_types = 1);

namespace lib\Comm;

use phpseclib\Crypt\RSA;

class Sftp {

    /* @var \phpseclib\Net\SFTP $_link */
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

        $this->_link = new \phpseclib\Net\SFTP($host, $port);
        if ($pwd !== '') {
            if ($this->_link->login($user, $pwd)) {
                return true;
            } else {
                throw new \Exception('[Error][lib\\Comm\\Sftp] Password failed.');
            }
        } else {
            $rsa = new RSA();
            $rsa->setPublicKey(file_get_contents($pub));
            $rsa->setPrivateKey(file_get_contents($prv));
            if ($this->_link->login($user, $rsa)) {
                return true;
            } else {
                throw new \Exception('[Error][lib\\Comm\\Sftp] Rsa failed.');
            }
        }
    }

    /**
     * --- 获取当前目录 ---
     * @return string
     */
    public function pwd(): string {
        return $this->_link->pwd();
    }

    /**
     * --- 获取文件列表 ---
     * @param string $dir
     * @return array
     */
    public function list($dir = '.'): array {
        return $this->_link->nlist($dir);
    }

    /**
     * --- 获取详细列表 ---
     * @param string $dir
     * @return array
     */
    public function listDetail($dir = '.'): array {
        return $this->_link->rawlist($dir);
    }

    /**
     * --- 直接获取远程文件到一个字符串 ---
     * @param string $remoteFile 远程文件地址
     * @param int $offset 分段偏移开始
     * @param int $length 分段偏移长度
     * @return string
     */
    public function getFile(string $remoteFile, $offset = 0, $length = -1): string {
        return $this->_link->get($remoteFile, false, $offset, $length);
    }

    /**
     * --- 下载文件到本地 ---
     * @param string $remoteFile
     * @param string $localFile
     * @param int $offset
     * @param int $length
     * @return bool
     */
    public function downloadFile(string $remoteFile, string $localFile, $offset = 0, $length = -1): bool {
        return $this->_link->get($remoteFile, $localFile, $offset, $length);
    }

    /**
     * --- 直接将文件上传到远程 ---
     * @param string $remoteFile
     * @param $data
     * @return bool
     */
    public function putFile(string $remoteFile, $data): bool {
        return $this->_link->put($remoteFile, $data);
    }

    /**
     * --- 上传本地文件到远程 ---
     * @param string $localFile 本地绝对路径
     * @param string $remoteFile
     * @return bool
     */
    public function uploadFile(string $localFile, string $remoteFile): bool {
        return $this->_link->put($remoteFile, $localFile, \phpseclib\Net\SFTP::SOURCE_LOCAL_FILE);
    }

    /**
     * --- 进入一个目录 ---
     * @param string $dir
     * @return bool
     */
    public function cd(string $dir): bool {
        return $this->_link->chdir($dir);
    }

    /**
     * --- $mode 需要带 0，如 0777 ---
     * @param string $remoteFile 远程文件
     * @param int $mode 如 0777
     * @return int|bool
     */
    public function chmod(string $remoteFile, int $mode) {
        return $this->_link->chmod($mode, $remoteFile);
    }

    /**
     * --- 创建目录 ---
     * @param string $name
     * @param int $mode 如 0777
     * @return bool
     */
    public function mkdir(string $name, int $mode = -1): bool {
        return $this->_link->mkdir($name, $mode);
    }

    /**
     * --- 删除一个空目录 ---
     * @param string $name
     * @return bool
     */
    public function rmdir(string $name): bool {
        return $this->_link->rmdir($name);
    }

    /**
     * --- Danger 危险：这特么是个危险函数，尽量不要使用 ---
     * --- This is a very weixian's function, dont to use ---
     * --- 删除一个非空目录 ---
     * @param string $name 目录名
     * @return bool
     */
    public function rmdirDeep(string $name): bool {
        if ($this->cd($name)) {
            $list = $this->listDetail();
            foreach ($list as $item) {
                if ($item['type'] === 2) {
                    // --- 目录 ---
                    if ($item['filename'] !== '.' && $item['filename'] !== '..') {
                        if ($this->rmdirDeep($item['filename']) === false) {
                            return false;
                        }
                    }
                } else {
                    if ($this->rmfile($item['filename']) === false) {
                        return false;
                    }
                }
            }
            if ($this->cd('..')) {
                return $this->rmdir($name);
            }
        } else {
            return false;
        }
    }

    /**
     * --- 删除一个文件 ---
     * @param string $name
     * @return bool
     */
    public function rmfile(string $name): bool {
        return $this->_link->delete($name);
    }

    /**
     * --- 重命名文件或文件夹 ---
     * @param string $old
     * @param string $new
     * @return bool
     */
    public function rename(string $old, string $new): bool {
        return $this->_link->rename($old, $new);
    }

    /**
     * --- 检测目录或文件是否存在 ---
     * @param string $name
     * @return bool
     */
    public function exists(string $name): bool {
        return $this->_link->file_exists($name);
    }

    /**
     * --- 判断是否是文件 ---
     * @param string $name
     * @return bool
     */
    public function isFile(string $name): bool {
        return $this->_link->is_file($name);
    }

    /**
     * --- 判断是否是链接 ---
     * @param string $name
     * @return bool
     */
    public function isLink(string $name): bool {
        return $this->_link->is_link($name);
    }

    /**
     * --- 文件是否存在并且是否可读 ---
     * @param string $name
     * @return bool
     */
    public function isReadable(string $name): bool {
        return $this->_link->is_readable($name);
    }

    /**
     * --- 是否可写 ---
     * @param string $name
     * @return bool
     */
    public function isWritable(string $name): bool {
        return $this->_link->is_writable($name);
    }

    /**
     * --- 判断是否是目录 ---
     * @param string $name
     * @return bool
     */
    public function isDir(string $name): bool {
        return $this->_link->is_dir($name);
    }

    /**
     * --- 返回文件的类型（block，char，dir，fifo，file，link） ---
     * @param string $name
     * @return string|bool
     */
    public function getType(string $name) {
        return $this->_link->filetype($name);
    }

    /**
     * @param string $name
     * @param string $link
     * @return bool
     */
    public function symlink(string $name, string $link): bool {
        return $this->_link->symlink($name, $link);
    }

    /**
     * --- 读取链接的 target ---
     * @param string $link
     * @return string|bool
     */
    public function readLink(string $link) {
        return $this->_link->readlink($link);
    }

    public function disconnect(): void {
        $this->_link->disconnect();
        $this->_link = NULL;
    }

}

