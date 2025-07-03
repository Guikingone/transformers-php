<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

use Codewithkyrian\Transformers\Models\Pretrained\CodeGenForCausalLM;
use Codewithkyrian\Transformers\Models\Pretrained\GPT2LMHeadModel;
use Codewithkyrian\Transformers\Models\Pretrained\GPTBigCodeForCausalLM;
use Codewithkyrian\Transformers\Models\Pretrained\GPTJForCausalLM;
use Codewithkyrian\Transformers\Models\Pretrained\LlamaForCausalLM;
use Codewithkyrian\Transformers\Models\Pretrained\Qwen2ForCausalLM;
use Codewithkyrian\Transformers\Models\Pretrained\TrOCRForCausalLM;

class AutoModelForCausalLM extends PretrainedMixin
{
    public const MODEL_CLASS_MAPPING = [
        'gpt2' => GPT2LMHeadModel::class,
        'gptj' => GPTJForCausalLM::class,
        'gpt_bigcode' => GPTBigCodeForCausalLM::class,
        'codegen' => CodeGenForCausalLM::class,
        'llama' => LlamaForCausalLM::class,
        'trocr' => TrOCRForCausalLM::class,
        'qwen2' => Qwen2ForCausalLM::class,
    ];

    public const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}
