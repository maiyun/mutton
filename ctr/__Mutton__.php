<?php
declare(strict_types = 1);

namespace ctr;

use lib\Net;
use lib\Text;
use sys\Ctr;

class __Mutton__ extends Ctr {

    private $_hasConfig = false;

    public function _load() {
        if (is_file(ETC_PATH.'__mutton__.php')) {
            $this->_hasConfig = true;
            require_once ETC_PATH.'__mutton__.php';
        }

        $l = isset($_GET['l']) ? $_GET['l'] : 'en';
        if (!in_array($l, ['en', 'zh-CN', 'zh-TW'])) {
            $l = 'en';
        }
        $this->_loadLocale($l, '__Mutton__');
    }

    // --- Index page ---
    public function index() {
        return $this->_loadView('__Mutton__/index', [
            'hasConfig' => $this->_hasConfig,
            'local' => $this->_getLocale(),
            '' => '',
            '_xsrf' => $this->_xsrf
        ]);
    }

    // --- API ---

    // --- 检查 - 刷新按钮 ---
    public function apiRefresh() {
        if (!$this->_hasConfig) {
            return [0, l('Please place the profile first.')];
        }
        if (!$this->_checkXInput($_POST, [
            'password' => ['require', __MUTTON__PWD, [0, l('Password is incorrect.')]]
        ], $return)) {
            return $return;
        }
        $res = Net::get('https://api.github.com/repos/MaiyunNET/Mutton/releases');
        if (!$res->content) {
            return [0, l('Network error, please try again.')];
        }
        $json = json_decode($res->content);
        $list = [];
        foreach ($json as $item) {
            preg_match('/[0-9.]+/', $item->tag_name, $matches);
            $list[] = [
                'value' => $matches[0],
                'label' => $item->name
            ];
        }
        return [1, 'list' => $list];
    }

    // --- 检查 - 检查按钮 ---
    public function apiCheck() {
        if (!$this->_hasConfig) {
            return [0, l('Please place the profile first.')];
        }
        if (!$this->_checkXInput($_POST, [
            'password' => ['require', __MUTTON__PWD, [0, l('Password is incorrect.')]],
            'ver' => ['require', [0, l('System error.')]]
        ], $return)) {
            return $return;
        }
        if (version_compare($_POST['ver'], '5.5.0', '<')) {
            return [0, l('Version must be >= ?.', ['5.5.0'])];
        }
        $res = Net::get('https://cdn.jsdelivr.net/gh/MaiyunNET/Mutton@' . $_POST['ver'] . '/doc/mblob');
        if (!$res->content) {
            return [0, l('Network error, please try again.')];
        }
        if (!($blob = @gzinflate($res->content))) {
            return [0, l('The downloaded data is incomplete, please try again.')];
        }
        if (!($json = json_decode($blob, true))) {
            return [0, l('The downloaded data is incomplete, please try again.')];
        }

        // --- json 键：file, lib ---

        // --- 要展示给用户的 ---
        $noMatch = [];      // --- 有差异的文件 ---
        $miss = [];         // --- 缺失的文件 ---
        $missConst = [];    // --- 缺少的常量 ---
        $lib = [];          // --- 需要更新的库 ---
        $libFolder = [];    // --- 库存在但附属文件夹缺失 ---

        // --- 判断是否有缺失文件、差异文件 ---
        foreach ($json['file'] as $file => $item) {
            if ($item[0] === 'must') {
                // --- 文件必须存在，并且要与框架原内容保持一致（md5 必须一样） ---
                if (is_file(ROOT_PATH . $file)) {
                    // --- 检查内容是否一致 ---
                    if (md5_file(ROOT_PATH . $file) !== $item[1]) {
                        $noMatch[] = $file;
                    }
                } else {
                    $miss[] = $file;
                }
            } else if ($item[0] === 'md5') {
                // --- 若存在则校验 md5，否则不校验 ---
                if (is_file(ROOT_PATH . $file) && (md5_file(ROOT_PATH . $file) !== $item[1])) {
                    $noMatch[] = $file;
                }
            } else if ($item[0] === 'const-must' || $item[0] === 'const') {
                // --- 常量文件，必须存在 ---
                if (is_file(ROOT_PATH . $file)) {
                    // --- 存在，则判断 const 是否都存在 ---
                    $local = $this->_getConstList(file_get_contents(ROOT_PATH . $file));
                    $res = $this->_checkMissConst($item[1], $local);
                    if (count($res) > 0) {
                        $missConst[$file] = $res;
                    }
                } else {
                    if ($item[0] === 'const-must') {
                        $miss[] = $file;
                    }
                }
            }
        }
        // --- 判断库是否有更新 ---
        $local = $this->_getLibList();
        foreach ($json['lib'] as $name => $data) {
            if (!isset($local[$name])) {
                continue;
            }
            if (version_compare($local[$name], $data['ver'], '>=')) {
                // --- 本地库版本大于等于线上库版本 ---
                if ($local['folder'] && !is_dir(LIB_PATH . $name)) {
                    $libFolder[] = $local;
                }
                continue;
            }
            // --- 本地库小于线上库，需要更新 ---
            $data['localVer'] = $local[$name]['ver'];
            $lib[] = $data;
        }
        return [1, 'noMatch' => $noMatch, 'miss' => $miss, 'missConst' => $missConst, 'lib' => $lib, 'libFolder' => $libFolder];
    }

