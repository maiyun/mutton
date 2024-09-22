<p align="center"><img src="./icon.svg" width="68" height="68" alt="Mutton"></p>
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

简单、易用且功能完整的 PHP 框架。

## 语言

[English](../README.md) | [繁體中文](README.tc.md)

## 环境

PHP 8.0 +

## 安装

下载最新的发行包，解压后即可。

> 提示：在 Nginx 中，你需要将以下规则添加到重新规则文件内：

```
if ($request_uri !~ ^/(stc/.*|favicon.\w+?\??.*|apple[\w-]+?\.png\??.*|[\w-]+?\.txt\??.*)$) {
    rewrite ^/([\s\S]*)$ /index.php?__path=$1 last;
}
```

## 库

Captcha, Consistent, Crypto, Db (MySQL), Fs, Kv (Redis), Net, Scan, Session, Jwt, Sql, Text.

## 部分特性

### 开袋即食

秉承开袋即食的原则，封装统一风格的常用类库。

### 自动加载

直接使用各种库，系统会自动加载它。

### 超好用 Net 库

可以这样用：

```php
$res = Net::open('https://xxx/test')->post()->data(['a' => '1', 'b' => '2'])->request();
```

也可以这样用：

```php
$res = Net::get('https://xxx/test');
```

可以设置自定义的解析结果：

```php
$res = Net::get('https://xxx/test', [
    'hosts' => [
        'xxx' => '111.111.111.111'
    ]
]);
```

也可以选择本地的其他网卡来访问：

```php
$res = Net::get('https://xxx/test', [
    'local' => '123.123.123.123'
]);
```

更可以在访问多条 url 时进行链接复用，大大加快访问速度：

```php
$res1 = Net::get('https://xxx/test1', [
    'reuse' => true
]);
$res2 = Net::get('https://xxx/test2', [
    'reuse' => true
]);
Net::closeAll();
```

[![Net reuse test](test-net-reuse.png)](test-net-reuse.png)

更拥有完整的 Cookie 管理器，可以轻松将 Cookie 获取并存在任何地方，发送请求时，系统也会根据 Cookie 设置的域名、路径等来选择发送，并且 Set-Cookie 如果有非法跨域设置，也会被舍弃不会被记录，就像真正的浏览器一样：

```php
$res1 = Net::get('https://xxx1.xxx/test1', [], $cookie);
$res2 = Net::get('https://xxx2.xxx/test2', [], $cookie);
```

> 提示：Net 库同时支持传入 options 和 open 链式操作，如 Net::open('xxx')->follow()->timeout(60)->reuse()->save(ROOT_PATH . 'doc/test.txt')->request();

### 好用的 Db 库

拥有大量好用的接口，可以轻松的从数据库筛选出需要的数据：

```php
$ls = Order::where([
    'state' => '1'
])->by('id', 'DESC')->page(10, 1);
$list = $ls->all();
$count = $ls->count();
$total = $ls->total();
```

获取一个用户：

```php
$user = User::select(['id', 'user'])->filter([
    ['time_add', '>=', '1583405134']
])->first();
```

### XSRF 检测

使用 _checkXInput 方法，可以进行 XSRF 检测，防止恶意访问。

### 扫码登录

借助 Scan 库可以轻松实现扫码登录的功能。

### 反向代理

使用 Net 库的 rproxy 方法，配合路由参数，可轻松实现反向代理功能。

#### 还有更多特性等你探索

## 部分示例

### 创建 16 位随机数

```php
$str = Core::random(16, Core::RANDOM_N);
```

### 创建一个验证码

```php
Captcha::get(400, 100)->getBuffer();
```

### 获取一个列表

```php
$userList = User::where([
    ['state', '!=', '0'],
    'type' => ['1', '2', '3'],
    'is_lock' => '0'
])->all();
```

> 提示：所有数据库操作都已经做了安全防注入处理。

### Sql 库自动增加表前缀和包裹字符“`”

```php
$sql->select(['SUM(user.age) age'], 'order')->leftJoin('user', ['order.user_id' => Sql::column('user.id')]);
```

将输出：

```sql
SELECT SUM(`test_user`.`age`) AS `age` FROM `test_order` LEFT JOIN `test_user` ON `test_order`.`user_id` = `test_user`.`id`
```

写起来好轻松！

### 本地化

```php
$this->_loadLocale($_GET['lang'], 'test');
echo l('copy');
```

根据 lang 值不同，将输出：Copy、复制、複製、コピー等，在目录 /data/locale/ 中配置。

### 数据校验

根据字符串、数字、比对大小甚至是正则，对提交的数据进行直接校验，方便！

```php
[
    'he' => ['require', [0, 'The he param does not exist.']],
    'num' => ['> 10', [0, 'The num param must > 10.']],
    'reg' => ['/^[A-CX-Z5-7]+$/', [0, 'The reg param is incorrect.']],
    'arr' => [['a', 'x', 'hehe'], [0, 'The arr param is incorrect.']]
]
```

参见：/test/ctr-checkinput

## 其他示例

你可以访问 /test/ 来查看更多示例。

## 更新日志

[更新日志](CHANGELOG.sc.md)

## 许可

基于 [Apache-2.0](../LICENSE) 许可。