<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Exceptions;

use Exception;

class TemplateParseException extends Exception implements TransformersException
{
    public static function undefinedVariable($variableName): TemplateParseException
    {
        return new self("Undefined variable:  $variableName");
    }
}
