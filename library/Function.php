<?php

/* 常用工具函数 */

/**
 * 浏览器友好的变量输出
 * @return type
 */
function dump()
{
    $args = func_get_args();

    ob_start();
    foreach ($args as $val)
    {
        var_dump($val);
    }
    $output = ob_get_clean();

    if (!extension_loaded('xdebug') && !IS_CLI)
    {
        $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
        $output = '<pre>' . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
    }
    echo $output;
    return;
}

/**
 * 优化的require_once
 * @staticvar array $_importFiles
 * @param string $filename
 * @return file_content
 */
function require_cache($filename)
{
    static $_importFiles = array();

    if (!file_exists_case($filename) || !is_readable($filename))
        return false;

    $realpath = realpath($filename);
    if (!isset($_importFiles[$realpath]))
    {
        require $filename;
        $_importFiles[$realpath] = true;
    }
    return $_importFiles[$realpath];
}

/**
 * 判断文件是否存在 (区分大小写)
 * @param string $filename
 * @return boolean
 */
function file_exists_case($filename)
{
    if (!is_file($filename))
    {
        return false;
    }
    if (basename(realpath($filename)) != basename($filename))
    {
        return false;
    }

    return true;
}

/**
 * 根据PHP各种类型变量生成唯一标识号
 * @param type $mix
 * @return type
 */
function to_guid_string($mix)
{
    if (is_object($mix) && function_exists('spl_object_hash'))
    {
        return spl_object_hash($mix);
    }
    elseif (is_resource($mix))
    {
        $mix = get_resource_type($mix) . strval($mix);
    }
    else
    {
        $mix = serialize($mix);
    }
    return md5($mix);
}

/**
 * 循环创建目录
 * @param string $dir
 * @param type $mode
 * @return boolean
 */
function mk_dir($dir, $mode = 0777)
{
    if (is_dir($dir) || mkdir($dir, $mode, true))
        return true;
    if (!mk_dir(dirname($dir), $mode))
        return false;
    return mkdir($dir, $mode, true);
}

/**
 * 批量创建目录
 * @param array $dirs
 * @param type $mode
 * @return boolean
 */
function mk_dirs(array $dirs, $mode = 0777)
{
    if (!is_array($dirs))
        return false;
    foreach ($dirs as $dir)
    {
        if (!is_dir($dir))
            mk_dir($dir, $mode);
    }
}

/**
 * 自动转换字符集 支持数组转换
 * @param string|array $fContents
 * @param string $from
 * @param string $to
 * @return string|array
 */
function auto_charset($fContents, $from = 'gbk', $to = 'utf-8')
{
    if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents)))
    {
        //如果编码相同或者非字符串标量则不转换
        return $fContents;
    }
    if (is_string($fContents))
    {
        if (function_exists('mb_convert_encoding'))
        {
            return mb_convert_encoding($fContents, $to, $from);
        }
        elseif (function_exists('iconv'))
        {
            return iconv($from, $to, $fContents);
        }
    }
    elseif (is_array($fContents))
    {
        foreach ($fContents as $key => $val)
        {
            $_key             = auto_charset($key, $from, $to);
            $fContents[$_key] = auto_charset($val, $from, $to);
            if ($key != $_key)
            {
                unset($fContents[$key]);
            }
        }
    }

    return $fContents;
}

/**
 * 字符串截取，支持中文和其他编码
 * @static
 * @access public
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * @return string
 */
function msubstr($str, $start = 0, $length = null, $charset = 'utf-8', $suffix = true)
{
    if (function_exists('mb_substr'))
    {
        return mb_substr($str, $start, $length, $charset);
    }
    elseif (function_exists('iconv_substr'))
    {
        return iconv_substr($str, $start, $length, $charset);
    }
    $re['utf-8']  = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
    $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
    $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
    $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
    preg_match_all($re[$charset], $str, $match);
    $slice        = join('', array_slice($match[0], $start, $length));
    if ($suffix)
        return $slice . '…';
    return $slice;
}

/**
 * XML编码
 * @param mixed $data 数据
 * @param string $encoding 数据编码
 * @param string $root 根节点名
 * @return string
 */
