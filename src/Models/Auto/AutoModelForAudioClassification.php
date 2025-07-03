<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

use Codewithkyrian\Transformers\Models\Pretrained\ASTForAudioClassification;
use Codewithkyrian\Transformers\Models\Pretrained\Wav2Vec2ForSequenceClassification;

class AutoModelForAudioClassification extends PretrainedMixin
{
    public const MODEL_CLASS_MAPPING = [
        'audio-spectrogram-transformer' => ASTForAudioClassification::class,
        'wav2vec2' => Wav2Vec2ForSequenceClassification::class,
    ];

    public const MODEL_CLASS_MAPPINGS = [
        self::MODEL_CLASS_MAPPING,
    ];
}
