# 更新日誌

## 語言

[English](./CHANGELOG.md) | [简体中文](./CHANGELOG.sc.md)

# 7.4.0

[+] Db 庫新增 GIS、JSON 類型的存取。  
[+] Mod 新增 oneArray、toArrayByRecord、firstArray、allArray 方法。  
[+] Ctr 新增 _cross 方法，可放開跨域限制。  
[+] 新增反代特性，使用 Net 庫的 rproxy 方法輕鬆實現反代。  
[+] 生命週期新增 onUnload 事件，在頁面結束時會執行。  
[+] Sql 庫部分方法新增 suf 表後綴參數。  
[+] Mod 類新增 removeByWhereSql 方法。  
[+] Mod 的各種 join 方法新增 index 參數。  
[*] 優化重寫規則，html 類型也將直接顯示。  
[*] 其他大量優化。

# 7.3.2

[\*] 優化 Mod、Sql 類。

# 7.3.1

[+] 新增 QS、URL_STC_FULL、STATIC_PATH_FULL 常量。  
[+] Ctr 類的 _checkInput 方法新增 boolean 類型判斷。  
[\*] 其他優化。

# 7.3.0

[+] 新增 Jwt 庫。  
[+] Net 庫新增 setCookie 方法。  
[\*] 其他的一些優化。

# 7.2.3

[+] Ctr 類的 _checkInput 方法新增可檢查 array 類型。  
[+] Ctr 類的 _loadView 方法新增 _urlFull 變數。

# 7.2.2

[+] Core 庫新增 convert62， unconvert62， purify 方法。  
[+] Text 庫新增 urlAtom 方法，優化了 urlResolve 方法。  
[+] Consistent 庫新增 migration 方法。  
[+] 新增 HOSTPORT 常量。  
[+] Ctr 類的 _loadView 方法新增 _urlBase 變數，新增 _enabledXsrf 方法，新增可自定義的 _httpCode 參數。  
[+] 路由新增自定義 404 跳轉參數 “#404”。  
[\*] 其他的一些優化。

# 7.2.1

[+] 新增 getRange 方法在 Consistent 類。  
[\*] 優化代碼。

# 7.1.0

[+] 新增 Consistent 庫。  
[+] Mod 新增 index 參數。  
[+] Mod 新增 explain 方法。

# 7.0.2 

[\*] 優化 Sql 庫的 by 和 field 方法。  
[\*] 優化 Ctr 類的 _checkInput 方法。

# 7.0.1

[\*] Mutton 7 正式版發佈，啟用新的 LOGO，[新LOGO]([2022-03-29]logo.png) / [舊LOGO](logo.png)。  
[\*] 優化 Session 庫。

# 7.0.0

[+] 新增 Scan 庫，用於掃碼認證。  
[+] Mod 類新增 sql 語句獲取的相關的方法。  
[+] Mod 類新增 updateByWhereSql 方法。  
[+] Ctr 類新增 _displayErrors 方法。  
[+] Ctr 類新增 rand 產生純數位隨機數的方法。  
[+] Ctr 類新增 muid 生成方法。  
[+] Ctr 類的 random 方法新增 block 參數。  
[+] Net 庫新增 string 數據提交。  
[\*] 修復 Sql 庫的一些問題。  
[\*] 日誌記錄變更為以每小時為一個檔。  
[\*] 日誌記錄新增使用者 POST 數據（不含文件上傳）。  
[\*] 日誌記錄新增 Net 庫存取錯誤。  
[\*] 兼容 PHP 8.0。

# 6.3.0

[+] 範本中新增 _staticPath、_staticVer 變數。  
[+] Kv 庫 incr、decr 方法新增浮點數支援。  
[+] Ctr 類新增 _setCookie 方法。  
[+] Mod 類的 primarys 方法新增 raw 參數。  
[\*] 修復 Text 庫的 urlResolve 方法。  
[\*] 修復當沒有 action 時會報錯的 BUG。

# 6.2.0

[+] 新增 isIPv6/isDomain/parseDomain 方法在 Text 庫。  
[\*] 優化 CookieManager 在 Net 庫。  
[\*] 修改 getStream 方法名為 getBuffer 在 Captcha 庫。  
[\*] 優化 urlResolve 方法支援 Windows 路徑處理在 Text 庫。  
[-] 移除 getHost 方法在 Text 庫，請用 parseDomain 方法替代。

# 6.1.0

[+] 新增檢測可能存在的廢棄檔在 Mutton Portal。    
[+] 新增 middle 結構，可預先處理所有請求。

## 6.0.2

[\*] 修復 reuse 的一些問題在 Net 庫。

## 6.0.1

[\*] 修改中國大陸的更新源。

## 6.0.0

[+] 大量更新，內核優化，更加靈活易用。

## 5.3.1

[+] 添加 lock 方法在 Sql 庫。  
[+] 添加軟刪模式在 Mod 類。  
[\*] 優化 Sql/Mod 類使其 API 與 Nuttom 更一致。

## 5.3.0

[+] 增加 isWritable() 方法再 Ctr 類。  
[+] 增加 resetCookieSession 方法再 Net 庫。  
[+] 增加 urlResolve 方法在 Text 庫。 
[\*] 優化 Route 代碼。  
[\*] Sql 庫重寫，與 Nuttom 完全一致的 API，更簡約的代碼。  
[\*] 其他大量代碼優化。

## 5.2.4

[+] Net 庫增加 followLocation 配置項。  
[+] Text 庫新增 match() 方法。  
[+] Ctr 類增加 mkdir()、rmdir() 方法。  
[+] 增加 i18n 支援，Ctr 類增加 setLocale()、getLocale() 方法，全域增加 l() 方法。  
[\*] 更新 Mutton Portal 邏輯，老版需要先自行更新 Mutton Portal 關聯檔後使用。  
[\*] 更新 phpseclib 庫到 2.0.15。

## 5.1.2

[+] Text 庫增加 getHost 方法。  
[+] Comm 庫，Ssh2 類增加 enablePTY / disablePTY / isPTYEnabled / setTimeout / write / writeLine / read / readValue / sendCtrlC / reset / readAll / isDone 方法。  
[\*] TencentCloud 自動載入核心 SDK。  
[\*] 優化 Text 庫。  
[\*] Comm/Ssh2 和 Comm/Sftp 庫自動執行 disconnect 方法。

## 5.1.1

[+] 增加 Dns 庫，已封裝阿裡雲、騰訊雲，並增加阿裡雲、騰訊雲的核心庫。  
[\*] 優化 Mutton Portal，增加資料夾替換提示。  
[\*] 優化一些代碼和配置項。

## 5.1.0

[+] Mutton Portal 增加嚴格校驗模式和完整校驗模式，嚴格校驗模式用以判斷框架是否存在協力廠商或已廢棄的庫檔，完整校驗模式用以判斷是否完整的安裝了所有官方庫。  
[\*] 移除 Ssh 庫，增加 Comm 庫，包含 Ssh 和 Sftp 子庫（老庫不刪除仍可繼續使用，但是不做更新維護，可適時遷移至新 Comm 庫）。

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