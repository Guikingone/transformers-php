<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Tokenizers;

use ArrayObject;
use Codewithkyrian\Transformers\Exceptions\HubException;
use Codewithkyrian\Transformers\Utils\Hub;
use Exception;

use function Codewithkyrian\Transformers\Utils\array_pop_key;
use function count;
use function implode;
use function is_int;
use function mb_ord;
use function mb_strlen;
use function mb_strtolower;
use function mb_substr;
use function preg_replace;
use function preg_split;

use const PREG_SPLIT_NO_EMPTY;

abstract class TokenizerModel
{
    public const SPECIAL_TOKEN_ATTRIBUTES = [
        'bos_token',
        'eos_token',
        'unk_token',
        'sep_token',
        'pad_token',
        'cls_token',
        'mask_token',
        // additional_special_tokens (TODO)
    ];

    /**
     * An array of tokens.
     *
     * @var string[]
     */
    public array $vocab = [];

    /**
     * A mapping of tokens to ids.
     *
     * @var array<string, int>
     */
    public array $tokenToIds = [];
    public ?string $endOfWordSuffix = null;
    public ?string $continuingSubwordPrefix = null;

    /**
     *The id of the unknown token.
     */
    protected ?int $unkTokenId = null;

    /**
     * The unknown token string.
     */
    protected ?string $unkToken = null;

    /**
     * Whether to fuse the unknown token into the vocabulary.
     */
    protected bool $fuseUnk = false;

    public function __construct(protected array $config)
    {
        $this->continuingSubwordPrefix = $config['continuing_subword_prefix'] ?? null;
        if ('' == $this->continuingSubwordPrefix) {
            $this->continuingSubwordPrefix = null;
        }

        $this->fuseUnk = $config['fuse_unk'] ?? false;
    }

    /**
     * Internal function to call the TokenizerModel instance.
     *
     * @param string[] $tokens the tokens to encode
     *
     * @return string[] the encoded token IDs
     */
    public function __invoke(array $tokens): array
    {
        $ids = $this->encode($tokens);

        if ($this->fuseUnk) {
            $ids = $this->fuse($ids, $this->unkTokenId, $this->tokenToIds);
        }

        return $ids;
    }

    /**
     * Instantiates a new TokenizerModel instance based on the configuration object provided.
     */
    public static function fromConfig(array $config, ...$args): self
    {
        return match ($config['type'] ?? null) {
            'WordPiece' => new WordPieceModel($config),
            'Unigram' => new UnigramModel($config, ...$args),
            'BPE' => new BPEModel($config),
            default => self::inferTokenizerModel($config, $args),
        };
    }

    /**
     * Loads a tokenizer from the specified path.
     *
     * @param string $modelNameOrPath The path to the tokenizer model directory
     *
     * @return array {tokenizerJson: array, tokenizerConfig: array}
     *
     * @throws HubException
     */
    public static function load(
        string $modelNameOrPath,
        ?string $cacheDir,
        string $revision,
        mixed $legacy,
        ?callable $onProgress = null,
    ): array {
        $tokenizerJson = Hub::getJson(
            $modelNameOrPath,
            fileName: 'tokenizer.json',
            cacheDir: $cacheDir,
            revision: $revision,
            fatal: false,
            onProgress: $onProgress,
        );

        $tokenizerConfig = Hub::getJson(
            $modelNameOrPath,
            fileName: 'tokenizer_config.json',
            cacheDir: $cacheDir,
            revision: $revision,
            fatal: false,
            onProgress: $onProgress,
        );

        if (null != $legacy) {
            $tokenizerConfig['legacy'] = $legacy;
        }

        return [
            'tokenizerJson' => $tokenizerJson,
            'tokenizerConfig' => $tokenizerConfig,
        ];
    }

