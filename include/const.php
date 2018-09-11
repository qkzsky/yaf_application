<?php

defined("__TIME__") || define("__TIME__", time());
defined("__DATE__") || define("__DATE__", date('Y-m-d', __TIME__));
defined("__DATETIME__") || define("__DATETIME__", date('Y-m-d H:i:s', __TIME__));
defined("CONF_PATH") || define("CONF_PATH", APP_PATH . '/config');