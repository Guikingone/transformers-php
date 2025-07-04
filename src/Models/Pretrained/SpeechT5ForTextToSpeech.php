<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Tensor\Tensor;

final class SpeechT5ForTextToSpeech extends SpeechT5PreTrainedModel
{
    public function generateSpeech(Tensor $values) {}
}
