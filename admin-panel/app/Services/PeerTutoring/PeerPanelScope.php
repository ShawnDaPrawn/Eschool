<?php

declare(strict_types=1);

namespace App\Services\PeerTutoring;

use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PeerPanelScope
{
    public function available(User $actor): bool
    {
        try {
            app(AcademicAuthority::class)->staff(app(PeerAccess::class)->actor($actor));

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    public function authorize(Request $request): void
    {
        $write = ! $request->isMethodSafe() && $request->route('action') !== 'cancel';
        $fresh = app(PeerAccess::class)->actor($request->user(), $write);
        app(AcademicAuthority::class)->staff($fresh);
        if (! $request->isMethodSafe()) {
            foreach (['expected_account_id' => (int) $fresh->id, 'expected_school_id' => app(CurrentSchool::class)->id()] as $key => $id) {
                $value = $request->input($key);
                abort_unless((is_int($value) || is_string($value)) && (string) $value === (string) $id, 403);
            }
            abort_if($request->query->count() > 0 || $request->allFiles() !== [], 422);
        }
    }
}
