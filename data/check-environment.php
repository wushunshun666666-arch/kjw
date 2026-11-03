<?php

/** 只读部署检查，不打印账号、密码、令牌或 Cookie 密钥。 */
$environment = $argv[1] ?? 'prod';
if (!in_array($environment, ['dev', 'test', 'prod'], true)) {
    fwrite(STDERR, "Usage: php data/check-environment.php [dev|test|prod]\n"); exit(1);
}
$root = dirname(__DIR__);
$file = $root . '/common/config/main-' . $environment . '.php';
if (!is_file($file)) { fwrite(STDERR, "Environment configuration missing\n"); exit(1); }
$config = require $file;
$db = $config['components']['db'] ?? [];
$dsn = $db['dsn'] ?? '';
preg_match('/(?:^|;)dbname=([^;]+)/', substr($dsn, 6), $database);
$report = ['checked_environment' => $environment, 'php' => PHP_VERSION,
    'database' => $database[1] ?? '(unknown)', 'role' => $config['params']['lotteryData']['mode'] ?? 'standalone',
    'cli_admin_default_environment' => extension_loaded('is_prod_mode') ? 'prod' : (extension_loaded('is_test_mode') ? 'test' : 'dev'),
    'frontend_environment_variable' => getenv('YII_ENV') ?: '(unset; defaults to dev)',
    'note' => 'CLI检查不代表Web/FPM环境；生产后台必须选择prod并关闭YII_DEBUG。'];
$ok = true;
foreach (['pdo_mysql', 'curl', 'mbstring', 'intl', 'fileinfo', 'gd'] as $extension) {
    $report['extensions'][$extension] = extension_loaded($extension);
    $ok = $ok && extension_loaded($extension);
}
try {
    $pdo = new PDO($dsn, $db['username'] ?? '', $db['password'] ?? '', [PDO::ATTR_TIMEOUT => 5]);
    $pdo->query('SELECT 1'); $report['database_connection'] = 'ok';
} catch (Throwable $exception) {
    $report['database_connection'] = 'failed (' . $exception->getCode() . ')'; $ok = false;
}
foreach (['common/runtime', 'frontend/runtime', 'console/runtime', 'admin/runtime'] as $path) {
    $report['writable'][$path] = is_writable($root . '/' . $path);
    $ok = $ok && $report['writable'][$path];
}
$report['feed_transport'] = 'HTTP; HTTPS support must be confirmed with upstream; TLS verification must remain enabled';
echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
exit($ok ? 0 : 1);
