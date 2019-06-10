<?php

class RefererPlugin extends Yaf_Plugin_Abstract
{

    public function routerShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        $referer     = $request->getServer("HTTP_REFERER");
        $server_name = $request->getServer("SERVER_NAME");
        if ($request->isPost() && !preg_match("/^http(s)?:\/\/{$server_name}/i", $referer)) {
            throw new AppException("invalid http referer, server_name:{$server_name}, referer:{$referer}", ErrorCode::INVALID_PARAMETER);
        }
    }

    public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
    }

    protected function verify_auth_token(Yaf_Request_Abstract $request)
    {
    }

}
