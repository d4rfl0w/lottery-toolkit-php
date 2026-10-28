<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Import\Internal;

use Rupcom\LotteryToolkit\Exception\ImportException;

/** @internal */
final class DrawDateParser
{
    private const INVALID_DATE = 'Dates must be valid YYYY-MM-DD or RFC3339 timestamps with seconds and an explicit timezone (no fractions).';

    public function parse(string $value): \DateTimeImmutable
    {
        if (str_contains($value, "\0")) {
            throw new ImportException('Dates must not contain NUL bytes.');
        }

        $value = str_ends_with($value, 'Z') ? substr($value, 0, -1).'+00:00' : $value;
        $format = strlen($value) === 10 ? 'Y-m-d' : 'Y-m-d\TH:i:sP';
        if ($format !== 'Y-m-d' && !preg_match('/[+-](?:(?:0[0-9]|1[0-3]):[0-5][0-9]|14:00)$/D', $value)) {
            throw new ImportException(self::INVALID_DATE);
        }

        $date = \DateTimeImmutable::createFromFormat('!'.$format, $value, new \DateTimeZone('UTC'));
        $errors = \DateTimeImmutable::getLastErrors();
        if ($date === false || $errors !== false || $date->format($format) !== $value) {
            throw new ImportException(self::INVALID_DATE);
        }

        return $date;
    }
}
