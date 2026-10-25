<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Cards;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cards\CardRequest;
use App\Services\Cards\CardPortraits;
use App\Services\Cards\CardQueries;
use App\Services\Cards\CardReaders;
use App\Services\Cards\Cards;
use App\Services\Cards\CardScans;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class CardController extends Controller
{
    public function read(CardRequest $request): JsonResponse
    {
        $queries = app(CardQueries::class);
        $actor = $request->user();
        $page = (int) $request->validated('page', 1);

        return $this->result(match ($request->action()) {
            'configuration' => $queries->configuration($actor), 'mine' => $queries->listing($actor, false, $page),
            'managed' => $queries->listing($actor, true, $page), 'read' => $queries->read($actor, (string) $request->route('card')),
            'people' => $queries->people($actor, $request->validated('type'), $request->validated('search') ?? '', $page),
        });
    }

    public function write(CardRequest $request): JsonResponse
    {
        $cards = app(Cards::class);
        $actor = $request->user();
        $id = (string) $request->route('card');
        $input = $request->validated();

        return $this->result(match ($request->action()) {
            'issue' => $cards->issue($actor, $input), 'credential' => $cards->credential($actor, $id),
            'freeze' => $cards->freeze($actor, $id, $input), 'replace' => $cards->replace($actor, $id, $input), 'restore' => $cards->restore($actor, $id, $input),
            'readers.register' => app(CardReaders::class)->register($actor, $input), 'readers.revoke' => $this->revoke($request),
        });
    }

    private function revoke(CardRequest $request): array
    {
        app(CardReaders::class)->revoke($request->user(), (string) $request->route('device'));

        return ['revoked' => true];
    }

    public function upload(CardRequest $request): JsonResponse
    {
        $photo = $request->file('photo');
        abort_unless($photo instanceof UploadedFile, 422);

        return $this->result(app(CardPortraits::class)->upload($request->user(), (int) $request->validated('person_id'), $request->validated('type'), $request->validated('operation_id'), $photo));
    }

    public function portrait(CardRequest $request): Response
    {
        if ($request->action() === 'device.portrait') {
            $image = app(CardPortraits::class)->scanned($request->bearerToken() ?? '', (string) $request->route('scan'));
        } else {
            $card = app(CardQueries::class)->read($request->user(), (string) $request->route('card'));
            $image = app(CardPortraits::class)->owner($request->user(), $card['person_id'], $card['type']);
        }

        return response($image['bytes'], 200, ['Content-Type' => $image['mime'], 'Cache-Control' => 'no-store, private', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function scan(CardRequest $request): JsonResponse
    {
        $scans = app(CardScans::class);

        return $this->result($request->action() === 'device.qr' ? $scans->qr($request->bearerToken() ?? '', $request->validated()) : $scans->sun($request->bearerToken() ?? '', $request->validated()));
    }

    private function result(array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => 'Card request completed.', 'data' => $data, 'code' => 200], 200, ['Cache-Control' => 'no-store, private']);
    }
}
