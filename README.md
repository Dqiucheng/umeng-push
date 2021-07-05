# umeng-push
友盟推送SDK，采用链式操作，支持一键双端发送与撤销等。

## Installation

#### 使用 Composer 安装
```shell
$ composer require qiucheng/dumeng
```

## 使用示例
**注意: 以下只是简单的使用示例, 不应该直接用于实际环境中!!**

### 初始化客户端
```php
require __DIR__ .'./vendor/autoload.php';

use Dumeng\Client;

$config = [
    'android' => [
        'appkey' => 'xxxxxxx',
        'appMasterSecret' => 'xxxxxxxxx',
    ],
    'ios' => [
        'appkey' => 'xxxxxxxxx',
        'appMasterSecret' => 'xxxxxxxxx',
    ]
];
//参数二可以指定日志路径，若要关闭日志设置为空即可。new Client($config, null);
$Client = new Client($config);
```

### 推送消息
```php
$push = $Client->push();
$push->set_platform(['ios','android']);
$push->set_type('broadcast');
$push->set_payload(['ios'=>['aaa'],'android'=>['bbb']]);
$push->send();
```
set_xxx函数参数对应友盟官方u-push 文档: https://developer.umeng.com/docs/67966/detail/68343

### 消息状态查询
```php
$push = $Client->push();
$push->set_platform(['ios','android']);
$push->status('XXXXX');
```

### 消息撤销
```php
$push = $Client->push();
$push->set_platform(['ios','android']);
$push->cancel('XXXXX');
```

### 文件上传
```php
$push = $Client->push();
$push->set_platform(['ios','android']);
$push->upload('XXXXX');
```




