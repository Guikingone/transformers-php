<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\PreTokenizers;

use InvalidArgumentException;

use function array_map;
use function array_merge;
use function is_array;

/**
 * A callable class representing a pre-tokenizer used in tokenization.
 */
abstract class PreTokenizer
{
    public const PUNCTUATION_REGEX = '\p{P}\u0021-\u002F\u003A-\u0040\u005B-\u0060\u007B-\u007E';

    /** Tokenizes the given text into pre-tokens.
     * @param string|string[] $text the text to pre-tokenize
     * @param array $options additional options for the pre-tokenization logic
     *
     * @return string[]
     */
    public function __invoke(array|string $text, array $options): array
    {
        return $this->preTokenize($text, $options);
    }

    public static function fromConfig(?array $config): ?self
    {
        if (null === $config) {
            return null;
        }

        return match ($config['type']) {
            'BertPreTokenizer' => new BertPreTokenizer($config),
            'Sequence' => new PreTokenizerSequence($config),
            'Whitespace' => new WhitespacePreTokenizer($config),
            'WhitespaceSplit' => new WhitespaceSplit($config),
            'Metaspace' => new MetaspacePreTokenizer($config),
            'ByteLevel' => new ByteLevelPreTokenizer($config),
            'Split' => new SplitPreTokenizer($config),
            'Punctuation' => new PunctuationPreTokenizer($config),
            'Digits' => new DigitsPreTokenizer($config),
            'Replace' => new ReplacePreTokenizer($config),
            default => throw new InvalidArgumentException("Unknown pre-tokenizer type {$config['type']}"),
        };
    }

    /** Tokenizes the given text into pre-tokens.
     * @param string|string[] $text the text to pre-tokenize
     * @param array $options additional options for the pre-tokenization logic
     *
     * @return string[]
     */
    public function preTokenize(array|string $text, array $options): array
    {
        // Check if $text is an array
        if (is_array($text)) {
            $result = array_map(function ($x) use ($options) {
                return $this->preTokenizeText($x, $options);
            }, $text);

            return array_merge(...$result);
        }

        // If $text is not an array, apply pre_tokenize_text directly
        return $this->preTokenizeText($text, $options);
    }

    /**
     * Method that should be implemented by subclasses to define the specific pre-tokenization logic.
     *
     * @param string|string[] $text the text to pre-tokenize
     * @param array $options additional options for the pre-tokenization logic
     *
     * @return string[] the pre-tokenized text
     */
    abstract protected function preTokenizeText(array|string $text, array $options): array;
}
