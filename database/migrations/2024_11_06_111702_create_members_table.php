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
        Schema::create('members', function (Blueprint $table) {
            $table->id(); // 자동 증가하는 ID
            $table->string('name'); // 이름
            $table->string('position')->nullable(); // 직급
            $table->string('task')->nullable(); // 업무
            $table->string('affiliation')->nullable(); // 소속
            $table->string('status')->nullable(); // 상태
            $table->string('authority')->nullable(); // 권한
            $table->integer('years')->nullable(); // 년차
            $table->integer('working_days_per_week')->nullable(); // 주당 근무일수
            $table->integer('remote_days_per_week')->nullable(); // 주당 재택근무 일수
            $table->string('bank')->nullable(); // 은행
            $table->string('account_number')->nullable(); // 계좌번호
            $table->text('notes')->nullable(); // 기타사항
            $table->timestamps(); // 생성일 및 수정일
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
