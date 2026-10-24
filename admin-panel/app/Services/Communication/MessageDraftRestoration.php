<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\SessionYear;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;

/** A failed compose belongs to one account, school and exact draft. */
class MessageDraftRestoration
{
    private function key(Request $request, ?int $message): string
    {
        return 'communication.compose.'.hash('sha256', app(CurrentSchool::class)->id().':'.$request->user()->id.':'.($message ?? 0));
    }

    public function retain(Request $request, ?int $message, mixed $errors): RedirectResponse
    {
        $data = $request->only(['title', 'markdown', 'channels', 'scope_selector', 'audience', 'parents', 'scheduled_local', 'operation_id', 'revision']);
        foreach (['title', 'markdown', 'scheduled_local'] as $field) {
            $data[$field] = is_string($data[$field] ?? null) ? $data[$field] : '';
        }
        $data['channels'] = is_array($data['channels'] ?? null) ? array_values(array_intersect(['in_app', 'push', 'email'], array_filter($data['channels'], 'is_string'))) : [];
        $data['parents'] = filter_var($data['parents'] ?? $request->input('audience.parents', false), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        if (! is_string($data['scope_selector'] ?? null)) {
            unset($data['scope_selector']);
        }
        $scope = $data['audience'] ?? null;
        if (! is_array($scope) || ! is_string($scope['kind'] ?? null) || ! in_array($scope['kind'], ['school', 'grade', 'homeroom', 'group', 'custom'], true)
            || ($scope['kind'] !== 'school' && filter_var($scope['target_id'] ?? null, FILTER_VALIDATE_INT) === false)) {
            unset($data['audience']);
        } elseif (! isset($data['scope_selector'])) {
            $data['scope_selector'] = $scope['kind'].':'.($scope['target_id'] ?? 0);
        }
        $request->session()->flash($this->key($request, $message), Crypt::encryptString(json_encode($data, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE)));
        $year = SessionYear::find((int) $request->input('audience.session_year_id'));

        return redirect()->route('communication-centre.index', ['message_id' => $message, 'session_year_id' => $year?->id])->withErrors($errors);
    }

    public function read(Request $request, ?int $message): ?array
    {
        $value = $request->session()->get($this->key($request, $message));
        if (! is_string($value)) {
            return null;
        }
        try {
            $data = json_decode(Crypt::decryptString($value), true, 32, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }
        if (! is_array($data)) {
            return null;
        }
        if (! is_string($data['operation_id'] ?? null) || ! Str::isUuid($data['operation_id'])) {
            $data['operation_id'] = (string) Str::uuid();
        }
        if (! isset($data['revision']) || filter_var($data['revision'], FILTER_VALIDATE_INT) === false || (int) $data['revision'] < 0) {
            unset($data['revision']);
        }

        return $data;
    }
}
