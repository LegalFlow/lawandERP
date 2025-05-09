<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryContract extends Model
{
    protected $fillable = [
        'user_id',
        'position',
        'base_salary',
        'contract_start_date',
        'contract_end_date',
        'created_date',
        'memo',
        'approved_at',
        'approved_by',
        'created_by'
    ];

    protected $casts = [
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'created_date' => 'date',
        'approved_at' => 'datetime',
        'base_salary' => 'decimal:2'
    ];

    /**
     * Get the user that owns the contract.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who approved the contract.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the admin who created the contract.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the salary statements for the contract.
     */
    public function salaryStatements(): HasMany
    {
        return $this->hasMany(SalaryStatement::class);
    }

    /**
     * Check if the contract is approved.
     */
    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /**
     * Approve the contract.
     */
    public function approve(int $approver_id): bool
    {
        return $this->update([
            'approved_at' => now(),
            'approved_by' => $approver_id
        ]);
    }

    /**
     * Scope a query to only include active contracts.
     */
    public function scopeActive($query)
    {
        $today = now()->format('Y-m-d');
        return $query->whereDate('contract_start_date', '<=', $today)
                    ->whereDate('contract_end_date', '>=', $today);
    }

    /**
     * Scope a query to only include approved contracts.
     */
    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    /**
     * Scope a query to only include pending contracts.
     */
    public function scopePending($query)
    {
        return $query->whereNull('approved_at');
    }
}