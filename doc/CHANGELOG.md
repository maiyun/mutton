# Changelog

## Languages

[简体中文](./CHANGELOG.zh-CN.md) | [繁體中文](./CHANGELOG.zh-TW.md)

## 5.1.0

[+] Mutton Portal adds strict mode and full mode, and the strict pattern is used to determine whether there are third-party and deprecated files, all of which are used to determine whether all official libraries are fully installed.  
[\*] Remove the Ssh library, add the Comm library, include Ssh and Sftp trusted sublibrary (old libraries can continue to be used without deletion, but do not do update maintenance and can be migrated to the Comm library).

## 5.0.2

[+] The session library add a "remove" method that manually removes session values that have not yet expired.  
[+] The set method of Session increases the auto parameter and automatically is renewed for session values that have a valid validity period.  
[\*] Optimizes the Text and Captcha Library's verification code strings for better visually differentiated characters.  
[\*] Optimizes "loadData" methods to prevent cross-level access to files.  
[\*] Optimizes the Aes library, the CFB mode automatically detects whether the decrypted data is normal.

## 5.0.1

[+] Text Library add RANDOM_LUNS constants.  
[+] Add SESSION_SSL constants to allow cookies to be read only in SSL mode.  
[+] The Sql library adds the onDuplicate method to achieve the effect of the insert if there is an update.  
[+] The Session library adds a get and set method that sets the validity period of a value for an ultra-short validity period.  
[\*] Optimizes Mutton Portal detection constant code.  
[\*] Optimizes the Session library to automatically run the GC method in Db mode to clean up expired data.  
[\*] For 5.0.0 upgrade you need to manually modify the set.php STC_PATH to HTTP_STC, otherwise STATIC_PATH constant read may be problematic.

## 5.0.0

- A new beginning, I'm ready.