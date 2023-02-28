<p align="center"><img src="./doc/icon.svg" width="68" height="68" alt="Mutton"></p>
<p align="center">
    <a href="https://github.com/maiyun/mutton/blob/master/LICENSE">
        <img alt="License" src="https://img.shields.io/github/license/maiyun/mutton?color=blue" />
    </a>
    <a href="https://github.com/maiyun/mutton/releases">
        <img alt="GitHub releases" src="https://img.shields.io/github/v/release/maiyun/mutton?color=brightgreen&logo=github" />
        <img alt="GitHub pre-releases" src="https://img.shields.io/github/v/release/maiyun/mutton?color=yellow&logo=github&include_prereleases" />
    </a>
    <a href="https://github.com/maiyun/mutton/issues">
        <img alt="GitHub issues" src="https://img.shields.io/github/issues/maiyun/mutton?color=blue&logo=github" />
    </a>
</p>

Simple, easy to use, full functionality of the PHP framework.

## Languages

[简体中文](doc/README.sc.md) | [繁體中文](doc/README.tc.md)

## Requirement

PHP 8.0 +

## Installation

Download the latest release package and unzip it.

> Note: In Nginx, you need to add the following rules to the rewrite rule file:

```
if ($request_uri !~ ^/(stc/.*|favicon.\w+?\??.*|apple[\w-]+?\.png\??.*|[\w-]+?\.txt\??.*)$) {
    rewrite ^/([\s\S]*)$ /index.php?__path=$1 last;
}
```

## Library

Captcha, Consistent, Crypto, Db (MySQL, SQLite), Fs, Kv (Redis, RedisSimulator), Net, Scan, Session, Jwt, Sql, Text.

## Key Features

### Ready-to-Use

Following the principle of ready-to-use, it encapsulates commonly used libraries in a uniform style.

### Automatic Loading

When using various libraries directly, the system will load them automatically.

### Super Useful Net Library

You can use it like this:

```php
$res = Net::open('https://xxx/test')->post()->data(['a' => '1', 'b' => '2'])->request();
```

You can also use it like this:

```php
$res = Net::get('https://xxx/test');
```

Custom dns results can be set:

```php
$res = Net::get('https://xxx/test', [
    'hosts' => [
        'xxx' => '111.111.111.111'
    ]
]);
```

You can also choose other local network cards to access:

```php
$res = Net::get('https://xxx/test', [
    'local' => '123.123.123.123'
]);
```

Link reuse can greatly improve access speed when accessing multiple URLs:

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

It also has a complete cookie manager that can easily retrieve and store cookies anywhere. When sending requests, the system will select the domain and path to send based on the cookie settings. If there is an illegal cross-domain setting in Set-Cookie, it will be discarded and not recorded, just like a real browser:

```php
$res1 = Net::get('https://xxx1.xxx/test1', [], $cookie);
$res2 = Net::get('https://xxx2.xxx/test2', [], $cookie);
```

> Note: The Net library supports both options and open chain operations. For example, Net::open('xxx')->follow()->timeout(60)->reuse()->save(ROOT_PATH . 'doc/test.txt')->request();.

### Easy-to-Use Db Library

With a large number of useful interfaces, you can easily filter the data you need from the database:

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

### XSRF Detection

Use the _checkXInput method to perform XSRF detection and prevent malicious access.

### Scan Login

With the help of the Scan library, it's easy to implement scan login.

#### There are more features waiting for you to explore

## Examples

### Creating a 16-bit random number

```php
$str = Core::random(16, Core::RANDOM_N);
```

### Creating a verification code

```php
Captcha::get(400, 100)->getBuffer();
```

### Getting a list

```php
$userList = User::where([
    ['state', '!=', '0'],
    'type' => ['1', '2', '3'],
    'is_lock' => '0'
])->all();
```

> Note: All database operations have been protected against injection attacks.

### Sql Library Automatically Adds Table Prefixes and Wrapping Characters "`"

```php
$sql->select(['SUM(user.age) age'], 'order')->leftJoin('user', ['order.user_id' => '#user.id'])
```

The output will be:

```sql
SELECT SUM(`test_user`.`age`) AS `age` FROM `test_order` LEFT JOIN `test_user` ON `test_order`.`user_id` = `test_user`.`id`
```

It's so easy to write!

### Localization

```php
$this->_loadLocale($_GET['lang'], 'test');
echo l('copy');
```

Based on the different values of lang, the output will be: Copy, 复制, 複製, コピー, etc., configured in the /data/locale/ directory.

### Data Validation

Directly validate submitted data based on strings, numbers, comparisons, and even regular expressions. It's convenient!

```php
[
    'he' => ['require', [0, 'The he param does not exist.']],
    'num' => ['> 10', [0, 'The num param must > 10.']],
    'reg' => ['/^[A-CX-Z5-7]+$/', [0, 'The reg param is incorrect.']],
    'arr' => [['a', 'x', 'hehe'], [0, 'The arr param is incorrect.']]
]
```

See: /test/ctr-checkinput

## Other Examples

You can visit /test/ to see more examples.

## Changelog

[Changelog](doc/CHANGELOG.md)

## License

This library is published under [Apache-2.0](./LICENSE) license.