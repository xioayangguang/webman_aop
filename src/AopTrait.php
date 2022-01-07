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
     * @param string $class
     * @param array $params_value
     * @param array $params_key
     * @return mixed
     */
    public static function __ProxyClosure__(\Closure $closure, string $method, string $class, array $params_value, array $params_key)
    {
        $params = [];
        foreach ($params_key as $key => $item) {
            $params[$item] = $params_value[$key] ?? 'DEFAULT';
        }
        $pipes = self::$__AspectMap__[$method] ?? [];
        $callback = array_reduce($pipes, function ($carry, $pipe) use ($method, $class, $params) {
            return function () use ($method, $class, $carry, $pipe, $params) {
                try {
                    /** @var AspectInterface $pipe */
                    $pipe::beforeAdvice($params, $class, $method);
                    $res = $carry();
                    $pipe::afterAdvice($res, $params, $class, $method);
                    return $res;
                } catch (\Throwable $throwable) {
                    $pipe::exceptionHandler($throwable, $params, $class, $method);
                    throw $throwable;
                }
            };
        }, function () use ($closure) {
            return $closure();
        });
        return $callback();
    }
}
