<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Processors\Processor;
use Codewithkyrian\Transformers\Tensor\Tensor;
use Codewithkyrian\Transformers\Utils\RawAudio;

final class TextToAudioPipeline extends Pipeline
{
    public function __invoke(array|string $inputs, ...$args): RawAudio
    {
        return $this->processor instanceof Processor
            ? $this->callTextToSpectrogram($inputs, $args)
            : $this->callTextToWaveForm($inputs);
    }

    private function callTextToWaveForm(array|string $inputs): RawAudio
    {
        $inputs = $this->tokenizer->tokenize(
            text: $inputs,
            padding: true,
            truncation: true,
        );

        $waveform = $this->model->generate(Tensor::fromArray($inputs));

        return new RawAudio(
            audio: $waveform,
            samplingRate: $this->model->config['samplingRate'],
        );
    }

    private function callTextToSpectrogram(array|string $inputs, ...$args): RawAudio
    {

    }
}
