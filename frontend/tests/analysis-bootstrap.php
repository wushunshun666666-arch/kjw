<?php

/** 与前台二维码动作相同的显式库加载，供静态检查识别已有供应商类。 */
require dirname(__DIR__, 2) . '/common/tests/lottery-bootstrap.php';
require dirname(__DIR__, 2) . '/vendor/phpqrcode/phpqrcode.php';
