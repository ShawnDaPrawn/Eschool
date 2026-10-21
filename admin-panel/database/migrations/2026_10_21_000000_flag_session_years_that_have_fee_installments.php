<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Session years created with fee installments saved the installments but
     * not include_fee_installments (it was missing from the model's fillable
     * list), so fees and the apps treated them as having none. Every session
     * year that has installments gets the flag.
     */
    public function up(): void
    {
        DB::table('session_years')
            ->where('include_fee_installments', 0)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('installment_fees')
                    ->whereColumn('installment_fees.session_year_id', 'session_years.id')
                    ->whereNull('installment_fees.deleted_at');
            })
            ->update(['include_fee_installments' => 1]);
    }

    /**
     * Reverse the migrations.
     *
     * Nothing to undo: the flag now matches the installments.
     */
    public function down(): void
    {
        //
    }
};
