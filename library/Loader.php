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
 * @version  Loader.php v1.0 2018/7/27 23:57
 */

class Loader
{
    public static function loaderService(string $class_name)
    {
        if (substr($class_name, -7) !== "Service") {
            return;
        }

        $file = implode(DIRECTORY_SEPARATOR, [
            Yaf_Application::app()->getConfig()->application->directory,
            "services",
            trim(str_replace(["_", "\\"], DIRECTORY_SEPARATOR, substr($class_name, 0, -7)), DIRECTORY_SEPARATOR) . ".php"
        ]);
        if (!is_file($file)) {
            throw new Yaf_Exception_LoadFailed("Failed opening script {$file}: No such file or directory");
        }

        Yaf_Loader::import($file);
    }
}