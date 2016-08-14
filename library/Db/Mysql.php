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
 * @version $Id: mysql.php, v 1.0 2015-3-14 0:08:43
 */
/**
 * Description of Db\mysql
 *
 * @author kuangzhiqiang
 */

namespace Db;

class Mysql
{

    /**
     * 连接数据库超时时间
     */
    const CONNECT_TIMEOUT = 1;

    /**
     * 慢查询时间
     */
    const SLOW_QUERY_TIME = 1;

    /**
     * 实例
     */
    private static $_instances = array();

    /**
     * 是否开启debug
     */
    private $_debug = false;

    /**
     * 数据库链接
     */
    private $_db = array();

    /**
     * 当前数据库链接
     * @var \PDO
     */
    private $_pdo = null;

    /**
     * 数据库配置
     */
    private $_db_config = null;

    /**
     * 当前连接的DB配置
     */
    private $_curr_db_conf = null;

    /**
     * 当前是否在事务中, 在事务中只支持操作主库
     */
    private $_is_trans = false;

    /**
     * 当前是否是写库动作
     */
    private $_is_write = false;

    /**
     * 持久化链接
     */
    private $_p_connect = false;

    /**
     * 默认字符集编码
     */
    private $_charset = 'utf8';

    /**
     * 操作结果
     * @var \PDOStatement
     */
    private $_pdo_statement;

    /**
     * 最后执行语句
     * @var string
     */
    private $_last_sql = null;

    /**
     * 构造函数
     *
     * @param array $db_config
     */
    function __construct($db_config)
    {
        $this->_db_config = $db_config;

        $config = \Yaf_Application::app()->app()->getConfig();
        if ($config->application->sqlDebug)
        {
            $this->setDebug(true);
        }
    }

    /**
     * 获取实例
     * @param string $id
     * @return static
     * @throws \Exception
     */
    static public function getInstance($id = 'default')
    {
        if (!isset(self::$_instances[$id]))
        {
            if (!extension_loaded('pdo_mysql'))
            {
                throw new \Exception('not found extension pdo_mysql', E_ERROR);
            }

            $config = \Yaf_Application::app()->getConfig()->db->mysql;

            if (!isset($config) || !isset($config->$id))
            {
                throw new \Exception("not found mysql_config for {$id}", E_ERROR);
            }
            $db_config = $config->$id;

            self::$_instances[$id] = new static($db_config);
        }
        return self::$_instances[$id];
    }

    /**
     * 链接数据库
     * @throws \Exception
     */
    private function connect()
    {
        $db_key = "db_";
        // 取得配置
        if (isset($this->_db_config->master))
        {
            // 事务中和增删改操作主库
            if ($this->_is_trans || $this->_is_write)
            {
                $db_key .= "master";
                $db_config = $this->_db_config->master;
            }
            else
            {
                $db_key .= "slave";
                $db_config = $this->_db_config->slave;
            }
        }
        else
        {
            $db_key .= "default";
            $db_config = $this->_db_config;
        }

        // 当前连接的数据库配置信息
        $this->_curr_db_conf = $db_config;

        // 链接
        if (!isset($this->_db[$db_key]))
        {
            $charset        = $db_config->charset ?: $this->_charset;
            $dsn            = "mysql:host={$db_config->host};port={$db_config->port};dbname={$db_config->dbname};charset={$charset};";
            $driver_options = array(
                \PDO::ATTR_EMULATE_PREPARES   => false,
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_PERSISTENT         => $this->_p_connect,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '{$charset}'",
            );

            $start_time = microtime(true);
            try
            {
                $this->_db[$db_key] = new \PDO($dsn, $db_config->username, $db_config->password, $driver_options);
            }
            catch (\PDOException $e)
            {
                \eYaf\Logger::getLogger()->log('Caught exception: ' . $e->getMessage());
                try
                {
                    $this->_db[$db_key] = new \PDO($dsn, $db_config->username, $db_config->password, $driver_options);
                }
                catch (\PDOException $e)
                {
                    throw $e;
                }
            }
            $runtime = microtime(true) - $start_time;
            if ($runtime > self::CONNECT_TIMEOUT)
            {
                \eYaf\Logger::getLogger()->log($runtime . '`MySQL connect slowly.' . "`{$dsn}");
            }
        }

        $this->_pdo = $this->_db[$db_key];
    }

