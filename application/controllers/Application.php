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
     * A Yaf_Config_Ini object that contains application configuration data.
     *
     * @var Yaf_Config_Ini
     */
    private $config;

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
        // Assign application config file to this controller
        $this->config            = Yaf_Application::app()->getConfig();
        // Assign config file to views
        $this->getView()->config = $this->config;

        //Set session.
        if (!$this->getRequest()->isCli())
        {
            if (!empty($this->config->session->save_handler) && !empty($this->config->session->save_path))
            {
                if (!session_id())
                {
                    ini_set("session.save_handler", $this->config->session->save_handler);
                    ini_set("session.save_path", $this->config->session->save_path);
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
     * @param string $name  the name of the property
     * @param mixed  $value the value of the property
     *
     * @return void
     */
    public function __set($name, $value)
    {
        $this->$name = $value;
        $this->getView()->assignRef($name, $this->$name);
    }

    public function getConfig()
    {
        return $this->config;
    }

    /**
     * 返回JSON格式数据
     * @param int $code
     * @param string $msg
     * @param string|object $content
     */
    public function responseJson($code, $msg, $content = '')
    {
        $this->getResponse()->setBody(json_encode(array(
            'code'    => $code,
            'message' => $msg,
            'content' => $content
        )));
    }

    public function redirectNotFound()
    {
        $this->redirect('/error/notFound');
        exit;
    }

    public function redirectAccessDenied()
    {
        $this->redirect('/error/accessDenied');
        exit;
    }

}
