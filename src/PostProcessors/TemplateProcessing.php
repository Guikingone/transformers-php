<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\PostProcessors;

use function array_fill;
use function array_merge;
use function count;

/**
 * Post processor that replaces special tokens in a template with actual tokens.
 */
class TemplateProcessing extends PostProcessor
{
    /**
     * @var array the template for a single sequence of tokens
     */
    public array $single;

    /**
     * @var array the template for a pair of sequences of tokens
     */
    protected array $pair;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->single = $config['single'];
        $this->pair = $config['pair'];
    }

    /**
     * Replaces special tokens in the template with actual tokens.
     *
     * @param string[] $tokens the input tokens
     * @param null|string[] $tokenPair the input tokens for the second sequence in a pair
     * @param bool $addSpecialTokens whether to add the special tokens associated with the corresponding model
     */
    public function postProcess(array $tokens, ?array $tokenPair = null, bool $addSpecialTokens = true): PostProcessedOutput
    {
        $type = null === $tokenPair ? $this->single : $this->pair;

        $processedTokens = [];
        $types = [];

        foreach ($type as $item) {
            if (isset($item['SpecialToken'])) {
                if ($addSpecialTokens) {
                    $processedTokens[] = $item['SpecialToken']['id'];
                    $types[] = $item['SpecialToken']['type_id'];
                }
            } elseif (isset($item['Sequence'])) {
                if ('A' === $item['Sequence']['id']) {
                    $processedTokens = array_merge($processedTokens, $tokens);
                    $types = array_merge($types, array_fill(0, count($tokens), $item['Sequence']['type_id']));
                } elseif ('B' === $item['Sequence']['id']) {
                    $processedTokens = array_merge($processedTokens, $tokenPair);
                    $types = array_merge($types, array_fill(0, count($tokenPair), $item['Sequence']['type_id']));
                }
            }
        }

        return new PostProcessedOutput($processedTokens, $types);
    }
}
