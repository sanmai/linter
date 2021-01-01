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

namespace Tests\Linter;

use Linter\StringLinter;
use PHPUnit\Framework\TestCase;
use function Pipeline\fromArray;

/**
 * @covers \Linter\StringLinter
 */
final class LinterTest extends TestCase
{
    const MAX_FILES_LIMIT = 30;

    /**
     * @dataProvider provider
     */
    public function testLinter(string $input, bool $expected)
    {
        $linter = new StringLinter($input);

        $this->assertSame($expected, $linter->foundErrors());
        $this->assertSame($expected, $linter->foundErrors());
        $this->assertSame($expected, !$linter->valid());
    }

    public function provider()
    {
        yield ['<?php return 1;', false];
        yield ['<?php yield true;', true];
        yield ['<?php var_dump(true); var_dump(true)', true];
    }

    /**
     * @runInSeparateProcess
     */
    public function testFailWithoutPHP()
    {
        putenv('PATH=');

        $linter = new StringLinter('<?php return 1;');
        $this->assertTrue($linter->foundErrors());
        $this->assertFalse($linter->valid());
    }

    /**
     * @runInSeparateProcess
     */
    public function testFailWithoutProcess()
    {
        if ('' === shell_exec('command -v prlimit')) {
            $this->markTestIncomplete('Please install prlimit');
        }

        // Preload most classes needed for the test
        $linter = new StringLinter('<?php return 1;');

        // Set the number of open files to some sane value
        exec(sprintf('prlimit --pid %d --nofile=%2$d:%2$d', getmypid(), self::MAX_FILES_LIMIT));
        // To debug: passthru('ulimit -a');

        // Use up most of our limit
        $files = fromArray(range(0, self::MAX_FILES_LIMIT))->map(function () {
            return @tmpfile();
        })->filter()->toArray();

        // ...Until we hit the sweet spot where linter fails at the right moment
        $linter->foundErrors();

        // Close all extra files we opened
        fromArray($files)->map('fclose')->reduce();

        $this->assertTrue($linter->foundErrors(), "Should fail when can't start a process");
    }

    /**
     * @runInSeparateProcess
     */
    public function testFailingProcOpen()
    {
        if (!function_exists('runkit_function_redefine')) {
            $this->markTestIncomplete('pecl install runkit');
        }

        runkit_function_redefine('proc_open', '', 'return false;');

        $linter = new StringLinter('<?php return 1;');

        $this->assertTrue($linter->foundErrors(), 'Should fail when proc_open() returns false');
    }
}
