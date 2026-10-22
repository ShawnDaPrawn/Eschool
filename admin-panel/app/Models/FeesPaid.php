<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Ledger\HasMoneyColumns;
use App\Services\Platform\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FeesPaid extends Model
{
    use Auditable, HasFactory, SoftDeletes, HasMoneyColumns;

    /** Float money columns and their cents (HasMoneyColumns); the cents are the truth. */
    public function moneyColumns(): array
    {
        return [
            'total_amount' => 'total_amount_cents',
            'due_charges' => 'due_charges_cents',
        ];
    }

    protected $hidden = ["deleted_at", "created_at", "updated_at"];

    public function session_year()
    {
        return $this->belongsTo(SessionYear::class, 'session_year_id');
    }

    public function student()
    {
        return $this->belongsTo(Students::class, 'student_id');
    }

    public function class()
    {
        return $this->belongsTo(ClassSchool::class, 'class_id')->with('medium')->withTrashed();
    }

    public function payment_transaction()
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }
}
