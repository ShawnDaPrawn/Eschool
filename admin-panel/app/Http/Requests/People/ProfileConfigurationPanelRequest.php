<?php

declare(strict_types=1);

namespace App\Http\Requests\People;

/** Normalise browser controls, retaining the same rules as the API. */
class ProfileConfigurationPanelRequest extends ProfileConfigurationRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->isMethod('GET') || $this->isMethod('DELETE') || ! str_contains($this->route()->uri(), '/definitions')) {
            return;
        }
        $this->merge([
            'visible_to' => $this->input('visible_to', []),
            'editable_by' => $this->input('editable_by', []),
            'options' => array_values(array_filter(array_map('trim', explode("\n", (string) $this->input('options_text', ''))), fn ($value) => $value !== '')),
            'validation' => array_filter((array) $this->input('validation', []), fn ($value) => $value !== null && $value !== ''),
        ]);
    }
}
