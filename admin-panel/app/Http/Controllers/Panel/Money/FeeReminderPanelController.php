<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\FeeReminderPanelRequest;
use App\Models\Money\FeeReminder;
use App\Models\Money\FeeReminderPolicy;
use App\Services\Money\FeeReminderSettings;
use App\Services\Platform\Modules\ModuleGate;
use Symfony\Component\HttpFoundation\Response;

class FeeReminderPanelController extends Controller
{
    public function index(FeeReminderPanelRequest $request): Response
    {
        $policy = FeeReminderPolicy::first();
        $reminders = FeeReminder::join('fee_accounts as a', function ($j) {
            $j->on('a.school_id', '=', 'fee_reminders.school_id')->on('a.id', '=', 'fee_reminders.fee_account_id');
        })
            ->orderByDesc('fee_reminders.id')->paginate(30, ['fee_reminders.*', 'a.name as account_name']);
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.reminders.index', compact('policy', 'reminders', 'canWrite'));
    }

    public function save(FeeReminderPanelRequest $request): Response
    {
        app(FeeReminderSettings::class)->save($request->user(), $request->validated());

        return redirect()->route('fee-reminders.index', [], 303)->with('success', __('Fee reminder schedule saved.'));
    }
}
