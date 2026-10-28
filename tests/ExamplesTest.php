<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class ExamplesTest extends TestCase
{
    public function testExecutableQuickStart(): void
    {
        $process = new Process([PHP_BINARY, 'examples/quick-start.php'], dirname(__DIR__));
        $process->mustRun();
        $result = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(2, $result['statistics']['drawCount']);
        self::assertSame(5, $result['matches'][0]['total']);
        self::assertCount(2, $result['randomBets']);
    }

    public function testMarkedDocumentationExamplesExecute(): void
    {
        $root = dirname(__DIR__);
        $count = 0;
        foreach ([$root.'/README.md', ...glob($root.'/docs/*.md')] as $file) {
            if (!is_file($file)) {
                continue;
            }
            preg_match_all('/<!-- executable -->\s*```php\n(.*?)```/s', file_get_contents($file), $matches);
            foreach ($matches[1] as $code) {
                $process = new Process([PHP_BINARY, '-d', 'display_errors=1', '-r', $code], $root);
                $process->mustRun();
                self::assertStringNotContainsString('Warning:', $process->getOutput(), $file);
                ++$count;
            }
        }
        self::assertGreaterThanOrEqual(6, $count, 'The README and guides must contain runnable, tested examples.');
    }
}
