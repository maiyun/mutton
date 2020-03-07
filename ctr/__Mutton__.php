<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2018-10-17
 * Last: 2020-3-3 17:03:21
 * --- 发布流程：修改 const.php 版本号，添加 CHANGELOG，创建 mblob 文件 ---
 */
declare(strict_types = 1);

namespace ctr;

use lib\Fs;
use lib\Net;
use sys\Ctr;
use ZipArchive;

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

        if (__MUTTON__PWD === 'Your password') {
            return [0, l('The password cannot be set to "?".', ['Your password'])];
        }
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
            'password' => ['require', __MUTTON__PWD, [0, l('Password is incorrect.')]],
            'mirror' => ['require', ['global', 'cn'], [0, l('System error.')]]
        ], $return)) {
            return $return;
        }
        $url = '';
        if ($_POST['mirror'] === 'global') {
            $url = 'https://api.github.com/repos/MaiyunNET/Mutton/tags';
        } else if ($_POST['mirror'] === 'cn') {
            $url = 'https://gitee.com/api/v5/repos/MaiyunNET/Mutton/tags';
        }
        $res = Net::get($url);
        if (!$res->content) {
            return [0, l('Network error, please try again.') . '(' . $res->error . ')'];
        }
        $json = json_decode($res->content, true);
        if ($_POST['mirror'] === 'cn') {
            $json = array_reverse($json);
        }

        $latestVer = '';
        $list = [];
        foreach ($json as $item) {
            preg_match('/[0-9.]+/', $item['name'], $matches);
            if ($latestVer === '') {
                $latestVer = $matches[0];
            }
            $list[] = [
                'value' => $matches[0],
                'label' => $item['name']
            ];
        }
        return [1, 'list' => $list, 'latestVer' => $latestVer];
    }

    // --- 检查 - 检查按钮 ---
    public function apiCheck() {
        if (!$this->_hasConfig) {
            return [0, l('Please place the profile first.')];
        }
        if (!$this->_checkXInput($_POST, [
            'password' => ['require', __MUTTON__PWD, [0, l('Password is incorrect.')]],
            'ver' => ['require', [0, l('System error.')]],
            'verName' => ['require', [0, l('System error.')]],
            'mirror' => ['require', ['global', 'cn'], [0, l('System error.')]]
        ], $return)) {
            return $return;
        }
        if (($_POST['ver'] !== 'master') && version_compare($_POST['ver'], '6.0.0', '<')) {
            return [0, l('Version must be >= ?.', ['6.0.0'])];
        }
        if (($data = $this->_getOnlineMblobData($_POST['mirror'], $_POST['verName']))[0] === 0) {
            return $data;
        }
        $data = $data[1];

        // --- json 键：file, lib ---

        // --- 要展示给用户的 ---
        $noMatch = [];      // --- 有差异的文件 ---
        $miss = [];         // --- 缺失的文件 ---
        $missConst = [];    // --- 缺少的常量 ---
        $lib = [];          // --- 需要更新的库 ---
        $libFolder = [];    // --- 库存在但附属文件夹缺失 ---
        $remove = [];       // --- 需要移除的文件 ---

        // --- 判断是否有缺失文件、差异文件 ---
        foreach ($data['file'] as $file => $item) {
            if ($item[0] === 'must') {
                // --- 文件必须存在，并且要与框架原内容保持一致（md5 必须一样） ---
                if (is_file(ROOT_PATH . $file)) {
                    // --- 文件存在，判断是否匹配 ---
                    if (md5_file(ROOT_PATH . $file) !== $item[1]) {
                        $noMatch[] = $file;
                    }
                    // --- 不能和 is_file 一块写，因为文件存在，并且匹配，则不做任何处理 ---
                } else {
                    $miss[] = $file;
                }
            } else if ($item[0] === 'mustn') {
                // --- 文件必须存在，但无需和框架原内容保持一致（不校验 md5） ---
                if (!is_file(ROOT_PATH . $file)) {
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
            } else if ($item[0] === 'remove') {
                // --- 实际运营中可能需要删除的文件 ---
                if (is_file(ROOT_PATH . $file)) {
                    $remove[] = $file;
                }
            }
        }
        // --- 判断库是否有更新 ---
        $localLibs = $this->_getLibList();
        foreach ($data['lib'] as $name => $item) {
            if (!isset($localLibs[$name])) {
                continue;
            }
            if (version_compare($localLibs[$name]['ver'], $item['ver'], '>=')) {
                // --- 本地库版本大于等于线上库版本，不需要更新，检测附属文件夹是否存在 ---
                if ($localLibs[$name]['folder'] && !is_dir(LIB_PATH . $name)) {
                    $libFolder[$name] = $localLibs[$name];
                }
                continue;
            }
            // --- 本地库小于线上库，需要更新 ---
            $item['localVer'] = $localLibs[$name]['ver'];
            $lib[$name] = $item;
        }

        // --- 以下显示“系统”选项卡线上库列表里 ---
        $onlineLibs = [];
        foreach ($data['lib'] as $name => $item) {
            $onlineLibs[] = ['label' => $name . ' ' . $item['ver'] . ($item['folder'] ? ' ' . l('(Contains folders)') : ''), 'value' => $name];
        }

        return [1,
            'lastTime' => date('Y-m-d H:i:s', $data['lastTime']),
            'noMatch' => $noMatch,
            'miss' => $miss,
            'missConst' => $missConst,
            'lib' => $lib,
            'libFolder' => $libFolder,
            'remove' => $remove,

            'onlineLibs' => $onlineLibs
        ];
    }

    /**
     * --- 获取需要自动更新的文件列表 ---
     * @return array
     */
    public function apiGetUpgradeList() {
        if (!$this->_hasConfig) {
            return [0, l('Please place the profile first.')];
        }
        if (!$this->_checkXInput($_POST, [
            'password' => ['require', __MUTTON__PWD, [0, l('Password is incorrect.')]],
            'verName' => ['require', [0, l('System error.')]],
            'mirror' => ['require', ['global', 'cn'], [0, l('System error.')]]
        ], $return)) {
            return $return;
        }
        $ver = 'master';
        if ($_POST['verName'] !== 'master') {
            preg_match('/[0-9.]+/', $_POST['verName'], $matches);
            $ver = $matches[0];
        }
        if (($ver !== 'master') && version_compare($ver, '6.0.0', '<')) {
            return [0, l('Version must be >= ?.', ['6.0.0'])];
        }
        if (($data = $this->_getOnlineMblobData($_POST['mirror'], $_POST['verName']))[0] === 0) {
            return $data;
        }
        $data = $data[1];

        $list = [];
        foreach ($data['file'] as $file => $item) {
            if (($item[0] !== 'must') && ($item[0] !== 'md5')) {
                continue;
            }
            if (substr($file, 0, 4) === 'lib/') {
                continue;
            }
            $url = '';
            if ($item[0] === 'must') {
                if (!is_file(ROOT_PATH . $file) || (md5_file(ROOT_PATH . $file) !== $item[1])) {
                    $url = $file;
                }
            } else if ($item[0] === 'md5') {
                if (is_file(ROOT_PATH . $file) && (md5_file(ROOT_PATH . $file) !== $item[1])) {
                    $url = $file;
                }
            }
            if ($url !== '') {
                if ($_POST['mirror'] === 'global') {
                    $list[$file] = 'https://github.com/MaiyunNET/Mutton/raw/' . $_POST['verName'] . '/' . $url;
                } else if ($_POST['mirror'] === 'cn') {
                    $list[$file] = 'https://gitee.com/MaiyunNET/Mutton/raw/' . $_POST['verName'] . '/' . $url;
                }
            }
        }

        return [1, 'list' => $list];
    }

    /**
     * --- 执行升级操作的函数，敏感 ---
     * @return array
     */
    public function apiUpgrade() {
        if (!$this->_hasConfig) {
            return [0, l('Please place the profile first.')];
        }
        if (!$this->_checkXInput($_POST, [
            'password' => ['require', __MUTTON__PWD, [0, l('Password is incorrect.')]],
            'file' => ['require', [0, l('System error.')]],
            'url' => ['require', [0, l('System error.')]]
        ], $return)) {
            return $return;
        }
        if (strpos($_POST['file'], '..') !== false || strpos($_POST['file'], './') !== false) {
            return [0, l('System error.')];
        }
        $checkUrl = substr($_POST['url'], 0, 35);
        if (($checkUrl !== 'https://github.com/MaiyunNET/Mutton') && ($checkUrl !== 'https://gitee.com/MaiyunNET/Mutton/')) {
            return [0, l('System error.')];
        }
        // --- 下载 ---
        $r = Net::get($_POST['url'], [
            'timeout' => 50,
            'follow' => true,
            'save' => ROOT_PATH . $_POST['file'],
            'reuse' => true
        ]);
        if ($r->content === '') {
            return [0, l('File "?" download failed.', [$_POST['file']])];
        }
        return [1];
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
     * --- 卸载本地库 ---
     * @return array
     */
    public function apiUninstallLib() {
        if (!$this->_hasConfig) {
            return [0, l('Please place the profile first.')];
        }
        if (!$this->_checkXInput($_POST, [
            'password' => ['require', __MUTTON__PWD, [0, l('Password is incorrect.')]],
            'lib' => ['require', [0, l('System error.')]]
        ], $return)) {
            return $return;
        }
        if (in_array($_POST['lib'], ['Fs', 'Net'])) {
            return [0, l('The library cannot be uninstalled.')];
        }
        if (!is_file(LIB_PATH . $_POST['lib'] . '.php')) {
            // --- 库本来就不存在，无需删除 ---
            return [1];
        }
        if (!@unlink(LIB_PATH . $_POST['lib'] . '.php')) {
            return [0, l('No server write permissions.')];
        }
        if (!is_dir(LIB_PATH . $_POST['lib'])) {
            return [1];
        }
        if (!Fs::rmdir(LIB_PATH . $_POST['lib'])) {
            return [0, l('No server write permissions.')];
        }
        return [1];
    }

    /**
     * --- 安装库 ---
     * @return array
     */
    public function apiInstallLib() {
        if (!$this->_hasConfig) {
            return [0, l('Please place the profile first.')];
        }
        if (!$this->_checkXInput($_POST, [
            'password' => ['require', __MUTTON__PWD, [0, l('Password is incorrect.')]],
            'lib' => ['require', [0, l('System error.')]],
            'verName' => ['require', [0, l('System error.')]],
            'mirror' => ['require', ['global', 'cn'], [0, l('System error.')]]
        ], $return)) {
            return $return;
        }
        $url = '';
        if ($_POST['mirror'] === 'global') {
            $url = 'https://github.com/MaiyunNET/Mutton/raw/'.$_POST['verName'].'/lib/'.$_POST['lib'].'.php';
        } else if ($_POST['mirror'] === 'cn') {
            $url = 'https://gitee.com/MaiyunNET/Mutton/raw/'.$_POST['verName'].'/lib/'.$_POST['lib'].'.php';
        }
        $res = Net::get($url, [
            'follow' => true,
            'reuse' => true
        ]);
        if (!$res->content) {
            Net::closeAll();
            return [0, l('Network error, please try again.').'('.$res->error.')'];
        }
        if (!($data = $this->_getLibData($res->content))) {
            Net::closeAll();
            return [0, l('The downloaded data is incomplete, please try again.')];
        }
        // --- 先写入本地文件 ---
        if (file_put_contents(LIB_PATH . $_POST['lib'] . '.php', $res->content) === false) {
            Net::closeAll();
            return [0, l('No server write permissions.')];
        }
        // --- 如果本来有目录，不管是否重装目录，都删除 ---
        if (is_dir(LIB_PATH . $_POST['lib']) && !Fs::rmdir(LIB_PATH .  $_POST['lib'])) {
            Net::closeAll();
            return [0, l('No server write permissions.')];
        }
        // --- 判断是否有目录 ---
        if ($data['folder']) {
            set_time_limit(120);
            // --- 创建目录 ---
            if (!@mkdir(LIB_PATH . $_POST['lib'])) {
                Net::closeAll();
                return [0, l('No server write permissions.')];
            }
            if (!@chmod(LIB_PATH . $_POST['lib'], 0755)) {
                Net::closeAll();
                return [0, l('No server write permissions.')];
            }
            // --- 循环遍历 ---
            foreach ($data['url'] as $file => $item) {
                // --- 是否创建子目录 ---
                $path = '';
                if (isset($item['path'])) {
                    Fs::mkdir(LIB_PATH . $_POST['lib'] . '/' . $item['path']);
                    $path = $item['path'];
                }
                // --- 保存的文件名是否有约定，还是随意保存名字 ---
                if (isset($item['save'])) {
                    // --- 有约定 ---
                    $name = $item['save'];
                } else {
                    $name = 'tmp' . rand(1000, 9999);
                }
                // --- 下载文件 ---
                if ($_POST['mirror'] !== 'global') {
                    if (isset($item['mirror-' . $_POST['mirror']])) {
                        $file = $item['mirror-' . $_POST['mirror']];
                    }
                }
                $file = str_replace('{ver}', $_POST['verName'], $file);
                // --- 下载 ---
                $r = Net::get($file, [
                    'timeout' => 50,
                    'follow' => true,
                    'save' => LIB_PATH . $_POST['lib'] . '/' . $path . $name,
                    'reuse' => true
                ]);
                if ($r->content === '') {
                    Net::closeAll();
                    return [0, l('File "?" download failed.', [$file])];
                }
                // --- 是否解压 ---
                if ($item['action'] === 'unzip') {
                    $zip = new ZipArchive();
                    if ($zip->open(LIB_PATH . $_POST['lib'] . '/' . $path . $name) !== true) {
                        Net::closeAll();
                        return [0, l('The decompression failed.')];
                    }
                    if (!$zip->extractTo(LIB_PATH . $_POST['lib'] . '/' . $path)) {
                        Net::closeAll();
                        return [0, l('The decompression failed.')];
                    }
                    $zip->close();
                    @unlink(LIB_PATH . $_POST['lib'] . '/' . $path . $name);
                }
            }
        }
        Net::closeAll();
        return [1];
    }

    /**
     * --- 获取本地库列表 ---
     * @return array
     */
    public function apiGetLocalLibs() {
        if (!$this->_hasConfig) {
            return [0, l('Please place the profile first.')];
        }
        if (!$this->_checkXInput($_POST, [
            'password' => ['require', __MUTTON__PWD, [0, l('Password is incorrect.')]]
        ], $return)) {
            return $return;
        }
        $libs = $this->_getLibList();
        $list = [];
        foreach ($libs as $lib => $data) {
            if ($data['folder'] && !is_dir(LIB_PATH . $lib)) {
                $list[] = ['label' => $lib . ' ' . $data['ver'] . ' ' . l('(The satellite folder was not found)'), 'value' => $lib];
            } else {
                $list[] = ['label' => $lib . ' ' . $data['ver'] . ($data['folder'] ? ' ' . l('(Contains folders)') : ''), 'value' => $lib];
            }
        }
        return [1, 'list' => $list];
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
            $lib[$name] = $this->_getLibData(file_get_contents(LIB_PATH . $file));
        }
        $libDir->close();
        return $lib;
    }

    /**
     * --- 获取库的 CONF 数据 ---
     * @param string $content
     * @return array|false
     */
    private function _getLibData(string $content): array {
        preg_match('/CONF - ([\s\S]+?) - END/', $content, $match);
        if (!isset($match[1])) {
            return false;
        }
        return json_decode($match[1], true);
    }

    /**
     * --- 获取线上 Mblob 文件的 data，返回 true 代表成功，数组为失败 ---
     * @param string $mirror 源
     * @param string $verName 版本名
     * @return array|true
     */
    private function _getOnlineMblobData(string $mirror, string $verName) {
        $url = '';
        if ($mirror === 'global') {
            $url = 'https://github.com/MaiyunNET/Mutton/raw/'.$verName.'/doc/mblob';
        } else if ($mirror === 'cn') {
            $url = 'https://gitee.com/MaiyunNET/Mutton/raw/'.$verName.'/doc/mblob';
        }
        $res = Net::get($url, [
            'follow' => true
        ]);
        if (!$res->content) {
            return [0, l('Network error, please try again.').'('.$res->error.')'];
        }
        if (!($blob = @gzinflate($res->content))) {
            return [0, l('The downloaded data is incomplete, please try again.')];
        }
        if (!($data = json_decode($blob, true))) {
            return [0, l('The downloaded data is incomplete, please try again.')];
        }
        return [1, $data];
    }

    /**
     * --- 建立本地的路径 ---
     * @return array
     */
    private function _buildMBlobData(): array {
        $list = [
            'file' => [
                'ctr/__Mutton__.php' => ['md5', ''],
                'ctr/middle.php' => ['mustn'],
                'ctr/test.php' => ['remove'],
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

                'lib/Captcha.php' => ['md5', ''],
                'lib/Crypto.php' => ['md5', ''],
                'lib/Db.php' => ['md5', ''],
                'lib/Fs.php' => ['md5', ''],
                'lib/Kv.php' => ['md5', ''],
                'lib/Net.php' => ['md5', ''],
                'lib/Session.php' => ['md5', ''],
                'lib/Sql.php' => ['md5', ''],
                'lib/Text.php' => ['md5', ''],

                'log/index.html' => ['must', ''],
                'mod/Mod.php' => ['must', ''],
                'mod/Session.php' => ['remove'],
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
        // --- 当前编译时间 ---
        $list['lastTime'] = time();
        return $list;
    }

}

