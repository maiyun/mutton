# Chameleon
变色龙 PHP 框架，一个极简的 PHP 框架，我们只给懂我们的人使用。  
Chameleon PHP framework a very simple PHP framework, we used only to understand our people.  

## 极轻 / Extremely light
完整项目仅有 320K 大小，却包含了平时使用当中的全部常用功能。  
Complete item only 320K sizes, includes the usual features common to all of them.  
### 库列表 / libs
Aes, Db, Memcached, Net, Oss (Need OSS PHP SDK), Redis, Session, Sql, Text

## 使用 https 效果极佳 / Using HTTPS with excellent results
我们更推荐您部署了 https 协议，保护数据传输的安全，部署 SSL 后使用 ctr 基类的 mustHttps() 方法来保证用户访问的一定是安全链接。  
We recommend that you deploy the HTTPS protocol, protect the security of data transfer, deploy SSL after the ctr base class's mustHttps() method to ensure user access must be secure links.  

## 自动加载类文件 / Automatically loads the class files
您只需要在控制器中尽情使用，类文件会被系统识别并自动加载。  
You just need to go ahead in the controller class file will be recognized by the system and automatically load.  
  
## 生成16位随机数演示 / Generates a 16-bit random number shows
```php
echo Text::random(16, ['N']);
```
  
## 对阿里云有支持扩展优化 / Alibaba Cloud supported optimization
使用 Memcached、Oss、Redis 类可以轻松使用阿里云的相关独立服务，当然，这些类也兼容自己搭建的服务器。  
Using Memcached, Oss, Redis can easily use Alibaba Cloud related services independently, of course, these classes are also compatible with your build server.  
  
## 关于 doc 目录 / About doc
这个目录的内容已经相当老，未抽出时间更新，内容请暂时忽略。  
The content of this directory is already quite old, not taking the time to update, please ignore.  
  
## 关于 About
本组件由韩国帅开发开源，欢迎各位PR。  
Powered by Han Guo Shuai, welcome to pull request.  
https://hanguoshuai.com  
  
Translation is provided by Microsoft.