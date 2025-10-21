<?php

namespace App\Exceptions;

use Exception;

class ApidianRequestException extends Exception
{
    /**
     * Additional context about the failing request.
     */
    protected $context = [];

    public function __construct(string $message, array $context = [], int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Retrieve request context.
     */
    public function context(): array
    {
        return $this->context;
    }
}
