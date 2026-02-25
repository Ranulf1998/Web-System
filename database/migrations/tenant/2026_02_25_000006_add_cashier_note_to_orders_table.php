<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'cashier_note')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->text('cashier_note')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'cashier_note')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('cashier_note');
            });
        }
    }
};
