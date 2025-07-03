<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\PreTokenizers;

use function str_replace;
use function str_starts_with;

/**
 * This PreTokenizer replaces spaces with the given replacement character, adds a prefix space if requested,
 *  and returns a list of tokens.
 */
class MetaspacePreTokenizer extends PreTokenizer
{
    /**
     * Whether to add a prefix space to the first token.
     */
    protected bool $addPrefixSpace;

    /**
     * The character to replace spaces with.
     */
    protected string $replacement;

    /**
     * optional string representation of the replacement character.
     */
    protected string $strRep;

    /**
     * The metaspace prepending scheme.
     */
    protected string $prependScheme;

    public function __construct(protected array $config)
    {
        $this->addPrefixSpace = $this->config['add_prefix_space'] ?? false;
        $this->replacement = $this->config['replacement'];
        $this->strRep = $this->config['str_rep'] ?? $this->replacement;
        $this->prependScheme = $this->config['prepend_scheme'] ?? 'always';
    }

    /**
     * This method takes a string, replaces spaces with the replacement character,
     *  adds a prefix space if requested, and returns a new list of tokens.
     *
     * @param array{ section_index : int} $options
     *
     * @return array|string[]
     */
    public function preTokenizeText(array|string $text, array $options): array
    {
        $normalized = str_replace(' ', $this->strRep, $text);

        $sectionIndex = $options['section_index'] ?? null;

        if (
            // We add a prefix space if:
            //  (1) The addPrefixSpace option is enabled and the normalized
            //      token does not already start with the replacement character.
            ($this->addPrefixSpace && !str_starts_with($normalized, $this->replacement))

            // and (2) either:
            //  (a) prepend_scheme is 'always'
            //  (b) prepend_scheme is 'first' and this is the first section
            && (
                'always' === $this->prependScheme
                || ('first' === $this->prependScheme && 0 === $sectionIndex)
            )
        ) {
            $normalized = $this->strRep . $normalized;
        }

        // Return as an array
        return [$normalized];
    }
}
