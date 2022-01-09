<?php
//定义切入方法区分大小写
use app\aop\TestAspect;
use app\social\controller\Like;
use  app\social\service\LikeService;

return [
    TestAspect::class => [ //切面
        Like::class => [ //切入类
            'list', //切入点
            'set',
        ],
        LikeService::class => [
            'list',
        ],
    ],
];


