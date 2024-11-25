# DB For Yii2.0
该组件扩展支持yii框架不支持的数据库驱动，目前增加了达梦数据库的支持，并对部分函数、关键字做了mysql格式的语法翻译。

特性说明：
1、支持不同第三方数据库连接（目前已实现达梦数据库）；
2、支持主要数据库ORM操作封装，比如创建索引、创建表等；
3、支持函数转换，并提供用户扩展转换sql的方法。

### 安装方法
------------
```shell
composer require huaweichenai/dmbase
```
### 配置
------------
在配置文件中components中添加连接组件

达梦数据库配置
```php
'components' => [
    'db' => [
        'class' => 'huaweichenai\dmbase\table\db\Connection',
        'dsn' => 'dm:host=127.0.0.1;port=5432;dbname=模式;',
        'username' => '用户名',
        'password' => '密码',
    ],
]
```
