<?php

namespace App\Models;

use App\Services\Platform\Ledger\HasMoneyColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaidInstallmentFee extends Model
{
    use HasFactory, HasMoneyColumns, SoftDeletes;

    /** Float money columns and their cents (HasMoneyColumns); the cents are the truth. */
    public function moneyColumns(): array
    {
        return [
            'amount' => 'amount_cents',
            'due_charges' => 'due_charges_cents',
        ];
    }

    protected $fillable = [
        'id',
        'student_id',
        'class_id',
        'parent_id',
        'installment_fee_id',
        'session_year_id',
        'amount',
        'due_charges',
        'amount_cents',
        'due_charges_cents',
        'date',
        'payment_transaction_id',
        'status',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function class()
    {
        return $this->belongsTo(ClassSchool::class, 'class_id');
    }

    public function student()
    {
        return $this->belongsTo(Students::class, 'student_id');
    }

    public function parent()
    {
        return $this->belongsTo(Parents::class, 'parent_id');
    }

    public function installment_fee()
    {
        return $this->belongsTo(InstallmentFee::class, 'installment_fee_id');
    }

    public function session_year()
    {
        return $this->belongsTo(SessionYear::class, 'session_year_id');
    }
}
