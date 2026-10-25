<?php

declare(strict_types=1);

namespace App\Services\Admissions;

use App\Models\Academics\GradeLevel;
use App\Models\Admissions\ApplicationDocument;
use App\Models\Admissions\Intake;
use App\Models\Admissions\IntakeGrade;
use App\Models\Admissions\Offer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ApplicantPage
{
    public function intake(Request $r): Intake
    {
        $intake = $r->attributes->get('admissions.intake');
        abort_unless($intake instanceof Intake, 404);

        return Intake::findOrFail($intake->id);
    }

    public function url(Request $r, string $page): string
    {
        return route('admissions.public.'.$page, ['schoolSlug' => $r->route('schoolSlug'), 'intakeId' => $r->route('intakeId')]);
    }

    public function render(Request $r, string $screen, array $messages = [], int $status = 200, bool $restore = false): Response
    {
        $intake = $this->intake($r);
        app(IntakeAuthority::class)->enabled();
        $row = null;
        $contact = $answers = $documents = [];
        $offer = null;
        if ($screen === 'application') {
            $row = app(ApplicantSession::class)->owner($r, $intake);
            $contact = $row->parent_contact;
            $answers = $row->answers;
            $documents = ApplicationDocument::where('application_id', $row->id)->orderBy('id')->get(['handle', 'category', 'mime', 'bytes']);
            $offer = Offer::where('application_id', $row->id)->orderByDesc('id')->first(['state', 'expires_at']);
        }
        $fields = in_array($screen, ['form', 'application'], true) ? app(ApplicationForms::class)->schema($intake) : [];
        if ($restore) {
            // Reflect only this CSRF-protected author's bounded known form values.
            foreach (['first_name', 'last_name', 'email', 'mobile', 'relationship'] as $name) {
                if (is_string($r->input('contact.'.$name))) {
                    $contact[$name] = mb_substr($r->input('contact.'.$name), 0, 191);
                }
            }
            foreach ($fields as $field) {
                $value = $r->input('answers.'.$field['id']);
                if (is_string($value)) {
                    $answers[$field['id']] = mb_substr($value, 0, 20000);
                } elseif (is_array($value)) {
                    $answers[$field['id']] = array_map(fn ($item) => is_string($item) ? mb_substr($item, 0, 191) : '', array_slice($value, 0, 40));
                }
            }
        }
        $grades = IntakeGrade::where('intake_id', $intake->id)->orderBy('id')->get()->map(fn ($grade) => ['id' => $grade->id, 'name' => GradeLevel::findOrFail($grade->grade_level_id)->name]);
        $base = ['schoolSlug' => $r->route('schoolSlug'), 'intakeId' => $r->route('intakeId')];
        $state = app(ApplicantSession::class)->state($r, $intake);
        $writable = false;
        try {
            app(IntakeAuthority::class)->open($intake);
            $writable = true;
        } catch (HttpExceptionInterface) {
            // Previously saved forms stay visible when an intake closes.
        }

        return response()->view('admissions.public.page', compact('intake', 'screen', 'row', 'contact', 'answers', 'fields', 'documents', 'offer', 'grades', 'base', 'state', 'writable', 'messages'), $status)
            ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }
}
