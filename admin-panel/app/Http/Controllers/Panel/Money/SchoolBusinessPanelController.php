<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\SchoolBusinessPanelRequest;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\SchoolBusiness\Bursaries;
use App\Services\SchoolBusiness\BursaryDocuments;
use App\Services\SchoolBusiness\BusinessAuthority;
use App\Services\SchoolBusiness\Giving;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SchoolBusinessPanelController extends Controller
{
    public function bursaries(SchoolBusinessPanelRequest $request): Response
    {
        $queue = app(Bursaries::class)->queue($request->user(), $request->only(['state', 'page']));

        return response()->view('money.business.bursaries', compact('queue'));
    }

    public function bursary(SchoolBusinessPanelRequest $request, string $application): Response
    {
        $record = app(Bursaries::class)->review($request->user(), $application);
        $section = (string) $request->input('section', 'overview');
        abort_unless(in_array($section, ['overview', 'note', 'award', 'decline', 'history', 'discount'], true), 404);
        $canWrite = app(ModuleGate::class)->writable(Bursaries::MODULE) && ! $record['erased'];

        return response()->view('money.business.bursary', compact('record', 'section', 'canWrite'));
    }

    public function bursaryWrite(SchoolBusinessPanelRequest $request, string $application): JsonResponse
    {
        $service = app(Bursaries::class);
        $data = match (true) {
            $request->routeIs('bursary-panel.note') => $service->note($request->user(), $application, $request->domainInput()),
            $request->routeIs('bursary-panel.decide') => $service->decide($request->user(), $application, $request->domainInput()),
            $request->routeIs('bursary-panel.discount') => $service->issueDiscount($request->user(), $application, $request->domainInput()),
            default => abort(404),
        };

        return $this->saved(__('Bursary record updated. Review its decision history and fee-discount state.'), route('bursary-panel.show', ['application' => $application, 'section' => 'history']), $data);
    }

    public function document(SchoolBusinessPanelRequest $request, string $application, string $document): Response
    {
        $file = app(BursaryDocuments::class)->read($request->user(), $application, $document, true);

        return response($file['bytes'], 200, ['Content-Type' => $file['mime'], 'Content-Disposition' => 'attachment; filename="'.$file['name'].'"', 'Content-Security-Policy' => 'sandbox']);
    }

    public function giving(SchoolBusinessPanelRequest $request): Response
    {
        $campaignPage = app(Giving::class)->campaignPage($request->user(), (string) $request->input('search', ''));
        $campaigns = $campaignPage->items();
        $canManage = app(BusinessAuthority::class)->holds($request->user(), Giving::CAMPAIGNS);
        $section = (string) $request->input('section', 'campaigns');
        abort_unless(in_array($section, ['campaigns', 'create'], true), 404);
        if ($section === 'create') {
            app(BusinessAuthority::class)->staff($request->user(), Giving::MODULE, Giving::CAMPAIGNS);
        }
        $canWrite = app(ModuleGate::class)->writable(Giving::MODULE);

        return response()->view('money.business.giving', compact('campaigns', 'campaignPage', 'section', 'canManage', 'canWrite'));
    }

    public function campaign(SchoolBusinessPanelRequest $request, int $campaign): Response
    {
        $record = app(Giving::class)->campaign($request->user(), $campaign);
        $section = (string) $request->input('section', 'overview');
        abort_unless(in_array($section, ['overview', 'donors', 'pledge', 'donation', 'close'], true), 404);
        $canManage = app(BusinessAuthority::class)->holds($request->user(), Giving::CAMPAIGNS);
        $canDonors = app(BusinessAuthority::class)->holds($request->user(), Giving::DONORS);
        $donors = null;
        if (in_array($section, ['donors', 'pledge', 'donation'], true)) {
            $donors = app(Giving::class)->donors($request->user(), $campaign);
        }
        if ($section === 'close') {
            app(BusinessAuthority::class)->staff($request->user(), Giving::MODULE, Giving::CAMPAIGNS);
        }
        $canWrite = app(ModuleGate::class)->writable(Giving::MODULE);

        return response()->view('money.business.campaign', compact('record', 'section', 'canManage', 'canDonors', 'donors', 'canWrite'));
    }

    public function givingWrite(SchoolBusinessPanelRequest $request, ?int $campaign = null): JsonResponse
    {
        $service = app(Giving::class);
        $data = match (true) {
            $request->routeIs('giving-panel.create') => $service->create($request->user(), $request->domainInput()),
            $request->routeIs('giving-panel.close') => $service->close($request->user(), $campaign, $request->domainInput()),
            $request->routeIs('giving-panel.pledge') => $service->pledge($request->user(), $campaign, $request->domainInput()),
            $request->routeIs('giving-panel.donate') => $service->donate($request->user(), $campaign, $request->domainInput()),
            default => abort(404),
        };
        $redirect = $request->routeIs('giving-panel.donate') ? route('giving-panel.receipt', ['donation' => $data['id']])
            : route('giving-panel.show', ['campaign' => $campaign ?? $data['id'], 'section' => $request->routeIs('giving-panel.pledge') ? 'donors' : 'overview']);

        return $this->saved($request->routeIs('giving-panel.pledge') ? __('Pledge recorded. No funds have been received or posted.') : __('Giving record saved. Review the resulting state.'), $redirect, $data);
    }

    public function receipt(SchoolBusinessPanelRequest $request, int $donation): Response
    {
        $record = app(Giving::class)->receipt($request->user(), $donation);

        return response()->view('money.business.receipt', compact('record'));
    }

    private function saved(string $message, string $redirect, array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => $message, 'redirect' => $redirect, 'data' => $data]);
    }
}
