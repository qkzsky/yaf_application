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
 * Description of ErrorCode
 *
 * @author kuangzhiqiang
 */
final class ErrorCode
{

    // 成功
    const SUCCESS           = 0;
    // 权限不足
    const PERMISSION_DENIED = 4001;
    // 无效的参数
    const INVALID_PARAMETER = 4002;
    // 无效的格式
    const INVALID_FORMAT = 4003;
    // 系统错误
    const SYS_FAILED = 5002;
    // 操作失败
    const OPERATION_FAILED = 5003;
    // API调用失败
    const API_FAILED = 6002;

}
