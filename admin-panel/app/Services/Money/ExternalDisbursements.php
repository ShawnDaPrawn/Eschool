<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

/** Common identity for already-paid outside money, separate from incoming receipts. */
final class ExternalDisbursements
{
    public function identity(string $method, string $currency, string $reference): string
    {
        return hash('sha256', json_encode([$method, $currency, FeePayments::reference($reference)], JSON_THROW_ON_ERROR));
    }

    public function available(string $method, string $currency, string $reference): string
    {
        $identity = $this->identity($method, $currency, $reference);
        abort_if(DB::table('financial_disbursements')->where('school_id', app(CurrentSchool::class)->id())->where('identity_hash', $identity)->lockForUpdate()->exists(), 409, 'This verified outside payout was already recorded.');

        return $identity;
    }
}
