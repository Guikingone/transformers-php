<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

use Codewithkyrian\Transformers\Models\Pretrained\BartForConditionalGeneration;
use Codewithkyrian\Transformers\Models\Pretrained\M2M100ForConditionalGeneration;
use Codewithkyrian\Transformers\Models\Pretrained\T5ForConditionalGeneration;

class AutoModelForSeq2SeqLM extends PretrainedMixin
{
    public const MODEL_CLASS_MAPPING = [
        'bart' => BartForConditionalGeneration::class,
        't5' => T5ForConditionalGeneration::class,
        'm2m_100' => M2M100ForConditionalGeneration::class,
    ];

    public const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}
