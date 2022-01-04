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
        $container = self::__GetContainer__();
        $pipes = self::$__AspectMap__[$method] ?? [];
        $callback = array_reduce($pipes, function ($carry, $pipe) use ($method, $container, &$params) {
            return function () use ($method, $carry, $pipe, $container, &$params) {
                /** @var AspectInterface $pipe */
                $pipe = $container->get($pipe);
                $pipe->beforeAdvice($params, $method);
                $res = $carry();
                $pipe->afterAdvice($res, $params, $method);
                return $res;
            };
        }, function () use ($closure) {
            return $closure();
        });
        return $callback();
    }

    /**
     * 获取容器，兼容老版本
     * @return \Psr\Container\ContainerInterface
     */
    private static function __GetContainer__()
    {
        if (class_exists('\support\Container', true)) {
            return \support\Container::instance();
        } else {
            return \support\bootstrap\Container::instance();
        }
    }
}
