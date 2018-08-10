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

class IndexTest extends \Test\PHPUnit\ControllerTestCase
{
    public function testDemo()
    {
        $request  = new \Yaf_Request_Simple("CLI", "", "Index", 'Demo');
        $response = $this->_application->getDispatcher()
            ->returnResponse(true)
            ->dispatch($request);
        $content  = $response->getBody();

        $this->assertEquals('demo', $content);
    }
}