function xml_encode($data, $encoding = 'utf-8', $root = '')
{
    $xml = '<?xml version="1.0" encoding="' . $encoding . '"?>';
    $xml.= data_to_xml($data);
    if(!empty($root))
    {
        $xml = "<{$root}>{$xml}</{$root}>";
    }

    return $xml;
}

/**
 * 数据XML编码
 * @param mixed $data 数据
 * @return string
 */
function data_to_xml($data)
{
    if (is_object($data))
    {
        $data = get_object_vars($data);
    }
    $xml = '';
    foreach ($data as $key => $val)
    {
        is_numeric($key) && $key = 'item id="' . $key . '"';
        $xml.='<' . $key . '>';
        $xml.= ( is_array($val) || is_object($val)) ? data_to_xml($val) : $val;
        list($key, ) = explode(' ', $key);
        $xml.='</' . $key . '>';
    }
    return $xml;
}

/**
 * 对多维数组进行排序
 * array_msort($arr, array('vip'=>array(SORT_DESC,SORT_REGULAR), 'sex'=>SORT_DESC, 'level'=>SORT_DESC, 'cid'=>SORT_ASC));
 * @param array $array
 * @param array $cols
 * @return array
 */
function array_msort($array, $cols)
{
    $col_arr = array();
    $params  = array();
    foreach ($cols as $col => $order)
    {
        $col_arr[$col] = array();
        foreach ($array as $k => $row)
        {
            $col_arr[$col]['_' . $k] = $row[$col];
        }
        $params[] = &$col_arr[$col];
        $params   = array_merge($params, (array) $order);
    }
    call_user_func_array('array_multisort', $params);

    $ret = array();
    foreach ($col_arr as $col => $sort_arr)
    {
        foreach ($sort_arr as $k => $v)
        {
            $k       = substr($k, 1);
            $ret[$k] = $array[$k];
        }
        break;
    }
    return $ret;
}

/**
 * URL重定向
 * @param string $url
 * @param int $time
 * @param string $msg
 */
function redirect($url, $time = 0, $msg = '')
{
    //多行URL地址支持
    $url = str_replace(array("\n", "\r"), '', $url);
    if (empty($msg))
        $msg = '系统将在' . $time . '秒之后自动跳转到' . $url . '！';
    if (!headers_sent())
    {
        // redirect
        if (0 === $time)
        {
            header('Location: ' . $url);
        }
        else
        {
            header('refresh:' . $time . ';url=' . $url);
            echo($msg);
        }
        exit();
    }
    else
    {
        $str = '<meta http-equiv="Refresh" content="' . $time . ';URL=' . $url . '">';
        if ($time != 0)
        {
            $str .= $msg;
        }
        exit($str);
    }
}

/**
 * 发送HTTP状态
 * @param integer $code 状态码
 * @return void
 */
function send_http_status($code)
{
    static $_status = array(
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Moved Temporarily ', // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // 306 is deprecated but reserved
        307 => 'Temporary Redirect',
        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
    );
    if (isset($_status[$code]))
    {
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
        header($protocol . ' ' . $code . ' ' . $_status[$code]);
        // 确保FastCGI模式下正常
        header('Status:' . $code . ' ' . $_status[$code]);
    }
}

/**
 * 产生随机字串，可用来自动生成密码 默认长度6位 字母和数字混合
 * @param string $len 长度
 * @param string $type 字串类型
 * 0 字母 1 数字 其它 混合
 * @param string $addChars 额外字符
 * @return string
 */
function rand_string($len = 6, $type = null, $addChars = null)
{
    $str = '';
    switch ($type)
    {
        case 1:
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
            break;
        case 2:
            $chars = str_repeat('0123456789', 3);
            break;
        case 3:
            $chars = '~!@#$%^&*()-_=+{}[]|?<>' . $addChars;
            break;
        case 4:
            $chars = '们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟蚕亿矩康遵牧遭幅园腔订香肉弟屋敏恢忘编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂灵蛋闭孩释乳巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙趋彼届墨碍启逆卸航衣孙龄岭骗休借' . $addChars;
            break;
        default :
            // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
            $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
            break;
    }
    $chars_len = mb_strlen($chars, 'utf-8');
    //位数过长重复字符串一定次数
    if ($len > $chars_len && $type != 4)
    {
        $chars = str_shuffle(str_repeat($chars, ceil($len / $chars_len)));
        $str   = substr($chars, 0, $len);
    }
    else
    {
        // 中文随机字
        for ($i = 0; $i < $len; $i++)
        {
            $str.= msubstr($chars, mt_rand(0, $chars_len - 1), 1);
        }
    }
    return $str;
}

