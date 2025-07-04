<?php

declare(strict_types=1);

namespace Transformers\Pipelines;

use Codewithkyrian\Transformers\Pipelines\Task;

/**
 * @internal
 *
 * @coversNothing
 */
final class TextToSpeechTaskTest extends AbstractTaskTestCase
{
    protected function getTask(): Task
    {
        return Task::tryFrom('text-to-speech');
    }

    protected function getDefaultModelName(): string
    {
        return 'Xenova/speecht5_hifigan';
    }
}
