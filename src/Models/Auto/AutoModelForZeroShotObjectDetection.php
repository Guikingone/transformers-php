<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

use Codewithkyrian\Transformers\Models\Pretrained\Owlv2ForObjectDetection;
use Codewithkyrian\Transformers\Models\Pretrained\OwlViTForObjectDetection;

class AutoModelForZeroShotObjectDetection extends PretrainedMixin
{
    public const MODEL_CLASS_MAPPING = [
        'owlvit' => OwlViTForObjectDetection::class,
        'owlv2' => Owlv2ForObjectDetection::class,
    ];

    public const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];

}
