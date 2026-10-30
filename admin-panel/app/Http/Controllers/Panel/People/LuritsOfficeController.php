<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\People;

use App\Http\Controllers\Controller;
use App\Services\People\LuritsOffice;
use App\Services\Platform\Identity\LuritsNumbers;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** /learners/lurits: the school office's LURITS numbers (Action Plan 1.0e). */
class LuritsOfficeController extends Controller
{
    public function __construct(private readonly LuritsOffice $office) {}

    public function index(Request $request): View
    {
        return view('people.lurits', [
            'summary' => $this->office->summary($request->user()),
            'missing' => $this->office->missing($request->user()),
            'claims' => $this->office->claims($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['admission_no' => ['required', 'string', 'max:64'], 'lurits' => ['required', 'string', 'max:32']]);
        $result = $this->office->record($request->user(), $data['admission_no'], $data['lurits']);
        $message = match ($result) {
            LuritsNumbers::RECORDED => __('LURITS number recorded for :number.', ['number' => $data['admission_no']]),
            LuritsNumbers::UNCHANGED => __(':number already has this LURITS number.', ['number' => $data['admission_no']]),
            default => __('This LURITS number is already registered to another learner, so it was not recorded. If the learner is joining from another school, start a transfer request; otherwise Navigate Ed support will check the number with you.'),
        };

        return redirect()->route('lurits-office.index')->with($result === LuritsNumbers::CONFLICT ? 'warning' : 'success', $message);
    }
}
