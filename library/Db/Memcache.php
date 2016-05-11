<?php

namespace Db;

/**
 * Memcache管理器
 *
 * @author kuangzhiqiang
 *
 */
class Memcache
{

    private static $_connects;

    /**
     * 取得Memcache实例
     * @param string $id
     * @return \Memcache
     * @throws \Exception
     */
    static public function getInstance($id = 'default')
    {
        if (!isset(self::$_connects[$id]))
        {
            $config = \Yaf_Application::app()->getConfig()->db->memcache;
            if (!isset($config->$id))
            {
                throw new \Exception("not found memcache_config for {$id}", E_ERROR);
            }
            $db_config = $config->$id;

            // 连接 memcache
            $memcache = new \Memcache();
            $memcache->addServer($db_config->host, $db_config->port);
            self::$_connects[$id] = $memcache;
        }

        return self::$_connects[$id];
    }

}
