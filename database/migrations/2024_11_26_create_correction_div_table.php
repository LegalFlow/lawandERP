<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('correction_div', function (Blueprint $table) {
            $table->id();
            
            // 구글 시트에서 가져오는 필드들
            $table->string('court_name', 50)->nullable();
            $table->string('court_department', 50)->nullable();
            $table->string('case_number', 50);
            $table->string('name', 50)->nullable();
            $table->string('document_name', 50);
            $table->date('shipment_date');
            $table->date('receipt_date')->nullable();
            $table->string('receipt_status', 50)->nullable();
            $table->string('case_manager', 50)->nullable();
            $table->string('case_state', 50)->nullable();
            
            // 추가 필드들
            $table->string('consultant', 50)->nullable();
            $table->string('pdf_path')->nullable();

            // 인덱스
            $table->index('receipt_date');
            $table->index('case_number');
            
            // 복합 유니크 키
            $table->unique(['case_number', 'document_name', 'shipment_date'], 'unique_case_document');
        });
    }

    public function down()
    {
        Schema::dropIfExists('correction_div');
    }
}; 