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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->string('prescription_number')->unique(); // Nomor resep
            $table->date('prescription_date');
            $table->text('instructions')->nullable(); // Instruksi penggunaan
            $table->enum('payment_status', ['UNPAID', 'PAID'])->default('UNPAID');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('status', ['ACTIVE', 'DISPENSED', 'CANCELLED'])->default('ACTIVE');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
