<?php

declare(strict_types=1);

use function Codewithkyrian\Transformers\Pipelines\pipeline;

require_once __DIR__ . '/../bootstrap.php';

$synthesizer = pipeline('text-to-speech', 'Xenova/speecht5_tts');

$result1 = $synthesizer('I love transformers!');
