<?php
declare(strict_types = 1);

namespace ctr;

use lib\Net;
use lib\Text;
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

    public function apiCheckRefresh() {
        if ($this->post('password') !== __MUTTON__PWD) {
            return [0, 'Password is incorrect.'];
        }
        $req = Net\Request::get();
        $req->setFollowLocation(true);
        $res = Net::get('https://api.github.com/repos/MaiyunNET/Mutton/contents/doc/mblob/', $req);
        if (!$res->content) {
            return [0, 'Network error, please try again.'];
        }
        $json = json_decode($res->content);
        $list = [];
        foreach ($json as $item) {
            $list[] = substr($item->name, 0, -6);
        }
        return [1, 'list' => $list];
    }

    public function apiCheck() {
        if ($this->post('password') !== __MUTTON__PWD) {
            return [0, 'Password is incorrect.'];
        }
        $res = Net::get('https://raw.githubusercontent.com/MaiyunNET/Mutton/master/doc/mblob/'.$this->post('ver').'.mblob');
        if (!$res->content) {
            return [0, 'Network error, please try again.'];
        }
        if (!($blob = gzinflate($res->content))) {
            return [0, 'Decryption failed.'];
        }
        if (!($json = json_decode($blob, true))) {
            return [0, 'Decryption failed.'];
        }
        $list = [];     // --- 有差异的文件 ---
        $qlist = [];    // --- 缺失的文件（夹） ---
        $dlist = [];    // --- 多余的文件（夹） ---
        $qlistConst = [];
        $dlistConst = [];

        $nowList = $this->_buildList();

        // --- 先判断目录结构 ---
        foreach ($nowList['folders'] as $k => $v) {
            if (isset($json['folders'][$k])) {
                unset($json['folders'][$k]);
            } else {
                // --- 本地多出目录 ---
                $dlist[] = $k;
            }
        }
        // --- 校验 md5，校验文件是否多出和缺失 ---
        foreach ($nowList['files'] as $k => $v) {
            if (isset($json['files'][$k])) {
                $match = [
                    '/^etc\\/(?!const\\.php).+/',
                    '/^stc\\/index\\.js/'
                ];
                if ($this->post('mode') === '1') {
                    $match[] = '/^stc-ts\\/.+/';
                } else {
                    $match[] = '/^stc-ts\\/(index\\.ts||tsconfig\\.js||tslint\\.json)/';
                }
                if (!Text::match($k, $match)) {
                    if ($json['files'][$k] !== $v) {
                        // --- 有差异 ---
                        $list[] = $k;
                    }
                }
                unset($json['files'][$k]);
            } else {
                // --- 本地多出文件 ---
                $dlist[] = $k;
            }
        }
        // --- 缺失文件文件夹序列 ---
        foreach ($json['folders'] as $k => $v) {
            if (($this->post('mode') === '1') && Text::match($k, [
                    '/^stc-ts\\/.*/',
                    '/^\\.gitignore/'
                ])) {
                // --- 在线模式，不计算 stc-ts 目录 ---
            } else {
                $qlist[] = $k;
            }
        }
        foreach ($json['files'] as $k => $v) {
            if (($this->post('mode') === '1') && Text::match($k, [
                    '/^stc-ts\\/.*/'
                ])) {
                // --- 在线模式，不计算 stc-ts 目录 ---
            } else {
                $qlist[] = $k;
            }
        }
        // --- 校验 const ---
        foreach ($nowList['const'] as $k => $v) {
            // --- 文件存在才判断 const ---
            if (isset($json['const'][$k])) {
                foreach ($v as $i => $v2) {
                    if (isset($json['const'][$k][$i])) {
                        unset($json['const'][$k][$i]);
                    } else {
                        // --- 本地多出本常量 ---
                        // --- [文件路径，常量名，常量值，const 还是 define]
                        $dlistConst[] = [$k, $i, $v2[1], $v2[0]];
                    }
                }
            }
        }
        // --- 缺失的常量 ---
        foreach ($json['const'] as $k => $v) {
            foreach ($v as $i => $v2) {
                $qlistConst[] = [$k, $i, $v2[1], $v2[0]];
            }
        }
        return [1, 'list' => $list, 'qlist' => $qlist, 'dlist' => $dlist, 'qlistConst' => $qlistConst, 'dlistConst' => $dlistConst, 'library' => $nowList['library']];
    }

    public function apiUpdate() {
        if ($this->post('password') !== __MUTTON__PWD) {
            return [0, 'Password is incorrect.'];
        }
        if (!is_writable(ROOT_PATH.'ctr/')) {
            return [0, 'Server cannot be written.'];
        }
        $mode = $this->post('mode');
        $ver = $this->post('ver');
        $path = $this->post('path');
        $v = json_decode($this->post('v'), true);
        $mblob = json_decode($this->post('mblob'), true);

        $res = Net::get('https://raw.githubusercontent.com/MaiyunNET/Mutton/v'.$ver.'/'.$path);
        if (!$res->content) {
            return [0, 'Network error, please try again.'];
        }
        file_put_contents($path, $res->content);
        return [1];
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
        $res = Net::get('https://api.github.com/repos/MaiyunNET/Mutton/releases/latest');
        if (!$res->content) {
            return [0, 'Network error, please try again.'];
        }
        $json = json_decode($res->content);
        preg_match('/[0-9\\.]+/', $json->tag_name, $matches);
        $version = $matches[0];
        // --- 获取 mblob ---
        $res = Net::get('https://raw.githubusercontent.com/MaiyunNET/Mutton/master/doc/mblob/'.$version.'.mblob');
        if (!$res->content) {
            return [0, 'Network error, please try again.'];
        }
        if (!($blob = gzinflate($res->content))) {
            return [0, 'Decryption failed.'];
        }
        if (!($json = json_decode($blob, true))) {
            return [0, 'Decryption failed.'];
        }
        return [1, 'version' => $matches[0], 'mblob' => $json];
    }

    /**
     * --- 建立本地的路径 ---
     * @return array
     */
    private function _buildList(): array {
        $list = [
            'files' => [],
            'folders' => [],
            'const' => [],
            'library' => []
        ];
        // --- 本地库 ---
        $dir = dir(LIB_PATH);
        while (($name = $dir->read()) !== false) {
            if (!is_file(LIB_PATH.$name)) {
                continue;
            }
            $list['library'][] = explode('.', $name)[0];
        }
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
                if ($name[0] === '_' && $name[1] === '_') {
                    continue;
                }
                $deep = $this->_buildListDeep($name.'/');
                $list['folders'] = array_merge($list['folders'], $deep['folders']);
                $list['files'] = array_merge($list['files'], $deep['files']);
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
                    $arr[$matches[1][$k]] = [0, $matches[2][$k]];
                }
            }

            preg_match_all('/define[\\S\\s]+?[\'"](.+?)[\'"]\\s*,\\s*([\\S\\s]+?)\\s*\\) *?;/i', $content, $matches);
            if (count($matches[0]) > 0) {
                foreach ($matches[0] as $k => $v) {
                    $arr[$matches[1][$k]] = [1, $matches[2][$k]];
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
        if (Text::match($path, [
            '/^\\.git\\//',
            '/^doc\\//',
            '/^\\.idea\\//',
            '/^ctr\\/.+/',
            '/^data\\/.+/',
            '/^log\\/.+/',
            '/^stc\\/(?!__Mutton__\\/).+/',
            '/^stc-ts\\/(?!__Mutton__\\/|typings\\/|typings\\/vue\\/).+/',
            '/^view\\/(?!__Mutton__\\/).+/'
        ])) {
            return $list;
        }
        $list['folders'][$path] = '';
        $dir = dir(ROOT_PATH.$path);
        while (($name = $dir->read()) !== false) {
            if (($name === '.') || ($name === '..')) {
                continue;
            }
            if (is_file(ROOT_PATH.$path.$name)) {
                if (Text::match($path.$name, [
                    '/^ctr\\/(?!__).+/',
                    '/^data\\/(?!index\\.html).+/',
                    '/^mod\\/(?!Mod\\.php).+/',
                    '/^stc\\/(?!__Mutton__\\/|index\\.html|index\\.js).+/',
                    '/^stc-ts\\/(?!__Mutton__\\/|typings\\/any\\.d\\.ts|typings\\/vue\\/index\\.d\\.ts|index\\.ts|tsconfig\\.json|tslint\\.json).+/',
                    '/^view\\/(?!__Mutton__\\/).+/'
                ])) {
                    continue;
                }
                $list['files'][$path.$name] = md5_file(ROOT_PATH.$path.$name);
            } else {
                $deep = $this->_buildListDeep($path.$name.'/');
                $list['folders'] = array_merge($list['folders'], $deep['folders']);
                $list['files'] = array_merge($list['files'], $deep['files']);
            }
        }
        return $list;
    }

}

