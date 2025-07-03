<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Decoders;

use function implode;

class FuseDecoder extends Decoder
{
    /**
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    protected function decodeChain(array $tokens): array
    {
        return [implode('', $tokens)];
    }
}
