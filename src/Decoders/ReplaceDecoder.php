<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Decoders;

use function array_map;
use function preg_replace;
use function str_replace;

class ReplaceDecoder extends Decoder
{
    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    protected function decodeChain(array $tokens): array
    {
        $pattern = $this->config['pattern'] ?? null;

        if (null === $pattern) {
            return $tokens;
        }

        $regex = $pattern['Regex'] ?? null;
        $string = $pattern['String'] ?? null;
        $replacement = $this->config['content'] ?? '';

        return array_map(static function ($token) use ($regex, $string, $replacement) {
            if (null !== $regex) {
                return preg_replace("/{$regex}/u", $replacement, (string) $token);
            }
            if (null !== $string) {
                return str_replace($string, $replacement, (string) $token);
            }

            return $token;
        }, $tokens);
    }
}
