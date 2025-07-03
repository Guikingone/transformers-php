<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

use Codewithkyrian\Transformers\Models\Pretrained\CLIPVisionModelWithProjection;
use Codewithkyrian\Transformers\Models\Pretrained\SiglipVisionModel;

class AutoModelForImageFeatureExtraction extends PretrainedMixin
{
    public const MODEL_CLASS_MAPPING = [
        'clip' => CLIPVisionModelWithProjection::class,
        'siglip' => SiglipVisionModel::class,
    ];

    public const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
        AutoModel::ENCODER_ONLY_MODEL_MAPPING,
        AutoModel::DECODER_ONLY_MODEL_MAPPING,
    ];
}
