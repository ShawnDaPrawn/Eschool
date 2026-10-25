<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicPages;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admissions\ApplicantRequest;
use App\Services\Admissions\AdmissionOffers;
use App\Services\Admissions\ApplicantPage;
use App\Services\Admissions\ApplicantSession;
use App\Services\Admissions\ApplicationDocuments;
use App\Services\Admissions\ApplicationLinks;
use App\Services\Admissions\Applications;
use App\Services\Admissions\IntakeAuthority;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AdmissionsController extends Controller
{
    public function form(ApplicantRequest $r): Response
    {
        $intake = app(ApplicantPage::class)->intake($r);
        if (isset(app(ApplicantSession::class)->state($r, $intake)['id'])) {
            return redirect(app(ApplicantPage::class)->url($r, 'application'), 303);
        }

        return app(ApplicantPage::class)->render($r, 'form');
    }

    public function another(ApplicantRequest $r): Response
    {
        $intake = app(ApplicantPage::class)->intake($r);
        app(IntakeAuthority::class)->open($intake);
        app(ApplicantSession::class)->put($r, $intake, ['nonce' => (string) Str::uuid(), 'key' => bin2hex(random_bytes(32)), 'issued' => now()->getTimestamp()]);

        return redirect(app(ApplicantPage::class)->url($r, 'form'), 303);
    }

    public function resume(ApplicantRequest $r): Response
    {
        return app(ApplicantPage::class)->render($r, 'resume');
    }

    public function application(ApplicantRequest $r): Response
    {
        return app(ApplicantPage::class)->render($r, 'application');
    }

    public function begin(ApplicantRequest $r): Response
    {
        $intake = app(ApplicantPage::class)->intake($r);
        $session = app(ApplicantSession::class);
        $session->challenge($r, $intake);
        $state = $session->state($r, $intake);
        $row = app(Applications::class)->begin($intake, (int) $r->validated('intake_grade_id'), $state['nonce'], $state['key'], $r->validated('contact'), $r->validated('answers', []));
        $session->put($r, $intake, $state + ['id' => (int) $row->id]);

        return redirect(app(ApplicantPage::class)->url($r, 'application'), 303);
    }

    public function save(ApplicantRequest $r): Response
    {
        $intake = app(ApplicantPage::class)->intake($r);
        $row = app(ApplicantSession::class)->owner($r, $intake, true);
        $state = app(ApplicantSession::class)->state($r, $intake);
        app(Applications::class)->save((int) $row->id, $state['key'], (int) $r->validated('revision'), $r->validated('contact'), $r->validated('answers', []));

        return redirect(app(ApplicantPage::class)->url($r, 'application'), 303);
    }

    public function submit(ApplicantRequest $r): Response
    {
        $intake = app(ApplicantPage::class)->intake($r);
        $row = app(ApplicantSession::class)->owner($r, $intake, true);
        app(Applications::class)->submit((int) $row->id, app(ApplicantSession::class)->state($r, $intake)['key'], (int) $r->validated('revision'));

        return redirect(app(ApplicantPage::class)->url($r, 'application'), 303);
    }

    public function resumeRequest(ApplicantRequest $r): Response
    {
        $intake = app(ApplicantPage::class)->intake($r);
        app(ApplicantSession::class)->challenge($r, $intake);
        app(ApplicationLinks::class)->requestResume($intake, $r->validated('email'));

        return app(ApplicantPage::class)->render($r, 'email-sent');
    }

    public function exchange(ApplicantRequest $r): Response
    {
        $intake = app(ApplicantPage::class)->intake($r);
        $state = app(ApplicationLinks::class)->consume($intake, $r->validated('token'));
        $r->session()->regenerate();
        app(ApplicantSession::class)->put($r, $intake, $state + ['issued' => now()->getTimestamp(), 'nonce' => (string) Str::uuid()]);

        return redirect(app(ApplicantPage::class)->url($r, 'application'), 303);
    }

    public function decide(ApplicantRequest $r): Response
    {
        $intake = app(ApplicantPage::class)->intake($r);
        $row = app(ApplicantSession::class)->owner($r, $intake, true);
        app(AdmissionOffers::class)->decide((int) $row->id, app(ApplicantSession::class)->state($r, $intake)['key'], (int) $r->validated('revision'), $r->validated('decision'));

        return redirect(app(ApplicantPage::class)->url($r, 'application'), 303);
    }

    public function upload(ApplicantRequest $r): Response
    {
        $intake = app(ApplicantPage::class)->intake($r);
        $row = app(ApplicantSession::class)->owner($r, $intake, true);
        app(ApplicationDocuments::class)->upload((int) $row->id, app(ApplicantSession::class)->state($r, $intake)['key'], $r->validated('category'), $r->validated('operation_id'), $r->file('document'));

        return redirect(app(ApplicantPage::class)->url($r, 'application'), 303);
    }

    public function document(ApplicantRequest $r): Response
    {
        $intake = app(ApplicantPage::class)->intake($r);
        $row = app(ApplicantSession::class)->owner($r, $intake);
        $result = app(ApplicationDocuments::class)->ownerBytes((int) $row->id, app(ApplicantSession::class)->state($r, $intake)['key'], $r->validated('handle'));

        return response($result['bytes'])->header('Content-Type', $result['mime'])->header('Content-Disposition', 'attachment; filename="application-document.'.($result['mime'] === 'application/pdf' ? 'pdf' : 'jpg').'"')->header('Cache-Control', 'private, no-store');
    }

    public function remove(ApplicantRequest $r): Response
    {
        $intake = app(ApplicantPage::class)->intake($r);
        $row = app(ApplicantSession::class)->owner($r, $intake, true);
        app(ApplicationDocuments::class)->remove((int) $row->id, app(ApplicantSession::class)->state($r, $intake)['key'], $r->validated('handle'));

        return redirect(app(ApplicantPage::class)->url($r, 'application'), 303);
    }
}
