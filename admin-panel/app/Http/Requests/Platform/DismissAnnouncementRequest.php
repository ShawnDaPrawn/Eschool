<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Services\Platform\Announcements\SchoolAnnouncements;
use Illuminate\Foundation\Http\FormRequest;

/** A school Super Admin dismisses a Navigate Ed announcement's banner (SA.13); the service checks again. */
class DismissAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(SchoolAnnouncements::class)->reaches($this->user());
    }

    public function rules(): array
    {
        return [];
    }
}
