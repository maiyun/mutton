## 2016-09-14 (2.5)
* set 移除 RSA 相关支持，您需要用更优秀的 https 方案作为替代方案。
* ctr 类移除 writeAesJson() 方法，您需要用更优秀的 https 方案作为替代方案。
* ctr 类新增 isHttps() 方法，判断当前连接是否是安全的。
* ctr 类新增 mustHttps() 方法，若当前连接不安全则强制重定向到安全连接。

## 2016-08-30 (2.4)

* set 新增 STATIC_PATH。

## 2016-08-28 (2.3)
  
* 新增 $this->action;。
* 优化 .htaccess。
  
## 2016-02-10
  
* 优化大部分更新，版本号变为 2.1。
  
## 2016-02-10
  
* 大部分重写 Chameleon，版本号变更为 2.0。
  
## 2015-07-15
  
* 添加 Model::set 方法，可以根据条件设置属性。
* 修改 Model::update 方法，添加对多条件的支持。
* 修改 Model::create 方法，将之定为专用于 auto_increment 表的插入方法。
* 引入 trait ModelWithPKey，用于取代 Model::create。
  
## 2015-07-14
  
* Add Chinese supports to the JSON encoder
* Improve speed by removing useless judgements
* Fixed the Memcached engine
* Add "add" method for Lib.Memcached and Lib.Memcached.Emulator
* Update "my_PhpStorm.php"
* Remove config file's namespace
* Fix instanceof BUG
* Fix can not use M()->load to load Module BUG
* Add "__autoload" for load Model
* 新增 model 的主模型