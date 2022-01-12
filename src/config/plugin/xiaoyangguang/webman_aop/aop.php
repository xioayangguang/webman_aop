<?php
//定义切入方法区分大小写
use app\controller\Index;
use Xiaoyangguang\WebmanAop\Example\TestAspect;

return [
    TestAspect::class => [
        Index::class => [
            'index',
        ],
    ],
];
