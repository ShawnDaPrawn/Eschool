<?php

declare(strict_types=1);

namespace App\Services\Wellbeing;

use App\Models\Platform\School;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PublicReportingPage
{
    public function school(string $slug): School
    {
        $school = School::active()->where('slug', $slug)->firstOrFail();
        app(CurrentSchool::class)->run($school, fn () => app(WellbeingSettings::class)->categories());

        return $school;
    }

    /** All draft state stays in this private response, never session flash. */
    public function render(Request $request, ?array $thread = null, ?string $message = null, array $errors = [], int $status = 200): Response
    {
        $school = $this->school((string) $request->route('schoolSlug'));
        $configuration = app(CurrentSchool::class)->run($school, fn () => app(WellbeingSettings::class)->categories());
        $canWrite = $this->writable($request);
        $posted = $request->isMethod('POST') ? ($request->isJson() ? $request->json()->all() : $request->request->all()) : [];
        $reportMode = in_array($request->route()->getName(), ['public.wellbeing.form', 'public.wellbeing.submit'], true);
        $provided = $posted['secret'] ?? null;
        $secret = is_string($provided) && preg_match('/^[a-f0-9]{64}$/D', $provided) ? $provided : ($reportMode ? bin2hex(random_bytes(32)) : '');
        $body = $posted['body'] ?? '';
        $draft = ['body' => is_string($body) && mb_strlen($body) <= 20000 ? $body : '',
            'category_id' => is_scalar($posted['category_id'] ?? null) ? (string) $posted['category_id'] : '',
            'urgency' => in_array($posted['urgency'] ?? null, ['routine', 'urgent'], true) ? $posted['urgency'] : 'routine'];

        return $this->view('wellbeing.public-reporting', compact('school', 'configuration', 'secret', 'reportMode', 'thread', 'draft', 'message', 'errors', 'canWrite') + ['operationId' => (string) Str::uuid()], $status);
    }

    public function writable(Request $request): bool
    {
        $school = $this->school((string) $request->route('schoolSlug'));

        return app(CurrentSchool::class)->run($school, fn () => app(ModuleGate::class)->writable('wellbeing'));
    }

    public function failedDraft(Request $request, array $errors, int $status): Response
    {
        $thread = null;
        if ($request->route()->getName() === 'public.wellbeing.reply') {
            $body = $request->isJson() ? $request->json()->all() : $request->request->all();
            $secret = $body['secret'] ?? null;
            if (is_string($secret) && preg_match('/^[a-f0-9]{64}$/D', $secret)) {
                try {
                    $school = $this->school((string) $request->route('schoolSlug'));
                    $thread = app(CurrentSchool::class)->run($school, fn () => app(IncidentReporting::class)->followUp($secret));
                } catch (ModelNotFoundException|HttpExceptionInterface) {
                    return $this->unavailable(404);
                }
            }
        }

        return $this->render($request, $thread, errors: $errors, status: $status);
    }

    public function unavailable(int $status): Response
    {
        return $this->view('wellbeing.public-unavailable', ['status' => $status], $status);
    }

    private function view(string $name, array $data, int $status): Response
    {
        $previous = app()->getLocale();
        try {
            app()->setLocale('en');

            return response()->view($name, $data, $status, $this->headers());
        } finally {
            app()->setLocale($previous);
        }
    }

    private function headers(): array
    {
        return ['Cache-Control' => 'no-store, private', 'Referrer-Policy' => 'no-referrer', 'X-Content-Type-Options' => 'nosniff', 'X-Frame-Options' => 'DENY',
            'Content-Security-Policy' => "default-src 'none'; style-src 'self' 'unsafe-inline'; font-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'"];
    }
}
