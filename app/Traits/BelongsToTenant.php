<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (tenant()) {
                $builder->where(tenant()->getForeignKey(), tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (tenant()) {
                $model->{tenant()->getForeignKey()} = tenant()->id;
            }
        });
    }
}