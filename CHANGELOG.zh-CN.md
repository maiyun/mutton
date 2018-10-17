## 更新日志

### 5.0.0

*2018-06-17*

- 大量优化，全面严格模式，即将发布正式版。

### 3.5

*2018-05-22*

- 改名发布前的最后版本。

#### 新特性

- 增加 SMS SDK 以及 Sms 类的胶水代码。
- Net 类新增 https 访问能力，SSL 真实验证，不是简单屏蔽验证，保障 https 网址的访问安全。
- Net 类新增 JSON 格式的 Post 请求。
- Net 类新增 Cookie 变量式留存，可存放在诸如 MySQL、Redis 等任何储存器。
- AliOSS 类 opt 新增 data 参数，可以传递任意参数。
- Redis 模拟器新增 delete 方法。
- Text 类新增 sizeFormat 方法用于格式化显示文件大小信息。
- Wechat 类新增 getUserInfo 通过公众号获取用户信息方法。
- Wechat 类新增 loginMS 方法用以小程序登录。
- Wechat 类新增 getWXConfig 方法用以获取相关 JS SDK 操作权限。
- mod 模型基类的 get 方法新增 lock 参数，在开启事务后设置为 true 可保证在事物结束前此条不可再被篡改。
- mod 模型基类新增 getListOpt 方法，可设置更多参数。
- mod 模型基类新增 removeByWhere 静态方法，根据 where 移除相关条。
- mod 模型基类新增 updateByWhere 静态方法，根据 where 更新相关条数据。
- 修复 mod 模型基类 trait modKey 中，创建完后更新数据没有清空的 BUG。
- 优化 .htaccess。

#### 修复和优化

- 修复 MOBILE 和 WECHAT 常量检测时可能因为 SERVER 相关信息不存在而报错的 BUG。
- 修改 SMS 配置信息的常量关键词。
- 修复 Db 类的连接报错问题。
- 优化 Net 类，增加更强大的 request 方法，统一访问数据。
- 优化 Wechat 类 login 方法的 url 传入参数。

### 3.0

*2017-11-20*

#### 新特性

- 新增 OSS_ENDPOINT_NI 常量，对应 OSS 内网地址。
- 新增 CACHE_TTL 常量，可定义页面缓存。
- NET 类 get/pust 支持 headers 设置，get 支持 data 设置，post 支持提交 JSON 格式。
- OSS 新增浏览器直传支持。
- 新增 Redis 模拟器类库，可在 etc/db.php RD_SIMULATOR 配置为 true 开启。
- 微信类库新增微信支付相关。
- mod 中 count 方法可以自定义前缀。

#### 修复和优化

- 更新 OSS 官方库到 2.2.4。
- AES 类更新加密方式由 mcrypt 为 openssl，因为 PHP 7 不建议 mcrypt。
- mod 模型只要调用 set 就必须得进行修改，而再判断是否变了。
- 优化 .htaccess，支持获取 HTTP 认证头。
- 优化 index.php，如果是 index.htm(l) 入口，则跳转到首页不再显示 404。
- 优化 tab 为空格。

### 3.0 Alpha

*2017-07-12*

- 3.0 即将发布，更高效率，更灵活，更轻更小。

### 2.6

*2016-11-10*

#### 新特性

- ctr 新增默认 sql 属性，用于全局使用 sql 类 (需自行初始化)。
- mod 基类 getList 方法新增参数 array，可设置返回为数组而不是对象。
- const 新增 HTTPS\_PATH 表示安全的 HTTPS 链接（对应 HTTP_PATH）。
- URL 路径新增下划线（\_）的识别。
- set.php 新增 MUST_HTTPS 常量，设置为 true 后保证全站仅支持 https 访问。
- mod 支持更好的 use modKey 模式，支持主键为您定义的随机字串或唯一任意字段为随机字串的设置（新增识别 __key 内部变量）。
- Net::post 新增 $upload 参数（默认 false），当需要上传文件时设置为 true。
- Text 类新增 phoneSP 方法，用来判断是联通、电信还是移动的手机号。
- Text 类新增 phoneSPGroup 方法，用来将不同运营商的电话列表重新分组，同一个运营商的放在同一个数组。
- Aes 类将支持字符串类型的加密，而不仅仅是数组，需要加密数组请用 json_encode 或者序列化。
- 重大更新，采用全新的路由机制，在 set.php 当中定义路由。

#### 修复和优化

- 完全移除 RSA 支持，删除 sys/rsa 目录，删除 lib/Rsa 类。
- 默认关闭访问写入访问日志，推荐您使用 Apache 的访问日志。
- Net 的 post 将自动识别是文件上传还是非普通 post。
- log 增加对 HTTP_USER_AGENT 的记录。

### 2.5

*2016-09-14*

- set 移除 RSA 相关支持，您需要用更优秀的 https 方案作为替代方案。
- ctr 类移除 writeAesJson() 方法，您需要用更优秀的 https 方案作为替代方案。
- ctr 类新增 isHttps() 方法，判断当前连接是否是安全的。
- ctr 类新增 mustHttps() 方法，若当前连接不安全则强制重定向到安全连接。

### 2.4

*2016-08-30*

- set 新增 STATIC_PATH。

### 2.3

*2016-08-28*

- 新增 $this->action;。
- 优化 .htaccess。

### 2.1

*2016-02-10*

- 优化大部分更新，版本号变为 2.1。

### 2.0

*2016-02-10*

- 大部分重写 Chameleon，版本号变更为 2.0。

### 1.1

*2015-07-15*

- 添加 Model::set 方法，可以根据条件设置属性。
- 修改 Model::update 方法，添加对多条件的支持。
- 修改 Model::create 方法，将之定为专用于 auto_increment 表的插入方法。
- 引入 trait ModelWithPKey，用于取代 Model::create。

### 1.0

*2015-07-14*

- Add Chinese supports to the JSON encoder
- Improve speed by removing useless judgements
- Fixed the Memcached engine
- Add "add" method for Lib.Memcached and Lib.Memcached.Emulator
- Update "my_PhpStorm.php"
- Remove config file's namespace
- Fix instanceof BUG
- Fix can not use M()->load to load Module BUG
- Add "__autoload" for load Model
- 新增 model 的主模型

### 0.1

*x*

- X