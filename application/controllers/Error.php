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
 * @version $Id: Error.php, v 1.0 2015-3-6 20:45:36
 */

class ErrorController extends Yaf_Controller_Abstract
{

    private $config;

    public function init()
    {
        $this->config = Yaf_Application::app()->getConfig();
    }

    public function errorAction($exception)
    {
        try {
            \Logger::getLogger(str_replace('\\', '_', get_class($exception)))->logException($exception);
        } catch (Exception $e) {
            $exception = $e;
        }

        $string_msg = get_class($exception) . ": "
            . $exception->getMessage()
            . " in file "
            . $exception->getFile()
            . " at line "
            . $exception->getLine() . "\n"
            . $exception->getTraceAsString() . "\n";

        if ($this->getRequest()->isCli()) {
            echo $string_msg;
            goto end;
        }

        $this->getView()->setLayout(null);
        $this->getView()->setScriptPath($this->config->application->directory . "/views");

        switch ($exception->getCode()) {
            case YAF_ERR_AUTOLOAD_FAILED:
            case YAF_ERR_NOTFOUND_MODULE:
            case YAF_ERR_NOTFOUND_CONTROLLER:
            case YAF_ERR_NOTFOUND_ACTION:
                if (!$this->config->application->displayErrors) {
                    if ($this->getRequest()->isXmlHttpRequest()) {
                        $this->responseMessage("404 Page Not Found");
                    } else {
                        $this->redirect('/error/notFound');
                    }
                    goto end;
                }
                break;
            default:
                $_uri = sprintf("%s-%s-%s", $this->getRequest()->getModuleName(), $this->getRequest()->getControllerName(), $this->getRequest()->getActionName());
                // \StatsD::count(sprintf("log.exception.%s", $_uri), 1);
                // send_http_status(500);
                break;
        }

        /* if errors are enabled show the full trace */
        if ($this->config->application->displayErrors) {
            if ($this->getRequest()->isXmlHttpRequest()) {
                $this->responseMessage($string_msg);
                goto end;
            }

            $params = $this->getRequest()->getParams();
            unset($params['exception']);
            $assign = [
                "e"              => $exception,
                "e_class"        => get_class($exception),
                "e_string_trace" => $exception->getTraceAsString(),
                "params"         => [] + $params + $this->getRequest()->getQuery() + $this->getRequest()->getPost()
            ];
            $this->display("exception", $assign);
        } else {
            if ($this->getRequest()->isXmlHttpRequest()) {
                $this->responseMessage("service error.");
                goto end;
            }

            $this->display("error");
        }

        end:
        exit;
    }

    /**
     * 返回错误信息
     * @param $message
     */
    private function responseMessage($message)
    {
        echo $this->getRequest()->isPost() ? json_encode([
            'code'    => ErrorCode::SYS_FAILED,
            'message' => $message,
        ]) : $message;
    }

    /**
     * 页面未找到
     */
    public function notFoundAction()
    {
        $this->display('notfound');
    }

    /**
     * 无权限访问
     */
    public function accessDeniedAction()
    {
        $this->display('accessdenied');
    }

}
