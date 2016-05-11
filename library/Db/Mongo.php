<?php

namespace Db;

/**
 * Mongo管理器
 *
 * @author kuangzhiqiang
 *
 */

class Mongo
{

    private static $_connects;

    /**
     * 取得Mongo实例
     * @param string $id
     * @return \MongoClient
     * @throws \Exception
     */
    static public function getInstance($id = 'default')
    {
        if (!isset(self::$_connects[$id]))
        {
            $config = \Yaf_Application::app()->getConfig()->db->mongo;
            if (!isset($config->$id))
            {
                throw new \Exception("not found mongo_config for {$id}", E_ERROR);
            }
            $db_config = $config->$id;

            $options = array();
            if (strstr($db_config->hosts, ',') !== false)
            {
                $options['replicaSet'] = 'myReplSet';
            }

            $con_str = 'mongodb://';
            if ((string) $db_config->username !== '' && (string) $db_config->password !== '')
            {
                $con_str .= $db_config->username . ':' . $db_config->password . '@';
            }
            $con_str .= $db_config->hosts;
            if ((string) $db_config->dbname !== '')
            {
                $con_str .= '/' . $db_config->dbname;
            }
            if ((string) $db_config->options !== '')
            {
                $con_str .= '?' . $db_config->options;
            }

            // 连接 mongo
            $mongo                = new \MongoClient($con_str, $options);
            self::$_connects[$id] = $mongo;
        }

        return self::$_connects[$id];
    }
}
