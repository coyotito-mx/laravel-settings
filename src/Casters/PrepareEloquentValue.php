<?php

namespace Coyotito\LaravelSettings\Casters;

class PrepareEloquentValue implements Contracts\PrepareValue
{
    /**
     * @inheritDoc
     */
    public function __construct(protected mixed $value)
    {
    }

    /**
     * @inheritDoc
     */
    public function transform(): mixed
    {
        return json_encode($this->getRawValue());
    }

    /**
     * @inheritDoc
     */
    public function restore(): mixed
    {
        return json_decode($this->value, true);
    }

    /**
     * @inheritDoc
     */
    public function getRawValue(): mixed
    {
        return $this->value;
    }
}
