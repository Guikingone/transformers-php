<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\PostProcessors;

use InvalidArgumentException;

abstract class PostProcessor
{
    public function __construct(public array $config) {}

    public function __invoke(array $tokens, ...$args): PostProcessedOutput
    {
        return $this->postProcess($tokens, ...$args);
    }

    /**
     * Factory method to create a PostProcessor object from a configuration object.
     */
    public static function fromConfig(?array $config): ?self
    {
        if (null === $config) {
            return null;
        }

        return match ($config['type']) {
            'BertProcessing' => new BertProcessing($config),
            'ByteLevel' => new ByteLevelPostProcessor($config),
            'TemplateProcessing' => new TemplateProcessing($config),
            'RobertaProcessing' => new RobertaProcessing($config),
            'Sequence' => new PostProcessorSequence($config),
            default => throw new InvalidArgumentException("Unknown post-processor type {$config['type']}"),
        };
    }

    /**
     * @param array $tokens the input tokens to be post-processed
     * @param null|array $tokenPair the input tokens for the second sequence in a pair
     * @param bool $addSpecialTokens whether to add the special tokens associated with the corresponding model
     */
    abstract public function postProcess(array $tokens, ?array $tokenPair = null, bool $addSpecialTokens = true): PostProcessedOutput;
}
