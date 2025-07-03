<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

use Codewithkyrian\Transformers\Models\Pretrained\VisionEncoderDecoderModel;

class AutoModelForVision2Seq extends PretrainedMixin
{
    public const MODEL_CLASS_MAPPING = [
        'vision-encoder-decoder' => VisionEncoderDecoderModel::class,
    ];

    public const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}
