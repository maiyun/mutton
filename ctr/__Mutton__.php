<?php
declare(strict_types = 1);

namespace ctr;

use lib\Net;
use sys\Ctr;

class __Mutton__ extends Ctr {

    private $_hasConfig = false;

    public function __construct($param, $action) {
        if (is_file(ETC_PATH.'__mutton__.php')) {
            $this->_hasConfig = true;
            require_once ETC_PATH.'__mutton__.php';
        }
    }

    // --- Index page ---
    public function index() {
        $this->loadView('__Mutton__/index', [
            'hasConfig' => $this->_hasConfig
        ]);
    }

    // --- API ---

    public function apiCheck() {
        if ($this->post('password') !== __MUTTON__PWD) {
            return [0, 'Password is incorrect.'];
        }
        $code = $this->post('code');
        if (!($code = base64_decode($code))) {
            return [0, 'Decryption failed.'];
        }
        if (!($code = gzinflate($code))) {
            return [0, 'Decryption failed.'];
        }
        if (!($json = json_decode($code, true))) {
            return [0, 'Decryption failed.'];
        }
        $list = [];
        $slist = [];  // --- 严格模式 ---
        $flist = [];  // --- 完全模式 ---

        // --- 校验 exists ---


        // --- 校验 md5 ---
        $dir = dir(LIB_PATH);
        while (($fileName = $dir->read()) !== false) {
            if (($fileName !== '.') && ($fileName !== '..')) {
                // --- 不判断的话有可能是文件夹 ---
                if (is_file(LIB_PATH.$fileName)) {
                    if (isset($json['fileList']['lib/' . $fileName])) {
                        $file = LIB_PATH . $fileName;
                        $md5n = md5_file($file);
                        if ($md5n === $json['fileList']['lib/' . $fileName]) {
                            // --- 似乎没问题 ---
                        } else {
                            $list[] = 'The file "lib/' . $fileName . '" mismatch, original "' . $json['fileList']['lib/' . $fileName] . '", yours "' . $md5n . '".';
                            $fileNameS = substr($fileName, 0, strrpos($fileName, '.'));
                            if (is_dir(LIB_PATH.$fileNameS)) {
                                $list[] = 'Please replace the "lib/'.$fileNameS.'" folder.';
                            }
                        }
                        unset($json['fileList']['lib/' . $fileName]);
                    } else {
                        $slist[] = 'The file "lib/' . $fileName . '" does not found on Mutton Official Library.';
                    }
                }
            }
        }
        $dir->close();
        foreach ($json['fileList'] as $file => $md5) {
            if (is_file(ROOT_PATH.$file)) {
                $md5n = md5_file(ROOT_PATH.$file);
                if ($md5n === $md5) {
                    // --- 似乎没问题 ---
                } else {
                    $list[] = 'The file "'.$file.'" mismatch, original "'.$md5.'", yours "'.$md5n.'".';
                    $fileS = substr($file, 0, strrpos($file, '.'));
                    if (is_dir(ROOT_PATH.$fileS)) {
                        $list[] = 'Please replace the "'.$fileS.'" folder.';
                    }
                }
            } else {
                if (substr($file, 0, 3) === 'lib') {
                    $flist[] = 'The file "'.$file.'" not been installed.';
                }
            }
        }
        // --- 校验 const ---
        $dir = dir(ETC_PATH);
        while (($fileName = $dir->read()) !== false) {
            if (($fileName !== '.') && ($fileName !== '..')) {
                if ($fileName[0] !== '_') {
                    $file = ETC_PATH . $fileName;
                    // --- 也可能是个文件夹，文件夹不检测 ---
                    if (is_file($file)) {
                        if (isset($json['const']['etc/' . $fileName])) {
                            // --- 提取现在文件的进行比对，看看有没有现在文件有实际上不该有的 ---
                            $arr = [];
                            $barr = $json['const']['etc/' . $fileName];
                            $content = file_get_contents($file);
                            preg_match_all('/const\\s+?([A-Z0-9_]+)/', $content, $matches);
                            if (count($matches[1]) > 0) {
                                foreach ($matches[1] as $val) {
                                    $arr[] = $val;
                                }
                            }
                            preg_match_all('/define[\\s\\S]+?\'([A-Z0-9_]+?)\'/', $content, $matches);
                            if (count($matches[1]) > 0) {
                                foreach ($matches[1] as $val) {
                                    $arr[] = '\'' . $val . '\'';
                                }
                            }
                            foreach ($arr as $key => $val) {
                                if (in_array($val, $barr)) {
                                    unset($barr[$key]);
                                } else {
                                    $slist[] = 'The file "etc/' . $fileName . '" does not exist "' . $val . '" in Mutton Official Etc.';
                                }
                            }
                            foreach ($barr as $val) {
                                $list[] = 'The file "etc/' . $fileName . '" missing constants: ' . $val . '.';
                            }
                            unset($json['const']['etc/' . $fileName]);
                        } else {
                            $slist[] = 'The file "etc/' . $fileName . '" does not found on Mutton Official Etc.';
                        }
                    }
                }
            }
        }
        $dir->close();
        foreach ($json['const'] as $file => $arr) {
            $flist[] = 'The file "'.$file.'" not been installed.';
        }
        return [1, 'list' => $list, 'slist' => $this->post('strict') == '1' ? $slist : [], 'flist' => $this->post('full') == '1' ? $flist : []];
    }

