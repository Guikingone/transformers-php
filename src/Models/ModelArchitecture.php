<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models;

use Codewithkyrian\Transformers\Exceptions\MissingModelInputException;
use Codewithkyrian\Transformers\Exceptions\ModelExecutionException;
use Codewithkyrian\Transformers\Models\Pretrained\PretrainedModel;
use Codewithkyrian\Transformers\Tensor\Tensor;
use Codewithkyrian\Transformers\Utils\GenerationConfig;
use Error;
use Interop\Polite\Math\Matrix\NDArray;

use function array_column;
use function array_fill;
use function array_map;
use function array_slice;
use function count;
use function in_array;
use function is_array;
use function property_exists;

enum ModelArchitecture: string
{
    case EncoderDecoder = 'EncoderDecoder';
    case EncoderOnly = 'EncoderOnly';
    case DecoderOnly = 'DecoderOnly';
    case Seq2SeqLM = 'Seq2SeqLM';
    case Vision2Seq = 'Vision2Seq';
    case MaskGeneration = 'MaskGeneration';

    // <editor-fold desc="Abstract methods">

    public function canGenerate(): bool
    {
        return match ($this) {
            self::EncoderOnly, self::EncoderDecoder => false,
            default => true,
        };
    }

    public function runBeam(PretrainedModel $model, array &$beam): array
    {
        return match ($this) {
            self::DecoderOnly => $this->decoderRunBeam($model, $beam),
            self::Seq2SeqLM, self::Vision2Seq => $this->seq2seqRunBeam($model, $beam),
            default => throw new Error('This model type does not support beam search'),
        };
    }

    public function startBeams(
        PretrainedModel $model,
        Tensor $inputTokenIds,
        GenerationConfig $generationConfig,
        int $numOutputTokens,
        ?Tensor $inputsAttentionMask = null,
    ): array {
        return match ($this) {
            self::DecoderOnly => $this->decoderStartBeams($model, $inputTokenIds, $generationConfig, $numOutputTokens, $inputsAttentionMask),
            self::Seq2SeqLM, self::Vision2Seq => $this->seq2seqStartBeams($model, $inputTokenIds, $generationConfig, $numOutputTokens),
            default => throw new Error('This model type does not support beam search'),
        };
    }

    public function updateBeam(array &$beam, int $newTokenId): void
    {
        match ($this) {
            self::DecoderOnly => $this->decoderUpdatebeam($beam, $newTokenId),
            self::Seq2SeqLM, self::Vision2Seq => $this->seq2seqUpdatebeam($beam, $newTokenId),
            default => throw new Error('This model type does not support beam search'),
        };
    }

    public function forward(PretrainedModel $model, array $modelInputs): array
    {
        return match ($this) {
            self::EncoderOnly => $this->encoderForward($model, $modelInputs),
            self::DecoderOnly => $this->decoderForward($model, $modelInputs),
            self::Seq2SeqLM, self::Vision2Seq => $this->seq2seqForward($model, $modelInputs),
            default => throw new Error('This model type does not have a forward method'),
        };
    }

    // </editor-fold>

    // <editor-fold desc="Encoder methods">

    private function encoderForward(PretrainedModel $model, array $modelInputs): array
    {
        $encoderFeeds = [];

        $inputNames = array_column($model->session->inputs(), 'name');

        foreach ($inputNames as $inputName) {
            $encoderFeeds[$inputName] = $modelInputs[$inputName];
        }

        $hasTokenTypeIds = in_array('token_type_ids', $inputNames);

        if ($hasTokenTypeIds) {
            // Assign default `token_type_ids` (all zeroes) to the `encoderFeeds` if the model expects it,
            // but they weren't created by the tokenizer.
            $encoderFeeds['token_type_ids'] ??= Tensor::zerosLike($encoderFeeds['input_ids']);
        }

        return $model->runSession($model->session, $encoderFeeds);
    }

    // </editor-fold>

    // <editor-fold desc="Decoder methods">

    /**
     * Runs a single step of the text generation process for a given beam.
     *
     * @param PretrainedModel $model the text generation model object
     * @param array $beam the beam to run the generation process for
     *
     * @return array the output of the generation process for the given beam
     */
    private function decoderRunBeam(PretrainedModel $model, array &$beam): array
    {
        $attnMaskLength = count($beam['output_token_ids']);
        $attnMaskData = array_fill(0, $attnMaskLength, 1);

        // 1. Prepare
        $modelInputs = [
            'input_ids' => $beam['model_input_ids'],
            'attention_mask' => new Tensor($attnMaskData, Tensor::int64, [1, $attnMaskLength]),
            'past_key_values' => $beam['prev_model_outputs']['past_key_values'] ?? null,
        ];

        // 2. Run
        $output = $model->forward($modelInputs);

        // 3. Update
        $beam['prev_model_outputs'] = $output;

        return $output;
    }

