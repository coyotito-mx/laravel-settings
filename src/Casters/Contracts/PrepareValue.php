<?php

namespace Coyotito\LaravelSettings\Casters\Contracts;

interface PrepareValue
{
    /**
     * Initialize the caster with the value to be processed
     *
     * @param mixed $value The value to be processed
     */
    public function __construct(mixed $value);

    /**
     * Transform the value before storing it
     */
    public function transform(): mixed;

    /**
     * Restore the value when retrieving it
     */
    public function restore(): mixed;

    /**
     * Get the raw value without any processing
     */
    public function getRawValue(): mixed;
}
