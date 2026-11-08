<?php

declare(strict_types=1);

namespace App\Http\Requests\Leadership;

use App\Services\Leadership\Benchmarking\SchoolBenchmarks;
use Illuminate\Foundation\Http\FormRequest;

class BenchmarkingRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(SchoolBenchmarks::class)->authorise($this->user());

        return true;
    }

    public function rules(): array
    {
        return ['participate' => ['required', 'boolean']];
    }
}
