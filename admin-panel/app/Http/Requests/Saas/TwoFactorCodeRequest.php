<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformStaffAccess;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A code from the authenticator app, or a recovery code (SA.2): confirming
 * the app while setting it up (with the current password), and the challenge. The service checks the
 * code itself; the field is never flashed back (bootstrap/app.php).
 */
class TwoFactorCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(PlatformStaffAccess::class)->operatesPlatform($user);
    }

    public function rules(): array
    {
        return array_filter([
            'two_factor_code' => ['required', 'string', 'max:64'],
            // Setting up the app always asks for the password again (SA.2).
            'current_password' => $this->route()?->getName() === 'saas.two-factor.confirm' ? ['required', 'string', 'max:191'] : null,
        ]);
    }
}
