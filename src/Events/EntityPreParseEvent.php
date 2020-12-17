<?php

namespace Rikudou\JsonApiBundle\Events;

use JetBrains\PhpStorm\ArrayShape;
use Symfony\Contracts\EventDispatcher\Event;

final class EntityPreParseEvent extends Event
{
    private const ARRAY_SHAPE = [
        'data' => [
            'id' => 'int|string',
            'type' => 'string',
            'attributes' => 'array',
            'relationships' => 'array',
        ]
    ];

    /**
     * @var array
     */
    #[ArrayShape(self::ARRAY_SHAPE)]
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}
