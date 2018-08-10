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

class DemoTest extends \Test\PHPUnit\ServiceTestCase
{
    public function testDemo()
    {
        $model  = new \DemoService();
        $result = $model->test();
        $this->assertCount(6, $result);
        $this->assertEquals(2, $result[1]);
        $this->assertArrayHasKey(0, $result);
        // $this->assertEmpty($result, "result is empty");
        $this->assertNotEmpty($result, "result is empty");
    }
}