<?php
/**
 * Created by PhpStorm.
 * User: kuangzhiqiang
 * Date: 2016/1/6 0006
 * Time: 10:42
 */

namespace Db;


final class DbString
{
    private static $_static = array();
    private        $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * 准备直接执行的sql字符串
     * @param string $string
     * @return static
     */
    public static function prepare($string)
    {
        $_key = md5(sha1($string));
        if (!isset(self::$_static[$_key]))
        {
            self::$_static[$_key] = new static($string);
        }
        return self::$_static[$_key];
    }

    public function __toString()
    {
        return $this->value;
    }
}