    // --- 自动升级 ---
    public function apiUpdate() {
        if ($this->post('password') !== __MUTTON__PWD) {
            return [0, 'Password is incorrect.'];
        }
        if (!$this->isWritable(ROOT_PATH.'ctr/')) {
            return [0, 'Server cannot be written.'];
        }
        // --- ["list", "qlist", "dlist", "qdlistConst"] ---
        $mode = $this->post('mode');
        $ver = $this->post('ver');
        $path = $this->post('path');
        $library = json_decode($this->post('library'), true); // 本地已装 library
        $isFile = substr($path, -1) === '/' ? false : true;

        $res = new \stdClass();
        if (in_array($mode, [0, 1, 3])) {
            if ($isFile) {
                $res = Net::get('https://cdn.jsdelivr.net/gh/MaiyunNET/Mutton@v' . $ver . '/' . $path);
                if (!$res->content) {
                    return [0, 'Network error.'];
                }
            }
        }
        switch ($mode) {
            case 0:
                // --- md5 不同，直接替换 ---
                $match = [
                    '/^etc\\/(?!const\\.php).+/',
                    '/^stc\\/index\\.js/',
                    '/^stc-ts\\/(index\\.ts|tsconfig\\.js|tslint\\.json)/'
                ];
                if (!Text::match($path, $match)) {
                    file_put_contents(ROOT_PATH.$path, $res->content);
                }
                return [1, 'File "'.$path.'" replacement success.'];
            case 1:
                // --- 本地缺失文件/文件夹，如果不是 lib，则直接补，如果是 lib，则判断是否安装了相应 lib，安装了直接补 ---
                if (substr($path, 0, 4) !== 'lib/') {
                    if ($isFile) {
                        file_put_contents(ROOT_PATH.$path, $res->content);
                        return [1, 'File "'.$path.'" replacement success.'];
                    } else {
                        $this->mkdir(ROOT_PATH.$path, 0755);
                        return [1, 'Folder "'.$path.'" has been created.'];
                    }
                } else {
                    // --- 判断缺失的文件，lib 是否是已安装的 lib ---
                    if (preg_match('/^lib\\/(.+?)\\//', $path, $matches)) {
                        if (in_array($matches[0], $library)) {
                            if ($isFile) {
                                file_put_contents(ROOT_PATH.$path, $res->content);
                                return [1, 'File "'.$path.'" replacement success.'];
                            } else {
                                $this->mkdir(ROOT_PATH.$path, 0755);
                                return [1, 'Folder "'.$path.'" has been created.'];
                            }
                        } else {
                            // --- 没有安装 ---
                            return [1, 'Lib "'.$matches[0].'" not installed.'];
                        }
                    } else {
                        // --- 无需替换 ---
                        return [1, 'Lib "'.$matches[0].'" not installed.'];
                    }
                }
            case 2:
                // --- 多出来的文件/文件夹 ---
                // 多出来理应删掉（ctr 等之类的不会被删掉，因为压根不会统计出来），但，如果不是 lib 里的直接删，如果是 lib，则判断是否安装了相应 lib，安装了直接删 ---
                if (substr($path, 0, 4) !== 'lib/') {
                    if ($isFile) {
                        unlink(ROOT_PATH.$path);
                        return [1, 'File "'.$path.'" deleted.'];
                    } else {
                        $this->rmdir(ROOT_PATH.$path);
                        return [1, 'Folder "'.$path.'" deleted.'];
                    }
                } else {
                    // --- 判断多出来的文件，是否 lib 已安装 ---
                    if (preg_match('/^lib\\/(.+?)\\//', $path, $matches)) {
                        if (in_array($matches[0], $library)) {
                            if ($isFile) {
                                unlink(ROOT_PATH.$path);
                                return [1, 'File "'.$path.'" deleted.'];
                            } else {
                                $this->rmdir(ROOT_PATH.$path);
                                return [1, 'Folder "'.$path.'" deleted.'];
                            }
                        } else {
                            // --- 没有安装 ---
                            return [1, 'Lib "'.$matches[0].'" not installed.'];
                        }
                    } else {
                        // --- 无需删除 ---
                        return [1, 'Lib "'.$matches[0].'" not installed.'];
                    }
                }
            default:
                // --- 常量缺失/多出 ---
                // --- 多出无所谓，就看缺失的 ---
                // --- 先把原常量内容都遍历出来 ---
                $arr = [];
                $content = file_get_contents(ROOT_PATH.$path);
                preg_match_all('/(define|const)([(\\\'\s]+)([A-Za-z0-9_]+)([\s\\\'][\s=,]+)([\S\s]+?)\)?;/i', $content, $matches);
                if (count($matches[0]) > 0) {
                    foreach ($matches[0] as $k => $v) {
                        $arr[$matches[3][$k]] = $matches[5][$k];
                    }
                }

                // --- 开始组成新的文件 ---
                // --- 多出和缺失都无所谓，把过去文件的数据替换进去就可以了 ---
                $content = $res->content;
                foreach ($arr as $k => $v) {
                    $content = preg_replace('/(define|const)([(\\\'\s]+)([A-Za-z0-9_]+)([\s\\\'][\s=,]+)([\S\s]+?)(\)?;)/i', '$1$2$3$4'.$v.'$6', $content);
                }
                file_put_contents(ROOT_PATH.$path, $content);
                return [1, 'File "'.$path.'" repair is complete.'];
        }
    }