    /**
     * 销毁链接
     */
    public function disconnect()
    {
        if (!empty($this->_db))
        {
            $this->_db  = array();
            $this->_pdo = null;
        }
    }

    /**
     * 持久化链接，需要在链接之前设置
     * @param bool $flag
     */
    public function pContent($flag = true)
    {
        $this->_p_connect = $flag;
    }

    /**
     * 执行语句
     * @param string $sql
     * @param array $parameters
     * @return \PDOStatement
     * @throws \Exception
     */
    public function execute($sql, array $parameters = array())
    {
        // 如果是写操作，一定走主库
        if (preg_match('/^\s*(SET|INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|TRUNCATE|LOAD DATA|COPY|ALTER|GRANT|REVOKE|LOCK|UNLOCK)\s+/i', $sql))
        {
            $this->_is_write = true;
        }

        $this->connect();
        $this->_is_write = false;

        $this->formatSqlData($sql, $parameters);

        $start_time = microtime(true);

        $this->_last_sql = $sql;
        try
        {
            $this->_pdo_statement = $this->_pdo->prepare($sql);
            $this->_pdo_statement->execute($parameters);
        }
        catch (\PDOException $e)
        {
            // 服务端断开时重连一次
            if (!empty($e->errorInfo[1]) && ($e->errorInfo[1] === 2006 || $e->errorInfo[1] === 2013))
            {
                $this->disconnect();
                // 事务中为保证事务完整性不进行重连
                if ($this->_is_trans)
                {
                    $this->rollback();
                    throw $e;
                }

                $this->connect();
                try
                {
                    $this->_pdo_statement = $this->_pdo->prepare($sql);
                    $this->_pdo_statement->execute($parameters);
                }
                catch (\PDOException $ex)
                {
                    $this->rollback();
                    throw $ex;
                }
            }
            else
            {
                $this->rollback();
                throw $e;
            }
        }

        if ($this->_pdo_statement->errorCode() !== '00000')
        {
            $errors  = $this->_pdo_statement->errorInfo();
            $message = "ERROR_NUMBER: {$errors[1]} ERROR_INFO: {$errors[2]} ERROR_SQL: {$sql} SQL_DATA: {" . json_encode($parameters) . "}";
            throw new \Exception($message, E_ERROR);
        }
        else
        {
            // 记录日志
            $runtime   = microtime(true) - $start_time;
            $query_log = $sql . ";\t" . json_encode($parameters) . "\t" . "host:{$this->_curr_db_conf->host},port:{$this->_curr_db_conf->port},dbname:{$this->_curr_db_conf->dbname}";
            if (true === $this->_debug)
            {
                \eYaf\Logger::getLogger('mysql_query')->logQuery($query_log, __CLASS__, $runtime);
            }

            // 慢查询日志
            if ($runtime > self::SLOW_QUERY_TIME)
            {
                \eYaf\Logger::getLogger('mysql_slow')->logQuery($query_log, __CLASS__, $runtime);
            }
        }

        return $this->_pdo_statement;
    }

    /**
     * 格式化SQL语句与传值，为function则直接使用返回的字符串操作
     * @param string $sql
     * @param array $parameters
     */
    private function formatSqlData(&$sql, array &$parameters)
    {
        foreach ($parameters as $k => $v)
        {
            if (is_object($v) && $v instanceof DbString)
            {
                $sql = str_replace($k, (string) $v, $sql);
                unset($parameters[$k]);
            }
        }
    }

    /**
     * 查询数据集
     * @param string $sql
     * @param array $parameters
     * @param int $mode
     * @return array
     * @throws \Exception
     */
    public function fetchAll($sql, array $parameters = array(), $mode = \PDO::FETCH_ASSOC)
    {
        $result = $this->execute($sql, $parameters);
        if ($result->rowCount() > 0)
        {
            $result->setFetchMode($mode);
        }
        return $result->fetchAll();
    }

    /**
     * 返回指定SQL结果的第一行
     * @param string $sql
     * @param array $parameters
     * @param int $mode
     * @return array
     * @throws \Exception
     */
    public function fetchRow($sql, array $parameters = array(), $mode = \PDO::FETCH_ASSOC)
    {
        $result = $this->execute($sql, $parameters);
        if ($result->rowCount() > 0)
        {
            $result->setFetchMode($mode);
        }
        return $result->fetch();
    }

