# Mutton

[![License](https://img.shields.io/github/license/MaiyunNET/Mutton.svg)](https://github.com/MaiyunNET/Mutton/blob/master/LICENSE)
[![GitHub issues](https://img.shields.io/github/issues/MaiyunNET/Mutton.svg)](https://github.com/MaiyunNET/Mutton/issues)
[![GitHub Releases](https://img.shields.io/github/release/MaiyunNET/Mutton.svg)](https://github.com/MaiyunNET/Mutton/releases "Stable Release")
[![GitHub Pre-Releases](https://img.shields.io/github/release/MaiyunNET/Mutton/all.svg)](https://github.com/MaiyunNET/Mutton/releases "Pre-Release")

簡單，易用，功能完整開袋即食的 PHP 框架。

## 安裝

下載最新的 release 版，隨即開始做你想做的。

## 環境

PHP 7.2+  
Nginx/Apache

> 注意：在 Nginx 下，需要您手動設定重寫規則，重寫規則如下：

```
if ($request_uri !~ ^/(stc/[\w-/.]+?\??.*|favicon.\w+?\??.*|[\w-]+?\.doc\?*?.*|[\w-]+?\.txt\??.*)$) {
    rewrite ^/([\w-/.?]*)$ /index.php?__uri=$1 last;
}
```

## 庫

Aes, Captcha, Db (MySQL), Mailer, Memcached, Net, Redis, Session, Sms, Sql, Storage (OSS / COS), Text, Wechat (Login / Payment), Alipay, Comm (Ssh / Sftp), Dns (Alibaba Cloud / Tencent Cloud).

## 部分特性

### 兩眼發黑

基於兩眼發黑的原則，無需動腦子，官方封裝了擁有統一代碼風格常用庫，可直接食用。

### 自動加載

直接瀟灑的使用庫，不需要手動 require。

### UI 主控台

包含了一個 UI 介面的主控台，可對 Mutton 的最新版本進行自動比對，檢測哪些檔案被修改或需要更新。

### Net 類庫包含完整 Cookie 實現

可將 Cookie 直接獲取為一個變數陣列，可存在資料庫、記憶體等任何地方。

### 完善的筛选器

合理的運用篩選器，可以快速的篩選資料庫條目。

### 大陸類庫支援

對微信支付、微信登錄、阿裡雲 OSS、騰訊雲 COS、支付寶支付（即將支援）已經完成封裝集成。

## 代碼演示

### 生成 16 位亂數

```php
$str = Text::random(16, Text::RANDOM_N);
```

### 生成驗證碼圖片

```php
Captcha::get(400, 100)->output();
```

### 根據條件從資料庫獲取清單

```php
$userList = User::getList([
    'where' => [
        ['state', '!=', '0'],
        'type' => ['1', '2', '3'],
        'is_lock' => '0'
    ]
]);
```

注：框架的所有資料庫操作已經做了防注入安全處理。

### 其他演示

可以下載後訪問首頁和查看首頁代碼（ctr/test.php）看更多示例。

## 更新日志

[更新日誌](CHANGELOG.zh-TW.md)

## 許可

本框架基於 [Apache-2.0](../LICENSE) 許可。

## 名字含義

羊肉真香 XD。