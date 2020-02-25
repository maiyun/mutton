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
            '__LOCALE_OBJ' => $this->_getLocaleJsonString(),
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
        if (($_POST['ver'] !== 'master') && version_compare($_POST['ver'], '5.5.0', '<')) {
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
        if (!$this->_hasConfig) {
            return [0, l('Please place the profile first.')];
        }
        if (!$this->_checkXInput($_POST, [
            'password' => ['require', __MUTTON__PWD, [0, l('Password is incorrect.')]],
            'ver' => ['require', [0, l('System error.')]]
        ], $return)) {
            return $return;
        }

        // --- TODO ---
    }

    /**
     * --- 创建本地文件的检测字串 ---
     * @return array
     */
    public function apiBuild() {
        if (!$this->_hasConfig) {
            return [0, l('Please place the profile first.')];
        }
        if (!$this->_checkXInput($_POST, [
            'password' => ['require', __MUTTON__PWD, [0, l('Password is incorrect.')]]
        ], $return)) {
            return $return;
        }

        if (file_put_contents(ROOT_PATH.'doc/mblob', gzdeflate(json_encode($this->_buildMBlobData()), 9)) === false) {
            return [0, l('No server write permissions.')];
        } else {
            return [1];
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
            $content = file_get_contents(LIB_PATH . $file);
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
    private function _buildMBlobData(): array {
        $list = [
            'file' => [
                'ctr/__Mutton__.php' => ['md5', ''],
                'data/locale/en.__Mutton__.json' => ['md5', ''],
                'data/locale/zh-CN.__Mutton__.json' => ['md5', ''],
                'data/locale/zh-TW.__Mutton__.json' => ['md5', ''],
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
                'stc-ts/__Mutton__/index.ts' => ['md5', ''],
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

