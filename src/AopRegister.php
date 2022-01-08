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
        try {
            $cache_dir = runtime_path() . '/aop/';
            $fp = fopen($cache_dir . 'process.lock', 'a+');
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                var_dump($worker->id . '获取锁');
                self::clearCache($cache_dir);
                $aspect_config = config('aop');
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
                    file_put_contents($cache_path, "<?php" . PHP_EOL . $proxy_code);
                }
            } else {
                var_dump('获取锁失败');
            }
        } catch (\Throwable $throwable) {
            var_dump($throwable);
            throw $throwable;
            flock($fp, LOCK_UN);
            fclose($fp);
        }
        flock($fp, LOCK_UN);
        fclose($fp);
        self::autoloadRegister();
    }

    /**
     * 生产代码
     * @param $class
     * @param $propertys
     * @return string
     * @throws \Exception
     */
    private static function generateCode(&$class, $propertys)
    {
        $class_path = base_path() . '/' . $class . '.php';
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $ast = $parser->parse(file_get_contents($class_path));
        $class_namespace_array = explode('/', $class);
        $visitor = new ProxyVisitor(end($class_namespace_array), $propertys);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $proxy_ast = $traverser->traverse($ast);
        
        foreach ($proxy_ast as $node) {
            if ($node instanceof \PhpParser\Node\Stmt\Namespace_) {
                $class_namespace = join('/', $node->name->parts);
                $class_name = basename($class_path, ".php");
                $class = $class_namespace . '/' . $class_name;
            }
        }
        if (!$proxy_ast) throw new \Exception(sprintf('Class %s AST 处理失败', $class));
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
                if ($val != "." && $val != "..") unlink($path . $val);
            }
        } else {
            mkdir($path, 0755, true);
        }
    }
}