<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

use Codewithkyrian\Transformers\Models\Pretrained\DetrForObjectDetection;
use Codewithkyrian\Transformers\Models\Pretrained\YolosForObjectDetection;

class AutoModelForObjectDetection extends PretrainedMixin
{
    public const MODEL_CLASS_MAPPING = [
        'detr' => DetrForObjectDetection::class,
        'yolos' => YolosForObjectDetection::class,
    ];

    public const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}
