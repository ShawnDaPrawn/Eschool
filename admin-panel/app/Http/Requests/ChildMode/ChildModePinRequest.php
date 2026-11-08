<?php

declare(strict_types=1);

namespace App\Http\Requests\ChildMode;

use App\Services\ChildMode\ChildModePins;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A child-mode PIN (Action Plan 4.2). Setting one: 4 to 6 digits, typed
 * twice, not all one digit or a straight run. Entering one (to start or
 * leave child mode): the same shape, nothing more, so a wrong PIN is counted
 * by ChildModePins rather than refused here.
 */
class ChildModePinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pin = ['required', 'string', 'regex:/^[0-9]{4,6}$/D'];
        if (! $this->routeIs('api.v2.child-mode.pin')) {
            return ['pin' => $pin];
        }

        return [
            'pin' => [...$pin, 'confirmed', function (string $attribute, mixed $value, Closure $fail): void {
                if (is_string($value) && preg_match('/^[0-9]{4,6}$/D', $value) === 1 && ChildModePins::tooEasy($value)) {
                    $fail(__('child_mode_pin_too_easy'));
                }
            }],
            'pin_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return ['pin.regex' => __('child_mode_pin_shape'), 'pin.confirmed' => __('child_mode_pin_mismatch')];
    }

    public function pin(): string
    {
        return (string) $this->validated('pin');
    }
}
