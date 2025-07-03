<?php

declare(strict_types=1);

use Codewithkyrian\Transformers\FFI\Libc;
use Codewithkyrian\Transformers\FFI\OnnxRuntime;
use Codewithkyrian\Transformers\FFI\Samplerate;
use Codewithkyrian\Transformers\FFI\Sndfile;
use Codewithkyrian\Transformers\FFI\TransformersUtils;

include __DIR__ . '/../vendor/autoload.php';

dd(
    Libc::version(),
    Sndfile::version(),
    Samplerate::version(),
    OnnxRuntime::version(),
    TransformersUtils::version(),
);
