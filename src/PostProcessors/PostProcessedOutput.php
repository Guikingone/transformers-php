<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\PostProcessors;

class PostProcessedOutput
{
    /**
     * @param string[] $tokens the tokens to be post-processed
     * @param int[] $tokenTypeIds list of token type ids produced by the post-processor
     */
    public function __construct(
        public array $tokens,
        public ?array $tokenTypeIds = null,
    ) {}
}