/**
 * 检查字符串是否是UTF8编码
 * @param string $string 字符串
 * @return Boolean
 */
function is_utf8($string)
{
    $rs = preg_match("/^[\x{4e00}-\x{9fa5}a-za-z0-9_]$/u", $string);
    return ($rs === false) ? false : true;
}

/**
 * 代码加亮
 * @param String  $str 要高亮显示的字符串 或者 文件名
 * @param Boolean $show 是否输出
 * @return String
 */
function highlight_code($str, $show = false)
{
    if (file_exists($str))
    {
        $str = file_get_contents($str);
    }
    $str = stripslashes(trim($str));
    // The highlight string function encodes and highlights
    // brackets so we need them to start raw
    $str = str_replace(array('&lt;', '&gt;'), array('<', '>'), $str);

    // Replace any existing PHP tags to temporary markers so they don't accidentally
    // break the string out of PHP, and thus, thwart the highlighting.

    $str = str_replace(array('&lt;?php', '?&gt;', '\\'), array('phptagopen', 'phptagclose', 'backslashtmp'), $str);

    // The highlight_string function requires that the text be surrounded
    // by PHP tags.  Since we don't know if A) the submitted text has PHP tags,
    // or B) whether the PHP tags enclose the entire string, we will add our
    // own PHP tags around the string along with some markers to make replacement easier later

    $str = '<?php //tempstart' . "\n" . $str . '//tempend ?>';
    // All the magic happens here, baby!
    $str = highlight_string($str, TRUE);

    // Prior to PHP 5, the highlight function used icky font tags
    // so we'll replace them with span tags.
    if (abs(phpversion()) < 5)
    {
        $str = str_replace(array('<font ', '</font>'), array('<span ', '</span>'), $str);
        $str = preg_replace('#color="(.*?)"#', 'style="color: \\1"', $str);
    }

    // Remove our artificially added PHP
    $str = preg_replace("#\<code\>.+?//tempstart\<br />\</span\>#is", "<code>\n", $str);
    $str = preg_replace("#\<code\>.+?//tempstart\<br />#is", "<code>\n", $str);
    $str = preg_replace("#//tempend.+#is", "</span>\n</code>", $str);

    // Replace our markers back to PHP tags.
    $str    = str_replace(array('phptagopen', 'phptagclose', 'backslashtmp'), array('&lt;?php', '?&gt;', '\\'), $str);
    $line   = explode("<br />", rtrim(ltrim($str, '<code>'), '</code>'));
    $result = '<div class="code"><ol>';
    foreach ($line as $key => $val)
    {
        $result .= '<li>' . $val . '</li>';
    }
    $result .= '</ol></div>';
    $result = str_replace("\n", '', $result);
    if ($show !== false)
    {
        echo($result);
    }
    else
    {
        return $result;
    }
}

/**
 * 获取客户端IP地址
 * @staticvar null $ip
 * @return null
 */
