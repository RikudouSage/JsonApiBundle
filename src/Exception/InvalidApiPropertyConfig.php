<?php

namespace Rikudou\JsonApiBundle\Exception;

use InvalidArgumentException;
use Throwable;

final class InvalidApiPropertyConfig extends InvalidArgumentException
{
    public const TYPE_GETTER = 'getter';

    public const TYPE_SETTER = 'setter';

    public const TYPE_ADDER = 'adder';

    public const TYPE_REMOVER = 'remover';

    public function __construct(string $type, ?string $propertyName = null, Throwable $previous = null)
    {
        $message = "The api property config is invalid, the method '{$type}' does not exist.";
        if ($propertyName !== null) {
            $message .= " Property name: {$propertyName}.";
        }
        parent::__construct($message, 0, $previous);
    }
}
