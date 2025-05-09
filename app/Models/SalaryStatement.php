<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class SalaryStatement extends Model
{
   protected $fillable = [
       'user_id',
       'contract_id',
       'name',
       'position',
       'affiliation',
       'statement_date',
       'base_salary',
       'meal_allowance',
       'vehicle_allowance',
       'child_allowance',
       'bonus',
       'performance_pay',
       'vacation_pay',
       'adjustment_pay',
       'income_tax',
       'local_income_tax',
       'national_pension',
       'health_insurance',
       'long_term_care',
       'employment_insurance',
       'student_loan_repayment',
       'other_deductions',
       'year_end_tax',
       'year_end_local_tax',
       'health_insurance_adjustment',
       'long_term_adjustment',
       'interim_tax',
       'interim_local_tax',
       'agriculture_tax',
       'total_payment',
       'total_deduction',
       'net_payment',
       'memo',
       'approved_at',
       'approved_by',
       'created_by'
   ];

   protected $casts = [
       'statement_date' => 'date',
       'approved_at' => 'datetime',
       'base_salary' => 'float',
       'meal_allowance' => 'float',
       'vehicle_allowance' => 'float',
       'child_allowance' => 'float',
       'bonus' => 'float',
       'performance_pay' => 'float',
       'vacation_pay' => 'float',
       'adjustment_pay' => 'float',
       'income_tax' => 'float',
       'local_income_tax' => 'float',
       'national_pension' => 'float',
       'health_insurance' => 'float',
       'long_term_care' => 'float',
       'employment_insurance' => 'float',
       'student_loan_repayment' => 'float',
       'other_deductions' => 'float',
       'year_end_tax' => 'float',
       'year_end_local_tax' => 'float',
       'health_insurance_adjustment' => 'float',
       'long_term_adjustment' => 'float',
       'interim_tax' => 'float',
       'interim_local_tax' => 'float',
       'agriculture_tax' => 'float',
       'total_payment' => 'float',
       'total_deduction' => 'float',
       'net_payment' => 'float'
   ];

   /**
    * Get the user that owns the salary statement.
    */
   public function user(): BelongsTo
   {
       return $this->belongsTo(User::class);
   }

   /**
    * Get the contract associated with this statement.
    */
   public function contract(): BelongsTo
   {
       return $this->belongsTo(SalaryContract::class);
   }

   /**
    * Get the admin who approved the statement.
    */
   public function approver(): BelongsTo
   {
       return $this->belongsTo(User::class, 'approved_by');
   }

   /**
    * Get the admin who created the statement.
    */
   public function creator(): BelongsTo
   {
       return $this->belongsTo(User::class, 'created_by');
   }

   /**
    * Check if the statement is approved.
    */
   public function isApproved(): bool
   {
       return $this->approved_at !== null;
   }

   /**
    * Approve the statement.
    */
   public function approve(int $approver_id): bool
   {
       return $this->update([
           'approved_at' => now(),
           'approved_by' => $approver_id
       ]);
   }

   /**
    * Calculate total payment amount
    */
   public function calculateTotalPayment()
   {
       $this->total_payment = $this->base_salary +
           $this->meal_allowance +
           $this->vehicle_allowance +
           $this->child_allowance +
           $this->bonus +
           $this->performance_pay +
           $this->vacation_pay +
           $this->adjustment_pay;
       
       return $this->total_payment;
   }

   /**
    * Calculate total deduction amount
    */
   public function calculateTotalDeduction()
   {
       $this->total_deduction = $this->income_tax +
           $this->local_income_tax +
           $this->national_pension +
           $this->health_insurance +
           $this->long_term_care +
           $this->employment_insurance +
           $this->student_loan_repayment +
           $this->other_deductions +
           $this->year_end_tax +
           $this->year_end_local_tax +
           $this->health_insurance_adjustment +
           $this->long_term_adjustment +
           $this->interim_tax +
           $this->interim_local_tax +
           $this->agriculture_tax;
       
       return $this->total_deduction;
   }

   /**
    * Calculate net payment amount
    */
   public function calculateNetPayment()
   {
       $this->calculateTotalPayment();
       $this->calculateTotalDeduction();
       $this->net_payment = $this->total_payment - $this->total_deduction;
       
       return $this->net_payment;
   }

   /**
    * Calculate standard deductions based on base salary
    */
   public function calculateStandardDeductions()
   {
       // 기본급 기준으로 공제액 자동 계산
       // 실제 계산 로직은 법률/규정에 맞게 조정 필요
       $this->national_pension = $this->base_salary * 0.045; // 4.5%
       $this->health_insurance = $this->base_salary * 0.0343; // 3.43%
       $this->long_term_care = $this->health_insurance * 0.1227; // 건강보험료의 12.27%
       $this->employment_insurance = $this->base_salary * 0.008; // 0.8%
       
       // 소득세와 지방소득세는 과세표준에 따라 계산해야 하므로 여기서는 예시로만 작성
       $this->income_tax = $this->base_salary * 0.03; // 예시: 3%
       $this->local_income_tax = $this->income_tax * 0.1; // 소득세의 10%
   }

   /**
    * Scope a query to only include approved statements.
    */
   public function scopeApproved($query)
   {
       return $query->whereNotNull('approved_at');
   }

   /**
    * Scope a query to only include pending statements.
    */
   public function scopePending($query)
   {
       return $query->whereNull('approved_at');
   }
}