<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brewing_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('ingredients')->nullable(); // JSON or text for ingredients list
            $table->text('steps'); // JSON or text for step-by-step instructions
            $table->string('image_path')->nullable();
            $table->integer('prep_time')->nullable(); // in minutes
            $table->integer('brew_time')->nullable(); // in minutes
            $table->string('difficulty')->default('medium'); // easy, medium, hard
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brewing_guides');
    }
};
