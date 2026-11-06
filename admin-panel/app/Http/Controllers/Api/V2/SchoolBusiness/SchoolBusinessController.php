<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\SchoolBusiness;

use App\Http\Controllers\Controller;
use App\Http\Requests\SchoolBusiness\SchoolBusinessRequest;
use App\Services\SchoolBusiness\Bursaries;
use App\Services\SchoolBusiness\BursaryDocuments;
use App\Services\SchoolBusiness\Giving;
use App\Services\SchoolBusiness\ReenrolmentContracts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/** Re-enrolment contracts, bursary applications and alumni giving (Action Plan 5.10). */
class SchoolBusinessController extends Controller
{
    public function handle(SchoolBusinessRequest $request): JsonResponse
    {
        $actor = $request->user();
        $input = $request->validated();
        $contract = (int) $request->route('contract');
        $learner = (int) $request->route('learner');
        $application = (string) $request->route('application');
        $campaign = (int) $request->route('campaign');
        $reenrolment = app(ReenrolmentContracts::class);
        $bursaries = app(Bursaries::class);
        $giving = app(Giving::class);

        return $this->result(match ($request->action()) {
            'reenrolment.index' => $reenrolment->index($actor),
            'reenrolment.publish' => $reenrolment->publish($actor, $input),
            'reenrolment.withdraw' => $reenrolment->withdraw($actor, $contract, $input),
            'reenrolment.outstanding' => $reenrolment->outstanding($actor, $contract, (int) ($input['page'] ?? 1)),
            'reenrolment.remind' => $reenrolment->remind($actor, $contract, $input),
            'reenrolment.learner' => $reenrolment->forGuardian($actor, $learner),
            'reenrolment.accept' => $reenrolment->accept($actor, $learner, $input, (string) $request->ip()),
            'bursaries.mine' => $bursaries->mine($actor),
            'bursaries.create' => $bursaries->create($actor, $request->all()),
            'bursaries.read' => $bursaries->read($actor, $application),
            'bursaries.update' => $bursaries->update($actor, $application, $request->all()),
            'bursaries.submit' => $bursaries->submit($actor, $application, $input),
            'bursaries.withdraw' => $bursaries->withdraw($actor, $application, $input),
            'bursaries.upload' => app(BursaryDocuments::class)->upload($actor, $application, ['operation_id' => $input['operation_id'], 'category' => $input['category']], $request->file('document')),
            'bursaries.queue' => $bursaries->queue($actor, $input),
            'bursaries.review' => $bursaries->review($actor, $application),
            'bursaries.note' => $bursaries->note($actor, $application, $input),
            'bursaries.decide' => $bursaries->decide($actor, $application, $input),
            'bursaries.discount' => $bursaries->issueDiscount($actor, $application, $input),
            'giving.index' => $giving->index($actor),
            'giving.create' => $giving->create($actor, $input),
            'giving.summary' => $giving->summary($actor, $campaign),
            'giving.close' => $giving->close($actor, $campaign, $input),
            'giving.pledge' => $giving->pledge($actor, $campaign, $input),
            'giving.donate' => $giving->donate($actor, $campaign, $input),
            'giving.donors' => $giving->donors($actor, $campaign),
            'giving.receipt' => $giving->receipt($actor, (int) $request->route('donation')),
            default => abort(404),
        });
    }

    public function document(SchoolBusinessRequest $request): Response
    {
        $content = app(BursaryDocuments::class)->read($request->user(), (string) $request->route('application'), (string) $request->route('document'), $request->action() === 'bursaries.committee-document');

        return response($content['bytes'], 200, ['Content-Type' => $content['mime'], 'Content-Disposition' => 'attachment; filename="'.$content['name'].'"',
            'Cache-Control' => 'no-store, private', 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => 'sandbox']);
    }

    private function result(array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => 'OK', 'data' => $data, 'code' => 200], 200, ['Cache-Control' => 'no-store, private']);
    }
}
