# Mutton

[![License](https://img.shields.io/github/license/MaiyunNET/Mutton.svg)](https://github.com/MaiyunNET/Mutton/blob/master/LICENSE)
[![GitHub issues](https://img.shields.io/github/issues/MaiyunNET/Mutton.svg)](https://github.com/MaiyunNET/Mutton/issues)
[![GitHub Releases](https://img.shields.io/github/release/MaiyunNET/Mutton.svg)](https://github.com/MaiyunNET/Mutton/releases "Stable Release")
[![GitHub Pre-Releases](https://img.shields.io/github/release/MaiyunNET/Mutton/all.svg)](https://github.com/MaiyunNET/Mutton/releases "Pre-Release")

Simple, easy to use, full functionality of the PHP framework.

## Languages

[简体中文](doc/README.zh-CN.md) | [繁體中文](doc/README.zh-TW.md)

## Installation

Download the latest release and put it to directory, then to start development.

## Environment

PHP 7.2+  
Nginx/Apache

> Note: Under Nginx, you need to manually configure the rewrite rule with the following rewrite rules:

```
if ($request_uri !~ ^/(stc/.*|favicon.\w+?\??.*|apple[\w-]+?\.png\??.*|[\w-]+?\.txt\??.*)$) {
    rewrite ^/([\s\S]*)$ /index.php?__uri=$1 last;
}
```

## Library

Captcha, Crypto, Db (MySQL, Sqlite), Kv (Memcached, Redis, RedisSimulator), Net, Session, Sql, Text.

## Features

### No brains

Based on the idea of not using the brain, the commonly used and uniform style of the library has been encapsulated.

### Library auto load

You can use the library directly without having to manually include them.

### UI Console

A console that contains a UI interface that automatically pairs the latest version of Mutton to detect which files have been modified or need to be upgraded.

### Net Library contains full Cookie implementation

Cookies can be obtained directly as an array of variables, which can exist anywhere, such as databases, memory, and so on.

### Perfect filter

Reasonable use of filters, you can quickly filter database entries.

### China Library Support

For WeChat payment, WeChat login, Alibaba Cloud OSS, Tencent Cloud COS, Alipay payment (forthcoming support) has been completed package.

#### And more...

## Demonstrate

### Generate 16-bit random numbers

```php
$str = $this->_random(16, Ctr::RANDOM_N);
```

### Generate a verification code picture

```php
Captcha::get(400, 100)->getStream();
```

### Get a list from the database

```php
$userList = User::where([
    ['state', '!=', '0'],
    'type' => ['1', '2', '3'],
    'is_lock' => '0'
])->all();
```

Note: All database operations are secure in this framework.

## Other demos

You can download and view the home Code (ctr/test.php) to see more examples.

## Changelog

[Changelog](doc/CHANGELOG.md)

## License

This library is published under [Apache-2.0](./LICENSE) license.

## Name meaning

Sheep are so cute.