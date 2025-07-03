<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

use Codewithkyrian\Transformers\Models\Pretrained\AlbertForQuestionAnswering;
use Codewithkyrian\Transformers\Models\Pretrained\BertForQuestionAnswering;
use Codewithkyrian\Transformers\Models\Pretrained\DebertaForQuestionAnswering;
use Codewithkyrian\Transformers\Models\Pretrained\DebertaV2ForQuestionAnswering;
use Codewithkyrian\Transformers\Models\Pretrained\DistilBertForQuestionAnswering;
use Codewithkyrian\Transformers\Models\Pretrained\MobileBertForQuestionAnswering;
use Codewithkyrian\Transformers\Models\Pretrained\RobertaForQuestionAnswering;
use Codewithkyrian\Transformers\Models\Pretrained\RoFormerForQuestionAnswering;

class AutoModelForQuestionAnswering extends PretrainedMixin
{
    public const MODEL_CLASS_MAPPING = [
        'albert' => AlbertForQuestionAnswering::class,
        'bert' => BertForQuestionAnswering::class,
        'deberta' => DebertaForQuestionAnswering::class,
        'deberta-v2' => DebertaV2ForQuestionAnswering::class,
        'distilbert' => DistilBertForQuestionAnswering::class,
        'mobilebert' => MobileBertForQuestionAnswering::class,
        'roberta' => RobertaForQuestionAnswering::class,
        'roformer' => RoFormerForQuestionAnswering::class,
    ];

    public const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}
