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
 * @version  IndexTest.php v1.0 2018/8/9 20:46
 */

class DemoTest extends \Test\PHPUnit\ControllerTestCase
{
    public function testHello()
    {
        $request  = new \Yaf_Request_Simple("CLI", "Demo", "index", "unit");
        $response = $this->getApplication()->getDispatcher()
            ->returnResponse(true)
            ->dispatch($request);
        $content  = $response->getBody();

        $result = "unit test";
        $this->assertEquals($result, $content);
    }

    // public function testHello2()
    // {
    //     $request  = new \Yaf_Request_Simple("CLI", "Demo", "hello", "");
    //     $response = $this->getApplication()->getDispatcher()
    //         ->returnResponse(true)
    //         ->dispatch($request);
    //     $content  = $response->getBody();
    //
    //     $result = "hello world";
    //     $this->assertEquals($result, $content);
    // }
}