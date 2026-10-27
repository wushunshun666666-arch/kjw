<?php

use PHPUnit\Framework\TestCase;

/** 用隔离站点执行真实入口的配置部分，禁止连接业务数据库。 */
final class EntryConfigTest extends TestCase
{
    private string $fixture;
    private string $root;
    private array $files = [];

    /** 复制入口和无密钥模板；在创建应用前输出配置供断言。 */
    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 4);
        $this->fixture = sys_get_temp_dir() . '/portal-entry-' . bin2hex(random_bytes(8));
        $this->write('vendor/autoload.php', '<?php require ' . var_export($this->root . '/vendor/autoload.php', true) . ';');
        $this->write('vendor/yiisoft/yii2/Yii.php', '<?php require ' . var_export($this->root . '/vendor/yiisoft/yii2/Yii.php', true) . ';');
        $this->write('common/config/main.php', '<?php return [];');
        $this->write('common/config/bootstrap.php', '<?php');
        foreach (['frontend', 'admin', 'console'] as $application) {
            $this->write($application . '/config/main.php', '<?php return ["params" => ["appMarker" => true]];');
            $this->write($application . '/config/bootstrap.php', '<?php');
        }
        foreach (['dev', 'test', 'prod'] as $environment) {
            $template = file_get_contents($this->root . '/data/environment-templates/main-' . $environment . '.php');
            $this->write('data/environment-templates/main-' . $environment . '.php', $template);
            $config = require $this->root . '/data/environment-templates/main-' . $environment . '.php';
            $config['components']['db']['dsn'] = 'mysql:dbname=fixture_' . $environment;
            $config['params']['lotteryData'] = ['mode' => 'client', 'endpoint' => 'https://center.example/index.php',
                'client_id' => 'fixture_' . $environment, 'token' => str_repeat('X', 64)];
            $this->write('common/config/main-' . $environment . '.php', '<?php return ' . var_export($config, true) . ';');
        }
        $this->write('data/configure-environments.php', file_get_contents($this->root . '/data/configure-environments.php'));
        foreach (['frontend/web/index.php', 'admin/web/index.php', 'console/index.php', 'yii'] as $entry) {
            $source = file_get_contents($this->root . '/' . $entry);
            $end = strpos($source, $entry === 'frontend/web/index.php' ? '(new yii\\web\\Application' : '$application = new yii\\');
            self::assertNotFalse($end);
            $this->write($entry, substr($source, 0, $end) . 'echo json_encode(["environment" => YII_ENV, "config" => $config]);');
        }
        $this->write('probe.php', '<?php define("YII_ENV", $argv[1]); require __DIR__ . "/" . $argv[2];');
        $this->write('common/config/main-local.php', '<?php throw new RuntimeException("obsolete override loaded");');
    }

    /** 只删除本测试登记的文件及空目录。 */
    protected function tearDown(): void
    {
        $directories = [];
        foreach (array_reverse($this->files) as $file) {
            if (is_file($file)) { unlink($file); }
            $directory = dirname($file);
            while (str_starts_with($directory, $this->fixture)) {
                $directories[$directory] = true;
                if ($directory === $this->fixture) { break; }
                $directory = dirname($directory);
            }
        }
        $directories = array_keys($directories);
        usort($directories, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        foreach ($directories as $directory) { rmdir($directory); }
    }

    /** 四个原有入口直接读取指定环境，数据库及分站身份保持一致。 */
    public function testEntriesDirectlyLoadThreeEnvironmentFiles(): void
    {
        foreach (['dev', 'test', 'prod'] as $environment) {
            foreach (['frontend/web/index.php', 'admin/web/index.php', 'console/index.php', 'yii'] as $entry) {
                [$status, $output] = $this->runPhp('probe.php', [$environment, $entry]);
                self::assertSame(0, $status, $output);
                $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
                self::assertSame($environment, $result['environment']);
                $config = $result['config'];
                self::assertSame('mysql:dbname=fixture_' . $environment, $config['components']['db']['dsn']);
                self::assertSame('fixture_' . $environment, $config['params']['lotteryData']['client_id']);
                self::assertTrue($config['params']['appMarker']);
                self::assertArrayNotHasKey('applications', $config);
                self::assertSame($environment === 'dev' && $entry !== 'frontend/web/index.php', isset($config['modules']['gii']));
            }
        }
    }

    /** 缺失配置必须失败，不能读其他环境或旧 local 文件。 */
    public function testMissingConfigurationDoesNotFallBack(): void
    {
        unlink($this->fixture . '/common/config/main-prod.php');
        foreach (['frontend/web/index.php', 'admin/web/index.php', 'console/index.php', 'yii'] as $entry) {
            [$status, $output] = $this->runPhp('probe.php', ['prod', $entry]);
            self::assertNotSame(0, $status);
            self::assertStringContainsString('main-prod.php', $output);
        }
    }

    /** 初始化只补充缺少的环境文件，不能改写已有数据库或 Cookie 密钥。 */
    public function testInstallerPreservesExistingSettings(): void
    {
        unlink($this->fixture . '/common/config/main-prod.php');
        $before = hash_file('sha256', $this->fixture . '/common/config/main-dev.php');
        [$status, $output] = $this->runPhp('data/configure-environments.php');
        self::assertSame(0, $status, $output);
        self::assertSame($before, hash_file('sha256', $this->fixture . '/common/config/main-dev.php'));
        $production = require $this->fixture . '/common/config/main-prod.php';
        $key = $production['applications']['frontend']['components']['request']['cookieValidationKey'];
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $key);
        $productionHash = hash_file('sha256', $this->fixture . '/common/config/main-prod.php');
        [$status, $output] = $this->runPhp('data/configure-environments.php');
        self::assertSame(0, $status, $output);
        self::assertSame($productionHash, hash_file('sha256', $this->fixture . '/common/config/main-prod.php'));
    }

    /** 在唯一临时目录创建并登记文件。 */
    private function write(string $path, string $contents): void
    {
        $file = $this->fixture . '/' . $path;
        if (!is_dir(dirname($file))) { mkdir(dirname($file), 0700, true); }
        file_put_contents($file, $contents);
        $this->files[] = $file;
    }

    /** 不通过 shell 拼接参数，执行入口配置而不创建应用。 */
    private function runPhp(string $script, array $arguments = []): array
    {
        $process = proc_open([PHP_BINARY, '-d', 'opcache.enable_cli=0', '-d', 'display_errors=1',
            $this->fixture . '/' . $script, ...$arguments],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $this->fixture);
        self::assertIsResource($process);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $output];
    }
}
