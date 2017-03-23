# Chameleon
变色龙 PHP 框架，一个极简的 PHP 框架，应用于我们的所有 PHP 项目，也欢迎您的使用。  
Chameleon PHP framework, a streamlined PHP framework that applies to all of our PHP projects, is also welcome for your use.  

## 极轻 / Extremely light
完整项目仅有 320K 大小，但包含了平时使用当中的全部常用功能。  
The full project is only 320K in size, but includes all of the usual features used in normal use.  
### 库列表 / libs
Aes, Db, Mailer, Memcached, Net, Oss, Redis, Session, Sql, Text

## HTTPS
我们更推荐您部署 SSL，保护数据传输安全，部署 SSL 后使可在某个控制器方法内使用 mustHttps() 保证用户访问的一定是安全链接或者设置 set.php 常量 MUST_HTTPS 为 true 保证全站都必须为 HTTPS 链接。  
We recommend that you deploy SSL, secure data transfer, and enable the use of mustHttps () within a controller method to ensure that the user accesses a secure link or sets the set.php constant MUST_HTTPS to true to ensure that all stations must For HTTPS links.  

## 自动加载类文件 / Automatically load class files
您只需要在控制器中尽情使用类库，文件会被系统识别并自动加载。  
You only need to use the library in the controller, the file will be recognized by the system and automatically loaded.  
  
## 生成16位随机数 / Generates a 16-bit random number
```php
echo Text::random(16, ['N']);
```
  
## 对阿里云优化 / Optimized for Alibaba Cloud
使用 Memcached、Oss、Redis、Db 类可以轻松使用阿里云的相关独立服务，当然，这些类也兼容自己搭建的服务器。  
Use Memcached, Oss, Redis, Db category can easily use the Alibaba Cloud independent services, of course, these classes are also compatible with their own built server.  
  
## 关于 doc 目录 / About doc
这个目录的内容已经相当老，未抽出时间更新，内容请暂时忽略。  
The contents of this directory has been quite old, did not take the time to update, please temporarily ignore the content.  
  
## 关于 About
本组件由雨滴社群开发开源，欢迎各位PR。  
The components developed by the Yu Di She Qun, to welcome you Pull Request.    
  
Translated by Google.