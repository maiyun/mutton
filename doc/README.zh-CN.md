# Mutton

[![License](https://img.shields.io/github/license/MaiyunNET/Mutton.svg)](https://github.com/MaiyunNET/Mutton/blob/master/LICENSE)
[![GitHub issues](https://img.shields.io/github/issues/MaiyunNET/Mutton.svg)](https://github.com/MaiyunNET/Mutton/issues)
[![GitHub Releases](https://img.shields.io/github/release/MaiyunNET/Mutton.svg)](https://github.com/MaiyunNET/Mutton/releases "Stable Release")
[![GitHub Pre-Releases](https://img.shields.io/github/release/MaiyunNET/Mutton/all.svg)](https://github.com/MaiyunNET/Mutton/releases "Pre-Release")

简单，易用且功能完整的 PHP 框架。

## 安装

下载最新的 release 版，放在网站目录下，即可开始开发。

## 环境

PHP 7.2+  
Nginx/Apache

> 注意：在 Nginx 下，需要您手动配置重写规则，重写规则如下：

```
if ($request_uri !~ ^/(stc/[\w-/.]+?\??.*|favicon.\w+?\??.*|[\w-]+?\.doc\?*?.*|[\w-]+?\.txt\??.*)$) {
    rewrite ^/([\w-/.?]*)$ /index.php?__uri=$1 last;
}
```

## 库

Aes, Captcha, Db (MySQL), Mailer, Memcached, Net, Redis, Session, Sms, Sql, Ssh, Storage (OSS/COS), Text, Wechat.

## 部分特性

### 开袋即食

秉承开袋即食的原则，封装统一风格的常用类库。

### 库自动加载

您可直接使用库，而无需手动去包含他们。

### UI 控制台

包含了一个 UI 界面的控制台，可对 Mutton 的最新版本进行自动比对，检测哪些文件被修改或需要升级。

### Net 类库包含完整 Cookie 实现

可将 Cookie 直接获取为一个变量数组，可存在数据库、内存等任何地方。

### 完善的筛选器

合理的运用筛选器，可以快速的筛选数据库条目。

### 中国化类库支持

对微信支付、微信登录、阿里云 OSS、腾讯云 COS、支付宝支付（即将支持）已经完成封装集成。

## 代码演示

### 生成 16 位随机数

```php
$str = Text::random(16, Text::RANDOM_N);
```

### 生成验证码图片

```php
Captcha::get(400, 100)->output();
```

### 根据条件从数据库获取列表

```php
$userList = User::getList([
    'where' => [
        ['state', '!=', '0'],
        'type' => ['1', '2', '3'],
        'is_lock' => '0'
    ]
]);
```

注：框架的所有数据库操作已经做了防注入安全处理。

### 其他演示

可以下载后访问首页和查看首页代码（ctr/main.php）看更多示例。

## 更新日志

[更新日志](CHANGELOG.zh-CN.md)

## 许可

本框架基于 [Apache-2.0](../LICENSE) 许可。

## 名字含义

作者爱吃羊。