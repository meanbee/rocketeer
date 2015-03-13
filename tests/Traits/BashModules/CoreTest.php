<?php

/*
 * This file is part of Rocketeer
 *
 * (c) Maxime Fabre <ehtnam6@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Rocketeer\Traits\BashModules;

use Mockery\MockInterface;
use Rocketeer\TestCases\RocketeerTestCase;

class CoreTest extends RocketeerTestCase
{
    public function testCanGetArraysFromRawCommands()
    {
        $contents = $this->task->runRaw('ls', true, true);

        $this->assertCount(static::$numberFiles, $contents);
    }

    public function testCanCheckStatusOfACommand()
    {
        $this->expectOutputRegex('/.+An error occured: "Oh noes", while running:\ngit clone.+/');

        $this->app['rocketeer.remote'] = clone $this->getRemote()->shouldReceive('status')->andReturn(1)->mock();
        $this->mockCommand([], [
            'line' => function ($error) {
                echo $error;
            },
        ]);

        $status = $this->task('Deploy')->checkStatus('Oh noes', 'git clone');

        $this->assertFalse($status);
    }

    public function testCheckStatusReturnsTrueSuccessful()
    {
        $this->assertTrue($this->pretendTask()->checkStatus('Oh noes'));
    }

    public function testCanGetTimestampOffServer()
    {
        $timestamp = $this->task->getTimestamp();

        $this->assertEquals(date('YmdHis'), $timestamp);
    }

    public function testCanGetLocalTimestampIfError()
    {
        $this->mockRemote('NOPE');
        $timestamp = $this->task->getTimestamp();

        $this->assertEquals(date('YmdHis'), $timestamp);
    }

    public function testCanLetFrameworkProcessCommands()
    {
        $this->connections->setStage('staging');
        $commands = $this->pretendTask()->processCommands([
            'artisan something',
            'rm readme*',
        ]);

        $this->assertEquals([
            'artisan something --env="staging"',
            'rm readme*',
        ], $commands);
    }

    public function testCanRemoveCommonPollutingOutput()
    {
        $this->mockRemote('stdin: is not a tty'.PHP_EOL.'something');
        $result = $this->bash->run('ls');

        $this->assertEquals('something', $result);
    }

    public function testCanRunCommandsLocally()
    {
        $this->mock('rocketeer.remote', 'Remote', function (MockInterface $mock) {
            return $mock->shouldReceive('run')->never();
        });

        $this->task->setLocal(true);
        $contents = $this->task->runRaw('ls', true, true);

        $this->assertCount(static::$numberFiles, $contents);
    }

    public function testCanConvertDirectorySeparators()
    {
        $this->mockConfig([
            'remote.variables.directory_separator' => '\\',
        ]);

        $commands  = 'cd C:/_bar?/12baz';
        $processed = $this->task->processCommands($commands);

        $this->assertEquals(['cd C:\_bar?\12baz'], $processed);
    }

    public function testDoesntConvertSlashesThatArentDirectorySeparators()
    {
        $this->mockConfig([
            'remote.variables.directory_separator' => '\\',
        ]);

        $commands  = 'find runtime -name "cache" -follow -exec rm -rf "{}" '.DS.';';
        $processed = $this->task->processCommands($commands);

        $this->assertEquals([$commands], $processed);
    }

    public function testShowsRawCommandsIfVerboseEnough()
    {
        $this->expectOutputString('<fg=magenta>$ ls</fg=magenta>');

        $this->mock('rocketeer.command', 'Command', function (MockInterface $mock) {
            $mock->shouldReceive('getOutput->getVerbosity')->andReturn(4)->mock();

            return $mock->shouldReceive('line')->andReturnUsing(function ($input) {
                echo $input;
            });
        });

        $this->bash->runRaw('ls');
    }

    public function testDoesntShowRawCommandsIfVerbosityNotHighEnough()
    {
        $this->expectOutputString('');

        $this->mock('rocketeer.command', 'Command', function (MockInterface $mock) {
            $mock->shouldReceive('getOutput->getVerbosity')->andReturn(1)->mock();

            return $mock->shouldReceive('line')->andReturnUsing(function ($input) {
                echo $input;
            });
        });

        $this->bash->runRaw('ls');
    }

    public function testCanFlattenCommands()
    {
        $commands = $this->pretendTask()->processCommands([
            ['foo', 'bar'],
            'baz',
        ]);

        $this->assertEquals(['foo', 'bar', 'baz'], $commands);
    }
}
