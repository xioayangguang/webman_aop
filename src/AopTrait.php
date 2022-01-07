<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaoxiao
 */

namespace xioayangguang\webman_aop;

/**
 * @property array $__AspectMap__
 */
trait AopTrait
{
    /**
     * @param \Closure $closure
     * @param string $method
     * @param array $params
     * @return mixed
     */
    public static function __ProxyClosure__(\Closure $closure, string $method, array $params)
    {
        $pipes = self::$__AspectMap__[$method] ?? [];
        $callback = array_reduce($pipes, function ($carry, $pipe) use ($method, &$params) {
            return function () use ($method, $carry, $pipe, &$params) {
                /** @var AspectInterface $pipe */
                $pipe::beforeAdvice($params, $method);
                $res = $carry();
                $pipe::afterAdvice($res, $params, $method);
                return $res;
            };
        }, function () use ($closure) {
            return $closure();
        });
        return $callback();
    }
}
