<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Rupcom\LotteryToolkit\{Draw, DrawCollection, DrawImporter, FrequencyOrder, GameRegistry, GameRules, LotteryTicket, NumberGenerator, NumberPool, StatisticsAnalyzer, TicketChecker};
use Rupcom\LotteryToolkit\Exception\{GameMismatchException, ValidationException};

final class CoreTest extends TestCase
{
    public function testPresetsAndRegistry(): void
    {
        self::assertSame(49, GameRules::lotto()->pool('main')->max);
        self::assertSame(5, GameRules::miniLotto()->pool('main')->count);
        self::assertSame(12, GameRules::eurojackpot()->pool('euro')->max);
        $replacement = new GameRules('lotto', [new NumberPool('main', 2, 1, 10)]);
        $registry = new GameRegistry([$replacement]);
        self::assertSame($replacement, $registry->get('lotto'));
        self::assertCount(3, $registry->all());
    }

    public function testCanonicalValuesAndImmutability(): void
    {
        $rules = GameRules::lotto();
        $numbers = ['main' => [6, 1, 4, 2, 5, 3]];
        $draw = new Draw($rules, 'a', new \DateTimeImmutable('2026-01-02T01:00:00+01:00'), $numbers);
        $numbers['main'][0] = 49;
        self::assertSame([1, 2, 3, 4, 5, 6], $draw->numbers['main']);
        self::assertSame('2026-01-02T00:00:00+00:00', $draw->date->format(DATE_ATOM));
        $this->expectException(\Error::class);
        $draw->numbers['main'][0] = 49;
    }

    public function testGameEqualityUsesCompleteRulesAndIgnoresPoolOrder(): void
    {
        $euro = GameRules::eurojackpot();
        self::assertTrue($euro->equals(new GameRules('eurojackpot', array_reverse(array_values($euro->pools)))));
        self::assertFalse($euro->equals(new GameRules('eurojackpot', [new NumberPool('main', 5, 1, 50)])));
    }

    #[DataProvider('invalidPools')]
    public function testInvalidPoolDefinitions(string $name, int $count, int $min, int $max): void
    {
        $this->expectException(ValidationException::class);
        new NumberPool($name, $count, $min, $max);
    }

    public static function invalidPools(): iterable
    {
        yield ['main', 0, 1, 49];
        yield ['main', 101, 1, 200];
        yield ['main', 6, 1, 5];
        yield ['main', 1, 0, 5];
        yield ['main', 1, 5, 4];
        yield ['main', 1, 1, 10001];
        yield ['main', 1, 1000001, 1000001];
        yield ['Main', 1, 1, 5];
        yield ['id', 1, 1, 5];
        yield ['date', 1, 1, 5];
    }

    #[DataProvider('invalidNumbers')]
    public function testInvalidNumbers(array $numbers): void
    {
        $this->expectException(ValidationException::class);
        new LotteryTicket(GameRules::lotto(), [$numbers]);
    }

