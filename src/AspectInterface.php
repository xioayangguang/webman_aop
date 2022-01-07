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
     * @param $class
     * @param $method
     */
    public static function beforeAdvice($params, $class, $method): void;

    /**
     * 后置通知
     * @param $res
     * @param $params
     * @param $class
     * @param $method
     */
    public static function afterAdvice(&$res, $params, $class, $method): void;

    /**
     * 异常处理
     * @param $res
     * @param $params
     * @param $class
     * @param $method
     */
    public static function exceptionHandler(&$res, $params, $class, $method): void;
}