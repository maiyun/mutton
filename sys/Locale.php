<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2019-3-26 23:31
 * Last: 2020-1-17 01:09:14
 */
declare(strict_types = 1);

/** @var string $__LOCALE 当前语言名 */
$__LOCALE = 'en';
/** @var array $__LOCALE_OBJ 已经加载的语言包 */
$__LOCALE_OBJ = [
    'en' => []
];
/** @var string[] $__LOCALE_OVER 已经加载的语言文件 */
$__LOCALE_OVER = [];

/**
 * --- 获取语言包值 ---
 * @param string $key
 * @return string
 */
function l(string $key): string {
    global $__LOCALE, $__LOCALE_OBJ;
    if (!isset($__LOCALE_OBJ[$__LOCALE])) {
        return 'LocaleError';
    }
    if (!isset($__LOCALE_OBJ[$__LOCALE][$key])) {
        return 'LocaleError';
    }
    return $__LOCALE_OBJ[$__LOCALE][$key];
}

