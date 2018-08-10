<?php

// 项目根目录
define("APP_PATH", realpath(dirname(__FILE__) . "/../"));

// develop 为开发环境  product 为生产环境
$app     = new Yaf_Application(APP_PATH . "/config/application.ini", YAF_ENVIRON);
$app->bootstrap()->run();