    /** Starts the generation of text by initializing the beams for the given input token IDs.
     * @param PretrainedModel $model the text generation model object
     * @param Tensor $inputTokenIds a tensor of input token IDs to generate text from
     * @param GenerationConfig $generationConfig the generation config
     * @param int $numOutputTokens the maximum number of tokens to generate for each beam
     * @param null|Tensor $inputsAttentionMask the attention mask tensor for the input token IDs
     *
     * @return array an array of beams initialized with the given inputs and parameters
     */
    private function decoderStartBeams(
        PretrainedModel $model,
        Tensor $inputTokenIds,
        GenerationConfig $generationConfig,
        int $numOutputTokens,
        ?Tensor $inputsAttentionMask = null,
    ): array {
        $beams = [];
        $beamId = 0;

        foreach ($inputTokenIds as $tokens) {
            $outputTokenIds = array_map('intval', $tokens->toArray());

            // TODO: Improve for parallel execution
            $tokens = $tokens->reshape([1, ...$tokens->shape()]);

            $attnMask = null;
            if (null !== $inputsAttentionMask) {
                $attnMask = $inputsAttentionMask[$beamId];
                $attnMask = $attnMask->reshape([1, ...$attnMask->shape()]);
            } else {
                $attnMask = $model->prepareAttentionMask($tokens);
            }

            $start = [
                'input' => $tokens,
                'model_input_ids' => $tokens,
                'attention_mask' => $attnMask,
                'prev_model_outputs' => null,

                'output_token_ids' => $outputTokenIds,
                'num_output_tokens' => $numOutputTokens,

                'done' => false,
                'score' => 0,
                'id' => $beamId++, // assign unique id to beams
            ];

            $beams[] = $start;
        }

        return $beams;
    }

    /**
     * Update a beam with a new token ID.
     *
     * @param array $beam the beam to update
     * @param int $newTokenId the new token ID to add to the beam
     */
    private function decoderUpdatebeam(array &$beam, int $newTokenId): void
    {
        $beam['output_token_ids'][] = $newTokenId;
        $beam['model_input_ids'] = new Tensor([$newTokenId], NDArray::int64, [1, 1]);
    }

    /**
     * Forward pass for the decoder model.
     *
     * @param PretrainedModel $model the model to use for the forward pass
     * @param array $modelInputs the inputs to the model
     *
     * @return array the output of the forward pass
     *
     * @throws MissingModelInputException|ModelExecutionException
     */
    private function decoderForward(PretrainedModel $model, array $modelInputs): array
    {
        ['input_ids' => $inputIds, 'past_key_values' => $pastKeyValues, 'attention_mask' => $attentionMask]
            = $modelInputs;

        $decoderFeeds = [
            'input_ids' => $inputIds,
            'attention_mask' => $attentionMask ?? $model->prepareAttentionMask($inputIds),
        ];

        $useCacheBranch = (bool) $pastKeyValues;

        $inputNames = array_column($model->session->inputs(), 'name');

        if (in_array('use_cache_branch', $inputNames)) {
            $decoderFeeds['use_cache_branch'] = new Tensor([$useCacheBranch], Tensor::bool, [1]);
        }

        $model->preparePositionIds($inputNames, $decoderFeeds, $useCacheBranch);
        $model->addPastKeyValues($decoderFeeds, $pastKeyValues);

        $decoderResults = $model->runSession($model->session, $decoderFeeds);

        $logits = $decoderResults['logits'];

        $pastKeyValues = $model->getPastKeyValues($decoderResults, $pastKeyValues);

        return ['logits' => $logits, 'past_key_values' => $pastKeyValues];
    }

    // </editor-fold>

    // <editor-fold desc="Seq2Seq methods">

