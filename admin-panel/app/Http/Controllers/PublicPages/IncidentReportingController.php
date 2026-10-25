<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicPages;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wellbeing\PublicIncidentRequest;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Wellbeing\IncidentReporting;
use App\Services\Wellbeing\PublicReportingPage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IncidentReportingController extends Controller
{
    public function form(Request $request): Response
    {
        return app(PublicReportingPage::class)->render($request);
    }

    public function submit(PublicIncidentRequest $request): Response
    {
        $data = $request->validated();
        abort_unless($data['anonymous'] === true || $data['anonymous'] === 1 || $data['anonymous'] === '1', 422);

        return $this->act($request, function (IncidentReporting $reports) use ($data) {
            $reports->submit($data);

            return $reports->followUp($data['secret']);
        }, __('Report received. Keep your private code to read school replies.'));
    }

    public function followUp(PublicIncidentRequest $request): Response
    {
        $data = $request->validated();

        return $this->act($request, fn (IncidentReporting $reports) => $reports->followUp($data['secret'], null, isset($data['before']) ? (int) $data['before'] : null));
    }

    public function reply(PublicIncidentRequest $request): Response
    {
        $data = $request->validated();

        return $this->act($request, function (IncidentReporting $reports) use ($data) {
            $reports->reply($data['secret'], $data);

            return $reports->followUp($data['secret']);
        }, __('Your follow-up was received.'));
    }

    private function act(PublicIncidentRequest $request, callable $operation, ?string $message = null): Response
    {
        $page = app(PublicReportingPage::class);
        $school = $page->school((string) $request->route('schoolSlug'));
        $thread = app(CurrentSchool::class)->run($school, fn () => $operation(app(IncidentReporting::class)));

        return $page->render($request, $thread, $message);
    }
}
