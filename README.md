### webmanAop使用教程

> 同时支持直接new和从容器获取需要被切入的对象， 在不改变现有代码的情况下切入需要切入的前置后置方法，用在统计http请求，Rpc，组件链路追踪，日志记录，统计函数耗时 修改函数返回结果的应用场景


#### 安装

```
composer require xiaoyangguang/webman_aop
```


>配置 bootstrap.php文件

```php
<?php
return [
    xioayangguang\webman_aop\AopRegister::class  //建议放在上面，否则先前加载的类无法使用到AOP
     //....省略其他 
];
```


>我们需要在 config 目录下，增加 aop.php 配置

```php
//定义切入方法区分大小写
<?php
return [
    \app\aop\TestAspect::class => [ //切面类
        app\service\IndexService::class => [ //被拦截的类
            'list',  //被拦截的方法
            'index', //被拦截的方法
        ],
    ],
];
```

>首先编写待切入类 app\aop\TestAspect

```php
<?php

namespace app\aop;

use xioayangguang\webman_aop\AspectInterface;

class TestAspect implements AspectInterface
{
    /**
     * 前置通知
     * @param $params
     * @param $method
     * @return mixed|void
     */
    public function beforeAdvice($params, $method): void
    {
        var_dump('beforeAdvice', $params, $method);
        echo PHP_EOL;
    }

    /**
     * 后置通知
     * @param $res
     * @param $params
     * @param $method
     * @return mixed|void
     */
    public function afterAdvice(&$res, $params, $method): void
    {
        var_dump('afterAdvice', $res, $params, $method);
        echo PHP_EOL;
    }
}
```


> 接下来编辑控制器 app\controller\Index

```php
<?php

namespace app\controller;

use app\service\IndexService;
use support\Container;
use support\Request;

class Index
{
    public function index(Request $request)
    {
        /** @var IndexService $IndexService */
        $IndexService = Container::get(IndexService::class);  //可以直接从容器里面获取
        $re = $IndexService->index();
        
        //$IndexService = new IndexService();  //也可以直接new 目标对象
        
        $re = $IndexService->index();
        $re = $IndexService->list();
        return response($re);
    }
}
```


> 编写service （被切入的类） app\service\IndexService

```php

<?php

namespace app\service;

class IndexService
{
    public function index()
    {
        return "IndexService.index";
    }

    public function list()
    {
        return "IndexService.list";
    }
}

```

>最后启动服务，并测试。

```shell
php start.php start
curl  http://127.0.0.1:8787
此时控制台打印前置和后置切面函数打印值
```


