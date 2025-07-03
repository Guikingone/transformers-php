<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\PreTrainedTokenizers;

use Exception;

use function array_filter;
use function array_flip;
use function array_keys;
use function array_map;
use function array_merge;
use function array_slice;
use function array_values;
use function Codewithkyrian\Transformers\Utils\array_every;
use function count;
use function end;
use function floor;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function json_encode;
use function max;
use function min;
use function preg_match;
use function round;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use function trim;

use const ARRAY_FILTER_USE_BOTH;

class WhisperTokenizer extends PreTrainedTokenizer
{
    private const WHISPER_LANGUAGES = [
        'en' => 'english',
        'zh' => 'chinese',
        'de' => 'german',
        'es' => 'spanish',
        'ru' => 'russian',
        'ko' => 'korean',
        'fr' => 'french',
        'ja' => 'japanese',
        'pt' => 'portuguese',
        'tr' => 'turkish',
        'pl' => 'polish',
        'ca' => 'catalan',
        'nl' => 'dutch',
        'ar' => 'arabic',
        'sv' => 'swedish',
        'it' => 'italian',
        'id' => 'indonesian',
        'hi' => 'hindi',
        'fi' => 'finnish',
        'vi' => 'vietnamese',
        'he' => 'hebrew',
        'uk' => 'ukrainian',
        'el' => 'greek',
        'ms' => 'malay',
        'cs' => 'czech',
        'ro' => 'romanian',
        'da' => 'danish',
        'hu' => 'hungarian',
        'ta' => 'tamil',
        'no' => 'norwegian',
        'th' => 'thai',
        'ur' => 'urdu',
        'hr' => 'croatian',
        'bg' => 'bulgarian',
        'lt' => 'lithuanian',
        'la' => 'latin',
        'mi' => 'maori',
        'ml' => 'malayalam',
        'cy' => 'welsh',
        'sk' => 'slovak',
        'te' => 'telugu',
        'fa' => 'persian',
        'lv' => 'latvian',
        'bn' => 'bengali',
        'sr' => 'serbian',
        'az' => 'azerbaijani',
        'sl' => 'slovenian',
        'kn' => 'kannada',
        'et' => 'estonian',
        'mk' => 'macedonian',
        'br' => 'breton',
        'eu' => 'basque',
        'is' => 'icelandic',
        'hy' => 'armenian',
        'ne' => 'nepali',
        'mn' => 'mongolian',
        'bs' => 'bosnian',
        'kk' => 'kazakh',
        'sq' => 'albanian',
        'sw' => 'swahili',
        'gl' => 'galician',
        'mr' => 'marathi',
        'pa' => 'punjabi',
        'si' => 'sinhala',
        'km' => 'khmer',
        'sn' => 'shona',
        'yo' => 'yoruba',
        'so' => 'somali',
        'af' => 'afrikaans',
        'oc' => 'occitan',
        'ka' => 'georgian',
        'be' => 'belarusian',
        'tg' => 'tajik',
        'sd' => 'sindhi',
        'gu' => 'gujarati',
        'am' => 'amharic',
        'yi' => 'yiddish',
        'lo' => 'lao',
        'uz' => 'uzbek',
        'fo' => 'faroese',
        'ht' => 'haitian creole',
        'ps' => 'pashto',
        'tk' => 'turkmen',
        'nn' => 'nynorsk',
        'mt' => 'maltese',
        'sa' => 'sanskrit',
        'lb' => 'luxembourgish',
        'my' => 'myanmar',
        'bo' => 'tibetan',
        'tl' => 'tagalog',
        'mg' => 'malagasy',
        'as' => 'assamese',
        'tt' => 'tatar',
        'haw' => 'hawaiian',
        'ln' => 'lingala',
        'ha' => 'hausa',
        'ba' => 'bashkir',
        'jw' => 'javanese',
        'su' => 'sundanese',
    ];
    protected string $defaultChatTemplate = '{% for message in messages %}" "{{ message.content }}{{ eos_token }}" "{% endfor %}';

    protected array $WHISPER_LANGUAGE_TO_CODE;

