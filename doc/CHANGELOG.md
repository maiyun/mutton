# Changelog

## Languages

[简体中文](./CHANGELOG.zh-CN.md) | [繁體中文](./CHANGELOG.zh-TW.md)

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