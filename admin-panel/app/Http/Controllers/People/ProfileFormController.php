<?php

declare(strict_types=1);

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\ProfilePanelRequest;
use App\Http\Requests\People\StaffInvitationRequest;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\People\Profiles\ProfileForms;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Identity\LearnerIdentifiers;
use App\Services\Platform\Identity\StaffInvitations;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileFormController extends Controller
{
    private function subject(ProfilePanelRequest $request, ?int $person): array
    {
        $subject = $person === null ? $request->user() : User::query()->findOrFail($person);
        $type = $request->validated('profile_type') ?? (app(ProfilePolicy::class)->types($subject)[0] ?? 'staff');

        return [$subject, $type];
    }

    public function index(ProfilePanelRequest $request, ProfileForms $forms, ?int $person = null): Response
    {
        [$subject, $type] = $this->subject($request, $person);
        Gate::authorize('profile.view', [$subject, $type]);
        $form = $forms->schema($request->user(), $subject, $type);

        $canInvite = app(StaffInvitations::class)->mayInvite($request->user(), $subject);
        // A learner is identified by the headline number first (ADR 0007),
        // then by the school's own admission number.
        $learner = $form['profile_type'] === 'learner' ? Students::query()->where('user_id', $subject->id)->first() : null;
        $identifier = app(LearnerIdentifiers::class)->forLearner($request->user(), $learner);
        $admissionNo = $learner?->admission_no;

        return response()->view('people.profile-form', compact('form', 'subject', 'canInvite', 'identifier', 'admissionNo'))->header('Cache-Control', 'private, no-store');
    }

    public function update(ProfilePanelRequest $request, ProfileForms $forms, ?int $person = null): JsonResponse
    {
        [$subject, $type] = $this->subject($request, $person);
        Gate::authorize('profile.update', [$subject, $type]);
        try {
            $forms->save($request->user(), $subject, $type, $request->profileValues());
        } catch (ValidationException $exception) {
            return response()->json(['message' => collect($exception->errors())->flatten()->first(), 'errors' => $exception->errors()], 422);
        }

        return ResponseService::successResponse('data_update_successfully');
    }

    /** Emails a staff member a one-use link to choose their password. */
    public function invite(StaffInvitationRequest $request, int $person): RedirectResponse
    {
        app(StaffInvitations::class)->inviteFromSchool($request->user(), $person);

        return redirect()->route('people-profiles.index', $person)->with('success', __('Invitation sent'));
    }

    public function export(ProfilePanelRequest $request, ProfileForms $forms, int $person): StreamedResponse
    {
        [$subject, $type] = $this->subject($request, $person);
        Gate::authorize('profile.view', [$subject, $type]);
        $form = $forms->schema($request->user(), $subject, $type, forExport: true);
        $fields = collect($form['categories'])->pluck('fields')->flatten(1);
        Audit::record('profile.exported', $subject, [], ['profile_type' => $type, 'field_keys' => $fields->pluck('key')->all()],
            schoolId: app(CurrentSchool::class)->id());

        return response()->streamDownload(function () use ($form) {
            $output = fopen('php://output', 'w');
            fputcsv($output, [__('Category'), __('Field key'), __('Field label'), __('Value')], escape: '');
            foreach ($form['categories'] as $category) {
                foreach ($category['fields'] as $field) {
                    $value = $field['value'];
                    $text = $value === null ? '' : (isset($value['file']) ? $value['file']['name'] : (isset($value['choices']) ? implode('; ', $value['choices'])
                        : (isset($value['flag']) ? ($value['flag'] ? 'true' : 'false') : $value['text'])));
                    $cells = array_map(fn ($cell) => preg_match('/^[\s\x00-\x1f]*[=+@-]/u', $cell) ? "'".$cell : $cell,
                        [$category['label'], $field['key'], $field['label'], $text]);
                    fputcsv($output, $cells, escape: '');
                }
            }
            fclose($output);
        }, 'profile-'.$subject->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
