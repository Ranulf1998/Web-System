<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = ['name', 'guard_name', 'tenant_id'];

    public function getConnectionName()
    {
        return $this->connection ?? (tenant() ? 'tenant' : 'central');
    }

    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($role) {
            if (tenant() && !$role->tenant_id) {
                $role->tenant_id = tenant()->id;
            }
        });
    }
}