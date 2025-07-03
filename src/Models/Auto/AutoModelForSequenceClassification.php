<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

use Codewithkyrian\Transformers\Models\Pretrained\AlbertForSequenceClassification;
use Codewithkyrian\Transformers\Models\Pretrained\BartForSequenceClassification;
use Codewithkyrian\Transformers\Models\Pretrained\BertForSequenceClassification;
use Codewithkyrian\Transformers\Models\Pretrained\DebertaForSequenceClassification;
use Codewithkyrian\Transformers\Models\Pretrained\DebertaV2ForSequenceClassification;
use Codewithkyrian\Transformers\Models\Pretrained\DistilBertForSequenceClassification;
use Codewithkyrian\Transformers\Models\Pretrained\MobileBertForSequenceClassification;
use Codewithkyrian\Transformers\Models\Pretrained\RobertaForSequenceClassification;
use Codewithkyrian\Transformers\Models\Pretrained\RoFormerForSequenceClassification;

class AutoModelForSequenceClassification extends PretrainedMixin
{
    public const MODEL_CLASS_MAPPING = [
        'albert' => AlbertForSequenceClassification::class,
        'bert' => BertForSequenceClassification::class,
        'bart' => BartForSequenceClassification::class,
        'deberta' => DebertaForSequenceClassification::class,
        'deberta-v2' => DebertaV2ForSequenceClassification::class,
        'distilbert' => DistilBertForSequenceClassification::class,
        'mobilebert' => MobileBertForSequenceClassification::class,
        'roberta' => RobertaForSequenceClassification::class,
        'roformer' => RoFormerForSequenceClassification::class,
    ];

    public const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}
