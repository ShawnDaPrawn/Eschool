<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Admissions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admissions\IntakePanelRequest;
use App\Services\Admissions\AdmissionOffers;
use App\Services\Admissions\AdmissionReview;
use App\Services\Admissions\ApplicationDocuments;
use App\Services\Admissions\EnrolApplication;
use App\Services\Admissions\EnrolApplications;
use App\Services\Admissions\IntakeCatalogue;
use App\Services\Admissions\IntakePanelPage;
use Symfony\Component\HttpFoundation\Response;

class IntakePanelController extends Controller
{
    public function index(IntakePanelRequest $r): Response
    {
        return app(IntakePanelPage::class)->index($r);
    }

    public function configuration(IntakePanelRequest $r): Response
    {
        return app(IntakePanelPage::class)->configuration($r);
    }

    public function detail(IntakePanelRequest $r): Response
    {
        return app(IntakePanelPage::class)->detail($r);
    }

    public function configure(IntakePanelRequest $r): Response
    {
        $row = app(IntakeCatalogue::class)->save($r->user(), $r->validated(), $r->route('intake') ? (int) $r->route('intake') : null);

        return redirect()->route('admissions-panel.index', ['intake_id' => $row->id], 303)->with('success', __('Intake saved.'));
    }

    public function write(IntakePanelRequest $r): Response
    {
        $data = $r->validated();
        $id = (int) $r->route('application');
        match ($r->route('operation')) {
            'offers' => app(AdmissionOffers::class)->issue($r->user(), $data),
            'bulk-enrol' => app(EnrolApplications::class)->enrol($r->user(), $data),
            'review' => app(AdmissionReview::class)->decide($r->user(), $id, $data),
            'fee' => app(AdmissionReview::class)->officeFee($r->user(), $id, $data),
            'enrol' => app(EnrolApplication::class)->enrol($r->user(), $id, $data),
            default => abort(404),
        };

        return redirect()->route($id > 0 ? 'admissions-panel.detail' : 'admissions-panel.index', $id > 0 ? ['application' => $id] : [], 303)->with('success', __('Admissions change saved.'));
    }

    public function document(IntakePanelRequest $r): Response
    {
        $result = app(ApplicationDocuments::class)->staffBytes($r->user(), (int) $r->route('application'), (string) $r->route('handle'));

        return response($result['bytes'])->header('Content-Type', $result['mime'])->header('Content-Disposition', 'attachment; filename="application-document.'.($result['mime'] === 'application/pdf' ? 'pdf' : 'jpg').'"')->header('Cache-Control', 'private, no-store');
    }
}
