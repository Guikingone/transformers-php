<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Normalizers;

use function array_map;
use function array_reduce;

/**
 * A Normalizer that applies a sequence of Normalizers.
 */
class NormalizerSequence extends Normalizer
{
    /**
     * @var Normalizer[]
     */
    protected array $normalizers;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->normalizers = array_map(
            static fn (array $config) => Normalizer::fromConfig($config),
            $config['normalizers'],
        );
    }

    public function normalize(string $text): string
    {
        return array_reduce(
            $this->normalizers,
            static fn (string $text, Normalizer $normalizer) => $normalizer->normalize($text),
            $text,
        );
    }
}