    public function __construct(array $tokenizerJSON, array $tokenizerConfig)
    {
        parent::__construct($tokenizerJSON, $tokenizerConfig);

        $this->WHISPER_LANGUAGE_TO_CODE = [
            ...array_flip(self::WHISPER_LANGUAGES),
            'burmese' => 'my',
            'valencian' => 'ca',
            'flemish' => 'nl',
            'haitian' => 'ht',
            'letzeburgesch' => 'lb',
            'pushto' => 'ps',
            'panjabi' => 'pa',
            'moldavian' => 'ro',
            'moldovan' => 'ro',
            'sinhalese' => 'si',
            'castilian' => 'es',
        ];
    }

    /**
     * Decodes automatic speech recognition (ASR) sequences.
     *
     * @param array $sequences the sequences to decode, each sequence is an associative array with 'tokens', 'token_timestamps', and 'stride'
     * @param bool $returnTimestamps whether to return timestamps
     * @param bool $returnLanguage whether to return language
     * @param float $timePrecision the precision of the timestamps in seconds
     * @param bool $forceFullSequences whether to force full sequences (default is true)
     *
     * @return array the decoded sequences
     *
     * @throws Exception if timePrecision is not specified
     */
    public function decodeASR(
        array $sequences,
        float $timePrecision,
        bool|string $returnTimestamps = false,
        bool $returnLanguage = false,
        bool $forceFullSequences = true,
    ): array {
        // Set forceFullSequences=false if you want streaming
        // TODO add support for `returnLanguage`

        // Internal method meant to only be used by ASR pipeline.
        // Handles all the little quirks specific to whisper to handle
        // the various options not allowed in other seq2seq models

        // =========== Overview ============
        // - iterate over all outputs
        // - all tokens within output
        // - Each token can be
        //   - language token
        //   - special token
        //   - timestamp token
        //   - text token
        // - We accumulate the text tokens.
        // - We split on end timestamps
        // - Lots of complexity comes from stride and timestamps

        $lastLanguage = null;

        $returnWordTimestamps = 'word' === $returnTimestamps;

        $newChunk = static fn () => ['language' => $lastLanguage, 'timestamp' => [null, null], 'text' => ''];

        // Welcome to the state machine!
        $chunks = [];
        $chunk = $newChunk();
        $timeOffset = 0.0;
        $timestampBegin = $this->model->convertTokensToIds(['<|notimestamps|>'])[0] + 1;

        $previousTokens = [];
        $previousTokenTimestamps = [];
        $skip = false;

        // Iterate over sequences
        foreach ($sequences as $output) {
            $tokenIds = $output['tokens'];
            $tokenTimestamps = $returnWordTimestamps ? $output['token_timestamps'] : null;

            $lastTimestamp = null;
            $firstTimestamp = $timestampBegin;

            if (isset($output['stride'])) {
                [$chunkLen, $strideLeft, $strideRight] = $output['stride'];

                // Offset the timings to account for the other `model_outputs`.
                $timeOffset -= $strideLeft;
                $rightStrideStart = $chunkLen - $strideRight;

                if ($strideLeft) {
                    $firstTimestamp = $strideLeft / $timePrecision + $timestampBegin;
                }

                if ($strideRight) {
                    for ($i = count($tokenIds) - 1; $i >= 0; --$i) {
                        $token = $tokenIds[$i];
                        if ($token >= $timestampBegin) {
                            if (null !== $lastTimestamp && ($token - $timestampBegin) * $timePrecision < $rightStrideStart) {
                                break;
                            }
                            $lastTimestamp = $token;
                        }
                    }
                }
            }

            $currentTokens = [];
            $currentTokenTimestamps = [];

            foreach ($tokenIds as $i => $token) {
                // 4 possible states for each token
                // - 1/ Language code
                // - 2/ all other special tokens (which we ignore)
                // - 3/ Timestamp
                // - 4/ Regular text
                if (in_array($token, $this->allSpecialIds)) {
                    $text = $this->decode([$token]);
                    $language = self::WHISPER_LANGUAGES[substr($text, 2, -2)] ?? null;

                    if (null !== $language) {
                        // 1/ Indeed some language
                        // TODO Handle when language is different from the previous
                        // one, and we cannot use timestamped tokens to create chunks
                        if (null !== $lastLanguage && $language !== $lastLanguage && !$returnTimestamps) {
                            $previousTokens[] = $currentTokens;
                            $resolvedTokens = $this->findLongestCommonSequence($previousTokens)[0];
                            $resolvedText = $this->decode($resolvedTokens);
                            $chunk['text'] = $resolvedText;
                            $chunks[] = $chunk;

                            $previousTokens = [];
                            $currentTokens = [];
                            $chunk = $newChunk();
                        }

                        $lastLanguage = $chunk['language'] = $language;
                    }
                // 2/ This is a regular special token, ignoring it
                } elseif ($token >= $timestampBegin) {
                    // 3/ Timestamp token
                    $time = ($token - $timestampBegin) * $timePrecision + $timeOffset;
                    $roundedTime = round($time, 2);

                    if (null !== $lastTimestamp && $token >= $lastTimestamp) {
                        // Whisper outputted a timestamp token, but it falls within
                        // our stride, so we're going to skip it for the time being
                        // and resolve this later
                        // Skip is necessary because timestamp tokens always come
                        // by pair, so we need to skip the next one too (which would mark the start of another chunk).
                        $skip = true;
                    } elseif ($skip || (!empty($previousTokens) && $token < $firstTimestamp)) {
                        $skip = false;
                    } elseif (null === $chunk['timestamp'][0]) {
                        $chunk['timestamp'][0] = $roundedTime;
                    } else {
                        // This is the end of the timestamp chunk
                        if ($roundedTime !== $chunk['timestamp'][0]) {
                            $chunk['timestamp'][1] = $roundedTime;

                            $previousTokens[] = $currentTokens;

                            if ($returnWordTimestamps) {
                                $previousTokenTimestamps[] = $currentTokenTimestamps;
                            }

                            [$resolvedTokens, $resolvedTokenTimestamps] = $this->findLongestCommonSequence(
                                $previousTokens,
                                $previousTokenTimestamps,
                            );

                            $resolvedText = $this->decode($resolvedTokens);
                            $chunk['text'] = $resolvedText;

                            if ($returnWordTimestamps) {
                                $chunk['words'] = $this->collateWordTimestamps(
                                    $resolvedTokens,
                                    $resolvedTokenTimestamps,
                                    $lastLanguage,
                                );
                            }

                            $chunks[] = $chunk;

                            // Flush all our temporary context
                            $previousTokens = [];
                            $currentTokens = [];
                            $previousTokenTimestamps = [];
                            $currentTokenTimestamps = [];
                            $chunk = $newChunk();
                        }
                        // This is a bug in timestamp token output
                        // where we're taking the duplicate token
                        // as a stop where it should be a start.
                        // This is an issue in the underlying model output
                        // Let's just skip it so it becomes de-factor a start agin
                    }
                } else {
                    // 4/ Regular token
                    // We just append to the list of all tokens so we can handle
                    // merges later and decode into text.
                    $currentTokens[] = $token;

                    if ($returnWordTimestamps) {
                        $startTime = round($tokenTimestamps[$i] + $timeOffset, 2);
                        $endTime = $i + 1 < count($tokenTimestamps) ? round($tokenTimestamps[$i + 1] + $timeOffset, 2) : null; // Null should never happen
                        $currentTokenTimestamps[] = [$startTime, $endTime];
                    }
                }
            }

            if (isset($output['stride'])) {
                [$chunkLen, $strideLeft, $strideRight] = $output['stride'];
                $timeOffset += $chunkLen - $strideRight;
            }

            if (!empty($currentTokens)) {
                $previousTokens[] = $currentTokens;

                if ($returnWordTimestamps) {
                    $previousTokenTimestamps[] = $currentTokenTimestamps;
                }
            } elseif (array_every($previousTokens, static fn ($x) => empty($x))) {
                $chunk = $newChunk();
                $previousTokens = [];
                $currentTokens = [];
                $previousTokenTimestamps = [];
                $currentTokenTimestamps = [];
            }
        }

        if (count($previousTokens) > 0) {
            if ($forceFullSequences && $returnTimestamps) {
                // Last token should always be timestamps, so there shouldn't be leftover
                throw new Exception(
                    'Whisper did not predict an ending timestamp, which can happen if audio is cut off in the middle of a word. '
                    . 'Also make sure WhisperTimeStampLogitsProcessor was used during generation.',
                );
            }

            // Happens when we don't use timestamps
            [$resolvedTokens, $resolvedTokenTimestamps] = $this->findLongestCommonSequence($previousTokens, $previousTokenTimestamps);

            // Flushing previous tokens (FINAL)
            $resolvedText = $this->decode($resolvedTokens);
            $chunk['text'] = $resolvedText;
            if ($returnWordTimestamps) {
                $chunk['words'] = $this->collateWordTimestamps($resolvedTokens, $resolvedTokenTimestamps, $lastLanguage);
            }
            $chunks[] = $chunk;
        }

        $optional = [];

        $fullText = implode('', array_map(static fn ($chunk) => $chunk['text'], $chunks));

        if ($returnTimestamps || $returnLanguage) {
            for ($i = 0; $i < count($chunks); ++$i) {
                if (!$returnTimestamps) {
                    unset($chunks[$i]['timestamp']);
                }

                if (!$returnLanguage) {
                    unset($chunks[$i]['language']);
                }
            }

            if ($returnWordTimestamps) {
                $newChunks = [];
                foreach ($chunks as $chunk) {
                    foreach ($chunk['words'] as $word) {
                        $newChunks[] = $word;
                    }
                }
                $optional = ['chunks' => $newChunks];
            } else {
                $optional = ['chunks' => $chunks];
            }
        }

        return [$fullText, $optional];
    }

