<?php

namespace RSE\DynaFields\Concerns;

use Illuminate\Support\Str;

trait HasUlidPrimaryKey
{
    public function initializeHasUlidPrimaryKey(): void
    {
        $this->incrementing = false;
        $this->keyType      = 'string';
    }

    protected static function bootHasUlidPrimaryKey(): void
    {
        static::creating(function ($model) {
            $key             = $model->getKeyName();
            $model->{$key}   = empty($model->{$key}) ? (string) Str::ulid() : (string) $model->{$key};
        });
    }
}
