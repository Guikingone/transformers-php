<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\DataStructures;

use function array_fill;
use function array_map;
use function array_reverse;
use function count;
use function mb_strlen;
use function mb_substr;

/**
 * A lattice data structure to be used for tokenization.
 */
class TokenLattice
{
    /** @var int The length of the input sentence. */
    public int $len;

    /** @var TokenLatticeNode[] An array of nodes representing the lattice nodes. */
    public array $nodes = [];

    /** @var array An array of nodes representing the beginning nodes in the lattice. */
    public array $beginNodes = [];

    /** @var array An array of nodes representing the ending nodes in the lattice. */
    public array $endNodes = [];

    /**
     * Creates a new TokenLattice instance.
     *
     * @param string $sentence the input sentence to be tokenized
     * @param int $bosTokenId the beginning-of-sequence token ID
     * @param int $eosTokenId the end-of-sequence token ID
     */
    public function __construct(
        public string $sentence,
        public ?int $bosTokenId,
        public ?int $eosTokenId,
    ) {
        $this->len = mb_strlen($sentence);
        $this->beginNodes = array_fill(0, $this->len + 1, []);
        $this->endNodes = array_fill(0, $this->len + 1, []);

        $bos = new TokenLatticeNode($this->bosTokenId, 0, 0, 0, 0.0);
        $eos = new TokenLatticeNode($this->eosTokenId, 1, $this->len, 0, 0.0);

        $this->nodes[] = $bos;
        $this->nodes[] = $eos;
        $this->beginNodes[$this->len][] = $eos;
        $this->endNodes[0][] = $bos;
    }

    /**
     * Inserts a new token node into the token lattice.
     *
     * @param int $pos the starting position of the token
     * @param int $length the length of the token
     * @param float $score the score of the token
     * @param int $tokenId the token ID of the token
     */
    public function insert(int $pos, int $length, float $score, int $tokenId): void
    {
        $nodeId = count($this->nodes);
        $node = new TokenLatticeNode($tokenId, $nodeId, $pos, $length, $score);
        $this->beginNodes[$pos][] = $node;
        $this->endNodes[$pos + $length][] = $node;
        $this->nodes[] = $node;
    }

    /**
     * Implements the Viterbi algorithm to compute the most likely sequence of tokens.
     *
     * @return TokenLatticeNode[] the array of nodes representing the most likely sequence of tokens
     */
    public function viterbi(): array
    {
        $len = $this->len;
        $pos = 0;
        while ($pos <= $len) {
            if (empty($this->beginNodes[$pos])) {
                return [];
            }
            foreach ($this->beginNodes[$pos] as $rnode) {
                $rnode->prev = null;
                $bestScore = 0.0;
                $bestNode = null;
                foreach ($this->endNodes[$pos] as $lnode) {
                    $score = $lnode->backtraceScore + $rnode->score;
                    if (null === $bestNode || $score > $bestScore) {
                        $bestNode = $lnode;
                        $bestScore = $score;
                    }
                }

                if (null !== $bestNode) {
                    $rnode->prev = $bestNode;
                    $rnode->backtraceScore = $bestScore;
                } else {
                    return [];
                }
            }
            ++$pos;
        }

        $results = [];
        $root = $this->beginNodes[$len][0];
        $prev = $root->prev;
        if (null === $prev) {
            return [];
        }

        $node = $prev;
        while (null !== $node->prev) {
            $results[] = $node;
            $n = $node;
            $node = $n->prev;
        }

        return array_reverse($results);
    }

    /**
     * @return string the array of nodes representing the most likely sequence of tokens
     */
    public function piece(TokenLatticeNode $node): string
    {
        return mb_substr($this->sentence, $node->pos, $node->length);
    }

    /**
     * @return array the array of nodes representing the most likely sequence of tokens
     */
    public function tokens(): array
    {
        $nodes = $this->viterbi();

        return array_map([$this, 'piece'], $nodes);
    }

    /**
     * @return array the array of nodes representing the most likely sequence of tokens
     */
    public function tokenIds(): array
    {
        $nodes = $this->viterbi();

        return array_map(static fn ($x) => $x->tokenId, $nodes);
    }
}
