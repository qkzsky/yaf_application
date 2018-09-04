<?php

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

    const DEBUG     = 0;
    const INFO      = 1;
    const NOTICE    = 2;
    const WARNING   = 3;
    const ERROR     = 4;
    const CRITICAL  = 5;
    const ALERT     = 6;
    const EMERGENCY = 7;

    const LEVEL_STRING = [
        self::DEBUG     => 'DEBUG',
        self::INFO      => 'INFO',
        self::NOTICE    => 'NOTICE',
        self::WARNING   => 'WARNING',
        self::ERROR     => 'ERROR',
        self::CRITICAL  => 'CRITICAL',
        self::ALERT     => 'ALERT',
        self::EMERGENCY => 'EMERGENCY',
    ];

    private static $start_time;
    private static $log_level = 0;
    private static $log_color = false;
    private static $service_name;
    private static $host_name;

    private static $log_id;
    private static $memory;
    private static $logger_instance = array();

    public function __construct($filename = null, $open_mode = "a")
    {
        parent::__construct($filename, $open_mode);
        if (!self::$log_id) {
            self::$log_id = uniqid();
        }
    }

    /**
     * getLogger
     * @param string $env
     * @param string $open_mode
     * @return \Logger
     */
    public static function getLogger($env = null, $open_mode = "a")
    {
        $env = $env ?: YAF_ENVIRON;
        if (!isset(static::$logger_instance[$env]) || !(static::$logger_instance[$env] instanceof self)) {
            $log_config = \Yaf_Application::app()->getConfig()->log;
            $log_path   = $log_config->path ?? ".";
            $filename   = $log_path . "/" . $env . '.log';
            mk_dir(dirname($filename));
            static::$logger_instance[$env] = new static($filename, $open_mode);

            static::$log_level    = $log_config->level ?? 0;
            static::$log_color    = $log_config->color ?? false;
            static::$service_name = $log_config->service_name ?? null;
            static::$host_name    = $_SERVER['SERVER_ADDR'] ?? $_SERVER['HOSTNAME'] ?? null;
        }
        return static::$logger_instance[$env];
    }

    public function startLogging()
    {
        self::$start_time = microtime(true);
        self::$memory     = memory_get_usage(true);
        $buffer           = self::colorString("Started at : [" . date('Y-m-d H:i:s', time()) . "]", self::GREEN);
        $this->write($buffer);
    }

    public function stopLogging()
    {
        $buffer = self::colorString("Completed in "
            . number_format((microtime(true) - self::$start_time) * 1000, 0)
            . "ms | "
            . "Mem Usage: ("
            . number_format((memory_get_usage(true) - self::$memory) / (1024), 0, ",", ".")
            . " kb)", self::GREEN);
        $this->write($buffer);
    }

    public function log($string)
    {
        $this->write($string);
    }

    private function write(string $log, int $level = null)
    {
        $log_level = "";
        if (!is_null($level) && isset(self::LEVEL_STRING[$level])) {
            if ($level < self::$log_level) {
                return;
            }
            $level_string = self::LEVEL_STRING[$level];
            switch ($level) {
                case self::DEBUG:
                case self::INFO:
                case self::NOTICE:
                    $level_color = self::CYAN;
                    break;
                case self::WARNING:
                    $level_color = self::YELLOW;
                    break;
                case self::ERROR:
                    $level_color = self::RED;
                    break;
                default:
                    $level_color = self::PURPLE;
                    break;
            }

            $log_level = self::colorString($level_string, $level_color);
        }

        $traces = debug_backtrace(false);
        array_shift($traces);
        foreach ($traces as $row) {
            if (isset($row['file'])) {
                $_trace_file = $row['file'];
                $_trace_line = $row['line'];
                break;
            }
        }

        $buffer_fields = [
            date('Y-m-d H:i:s'),
            self::$service_name,
            self::$host_name,
            $log_level,
            self::$log_id,
        ];
        $log_content   = json_encode([
            "content" => $log,
            "file"    => $_trace_file ?? null,
            "line"    => $_trace_line ?? null
        ]);

        $this->fwrite(implode(" ", array_map(function($v) {
                if (is_null($v) || $v === "") {
                    return null;
                }
                return "[{$v}]";
            }, $buffer_fields)) . " {$log_content}\n");
    }

    /**
     * 系统不可用
     * @param string $message
     * @param array $context
     */
    public function emergency($message, array $context = array())
    {
        $buffer = $this->interpolate($message, $context);
        $this->write($buffer, self::EMERGENCY);
    }

    /**
     * **必须**立刻采取行动
     * 例如：在整个网站都垮掉了、数据库不可用了或者其他的情况下，**应该**发送一条警报短信把你叫醒。
     * @param string $message
     * @param array $context
     */
    public function alert($message, array $context = array())
    {
        $buffer = $this->interpolate($message, $context);
        $this->write($buffer, self::ALERT);
    }

    /**
     * 紧急情况
     * 例如：程序组件不可用或者出现非预期的异常。
     * @param string $message
     * @param array $context
     */
    public function critical($message, array $context = array())
    {
        $buffer = $this->interpolate($message, $context);
        $this->write($buffer, self::CRITICAL);
    }

    /**
     * 运行时出现的错误，不需要立刻采取行动，但必须记录下来以备检测。
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = array())
    {
        $buffer = $this->interpolate($message, $context);
        $this->write($buffer, self::ERROR);
    }

    /**
     * 出现非错误性的异常。
     * 例如：使用了被弃用的API、错误地使用了API或者非预想的不必要错误。
     * @param string $message
     * @param array $context
     */
    public function warning($message, array $context = array())
    {
        $buffer = $this->interpolate($message, $context);
        $this->write($buffer, self::WARNING);
    }

    /**
     * 一般性重要的事件。
     * @param string $message
     * @param array $context
     */
    public function notice($message, array $context = array())
    {
        $buffer = $this->interpolate($message, $context);
        $this->write($buffer, self::NOTICE);
    }

    /**
     * 重要事件
     * 例如：用户登录和SQL记录。
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = array())
    {
        $buffer = $this->interpolate($message, $context);
        $this->write($buffer, self::INFO);
    }

    /**
     * debug 详情
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = array())
    {
        $buffer = $this->interpolate($message, $context);
        $this->write($buffer, self::DEBUG);
    }

    public function logQuery($query, $class_name = null, $parse_time = 0, $action = 'Load')
    {
        $class_name = $class_name ?: 'Sql';
        $buffer     = self::colorString("{$class_name} {$action} ("
                . number_format($parse_time * 1000, '4')
                . "ms)  ", self::PURPLE)
            . self::colorString($query, self::CYAN);
        $this->write($buffer);
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
            . " [{$request->getMethod()}]" . "\t";
        $params = array() + $request->getParams() + $request->getQuery() + $request->getPost() + $request->getFiles();
        $log    .= json_encode($params);

        $this->write($log);
    }

    public function logException($exception)
    {
        $log = self::colorString(get_class($exception) . ": "
                . $exception->getMessage()
                . " in file "
                . $exception->getFile()
                . " at line "
                . $exception->getLine(), self::RED)
            . "\t"
            . json_encode($exception->getTrace());

        $this->write($log);
    }

    /**
     * 用上下文信息替换记录信息中的占位符
     * @param string $message
     * @param array $context
     * @return string
     */
    private function interpolate($message, array $context = array())
    {
        // 构建一个花括号包含的键名的替换数组
        $replace = array();
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = is_array($val) ? json_encode($val) : $val;
        }

        // 替换记录信息中的占位符，最后返回修改后的记录信息。
        return strtr($message, $replace);
    }

    private function colorString($string, $color = null)
    {
        if ($color !== null && self::$log_color) {
            return self::COLOR_SEQ . $color . $string . self::RESET_SEQ;
        }
        return $string;
    }
}
