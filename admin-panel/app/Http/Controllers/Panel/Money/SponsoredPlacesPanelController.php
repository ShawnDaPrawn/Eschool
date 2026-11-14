<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\SchoolBusinessPanelRequest;
use App\Models\Students;
use App\Services\Platform\Subscriptions\SponsoredPlaces;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SponsoredPlacesPanelController extends Controller
{
    public function index(SchoolBusinessPanelRequest $request): Response
    {
        $records = app(SponsoredPlaces::class)->page($request->user());

        return response()->view('money.sponsored.index', compact('records'));
    }

    public function show(SchoolBusinessPanelRequest $request, int $subscription): Response
    {
        $record = app(SponsoredPlaces::class)->show($request->user(), $subscription);
        $section = (string) $request->input('section', 'learners');
        abort_unless(in_array($section, ['learners', 'assign'], true), 404);
        $learners = collect();
        if ($section === 'assign') {
            $learners = Students::query()->with('user')->when($request->filled('search'), fn ($q) => $q->whereHas('user', fn ($u) => $u->where(fn ($w) => $w->where('first_name', 'like', '%'.$request->input('search').'%')->orWhere('last_name', 'like', '%'.$request->input('search').'%'))))
                ->whereNotIn('id', array_column($record['learners'], 'learner_id'))->orderBy('id')->limit(100)->get();
        }

        return response()->view('money.sponsored.show', compact('record', 'section', 'learners'));
    }

    public function release(SchoolBusinessPanelRequest $request, int $subscription, int $learner): Response
    {
        $record = app(SponsoredPlaces::class)->show($request->user(), $subscription);
        $member = collect($record['learners'])->firstWhere('learner_id', $learner);
        abort_if($member === null, 404);

        return response()->view('money.sponsored.release', compact('record', 'member'));
    }

    public function add(SchoolBusinessPanelRequest $request, int $subscription): JsonResponse
    {
        app(SponsoredPlaces::class)->add($request->user(), $subscription, (int) $request->validated('learner_id'));

        return $this->saved($subscription, __('Learner assigned to the sponsored place.'));
    }

    public function remove(SchoolBusinessPanelRequest $request, int $subscription, int $learner): JsonResponse
    {
        app(SponsoredPlaces::class)->remove($request->user(), $subscription, $learner);

        return $this->saved($subscription, __('The learner was removed from this sponsorship. Their other entitlements are unchanged.'));
    }

    private function saved(int $subscription, string $message): JsonResponse
    {
        return response()->json(['error' => false, 'message' => $message, 'redirect' => route('sponsored-places.show', ['subscription' => $subscription])]);
    }
}
