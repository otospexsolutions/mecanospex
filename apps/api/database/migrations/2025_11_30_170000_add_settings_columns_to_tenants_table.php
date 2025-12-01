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
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('legal_name')->nullable()->after('name');
            $table->string('registration_number')->nullable()->after('tax_id');
            $table->json('address')->nullable()->after('registration_number');
            $table->string('phone', 30)->nullable()->after('address');
            $table->string('email')->nullable()->after('phone');
            $table->string('website')->nullable()->after('email');
            $table->string('logo_path')->nullable()->after('website');
            $table->string('primary_color', 7)->default('#2563EB')->after('logo_path');
            $table->string('timezone', 50)->default('Europe/Paris')->after('currency_code');
            $table->string('date_format', 20)->default('DD/MM/YYYY')->after('timezone');
            $table->string('locale', 10)->default('fr')->after('date_format');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn([
                'legal_name',
                'registration_number',
                'address',
                'phone',
                'email',
                'website',
                'logo_path',
                'primary_color',
                'timezone',
                'date_format',
                'locale',
            ]);
        });
    }
};
