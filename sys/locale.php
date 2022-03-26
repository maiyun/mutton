<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2019-3-26 23:31
 * Last: 2020-1-17 01:09:14, 2020-3-23 17:40:54
 */
declare(strict_types = 1);

/** @var string --- 当前语言名 --- */
$_locale = 'en';

/** @var array 已经加载的语言包 */
$_localeData = [];
/** @var string[] 已经加载的语言文件 */
$_localeFiles = [];

/**
 * --- 获取语言包值 ---
 * @param string $key
 * @param array|null $data 要替换的数据
 * @return string
 */
function l(string $key, ?array $data = null): string {
    global $_localeData, $_locale;
    if (!isset($_localeData[$_locale])) {
        return 'LocaleError';
    }
    if (!isset($_localeData[$_locale][$key])) {
        return 'LocaleError';
    }
    if ($data) {
        $i = -1;
        return preg_replace_callback('/\\?/', function () use (&$i, $data) {
            ++$i;
            if (isset($data[$i])) {
                return $data[$i];
            }
            else {
                return '';
            }
        }, $_localeData[$_locale][$key]);
    }
    else {
        return $_localeData[$_locale][$key];
    }
}

