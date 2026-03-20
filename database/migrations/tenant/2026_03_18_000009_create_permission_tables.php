<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        if (! Schema::connection('tenant')->hasTable($tableNames['permissions'])) {
            Schema::connection('tenant')->create($tableNames['permissions'], function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        if (! Schema::connection('tenant')->hasTable($tableNames['roles'])) {
            Schema::connection('tenant')->create($tableNames['roles'], function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['tenant_id', 'name', 'guard_name'], 'roles_tenant_name_guard_unique');
            });
        }

        if (! Schema::connection('tenant')->hasTable($tableNames['model_has_permissions'])) {
            Schema::connection('tenant')->create($tableNames['model_has_permissions'], function (Blueprint $table) use ($columnNames, $pivotPermission) {
                $table->unsignedBigInteger($pivotPermission);
                $table->string('model_type');
                $table->unsignedBigInteger($columnNames['model_morph_key']);

                $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');
                $table->primary([$pivotPermission, $columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_permission_model_type_primary');
            });
        }

        if (! Schema::connection('tenant')->hasTable($tableNames['model_has_roles'])) {
            Schema::connection('tenant')->create($tableNames['model_has_roles'], function (Blueprint $table) use ($columnNames, $pivotRole) {
                $table->unsignedBigInteger($pivotRole);
                $table->string('model_type');
                $table->unsignedBigInteger($columnNames['model_morph_key']);

                $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');
                $table->primary([$pivotRole, $columnNames['model_morph_key'], 'model_type'], 'model_has_roles_role_model_type_primary');
            });
        }

        if (! Schema::connection('tenant')->hasTable($tableNames['role_has_permissions'])) {
            Schema::connection('tenant')->create($tableNames['role_has_permissions'], function (Blueprint $table) use ($pivotRole, $pivotPermission) {
                $table->unsignedBigInteger($pivotPermission);
                $table->unsignedBigInteger($pivotRole);
                $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
            });
        }
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');

        Schema::connection('tenant')->dropIfExists($tableNames['role_has_permissions']);
        Schema::connection('tenant')->dropIfExists($tableNames['model_has_roles']);
        Schema::connection('tenant')->dropIfExists($tableNames['model_has_permissions']);
        Schema::connection('tenant')->dropIfExists($tableNames['roles']);
        Schema::connection('tenant')->dropIfExists($tableNames['permissions']);
    }
};
