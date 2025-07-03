<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Decoders;

use Codewithkyrian\Transformers\Tokenizers\TokenizerModel;

use function str_replace;
use function str_starts_with;

class WordPieceDecoder extends Decoder
{
    protected bool $cleanup;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->cleanup = $config['cleanup'];
    }

    protected function decodeChain(array $tokens): array
    {
        $decodedTokens = [];
        foreach ($tokens as $i => $token) {
            if (0 !== $i) {
                if (str_starts_with((string) $token, $this->config['prefix'])) {
                    // NOTE: Use str_replace to replace only the first occurrence
                    $token = str_replace($this->config['prefix'], '', $token);
                } else {
                    $token = ' ' . $token;
                }
            }
            if ($this->cleanup) {
                $token = TokenizerModel::cleanUpTokenization($token);
            }

            $decodedTokens[] = $token;
        }

        return $decodedTokens;
    }
}
