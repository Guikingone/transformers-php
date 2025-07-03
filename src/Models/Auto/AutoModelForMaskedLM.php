<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

use Codewithkyrian\Transformers\Models\Pretrained\AlbertForMaskedLM;
use Codewithkyrian\Transformers\Models\Pretrained\BertForMaskedLM;
use Codewithkyrian\Transformers\Models\Pretrained\DebertaForMaskedLM;
use Codewithkyrian\Transformers\Models\Pretrained\DebertaV2ForMaskedLM;
use Codewithkyrian\Transformers\Models\Pretrained\DistilBertForMaskedLM;
use Codewithkyrian\Transformers\Models\Pretrained\MobileBertForMaskedLM;
use Codewithkyrian\Transformers\Models\Pretrained\RobertaForMaskedLM;
use Codewithkyrian\Transformers\Models\Pretrained\RoFormerForMaskedLM;

class AutoModelForMaskedLM extends PretrainedMixin
{
    public const MODEL_CLASS_MAPPING = [
        "albert" => AlbertForMaskedLM::class,
        "bert" => BertForMaskedLM::class,
        "deberta" => DebertaForMaskedLM::class,
        "deberta-v2" => DebertaV2ForMaskedLM::class,
        "distilbert" => DistilBertForMaskedLM::class,
        "mobilebert" => MobileBertForMaskedLM::class,
        "roberta" => RobertaForMaskedLM::class,
        "roformer" => RoFormerForMaskedLM::class,
    ];

    public const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}
