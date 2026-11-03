<?php

use app\models\portal\{CollectForm, CollectService};
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/models/Service.php';
require_once dirname(__DIR__, 2) . '/models/portal/CollectForm.php';
require_once dirname(__DIR__, 2) . '/models/portal/CollectService.php';

final class CollectWatchTest extends TestCase
{
    public function testPartialFailureDoesNotSlowHealthySources(): void
    {
        $service = new class extends CollectService {
            public array $delays = [];
            public function collect(string $game = 'all'): array|false
            {
                return ['received' => 2, 'inserted' => 1, 'results' => ['jnd28' => [], 'az51' => []],
                    'errors' => ['az51' => 'conflict']];
            }
            protected function closeConnection(): void {}
            protected function pause(int $seconds): void { $this->delays[] = $seconds; }
        };
        self::assertFalse($service->watch(new CollectForm(['rounds' => 4]), static function (): void {}));
        self::assertSame([5, 5, 5], $service->delays);
    }

    public function testBusinessFailuresAlsoRetryAndSuccessfulWatchReturnsTrue(): void
    {
        $service = new class extends CollectService {
            public int $calls = 0;
            public array $delays = [];
            public function collect(string $game = 'all'): array|false
            {
                $this->calls++;
                if ($this->calls === 1) { return $this->setError('source unavailable'); }
                return ['received' => 1, 'inserted' => 0, 'results' => [],
                    'errors' => $this->calls === 2 ? ['jnd28' => 'draw conflict'] : []];
            }
            protected function closeConnection(): void {}
            protected function pause(int $seconds): void { $this->delays[] = $seconds; }
        };
        $reports = [];
        $report = static function ($result, $error) use (&$reports): void { $reports[] = $error; };
        self::assertFalse($service->watch(new CollectForm(['rounds' => 4]), $report));
        self::assertSame([10, 20, 5], $service->delays);
        self::assertSame(['source unavailable', 'draw conflict', '', ''], $reports);
        self::assertTrue($service->watch(new CollectForm(['rounds' => 1]), $report));
    }

    public function testExceptionsRetryWithCappedBackoffAndResetOnRecovery(): void
    {
        $service = new class extends CollectService {
            public int $calls = 0;
            public int $closed = 0;
            public array $delays = [];
            public function collect(string $game = 'all'): array|false
            {
                $this->calls++;
                if ($this->calls <= 8) { throw new RuntimeException('private database password'); }
                return ['received' => 1, 'inserted' => 1, 'results' => [], 'errors' => []];
            }
            protected function closeConnection(): void { $this->closed++; }
            protected function pause(int $seconds): void { $this->delays[] = $seconds; }
        };
        $reports = [];
        self::assertFalse($service->watch(new CollectForm(['rounds' => 10]),
            static function ($result, $error) use (&$reports): void { $reports[] = [$result, $error]; }));
        self::assertSame(10, $service->calls);
        self::assertSame(10, $service->closed);
        self::assertSame([10, 20, 40, 80, 160, 300, 300, 300, 5], $service->delays);
        self::assertCount(10, $reports);
        self::assertStringContainsString('RuntimeException', $reports[0][1]);
        self::assertStringNotContainsString('private database password', $reports[0][1]);
        self::assertSame('', $reports[8][1]);
    }
}
