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
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Kode obat
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category'); // Kategori obat
            $table->string('unit'); // Satuan (tablet, kapsul, dll)
            $table->decimal('price', 10, 2);
            $table->integer('stock');
            $table->integer('min_stock')->default(10); // Stok minimum
            $table->enum('status', ['AVAILABLE', 'OUT_OF_STOCK', 'DISCONTINUED'])->default('AVAILABLE');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
