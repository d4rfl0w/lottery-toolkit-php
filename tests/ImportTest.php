<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rupcom\LotteryToolkit\Exception\ImportException;
use Rupcom\LotteryToolkit\Exception\ValidationException;
use Rupcom\LotteryToolkit\Game\GameRules;
use Rupcom\LotteryToolkit\Game\NumberPool;
use Rupcom\LotteryToolkit\Import\DrawImporter;
use Rupcom\LotteryToolkit\Import\DrawRecord;
use Rupcom\LotteryToolkit\Import\DrawSourceInterface;
use Rupcom\LotteryToolkit\Import\Source\CsvDrawSource;
use Rupcom\LotteryToolkit\Import\Source\InMemoryDrawSource;
use Rupcom\LotteryToolkit\Import\Source\JsonDrawSource;

final class ImportTest extends TestCase
{
    public function testEquivalentTypedRecordsJsonAndCsvImports(): void
    {
        $game = GameRules::lotto();
        $rows = [new DrawRecord('a', '2026-01-01', ['main' => [1, 2, 3, 4, 5, 6]])];
        $importer = new DrawImporter();
        $array = $importer->import($game, new InMemoryDrawSource((static function () use ($rows) {
            yield from $rows;
        })()));
        self::assertEquals($array, $importer->import($game, new JsonDrawSource(json_encode($rows))));
        self::assertEquals($array, $importer->import($game, new CsvDrawSource("\xEF\xBB\xBFmain,date,id\r\n\"1 2 3 4 5 6\",2026-01-01,a\r\n\r\n")));
        self::assertEquals($array, $importer->import($game, new CsvDrawSource("id;date;main\na;2026-01-01;1 2 3 4 5 6", ';')));
        self::assertCount(0, $importer->import($game, new JsonDrawSource('[]')));
        self::assertCount(0, $importer->import($game, new CsvDrawSource('id,date,main')));
    }

    public function testQuotedMultilineFieldsAndEuroPools(): void
    {
        $draws = (new DrawImporter())->import(GameRules::eurojackpot(), new CsvDrawSource("id,date,main,euro\n\"a,\"\"b\"\"\nc\",2026-01-01T01:00:00+01:00,1 2 3 4 5,11 12\n"));
        self::assertSame("a,\"b\"\nc", $draws->latest()->id);
        self::assertSame([11, 12], $draws->latest()->numbers['euro']);
        self::assertSame('2026-01-01T00:00:00+00:00', $draws->latest()->date->format(DATE_ATOM));
    }

    #[DataProvider('invalidJson')]
    public function testInvalidJson(string $json): void
    {
        $this->expectException(ImportException::class);
        (new DrawImporter())->import(GameRules::lotto(), new JsonDrawSource($json));
    }

    public static function invalidJson(): iterable
    {
        foreach (['{', '{}', 'null', '1', '[1]', '[null]', '[{}]', '[{"id":1,"date":"2026-01-01","numbers":{"main":[1,2,3,4,5,6]}}]', '[{"id":"a","date":"2026-01-01","numbers":{"main":["1",2,3,4,5,6]}}]'] as $json) {
            yield [$json];
        }
    }

    #[DataProvider('invalidCsv')]
    public function testInvalidCsv(string $csv, string $delimiter = ','): void
    {
        $this->expectException(ImportException::class);
        (new DrawImporter())->import(GameRules::lotto(), new CsvDrawSource($csv, $delimiter));
    }

    public static function invalidCsv(): iterable
    {
        yield [''];
        yield ['id,date'];
        yield ['id,date,main,main'];
        yield ["id,date,main\na,2026-01-01"];
        yield ["id,date,main\na,2026-01-01,1 2 3 4 5 6,extra"];
        yield ["id,date,main\na,2026-01-01,1 2 3 4 5 nope"];
        yield ["id,date,main\na,2026-01-01,1 2 3 4 5 999999999999999999999"];
        yield ["id,date,main\na,2026-01-01,1 2 3 4 5 5"];
        yield ["id,date,main\n\"a,2026-01-01,1 2 3 4 5 6"];
        yield ["id,date,main\n\"a\"oops,2026-01-01,1 2 3 4 5 6"];
        yield ["id,date,main\na\"b,2026-01-01,1 2 3 4 5 6"];
        yield ['id,date,main', '||'];
        yield ['id,date,main', ' '];
    }

    #[DataProvider('invalidDates')]
    public function testInvalidDates(string $date): void
    {
        $this->expectException(ImportException::class);
        (new DrawImporter())->import(GameRules::lotto(), new InMemoryDrawSource([new DrawRecord('a', $date, ['main' => [1, 2, 3, 4, 5, 6]])]));
    }

    public static function invalidDates(): iterable
    {
        foreach (['tomorrow', '', '2026-02-30', '2026-13-01', '2026-1-01', '2026-01-01T24:00:00Z', '2026-01-01T00:00:00', '2026-01-01T00:00:00+15:00', '2026-01-01T00:00:00+14:30', '2026-01-01T00:00:00.123Z', "2026-01-01\0"] as $date) {
            yield [$date];
        }
    }

