<?php
//定义切入方法区分大小写
return [
    \app\aop\TestAspect::class => [
        app\social\controller\Like::class => [
            'list',
            'set',
        ],
//        app\social\service\LikeService::class => [
//            'list',
//        ],
    ],
];


