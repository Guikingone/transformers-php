<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Output;

use Codewithkyrian\Transformers\Tensor\Tensor;

final class VitsModelOutput implements ModelOutput
{
    private Tensor $waveform;
    private Tensor $spectrogram;

    public function __construct(
        Tensor $waveform,
        Tensor $spectrogram,
    ) {
        $this->waveform = $waveform;
        $this->spectrogram = $spectrogram;
    }

    public function waveform(): Tensor
    {
        return $this->waveform;
    }

    public function spectrogram(): Tensor
    {
        return $this->spectrogram;
    }
}
