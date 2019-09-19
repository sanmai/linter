<?php
/**
 * Copyright 2019 Alexey Kopytko <alexey@kopytko.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace Linter;

use Linter\Interfaces\Linter;

final class StringLinter implements Linter
{
    /**
     * @var string
     */
    private $sourceCode;

    /**
     * @var bool|null
     */
    private $codeIsCorrect;

    public function __construct(string $sourceCodeToLint)
    {
        $this->sourceCode = $sourceCodeToLint;
    }

    public function foundErrors(): bool
    {
        if (null === $this->codeIsCorrect) {
            $this->codeIsCorrect = self::proc_open_linter($this->sourceCode);
        }

        return !$this->codeIsCorrect;
    }

    private static function proc_open_linter(string $code): bool
    {
        // We have to override stdout and stderr here,
        // else they'll get connected to *our* stdin/stderr
        // flooding them with errors
        $process = @proc_open('php -l', [
            ['pipe', 'r'],
            ['pipe', 'w'],
            ['pipe', 'r'],
        ], $pipes);

        if (!is_resource($process)) {
            // Out of memory, etc
            return false;
        }

        // Pass our code
        fwrite($pipes[0], $code);
        fclose($pipes[0]);

        // We don't capture the output for time being - some older versions of PHP output nothing of interest
        stream_get_contents($pipes[1]);

        return 0 === proc_close($process);
    }
}
