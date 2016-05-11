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
 * @version $Id: Cookie.php, v 1.0 2015-3-16 11:11:07
 */

/**
 * Description of Cookie
 *
 * @author kuangzhiqiang
 */
class Cookie
{
    // 前缀
    private $_prefix = null;
    // 保存时间
    private $_expire = null;
    // 保存路径
    private $_path = null;
    // 有效域名
    private $_domain = null;

    public function __construct()
    {
        $config        = Yaf_Application::app()->getConfig()->cookie;
        $this->_prefix = isset($config->prefix) ? $config->prefix : null;
        $this->_expire = isset($config->expire) ? (int)$config->expire : 0;
        $this->_path   = isset($config->path) ? $config->path : null;
        $this->_domain = isset($config->domain) ? $config->domain : null;
    }

    /**
     * 获取Cookie实例
     * @return static
     */
    static public function getInstance()
    {
        return new static();
    }

    /**
     * 设置前缀
     * @param string $prefix
     * @return \Cookie
     */
    public function setPrefix($prefix)
    {
        $this->_prefix = (string)$prefix;
        return $this;
    }

    /**
     * 设置过期时间
     * @param string $expire
     * @return \Cookie
     */
    public function setExpire($expire)
    {
        $this->_expire = (int)$expire;
        return $this;
    }

    /**
     * 设置路径
     * @param string $path
     * @return \Cookie
     */
    public function setPath($path)
    {
        $this->_path = (string)$path;
        return $this;
    }

    /**
     * 设置有效域名
     * @param string $domain
     * @return \Cookie
     */
    public function setDomain($domain)
    {
        $this->_domain = (string)$domain;
        return $this;
    }

    /**
     * 获取值
     * @param string $name
     * @return object
     */
    public function get($name = null)
    {
        if ($name === null)
        {
            return $_COOKIE;
        }
        return isset($_COOKIE[$this->_prefix . $name]) ? $_COOKIE[$this->_prefix . $name] : null;
    }

    public function set($name, $value)
    {
        $_expire = $this->_expire ? time() + $this->_expire : 0;
        setcookie($this->_prefix . $name, $value, $_expire, $this->_path, $this->_domain);
    }

    /**
     * 删除
     * @param string $name
     */
    public function del($name)
    {
        if (isset($_COOKIE[$this->_prefix . $name]))
        {
            setcookie($this->_prefix . $name, null, time() - 3600, $this->_path, $this->_domain);
            unset($_COOKIE[$name]);
        }
    }

    /**
     * 清理
     */
    public function clear()
    {
        if (empty($_COOKIE))
        {
            return;
        }

        foreach ($_COOKIE as $key => $val)
        {
            // 删除config设置的指定前缀
            if ($this->_prefix === null || 0 === stripos($key, $this->_prefix))
            {
                setcookie($key, null, time() - 3600, $this->_path, $this->_domain);
                unset($_COOKIE[$key]);
            }
        }
    }
}
