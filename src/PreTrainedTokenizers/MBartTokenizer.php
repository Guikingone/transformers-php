<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\PreTrainedTokenizers;

use Closure;
use Codewithkyrian\Transformers\Tensor\Tensor;
use Codewithkyrian\Transformers\Utils\GenerationConfig;
use Exception;

use function array_filter;
use function call_user_func;
use function implode;
use function in_array;
use function preg_match;

class MBartTokenizer extends PreTrainedTokenizer
{
    protected string $languageRegex = '/^[a-z]{2}_[A-Z]{2}$/';
    protected array $languageCodes = [];
    protected Closure $langToToken;

    public function __construct(array $tokenizerJSON, array $tokenizerConfig)
    {
        parent::__construct($tokenizerJSON, $tokenizerConfig);

        $this->languageCodes = array_filter($this->specialTokens, function ($x) {
            return preg_match($this->languageRegex, $x);
        });

        $this->langToToken = static fn ($x) => $x;  // Identity function
    }

    /**
     * Helper function to build translation inputs for an `MBartTokenizer`.
     *
     * @param array|string $rawInputs the text to tokenize
     * @param GenerationConfig $generationConfig the additional arguments for the generation method
     * @param bool|string $padding whether to pad the input sequences
     * @param bool $truncation whether to truncate the input sequences
     * @param null|int $maxLength maximum length of the returned list and optionally padding length
     * @param bool $addSpecialTokens whether to add the special tokens associated with the corresponding model
     *
     * @return array{input_ids: Tensor, token_type_ids: Tensor, attention_mask: Tensor}
     *
     * @throws Exception
     */
    public function buildTranslationInputs(
        array|string $rawInputs,
        GenerationConfig $generationConfig,
        bool|string $padding = false,
        bool $truncation = false,
        ?int $maxLength = null,
        bool $addSpecialTokens = true,
    ): array {
        $srcLangToken = $generationConfig['src_lang'] ?? null;
        $tgtLangToken = $generationConfig['tgt_lang'];

        // Check that the target language is valid:
        if (!in_array($tgtLangToken, $this->languageCodes)) {
            throw new Exception("Target language code \"{$tgtLangToken}\" is not valid. Must be one of: {" . implode(', ', $this->languageCodes) . '}');
        }

        // Allow `src_lang` to be optional. If not set, we'll use the tokenizer's default.
        if (null !== $srcLangToken) {
            // Check that the source language is valid:
            if (!in_array($srcLangToken, $this->languageCodes)) {
                throw new Exception("Source language code \"{$srcLangToken}\" is not valid. Must be one of: {" . implode(', ', $this->languageCodes) . '}');
            }

            // In the same way as the Python library, we override the post-processor
            // to force the source language to be first:
            foreach ($this->postProcessor->single as &$item) {
                if (isset($item['SpecialToken']) && preg_match($this->languageRegex, $item['SpecialToken']['id'])) {
                    $item['SpecialToken']['id'] = call_user_func($this->langToToken, $srcLangToken);

                    break;
                }
            }
            // TODO: Do the same for pair?
        }

        // Override the `forced_bos_token_id` to force the correct language
        $generationConfig->forced_bos_token_id = $this->model->convertTokensToIds(
            [call_user_func($this->langToToken, $tgtLangToken)],
        )[0];

        return $this->__invoke($rawInputs, padding: $padding, addSpecialTokens: $addSpecialTokens, truncation: $truncation, maxLength: $maxLength);
    }
}
