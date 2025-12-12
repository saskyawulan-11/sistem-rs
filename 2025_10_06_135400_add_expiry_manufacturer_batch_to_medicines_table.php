<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('medicines', function (Blueprint $table) {
            if (! Schema::hasColumn('medicines', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('min_stock');
            }
            if (! Schema::hasColumn('medicines', 'manufacturer')) {
                $table->string('manufacturer')->nullable()->after('expiry_date');
            }
            if (! Schema::hasColumn('medicines', 'batch_number')) {
                $table->string('batch_number')->nullable()->after('manufacturer');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('medicines', function (Blueprint $table) {
            if (Schema::hasColumn('medicines', 'batch_number')) {
                $table->dropColumn('batch_number');
            }
            if (Schema::hasColumn('medicines', 'manufacturer')) {
                $table->dropColumn('manufacturer');
            }
            if (Schema::hasColumn('medicines', 'expiry_date')) {
                $table->dropColumn('expiry_date');
            }
        });
    }
};