    /**
     * --- 创建本地文件的检测字串 ---
     * @return array
     */
    public function apiBuild() {
        if ($this->post('password') !== __MUTTON__PWD) {
            return [0, 'Password is incorrect.'];
        }
        $blob = base64_encode(gzdeflate(json_encode($this->_buildList())));
        return [1, 'blob' => $blob, 'ver' => VER];
    }

    /**
     * --- 获取最新版本号 ---
     * @return array
     */
    public function apiGetLatestVer() {
        if ($this->post('password') !== __MUTTON__PWD) {
            return [0, 'Password is incorrect.'];
        }
        $res = Net::get('https://github.com/MaiyunNET/Mutton/releases/latest');
        preg_match('/g\\/v([0-9\\.]+)/', $res->content, $matches);
        return [1, 'version' => $matches[1]];
    }

    /**
     * --- 建立本地的路径
     * @return array
     */
    private function _buildList(): array {
        $list = [
            'files' => [],
            'folders' => [],
            'const' => []
        ];
        // --- 序列 ---
        $dir = dir(ROOT_PATH);
        while (($name = $dir->read()) !== false) {
            if (($name === '.') || ($name === '..')) {
                continue;
            }
            if (is_file(ROOT_PATH.$name)) {
                if (!in_array($name, ['.project', 'LICENSE', 'README.md'])) {
                    $list['files'][$name] = md5_file(ROOT_PATH . $name);
                }
            } else {
                if (!in_array($name, ['.git', 'doc', '.idea'])) {
                    $list['folders'][$name . '/'] = '';
                    $deep = $this->_buildListDeep($name.'/');
                    $list['folders'] = array_merge($list['folders'], $deep['folders']);
                    $list['files'] = array_merge($list['files'], $deep['files']);
                }
            }
        }
        $dir->close();
        // --- 常量 ---
        $dir = dir(ETC_PATH);
        while (($name = $dir->read()) !== false) {
            if (($name === '.') || ($name === '..')) {
                continue;
            }
            if ($name[0] === '_') {
                continue;
            }
            $file = ETC_PATH . $name;
            if (!is_file($file)) {
                continue;
            }
            $arr = [];
            $content = file_get_contents($file);

            preg_match_all('/const\\s+([a-z0-9_]+)\\s*=\\s*([\\S\\s]+?);/i', $content, $matches);
            if (count($matches[0]) > 0) {
                foreach ($matches[0] as $k => $v) {
                    $arr[$matches[1][$k]] = $matches[2][$k];
                }
            }

            preg_match_all('/define[\\S\\s]+?[\'"](.+?)[\'"]\\s*,\\s*([\\S\\s]+?)\\s*\\) *?;/i', $content, $matches);
            if (count($matches[0]) > 0) {
                foreach ($matches[1] as $k => $v) {
                    $arr[$matches[1][$k]] = $matches[2][$k];
                }
            }
            $list['const']['etc/'.$name] = $arr;
        }
        $dir->close();
        return $list;
    }
    private function _buildListDeep(string $path): array {
        $list = [
            'files' => [],
            'folders' => []
        ];
        $dir = dir(ROOT_PATH.$path);
        while (($name = $dir->read()) !== false) {
            if (($name === '.') || ($name === '..')) {
                continue;
            }
            if (is_file(ROOT_PATH.$path.$name)) {
                $list['files'][$path.$name] = md5_file(ROOT_PATH.$path.$name);
            } else {
                $list['folders'][$path.$name.'/'] = '';
                $deep = $this->_buildListDeep($path.$name.'/');
                $list['folders'] = array_merge($list['folders'], $deep['folders']);
                $list['files'] = array_merge($list['files'], $deep['files']);
            }
        }
        return $list;
    }

}

