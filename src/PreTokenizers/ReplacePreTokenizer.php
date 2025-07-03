<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\PreTokenizers;

use function str_replace;

class ReplacePreTokenizer extends PreTokenizer
{
    protected ?string $pattern;
    protected string $content;

    public function __construct(array $config)
    {
        $this->pattern = $config['pattern'] ?? null;
        $this->content = $config['content'];
    }

    public function preTokenizeText(array|string $text, array $options): array
    {
        if (null === $this->pattern) {
            return [$text];
        }

        return str_replace($this->pattern, $this->content, $text);
    }
}
