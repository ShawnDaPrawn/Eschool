<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\User;
use App\Policies\Commerce\VendorPolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Cards\CardAccess;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** The same current authority for navigation and the private reader page. */
final class VendorPaymentReaderAccess
{
    public function actor(User $user, bool $register = false): User
    {
        abort_unless(app(ModuleGate::class)->allows('wallet_payments'), 404);
        app(VendorPolicy::class)->finance($user, $register);
        $actor = app(CardAccess::class)->actor($user, revocation: true);
        abort_unless(app(ProfilePolicy::class)->permission($actor, CardAccess::READERS), 403);

        return $actor;
    }

    public function available(User $user): bool
    {
        try {
            $this->actor($user);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }
}