    public function collateWordTimestamps($tokens, $tokenTimestamps, $language): array
    {
        [$words, , $tokenIndices] = $this->combineTokensIntoWords($tokens, $language);

        $timings = [];
        foreach ($words as $i => $word) {
            $indices = $tokenIndices[$i];
            $timings[] = [
                'text' => $word,
                'timestamp' => [
                    $tokenTimestamps[$indices[0]][0],
                    $tokenTimestamps[end($indices)][1],
                ],
            ];
        }

        return $timings;
    }

    public function decode(
        array $tokenIds,
        bool $skipSpecialTokens = false,
        ?bool $cleanUpTokenizationSpaces = null,
        ?bool $decodeWithTimestamps = null,
        float $timePrecision = 0.02,
    ): string {
        if ($decodeWithTimestamps) {
            $text = $this->decodeWithTimestamps($tokenIds, $skipSpecialTokens, $cleanUpTokenizationSpaces, $timePrecision);
        } else {
            $text = parent::decode($tokenIds, $skipSpecialTokens, $cleanUpTokenizationSpaces);
        }

        // TODO: implement offsets
        // if (isset($decode_args['output_offsets']) && $decode_args['output_offsets']) {
        //     $offsets = $this->computeOffsets();
        // }
        return $text;
    }

    /**
     * Helper function to build translation inputs for a `WhisperTokenizer`,
     * depending on the language, task, and whether to predict timestamp tokens.
     *
     * Used to override the prefix tokens appended to the start of the label sequence.
     *
     * **Example: Get ids for a language**
     * ```php
     * // instantiate the tokenizer and set the prefix token to Spanish
     * $tokenizer = WhisperTokenizer::fromPretrained('Xenova/whisper-tiny');
     * $forcedDecoderIds = $tokenizer->getDecoderPromptIds(language: 'spanish');
     * // [[1, 50262], [2, 50363]]
     * ```
     *
     * @param null|string $language The language of the transcription text.
     *                              The corresponding language id token is appended to the start of the sequence for multilingual
     *                              speech recognition and speech translation tasks, e.g. for "Spanish" the token "<|es|>" is appended
     *                              to the start of sequence.
     * @param null|string $task Task identifier to append at the start of sequence (if any).
     *                          This should be used for mulitlingual fine-tuning, with "transcribe" for speech recognition and
     *                          "translate" for speech translation.
     * @param bool $noTimestamps whether to add the <|notimestamps|> token at the start of the sequence
     *
     * @return array the decoder prompt ids
     *
     * @throws Exception
     */
    public function getDecoderPromptIds(
        ?string $language = null,
        ?string $task = null,
        bool $noTimestamps = true,
    ): array {
        // <|lang_id|> <|task|> <|notimestamps|>
        $forcedDecoderIds = [];

        if ($language) {
            // User wishes to specify the language
            $language = strtolower($language);

            // Map to code from user-friendly name (e.g., "english" -> "en")
            $languageCode = $this->WHISPER_LANGUAGE_TO_CODE[$language] ?? null;

            if (null === $languageCode) {
                // User provided something that is not a language name

                if (isset(self::WHISPER_LANGUAGES[$language])) {
                    // User provided the language code directly (e.g., "en")
                    $languageCode = $language;
                } else {
                    // User provided something that is not a language code or name
                    $is_language_code = 2 === strlen($language);
                    $languages = $is_language_code ? array_keys(self::WHISPER_LANGUAGES) : array_values(self::WHISPER_LANGUAGES);

                    throw new Exception("Language \"{$language}\" is not supported. Must be one of: " . json_encode($languages));
                }
            }

            $languageTokenId = $this->model->tokenToIds["<|{$languageCode}|>"] ?? null;
            if (null === $languageTokenId) {
                throw new Exception("Unable to find language \"{$languageCode}\" in model vocabulary. Please report this issue.");
            }

            $forcedDecoderIds[] = $languageTokenId;
        } else {
            // No token will be forced, which leaves the model to predict the language
            $forcedDecoderIds[] = null;
        }

        if ($task) {
            $task = strtolower($task);
            if ('transcribe' !== $task && 'translate' !== $task) {
                throw new Exception("Task \"{$task}\" is not supported. Must be one of: [\"transcribe\", \"translate\"]");
            }

            $taskTokenId = $this->model->tokenToIds["<|{$task}|>"] ?? null;
            if (null === $taskTokenId) {
                throw new Exception("Unable to find task \"{$task}\" in model vocabulary. Please report this issue.");
            }

            $forcedDecoderIds[] = $taskTokenId;
        } else {
            // No token will be forced, which leaves the model to predict the task
            $forcedDecoderIds[] = null;
        }

        if ($noTimestamps) {
            $noTimestampsId = $this->model->tokenToIds['<|notimestamps|>'] ?? null;
            if (null === $noTimestampsId) {
                throw new Exception('Unable to find "<|notimestamps|>" in model vocabulary. Please report this issue.');
            }

            $forcedDecoderIds[] = $noTimestampsId;
        }

        // Prepend index numbers
        $mapped = array_map(static fn ($x, $i) => [$i + 1, $x], $forcedDecoderIds, array_keys($forcedDecoderIds));

        // Remove null elements
        $filtered = array_filter($mapped, static fn ($x) => null !== $x[1]);

        return array_values($filtered);
    }

