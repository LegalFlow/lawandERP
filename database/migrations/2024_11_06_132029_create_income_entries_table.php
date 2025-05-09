<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('income_entries', function (Blueprint $table) {
        $table->id();
        $table->date('deposit_date');
        $table->string('depositor_name');
        $table->integer('amount');
        $table->unsignedBigInteger('representative_id'); // 구성원 ID 참조
        $table->enum('account_type', ['서비스매출', '송인부']);
        $table->text('memo')->nullable();
        $table->timestamps();

            // representative_id 외래 키 설정 (구성원 테이블과 연결)
        $table->foreign('representative_id')->references('id')->on('members')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('income_entries');
    }
};
