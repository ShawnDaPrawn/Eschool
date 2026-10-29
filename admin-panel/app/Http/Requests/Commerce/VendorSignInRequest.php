<?php

declare(strict_types=1);

namespace App\Http\Requests\Commerce;

use App\Http\Requests\Facilitators\SignInRequest;

class VendorSignInRequest extends SignInRequest
{
    public function authorize(): bool
    {
        abort_if($this->query->count() > 0, 422);

        return true;
    }
}
