<?php
/*
 * Mi Framework
 *
 * Copyright (C) 2015 by kuangzhiqiang. All rights reserved
 *
 * To contact the author write to {@link mailto:kuangzhiqiang@xiaomi.com}
 *
 * @author kuangzhiqiang
 * @encoding UTF-8
 * @version $Id: Index.php, v 1.0 2015-3-6 14:49:53
 */

/**
 * Description of Index
 *
 * @author kuangzhiqiang
 */
class IndexController extends ApplicationController
{

    protected $layout = 'frontend';

    public function indexAction()
    {
        $data = array(
            "title"   => "Home Page",
            "content" => "Home Page"
        );

        $this->display("index", $data);
    }

    // php index.php "index%index/shell?k1=v1&k2=v2"
    public function shellAction()
    {
        if (!$this->getRequest()->isCli()) {
            $this->redirectNotFound();
            return false;
        }

        $params = $this->getRequest()->getParams();
        printf("Time: %s\nParams: %s\n", __DATETIME__, http_build_query($params));
    }
}
