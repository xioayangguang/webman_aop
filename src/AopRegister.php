<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaoxiao
 */

namespace xioayangguang\webman_aop;

use Webman\Bootstrap;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\PrettyPrinter\Standard;

class AopRegister implements Bootstrap
{
    /**
     * @param $worker
     * @return mixed|void
     * @throws \Exception
     */
    public static function start($worker)
    {
        self::GenerateProxy(config('aop'));
        self::autoloadRegister();
    }

    /**
     * 生成代理对象
     * @param array $aspect_config
     * @throws \Exception
     */
    public static function GenerateProxy(array $aspect_config)
    {
        $cache_dir = runtime_path() . '/aop/';
        self::clearCache($cache_dir);
        $aspect_map = [];
        foreach ($aspect_config as $aspect_class => $business_classes) {
            foreach ($business_classes as $class => $methods) {
                foreach ($methods as $method) {
                    $aspect_map[$class][$method][] = $aspect_class;
                }
            }
        }
        foreach ($aspect_map as $business_class => $method) {
            $business_class = \str_replace('\\', \DIRECTORY_SEPARATOR, $business_class);
            $proxy_code = self::generateCode($business_class, $method);
            $cache_path = $cache_dir . str_replace(DIRECTORY_SEPARATOR, "_", $business_class) . '.php';
            file_put_contents($cache_path, "<?php" . PHP_EOL . $proxy_code, LOCK_EX);
        }
    }

    /**
     * 生产代码
     * @param $business_class
     * @param $propertys
     * @return string
     * @throws \Exception
     */
    private static function generateCode(&$business_class, $propertys)
    {
        $class_path = base_path() . '/' . $business_class . '.php';
        if (!file_exists($class_path)) \Exception(sprintf('文件 %s 不存在!', $class_path));
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $ast = $parser->parse(file_get_contents($class_path));
        $class_namespace_array = explode('/', $business_class);
        $visitor = new ProxyVisitor(end($class_namespace_array), $propertys);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $proxy_ast = $traverser->traverse($ast);
        if (!$proxy_ast) throw new \Exception(sprintf('Class %s AST 处理失败', $business_class));
        foreach ($proxy_ast as $node) {
            if ($node instanceof \PhpParser\Node\Stmt\Namespace_) {
                $class_namespace = join('/', $node->name->parts);
                $class_name = basename($class_path, ".php");
                $business_class = $class_namespace . '/' . $class_name;
                break;
            }
        }
        $printer = new Standard();
        return $printer->prettyPrint($proxy_ast);
    }

    /**
     * 注册自动加载函数
     */
    private static function autoloadRegister()
    {
        \spl_autoload_register(function ($class) {
            $class_namespace_array = explode('\\', $class);
            $cache_name = join('_', $class_namespace_array) . '.php';
            $aop_cache_dir = runtime_path() . '/aop/';
            $path = $aop_cache_dir . $cache_name;
            if (file_exists($path)) {
                include_once $path;
                return true;
            } else {
                return false;
            }
        }, true, true);
    }

    /**
     * 清空缓存目录下的文件
     * @param $path
     */
    private static function clearCache($path)
    {
        if (is_dir($path)) {
            $dir = scandir($path);
            foreach ($dir as $val) {
                if ($val != "." && $val != "..") @unlink($path . $val);
            }
        } else {
            mkdir($path, 0755, true);
        }
    }
}