<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Tensor;

use FFI;

use function class_exists;

class TensorBufferFactory
{
    public function isAvailable(): bool
    {
        return class_exists(FFI::class);
    }

    public function Buffer(int $size, int $dtype): TensorBuffer
    {
        return new TensorBuffer($size, $dtype);
    }
}
