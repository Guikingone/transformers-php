<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

use Codewithkyrian\Transformers\Models\Pretrained\Swin2SRForImageSuperResolution;

class AutoModelForImageToImage extends PretrainedMixin
{
    public const MODEL_CLASS_MAPPING = [
        'swin2sr' => Swin2SRForImageSuperResolution::class,
    ];

    public const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}
