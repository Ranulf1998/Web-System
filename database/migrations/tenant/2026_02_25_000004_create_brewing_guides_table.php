<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('brewing_guides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('ingredients')->nullable();
            $table->text('steps');
            $table->string('image_path')->nullable();
            $table->integer('prep_time')->nullable();
            $table->integer('brew_time')->nullable();
            $table->string('difficulty')->default('medium');
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('brewing_guides');
    }
};
