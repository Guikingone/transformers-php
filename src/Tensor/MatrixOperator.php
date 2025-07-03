<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Tensor;

use Interop\Polite\Math\Matrix\NDArray;

class MatrixOperator extends \Rindow\Math\Matrix\MatrixOperator
{
    protected function alloc(mixed $array, ?int $dtype = null, ?array $shape = null): NDArray
    {
        if (null === $dtype) {
            // $dtype = $this->resolveDtype($array);
            $dtype = $this->defaultFloatType;
        }

        return new Tensor($array, $dtype, $shape);
    }
}
