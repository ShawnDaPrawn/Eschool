<?php

declare(strict_types=1);

namespace App\Http\Resources\Facilitators;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SignInResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['authenticated' => $this->resource['authenticated'], 'token' => $this->resource['token'], 'profile' => $this->resource['profile'],
            'password_change_required' => $this->resource['password_change_required'], 'documents' => $this->resource['documents']];
    }
}
