<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('tenants', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // coffee shop name
        $table->string('subdomain')->unique(); // for subdomain identification
        $table->string('plan')->default('starter'); // starter, standard, business
        $table->json('settings')->nullable(); // shop logo, colors, etc.
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
