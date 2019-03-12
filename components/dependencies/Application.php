<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: lmh <lmh@weiyian.com>
 */

namespace app\components\dependencies;


use Exception;
use ReflectionClass;

class Application
{
    /**
     * @author lmh
     * @param $class
     * @param array $parameters
     * @return object
     * @throws \ReflectionException
     * @throws Exception
     */
    public static function make($class, $parameters = [])
    {
        // 通过反射获取反射类
        $rel_class = new ReflectionClass($class);

        // 查看是否可以实例化
        if (!$rel_class->isInstantiable()) {
            throw new Exception($class . ' 类不可实例化');
        }

        // 查看是否用构造函数
        $rel_method = $rel_class->getConstructor();

        // 没有构造函数的话，就可以直接 new 本类型了
        if (is_null($rel_method)) {
            return new $class();
        }

        // 有构造函数的话就获取构造函数的参数
        $dependencies = $rel_method->getParameters();
        // 处理，把传入的索引数组变成关联数组， 键为函数参数的名字
        foreach ($parameters as $key => $value) {
            if (is_numeric($key)) {
                // 删除索引数组， 只留下关联数组
                unset($parameters[$key]);

                // 用参数的名字做为键
                $parameters[$dependencies[$key]->name] = $value;
            }
        }
        // 处理依赖关系
        $actual_parameters = [];

        foreach ($dependencies as $dependenci) {
            // 获取对象名字，如果不是对象返回 null
            $class_name = $dependenci->getClass();
            // 获取变量的名字
            $var_name = $dependenci->getName();

            // 如果是对象， 则递归new
            if (array_key_exists($var_name, $parameters)) {
                $actual_parameters[] = $parameters[$var_name];
            } elseif (is_null($class_name)) {
                // null 则不是对象，看有没有默认值， 如果没有就要抛出异常
                if (!$dependenci->isDefaultValueAvailable()) {
                    throw new Exception($var_name . ' 参数没有默认值');
                }

                $actual_parameters[] = $dependenci->getDefaultValue();
            } else {
                $actual_parameters[] = self::make($class_name->getName());
            }
        }
        // 获得构造函数的数组之后就可以实例化了
        return $rel_class->newInstanceArgs($actual_parameters);
    }

}