    /**
     * Finds the longest common sequence among the provided sequences.
     *
     * @param array $sequences an array of sequences of token ids to compare
     * @param null|array $tokenTimestampSequences optional array of token timestamp sequences
     *
     * @return array the longest common sequence found
     *
     * @throws Exception if there is a bug within the function
     *
     * @private
     */
    private function findLongestCommonSequence(array $sequences, ?array $tokenTimestampSequences = null): array
    {
        $leftSequence = $sequences[0];
        $leftLength = count($leftSequence);
        $totalSequence = [];

        $useTokenTimestampSequences = is_array($tokenTimestampSequences) && count($tokenTimestampSequences) > 0;
        $totalTokenTimestampSequence = $useTokenTimestampSequences ? [] : null;
        $leftTokenTimestampSequence = $useTokenTimestampSequences ? $tokenTimestampSequences[0] : null;

        for ($i = 1; $i < count($sequences); ++$i) {
            $rightSequence = $sequences[$i];
            $max = 0.0;
            $maxIndices = [$leftLength, $leftLength, 0, 0];

            $rightLength = count($rightSequence);
            for ($j = 1; $j < $leftLength + $rightLength; ++$j) {
                // epsilon to favor long perfect matches
                $eps = $j / 10000.0;
                $leftStart = max(0, $leftLength - $j);
                $leftStop = min($leftLength, $leftLength + $rightLength - $j);
                $left = array_slice($leftSequence, $leftStart, $leftStop - $leftStart);
                $rightStart = max(0, $j - $leftLength);
                $rightStop = min($rightLength, $j);
                $right = array_slice($rightSequence, $rightStart, $rightStop - $rightStart);

                if (count($left) !== count($right)) {
                    throw new Exception('There is a bug within whisper `decodeASR` function, please report it. Dropping to prevent bad inference.');
                }

                $matches = count(
                    array_filter($left, static fn ($elem, $idx) => $elem === $right[$idx], ARRAY_FILTER_USE_BOTH),
                );

                $matching = $matches / $j + $eps;
                if ($matches > 1 && $matching > $max) {
                    $max = $matching;
                    $maxIndices = [$leftStart, $leftStop, $rightStart, $rightStop];
                }
            }

            [$leftStart, $leftStop, $rightStart, $rightStop] = $maxIndices;
            $leftMid = (int) floor(($leftStop + $leftStart) / 2);
            $rightMid = (int) floor(($rightStop + $rightStart) / 2);
            $totalSequence = array_merge($totalSequence, array_slice($leftSequence, 0, $leftMid));
            $leftSequence = array_slice($rightSequence, $rightMid);
            $leftLength = count($leftSequence);

            if ($useTokenTimestampSequences) {
                $totalTokenTimestampSequence = array_merge($totalTokenTimestampSequence, array_slice($leftTokenTimestampSequence, 0, $leftMid));
                $leftTokenTimestampSequence = array_slice($tokenTimestampSequences[$i], $rightMid);
            }
        }

        $totalSequence = array_merge($totalSequence, $leftSequence);

        if ($useTokenTimestampSequences) {
            $totalTokenTimestampSequence = array_merge($totalTokenTimestampSequence, $leftTokenTimestampSequence);

            return [$totalSequence, $totalTokenTimestampSequence];
        }

        return [$totalSequence, []];
    }