    private function seq2seqRunBeam(PretrainedModel $model, array &$beam): array
    {
        $inputName = $model->mainInputName;

        $decoderInputIds = $beam['output_token_ids'];

        if ($beam['prev_model_outputs']) {
            // After the first step, `prev_model_outputs` won't be null.
            // So, we cut decoder_input_ids if past is used
            $decoderInputIds = array_slice($decoderInputIds, -1);
        }

        // 1. Prepare
        $modelInputs = [
            $inputName => $beam['inputs'],
            'decoder_input_ids' => new Tensor($decoderInputIds, Tensor::int64, [1, count($decoderInputIds)]),
            'encoder_outputs' => $beam['encoder_outputs'],
            'past_key_values' => $beam['prev_model_outputs']['past_key_values'] ?? null,
        ];

        if (isset($beam['attention_mask'])) {
            $modelInputs['attention_mask'] = $beam['attention_mask'];
        }

        // 2. Run
        $output = $model->forward($modelInputs);

        // 3. Update
        $beam['prev_model_outputs'] = $output;
        $beam['encoder_outputs'] = $output['encoder_outputs'];

        return $output;
    }

    /** Start the beam search process for the seq2seq model.
     * @param PretrainedModel $model the model to use for the beam search
     * @param Tensor $inputTokenIds array of input token ids for each input sequence
     * @param GenerationConfig $generationConfig the generation configuration
     * @param int $numOutputTokens the maximum number of output tokens for the model
     *
     * @return array array of beam search objects
     */
    private function seq2seqStartBeams(
        PretrainedModel $model,
        Tensor $inputTokenIds,
        GenerationConfig $generationConfig,
        int $numOutputTokens,
    ): array {
        $beams = [];
        $beamId = 0;

        $requiresAttentionMask = !property_exists($model, 'requiresAttentionMask') || $model->requiresAttentionMask;

        $decoder_input_ids = $generationConfig->decoder_input_ids
            ?? $generationConfig->decoder_start_token_id
            ?? $generationConfig->bos_token_id
            ?? $generationConfig->eos_token_id;

        // TODO support batched decoder_input_ids
        if (!is_array($decoder_input_ids)) {
            $decoder_input_ids = [$decoder_input_ids];
        }

        foreach ($inputTokenIds as $tokens) {
            // TODO: Improve
            // Currently, just add back batch dimension.
            // In future, allow for true parallel execution
            $tokens = $tokens->reshape([1, ...$tokens->shape()]);

            // Create beam
            $start = [
                'inputs' => $tokens,
                'encoder_outputs' => null,
                'prev_model_outputs' => null,
                'output_token_ids' => $decoder_input_ids,
                'done' => false,
                'score' => 0,
                'id' => $beamId++, // assign unique id to beams
            ];

            if ($requiresAttentionMask) {
                $start['attention_mask'] = $model->prepareAttentionMask($tokens);
            }

            $beams[] = $start;
        }

        return $beams;
    }

    private function seq2seqUpdatebeam(array &$beam, int $newTokenId): void
    {
        $beam['output_token_ids'][] = $newTokenId;
    }

    private function seq2seqForward(PretrainedModel $model, array $modelInputs): array
    {
        ['encoder_outputs' => $encoderOutputs, 'past_key_values' => $pastKeyValues] = $modelInputs;

        if (null === $encoderOutputs) {
            // Encoder outputs are not given, so we must compute them.
            $encoderOutputs = $this->encoderForward($model, $modelInputs)['last_hidden_state'];
        }

        $decoderFeeds = [
            'input_ids' => $modelInputs['decoder_input_ids'],
            'encoder_hidden_states' => $encoderOutputs,
        ];

        $useCacheBranch = (bool) $pastKeyValues;

        $inputNames = array_column($model->decoderMergedSession->inputs(), 'name');

        if (in_array('use_cache_branch', $inputNames)) {
            $decoderFeeds['use_cache_branch'] = new Tensor([$useCacheBranch], Tensor::bool, [1]);
        }

        if (in_array('encoder_attention_mask', $inputNames)) {
            $decoderFeeds['encoder_attention_mask'] = $modelInputs['attention_mask'];
        }

        $model->preparePositionIds($inputNames, $decoderFeeds, $useCacheBranch);
        $model->addPastKeyValues($decoderFeeds, $pastKeyValues);

        $decoderResults = $model->runSession($model->decoderMergedSession, $decoderFeeds);
        $logits = $decoderResults['logits'];
        $pastKeyValues = $model->getPastKeyValues($decoderResults, $pastKeyValues);

        // Get cross attention and/or decoder attentions if they are present
        $attns = $model->getAttentions($decoderResults);

        return [
            'logits' => $logits,
            'past_key_values' => $pastKeyValues,
            'encoder_outputs' => $encoderOutputs,
            ...$attns,
        ];
    }

    // </editor-fold>
}
