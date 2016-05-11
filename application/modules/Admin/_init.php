<?php

$dispatcher = Yaf_Dispatcher::getInstance();

//Initialize Routes for Admin module
$routes = new Yaf_Config_Ini(__DIR__ . "/config" . "/routes.ini");
$dispatcher->getRouter()->addConfig($routes->admin);
