<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 0.1.1: Add personal info columns to tenants table.
 *
 * Tenant = Account/Person (subscription holder), NOT a company.
 * Company-specific fields (tax_id, country_code, etc.) will be moved
 * to the companies table in a later migration.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable()->after('name');
            $table->string('last_name', 100)->nullable()->after('first_name');
            $table->string('preferred_locale', 10)->default('fr')->after('last_name');
        });

        // Migrate existing 'name' data to first_name for existing tenants
        DB::statement('UPDATE tenants SET first_name = name WHERE first_name IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'preferred_locale']);
        });
    }
};
