<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * The signed-in user (openapi.yaml, ContextUser). Learners sign in with their
 * GR number, which is what "email" holds for them.
 */
class ContextUserResource extends JsonResource
{
    /**
     * @param  list<string>  $roles
     */
    public function __construct(User $user, private readonly array $roles)
    {
        parent::__construct($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'id' => (int) $user->id,
            'first_name' => (string) $user->first_name,
            'last_name' => (string) $user->last_name,
            'name' => trim($user->first_name.' '.$user->last_name),
            'email' => $user->email,
            'image' => self::imageUrl($user),
            'roles' => $this->roles,
        ];
    }

    /** The profile picture's URL, or null when there is none (the model's accessor always builds a URL). */
    public static function imageUrl(User $user): ?string
    {
        $path = $user->getRawOriginal('image');

        return $path === null || $path === '' ? null : url(Storage::url($path));
    }
}
