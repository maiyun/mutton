<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * CONF - {"ver":"0.1","folder":false} - END
 * Dat: 2019-12-14 16:10:54
 * Las: 2019-12-14 16:10:58, 2020-2-27 18:46:35
 */
declare(strict_types = 1);

namespace lib;

class Fs {

    /**
     * --- 深度创建文件夹并赋予权限，失败不会回滚 ---
     * @param string $path
     * @param int $mode
     * @return bool
     */
    public static function mkdir(string $path, int $mode = 0755): bool {
        $path = str_replace('\\', '/', $path);
        $dirs = explode('/', $path);
        $tpath = '';
        foreach ($dirs as $v) {
            if ($v === '') {
                continue;
            }
            $tpath .= $v . '/';
            if (!is_dir($tpath)) {
                if (!@mkdir($tpath)) {
                    return false;
                }
                @chmod($tpath, $mode);
            }
        }
        return true;
    }

    /**
     * --- 深度删除文件夹以及所有文件 ---
     * @param string $path
     * @return bool
     */
    public static function rmdir(string $path): bool {
        $path = str_replace('\\', '/', $path);
        if (substr($path, -1) !== '/') {
            $path .= '/';
        }
        if (!file_exists($path)) {
            return true;
        }
        $dir = dir($path);
        while (($name = $dir->read()) !== false) {
            if (($name === '.') || ($name === '..')) {
                continue;
            }
            if (is_file($path.$name)) {
                if (!@unlink($path.$name)) {
                    return false;
                }
            } else {
                if (!self::rmdir($path.$name.'/')) {
                    return false;
                }
            }
        }
        $dir->close();
        return @rmdir($path);
    }

    /**
     * --- 检验文件或文件夹是否可写 ---
     * @param string $path
     * @return bool
     */
    public static function isWritable(string $path): bool {
        // If we're on a Unix server with safe_mode off we call is_writable
        if (DIRECTORY_SEPARATOR == '/' AND @ini_get("safe_mode") == false) {
            return is_writable($path);
        }
        // For windows servers and safe_mode "on" installations we'll actually
        // write a file then read it. Bah...
        if (is_dir($path)) {
            $file = rtrim($path, '/') . '/' . md5(mt_rand(1, 100).mt_rand(1, 100));
            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }
            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return true;
        } else if (!is_file($path) or ($fp = @fopen($path, 'ab')) === false) {
            return false;
        }
        fclose($fp);
        return true;
    }

}

