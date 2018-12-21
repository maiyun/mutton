# Mutton

Simple, easy-to-use and fully functional PHP framework.

## Installation

Download the latest release version and put it in the website directory to start development.

## Environment

PHP 7.2+  
Nginx/Apache

## Library

Aes, Captcha, Db (MySQL), Mailer, Memcached, Net, Redis, Session, Sms, Sql, Ssh, Storage (OSS/COS), Text, Wechat.

## Some features

### No brains

Based on the idea of not using the brain, the commonly used and uniform style of the library has been encapsulated.

### Library Auto Load

You can use the library directly without having to manually include them.

### UI Console

A console that contains a UI interface that automatically pairs the latest version of Mutton to detect which files have been modified or need to be upgraded.

### Net Library contains full Cookie implementation

Cookies can be obtained directly as an array of variables, which can exist anywhere, such as databases, memory, and so on.

### Perfect filter

Reasonable use of filters, you can quickly filter database entries.

### China Library Support

For WeChat payment, WeChat login, Alibaba Cloud OSS, Tencent Cloud COS, Alipay payment (forthcoming support) has been completed package.

## Demonstrate

### Generate 16-bit random numbers

```php
$str = Text::random(16, Text::RANDOM_N);
```

### Generate a verification code picture

```php
Captcha::get(400, 100)->output();
```

### Get a list from the database based on criteria

```php
$userList = User::getList([
    'where' => [
        ['state', '!=', '0'],
        'type' => ['1', '2', '3'],
        'is_lock' => '0'
    ]
]);
```

Note: All database operations are secure in this framework.

## Other demos

You can download and view the home Code (ctr/main.php) to see more examples.

## License

This library is published under [Apache-2.0](./LICENSE) license.

## Name meaning

The author loves to eat sheep.

## Other languages

[简体中文](doc/README.zh-CN.md)  
[繁體中文](doc/README.zh-TW.md)