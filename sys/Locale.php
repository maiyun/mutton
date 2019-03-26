<?php
declare(strict_types = 1);

$__LOCALE = 'en';
$__LOCALE_OBJ = [
    'en' => []
];

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

