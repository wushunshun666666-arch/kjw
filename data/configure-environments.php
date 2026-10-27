<?php

/** 仅在首次安装时复制三份环境模板，不覆盖已有站点配置。 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
$root = dirname(__DIR__);
foreach (['dev', 'test', 'prod'] as $environment) {
    $target = $root . '/common/config/main-' . $environment . '.php';
    $handle = @fopen($target, 'x');
    if ($handle === false) {
        if (is_file($target)) {
            echo '保留已有配置：main-' . $environment . '.php' . PHP_EOL;
            continue;
        }
        fwrite(STDERR, '无法创建环境配置，请检查 common/config 写入权限。' . PHP_EOL);
        exit(1);
    }
    $template = file_get_contents(__DIR__ . '/environment-templates/main-' . $environment . '.php');
    $template = str_replace('REPLACE_WITH_A_RANDOM_COOKIE_KEY', bin2hex(random_bytes(32)), $template);
    $written = fwrite($handle, $template);
    fclose($handle);
    @chmod($target, 0600);
    if ($written !== strlen($template)) {
        fwrite(STDERR, '环境配置写入不完整，请检查磁盘空间。' . PHP_EOL);
        exit(1);
    }
    echo '已创建：main-' . $environment . '.php，请填写本站配置。' . PHP_EOL;
}
