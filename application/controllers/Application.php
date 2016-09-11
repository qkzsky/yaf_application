<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * The main application controller.
 *
 * All application controllers may inherit from this controller.
 * This controller uses Layout class (@see lib/Layout.php)
 */
class ApplicationController extends Yaf_Controller_Abstract
{

    /**
     * The name of layout file.
     *
     * The name of layout file to be used for this controller ommiting extension.
     * Layout class will use extension from application config ini.
     *
     * @var string
     */
    protected $layout;

    /**
     * The session instance.
     * Yaf_Session instance to be used for this application.
     * @var Yaf_Session
     */
    protected $session;

    /**
     * Initialize layout and session.
     *
     * In this method can be initialized anything that could be usefull for
     * the controller.
     *
     * @return void
     */
    public function init()
    {
        //Set session.
        if (!$this->getRequest()->isCli())
        {
            $session_conf = \Yaf_Application::app()->getConfig()->session;
            if (!empty($session_conf->save_handler) && !empty($session_conf->save_path))
            {
                if (!session_id())
                {
                    ini_set("session.save_handler", $session_conf->save_handler);
                    ini_set("session.save_path", $session_conf->save_path);
                }
            }
            $this->session = Yaf_Session::getInstance();

            // Assign session to views too.
            $this->getView()->session = $this->session;
        }

        // Set the layout.
        $this->getView()->setLayout($this->layout);
    }

    /**
     * When assign a public property to controller, this property will be
     * available to action view template too.
     *
     * @param string $name the name of the property
     * @param mixed $value the value of the property
     *
     * @return void
     */
    public function __set($name, $value)
    {
        $this->$name = $value;
        $this->getView()->assignRef($name, $this->$name);
    }

    /**
     * 返回文本数据
     * @param string|object $content
     */
    public function responseText($content)
    {
        $this->getResponse()->setBody($content);
    }

    /**
     * 返回分页数据
     * @param $total
     * @param array $items
     */
    public function responsePageData($total, array $items)
    {
        $this->getResponse()->setBody(json_encode([
            'total' => $total,
            'data'  => $items,
        ]));
    }

    /**
     * 返回JSON数据
     * @param int $code
     * @param string $msg
     * @param string|object $content
     */
    public function responseJson($code, $msg, $content = '')
    {
        $this->getResponse()->setBody(json_encode([
            'code'    => $code,
            'message' => $msg,
            'content' => $content
        ]));
    }

    public function redirectNotFound()
    {
        $this->redirect('/error/notFound');
    }

    public function redirectAccessDenied()
    {
        $this->redirect('/error/accessDenied');
    }

}
