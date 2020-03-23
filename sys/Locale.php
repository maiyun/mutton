<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2019-3-26 23:31
 * Last: 2020-1-17 01:09:14, 2020-3-23 17:40:54
 */
declare(strict_types = 1);

/** @var string --- 当前语言名 --- */
$_local = 'en';

/** @var array 已经加载的语言包 */
$_localData = [];
/** @var string[] 已经加载的语言文件 */
$_localFiles = [];

/**
 * --- 获取语言包值 ---
 * @param string $key
 * @param array|null $data 要替换的数据
 * @return string
 */
function l(string $key, ?array $data = null): string {
    global $_localData, $_local;
    if (!isset($_localData[$_local])) {
        return 'LocaleError';
    }
    if (!isset($_localData[$_local][$key])) {
        return 'LocaleError';
    }
    if ($data) {
        $i = -1;
        return preg_replace_callback('/\\?/', function () use (&$i, $data) {
            ++$i;
            if (isset($data[$i])) {
                return $data[$i];
            } else {
                return '';
            }
        }, $_localData[$_local][$key]);
    } else {
        return $_localData[$_local][$key];
    }
}

