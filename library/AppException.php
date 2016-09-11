<?php

/**
 * Created by PhpStorm.
 * User: kuangzhiqiang
 * Date: 2016/4/3 0003
 * Time: 17:10
 */
class AppException extends Yaf_Exception
{
    public function __construct($message, $code = null, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}