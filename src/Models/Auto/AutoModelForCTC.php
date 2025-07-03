<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

use Codewithkyrian\Transformers\Models\Pretrained\Wav2Vec2ForCTC;

class AutoModelForCTC extends PretrainedMixin
{
    public const MODEL_CLASS_MAPPING = [
        'wav2vec2' => Wav2Vec2ForCTC::class,
    ];

    public const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}
