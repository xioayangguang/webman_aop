<?php
//定义切入方法区分大小写
use app\aop\TestAspect;
use app\shop\controller\BroadcastRoom;

return [
    TestAspect::class => [
        BroadcastRoom::class => [
            'list',
        ],
    ],
];