    /**
     * --- 创建本地文件的检测字串 ---
     * @return array
     */
    public function apiBuild() {
        if ($this->post('password') !== __MUTTON__PWD) {
            return [0, 'Password is incorrect.'];
        }
        $mode = $this->post('mode');
        $json = $this->_buildList();
        if ($mode === '0') {
            $blob = gzdeflate(json_encode($json));
            return [1, 'blob' => base64_encode($blob), 'ver' => VER];
        } else if ($mode === '2') {
            return [1, 'blob' => base64_encode(json_encode($json)), 'ver' => VER];
        } else {
            $blob = gzdeflate(json_encode($json));
            if (file_put_contents(ROOT_PATH.'doc/mblob/'.VER.'.mblob', $blob) === false) {
                return [0, 'Permission denied.'];
            } else {
                return [1, 'source' => $json];
            }
        }
    }

    /**
     * --- 获取最新版本号 ---
     * @return array
     */
    public function apiGetLatestVer() {
        if ($this->post('password') !== __MUTTON__PWD) {
            return [0, 'Password is incorrect.'];
        }
        $res = Net::get('https://api.github.com/repos/MaiyunNET/Mutton/releases/latest');
        if (!$res->content) {
            return [0, 'Network error, please try again.'];
        }
        $json = json_decode($res->content);
        preg_match('/[0-9\\.]+/', $json->tag_name, $matches);
        $version = $matches[0];
        return [1, 'version' => $version];
    }

