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
 * @version  bootstrap.php v1.0 2018/8/9 18:16
 */

// 项目根目录
define("APP_PATH", realpath(dirname(__FILE__) . "/../"));

// var_dump(class_exists("\PHPUnit\DbUnit\TestCase"));
// var_dump(class_exists("\PHPUnit\Framework\TestCase"));
// exit;

spl_autoload_register(function($class_name) {
    $file = implode(DIRECTORY_SEPARATOR, [
        APP_PATH,
        "library",
        trim(str_replace(["_", "\\"], DIRECTORY_SEPARATOR, $class_name), DIRECTORY_SEPARATOR) . ".php"
    ]);
    if (is_file($file)) {
        Yaf_Loader::import($file);
    }
});

ini_set("yaf.use_spl_autoload", 1);