<?php

// 项目根目录
define("APP_PATH", realpath(dirname(__FILE__) . "/../"));
// models所在目录
define("MODELS_PATH", APP_PATH . "/application/models/");

// develop 为开发环境  product 为生产环境
$app     = new Yaf_Application(APP_PATH . "/config/application.ini", YAF_ENVIRON);
$app->bootstrap()->run();