    // --- 以下是内部工具方法 ---

    /**
     * --- 通过字符串获取定义的常量列表 ---
     * @param string $content
     * @return array
     */
    private function _getConstList(string $content): array {
        $constList = [];
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);
        $content = preg_replace('/\/\/.+?(\n|$)/', '', $content);
        preg_match_all('/(define|const)[(\'\s]+([A-Za-z0-9_]+)[\s\'][\s=,]+([\S\s]+?)\)?;/i', $content, $matches);
        if (count($matches[0]) > 0) {
            foreach ($matches[0] as $k => $v) {
                $constList[] = $matches[2][$k];
            }
        }
        return $constList;
    }

    /**
     * --- 根据 base 查找 arr 中缺失的 const ---
     * @param array $base
     * @param array $arr
     * @return array
     */
    private function _checkMissConst(array $base, array $arr): array {
        $miss = [];
        foreach ($base as $val) {
            if (!in_array($val, $arr)) {
                $miss[] = $val;
            }
        }
        return $miss;
    }

    /**
     * --- 获取所有库的列表和信息 ---
     * @return array
     */
    private function _getLibList(): array {
        $lib = [];
        $libDir = dir(LIB_PATH);
        while (($file = $libDir->read()) !== false) {
            if (($file === '.') || ($file === '..')) {
                continue;
            }
            if (!is_file(LIB_PATH . $file)) {
                continue;
            }
            $name = explode('.', $file)[0];
            $content = file_get_contents($name);
            preg_match('/CONF - (.+?) - END/', $content, $match);
            $lib[$name] = json_decode($match[1], true);
        }
        $libDir->close();
        return $lib;
    }

    /**
     * --- 建立本地的路径 ---
     * @return array
     */
    private function _buildMBlob(): array {
        $list = [
            'file' => [
                'ctr/__Mutton__.php' => ['md5', ''],
                'data/index.html' => ['must', ''],
                'etc/const.php' => ['must', ''],
                'etc/db.php' => ['const', []],
                'etc/kv.php' => ['const', []],
                'etc/route.php' => ['const', []],
                'etc/session.php' => ['const', []],
                'etc/set.php' => ['const-must', []],
                'etc/sql.php' => ['const', []],
                'log/index.html' => ['must', ''],
                'mod/Mod.php' => ['must', ''],
                'stc/__Mutton__/index.css' => ['md5', ''],
                'stc/__Mutton__/index.js' => ['md5', ''],
                'stc/index.html' => ['must', ''],
                'stc/__Mutton__/index.ts' => ['md5', ''],
                'sys/Boot.php' => ['must', ''],
                'sys/Ctr.php' => ['must', ''],
                'sys/Locale.php' => ['must', ''],
                'sys/Route.php' => ['must', ''],
                'view/__Mutton__/index.php' => ['md5', ''],
                '.htaccess' => ['md5', ''],
                'index.php' => ['must', '']
            ],
            'lib' => $this->_getLibList()
        ];
        // --- 获取 md5 和 const ---
        foreach ($list['file'] as $file => $item) {
            if ($item[0] === 'must' || $item[0] === 'md5') {
                $list['file'][$file][1] = md5_file(ROOT_PATH . $file);
            } else if ($item[0] === 'const' || $item[0] === 'const-must') {
                $list['file'][$file][1] = $this->_getConstList(file_get_contents(ROOT_PATH . $file));
            }
        }
        return $list;
    }

}

