<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaoxiao
 */

namespace xioayangguang\webman_aop;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\NodeFinder;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Stmt\Property;

class ProxyVisitor extends NodeVisitorAbstract
{
    /**
     * @var string
     */
    protected $className;

    /**
     * @var array
     */
    protected $property;

    /**
     * @param string $className
     * @param array $methods
     */
    public function __construct(string $className, array $property)
    {
        $this->className = $className;
        $this->property = $property;
    }

    /**
     * @return TraitUse
     */
    private function getAopTraitUseNode(): TraitUse
    {
        return new TraitUse([new Name('\\' . __NAMESPACE__ . '\\AopTrait')]);
    }

    /**
     * @return Property
     */
    private function getAopPropertyNode(): Property
    {
        $property_array_items = [];
        foreach ($this->property as $key => $item) {
            $array_items = [];
            foreach ($item as $value) $array_items[] = new ArrayItem(new String_($value));
            $property_array_items[] = new ArrayItem(new Array_($array_items), new String_($key));
        }
        return new Property(Class_::MODIFIER_STATIC, [new PropertyProperty('__AspectMap__', new Array_($property_array_items))]);
    }

    /**
     * @param Node $node
     * @return Class_|ClassMethod|void
     */
    public function leaveNode(Node $node)
    {
        if ($node instanceof Class_) {
            return new Class_($this->className, [ 
                'flags' => $node->flags,
                'stmts' => $node->stmts,
                'extends' => $node->extends,
                'implements' => $node->implements,
                'attrGroups' => $node->attrGroups,
            ]);
        }
        if ($node instanceof ClassMethod) {
            $method_name = $node->name->toString();
            if (in_array($method_name, array_keys($this->property))) {
                $uses = [];
                foreach ($node->params as $key => $param) {
                    if ($param instanceof Param)
                        $uses[$key] = new Param($param->var, null, null, true);
                }
                $params = [
                    new Closure(['static' => false, 'uses' => $uses, 'stmts' => $node->stmts]),
                    new String_($method_name),
                    new FuncCall(new Name('func_get_args')),
                ];
                $stmts = [new Return_(new StaticCall(new Name('self'), '__ProxyClosure__', $params))];
                $return_type = $node->getReturnType();
                if ($return_type instanceof Name && $return_type->toString() === 'self') {
                    $return_type = new Name('\\' . $this->className);
                }
                return new ClassMethod($method_name, [
                    'flags' => $node->flags,
                    'byRef' => $node->byRef,
                    'params' => $node->params,
                    'returnType' => $return_type,
                    'stmts' => $stmts,
                ]);
            }
        }
    }

    /**
     * @param array $nodes
     * @return array
     */
    public function afterTraverse(array $nodes): array
    {
        $add_enhancement_methods = true;
        $node_finder = new NodeFinder();
        $node_finder->find($nodes, function (Node $node) use (&$add_enhancement_methods) {
            if ($node instanceof TraitUse) {
                foreach ($node->traits as $trait) {
                    if ($trait instanceof Name && $trait->toString() === '\\' . __NAMESPACE__ . '\\AopTrait') {
                        $add_enhancement_methods = false;
                        break;
                    }
                }
            }
        });
        $class_node = $node_finder->findFirstInstanceOf($nodes, Class_::class);
        array_unshift($class_node->stmts, $this->getAopPropertyNode());
        $add_enhancement_methods && array_unshift($class_node->stmts, $this->getAopTraitUseNode());
        return $nodes;
    }
}