<?php

declare(strict_types=1);

namespace App\Services\Cards;

use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class CardPanelScope
{
    public function available(User $user, bool $readers = false): bool
    {
        try {
            app(CardAccess::class)->manage($user, $readers, false);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    public function authorize(Request $request): bool
    {
        $readers = str_starts_with((string) $request->route()->getName(), 'card-readers-panel.');
        $negative = in_array($request->route('operation'), ['freeze', 'revoke'], true);
        $fresh = app(CardAccess::class)->actor($request->user(), ! $request->isMethodSafe() && $request->route('operation') !== 'print', $negative);
        abort_unless(app(ProfilePolicy::class)->permission($fresh, $readers ? CardAccess::READERS : CardAccess::MANAGE), 403);
        if (! $request->isMethodSafe() || $request->route('operation') === 'portrait') {
            $account = $request->input('expected_account_id');
            $school = $request->input('expected_school_id');
            abort_unless((is_int($account) || is_string($account)) && (is_int($school) || is_string($school))
                && (string) $account === (string) $fresh->id && (string) $school === (string) app(CurrentSchool::class)->id(), 403);
        }
        abort_if(! $request->isMethodSafe() && $request->query->count() > 0, 422, 'Send card changes in the private form.');

        return true;
    }
}
