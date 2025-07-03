<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

use Codewithkyrian\Transformers\Models\Pretrained\BertForTokenClassification;
use Codewithkyrian\Transformers\Models\Pretrained\DebertaForTokenClassification;
use Codewithkyrian\Transformers\Models\Pretrained\DebertaV2ForTokenClassification;
use Codewithkyrian\Transformers\Models\Pretrained\RobertaForTokenClassification;
use Codewithkyrian\Transformers\Models\Pretrained\RoFormerForTokenClassification;

class AutoModelForTokenClassification extends PretrainedMixin
{
    public const MODEL_CLASS_MAPPING = [
        "bert" => BertForTokenClassification::class,
        "deberta" => DebertaForTokenClassification::class,
        "deberta-v2" => DebertaV2ForTokenClassification::class,
        "roberta" => RobertaForTokenClassification::class,
        'roformer' => RoFormerForTokenClassification::class,
    ];

    public const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}
