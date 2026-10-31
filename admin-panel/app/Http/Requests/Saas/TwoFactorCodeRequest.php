<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformStaffAccess;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A code from the authenticator app, or a recovery code (SA.2): confirming
 * the app while setting it up, and the challenge. The service checks the
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
        return ['two_factor_code' => ['required', 'string', 'max:64']];
    }
}
