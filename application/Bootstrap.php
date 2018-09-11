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
 * @version $Id: Bootstr.php, v 1.0 2015-3-6 15:27:35
 */

/**
 * 所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
 * 这些方法, 都接受一个参数:Yaf_Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 * @author kuangzhiqiang
 */
class Bootstrap extends Yaf_Bootstrap_Abstract
{

    private function _initErrors(Yaf_Dispatcher $dispatcher)
    {
        error_reporting(-1);
        //报错是否开启
        if (Yaf_Application::app()->getConfig()->application->displayErrors) {
            ini_set('display_errors', 'On');
        } else {
            ini_set('display_errors', 'Off');
        }

        $dispatcher->setErrorHandler([__CLASS__, 'error_handler']);
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error) {
                \Logger::getLogger("fatal_error")->error(json_encode($error));
            }
        });
    }

    /**
     * 定义一些常量
     */
    private function _initConstant()
    {
        Yaf_Loader::import(APP_PATH . "/include/const.php");
    }

    private function _initTimezone()
    {
        ini_set("date.timezone", Yaf_Application::app()->getConfig()->application->timezone);
    }

    private function _initIncludePath()
    {
        // set_include_path(get_include_path() . PATH_SEPARATOR . $this->config->application->library);
    }

    private function _initFuntion()
    {
        Yaf_Loader::import(APP_PATH . '/include/function.php');
    }

    //private function _initRequest(Yaf_Dispatcher $dispatcher)
    //{
    //    $dispatcher->setRequest(new \Request());
    //}

    /**
     * 初始化视图，如果有自己的视图工具，关闭其自动渲染
     * @param Yaf_Dispatcher $dispatcher
     */
    private function _initView(Yaf_Dispatcher $dispatcher)
    {
        // 关闭自动渲染
        $dispatcher->disableView();
        // todo
        // 在这注册自己的view控制器
        $layout = new \Layout(Yaf_Application::app()->getConfig()->application->layout->directory);
        $dispatcher->setView($layout);
    }

    private function _initPlugin(Yaf_Dispatcher $dispatcher)
    {
        // $dispatcher->registerPlugin(new LogPlugin());
        if (Yaf_Application::app()->getConfig()->application->xhprof && extension_loaded('xhprof')) {
            $dispatcher->registerPlugin(new XhprofPlugin());
        }
        // $dispatcher->registerPlugin(new SamplePlugin());

        //        if ($this->config->application->protect_from_csrf && !$dispatcher->getRequest()->isCli())
        //        {
        //            $dispatcher->registerPlugin(new AuthTokenPlugin());
        //        }
    }

    private function _initLoader(Yaf_Dispatcher $dispatcher)
    {
        spl_autoload_register(["Loader", "loaderService"], true, true);
    }

    /**
     * 路由设置
     * @param Yaf_Dispatcher $dispatcher
     */
    private function _initRoute(Yaf_Dispatcher $dispatcher)
    {
        $router = $dispatcher->getRouter();

        // 添加自定义路由规则
        $router->addRoute('my_route', new Router());

        $config = new Yaf_Config_Ini(CONF_PATH . "/routes.ini");
        $router->addConfig($config);
    }

    /**
     * Custom init file for modules.
     *
     * Allows to load extra settings per module, like routes etc.
     */
    private function _initModules(Yaf_Dispatcher $dispatcher)
    {
        $app = $dispatcher->getApplication();

        $modules = $app->getModules();
        foreach ($modules as $module) {
            if ($module === 'Index') {
                continue;
            }

            \Yaf_Loader::import($app->getAppDirectory() . "/modules/{$module}/_init.php");
        }
    }

    /**
     * php index.php "moduleName%controllerName/Action?k1=v1&k2=v2"
     */
    public function _initCli(Yaf_Dispatcher $dispatcher)
    {
        $request = $dispatcher->getRequest();
        if ($request->isCli()) {
            global $argc, $argv;
            if ($argc > 1) {
                $uri = $argv [1];
                if (preg_match('/^[^?]*%/i', $uri)) {
                    list ($module, $uri) = explode('%', $uri, 2);
                    if (isset($module)) {
                        $module = strtolower($module);
                        if (in_array(ucfirst($module), Yaf_Application::app()->getModules())) {
                            $request->setModuleName($module);
                        }
                    }
                }

                $args = [];
                if (false !== strpos($uri, '?')) {
                    list ($uri, $args_str) = explode('?', $uri, 2);
                    parse_str($args_str, $args);
                }
                $request->setRequestUri($uri);
                foreach ($args as $k => $v) {
                    $request->setParam($k, $v);
                }
            }
        }
    }

    /**
     * Custom error handler.
     *
     * Catches all errors (not exceptions) and creates an ErrorException.
     * ErrorException then can caught by Yaf_ErrorController.
     *
     * @param integer $errno the error number.
     * @param string $errstr the error message.
     * @param string $errfile the file where error occured.
     * @param integer $errline the line of the file where error occured.
     *
     * @throws ErrorException
     */
    public static function error_handler($errno, $errstr, $errfile, $errline)
    {
        // Do not throw exception if error was prepended by @
        //
        // See {@link http://www.php.net/set_error_handler}
        //
        // error_reporting() settings will have no effect and your error handler
        // will be called regardless - however you are still able to read
        // the current value of error_reporting and act appropriately.
        // Of particular note is that this value will be 0
        // if the statement that caused the error was prepended
        // by the @ error-control operator.
        //

        if (error_reporting() & $errno) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }
    }

}
