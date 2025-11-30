<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->uuid('parent_id')->nullable();
            $table->string('code', 20);
            $table->string('name', 255);
            $table->string('type', 20); // asset, liability, equity, revenue, expense
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->decimal('balance', 19, 2)->default(0);
            $table->timestamps();

            // Unique code per tenant
            $table->unique(['tenant_id', 'code']);

            // Index for hierarchical queries
            $table->index(['tenant_id', 'parent_id']);

            // Index for type filtering
            $table->index(['tenant_id', 'type']);

            // Self-referential FK for parent
            $table->foreign('parent_id')
                ->references('id')
                ->on('accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