    public static function invalidNumbers(): iterable
    {
        yield [['main' => [1, 2, 3, 4, 5]]];
        yield [['main' => [1, 2, 3, 4, 5, 5]]];
        yield [['main' => [0, 2, 3, 4, 5, 6]]];
        yield [['main' => [50, 2, 3, 4, 5, 6]]];
        yield [['main' => ['1', 2, 3, 4, 5, 6]]];
        yield [['main' => [1.0, 2, 3, 4, 5, 6]]];
        yield [['main' => [true, 2, 3, 4, 5, 6]]];
        yield [['main' => null]];
        yield [['main' => [1, 2, 3, 4, 5, 6], 'extra' => []]];
        yield [['wrong' => [1, 2, 3, 4, 5, 6]]];
        yield [['main' => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6]]];
    }

    #[DataProvider('invalidOperations')]
    public function testDomainExceptions(\Closure $operation): void
    {
        $this->expectException(ValidationException::class);
        $operation();
    }

    public static function invalidOperations(): iterable
    {
        yield [static fn () => new GameRules('bad-id', [new NumberPool('main', 1, 1, 2)])];
        yield [static fn () => new GameRules('empty', [])];
        yield [static fn () => new GameRules('repeat', [new NumberPool('main', 1, 1, 2), new NumberPool('main', 1, 1, 2)])];
        yield [static fn () => new LotteryTicket(GameRules::lotto(), [])];
        yield [static fn () => new LotteryTicket(GameRules::lotto(), [null])];
        yield [static fn () => new LotteryTicket(GameRules::lotto(), ['bet' => ['main' => [1, 2, 3, 4, 5, 6]]])];
        yield [static fn () => GameRules::lotto()->pool('unknown')];
        yield [static fn () => (new GameRegistry())->get('unknown')];
        yield [static fn () => new Draw(GameRules::lotto(), ' ', new \DateTimeImmutable(), ['main' => [1, 2, 3, 4, 5, 6]])];
        yield [static fn () => (new DrawCollection(GameRules::lotto()))->last(0)];
        yield [static fn () => (new DrawCollection(GameRules::lotto()))->between(new \DateTimeImmutable('2026-02-01'), new \DateTimeImmutable('2026-01-01'))];
        yield [static fn () => (new NumberGenerator())->generate(GameRules::lotto(), 0)];
        yield [static fn () => (new NumberGenerator())->generate(GameRules::lotto(), 10001)];
        yield [static fn () => (new StatisticsAnalyzer())->analyze(new DrawCollection(GameRules::lotto()))->mostFrequent(0)];
        yield [static fn () => (new StatisticsAnalyzer())->analyze(new DrawCollection(GameRules::lotto()))->mostFrequentPairs(0)];
    }

    public function testFilteringAndChronologicalOrdering(): void
    {
        $game = new GameRules('demo', [new NumberPool('main', 1, 1, 9)]);
        $draws = (new DrawImporter())->fromArray($game, [
            ['id' => 'b', 'date' => '2026-01-02', 'numbers' => ['main' => [2]]],
            ['id' => 'a', 'date' => '2026-01-01', 'numbers' => ['main' => [1]]],
            ['id' => 'c', 'date' => '2026-01-02', 'numbers' => ['main' => [3]]],
        ]);
        self::assertSame(['a', 'b', 'c'], array_map(static fn (Draw $d) => $d->id, $draws->toArray()));
        self::assertSame('c', $draws->latest()->id);
        self::assertCount(2, $draws->last(2));
        self::assertCount(3, $draws->last(99));
        self::assertCount(2, $draws->between(new \DateTimeImmutable('2026-01-02T00:00:00Z'), new \DateTimeImmutable('2026-01-02T01:00:00+01:00')));
        self::assertCount(3, $draws->between());
        self::assertCount(1, $draws->between(to: new \DateTimeImmutable('2026-01-01T00:00:00Z')));
        self::assertCount(0, $draws->between(new \DateTimeImmutable('2027-01-01')));
        self::assertNull((new DrawCollection($game))->latest());
        self::assertCount(3, $draws);
    }

    public function testCollectionRejectsDuplicateIds(): void
    {
        $game = GameRules::lotto();
        $draw = new Draw($game, 'same', new \DateTimeImmutable(), ['main' => [1, 2, 3, 4, 5, 6]]);
        $this->expectException(ValidationException::class);
        new DrawCollection($game, [$draw, $draw]);
    }

    public function testCollectionRejectsMismatchedGame(): void
    {
        $draw = new Draw(GameRules::miniLotto(), 'mini', new \DateTimeImmutable(), ['main' => [1, 2, 3, 4, 5]]);
        $this->expectException(GameMismatchException::class);
        new DrawCollection(GameRules::lotto(), [$draw]);
    }

    public function testMultipleBetsAndIndependentPools(): void
    {
        $game = GameRules::eurojackpot();
        $draw = new Draw($game, 'ej', new \DateTimeImmutable(), ['main' => [1, 2, 3, 4, 5], 'euro' => [2, 3]]);
        $ticket = new LotteryTicket($game, [
            ['main' => [1, 2, 3, 4, 5], 'euro' => [1, 2]],
            ['main' => [6, 7, 8, 9, 10], 'euro' => [3, 4]],
        ]);
        $matches = (new TicketChecker())->check($ticket, $draw);
        self::assertCount(2, $matches);
        self::assertSame(5, $matches[0]->count());
        self::assertSame(1, $matches[0]->count('euro'));
        self::assertSame(6, $matches[0]->total());
        self::assertSame(0, $matches[1]->count());
        self::assertSame(1, $matches[1]->betIndex);
        self::assertSame('ej', json_decode(json_encode($matches, JSON_THROW_ON_ERROR), true)[0]['drawId']);
        $this->expectException(ValidationException::class);
        $matches[0]->count('unknown');
    }

    public function testTicketCheckerRejectsSameIdWithDifferentRules(): void
    {
        $other = new GameRules('lotto', [new NumberPool('main', 6, 1, 50)]);
        $draw = new Draw($other, 'x', new \DateTimeImmutable(), ['main' => [1, 2, 3, 4, 5, 6]]);
        $ticket = new LotteryTicket(GameRules::lotto(), [['main' => [1, 2, 3, 4, 5, 6]]]);
        $this->expectException(GameMismatchException::class);
        (new TicketChecker())->check($ticket, $draw);
    }

    public function testRepeatedNumbersUseMultisetMatching(): void
    {
        $game = new GameRules('repeat', [new NumberPool('main', 3, 1, 2, true)]);
        $draw = new Draw($game, 'x', new \DateTimeImmutable(), ['main' => [1, 2, 2]]);
        $ticket = new LotteryTicket($game, [['main' => [1, 1, 2]]]);
        self::assertSame([1, 2], (new TicketChecker())->check($ticket, $draw)[0]->matchedNumbers['main']);
    }

    public function testGeneratorValidatesAllGamesAndIsReproducibleWhenInjected(): void
    {
        foreach ((new GameRegistry())->all() as $game) {
            $ticket = (new NumberGenerator())->generate($game, 50);
            self::assertCount(50, $ticket->bets);
            foreach ($ticket->bets as $bet) {
                self::assertSame($bet, $game->validate($bet));
            }
        }
        $a = new NumberGenerator(new Randomizer(new Mt19937(42)));
        $b = new NumberGenerator(new Randomizer(new Mt19937(42)));
        self::assertSame($a->generate(GameRules::lotto(), 3)->bets, $b->generate(GameRules::lotto(), 3)->bets);
        $repeat = new GameRules('repeat', [new NumberPool('main', 3, 2, 2, true)]);
        self::assertSame([['main' => [2, 2, 2]]], (new NumberGenerator())->generate($repeat)->bets);
        $full = new GameRules('full', [new NumberPool('main', 3, 10, 12)]);
        self::assertSame([['main' => [10, 11, 12]]], (new NumberGenerator())->generate($full)->bets);
    }
}
