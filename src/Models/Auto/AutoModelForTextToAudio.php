<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

use Codewithkyrian\Transformers\Models\Pretrained\VitsModel;

final class AutoModelForTextToAudio extends PretrainedMixin
{
    public const MODEL_CLASS_MAPPING = [
        'vits' => VitsModel::class,
        'musicgen' => '',
    ];

    public const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}