    /**
     * 返回指定SQL结果的第一个字段的列
     * @param string $sql
     * @param array $parameters
     * @return array
     */
    public function fetchCol($sql, array $parameters = array())
    {
        $rs = $this->fetchAll($sql, $parameters);
        if (empty($rs) && !is_array($rs))
        {
            return $rs;
        }

        $data = array();
        foreach ($rs as $row)
        {
            $data[] = array_shift($row);
        }

        return $data;
    }

    /**
     * 返回指定SQL结果的指定列第一行的值
     * @param string $sql
     * @param array $parameters
     * @param int $column_number 第几列
     * @return string
     * @throws \Exception
     */
    public function fetchOne($sql, array $parameters = array(), $column_number = 0)
    {
        $result = $this->execute($sql, $parameters);
        return $result->fetchColumn($column_number);
    }

    /**
     * 对查询结果进行按字段分组
     * @param string $sql
     * @param array $parameters
     * @param array $key_fields 用于分组的key,多个代表多维
     * @param array $val_fields 分组的值
     * @param int $mode
     * @return array
     * @throws \Exception
     */
    public function fetchMap($sql, array $parameters = array(), array $key_fields = array(), array $val_fields = array(), $mode = \PDO::FETCH_ASSOC)
    {
        $result = $this->execute($sql, $parameters);
        if ($result->rowCount() > 0)
        {
            $result->setFetchMode($mode);
        }
        $val_fields = array_values($val_fields);

        $data = array();
        while ($row = $result->fetch())
        {
            $key_string = '';
            foreach ($key_fields as $key)
            {
                $key_string .= "[" . (isset($row[$key]) ? "'{$row[$key]}'" : '') . "]";
            }

            $val_data = array();
            if (empty($val_fields))
            {
                $val_data = $row;
            }
            elseif (count($val_fields) === 1)
            {
                $val_key  = $val_fields[0];
                $val_data = isset($row[$val_key]) ? $row[$val_key] : null;
            }
            else
            {
                foreach ($val_fields as $val_key)
                {
                    $val_data[$val_key] = isset($row[$val_key]) ? $row[$val_key] : null;
                }
            }

            eval("\$data{$key_string}=\$val_data;");
        }

        return $data;
    }

    /**
     * 新增数据
     * @param string $table
     * @param array $data
     */
    public function insert($table, array $data)
    {
        $s_list     = array();
        $parameters = array();
        foreach ($data as $k => $v)
        {
            $bind_key              = ":_{$k}_";
            $s_list[]              = "`{$k}`={$bind_key}";
            $parameters[$bind_key] = $v;
        }

        $sql = "INSERT INTO `{$table}` SET " . implode(',', $s_list);
        $this->execute($sql, $parameters);
    }

    /**
     * 替换数据
     * @param string $table
     * @param array $data
     */
    public function replace($table, array $data)
    {
        $s_list     = array();
        $parameters = array();
        foreach ($data as $k => $v)
        {
            $bind_key              = ":_{$k}_";
            $s_list[]              = "`{$k}`={$bind_key}";
            $parameters[$bind_key] = $v;
        }

        $sql = "REPLACE INTO `{$table}` SET " . implode(',', $s_list);
        $this->execute($sql, $parameters);
    }

    /**
     * 存在则不替换
     * @param string $table
     * @param array $data
     */
    public function insertIgnore($table, array $data)
    {
        $s_list     = array();
        $parameters = array();
        foreach ($data as $k => $v)
        {
            $bind_key              = ":_{$k}_";
            $s_list[]              = "`{$k}`={$bind_key}";
            $parameters[$bind_key] = $v;
        }

        $sql = "INSERT IGNORE INTO `{$table}` SET " . implode(',', $s_list);
        $this->execute($sql, $parameters);
    }

    /**
     * 忽略插入别名方法
     * @param string $table
     * @param array $data
     */
    public function ignoreInsert($table, array $data)
    {
        $this->insertIgnore($table, $data);
    }

