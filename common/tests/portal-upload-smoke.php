<?php

/** 本地真实 multipart 上传验收；独立回环服务、随机令牌、配置事务回滚和文件清理。 */
if (!in_array(PHP_SAPI, ['cli', 'cli-server'], true)) { http_response_code(404); exit; }

if (PHP_SAPI === 'cli-server') {
    $expected = getenv('PORTAL_UPLOAD_TEST_TOKEN');
    if (!$expected || ($_SERVER['REMOTE_ADDR'] ?? '') !== '127.0.0.1'
        || !hash_equals($expected, $_SERVER['HTTP_X_PORTAL_TEST_TOKEN'] ?? '')) {
        http_response_code(404);
        exit;
    }
    require __DIR__ . '/lottery-bootstrap.php';
    common\models\LogicModel::setIdentity(common\models\system\UserIdentity::findIdentity(common\models\system\User::ADMIN_ID));
    $resource = new common\resource\PortalImageResource();
    $result = $resource->upload(yii\web\UploadedFile::getInstanceByName('upload'));
    header('Content-Type: application/json');
    echo json_encode($result === false ? ['error' => $resource->getError()] : $result);
    exit;
}

require __DIR__ . '/lottery-bootstrap.php';
common\models\LogicModel::setIdentity(common\models\system\UserIdentity::findIdentity(common\models\system\User::ADMIN_ID));
Yii::$app->set('urlManager', ['class' => yii\web\UrlManager::class, 'scriptUrl' => '/index.php', 'baseUrl' => '']);
$socket = stream_socket_server('tcp://127.0.0.1:0');
$address = stream_socket_get_name($socket, false);
fclose($socket);
$token = bin2hex(random_bytes(32));
$log = tmpfile();
$root = sys_get_temp_dir() . '/portal-upload-smoke-' . bin2hex(random_bytes(8));
mkdir($root);
$process = proc_open([PHP_BINARY, '-d', 'opcache.enable=0', '-d', 'opcache.enable_cli=0', '-d', 'xdebug.mode=off',
    '-d', 'upload_max_filesize=8M', '-d', 'post_max_size=10M', '-S', $address, '-t', $root, __FILE__],
    [0 => ['pipe', 'r'], 1 => $log, 2 => $log], $pipes, dirname(__DIR__, 2), array_merge(getenv(), ['PORTAL_UPLOAD_TEST_TOKEN' => $token]));
$createdFiles = [];
$transaction = null;
$count = 0;

/** 中断测试并给出明确断言失败原因。 */
function portalUploadAssert(bool $condition, string $message): void
{
    global $count;
    if (!$condition) { throw new RuntimeException($message); }
    $count++;
}

/** 通过真实 HTTP 请求触发 PHP multipart 上传处理。 */
function portalUploadRequest(string $address, string $token, string $file, string $name): array
{
    $handle = curl_init('http://' . $address . '/upload');
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['X-Portal-Test-Token: ' . $token],
        CURLOPT_POSTFIELDS => ['upload' => new CURLFile($file, 'application/octet-stream', $name)]]);
    $body = curl_exec($handle);
    $result = is_string($body) ? json_decode($body, true) : null;
    if (!is_array($result)) { throw new RuntimeException('上传测试服务返回无效响应。'); }
    return $result;
}

try {
    for ($attempt = 0; $attempt < 30; $attempt++) {
        $probe = @stream_socket_client('tcp://' . $address, $errno, $error, 0.1);
        if ($probe !== false) { fclose($probe); break; }
        usleep(100000);
    }
    $gif = Yii::getAlias('@frontend/web/assets1/ad.gif');
    $response = portalUploadRequest($address, $token, $gif, 'advertisement.gif');
    portalUploadAssert(isset($response['reference']), '真实 GIF 上传失败。');
    $reference = $response['reference'];
    $resource = new common\resource\PortalImageResource();
    $image = $resource->resolve($reference);
    portalUploadAssert($image !== null, '上传文件无法读取。');
    $createdFiles[] = $image['path'];
    $createdFiles[] = $image['path'] . '.json';
    portalUploadAssert(hash_file('sha256', $gif) === hash_file('sha256', $image['path']), 'GIF 上传内容发生变化。');
    portalUploadAssert($resource->canUse($reference, []), '上传者不能预览自己的图片。');
    portalUploadAssert((new frontend\models\ReferencePortalService())->image($reference) === null, '未保存图片被公开。');
    portalUploadAssert(isset(portalUploadRequest($address, $token, $gif, 'advertisement.php')['error']), '危险扩展被接受。');
    $fake = $root . '/fake.jpg';
    file_put_contents($fake, '<?php echo "invalid image";');
    $createdFiles[] = $fake;
    portalUploadAssert(isset(portalUploadRequest($address, $token, $fake, 'fake.jpg')['error']), '伪造图片被接受。');
    $large = $root . '/large.gif';
    file_put_contents($large, file_get_contents($gif) . str_repeat('x', common\resource\PortalImageResource::MAX_SIZE));
    $createdFiles[] = $large;
    portalUploadAssert(isset(portalUploadRequest($address, $token, $large, 'large.gif')['error']), '超限图片被接受。');
    $transaction = Yii::$app->db->beginTransaction();
    $attributes = common\models\lottery\Settings::current()->attributes;
    $attributes['ads_json'] = json_encode([['cover' => $reference, 'link' => 'https://example.com/upload-test', 'enabled' => 1]]);
    $model = new common\models\lottery\SettingsModel();
    portalUploadAssert($model->save($attributes), '上传广告不能保存：' . $model->getError());
    $service = new frontend\models\ReferencePortalService();
    portalUploadAssert($service->image($reference) !== null, '保存后的广告图片不能公开读取。');
    portalUploadAssert(str_contains($service->website()['ad'][0]['cover'], 'reference%2Fimage'), '前台没有使用受控图片入口。');
    $attributes = common\models\lottery\Settings::current()->attributes;
    $attributes['ads_json'] = json_encode([['cover' => $reference, 'link' => 'https://example.com/upload-test', 'enabled' => 0]]);
    portalUploadAssert((new common\models\lottery\SettingsModel())->save($attributes), '隐藏广告失败。');
    portalUploadAssert($service->image($reference) === null, '隐藏的广告仍可公开读取。');
    echo 'OK (' . $count . ' assertions): multipart GIF upload, validation, save, publish and hide.' . PHP_EOL;
} finally {
    if ($transaction?->isActive) { $transaction->rollBack(); }
    if (is_resource($process)) { proc_terminate($process); proc_close($process); }
    foreach ($pipes as $pipe) { if (is_resource($pipe)) { fclose($pipe); } }
    fclose($log);
    foreach ($createdFiles as $file) { if (is_file($file)) { unlink($file); } }
    rmdir($root);
}
