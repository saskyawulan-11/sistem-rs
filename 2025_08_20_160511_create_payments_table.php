<?php

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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->onDelete('cascade');
            $table->string('payment_number')->unique(); // Nomor pembayaran
            $table->enum('payment_type', ['CASH', 'DEBIT', 'CREDIT', 'BPJS', 'TRANSFER']);
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['PENDING', 'PAID', 'FAILED', 'REFUNDED'])->default('PENDING');
            $table->string('payment_gateway')->nullable(); // Midtrans, dll
            $table->string('transaction_id')->nullable(); // ID transaksi dari payment gateway
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
