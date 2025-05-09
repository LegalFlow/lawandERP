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
        Schema::table('correction_div', function (Blueprint $table) {
            $table->string('case_idx')->nullable()->after('case_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('correction_div', function (Blueprint $table) {
            $table->dropColumn('case_idx');
        });
    }
};
