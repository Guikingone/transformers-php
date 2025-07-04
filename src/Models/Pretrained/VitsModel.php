<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\Output\ModelOutput;
use Codewithkyrian\Transformers\Models\Output\VitsModelOutput;

final class VitsModel extends VitsPretrainedModel
{
    public function __invoke(array $modelInputs): ModelOutput
    {
        $output = parent::__invoke($modelInputs);

        return new VitsModelOutput(
            $output['waveform'],
            $output['spectrogram'],
        );
    }
}
