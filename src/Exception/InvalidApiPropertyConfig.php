<?php

namespace Rikudou\JsonApiBundle\Exception;

use InvalidArgumentException;
use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Pure;
use Throwable;

final class InvalidApiPropertyConfig extends InvalidArgumentException
{
    public const TYPE_GETTER = 'getter';

    public const TYPE_SETTER = 'setter';

    public const TYPE_ADDER = 'adder';

    public const TYPE_REMOVER = 'remover';

    #[Pure]
    public function __construct(
        #[ExpectedValues(valuesFromClass: self::class)]
        string $type,
        ?string $propertyName = null,
        Throwable $previous = null,
    ) {
        $message = "Invalid api property {$type}";
        if ($propertyName !== null) {
            $message .= " (property '{$propertyName}')";
        }
        $message .= '.';
        parent::__construct($message, 0, $previous);
    }
}
