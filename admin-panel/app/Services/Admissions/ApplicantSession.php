<?php

declare(strict_types=1);

namespace App\Services\Admissions;

use App\Models\Admissions\Application;
use App\Models\Admissions\Intake;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/** Opaque ownership capabilities are encrypted even in the server session. */
class ApplicantSession
{
    private function slot(Intake $intake): string
    {
        return 'admissions.'.app(CurrentSchool::class)->id().'.'.$intake->public_id;
    }

    public function state(Request $request, Intake $intake): array
    {
        $stored = $request->session()->get($this->slot($intake));
        if (is_string($stored)) {
            $decoded = json_decode(Crypt::decryptString($stored), true, 8, JSON_THROW_ON_ERROR);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        $state = ['nonce' => (string) Str::uuid(), 'key' => bin2hex(random_bytes(32)), 'issued' => now()->getTimestamp()];
        $this->put($request, $intake, $state);

        return $state;
    }

    public function put(Request $request, Intake $intake, array $state): void
    {
        $request->session()->put($this->slot($intake), Crypt::encryptString(json_encode($state, JSON_THROW_ON_ERROR)));
    }

    public function owner(Request $request, Intake $intake, bool $write = false): Application
    {
        $state = $this->state($request, $intake);
        abort_unless(isset($state['id']), 404);
        $row = app(IntakeAuthority::class)->owner((int) $state['id'], (string) $state['key'], $write);
        abort_unless((int) $row->intake_id === (int) $intake->id, 404);

        return $row;
    }

    public function challenge(Request $request, Intake $intake): void
    {
        $state = $this->state($request, $intake);
        abort_unless(in_array($request->input('website'), [null, ''], true) && now()->getTimestamp() - (int) $state['issued'] >= 2, 422, 'Please wait a moment and try again.');
    }
}