    /**
     * 存在则更新
     * @param string $table
     * @param array $in_data 插入的数据
     * @param array $up_data 更新的数据
     */
    public function insertUpdate($table, array $in_data, array $up_data)
    {
        $in_list    = array();
        $up_list    = array();
        $parameters = array();
        foreach ($in_data as $k => $v)
        {
            $bind_key              = ":i_{$k}_";
            $in_list[]             = "`{$k}`={$bind_key}";
            $parameters[$bind_key] = $v;
        }
        foreach ($up_data as $k => $v)
        {
            $bind_key              = ":u_{$k}_";
            $up_list[]             = "`{$k}`={$bind_key}";
            $parameters[$bind_key] = $v;
        }

        $sql = "INSERT INTO `{$table}` SET " . implode(',', $in_list)
            . " ON DUPLICATE KEY UPDATE " . implode(',', $up_list);
        $this->execute($sql, $parameters);
    }

    /**
     * 批量插入
     * @param string $table
     * @param array $data 二维数组
     * @param array $fields 指定需要插入的值, 为空则使用$data中所有key
     * @param string $type insert|ignore|replace
     */
    public function insertMulti($table, array $data, array $fields = array(), $type = 'insert')
    {
        $fields     = $fields ?: array_keys(current($data));
        $values     = '';
        $parameters = array();
        $i          = 0;
        foreach ($data as $row)
        {
            $i++;
            $value = null;
            foreach ($fields as $key)
            {
                $bind_key              = ":_{$key}_{$i}_";
                $val                   = isset($row[$key]) ? $row[$key] : null;
                $parameters[$bind_key] = $val;
                $value .= "{$bind_key},";
            }
            $values .= '(' . trim($value, ',') . '),';
        }
        $fields = '`' . implode('`,`', $fields) . '`';
        $values = trim($values, ',');

        $sql_before = null;
        switch ($type)
        {
            case 'replace':
                $sql_before = 'REPLACE';
                break;

            case 'ignore':
                $sql_before = 'INSERT IGNORE';
                break;

            case 'insert':
            default:
                $sql_before = 'INSERT';
                break;
        }

        $sql = "{$sql_before} INTO `{$table}` ({$fields}) VALUES {$values}";
        $this->execute($sql, $parameters);
    }

    /**
     * 批量插入别名方法
     *
     * @param string $table
     * @param array $data
     * @param array $fields
     * @param string $type
     */
    public function multiInsert($table, array $data, array $fields = array(), $type = 'insert')
    {
        $this->insertMulti($table, $data, $fields, $type);
    }

    /**
     * 存在则更新(批量)
     * @param string $table
     * @param array $in_data 插入的数据(二维数组)
     * @param array $up_data 更新的数据(一维数组)
     * @param array $fields 指定需要插入的值, 为空则使用$in_data第一个中所有key
     */
    public function insertUpdateMulti($table, array $in_data, array $up_data, array $fields = array())
    {
        $fields     = $fields ?: array_keys(current($in_data));
        $values     = '';
        $parameters = array();
        $i          = 0;
        foreach ($in_data as $row)
        {
            $i++;
            $value = null;
            foreach ($fields as $key)
            {
                $bind_key              = ":i_{$key}_{$i}_";
                $val                   = isset($row[$key]) ? $row[$key] : null;
                $parameters[$bind_key] = $val;
                $value .= "{$bind_key},";
            }
            $values .= '(' . trim($value, ',') . '),';
        }
        $fields = '`' . implode('`,`', $fields) . '`';
        $values = trim($values, ',');

        $up_list = array();
        foreach ($up_data as $k => $v)
        {
            $bind_key              = ":u_{$k}_";
            $up_list[]             = "`{$k}`={$bind_key}";
            $parameters[$bind_key] = $v;
        }

        $sql = "INSERT INTO `{$table}` ({$fields}) VALUES {$values}"
            . " ON DUPLICATE KEY UPDATE " . implode(',', $up_list);
        $this->execute($sql, $parameters);
    }

    /**
     * 存在则更新(批量)别名方法
     * @param string $table
     * @param array $in_data 插入的数据(二维数组)
     * @param array $up_data 更新的数据(一维数组)
     * @param array $fields 指定需要插入的值, 为空则使用$in_data第一个中所有key
     */
    public function multiInsertUpdate($table, array $in_data, array $up_data, array $fields = array())
    {
        $this->insertUpdateMulti($table, $in_data, $up_data, $fields);
    }

