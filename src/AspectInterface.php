<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaoxiao
 */

namespace xioayangguang\webman_aop;

interface AspectInterface
{
    /**
     * 前置通知
     * @param $params
     * @param $method
     */
    public function beforeAdvice($params, $method): void;

    /**
     * 后置通知
     * @param $res
     * @param $params
     * @param $method
     */
    public function afterAdvice(&$res, $params, $method): void;
}