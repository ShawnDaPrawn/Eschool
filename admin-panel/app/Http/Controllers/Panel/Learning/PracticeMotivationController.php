<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\PracticeMotivationRequest;
use App\Services\Learning\Motivation\MotivationSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** /practice-motivation: the school's gentle practice reminders (Action Plan 3.13). */
class PracticeMotivationController extends Controller
{
    public function __construct(private readonly MotivationSettings $settings) {}

    public function index(PracticeMotivationRequest $request): View
    {
        return view('learning.practice-motivation', ['settings' => $this->settings->current(), 'operationId' => (string) Str::uuid(),
            'minDays' => (int) config('motivation.nudges.min_days'), 'maxDays' => (int) config('motivation.nudges.max_days')]);
    }

    public function update(PracticeMotivationRequest $request): RedirectResponse
    {
        try {
            $this->settings->configure($request->user(), $request->validated());
        } catch (HttpExceptionInterface $error) {
            if ($error->getStatusCode() !== 409) {
                throw $error;
            }

            return back()->withErrors(['settings' => $error->getMessage()]);
        }

        return redirect()->route('practice-motivation.index')->with('success', __('Practice reminder settings saved.'));
    }
}
