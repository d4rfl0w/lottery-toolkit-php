<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Import\Internal;

use Rupcom\LotteryToolkit\Exception\ImportException;

/** @internal */
final class CsvSyntaxValidator
{
    /** Reject malformed quoting instead of relying on fgetcsv's permissive recovery. */
    public function validate(string $csv, string $delimiter): void
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
