# 更新日誌

## 5.0.2

[+] Session 庫增加 remove 方法，可手動移除還未到期的 session 值。  
[+] Session 的 set 方法增加 auto 參數，可自動為有有效期的 session 值續期。  
[\*] 優化 Text 和 Captcha 庫的驗證碼字串為更好肉眼區分的字元。  
[\*] 優化 loadData 方法，防止跨級獲取檔案。  
[\*] 優化 Aes 庫，CFB 模式自動檢測解密資料是否正常。

## 5.0.1

[+] Text 庫添加 RANDOM_LUNS 常量。  
[+] 添加 SESSION_SSL 常量，用以將 Cookie 只允許在 SSL 模式下讀取，更安全。  
[+] Sql 庫添加 onDuplicate 方法，可實現 insert 若存在則 update 的效果。  
[+] Session 庫增加 get 和 set 方法，可設定某一個值的有效期，用於後臺等超短時限時的用途。  
[\*] 優化 Mutton Portal 檢測常量代碼。  
[\*] 優化 Session 庫在 Db 模式下自動運行 gc 方法清理過期資料。  
[\*] 針對 5.0.0 升級需要手動修改 set.php 的 STC_PATH 為 HTTP_STC，否則 STATIC_PATH 常量讀取可能會有問題。

## 5.0.0

- 整裝待發，全新起航。