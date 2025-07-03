<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Decoders;

use function array_map;
use function array_reduce;

class DecoderSequence extends Decoder
{
    /**
     * @var array Decoder[]
     */
    protected array $decoders;

    /**
     */
    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->decoders = array_map(
            static fn (array $decoderConfig) => Decoder::fromConfig($decoderConfig),
            $config['decoders'],
        );
    }

    protected function decodeChain(array $tokens): array
    {
        return array_reduce(
            $this->decoders,
            static fn (array $tokens, Decoder $decoder) => $decoder->decodeChain($tokens),
            $tokens,
        );
    }
}