    /**
     * Groups tokens by word. Returns a tuple containing a list of strings with the words,
     * and a list of `token_id` sequences with the tokens making up each word.
     *
     * @private
     */
    private function combineTokensIntoWords(
        array $tokens,
        ?string $language = null,
    ): array {
        $language ??= 'english';
        $prependPunctuations = "\"'“¡¿([{-";
        $appendPunctuations = "\"'.。,，!！?？:：”)]}、";

        if (in_array($language, ['chinese', 'japanese', 'thai', 'lao', 'myanmar'])) {
            // These languages don't typically use spaces.
            [$words, $wordTokens, $tokenIndices] = $this->splitTokensOnUnicode($tokens);
        } else {
            [$words, $wordTokens, $tokenIndices] = $this->splitTokensOnSpaces($tokens);
        }

        return $this->mergePunctuations($words, $wordTokens, $tokenIndices, $prependPunctuations, $appendPunctuations);
    }

    private function decodeWithTimestamps(
        array $tokenIds,
        bool $skipSpecialTokens = false,
        ?bool $cleanUpTokenizationSpaces = null,
        float $timePrecision = 0.02,
    ): string {
        $timestampBegin = end($this->allSpecialIds) + 1;
        $outputs = [[]];

        foreach ($tokenIds as $token) {
            if ($token >= $timestampBegin) {
                $timestamp = round(($token - $timestampBegin) * $timePrecision, 2);
                $outputs[] = "<|{$timestamp}|>";
                $outputs[] = [];
            } else {
                $outputs[count($outputs) - 1][] = $token;
            }
        }

        $outputs = array_map(fn ($s) => is_string($s) ? $s : parent::decode($tokenIds, $skipSpecialTokens, $cleanUpTokenizationSpaces), $outputs);

        return implode('', $outputs);
    }

