<?php

declare(strict_types=1);

namespace App\Services\SchoolBusiness;

use App\Models\SchoolBusiness\ReenrolmentAcceptance;
use App\Models\SchoolBusiness\ReenrolmentContract;
use App\Models\SessionYear;
use App\Models\User;

/** Authorised staff reading of immutable wording and acceptance evidence; no guardian impersonation. */
class ReenrolmentPanelQueries
{
    public function detail(User $actor, int $id, int $page = 1): array
    {
        $authority = app(BusinessAuthority::class);
        $authority->staff($actor, ReenrolmentContracts::MODULE, ReenrolmentContracts::PERMISSION);
        $contract = ReenrolmentContract::findOrFail($id);
        $acceptances = ReenrolmentAcceptance::where('contract_id', $contract->id)->orderBy('id')->paginate(100, ['*'], 'page', $page);
        $rows = $acceptances->map(fn (ReenrolmentAcceptance $acceptance) => [
            'learner' => BusinessAuthority::learnerName($acceptance->learner_id),
            'guardian' => BusinessAuthority::personName($acceptance->guardian_user_id),
            'signed_name' => $acceptance->signed_name, 'version' => $acceptance->version,
            'text_hash' => $acceptance->text_hash, 'fee_responsible' => $acceptance->fee_responsible,
            'accepted_at' => $acceptance->accepted_at->toIso8601String(),
        ])->all();
        $authority->staff($actor, ReenrolmentContracts::MODULE, ReenrolmentContracts::PERMISSION);

        return ['contract' => $contract, 'year' => SessionYear::findOrFail($contract->session_year_id)->name,
            'acceptances' => $rows, 'acceptance_count' => $acceptances->total(), 'page' => $acceptances->currentPage(), 'last_page' => $acceptances->lastPage()];
    }
}
