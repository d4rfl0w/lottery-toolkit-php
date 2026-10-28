<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rupcom\LotteryToolkit\{DrawImporter, GameRules, NumberPool};
use Rupcom\LotteryToolkit\Exception\ImportException;

final class ImportTest extends TestCase
{
    public function testEquivalentArrayJsonAndCsvImports(): void
    {
        $game = GameRules::lotto();
        $rows = [['id' => 'a', 'date' => '2026-01-01', 'numbers' => ['main' => [1, 2, 3, 4, 5, 6]]]];
        $importer = new DrawImporter();
        $array = $importer->fromArray($game, (static function () use ($rows) {
            yield from $rows;
        })());
        self::assertEquals($array, $importer->fromJson($game, json_encode($rows)));
        self::assertEquals($array, $importer->fromCsv($game, "\xEF\xBB\xBFmain,date,id\r\n\"1 2 3 4 5 6\",2026-01-01,a\r\n\r\n"));
        self::assertEquals($array, $importer->fromCsv($game, "id;date;main\na;2026-01-01;1 2 3 4 5 6", ';'));
        self::assertCount(0, $importer->fromJson($game, '[]'));
        self::assertCount(0, $importer->fromCsv($game, 'id,date,main'));
    }

    public function testQuotedMultilineFieldsAndEuroPools(): void
    {
        $draws = (new DrawImporter())->fromCsv(GameRules::eurojackpot(), "id,date,main,euro\n\"a,\"\"b\"\"\nc\",2026-01-01T01:00:00+01:00,1 2 3 4 5,11 12\n");
        self::assertSame("a,\"b\"\nc", $draws->latest()->id);
        self::assertSame([11, 12], $draws->latest()->numbers['euro']);
        self::assertSame('2026-01-01T00:00:00+00:00', $draws->latest()->date->format(DATE_ATOM));
    }

    #[DataProvider('invalidJson')]
    public function testInvalidJson(string $json): void
    {
        $this->expectException(ImportException::class);
        (new DrawImporter())->fromJson(GameRules::lotto(), $json);
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
        (new DrawImporter())->fromCsv(GameRules::lotto(), $csv, $delimiter);
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
        (new DrawImporter())->fromArray(GameRules::lotto(), [['id' => 'a', 'date' => $date, 'numbers' => ['main' => [1, 2, 3, 4, 5, 6]]]]);
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
                $draws = (new DrawImporter())->fromArray(GameRules::lotto(), [['id' => 'a', 'date' => $date, 'numbers' => ['main' => [1, 2, 3, 4, 5, 6]]]]);
                self::assertSame('2024-02-29T00:00:00+00:00', $draws->latest()->date->format(DATE_ATOM));
            }
        } finally {
            date_default_timezone_set($original);
        }
    }

    #[DataProvider('invalidPhpRecords')]
    public function testMalformedPhpRecordsProduceImportExceptions(mixed $row): void
    {
        $this->expectException(ImportException::class);
        (new DrawImporter())->fromArray(GameRules::lotto(), [$row]);
    }

    public static function invalidPhpRecords(): iterable
    {
        yield [null];
        yield ['not a record'];
        yield [['id' => 'a', 'date' => '2026-01-01', 'numbers' => ['main' => [1, 2, 3, 4, 5, 6]], 'unexpected' => true]];
    }

    public function testDuplicateIdsFailAtomicallyAndPreserveCause(): void
    {
        $row = ['id' => 'a', 'date' => '2026-01-01', 'numbers' => ['main' => [1, 2, 3, 4, 5, 6]]];
        try {
            (new DrawImporter())->fromArray(GameRules::lotto(), [$row, $row]);
            self::fail('Expected duplicate rejection.');
        } catch (ImportException $exception) {
            self::assertStringContainsString('record 2', $exception->getMessage());
            self::assertNotNull($exception->getPrevious());
        }
    }
}
