<?php

namespace eYaf;

class Logger extends \SplFileObject
{

    const RED       = '1;31m';
    const GREEN     = '1;32m';
    const YELLOW    = '1;33m';
    const BLUE      = '1;34m';
    const PURPLE    = '1;35m';
    const CYAN      = '1;36m';
    const WHITE     = '1;37m';
    const COLOR_SEQ = "\033[";
    const RESET_SEQ = "\033[0m";
    const BOLD_SEQ  = "\033[1m";

    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

    private static $log_id;
    private static $start_time;
    private static $memory;
    private static $logger_instance = array();

    public function __construct($filename = null, $open_mode = "a")
    {
        parent::__construct($filename, $open_mode);
        if (!self::$log_id)
        {
            self::$log_id = uniqid();
        }
    }

    /**
     * getLogger
     * @param string $env
     * @param string $open_mode
     * @return \eYaf\Logger
     */
    public static function getLogger($env = null, $open_mode = "a")
    {
        $env = $env ?: YAF_ENVIRON;
        if (!isset(static::$logger_instance[$env]) || !(static::$logger_instance[$env] instanceof self))
        {
            $log_path = \Yaf_Application::app()->getConfig()->application->log;
            mk_dir($log_path);
            $filename                      = $log_path . "/" . $env . '.log';
            static::$logger_instance[$env] = new static($filename, $open_mode);
        }
        return static::$logger_instance[$env];
    }

    public function startLogging()
    {
        self::$start_time = microtime(true);
        self::$memory     = memory_get_usage(true);
        $buffer           = self::colorString("Started at : [" . date('Y-m-d H:i:s', time()) . "]", self::GREEN);
        $this->log($buffer);
    }

    public function stopLogging()
    {
        $buffer = self::colorString("Completed in "
            . number_format((microtime(true) - self::$start_time) * 1000, 0)
            . "ms | "
            . "Mem Usage: ("
            . number_format((memory_get_usage(true) - self::$memory) / (1024), 0, ",", ".")
            . " kb)", self::GREEN);
        $this->log($buffer);
    }

    public function log($string)
    {
        $traces = debug_backtrace(false);
        array_shift($traces);
        foreach ($traces as $row)
        {
            if (isset($row['file']))
            {
                $string .= "\t[{$row['file']}:{$row['line']}]";
                break;
            }
        }
        $buffer = "[" . self::$log_id . "]\t"
            . self::colorString("[" . date('Y-m-d H:i:s') . "]\t", self::BLUE)
            . $string . "\n";
        $this->fwrite($buffer);
    }

    /**
     * 系统不可用
     * @param string $message
     * @param array $context
     */
    public function emergency($message, array $context = array())
    {
        $buffer = self::colorString("[" . self::EMERGENCY . "]\t" . $this->interpolate($message, $context), self::PURPLE);
        $this->log($buffer);
    }

    /**
     * **必须**立刻采取行动
     * 例如：在整个网站都垮掉了、数据库不可用了或者其他的情况下，**应该**发送一条警报短信把你叫醒。
     * @param string $message
     * @param array $context
     */
    public function alert($message, array $context = array())
    {
        $buffer = self::colorString("[" . self::ALERT . "]\t" . $this->interpolate($message, $context), self::PURPLE);
        $this->log($buffer);
    }

    /**
     * 紧急情况
     * 例如：程序组件不可用或者出现非预期的异常。
     * @param string $message
     * @param array $context
     */
    public function critical($message, array $context = array())
    {
        $buffer = self::colorString("[" . self::CRITICAL . "]\t" . $this->interpolate($message, $context), self::PURPLE);
        $this->log($buffer);
    }

    /**
     * 运行时出现的错误，不需要立刻采取行动，但必须记录下来以备检测。
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = array())
    {
        $buffer = self::colorString("[" . self::ERROR . "]\t" . $this->interpolate($message, $context), self::RED);
        $this->log($buffer);
    }

    /**
     * 出现非错误性的异常。
     * 例如：使用了被弃用的API、错误地使用了API或者非预想的不必要错误。
     * @param string $message
     * @param array $context
     */
    public function warning($message, array $context = array())
    {
        $buffer = self::colorString("[" . self::WARNING . "]\t" . $this->interpolate($message, $context), self::YELLOW);
        $this->log($buffer);
    }

    /**
     * 一般性重要的事件。
     * @param string $message
     * @param array $context
     */
    public function notice($message, array $context = array())
    {
        $buffer = self::colorString("[" . self::NOTICE . "]\t" . $this->interpolate($message, $context), self::WHITE);
        $this->log($buffer);
    }

    /**
     * 重要事件
     * 例如：用户登录和SQL记录。
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = array())
    {
        $buffer = self::colorString("[" . self::INFO . "]\t" . $this->interpolate($message, $context), self::CYAN);
        $this->log($buffer);
    }

    /**
     * debug 详情
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = array())
    {
        $buffer = self::colorString("[" . self::DEBUG . "]\t" . $this->interpolate($message, $context), self::CYAN);
        $this->log($buffer);
    }

    public function logQuery($query, $class_name = null, $parse_time = 0, $action = 'Load')
    {
        $class_name = $class_name ?: 'Sql';
        $buffer     = self::colorString("{$class_name} {$action} ("
            . number_format($parse_time * 1000, '4')
            . "ms)  ", self::PURPLE)
            . self::colorString($query, self::CYAN);
        $this->log($buffer);
    }

    public function logRequest(\Yaf_Request_Abstract $request)
    {
        $log    = "Processing "
            . $request->getModuleName() . "\\"
            . $request->getControllerName()
            . "Controller#"
            . $request->getActionName()
            . " (for {$request->getServer('REMOTE_ADDR')}"
            . " at " . date('Y-m-d H:i:s') . ")"
            . " [{$request->getMethod()}]" . "\n";
        $params = array() + $request->getParams() + $request->getQuery() + $request->getPost() + $request->getFiles();
        $log .= "Parameters: " . trim(print_r($params, true));

        $this->log($log);
    }

    public function logException($exception)
    {
        $log = self::colorString(get_class($exception) . ": "
            . $exception->getMessage()
            . " in file "
            . $exception->getFile()
            . " at line "
            . $exception->getLine(), self::RED) . "\n"
            . $exception->getTraceAsString();

        $this->log($log);
    }

    /**
     * 用上下文信息替换记录信息中的占位符
     * @param string $message
     * @param array $context
     * @return string
     */
    function interpolate($message, array $context = array())
    {
        // 构建一个花括号包含的键名的替换数组
        $replace = array();
        foreach ($context as $key => $val)
        {
            $replace['{' . $key . '}'] = $val;
        }

        // 替换记录信息中的占位符，最后返回修改后的记录信息。
        return strtr($message, $replace);
    }

    function colorString($string, $color = null)
    {
        if ($color !== null)
        {
            return self::COLOR_SEQ . $color . $string . self::RESET_SEQ;
        }
        return $string;
    }
}
