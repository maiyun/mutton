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
            'local' => $this->_getLocale()
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
        if (version_compare($_POST['ver'], '5.2.0', '<')) {
            return [0, 'Version must be >= 5.2.0.'];
        }
        $res = Net::get('https://cdn.jsdelivr.net/gh/MaiyunNET/Mutton/doc/mblob/'.$_POST['ver'].'.mblob');
        if (!$res->content) {
            return [0, l('Network error, please try again.')];
        }
        if (!($blob = @gzinflate($res->content))) {
            return [0, l('The downloaded data is incomplete, please try again.')];
        }
        if (!($json = json_decode($blob, true))) {
            return [0, l('The downloaded data is incomplete, please try again.')];
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
                if ($_POST['mode'] === '1') {
                    // --- online，忽略 stc-ts 文件夹下所有文件 ---
                    $match[] = '/^stc-ts\\/.+/';
                } else {
                    // --- offline，只忽略 stc-ts 下面3个的文件比对 ---
                    $match[] = '/^stc-ts\\/(index\\.ts|tsconfig\\.js|tslint\\.json)/';
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
            if (($_POST['mode'] === '1') && Text::match($k, [
                    '/^stc-ts\\/.*/',
                    '/^\\.gitignore/'
                ])) {
                // --- 在线模式，不计算 stc-ts 目录 ---
            } else {
                $qlist[] = $k;
            }
        }
        foreach ($json['files'] as $k => $v) {
            if (($_POST['mode'] === '1') && Text::match($k, [
                    '/^stc-ts\\/.*/'
                ])) {
                // --- 在线模式，不计算 stc-ts 目录 ---
            } else {
                $qlist[] = $k;
            }
        }
        // --- 校验 const ---
        foreach ($nowList['const'] as $k => $arr) {
            // --- 文件存在才判断 const ---
            if (isset($json['const'][$k])) {
                foreach ($arr as $i => $v2) {
                    if (in_array($v2, $json['const'][$k])) {
                        unset($json['const'][$k][$i]);
                    } else {
                        // --- 本地多出本常量 ---
                        // --- [文件路径，常量名] ---
                        $dlistConst[] = [$k, $v2];
                    }
                }
            }
        }
        // --- 缺失的常量 ---
        foreach ($json['const'] as $k => $arr) {
            foreach ($arr as $i => $v2) {
                $qlistConst[] = [$k, $v2];
            }
        }
        return [1, 'list' => $list, 'qlist' => $qlist, 'dlist' => $dlist, 'qlistConst' => $qlistConst, 'dlistConst' => $dlistConst, 'library' => $nowList['library']];
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
            if (($name === '.') || ($name === '..')) {
                continue;
            }
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
            if ($name === 'const.php') {
                continue;
            }
            $file = ETC_PATH . $name;
            if (!is_file($file)) {
                continue;
            }
            $arr = [];
            $content = file_get_contents($file);

            preg_match_all('/(define|const)[(\\\'\s]+([A-Za-z0-9_]+)[\s\\\'][\s=,]+([\S\s]+?)\)?;/i', $content, $matches);
            if (count($matches[0]) > 0) {
                foreach ($matches[0] as $k => $v) {
                    $arr[] = $matches[2][$k];
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
        // --- 以下正则代表排除的文件夹，排除的文件夹不做比对 ---
        if (Text::match($path, [
            '/^\\.git\\//',
            '/^doc\\//',
            '/^\\.idea\\//',
            '/^ctr\\/.+/',
            '/^data\\/(?!locale\\/).+/',
            '/^log\\/.+/',
            '/^stc\\/(?!__Mutton__\\/).+/',
            '/^stc-ts\\/(?!__Mutton__\\/|types\\/).+/',
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
                    '/^data\\/(?!index\\.html|locale\\/index.html|locale\\/.+?__Mutton__.+?).+/',
                    '/^mod\\/(?!Mod\\.php).+/',
                    '/^stc\\/(?!__Mutton__\\/|index\\.html|index\\.js).+/',
                    '/^stc-ts\\/(?!__Mutton__\\/|types\\/any\\.d\\.ts|types\\/vue\\.d\\.ts|index\\.ts|tsconfig\\.json|tslint\\.json).+/',
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

