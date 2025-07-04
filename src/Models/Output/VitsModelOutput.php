<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Output;

use Codewithkyrian\Transformers\Tensor\Tensor;

final class VitsModelOutput implements ModelOutput
{
    public function __construct(
        public readonly Tensor $waveform,
        public readonly Tensor $spectrogram,
    ) {}
}
