<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('pending');
            $table->string('plan')->default('trial');
            $table->string('tax_id')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->jsonb('settings')->default('{}');
            $table->jsonb('data')->nullable(); // Required by stancl/tenancy
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->timestamps();

            $table->index('slug');
            $table->index('status');
            $table->index('plan');
            $table->index('country_code');
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