    /**
     * 更新
     * @param string $table
     * @param array $data
     * @param string $condition
     * @param array $cond_parameters
     */
    public function update($table, array $data, $condition, array $cond_parameters = array())
    {
        $s_list     = array();
        $parameters = array();
        foreach ($data as $k => $v)
        {
            $bind_key              = ":_{$k}_";
            $s_list[]              = "`{$k}`={$bind_key}";
            $parameters[$bind_key] = $v;
        }

        // 如果指定了条件，则跟上条件串
        if (!empty($condition))
        {
            $condition = " WHERE " . $condition;
        }

        $sql = "UPDATE `{$table}` SET " . implode(',', $s_list) . $condition;
        $this->execute($sql, array_merge($parameters, $cond_parameters));
    }

    /**
     * 更新多行记录
     * @param string $table
     * @param array $data 待更新的二维数组
     * @param string $index_field 用于判断的字段名，本字段需要在$data第二维中存在
     * @param string $condition
     * @param array $cond_parameters
     * @throws \Exception
     */
    public function updateMulti($table, array $data, $index_field, $condition, array $cond_parameters = array())
    {
        $s_list     = array();
        $parameters = array();
        $field_case = array();
        $i          = 0;
        foreach ($data as $row)
        {
            $i++;
            $row_keys = array_keys($row);
            if (!isset($row[$index_field]))
            {
                throw new \Exception("未在数据中找到更新的条件字段 {$index_field}", E_ERROR);
            }

            foreach ($row_keys as $key)
            {
                if ($key === $index_field)
                {
                    continue;
                }

                $bind_index_key              = ":_index_field_{$i}";
                $bind_value_key              = ":_{$key}_{$i}";
                $parameters[$bind_index_key] = $row[$index_field];
                $parameters[$bind_value_key] = $row[$key];

                $field_case[$key][] = " WHEN `{$index_field}` = {$bind_index_key} THEN {$bind_value_key} ";
            }
        }

        foreach ($field_case as $field => $v)
        {
            $case     = "`{$field}`=CASE " . implode($v, ' ') . " ELSE `{$field}` END";
            $s_list[] = $case;
        }

        // 如果指定了条件，则跟上条件串
        if (!empty($condition))
        {
            $condition = " WHERE " . $condition;
        }

        $sql = "UPDATE `{$table}` SET " . implode(',', $s_list) . $condition;
        $this->execute($sql, array_merge($parameters, $cond_parameters));
    }

    /**
     * 删除
     * @param string $table
     * @param string $condition
     * @param array $cond_parameters
     */
    public function delete($table, $condition, array $cond_parameters = array())
    {
        if (!empty($condition))
        {
            $condition = " WHERE " . $condition;
        }

        $sql = "DELETE FROM {$table} {$condition}";
        $this->execute($sql, $cond_parameters);
    }

    /**
     * 最后插入的ID
     * @return int
     */
    public function lastInsertId()
    {
        return !is_null($this->_pdo) ? $this->_pdo->lastInsertId() : 0;
    }

    /**
     * 最后一条执行的sql
     * @return string
     */
    public function lastSql()
    {
        return $this->_last_sql;
    }

    /**
     * 返回影响行数
     * @return int
     */
    public function rowCount()
    {
        return $this->_pdo_statement->rowCount();
    }

    /**
     * 开始事务
     * @return boolean
     */
    public function begin()
    {
        $this->_is_trans = true;
        $this->connect();
        if (!$this->_pdo->inTransaction())
        {
            return $this->_pdo->beginTransaction();
        }
        return false;
    }

    /**
     * 提交事务
     * @return boolean
     */
    public function commit()
    {
        $this->_is_trans = false;
        if (!is_null($this->_pdo) && $this->_pdo->inTransaction())
        {
            return $this->_pdo->commit();
        }
        return false;
    }

    /**
     * 回滚事务
     * @return boolean
     */
    public function rollback()
    {
        $this->_is_trans = false;
        if (!is_null($this->_pdo) && $this->_pdo->inTransaction())
        {
            return $this->_pdo->rollback();
        }
        return false;
    }

    /**
     * 设置debug模式
     * @param Boolean $debug_mode true 开启debug; false关闭debug
     */
    private function setDebug($debug_mode)
    {
        $this->_debug = (true === $debug_mode) ? true : false;
    }

}
