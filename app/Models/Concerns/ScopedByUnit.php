<?php

namespace App\Models\Concerns;

use App\Models\Scopes\BerdasarkanUnit;

trait ScopedByUnit
{
    protected static function bootScopedByUnit(): void
    {
        static::addGlobalScope(new BerdasarkanUnit);
    }
}
