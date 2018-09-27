<?php
namespace Flarum\Core\PhoneVerification\PaaSoo\Exception;

use Exception;
use Throwable;

class HttpResponseException extends Exception
{
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message ?? 'Response error.', $code, $previous);
    }
}