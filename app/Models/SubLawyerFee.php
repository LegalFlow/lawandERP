<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubLawyerFee extends Model
{
    protected $table = 'Sub_LawyerFee';
    
    protected $primaryKey = 'id';
    
    public $timestamps = false;
    
    protected $fillable = [
        'case_idx',
        'id_request',
        'seal_request',
        'first_doc_request',
        'second_doc_request',
        'debt_cert_request',
        'contract_cancel',
        'contract_termination',
    ];
    
    protected $casts = [
        'id_request' => 'boolean',
        'seal_request' => 'boolean',
        'first_doc_request' => 'boolean',
        'second_doc_request' => 'boolean',
        'debt_cert_request' => 'boolean',
        'contract_cancel' => 'boolean',
        'contract_termination' => 'boolean',
    ];
    
    public function caseAssignment()
    {
        return $this->belongsTo(CaseAssignment::class, 'case_idx', 'case_idx');
    }
} 