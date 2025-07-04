<?php

declare(strict_types=1);

namespace Transformers\Pipelines;

use Codewithkyrian\Transformers\Pipelines\Task;
use PHPUnit\Framework\TestCase;

abstract class AbstractTaskTestCase extends TestCase
{
    abstract protected function getTask(): Task;

    abstract protected function getDefaultModelName(): string;

    protected function testModelName(): void
    {
        $task = static::getTask();

        self::assertSame(static::getDefaultModelName(), $task->defaultModelName());
    }
}
