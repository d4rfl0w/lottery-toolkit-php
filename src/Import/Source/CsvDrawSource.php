<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Import\Source;

use Rupcom\LotteryToolkit\Exception\ImportException;
use Rupcom\LotteryToolkit\Game\GameRules;
use Rupcom\LotteryToolkit\Import\DrawRecord;
use Rupcom\LotteryToolkit\Import\DrawSourceInterface;
use Rupcom\LotteryToolkit\Import\Internal\CsvSyntaxValidator;

/** CSV contents with headers id,date,<pool names> and space-separated numbers. */
final readonly class CsvDrawSource implements DrawSourceInterface
{
    public function __construct(private string $csv, private string $delimiter = ',')
    {
        if (strlen($delimiter) !== 1 || in_array($delimiter, ["\r", "\n", '"', "\0", ' '], true)) {
            throw new ImportException('CSV delimiter must be one non-space byte other than quote, newline or NUL.');
        }
    }

    public function records(GameRules $game): iterable
    {
        $csv = str_starts_with($this->csv, "\xEF\xBB\xBF") ? substr($this->csv, 3) : $this->csv;
        (new CsvSyntaxValidator())->validate($csv, $this->delimiter);
        $stream = fopen('php://temp', 'w+');
        if ($stream === false) {
            throw new ImportException('Cannot allocate CSV parsing buffer.');
        }

        try {
            if (fwrite($stream, $csv) !== strlen($csv) || !rewind($stream)) {
                throw new ImportException('Cannot write CSV parsing buffer.');
            }

            $header = $this->readHeader($stream, $game);
            $line = 1;
            while (($cells = fgetcsv($stream, separator: $this->delimiter, escape: '')) !== false) {
                ++$line;
                if ($cells === [null]) {
                    continue;
                }
                if (count($cells) !== count($header)) {
                    throw new ImportException("CSV record $line has an incorrect number of columns.");
                }

                $row = array_combine($header, $cells);
                $numbers = [];
                foreach ($game->pools as $name => $pool) {
                    $numbers[$name] = $this->parseNumbers($row[$name] ?? '', $line, $name);
                }

                yield new DrawRecord($row['id'] ?? '', $row['date'] ?? '', $numbers);
            }
        } finally {
            fclose($stream);
        }
    }

    /**
     * @param resource $stream
     * @return non-empty-list<string>
     */
    private function readHeader($stream, GameRules $game): array
    {
        $header = fgetcsv($stream, separator: $this->delimiter, escape: '');
        $expected = ['id', 'date', ...array_keys($game->pools)];
        if ($header === false || count($header) !== count($expected)
            || count(array_unique($header)) !== count($expected) || array_diff($expected, $header) !== []) {
            throw new ImportException('CSV requires exactly these unique headers: '.implode(', ', $expected).'.');
        }
        foreach ($header as $column) {
            if (!is_string($column)) {
                throw new ImportException('CSV header names must be strings.');
            }
        }

        return $header;
    }

    /** @return list<int> */
    private function parseNumbers(string $cell, int $line, string $pool): array
    {
        $numbers = [];
        foreach (explode(' ', trim($cell)) as $token) {
            if (!preg_match('/^[1-9][0-9]*$/D', $token) || (string) (int) $token !== $token) {
                throw new ImportException("CSV record $line, pool $pool: expected space-separated positive integers.");
            }
            $numbers[] = (int) $token;
        }

        return $numbers;
    }
}
