# 更新日志

## 语言

[English](./CHANGELOG.md) | [繁體中文](./CHANGELOG.tc.md)

# 7.0.1

[\*] Mutton 7 正式版发布，启用新的 LOGO，[新LOGO]([2022-03-29]logo.png) / [旧LOGO](logo.png)。  
[\*] 优化 Session 库。

# 7.0.0

[+] 新增 Scan 库，用于扫码认证。  
[+] Mod 类新增 sql 语句获取的相关的方法。  
[+] Mod 类新增 updateByWhereSql 方法。  
[+] Ctr 类新增 _displayErrors 方法。  
[+] Ctr 类新增 rand 生成纯数字随机数的方法。  
[+] Ctr 类新增 muid 生成方法。  
[+] Ctr 类的 random 方法新增 block 参数。  
[+] Net 库新增 string 数据提交。  
[\*] 修复 Sql 库的一些问题。  
[\*] 日志记录变更为以每小时为一个文件。  
[\*] 日志记录新增用户 POST 数据（不含文件上传）。  
[\*] 日志记录新增 Net 库访问错误。  
[\*] 兼容 PHP 8.0。

# 6.3.0

[+] 模板中新增 _staticPath、_staticVer 变量。  
[+] Kv 库 incr、decr 方法新增浮点数支持。  
[+] Ctr 类新增 _setCookie 方法。  
[+] Mod 类的 primarys 方法新增 raw 参数。  
[\*] 修复 Text 库的 urlResolve 方法。  
[\*] 修复当没有 action 时会报错的 BUG。

# 6.2.0

[+] 新增 isIPv6/isDomain/parseDomain 方法在 Text 库。  
[\*] 优化 CookieManager 在 Net 库。  
[\*] 修改 getStream 方法名为 getBuffer 在 Captcha 库。  
[\*] 优化 urlResolve 方法支持 Windows 路径处理在 Text 库。  
[-] 移除 getHost 方法在 Text 库，请用 parseDomain 方法替代。

# 6.1.0

[+] 新增检测可能存在的废弃文件在 Mutton Portal。  
[+] 新增 middle 结构，可预先处理所有请求。

## 6.0.2

[\*] 修复 reuse 的一些问题在 Net 库。

## 6.0.1

[\*] 修改中国大陆的更新源。

## 6.0.0

[+] 大量更新，内核优化，更加灵活易用。

## 5.3.1

[+] 添加 lock 方法在 Sql 库。  
[+] 添加软删模式在 Mod 类。  
[\*] 优化 Sql/Mod 类使其 API 与 Nuttom 更一致。

## 5.3.0

[+] 增加 isWritable() 方法在 Ctr 类。  
[+] 增加 resetCookieSession 方法在 Net 库。  
[+] 增加 urlResolve 方法在 Text 库。 
[\*] 优化 Route 代码。  
[\*] Sql 库重写，与 Nuttom 完全一致的 API，更简约的代码。  
[\*] 其他大量代码优化。

## 5.2.4

[+] Net 库增加 followLocation 配置项。  
[+] Text 库新增 match() 方法。  
[+] Ctr 类增加 mkdir()、rmdir() 方法。  
[+] 增加 i18n 支持，Ctr 类增加 setLocale()、getLocale() 方法，全局增加 l() 方法。  
[\*] 更新 Mutton Portal 逻辑，老版需要先自行更新 Mutton Portal 关联文件后使用。  
[\*] 更新 phpseclib 库到 2.0.15。

## 5.1.2

[+] Text 库增加 getHost 方法。  
[+] Comm 库，Ssh2 类增加 enablePTY / disablePTY / isPTYEnabled / setTimeout / write / writeLine / read / readValue / sendCtrlC / reset / readAll / isDone 方法。  
[\*] TencentCloud 自动加载核心 SDK。  
[\*] 优化 Text 库。  
[\*] Comm/Ssh2 和 Comm/Sftp 库自动执行 disconnect 方法。

## 5.1.1

[+] 增加 Dns 库，已封装阿里云、腾讯云，并增加阿里云、腾讯云的核心库。  
[\*] 优化 Mutton Portal，增加目录替换提示。  
[\*] 优化一些代码和配置项。

## 5.1.0

[+] Mutton Portal 增加严格校验模式和完整校验模式，严格校验模式用以判断框架是否存在第三方或已废弃的库文件，完整校验模式用以判断是否完整的安装了所有官方库。  
[\*] 移除 Ssh 库，增加 Comm 库，包含 Ssh 和 Sftp 子库（老库不删除仍可继续使用，但是不做更新维护，可适时迁移至新 Comm 库）。

## 5.0.2

[+] Session 库增加 remove 方法，可手动移除还未到期的 session 值。  
[+] Session 的 set 方法增加 auto 参数，可自动为有有效期的 session 值续期。  
[\*] 优化 Text 和 Captcha 库的验证码字符串为更好肉眼区分的字符。  
[\*] 优化 loadData 方法，防止跨级获取文件。  
[\*] 优化 Aes 库，CFB 模式自动检测解密数据是否正常。

## 5.0.1

[+] Text 库添加 RANDOM_LUNS 常量。  
[+] 添加 SESSION_SSL 常量，用以将 Cookie 只允许在 SSL 模式下读取，更安全。  
[+] Sql 库添加 onDuplicate 方法，可实现 insert 若存在则 update 的效果。  
[+] Session 库增加 get 和 set 方法，可设定某一个值的有效期，用于后台等超短时限时的用途。  
[\*] 优化 Mutton Portal 检测常量代码。  
[\*] 优化 Session 库在 Db 模式下自动运行 gc 方法清理过期数据。  
[\*] 针对 5.0.0 升级需要手动修改 set.php 的 STC_PATH 为 HTTP_STC，否则 STATIC_PATH 常量读取可能会有问题。

## 5.0.0

- 整装待发，全新起航。