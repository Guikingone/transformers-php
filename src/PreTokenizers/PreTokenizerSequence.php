<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\PreTokenizers;

use function array_map;
use function array_reduce;

class PreTokenizerSequence extends PreTokenizer
{
    /**
     * @var PreTokenizer[]
     */
    protected array $preTokenizers;

    public function __construct(array $config)
    {
        $this->preTokenizers = array_map(
            static fn (array $config) => PreTokenizer::fromConfig($config),
            $config['pretokenizers'],
        );
    }

    public function preTokenizeText(array|string $text, array $options): array
    {
        return array_reduce(
            $this->preTokenizers,
            static fn ($text, PreTokenizer $preTokenizer) => $preTokenizer->preTokenize($text, $options),
            [$text],
        );
    }
}
