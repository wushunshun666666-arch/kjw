<?php

use PHPUnit\Framework\TestCase;

/** 各环境在隔离配置目录验证本站数据库覆盖，不读取实际私有文件。 */
final class LocalConfigTest extends TestCase
{
    private string $configDirectory;

    /** 仅复制受版本管理的环境文件，真实数据库连接不会被实例化。 */
    protected function setUp(): void
    {
        $this->configDirectory = sys_get_temp_dir() . '/lottery-config-test-' . bin2hex(random_bytes(8));
        mkdir($this->configDirectory, 0700);
        foreach (['main-dev.php', 'main-test.php', 'main-prod.php', 'merge-local.php'] as $file) {
            copy(dirname(__DIR__, 3) . '/config/' . $file, $this->configDirectory . '/' . $file);
        }
    }

    /** 清理本测试创建的固定文件，不递归操作真实项目目录。 */
    protected function tearDown(): void
    {
        foreach (['main-dev.php', 'main-test.php', 'main-prod.php', 'merge-local.php', 'main-local.php'] as $file) {
            $path = $this->configDirectory . '/' . $file;
            if (is_file($path)) {
                unlink($path);
            }
        }
        rmdir($this->configDirectory);
    }

    /** 没有本站私有文件时完整保留原配置和缓存实现。 */
    public function testMissingLocalFileKeepsConfiguration(): void
    {
        $merge = require $this->configDirectory . '/merge-local.php';
        $base = ['components' => ['db' => ['dsn' => 'fixture'], 'cache' => ['class' => 'FixtureCache', 'redis' => 'fixture']]];
        self::assertSame($base, $merge($base));

        foreach (['dev', 'test', 'prod'] as $environment) {
            $config = $this->environment($environment);
            self::assertSame('yii\db\Connection', $config['components']['db']['class']);
            self::assertSame('mylibs\yii\caching\RedisCache', $config['components']['cache']['class']);
        }
    }

    /** 三种环境均读取本站数据库，覆盖缓存时不遗留 Redis 专用参数。 */
    public function testEveryEnvironmentUsesPrivateDatabaseAndReplacesCache(): void
    {
        $baseFingerprints = [];
        foreach (['dev', 'test', 'prod'] as $environment) {
            $base = $this->environment($environment);
            unset($base['components']['db'], $base['components']['cache']);
            $baseFingerprints[$environment] = hash('sha256', serialize($base));
        }

        $this->writeLocal([
            'components' => [
                'db' => ['dsn' => 'mysql:host=127.0.0.1;dbname=fixture_client_a', 'username' => 'fixture_a', 'password' => 'fixture-only'],
                'cache' => ['class' => 'yii\caching\FileCache'],
            ],
        ]);

        foreach (['dev', 'test', 'prod'] as $environment) {
            $config = $this->environment($environment);
            self::assertSame('mysql:host=127.0.0.1;dbname=fixture_client_a', $config['components']['db']['dsn']);
            self::assertSame('fixture_a', $config['components']['db']['username']);
            self::assertSame('fixture-only', $config['components']['db']['password']);
            self::assertSame('utf8mb4', $config['components']['db']['charset']);
            self::assertSame(['class' => 'yii\caching\FileCache'], $config['components']['cache']);
            unset($config['components']['db'], $config['components']['cache']);
            self::assertSame($baseFingerprints[$environment], hash('sha256', serialize($config)));
        }
    }

    /** 重新读取环境配置使用当前站点的值，不复用之前载入的私有连接。 */
    public function testPrivateConfigurationIsReadOnEachLoad(): void
    {
        foreach (['fixture_client_a', 'fixture_client_b'] as $database) {
            $this->writeLocal(['components' => ['db' => ['dsn' => 'mysql:dbname=' . $database]]]);
            foreach (['dev', 'test', 'prod'] as $environment) {
                self::assertSame('mysql:dbname=' . $database, $this->environment($environment)['components']['db']['dsn']);
            }
        }
    }

    /** 在本测试目录写入纯虚构的私有配置。 */
    private function writeLocal(array $config): void
    {
        file_put_contents($this->configDirectory . '/main-local.php', '<?php return ' . var_export($config, true) . ';');
    }

    /** 隔离 require 的局部变量，返回指定环境的完整配置。 */
    private function environment(string $environment): array
    {
        return require $this->configDirectory . '/main-' . $environment . '.php';
    }
}
