<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

use Codewithkyrian\Transformers\Models\Pretrained\AlbertModel;
use Codewithkyrian\Transformers\Models\Pretrained\ASTModel;
use Codewithkyrian\Transformers\Models\Pretrained\BartModel;
use Codewithkyrian\Transformers\Models\Pretrained\BertModel;
use Codewithkyrian\Transformers\Models\Pretrained\CLIPModel;
use Codewithkyrian\Transformers\Models\Pretrained\CodeGenModel;
use Codewithkyrian\Transformers\Models\Pretrained\DebertaModel;
use Codewithkyrian\Transformers\Models\Pretrained\DebertaV2Model;
use Codewithkyrian\Transformers\Models\Pretrained\DeiTModel;
use Codewithkyrian\Transformers\Models\Pretrained\DETRModel;
use Codewithkyrian\Transformers\Models\Pretrained\DistilBertModel;
use Codewithkyrian\Transformers\Models\Pretrained\GPT2Model;
use Codewithkyrian\Transformers\Models\Pretrained\GPTBigCodeModel;
use Codewithkyrian\Transformers\Models\Pretrained\GPTJModel;
use Codewithkyrian\Transformers\Models\Pretrained\LlamaModel;
use Codewithkyrian\Transformers\Models\Pretrained\M2M100Model;
use Codewithkyrian\Transformers\Models\Pretrained\MobileBertModel;
use Codewithkyrian\Transformers\Models\Pretrained\OwlV2Model;
use Codewithkyrian\Transformers\Models\Pretrained\OwlVitModel;
use Codewithkyrian\Transformers\Models\Pretrained\Qwen2Model;
use Codewithkyrian\Transformers\Models\Pretrained\RobertaModel;
use Codewithkyrian\Transformers\Models\Pretrained\RoFormerModel;
use Codewithkyrian\Transformers\Models\Pretrained\SigLipModel;
use Codewithkyrian\Transformers\Models\Pretrained\Swin2SRModel;
use Codewithkyrian\Transformers\Models\Pretrained\T5Model;
use Codewithkyrian\Transformers\Models\Pretrained\ViTModel;
use Codewithkyrian\Transformers\Models\Pretrained\Wav2Vec2Model;
use Codewithkyrian\Transformers\Models\Pretrained\YOLOSModel;

class AutoModel extends PretrainedMixin
{
    public const ENCODER_ONLY_MODEL_MAPPING = [
        'albert' => AlbertModel::class,
        "bert" => BertModel::class,
        "distilbert" => DistilBertModel::class,
        "deberta" => DebertaModel::class,
        "deberta-v2" => DebertaV2Model::class,
        "mobilebert" => MobileBertModel::class,
        "roformer" => RoFormerModel::class,
        "roberta" => RobertaModel::class,
        "clip" => CLIPModel::class,
        "vit" => ViTModel::class,
        "deit" => DeiTModel::class,
        "siglip" => SigLipModel::class,

        "audio-spectrogram-transformer" => ASTModel::class,
        "wav2vec2" => Wav2Vec2Model::class,

        'detr' => DETRModel::class,
        'yolos' => YOLOSModel::class,
        'owlvit' => OwlVitModel::class,
        'owlv2' => OwlV2Model::class,
        'swin2sr' => Swin2SRModel::class,
    ];

    public const ENCODER_DECODER_MODEL_MAPPING = [
        "t5" => T5Model::class,
        "bart" => BartModel::class,
        "m2m_100" => M2M100Model::class,
    ];

    public const DECODER_ONLY_MODEL_MAPPING = [
        "gpt2" => GPT2Model::class,
        "gptj" => GPTJModel::class,
        "gpt_bigcode" => GPTBigCodeModel::class,
        "codegen" => CodeGenModel::class,
        "llama" => LlamaModel::class,
        "qwen2" => Qwen2Model::class,
    ];

    public const MODEL_CLASS_MAPPINGS = [
        self::ENCODER_ONLY_MODEL_MAPPING,
        self::ENCODER_DECODER_MODEL_MAPPING,
        self::DECODER_ONLY_MODEL_MAPPING,

        AutoModelForSequenceClassification::MODEL_CLASS_MAPPING,
        AutoModelForTokenClassification::MODEL_CLASS_MAPPING,
        AutoModelForSeq2SeqLM::MODEL_CLASS_MAPPING,
        AutoModelForCausalLM::MODEL_CLASS_MAPPING,
        AutoModelForMaskedLM::MODEL_CLASS_MAPPING,
        AutoModelForQuestionAnswering::MODEL_CLASS_MAPPING,
        AutoModelForImageClassification::MODEL_CLASS_MAPPING,
        AutoModelForVision2Seq::MODEL_CLASS_MAPPING,
        AutoModelForObjectDetection::MODEL_CLASS_MAPPING,
        AutoModelForZeroShotObjectDetection::MODEL_CLASS_MAPPING,
    ];

    public const BASE_IF_FAIL = true;
}
