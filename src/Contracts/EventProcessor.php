<?php

declare(strict_types=1);

namespace MrThito\MicroService\Contracts;

interface EventProcessor
{
    /**
     * @param  array<string, mixed>  $event
     */
    public function process(array $event): void;
}
