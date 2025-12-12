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
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('visit_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->text('symptoms'); // Gejala
            $table->text('diagnosis'); // Diagnosis
            $table->text('treatment'); // Pengobatan
            $table->text('notes')->nullable(); // Catatan tambahan
            $table->decimal('blood_pressure', 5, 2)->nullable(); // Tekanan darah
            $table->decimal('temperature', 4, 1)->nullable(); // Suhu
            $table->integer('pulse_rate')->nullable(); // Denyut nadi
            $table->decimal('weight', 5, 2)->nullable(); // Berat badan
            $table->decimal('height', 5, 2)->nullable(); // Tinggi badan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