    /**
     * Combine tokens into words by splitting at any position where the tokens are decoded as valid Unicode points.
     *
     * @private
     */
    private function splitTokensOnUnicode(array $tokens): array
    {
        $decodedFull = $this->decode($tokens, decodeWithTimestamps: true);

        $replacementChar = "\u{FFFD}";

        $words = [];
        $wordTokens = [];
        $tokenIndices = [];
        $currentTokens = [];
        $currentIndices = [];
        $unicodeOffset = 0;

        foreach ($tokens as $tokenIdx => $token) {
            $currentTokens[] = $token;
            $currentIndices[] = $tokenIdx;

            $decoded = $this->decode($currentTokens, decodeWithTimestamps: true);

            if (!str_contains($decoded, $replacementChar) || $decodedFull[$unicodeOffset + strpos($decoded, $replacementChar)] === $replacementChar) {
                $words[] = $decoded;
                $wordTokens[] = $currentTokens;
                $tokenIndices[] = $currentIndices;
                $currentTokens = [];
                $currentIndices = [];
                $unicodeOffset += strlen($decoded);
            }
        }

        return [$words, $wordTokens, $tokenIndices];
    }

    /**
     * Combine tokens into words by splitting at whitespace and punctuation tokens.
     *
     * @private
     */
    private function splitTokensOnSpaces(array $tokens): array
    {
        [$subwords, $subwordTokensList, $subwordIndicesList] = $this->splitTokensOnUnicode($tokens);

        $words = [];
        $wordTokens = [];
        $tokenIndices = [];

        //        $punctuationRegex = '/^\p{P}+$/u';
        $punctuationRegex = '\p{P}\x21-\x2F\x3A-\x40\x5B-\x60\x7B-\x7E';
        $punctuationRegex = "/\\s+|([{$punctuationRegex}])+/u";

        foreach ($subwords as $i => $subword) {
            $subwordTokens = $subwordTokensList[$i];
            $subwordIndices = $subwordIndicesList[$i];

            $special = $subwordTokens[0] >= $this->model->tokenToIds['<|endoftext|>'];
            $withSpace = str_starts_with($subword, ' ');
            $trimmed = trim($subword);
            $punctuation = preg_match($punctuationRegex, $trimmed);

            if ($special || $withSpace || false === $punctuation || empty($words)) {
                $words[] = $subword;
                $wordTokens[] = $subwordTokens;
                $tokenIndices[] = $subwordIndices;
            } else {
                $ix = count($words) - 1;
                $words[$ix] .= $subword;
                $wordTokens[$ix] = array_merge($wordTokens[$ix], $subwordTokens);
                $tokenIndices[$ix] = array_merge($tokenIndices[$ix], $subwordIndices);
            }
        }

        return [$words, $wordTokens, $tokenIndices];
    }

