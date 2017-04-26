<?php

class HttpClient
{

    /**
     * 连接超时时间，默认 2 秒
     */
    private $_connect_timeout_ms = 2000;

    /**
     * 超时时间，默认 3 秒
     */
    private $_timeout_ms = 3000;

    /**
     * 请求的url地址
     * @var string
     */
    private $_request_url;

    /**
     * 请求的port端口
     * @var int
     */
    private $_request_port;

    /**
     * 请求的cookie
     * @var string
     */
    private $_request_cookie;

    /**
     * 请求内容，请将请求的内容以 key=value&key2=value2 的方式提供
     * @var string
     */
    private $_request_data;

    /**
     * 请求方法，默认是POST 方法
     * @var string
     */
    private $_method = 'GET';

    /**
     * 证书文件
     */
    private $_cert_file;

    /**
     * 证书密码
     * @var string
     */
    private $_cert_passwd;

    /**
     * 证书类型PEM
     * @var string
     */
    private $_cert_type = 'PEM';

    /**
     * CA文件
     * @var string
     */
    private $_ca_file;

    /**
     * 错误码
     * @var
     */
    private $_errno;

    /**
     * 错误信息
     * @var string
     */
    private $_error;

    /**
     * HTTP HEADER
     * @var array
     */
    private $_headers = array();

    /**
     * UserAgent 信息
     * @var string
     */
    private $_user_agent;

    /**
     * http状态码
     * @var int
     */
    private $_response_code = 0;

    /**
     * 应答头
     * @var string
     */
    private $_response_header;

    /**
     * 应答内容
     * @var string
     */
    private $_response_body;

    /**
     * http 日志名
     * @var string
     */
    private $_log_name = 'http_slow_request';

    /**
     * curl 句柄资源信息
     * @var array
     */
    private $_curl_info = array();

    /**
     * 构造方法
     */
    /**
     * HttpClient constructor.
     * @param string $url 请求地址
     * @param string $method 请求方式 get|post
     * @param int $timeout 超时时间, 单位毫秒, 默认3秒
     */
    public function __construct($url = '', $method = 'GET', $timeout = 3000)
    {
        $this->setRequestUrl($url);
        $this->setMethod($method);
        $this->setTimeout($timeout);
    }

    /**
     * 设置请求地址
     * @param string $url
     * @return bool
     */
    public function setRequestUrl($url)
    {
        $this->_request_url = $url;
        return true;
    }

    /**
     * 设置请求端口
     * @param int $port
     * @return bool
     */
    public function setRequestPort($port)
    {
        $this->_request_port = (int) $port;
        return true;
    }

    /**
     * 设置请求Cookie
     * @param array|string $data
     * @return bool
     */
    public function setCookie($data)
    {
        if (is_array($data)) {
            $this->_request_cookie = http_build_query($data, '', ';');
        } else {
            $this->_request_cookie = $data;
        }

        return true;
    }

    /**
     * 设置请求的内容
     * @param string|array $data
     * @return bool
     */
    public function setRequestBody($data)
    {
//        if (is_array($data))
//        {
//            $this->_request_data = http_build_query($data);
//        }
//        else
//        {
        $this->_request_data = $data;
//        }

        return true;
    }

    /**
     * @param $file
     * @param null $filename
     * @return CURLFile|string
     * @throws Exception
     */
    public function fileCreate($file, $filename = null)
    {
        if (!is_readable($file)) {
            throw new Exception("file not found!");
        }

        if ($filename === null) {
            $filename = basename($file);
        }

        $f_info    = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($f_info, $file);

        if (function_exists('curl_file_create')) {
            $c_file = curl_file_create($file, $mime_type, $filename);
        } else {
            $c_file = "@{$file};filename={$filename}"
                . ($mime_type ? ";type={$mime_type}" : '');
        }

        return $c_file;
    }

    /**
     * 设置请求方法
     * @param string $method
     */
    public function setMethod($method)
    {
        if (!in_array(strtoupper($method), array('POST', 'GET'))) {
            $method = 'GET';
        }

        $this->_method = strtoupper($method);
    }

    /**
     * 设置证书信息
     * @param string $cert_file
     * @param string $cert_passwd
     * @param string $cert_type
     * @return bool
     */
    public function setCertInfo($cert_file, $cert_passwd = '', $cert_type = "PEM")
    {
        if (!is_readable($cert_file) || empty($cert_type)) {
            return false;
        }
        $this->_cert_file   = $cert_file;
        $this->_cert_passwd = $cert_passwd;
        $this->_cert_type   = $cert_type;
        return true;
    }

