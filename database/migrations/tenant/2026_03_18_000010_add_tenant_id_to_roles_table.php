<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('tenant')->hasTable('roles') && ! Schema::connection('tenant')->hasColumn('roles', 'tenant_id')) {
            Schema::connection('tenant')->table('roles', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id')->index();
                $table->dropUnique('roles_name_guard_name_unique');
                $table->unique(['tenant_id', 'name', 'guard_name'], 'roles_tenant_name_guard_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('tenant')->hasTable('roles') && Schema::connection('tenant')->hasColumn('roles', 'tenant_id')) {
            Schema::connection('tenant')->table('roles', function (Blueprint $table) {
                $table->dropUnique('roles_tenant_name_guard_unique');
                $table->dropColumn('tenant_id');
                $table->unique(['name', 'guard_name']);
            });
        }
    }
};
