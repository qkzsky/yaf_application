<?php

/**
 * Class BaseCliController
 */
class BaseCliController extends ApplicationController
{

    protected $fp;

    /**
     * @var \Logger
     */
    protected $logger;

    public function init()
    {
        parent::init();

        // 非命令行访问, 跳转至404页面
        if (!$this->getRequest()->isCli())
        {
            $this->redirectNotFound();
            exit;
        }

        // 同一进程防止重复启动
        if (!$this->lock())
        {
            exit;
        }
    }

    /**
     * 输出信息
     * @param $message
     */
    protected function console($message)
    {
        echo $message . "\n";
    }

    protected function getLockFile()
    {
        return sprintf('/tmp/%s_%s_%s.lock', $this->getModuleName(), $this->getRequest()->getControllerName(), $this->getRequest()->getActionName());
    }

    /**
     * 对运行方法进行锁文件操作
     *
     * @return  bool
     */
    protected function lock()
    {
        $lock_file = $this->getLockFile();
        clearstatcache();

        $this->fp = fopen($lock_file, 'a');

        // 独享锁
        if (!flock($this->fp, LOCK_EX | LOCK_NB))
        {
            return false;
        }
        return true;
    }

    /**
     * 析构方法
     */
    public function __destruct()
    {
        if (!empty($this->fp))
        {
            fclose($this->fp);
            unlink($this->getLockFile());
        }
    }
}
