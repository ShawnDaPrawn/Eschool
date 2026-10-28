<?php

declare(strict_types=1);

namespace App\Http\Resources\Money;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Whitelisted financial projection only; never serializes a money model. */
class WalletResource extends JsonResource
{
    /** @var array<string, mixed> */
    public $resource;

    public function __construct(array $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