    public function testUtcDatesAndLeapDayAreAcceptedWithoutGlobalTimezoneDependency(): void
    {
        $original = date_default_timezone_get();
        date_default_timezone_set('Pacific/Auckland');
        try {
            foreach (['2024-02-29', '2024-02-29T00:00:00Z', '2024-02-29T01:00:00+01:00'] as $date) {
                $draws = (new DrawImporter())->import(GameRules::lotto(), new InMemoryDrawSource([new DrawRecord('a', $date, ['main' => [1, 2, 3, 4, 5, 6]])]));
                self::assertSame('2024-02-29T00:00:00+00:00', $draws->latest()->date->format(DATE_ATOM));
            }
        } finally {
            date_default_timezone_set($original);
        }
    }

    #[DataProvider('malformedJsonRecords')]
    public function testMalformedJsonRecordsProduceImportExceptions(mixed $row): void
    {
        $this->expectException(ImportException::class);
        (new DrawImporter())->import(GameRules::lotto(), new JsonDrawSource(json_encode([$row], JSON_THROW_ON_ERROR)));
    }

    public static function malformedJsonRecords(): iterable
    {
        yield [null];
        yield ['not a record'];
        yield [['id' => 'a', 'date' => '2026-01-01', 'numbers' => ['main' => [1, 2, 3, 4, 5, 6]], 'unexpected' => true]];
    }

    public function testDuplicateIdsFailAtomicallyAndPreserveCause(): void
    {
        $row = new DrawRecord('a', '2026-01-01', ['main' => [1, 2, 3, 4, 5, 6]]);
        try {
            (new DrawImporter())->import(GameRules::lotto(), new InMemoryDrawSource([$row, $row]));
            self::fail('Expected duplicate rejection.');
        } catch (ImportException $exception) {
            self::assertStringContainsString('record 2', $exception->getMessage());
            self::assertNotNull($exception->getPrevious());
        }
    }

    public function testCustomSourceReceivesRulesAndDoesNotNeedImporterChanges(): void
    {
        $game = new GameRules('tiny', [new NumberPool('main', 1, 1, 9)]);
        $source = new class ($game) implements DrawSourceInterface {
            public function __construct(private readonly GameRules $expectedGame)
            {
            }

            public function records(GameRules $game): iterable
            {
                TestCase::assertSame($this->expectedGame, $game);
                yield 'later' => new DrawRecord('b', '2026-01-02', ['main' => [2]]);
                yield 'earlier' => new DrawRecord('a', '2026-01-01', ['main' => [1]]);
            }
        };

        $draws = (new DrawImporter())->import($game, $source);

        self::assertSame(['a', 'b'], array_column($draws->toArray(), 'id'));
        self::assertSame($game, $draws->game);
    }

    public function testDuplicateFailsAtItsActualPositionWithoutConsumingRemainingRecords(): void
    {
        $record = new DrawRecord('a', '2026-01-01', ['main' => [1, 2, 3, 4, 5, 6]]);
        $source = new InMemoryDrawSource((static function () use ($record): iterable {
            yield $record;
            yield $record;
            throw new \LogicException('The importer must stop at the duplicate.');
        })());

        try {
            (new DrawImporter())->import(GameRules::lotto(), $source);
            self::fail('Expected duplicate rejection.');
        } catch (ImportException $exception) {
            self::assertStringContainsString('record 2', $exception->getMessage());
            self::assertInstanceOf(ValidationException::class, $exception->getPrevious());
        }
    }

    public function testMalformedJsonRecordReportsPositionAfterValidRecords(): void
    {
        $source = new JsonDrawSource('[{"id":"a","date":"2026-01-01","numbers":{"main":[1,2,3,4,5,6]}},null]');
        $this->expectException(ImportException::class);
        $this->expectExceptionMessage('record 2');

        (new DrawImporter())->import(GameRules::lotto(), $source);
    }

    public function testInvalidTypedRecordIsValidatedByTheImporter(): void
    {
        $source = new InMemoryDrawSource([
            new DrawRecord('a', '2026-01-01', ['main' => [1, 2, 3, 4, 5, '6']]),
        ]);
        $this->expectException(ImportException::class);

        (new DrawImporter())->import(GameRules::lotto(), $source);
    }

    public function testSourceFailureAfterAValidRecordPreservesCauseAndPosition(): void
    {
        $failure = new ImportException('Source became unavailable.');
        $source = new InMemoryDrawSource((static function () use ($failure): iterable {
            yield new DrawRecord('a', '2026-01-01', ['main' => [1, 2, 3, 4, 5, 6]]);
            throw $failure;
        })());

        try {
            (new DrawImporter())->import(GameRules::lotto(), $source);
            self::fail('Expected source failure.');
        } catch (ImportException $exception) {
            self::assertStringContainsString('record 2', $exception->getMessage());
            self::assertSame($failure, $exception->getPrevious());
        }
    }

    public function testNativeSourceFailurePropagatesWithoutBeingReclassified(): void
    {
        $failure = new \RuntimeException('Transport failure.');
        $source = new InMemoryDrawSource((static function () use ($failure): iterable {
            yield new DrawRecord('a', '2026-01-01', ['main' => [1, 2, 3, 4, 5, 6]]);
            throw $failure;
        })());
        $this->expectExceptionObject($failure);

        (new DrawImporter())->import(GameRules::lotto(), $source);
    }

    public function testEmptyTypedSourcePreservesTheGame(): void
    {
        $game = GameRules::eurojackpot();
        $draws = (new DrawImporter())->import($game, new InMemoryDrawSource([]));

        self::assertCount(0, $draws);
        self::assertSame($game, $draws->game);
        self::assertNull($draws->latest());
    }
}