    /**
     * Helper function to split a string on whitespace.
     *
     * @param string $text the text to split
     *
     * @return string[] the split text
     */
    public static function whitespaceSplit(string $text): array
    {
        return preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Helper function to lowercase a string and remove accents.
     *
     * @param string $text the text to lowercase and remove accents from
     *
     * @return string the text with accents removed and lowercased
     */
    public static function lowerCaseAndRemoveAccents(string $text): string
    {
        return mb_strtolower(self::removeAccents($text));
    }

    /**
     * Helper function to remove accents from a string.
     *
     * @param string $text the text to remove accents from
     *
     * @return string the text with accents removed
     */
    public static function removeAccents(string $text): string
    {
        return preg_replace('/[\x{0300}-\x{036f}]/u', '', $text);
    }

    /**
     * Clean up a list of simple English tokenization artifacts like spaces before punctuations and abbreviated forms.
     *
     * @param string $text the text to clean up
     *
     * @return string the cleaned up text
     */
    public static function cleanUpTokenization(int|string $text): string
    {
        if (is_int($text)) {
            $text = (string) $text;
        }

        $text = preg_replace('/ \./', '.', $text);
        $text = preg_replace('/ \?/', '?', $text);
        $text = preg_replace('/ \!/', '!', $text);
        $text = preg_replace('/ ,/', ',', $text);
        $text = preg_replace('/ \' /', "'", $text);
        $text = preg_replace('/ n\'t/', "n't", $text);
        $text = preg_replace('/ \'m/', "'m", $text);
        $text = preg_replace('/ \'s/', "'s", $text);
        $text = preg_replace('/ \'ve/', "'ve", $text);

        return preg_replace('/ \'re/', "'re", $text);
    }

    public static function toMap(array $arr): array
    {
        $arrayObject = new ArrayObject($arr);

        return $arrayObject->getArrayCopy();
    }

    /**
     * Adds whitespace around any CJK (Chinese, Japanese, or Korean) character in the input text.
     *
     * @param string $text the input text to tokenize
     *
     * @return string the tokenized text with whitespace added around CJK characters
     */
    public static function tokenizeChineseChars(string $text): string
    {
        $output = [];
        for ($i = 0; $i < mb_strlen($text); ++$i) {
            $char = mb_substr($text, $i, 1);
            $cp = mb_ord($char);
            if (self::isChineseChar($cp)) {
                $output[] = ' ';
                $output[] = $char;
                $output[] = ' ';
            } else {
                $output[] = $char;
            }
        }

        return implode('', $output);
    }

    /**
     * Checks whether the given Unicode codepoint represents a CJK (Chinese, Japanese, or Korean) character.
     *
     * A "chinese character" is defined as anything in the CJK Unicode block.
     *
     * @param int $cp the Unicode codepoint to check
     *
     * @return bool true if the codepoint represents a CJK character, false otherwise
     */
    public static function isChineseChar(int $cp): bool
    {
        return
            ($cp >= 0x4E00 && $cp <= 0x9FFF)
            || ($cp >= 0x3400 && $cp <= 0x4DBF)
            || ($cp >= 0x20000 && $cp <= 0x2A6DF)
            || ($cp >= 0x2A700 && $cp <= 0x2B73F)
            || ($cp >= 0x2B740 && $cp <= 0x2B81F)
            || ($cp >= 0x2B820 && $cp <= 0x2CEAF)
            || ($cp >= 0xF900 && $cp <= 0xFAFF)
            || ($cp >= 0x2F800 && $cp <= 0x2FA1F);
    }

    /**
     * Converts a list of tokens into a list of token IDs.
     *
     * @param string[] $tokens the tokens to convert
     *
     * @return int[] the converted token IDs
     */
    public function convertTokensToIds(array $tokens): array
    {
        $ids = [];

        foreach ($tokens as $token) {
            $ids[] = $this->tokenToIds[$token] ?? $this->unkTokenId;
        }

        return $ids;
    }

    /**
     * Converts a list of token IDs into a list of tokens.
     *
     * @param string[] $ids the token IDs to convert
     *
     * @return string[] the converted tokens
     */
    public function convertIdsToTokens(array $ids): array
    {
        $tokens = [];

        foreach ($ids as $id) {
            $tokens[] = $this->vocab[$id] ?? $this->unkToken;
        }

        return $tokens;
    }

    /**
     * Encodes a list of tokens into a list of token IDs.
     *
     * @param string[] $tokens the tokens to encode
     *
     * @return string[] the encoded token IDs
     */
    abstract protected function encode(array $tokens): array;

    /**
     * Helper function to fuse consecutive values in an array equal to the specified value.
     *
     * @param array $arr the input array
     * @param mixed $value the value to fuse on
     * @param array $mapping the mapping from input domain to value
     *
     * @return array the fused array
     */
    protected function fuse(array $arr, mixed $value, array $mapping): array
    {
        $fused = [];
        $i = 0;
        $length = count($arr);

        while ($i < $length) {
            $fused[] = $arr[$i];

            // Check if the current element's mapping is not equal to the specified value
            if (($mapping[$arr[$i]] ?? $value) !== $value) {
                ++$i;

                continue;
            }

            // Skip consecutive elements equal to the specified value
            while ($i < $length && ($mapping[$arr[$i]] ?? $value) === $value) {
                ++$i;
            }
        }

        return $fused;
    }

    /**
     * Infers the tokenizer model based on the pretokenizer configuration.
     *
     * This function is necessary for legacy tokenizer.json files that do not contain the model.type key.
     *
     * @param array $config the tokenizer configuration
     * @param array $args additional arguments that may include pretokenizerConfig
     *
     * @return TokenizerModel the inferred tokenizer model instance
     *
     * @throws Exception if the tokenizer type is unknown
     */
    private static function inferTokenizerModel(array $config, array &$args): TokenizerModel
    {
        $pretokenizerConfig = array_pop_key($args, 'pretokenizerConfig');
        $decoderConfig = array_pop_key($args, 'decoderConfig');

        if ($pretokenizerConfig) {
            if ('ByteLevel' === $pretokenizerConfig['type']) {
                return new BPEModel($config);
            }
            if ('MetaSpace' === $pretokenizerConfig['type']) {
                return new UnigramModel($config, ...$args);
            }
            if ('Sequence' === $pretokenizerConfig['type']) {
                foreach ($pretokenizerConfig['pretokenizers'] as $pretokenizer) {
                    if ('ByteLevel' === $pretokenizer['type']) {
                        return new BPEModel($config);
                    }
                    if ('Metaspace' === $pretokenizer['type']) {
                        return new UnigramModel($config, ...$args);
                    }
                }
            }
        }

        if ($decoderConfig) {
            if ('WordPiece' === $decoderConfig['type']) {
                return new WordPieceModel($config);
            }
        }

        if ($config['vocab'] ?? false) {
            return new LegacyModel($config, ...$args);
        }

        throw new Exception("Unknown tokenizer type {$config['type']}");
    }
}
