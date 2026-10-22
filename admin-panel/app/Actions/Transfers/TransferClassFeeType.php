<?php

namespace App\Actions\Transfers;

use App\Models\FeesClass;
use App\Services\Platform\Tenancy\SchoolTable;

class TransferClassFeeType
{
    public function execute(int $fromSessionId, int $toSessionId): void
    {
        FeesClass::where('session_year_id', $fromSessionId)
            ->chunkById(500, function ($rows) use ($toSessionId) {

                $data = $rows->map(function ($row) use ($toSessionId) {
                    return array_merge(
                        collect($row->getAttributes())
                            ->except(['id', 'created_at', 'updated_at'])
                            ->toArray(),
                        [
                            'amount_cents' => $row->money('amount')?->cents,
                            'session_year_id' => $toSessionId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                })->toArray();
                if (! empty($data)) {
                    SchoolTable::query('fees_classes')->insert($data);
                }
            });
    }
}
