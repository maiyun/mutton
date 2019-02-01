# 更新日志

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