    /**
     * 设置CA
     * @param string $ca_file
     * @return bool
     */
    public function setCaInfo($ca_file)
    {
        if (!is_readable($ca_file)) {
            return false;
        }
        $this->_ca_file = $ca_file;
        return true;
    }

    /**
     * 设置超时时间,单位毫秒
     * @param int $timeout
     * @return bool
     */
    public function setTimeout($timeout)
    {
        $this->_timeout_ms = (int) $timeout;
        return true;
    }

    /**
     * 设置连接超时时间,单位毫秒
     * @param int $timeout
     * @return bool
     */
    public function setConnectTimeout($timeout)
    {
        $this->_connect_timeout_ms = (int) $timeout;
        return true;
    }

    /**
     * 设置HttpHeader
     * @param array $headers
     * @return bool
     */
    public function setRequestHeader(array $headers)
    {
        if (empty($headers)) {
            return false;
        }

        $this->_headers = $headers;
        return true;
    }

    /**
     * 设置UserAgent
     * @param string $user_agent
     * @return bool
     */
    public function setUserAgent($user_agent)
    {
        if (!empty($user_agent)) {
            $this->_user_agent = $user_agent;
        }

        return true;
    }

    /**
     * 获取http响应状态码
     * @return int
     */
    public function getResponseCode()
    {
        return $this->_response_code;
    }

    /**
     * 获取UserAgent
     * @return string
     */
    public function getUserAgent()
    {
        return $this->_user_agent;
    }

    /**
     * 获取curl句柄信息
     * @return array
     */
    public function getCurlInfo()
    {
        return $this->_curl_info;
    }

    /**
     * 获取响应头信息
     * @return string
     */
    public function getResponseHeader()
    {
        return $this->_response_header;
    }

    /**
     * 获取响应结果信息
     * @return string
     */
    public function getResponseBody()
    {
        return $this->_response_body;
    }

    /**
     * 获取响应Cookie
     * @return array
     */
    public function getResponseCookie()
    {
        $_cookie = array();
        if (!empty($this->_response_header)) {
            $temp = array();
            preg_match_all('/Set-Cookie:\s*([^=]+)=([^;]+);*/i', $this->_response_header, $temp);
            if (is_array($temp) && isset($temp[1]) && isset($temp[2])) {
                $_cookie = array_combine($temp[1], array_map('urldecode', $temp[2]));
            }
        }
        return $_cookie;
    }

    /**
     * 获取错误码
     * @return int
     */
    public function getErrNo()
    {
        return $this->_errno;
    }

    /**
     * 获取错误码
     * @return string
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * 执行远程请求
     * @return bool
     */
    public function exec()
    {
        if (empty($this->_request_url)) {
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $this->_connect_timeout_ms);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->_timeout_ms);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }

        if ($this->_method == 'GET') {
            if ($this->_request_data !== null && is_array($this->_request_data)) {
                $this->_request_url .= ((strpos($this->_request_url, '?') === false) ? '?' : '&') . http_build_query($this->_request_data);
            }
            curl_setopt($ch, CURLOPT_URL, $this->_request_url);
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_URL, $this->_request_url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_request_data);
        }

        //设置HttpHeader
        if ($this->_headers !== null) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_headers);
        }
        curl_setopt($ch, CURLOPT_HEADER, true);

        if ($this->_request_port !== null) {
            curl_setopt($ch, CURLOPT_PORT, $this->_request_port);
        }

        if ($this->_request_cookie !== null) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->_request_cookie);
        }

        if ($this->_user_agent) {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->_user_agent);
        }

        //设置证书信息
        if ($this->_cert_file !== null) {
            curl_setopt($ch, CURLOPT_SSLCERT, $this->_cert_file);
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->_cert_passwd);
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, $this->_cert_type);
        }

        //设置CA
        if ($this->_ca_file !== null) {
            // 对认证证书来源的检查，0表示阻止对证书的合法性的检查。1需要设置CURLOPT_CAINFO
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_CAINFO, $this->_ca_file);
        } else {
            // 对认证证书来源的检查，0表示阻止对证书的合法性的检查。1需要设置CURLOPT_CAINFO
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        $response = curl_exec($ch);

        $this->_curl_info = curl_getinfo($ch);

        $this->_response_code = isset($this->_curl_info['http_code']) ? $this->_curl_info['http_code'] : 0;

        $this->_errno = curl_errno($ch);
        $this->_error = curl_error($ch);
        if ($this->_errno > 0) {
            curl_close($ch);
            return false;
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $this->_response_header = substr($response, 0, $headerSize);
        $this->_response_body   = substr($response, $headerSize);
        return true;
    }

}
