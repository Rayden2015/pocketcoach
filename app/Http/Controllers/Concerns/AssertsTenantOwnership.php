<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;

trait AssertsTenantOwnership
{
    protected function assertBelongsToTenant(Model $model, Tenant $tenant, string $attribute = 'tenant_id'): void
    {
        abort_unless((int) $model->getAttribute($attribute) === (int) $tenant->id, 404);
    }
}
