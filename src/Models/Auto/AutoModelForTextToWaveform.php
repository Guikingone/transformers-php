<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

final class AutoModelForTextToWaveform extends PretrainedMixin
{
    public const MODEL_CLASS_MAPPING = [
        'vits' => '',
    ];

    public const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}
