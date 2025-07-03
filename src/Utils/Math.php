<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Utils;

use ArrayAccess;
use Traversable;

use function array_fill;
use function array_keys;
use function array_map;
use function array_merge;
use function array_reduce;
use function array_search;
use function array_slice;
use function array_sum;
use function count;
use function exp;
use function floor;
use function intval;
use function log;
use function max;
use function min;
use function usort;

class Math
{
    public static function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    public static function sigmoid(array|ArrayAccess $arr): array
    {
        $result = [];

        foreach ($arr as $value) {
            $result[] = 1 / (1 + exp(-$value));
        }

        return $result;
    }

    /**
     * Calculates the logarithm of the softmax function for the input array.
     *
     * @template T of int|float
     *
     * @param array<float|int> $arr the input array to calculate the log_softmax function for
     *
     * @return array<float|int> the resulting log_softmax array
     */
    public static function logSoftmax(array $arr): array
    {
        // Compute the softmax values
        $softmaxArr = self::softmax($arr);

        // Apply log formula to each element
        return array_map(static fn ($x) => log($x), $softmaxArr);
    }

    /**
     * Compute the softmax of an array of numbers.
     *
     * @template T of int|float
     *
     * @param array<float|int> $arr the array of numbers to compute the softmax of
     *
     * @return array<float|int> the softmax array
     */
    public static function softmax(array $arr): array
    {
        // Compute the maximum value in the array
        $maxVal = max($arr);

        // Compute the exponentials of the array values
        $exps = array_map(static fn ($x) => exp($x - $maxVal), $arr);

        // Compute the sum of the exponentials
        $sumExps = array_sum($exps);

        // Compute the softmax values
        return array_map(static fn ($x) => $x / $sumExps, $exps);
    }

    /**
     * Get the top k items from an iterable, sorted by descending order.
     *
     * @param array|Traversable $items The items to be sorted
     * @param int $topK The number of top items to return (default: 0 = return all)
     *
     * @return array The top k items, sorted by descending order
     */
    public static function getTopItems(array $items, int $topK = -1): array
    {
        $indexedItems = [];
        foreach ($items as $index => $value) {
            $indexedItems[] = [$index, $value];
        }

        usort($indexedItems, static function ($a, $b) {
            return $b[1] <=> $a[1];
        });

        // Get top k items if top_k > 0
        if (-1 !== $topK && $topK > 0) {
            $indexedItems = array_slice($indexedItems, 0, $topK);
        }

        return $indexedItems;
    }

    /**
     * Compute the Cartesian product of given arrays.
     *
     * @param array ...$a Arrays to compute the product
     *
     * @return array Returns the computed Cartesian product as an array
     */
    public static function product(...$a): array
    {
        // Cartesian product of items
        // Adapted from https://stackoverflow.com/a/43053803

        return array_reduce($a, static function ($carry, $array) {
            return array_merge(
                ...array_map(static function ($d) use ($array) {
                    return array_map(static function ($e) use ($d) {
                        return [...$d, $e];
                    }, $array);
                }, $carry),
            );
        }, [[]]);
    }

    /**
     * Helper method to permute a typed array directly.
     *
     * @template T
     *
     * @param T $array
     * @param int[] $shape
     * @param int[] $axes
     *
     * @return array{0: T, 1: int[]} the permuted array and the new shape
     */
    public static function permuteData($array, array $shape, array $axes): array
    {
        // Calculate the new shape of the permuted array
        // and the stride of the original array
        $newShape = array_fill(0, count($axes), 0);
        $stride = array_fill(0, count($axes), 0);

        for ($i = count($axes) - 1, $s = 1; $i >= 0; --$i) {
            $stride[$i] = $s;
            $newShape[$i] = $shape[$axes[$i]];
            $s *= $newShape[$i];
        }

        // Precompute inverse mapping of stride
        $invStride = array_map(static function ($_, $i) use ($stride, $axes) {
            return $stride[array_search($i, $axes)];
        }, $stride, array_keys($stride));

        // Create the permuted array with the new shape
        $permutedData = array_fill(0, count($array), null);

        // Permute the original array to the new array
        for ($i = 0; $i < count($array); ++$i) {
            $newIndex = 0;
            for ($j = count($shape) - 1, $k = $i; $j >= 0; --$j) {
                $newIndex += ($k % $shape[$j]) * $invStride[$j];
                $k = intval(floor($k / $shape[$j]));
            }
            $permutedData[$newIndex] = $array[$i];
        }

        return [$permutedData, $newShape];
    }
}
