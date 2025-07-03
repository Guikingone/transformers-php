<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\PostProcessors;

use function array_fill;
use function array_merge;
use function count;

/**
 * A PostProcessor that returns the given tokens as is.
 */
class ByteLevelPostProcessor extends PostProcessor
{
    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    /**
     * Post process the given tokens.
     *
     * @param string[] $tokens the input tokens
     * @param null|string[] $tokenPair the input tokens for the second sequence in a pair
     * @param bool $addSpecialTokens whether to add the special tokens associated with the corresponding model
     */
    public function postProcess(array $tokens, ?array $tokenPair = null, bool $addSpecialTokens = true): PostProcessedOutput
    {
        if (null !== $tokenPair) {
            $tokens = array_merge($tokens, $tokenPair);
        }

        return new PostProcessedOutput($tokens, array_fill(0, count($tokens), 0));
    }
}
