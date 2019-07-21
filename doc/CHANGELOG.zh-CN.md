# 更新日志

## 5.3.0

[+] 增加 isWritable() 方法再  Ctr 类。  
[+] 增加 resetCookieSession 方法再 Net 库。  
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