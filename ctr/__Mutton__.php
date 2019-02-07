<?php
declare(strict_types = 1);

namespace ctr;

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
        if ($this->post('password') == __MUTTON__PWD) {
            $code = $this->post('code');
            if ($code = base64_decode($code)) {
                if ($code = gzinflate($code)) {
                    if ($json = json_decode($code, true)) {
                        // --- 校验 md5 ---
                        $list = [];
                        $slist = [];  // --- 严格模式 ---

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
                                        } else {
                                            $slist[] = 'The file "etc/' . $fileName . '" does not found on Mutton Official Etc.';
                                        }
                                    }
                                }
                            }
                        }
                        $dir->close();
                        return [1, 'list' => $list, 'slist' => $this->post('strict') == '1' ? $slist : []];
                    } else {
                        return [0, 'Decryption failed.'];
                    }
                } else {
                    return [0, 'Decryption failed.'];
                }
            } else {
                return [0, 'Decryption failed.'];
            }
        } else {
            return [0, 'Password is incorrect.'];
        }
    }

    public function apiBuild() {
        if ($this->post('password') == __MUTTON__PWD) {
            $json = [
                // --- 以下检测 md5 用，顺带可以检测 lib 文件夹是否有第三方的 lib ---
                'fileList' => [
                    'ctr/__Mutton__.php' => '',
                    'etc/const.php' => '',
                    'mod/Mod.php' => '',
                    'stc/__Mutton__/index.css' => '',
                    'stc/__Mutton__/index.js' => '',
                    'sys/Boot.php' => '',
                    'sys/Ctr.php' => '',
                    'sys/Route.php' => '',
                    'index.php' => '',
                    '.htaccess' => ''
                ],
                'const' => []
            ];
            // --- lib ---
            $dir = dir(LIB_PATH);
            while (($fileName = $dir->read()) !== false) {
                if (($fileName !== '.') && ($fileName !== '..')) {
                    $file = LIB_PATH . $fileName;
                    if (is_file($file)) {
                        $json['fileList']['lib/'.$fileName] = '';
                    }
                }
            }
            $dir->close();
            // --- const ---
            $dir = dir(ETC_PATH);
            while (($fileName = $dir->read()) !== false) {
                if (($fileName !== '.') && ($fileName !== '..')) {
                    if ($fileName[0] !== '_') {
                        $file = ETC_PATH . $fileName;
                        if (is_file($file)) {
                            $arr = [];
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
                                    $arr[] = '\''.$val.'\'';
                                }
                            }
                            $json['const']['etc/'.$fileName] = $arr;
                        }
                    }
                }
            }
            $dir->close();
            // --- 检测 md5 ---
            foreach ($json['fileList'] as $file => $md5) {
                if (is_file(ROOT_PATH.$file)) {
                    $json['fileList'][$file] = md5_file(ROOT_PATH . $file);
                } else {
                    return [0, 'Can not found "'.$file.'".'];
                }
            }
            return [1, 'output' => base64_encode(gzdeflate(json_encode($json)))];
        } else {
            return [0, 'Password is incorrect.'];
        }
    }

}

