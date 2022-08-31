<p align="center"><img src="doc/[2022-03-29]logo.png" width="260" height="80" alt="Mutton"></p>

[![License](https://img.shields.io/github/license/maiyun/Mutton.svg)](https://github.com/maiyun/Mutton/blob/master/LICENSE)
[![GitHub issues](https://img.shields.io/github/issues/maiyun/Mutton.svg)](https://github.com/maiyun/Mutton/issues)
[![GitHub Releases](https://img.shields.io/github/release/maiyun/Mutton.svg)](https://github.com/maiyun/Mutton/releases "Stable Release")
[![GitHub Pre-Releases](https://img.shields.io/github/release/maiyun/Mutton/all.svg)](https://github.com/maiyun/Mutton/releases "Pre-Release")

Simple, easy to use, full functionality of the PHP framework.

## Languages

[简体中文](doc/README.sc.md) | [繁體中文](doc/README.tc.md)

## Requirement

PHP 8.0+  
Nginx/Apache

## Installation

Download the latest release and put it to directory, then enjoy.

> Note: Under Nginx, you need to manually configure the rewrite rule with the following rewrite rules:

```
if ($request_uri !~ ^/(stc/.*|favicon.\w+?\??.*|apple[\w-]+?\.png\??.*|[\w-]+?\.txt\??.*)$) {
    rewrite ^/([\s\S]*)$ /index.php?__path=$1 last;
}
```

## Library

Captcha, Crypto, Db (MySQL, Sqlite), Fs, Kv (Redis, RedisSimulator), Net, Scan, Session, Sql, Text.

## Features

### No brains

Simple and easy-to-use interface with rich code tips (phpDoc-based).

### Autoload

Using the various libraries directly, the system loads them automatically.

### Super-friendly Net library

You can use like:

```php
$res = Net::open('https://xxx/test')->post()->data(['a' => '1', 'b' => '2'])->request();
```

You can also use like:

```php
$res = Net::get('https://xxx/test');
```

You can set custom dns results:

```php
$res = Net::get('https://xxx/test', [
    'hosts' => [
        'xxx' => '111.111.111.111'
    ]
]);
```

You can also select another local network interface card:

```php
$res = Net::get('https://xxx/test', [
    'local' => '123.123.123.123'
]);
```

You can also possible to reuse links when accessing multiple urls, greatly speeding up access:

```php
$res1 = Net::get('https://xxx/test1', [
    'reuse' => true
]);
$res2 = Net::get('https://xxx/test2', [
    'reuse' => true
]);
Net::closeAll();
```

[![Net reuse test](doc/test-net-reuse.png)](doc/test-net-reuse.png)

With a complete cookie manager, cookies can be easily obtained and exist anywhere, when a request is sent, the system will also choose to send based on the domain name, path, etc. set by the cookie, and Set-Cookie will be discarded if it is set illegally across domains. Just like a real browser:

```php
$res1 = Net::get('https://xxx1.xxx/test1', [], $cookie);
$res2 = Net::get('https://xxx2.xxx/test2', [], $cookie);
```

> Tip: Net library support both incoming options and open chain operation, such as Net::open('xxx')->follow()->timeout(60)->reuse()->save(ROOT_PATH . 'doc/test.txt')->request();.

### Perfect Db library

With a number of useful interfaces, you can easily filter out the required data from the database:

```php
$ls = Order::where([
    'state' => '1'
])->by('id', 'DESC')->page(10, 1);
$list = $ls->all();
$count = $ls->count();
$total = $ls->total();
```

Get a user:

```php
$user = User::select(['id', 'user'])->filter([
    ['time_add', '>=', '1583405134']
])->first();
```

### XSRF

The checkXInput method enables XSRF detection to prevent malicious access.

### Scan the QRCode to log in

The Scan library makes it easy to implement the ability to scan QRCode to log in.

#### And more...

## Demonstrate

### Generate random numbers

```php
$str = Core::random(16, Core::RANDOM_N);
```

### Generate a verification code picture

```php
Captcha::get(400, 100)->getBuffer();
```

### Get a list

```php
$userList = User::where([
    ['state', '!=', '0'],
    'type' => ['1', '2', '3'],
    'is_lock' => '0'
])->all();
```

Note: All database operations are secure in this framework.

### The Sql library automatically adds table prefixes and wrapped characters '`'

```php
$sql->select(['SUM(user.age) age'], 'order')->leftJoin('user', ['order.user_id' => '#user.id'])
```

Output:

```sql
SELECT SUM(`test_user`.`age`) AS `age` FROM `test_order` LEFT JOIN `test_user` ON `test_order`.`user_id` = `test_user`.`id`
```

Cool!

### Localization

```php
$this->_loadLocale($_GET['lang'], 'test');
echo l('copy');
```

Depending on the lang value, the output: Copy、复制、複製、コピー, etc., is configured in the directory /data/locale/.

### Data validation

Based on strings, numbers, alignment sizes, and even regularities, the submitted data is directly validated, convenient!

```php
[
    'he' => ['require', [0, 'The he param does not exist.']],
    'num' => ['> 10', [0, 'The num param must > 10.']],
    'reg' => ['/^[A-CX-Z5-7]+$/', [0, 'The reg param is incorrect.']],
    'arr' => [['a', 'x', 'hehe'], [0, 'The arr param is incorrect.']]
]
```

See: /test/ctr-checkinput

## Other demos

You can visit /test/ to see more examples.

## Changelog

[Changelog](doc/CHANGELOG.md)

## License

This library is published under [Apache-2.0](./LICENSE) license.

## Join the translation team

If you speak multiple languages, join the translation team:

Telegram team: [https://t.me/maiyunlocale](https://t.me/maiyunlocale)  
QQ team: 24158113