<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class StateEvent extends Event
{
    public function __construct(
        private int $id,
        private string $className,
        private string $state,
        private bool $value
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getValue(): bool
    {
        return $this->value;
    }
}
