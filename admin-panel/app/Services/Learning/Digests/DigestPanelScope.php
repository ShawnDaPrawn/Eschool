<?php

declare(strict_types=1);

namespace App\Services\Learning\Digests;

use App\Models\Learning\DigestLanguageTemplate;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class DigestPanelScope
{
    public function available(User $actor): bool
    {
        try {
            app(DigestAuthority::class)->manager($actor);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    public function authorize(Request $r): bool
    {
        app(DigestAuthority::class)->manager($r->user(), ! $r->isMethodSafe());
        if (! $r->isMethodSafe()) {
            abort_unless((int) $r->input('expected_account_id') === (int) $r->user()->id && (int) $r->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
            abort_if($r->query->count() > 0, 422, 'Submit language reviews in the private form.');
        }
        if ($r->route('template') !== null) {
            DigestLanguageTemplate::select(['id'])->findOrFail((int) $r->route('template'));
        }

        return true;
    }
}
