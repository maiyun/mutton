# Changelog

## Languages

[简体中文](./CHANGELOG.sc.md) | [繁體中文](./CHANGELOG.tc.md)

# 7.2.1

[+] Added getRange method in the Consistent class.  
[\*] Optimize code.

# 7.1.0

[+] Add Consistent library.  
[+] Add index param of Mod.  
[+] Add explain method of Mod.

# 7.0.2 

[\*] Optimize the by and field methods of the Sql library.  
[\*] Optimizes the _checkInput method of the Ctr class.

# 7.0.1

[\*] The official version of Mutton 7 is released, enabling the new LOGO, [new LOGO]([2022-03-29]logo.png) / [old LOGO](logo.png).  
[\*] Optimized the Session library.

# 7.0.0

[+] Added the Scan library for scan QR Code authentication.  
[+] The Mod class adds an sql statement to get the relevant methods.  
[+] The Mod class adds an updateByWhereSql method.  
[+] New _displayErrors methods have been added to the Ctr class.  
[+] The Ctr class adds a new way for rand to generate purely numeric random numbers.  
[+] A new muid generation method is added to the Ctr class.  
[+] The random method of the Ctr class adds a new block parameter.  
[+] New string data submission for the Net library.  
[\*] Fixed some issues with the Sql library.  
[\*] Log is changed to one file per hour.  
[\*] Log adds user POST data (excluding file uploads).  
[\*] Log adds a Net library access error.  
[\*] PHP 8.0 compatible.

# 6.3.0

[+] New _staticPath, _staticVer variables are added to the template.  
[+] The Kv library incr, decr method adds floating point support.  
[+] The Ctr class add _setCookie method.  
[+] The primarys method of the Mod class adds raw parameters.  
[\*] Fix the urlResolve method for the Text library.  
[\*] Fix bug that report errors when there is no action.

# 6.2.0

[+] Add isIPv6/isDomain/parseDomain method in Text library.  
[\*] Optimize CookieManager in Net library.  
[\*] Modify getStream method named getBuffer in Captcha library.  
[\*] The optimization urlResolve method supports Windows path processing in the Text library.  
[-] Remove getHost method from the Text library, instead using the parseDomain method.

# 6.1.0

[+] New detection of possible waste files is found in Mutton Portal.  
[+] New middle structure to pre-process all requests.

## 6.0.2

[\*] Fix reuse's issues of the Net library.

## 6.0.1

[\*] Modify the update source for Mainland China.

## 6.0.0

[+] A large number of updates, kernel optimization, more flexible and easy to use.

## 5.3.1

[+] Add the lock method in the Sql library.  
[+] Add a soft-delete pattern in the Mod class.  
[\*] Optimize the Sql/Mod class to make its API more consistent with Nuttom.

## 5.3.0  

[+] Add the isWritable() method in the Ctr class.  
[+] Add the reset cookie Session method in the Net library.  
[+] Add the urlResolve method in the Text library.  
[\*] Optimize the Route code.  
[\*] Sql library rewrites, perfectly consistent with Nuttom's API, more minimalist code.  
[\*] A large number of other code optimizations.

## 5.2.4

[+] Net Library adds followLocation configuration items.  
[+] Text library adds a match() method.  
[+] Ctr class adds the mkdir(), rmDir() method.  
[+] Increase i18n support, Ctr class increase setLocale(), getLocale() method, global increase l() method.  
[\*] Update Mutton Portal, the old version needs to first update the Mutton Portal Association file after use.  
[\*] Update phpseclib Library to 2.0.15.

## 5.1.2

[+] Text library add getHost method.  
[+] Comm library, Ssh2 class increase enablePTY / disablePTY / isPTYEnabled / setTimeout / write / writeLine / read / readValue / sendCtrlC / reset / readAll / isDone method.  
[\*] TencentCloud automatically loads the core SDK.  
[\*] Optimizes the Text library.  
[\*] The Comm/Ssh2 and Comm/Sftp libraries automatically execute the disconnect method.

## 5.1.1

[+] Add Dns library, has encapsulated Alibaba Cloud, Tencent Cloud, and add Alibaba Cloud, Tencent Cloud Core library.  
[\*] Optimizes Mutton Portal to add directory replacement prompts.  
[\*] Optimizes some code and configuration items.

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