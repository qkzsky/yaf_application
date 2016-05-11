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
class IndexController extends Yaf_Controller_Abstract
{

    public function indexAction()
    {
        $this->display("index", array("content" => "Demo"));
    }
}
