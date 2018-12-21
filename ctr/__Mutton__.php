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
                        foreach ($json['const'] as $file => $arr) {
                            if (is_file(ROOT_PATH.$file)) {
                                $content = file_get_contents(ROOT_PATH.$file);
                                $txt = '';
                                foreach ($arr as $val) {
                                    if (strpos($content, $val) === false) {
                                        $txt .= $val.', ';
                                    }
                                }
                                if ($txt !== '') {
                                    $list[] = 'The file "'.$file.'" missing constants: '.substr($txt, 0, -2).'.';
                                }
                            }
                        }
                        return [1, 'list' => $list];
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
                'fileList' => [
                    'index.php' => '',
                    '.htaccess' => '',
                    'mod/Mod.php' => '',
                    'sys/Boot.php' => '',
                    'sys/Ctr.php' => '',
                    'sys/Route.php' => ''
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

                            preg_match_all('/const.*?([A-Z0-9_]+)/', $content, $matches);
                            if (count($matches[1]) > 0) {
                                foreach ($matches[1] as $val) {
                                    $arr[] = $val;
                                }
                            }

                            preg_match_all('/define.+?\'([A-Z0-9_]+?)\'/', $content, $matches);
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
                $json['fileList'][$file] = md5_file(ROOT_PATH.$file);
            }
            return [1, 'output' => base64_encode(gzdeflate(json_encode($json)))];
        } else {
            return [0, 'Password is incorrect.'];
        }
    }

}

