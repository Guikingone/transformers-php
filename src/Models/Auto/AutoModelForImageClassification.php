<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

use Codewithkyrian\Transformers\Models\Pretrained\DeiTForImageClassification;
use Codewithkyrian\Transformers\Models\Pretrained\ViTForImageClassification;

class AutoModelForImageClassification extends PretrainedMixin
{
    public const MODEL_CLASS_MAPPING = [
        'vit' => ViTForImageClassification::class,
        'deit' => DeiTForImageClassification::class,
    ];

    public const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}
