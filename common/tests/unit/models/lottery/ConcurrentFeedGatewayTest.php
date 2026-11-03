<?php

use common\models\Error;
use common\models\lottery\{ConcurrentFeedGateway, Game};
use PHPUnit\Framework\TestCase;

final class ConcurrentFeedGatewayTest extends TestCase
{
    public function testIndependentBodiesStatusProtocolAndSizeLimits(): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $address = stream_socket_get_name($socket, false);
        fclose($socket);
        $process = proc_open([PHP_BINARY, '-S', $address, dirname(__DIR__, 3) . '/fixtures/feed-router.php'],
            [['file', 'NUL', 'r'], ['file', 'NUL', 'w'], ['file', 'NUL', 'w']], $pipes);
        self::assertIsResource($process);
        try {
            for ($i = 0; $i < 100; $i++) {
                $connection = @stream_socket_client('tcp://' . $address, $errno, $message, 0.05);
                if ($connection) { fclose($connection); break; }
                usleep(20000);
            }
            $gateway = new class($address) extends ConcurrentFeedGateway {
                public function __construct(private string $address) {}
                protected function feedUrl(string $code): string { return 'http://' . $this->address . '/?game=' . $code; }
            };
            $games = Game::find()->where(['code' => ['jnd28', 'az51', 'jsssc', 'jspk10']])->indexBy('code')->all();
            $rows = $gateway->fetchAll($games);
            self::assertCount(4, $rows);
            self::assertSame('1,2,3', $rows['jnd28'][0]->numbers);
            self::assertSame('fixture_1', $rows['jnd28'][0]->issue);
            foreach (['az51', 'jsssc', 'jspk10'] as $code) { self::assertInstanceOf(Error::class, $rows[$code]); }
            self::assertSame([], $gateway->fetchAll([]));
        } finally {
            proc_terminate($process); proc_close($process);
        }
    }
}
