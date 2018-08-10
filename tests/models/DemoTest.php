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
 * @version  UserTest.php v1.0 2018/8/9 21:12
 */

class DemoTest extends \Test\PHPUnit\ModelTestCase
{
    public function testAdd()
    {
        $model  = new \DemoModel();
        $a      = 1;
        $b      = 2;
        $result = $model->add($a, $b);
        $this->assertEquals($a + $b, $result);

        $a      = -1;
        $b      = 2;
        $result = $model->add($a, $b);
        $this->assertEquals($a + $b, $result);
    }
}