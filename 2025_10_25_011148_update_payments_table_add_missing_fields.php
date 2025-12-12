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
        Schema::table('payments', function (Blueprint $table) {
            // Add missing fields
            $table->string('payment_method')->nullable()->after('payment_number');
            $table->string('reference_number')->nullable()->after('payment_method');
            $table->unsignedBigInteger('created_by')->nullable()->after('notes');
            
            // Rename payment_type to payment_method if needed
            if (Schema::hasColumn('payments', 'payment_type')) {
                $table->dropColumn('payment_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'reference_number', 'created_by']);
        });
    }
};
