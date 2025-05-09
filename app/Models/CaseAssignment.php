<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseAssignment extends Model
{
    protected $table = 'case_assignments';
    
    protected $fillable = [
        'case_idx',
        'case_type',
        'assignment_date',
        'client_name',
        'living_place',
        'consultant',
        'case_state',
        'court_name',
        'case_number',
        'case_manager',
        'notes',
        'summit_date'
    ];

    protected $casts = [
        'case_idx' => 'integer',
        'assignment_date' => 'date',
        'case_state' => 'integer',
        'case_type' => 'integer',
        'summit_date' => 'date'
    ];

    public function target()
    {
        return $this->belongsTo(Target::class, 'case_idx', 'idx_TblCase');
    }
}