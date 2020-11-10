<?php

namespace Db;

/**
 * Redis管理器
 *
 * @author kuangzhiqiang
 *
 */
class Redis
{

    private $_name;

    /**
     * @var \Redis
     */
    private $_redis;

    private static $_instances;

    private static $_connect_timeout = 3;

    private $_stats = false;

    private function __construct(string $name)
    {
        $config = \Yaf_Application::app()->getConfig()->db->redis;
        if (!isset($config->$name)) {
            throw new \Exception("not found redis_config for {$name}", E_ERROR);
        }

        $db_config    = $config->$name;
        $this->_name  = $name;
        $this->_redis = $this->getConnect($db_config);
        $this->_stats = !empty(\Yaf_Application::app()->app()->getConfig()->stats->redis);
    }

    /**
     * @param \Yaf_Config_Ini $_config
     * @return \Redis
     */
    private function getConnect(\Yaf_Config_Ini $_config)
    {
        $redis = new \Redis();
        $redis->connect($_config->host, $_config->port, self::$_connect_timeout);//设置option
        if (!empty($_config->prefix)) {
            $redis->setOption(\Redis::OPT_PREFIX, $_config->prefix);
        }
        switch ($_config->serializer) {
            case 'php':
                $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
                break;
            case 'igbinary':
                if (extension_loaded('igbinary')) {
                    $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_IGBINARY);
                }
                break;
            default:
                $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
                break;
        }
        if (isset($_config->db) && $_config->db !== '') {
            $redis->select($_config->db);
        }
        if (isset($_config->auth)) {
            $redis->auth($_config->auth);
        }
        return $redis;
    }

    /**
     * 取得Redis实例
     * @param string $name
     * @return \Redis
     * @throws \Exception
     */
    static public function getInstance($name = 'default')
    {
        if (!isset(self::$_instances[$name])) {
            // 连接redis
            self::$_instances[$name] = new static($name);
        }

        return self::$_instances[$name];
    }

    public function __call($name, $arguments)
    {
        if (!method_exists($this->_redis, $name)) {
            throw new \ErrorException("Call to undefined method Db\Redis::{$name}()");
        }

        // $_start_time = microtime(true);

        // $stat_key_total_prefix   = "db.redis.{$this->_name}";
        // $stat_key_command_prefix = "db.redis.{$this->_name}.method.{$name}";

        try {
            $result = $this->_redis->$name(...$arguments);

            if ($this->_stats) {
                // $_const_microsecond = intval(timeSince($_start_time, "us", true));
                // \StatsD::timing("{$stat_key_total_prefix}.timing", $_const_microsecond);
                // \StatsD::timing("{$stat_key_command_prefix}.timing", $_const_microsecond);

                // \StatsD::count("{$stat_key_total_prefix}.total", 1);
                // \StatsD::count("{$stat_key_command_prefix}.total", 1);
            }
        } catch (\Exception $e) {
            if ($this->_stats) {
                // \StatsD::count("{$stat_key_total_prefix}.failed", 1);
                // \StatsD::count("{$stat_key_command_prefix}.failed", 1);
            }
            throw $e;
        }

        return $result;
    }

}
