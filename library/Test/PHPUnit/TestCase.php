<?php
/**
 * Mi Framework
 *
 * Copyright (C) 2018 by kuangzhiqiang. All rights reserved
 *
 * To contact the author write to {@link mailto:kuangzhiqiang@xiaomi.com}
 *
 * @author   kuangzhiqiang
 * @encoding UTF-8
 * @version  TestCase.php v1.0 2018/8/9 18:17
 */

namespace Test\PHPUnit;

/**
 * 测试基类
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * yaf运行实例
     *
     * @var \Yaf\Application
     */
    protected $_application = null;

    protected function setUp()
    {
        $this->setApplication();

        parent::setUp();
    }

    /**
     * 设置application
     */
    public function setApplication()
    {
        $application = new \Yaf_Application(APP_PATH . "/config/application.ini", YAF_ENVIRON);
        $application->bootstrap();
        $this->_application = $application;
    }

    /**
     * 获取application
     *
     * @return \Yaf_Application
     */
    public function getApplication()
    {
        if (self::$_application == null) {
            $this->setApplication();
        }
        return $this->_application;
    }
}