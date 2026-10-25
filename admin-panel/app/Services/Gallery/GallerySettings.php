<?php

declare(strict_types=1);

namespace App\Services\Gallery;

use App\Models\Gallery\GallerySetting;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use Illuminate\Support\Facades\Validator;

class GallerySettings
{
    public function current(): GallerySetting
    {
        return GallerySetting::first() ?? new GallerySetting(['guardian_required' => true, 'revision' => 0]);
    }

    public function save(User $actor, array $input): GallerySetting
    {
        $a = app(GalleryAuthority::class);
        $a->manage($actor, true);
        $d = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'guardian_required' => ['required', 'boolean']])->validate();
        $d['kind'] = 'settings';
        $result = app(GalleryOperations::class)->once($actor, $d, fn () => $a->manage($actor, true), function () use ($d): array {
            $row = GallerySetting::lockForUpdate()->first() ?? new GallerySetting;
            abort_unless((int) ($row->revision ?? 0) === (int) $d['revision'], 409, 'Compare the current photo consent rule.');
            $row->fill(['guardian_required' => (bool) $d['guardian_required'], 'revision' => (int) ($row->revision ?? 0) + 1])->save();
            Audit::record('gallery.consent_rule', $row, [], ['guardian_required' => $row->guardian_required, 'revision' => $row->revision]);

            return ['id' => (int) $row->id];
        });

        return GallerySetting::findOrFail($result['id']);
    }
}
