<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit;

use Rupcom\LotteryToolkit\Exception\ImportException;
use Rupcom\LotteryToolkit\Exception\LotteryException;

/** Strict, atomic imports. No coercion of JSON/PHP values and no silently skipped records. */
final class DrawImporter
{
    /** @param iterable<mixed> $rows */
    public function fromArray(GameRules $game, iterable $rows): DrawCollection
    {
        $draws = [];
        $rowNumber = 0;
        try {
            foreach ($rows as $row) {
                ++$rowNumber;
                if (!is_array($row) || count($row) !== 3 || !is_string($row['id'] ?? null)
                    || !is_string($row['date'] ?? null) || !is_array($row['numbers'] ?? null)) {
                    throw new ImportException('Expected exactly id (string), date (string), numbers (pool map).');
                }
                $draws[] = new Draw($game, $row['id'], $this->parseDate($row['date']), $row['numbers']);
            }

            return new DrawCollection($game, $draws);
        } catch (LotteryException $exception) {
            throw new ImportException("Import failed at record $rowNumber: ".$exception->getMessage(), 0, $exception);
        }
    }

    public function fromJson(GameRules $game, string $json): DrawCollection
    {
        try {
            $rows = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ImportException('Invalid JSON: '.$exception->getMessage(), 0, $exception);
        }
        if (!is_array($rows) || !array_is_list($rows) || !str_starts_with(ltrim($json), '[')) {
            throw new ImportException('JSON must contain a top-level list of draw records.');
        }
        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                throw new ImportException('Expected an object at JSON record '.($index + 1).'.');
            }
        }

        return $this->fromArray($game, $rows);
    }

    /** CSV headers: id,date,<pool names>. Pool cells contain space-separated positive integers. */
    public function fromCsv(GameRules $game, string $csv, string $delimiter = ','): DrawCollection
    {
        if (strlen($delimiter) !== 1 || in_array($delimiter, ["\r", "\n", '"', "\0", ' '], true)) {
            throw new ImportException('CSV delimiter must be one non-space byte other than quote, newline or NUL.');
        }
        $csv = str_starts_with($csv, "\xEF\xBB\xBF") ? substr($csv, 3) : $csv;
        $this->validateCsvSyntax($csv, $delimiter);
        $stream = fopen('php://temp', 'w+');
        if ($stream === false) {
            throw new ImportException('Cannot allocate CSV parsing buffer.');
        }
        try {
            fwrite($stream, $csv);
            rewind($stream);
            $header = fgetcsv($stream, separator: $delimiter, escape: '');
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
            $rows = [];
            $line = 1;
            while (($cells = fgetcsv($stream, separator: $delimiter, escape: '')) !== false) {
                ++$line;
                if ($cells === [null]) {
                    continue;
                }
                if (count($cells) !== count($header)) {
                    throw new ImportException("CSV record $line has an incorrect number of columns.");
                }
                $record = array_combine($header, $cells);
                $numbers = [];
                foreach ($game->pools as $name => $pool) {
                    $tokens = explode(' ', trim($record[$name] ?? ''));
                    $numbers[$name] = [];
                    foreach ($tokens as $token) {
                        if (!preg_match('/^[1-9][0-9]*$/D', $token) || (string) (int) $token !== $token) {
                            throw new ImportException("CSV record $line, pool $name: expected space-separated positive integers.");
                        }
                        $numbers[$name][] = (int) $token;
                    }
                }
                $rows[] = ['id' => $record['id'], 'date' => $record['date'], 'numbers' => $numbers];
            }

            return $this->fromArray($game, $rows);
        } finally {
            fclose($stream);
        }
    }

    private function parseDate(string $value): \DateTimeImmutable
    {
        if (str_contains($value, "\0")) {
            throw new ImportException('Dates must not contain NUL bytes.');
        }
        $value = str_ends_with($value, 'Z') ? substr($value, 0, -1).'+00:00' : $value;
        $format = strlen($value) === 10 ? 'Y-m-d' : 'Y-m-d\TH:i:sP';
        $date = \DateTimeImmutable::createFromFormat('!'.$format, $value, new \DateTimeZone('UTC'));
        $errors = \DateTimeImmutable::getLastErrors();
        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format($format) !== $value || ($format !== 'Y-m-d' && !preg_match('/[+-](?:0[0-9]|1[0-4]):[0-5][0-9]$/D', $value))
            || preg_match('/[+-]14:(?!00)[0-9]{2}$/D', $value)) {
            throw new ImportException('Dates must be valid YYYY-MM-DD or RFC3339 timestamps with seconds and an explicit timezone (no fractions).');
        }

        return $date;
    }

    /** Reject malformed quoting instead of relying on fgetcsv's permissive recovery. */
    private function validateCsvSyntax(string $csv, string $delimiter): void
    {
        $quoted = false;
        $start = true;
        $closed = false;
        for ($i = 0, $length = strlen($csv); $i < $length; ++$i) {
            $char = $csv[$i];
            if ($quoted) {
                if ($char === '"') {
                    if (($csv[$i + 1] ?? null) === '"') {
                        ++$i;
                    } else {
                        $quoted = false;
                        $closed = true;
                    }
                }
                continue;
            }
            if ($char === $delimiter || $char === "\n" || $char === "\r") {
                $start = true;
                $closed = false;
            } elseif ($char === '"' && $start) {
                $quoted = true;
                $start = false;
            } elseif ($char === '"' || $closed) {
                throw new ImportException('Malformed CSV quoting. Use double quotes around complete fields and double embedded quotes.');
            } else {
                $start = false;
            }
        }
        if ($quoted) {
            throw new ImportException('Unterminated quoted CSV field.');
        }
    }
}
