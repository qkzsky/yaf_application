<?php

/**
 * 自定义路由
 * @author kuangzhiqiang
 */
class Router implements Yaf_Route_Interface
{
    /**
     * 路由规则定义
     * @param Yaf_Request_Abstract $request
     * @return boolean
     */
    public function route($request)
    {
        $uri = explode('/', trim($request->getRequestUri(), '/'));
        // 路径中第一级只要匹配上module名称, 就统一定向至module处理
        if (in_array(ucfirst(strtolower($uri[0])), Yaf_Application::app()->getModules()))
        {
            $request->setModuleName($uri[0]);
            isset($uri[1]) && $request->setControllerName($uri[1]);
            isset($uri[2]) && $request->setActionName($uri[2]);
            if (isset($uri[3]))
            {
                $params = array_slice($uri, 3);
                foreach ($params as $key => $value)
                {
                    if ($key % 2 === 0)
                    {
                        $request->setParam($value, isset($params[$key + 1]) ? $params[$key + 1] : null);
                    }
                }
            }
            return true;
        }
    }

    public function assemble(array $info, array $query = array())
    {
        return true;
    }

}
