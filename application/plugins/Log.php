<?php

use eYaf\Logger;

class LogPlugin extends Yaf_Plugin_Abstract
{
    /**
     * @var \eYaf\Logger
     */
    private $logger;

    function __construct()
    {
        $this->logger = Logger::getLogger();
    }

    public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        $this->logger->startLogging();
        $this->logger->log("[{$request->getRequestUri()}]");
    }

    public function routerShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        $this->logger->logRequest($request);
    }

    public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {

    }

    public function preDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {

    }

    public function postDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {

    }

    public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        $this->logger->stopLogging();
    }

    public function preResponse(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {

    }

}
