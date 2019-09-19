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

/**
 * @covers \Linter\StringLinter
 */
final class LinterTest extends TestCase
{
    /**
     * @dataProvider provider
     */
    public function testLinter(string $input, bool $expected)
    {
        $linter = new StringLinter($input);

        $this->assertSame($expected, $linter->foundErrors());
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
    }

    /**
     * @runInSeparateProcess
     */
    public function testFailWithoutProcess()
    {
        if ('' === `command -v prlimit`) {
            $this->markTestIncomplete('Please install prlimit');
        }

        // Preload most classes needed for the test
        new StringLinter('');
        $this->assertTrue(true);

        foreach (range(20, 8, -1) as $limit) {
            // Continuously lock current process to less max files open, and less
            exec(sprintf('prlimit --pid %d --nofile=%d:%d', getmypid(), $limit, $limit));
            // To debug: passthru('ulimit -a');

            // ...Until we hit the sweet spot where linter fails at the right moment
            $linter = new StringLinter('<?php return 1;');
            if ($linter->foundErrors()) {
                return;
            }
        }

        $this->fail("Should fail when can't start a process");
    }
}
