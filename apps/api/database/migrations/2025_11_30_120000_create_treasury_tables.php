<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_repositories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');

            // Identification
            $table->string('code', 30);
            $table->string('name', 100);
            $table->string('type', 30); // cash_register, safe, bank_account, virtual

            // For bank accounts
            $table->string('bank_name', 100)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('iban', 50)->nullable();
            $table->string('bic', 20)->nullable();

            // Balance tracking
            $table->decimal('balance', 15, 2)->default(0);
            $table->timestamp('last_reconciled_at')->nullable();
            $table->decimal('last_reconciled_balance', 15, 2)->nullable();

            // Access control
            $table->uuid('location_id')->nullable();
            $table->uuid('responsible_user_id')->nullable();

            // Linked account
            $table->uuid('account_id')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('payment_methods', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');

            // Identification
            $table->string('code', 30);
            $table->string('name', 100);

            // Universal switches
            $table->boolean('is_physical')->default(false);
            $table->boolean('has_maturity')->default(false);
            $table->boolean('requires_third_party')->default(false);
            $table->boolean('is_push')->default(true);
            $table->boolean('has_deducted_fees')->default(false);
            $table->boolean('is_restricted')->default(false);

            // Fees
            $table->string('fee_type', 20)->nullable(); // none, fixed, percentage, mixed
            $table->decimal('fee_fixed', 10, 2)->default(0);
            $table->decimal('fee_percent', 5, 2)->default(0);

            // Restrictions
            $table->string('restriction_type', 50)->nullable(); // food, fuel, etc.

            // Linked accounts (nullable, no FK constraint for flexibility)
            $table->uuid('default_journal_id')->nullable();
            $table->uuid('default_account_id')->nullable();
            $table->uuid('fee_account_id')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->integer('position')->default(0);

            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('payment_repositories');
    }
};
