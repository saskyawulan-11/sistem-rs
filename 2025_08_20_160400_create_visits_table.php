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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->string('visit_number')->unique(); // Nomor kunjungan
            $table->integer('queue_number'); // Nomor antrian
            $table->date('visit_date');
            $table->time('registration_time');
            $table->time('examination_time')->nullable();
            $table->time('completion_time')->nullable();
            $table->enum('status', ['WAITING', 'EXAMINING', 'COMPLETED', 'CANCELLED'])->default('WAITING');
            $table->text('complaints')->nullable(); // Keluhan pasien
            $table->text('diagnosis')->nullable(); // Diagnosis dokter
            $table->text('treatment_plan')->nullable(); // Rencana pengobatan
            $table->enum('payment_status', ['UNPAID', 'PAID'])->default('UNPAID');
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