function get_client_ip()
{
    static $ip = null;
    if ($ip !== null)
    {
        return $ip;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
    {
        $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($arr[0]);
    }
    elseif (!empty($_SERVER['HTTP_CLIENT_IP']))
    {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['REMOTE_ADDR']))
    {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $ip = (false !== ip2longfix($ip)) ? $ip : '0.0.0.0';
    return $ip;
}

function long2ipfix($ip_32)
{
    $ip  = long2ip($ip_32);
    //先判断是big-endian还是little-endian
    $foo = 0x3456789a;
    switch (pack('L', $foo))
    {
        case pack('V', $foo):
            //little-endian
            $tmp = explode(".", $ip);
            $ip  = $tmp[3] . "." . $tmp[2] . "." . $tmp[1] . "." . $tmp[0];
            break;

        case pack('V', $foo):
            //big-endian
            //Nothing
            break;
        default:
    }

    return $ip;
}

/**
 * ip转整形
 * @param string $ip
 * @return int
 */
function ip2longfix($ip)
{
    $ip_arr = explode('.', $ip);
    $iplong = ($ip_arr[0] << 24) +
            ($ip_arr[1] << 16) +
            ($ip_arr[2] << 8) +
            $ip_arr[3];
    return $iplong;
}

/**
 * 命令行中获取用户输入
 * @param string $notice 提示
 * @param int $length    读取的长度, 默认255
 * @return boolean | string
 */
function raw_input($notice, $length = 255)
{
    if ((is_string($notice) && $notice === '') || is_bool($notice))
    {
        return false;
    }
    print($notice . " ");
    $fp    = fopen('/dev/stdin', 'r');
    $input = fgets($fp, 255);
    fclose($fp);
    return trim($input);
}

/**
 * 解析命令行中传参
 * example: ./test.php --l=4 -d --n=6
 * @param array $argv
 * @return array
 */
function arguments(array $argv)
{
    $_ARG = array();
    foreach ($argv as $arg)
    {
        $reg = array();
        if (preg_match('/--([^=]+)=(.*)/', $arg, $reg))
        {
            $_ARG[$reg[1]] = $reg[2];
        }
        elseif (preg_match('/^-([a-zA-Z0-9])/', $arg, $reg))
        {
            $_ARG[$reg[1]] = 'true';
        }
    }
    return $_ARG;
}

/**
 * 字节格式化 把字节数格式为 B K M G T 描述的大小
 * @return string
 */
function byte_format($size, $dec = 2)
{
    $a   = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    $pos = 0;
    while ($size >= 1024)
    {
        $size /= 1024;
        $pos++;
    }
    return round($size, $dec) . ' ' . $a[$pos];
}

function base64_encode4url($str)
{
    $baseStr = base64_encode($str);
    $search  = array('+', '/', '=');
    $replace = array('*', '-', '.');
    return str_replace($search, $replace, $baseStr);
}

function base64_decode4url($str)
{
    $search  = array('*', '-', '.');
    $replace = array('+', '/', '=');
    $str     = str_replace($search, $replace, $str);
    return base64_decode($str);
}

function get_zodiac_sign($month, $day)
{
    // 检查参数有效性
    if ($month < 1 || $month > 12 || $day < 1 || $day > 31)
        return (false);
    // 星座名称以及开始日期
    $signs = array(
        array("20" => "水瓶座"),
        array("19" => "双鱼座"),
        array("21" => "白羊座"),
        array("20" => "金牛座"),
        array("21" => "双子座"),
        array("22" => "巨蟹座"),
        array("23" => "狮子座"),
        array("23" => "处女座"),
        array("23" => "天秤座"),
        array("24" => "天蝎座"),
        array("23" => "射手座"),
        array("22" => "摩羯座")
    );
    list($sign_start, $sign_name) = each($signs[(int) $month - 1]);
    if ($day < $sign_start)
        list($sign_start, $sign_name) = each($signs[($month - 2 < 0) ? $month = 11 : $month -= 2]);
    return $sign_name;
}

/**
 * 快速掉用RPC
 *
 * @param string $server_addr
 * @param string $method
 * @param array $parameters
 * @param string $charset
 * @return mixed 如果返回值为 -9999, 则表示RPC错误
 */
function fast_rpc_call($server_addr, $method, array $parameters, $charset = 'utf-8')
{
    $request  = xmlrpc_encode_request($method, $parameters, array(
        'escaping' => 'markup',
        'encoding' => $charset
    ));
    $context  = stream_context_create(array(
        'http' => array(
            'method'  => 'POST',
            'header'  => 'Content-Type: text/xml',
            'content' => $request
        )
    ));
    $_response = file_get_contents($server_addr, null, $context);

    $response = xmlrpc_decode($_response);
    if (is_array($response))
    {
        if (xmlrpc_is_fault($response))
        {
            return - 9999;
        }
    }
    return $response;
}
