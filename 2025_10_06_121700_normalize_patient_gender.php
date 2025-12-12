<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert any legacy 'male'/'female' string values to DB enum 'L'/'P'
        DB::table('patients')->where('gender', 'male')->update(['gender' => 'L']);
        DB::table('patients')->where('gender', 'female')->update(['gender' => 'P']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert values back to 'male'/'female' if needed
        DB::table('patients')->where('gender', 'L')->update(['gender' => 'male']);
        DB::table('patients')->where('gender', 'P')->update(['gender' => 'female']);
    }
};
