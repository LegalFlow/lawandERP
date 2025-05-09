<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('correction_div', function (Blueprint $table) {
            $table->integer('sequence')->default(1)->after('pdf_path');
        });
    }

    public function down()
    {
        Schema::table('correction_div', function (Blueprint $table) {
            $table->dropColumn('sequence');
        });
    }
}; 