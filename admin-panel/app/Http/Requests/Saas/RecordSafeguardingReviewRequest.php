<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Learning\SafeguardingPhraseReviews;
use App\Services\Platform\Saas\PlatformStaffAccess;

/** A professional review of one language's safeguarding phrase list (Action Plan 3.2). */
class RecordSafeguardingReviewRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && app(PlatformStaffAccess::class)->can($this->user(), SafeguardingPhraseReviews::ABILITY);
    }

    public function rules(): array
    {
        return SafeguardingPhraseReviews::rules();
    }
}
