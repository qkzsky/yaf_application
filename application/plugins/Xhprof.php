<?php

/**
 * XhprofPlugin.php
 */
class XhprofPlugin extends Yaf_Plugin_Abstract
{

    //在路由之前触发，这个是7个事件中, 最早的一个. 但是一些全局自定的工作, 还是应该放在Bootstrap中去完成
    public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        xhprof_enable();
    }

    //分发循环结束之后触发，此时表示所有的业务逻辑都已经运行完成, 但是响应还没有发送
    public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        $source      = $request->getModuleName() . "%" . $request->getControllerName() . "::" . $request->getActionName();
        $xhprof_data = xhprof_disable();

        Yaf_Loader::import(Yaf_Application::app()->getConfig()->application->library . '/xhprof/xhprof_lib/utils/xhprof_lib.php');
        Yaf_Loader::import(Yaf_Application::app()->getConfig()->application->library . '/xhprof/xhprof_lib/utils/xhprof_runs.php');

        $xhprof_runs = new \XHProfRuns_Default();
        $run_id      = $xhprof_runs->save_run($xhprof_data, $source);
        $xhprof_url  = "http://xhprof.me/index.php?source={$source}&run={$run_id}";

        $logger = \eYaf\Logger::getLogger('xhprof');
        $logger->log("{$source}\t{$xhprof_url}");
        echo 'Xhprof: <a href="' . $xhprof_url . '">' . $source . '</a>';
    }

}