    /**
     * Merges punctuation tokens with neighboring words.
     *
     * @private
     */
    private function mergePunctuations(array $words, array $tokens, array $indices, string $prepended, string $appended): array
    {
        $newWords = $words;
        $newTokens = $tokens;
        $newIndices = $indices;

        // prepend punctuations
        $i = count($newWords) - 2;
        $j = count($newWords) - 1;

        //        dd($newWords[8], str_contains($prepended, trim($newWords[$i])));

        while ($i >= 0) {
            if (str_starts_with($newWords[$i], ' ') && str_contains($prepended, trim($newWords[$i]))) {
                $newWords[$j] = $newWords[$i] . $newWords[$j];
                $newTokens[$j] = array_merge($newTokens[$i], $newTokens[$j]);
                $newIndices[$j] = array_merge($newIndices[$i], $newIndices[$j]);
                $newWords[$i] = '';
                $newTokens[$i] = [];
                $newIndices[$i] = [];
            } else {
                $j = $i;
            }
            --$i;
        }

        // append punctuations
        $i = 0;
        $j = 1;
        while ($j < count($newWords)) {
            if (!str_ends_with($newWords[$i], ' ') && str_contains($appended, $newWords[$j])) {
                $newWords[$i] .= $newWords[$j];
                $newTokens[$i] = array_merge($newTokens[$i], $newTokens[$j]);
                $newIndices[$i] = array_merge($newIndices[$i], $newIndices[$j]);
                $newWords[$j] = '';
                $newTokens[$j] = [];
                $newIndices[$j] = [];
            } else {
                $i = $j;
            }
            ++$j;
        }

        return [
            array_values(array_filter($newWords, static fn ($x) => '' !== $x)),
            array_values(array_filter($newTokens, static fn ($x) => count($x) > 0)),
            array_values(array_filter($newIndices, static fn ($x) => count($x) > 0)),
        ];
    }
}
