<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Generation\LogitsProcessors;

use Codewithkyrian\Transformers\Tensor\Tensor;
use IteratorAggregate;
use Traversable;

use function count;

class LogitsProcessorList implements IteratorAggregate
{
    /** @var LogitsProcessor[] Array of logits processor functions */
    private array $processors = [];

    /**
     * Applies all logits processors in the list to a batch of logits, modifying them in-place.
     *
     * @param array $inputIds the input IDs for the language model
     * @param Tensor $batchedLogits a 2D array of logits, where each row corresponds to a single input sequence
     */
    public function __invoke(array $inputIds, Tensor &$batchedLogits): void
    {
        for ($i = 0; $i < count($batchedLogits); ++$i) {
            foreach ($this->processors as $processor) {
                $processor($inputIds, $batchedLogits[$i]); // Apply processors in-place
            }
        }
    }

    /**
     * Adds a new logits processor to the list.
     *
     * @param LogitsProcessor $item the logits processor function to add
     */
    public function push(LogitsProcessor $item): void
    {
        $this->processors[] = $item;
    }

    /**
     * Adds multiple logits processors to the list.
     *
     * @param LogitsProcessor[] $items the logits processor functions to add
     */
    public function extend(Traversable $items): void
    {
        foreach ($items as $item) {
            $this->processors[] = $item;
        }
    }

    /**
     * Allows iteration over the processors.
     *
     * @return Traversable an iterator over the processors
     */
    public function getIterator(): Traversable
    {
        yield from $this->processors;
    }
}
