<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Ledger\HasMoneyColumns;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentTransaction extends Model
{
    use BelongsToSchool, HasFactory, HasMoneyColumns, SoftDeletes;

    /** Float money columns and their cents (HasMoneyColumns); the cents are the truth. */
    public function moneyColumns(): array
    {
        return [
            'total_amount' => 'total_amount_cents',
        ];
    }

    protected $hidden = ['deleted_at', 'created_at', 'updated_at'];

    public function student()
    {
        return $this->belongsTo(Students::class, 'student_id')->withTrashed();
    }

    public function class()
    {
        return $this->belongsTo(ClassSchool::class, 'class_id');
    }

    public function session_year()
    {
        return $this->belongsTo(SessionYear::class);
    }
}
