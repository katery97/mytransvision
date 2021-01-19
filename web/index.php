<?php

$startMemory = memory_get_usage();
$t1 = microtime(true);

define('APP_DEBUG',true);

require_once __DIR__ . '/../app/inc/router.php';
