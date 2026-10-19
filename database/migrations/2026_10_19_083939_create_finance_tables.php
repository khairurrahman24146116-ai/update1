<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_unit_settings', function (Blueprint $table) {
            $table->id();
            $table->string('unit', 10)->unique();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('finance_categories', function (Blueprint $table) {
            $table->id();
            $table->string('unit', 10)->index();
            $table->string('type', 10)->index();
            $table->string('name', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['unit', 'type', 'name']);
        });

        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('unit', 10)->index();
            $table->foreignId('category_id')->nullable()->constrained('finance_categories')->nullOnDelete();
            $table->string('type', 10)->index();
            $table->string('source', 20)->default('manual');
            $table->date('transaction_date')->index();
            $table->decimal('amount', 15, 2);
            $table->string('description');
            $table->string('destination')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('reference', 60)->nullable()->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['unit', 'type', 'transaction_date']);
        });

        Schema::create('spp_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('unit', 10)->index();
            $table->foreignId('student_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('period', 7);
            $table->date('due_date')->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('status', 12)->default('unpaid')->index();
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'period']);
            $table->index(['unit', 'status']);
        });

        Schema::create('spp_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spp_invoice_id')->constrained('spp_invoices')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('unit', 10)->index();
            $table->string('receipt_number', 40)->unique();
            $table->decimal('amount', 15, 2);
            $table->dateTime('paid_at')->index();
            $table->string('method', 30)->default('tunai');
            $table->string('notes')->nullable();
            $table->string('proof_path')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['unit', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spp_payments');
        Schema::dropIfExists('spp_invoices');
        Schema::dropIfExists('finance_transactions');
        Schema::dropIfExists('finance_categories');
        Schema::dropIfExists('finance_unit_settings');
    }
};
