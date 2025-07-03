<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

use Codewithkyrian\Transformers\Models\Pretrained\WhisperForConditionalGeneration;

class AutoModelForSpeechSeq2Seq extends PretrainedMixin
{
    public const MODEL_CLASS_MAPPING = [
        'whisper' => WhisperForConditionalGeneration::class,
    ];

    public const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}
