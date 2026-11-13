<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Emergency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Emergency\EmergencyPanelRequest;
use App\Services\Emergency\EmergencyAlerts;
use App\Services\Emergency\EmergencyDrills;
use App\Services\Emergency\EmergencyQueries;
use App\Services\Emergency\EmergencyReunification;
use App\Services\Emergency\EmergencyVisitors;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Emergency management in the panel: alerts, roll call, reunification, visitors and the drill log. */
class EmergencyPanelController extends Controller
{
    public function index(EmergencyPanelRequest $request): Response
    {
        return $this->failure($request, [], 200);
    }

    public function failure(EmergencyPanelRequest $request, array $messages, int $status): Response
    {
        $page = app(EmergencyQueries::class)->panel($request->user());
        $account = (int) $request->user()->id;
        $school = app(CurrentSchool::class)->id();

        return response()->view('emergency.index', compact('page', 'account', 'school', 'messages'), $status, ['Cache-Control' => 'no-store, private']);
    }

    public function write(EmergencyPanelRequest $request): Response|RedirectResponse
    {
        try {
            $input = $request->serviceInput();
            $id = (string) $request->validated('source_id', '');
            $user = $request->user();
            $result = match ($request->route('operation')) {
                'alert-prepare' => app(EmergencyAlerts::class)->prepare($user, $input),
                'alert-confirm' => app(EmergencyAlerts::class)->confirm($user, $id, $input),
                'alert-all-clear' => app(EmergencyAlerts::class)->allClear($user, $id, $input),
                'alert-cancel' => app(EmergencyAlerts::class)->cancel($user, $id, $input),
                'roll-call-mark' => app(EmergencyReunification::class)->mark($user, $id, $input),
                'release' => app(EmergencyReunification::class)->release($user, $id, $input),
                'visitor-sign-in' => app(EmergencyVisitors::class)->signIn($user, $input),
                'visitor-sign-out' => app(EmergencyVisitors::class)->signOut($user, $id, $input),
                'drill-plan' => app(EmergencyDrills::class)->plan($user, $input),
                'drill-record' => app(EmergencyDrills::class)->record($user, $id, $input),
                'drill-cancel' => app(EmergencyDrills::class)->cancel($user, $id, $input),
                'retention' => app(EmergencyVisitors::class)->changeRetention($user, $input),
            };
            $notice = match ($request->route('operation')) {
                'alert-prepare' => __('Check the alert, then type :word to send it.', ['word' => $result['confirmation_word'] ?? '']),
                'alert-confirm' => __('The alert was sent.'),
                'alert-all-clear' => __('The all-clear was sent.'),
                'release' => __('The release was recorded.'),
                default => __('Saved.'),
            };

            $view = match ($request->route('operation')) {
                'roll-call-mark' => 'roll-call', 'release' => 'reunification',
                'visitor-sign-in', 'visitor-sign-out' => 'visitors',
                'drill-plan', 'drill-record', 'drill-cancel' => 'drills',
                'retention' => 'configuration', default => 'alerts',
            };

            return redirect()->route('emergency-panel.index', ['view' => $view])->with('success', $notice);
        } catch (ValidationException $error) {
            return $this->failure($request, $error->validator->errors()->all(), 422);
        } catch (HttpExceptionInterface $error) {
            // A refused release is shown on the page, so the gate sees why: do not release the child.
            $refusedRelease = $request->route('operation') === 'release' && $error->getStatusCode() === 403 && $error->getMessage() !== '';
            if (! $refusedRelease && ! in_array($error->getStatusCode(), [409, 422, 423], true)) {
                throw $error;
            }

            return $this->failure($request, [$error->getMessage() ?: __('Refresh the page and try again.')], $error->getStatusCode());
        }
    }
}
