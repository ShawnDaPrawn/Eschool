<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Cards;

use App\Http\Controllers\Controller;
use App\Models\Cards\Card;
use App\Models\Cards\CardReaderDevice;
use App\Models\User;
use App\Services\Cards\CardAccess;
use App\Services\Cards\CardPanelScope;
use App\Services\Cards\CardPortraits;
use App\Services\Cards\CardPrintExports;
use App\Services\Cards\CardQueries;
use App\Services\Cards\CardReaders;
use App\Services\Cards\Cards;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class CardPanelController extends Controller
{
    public function index(Request $request, array $messages = [], int $status = 200): Response
    {
        app(CardPanelScope::class)->authorize($request);
        $readers = str_starts_with((string) $request->route()->getName(), 'card-readers-panel.');
        $school = app(CurrentSchool::class)->id();
        $account = (int) $request->user()->id;
        try {
            $data = Validator::make($request->query->all(), ['type' => ['sometimes', 'in:learner,staff,guardian'], 'search' => ['sometimes', 'nullable', 'string', 'max:100'], 'page' => ['sometimes', 'integer', 'between:1,100000'], 'person_id' => ['sometimes', 'integer', 'min:1']])->validate();
        } catch (ValidationException $error) {
            return response()->view('cards.invalid', ['messages' => collect($error->errors())->flatten()->all()], 422);
        }
        $type = $data['type'] ?? 'learner';
        $page = (int) ($data['page'] ?? 1);
        $search = $data['search'] ?? '';
        $selected = isset($data['person_id']) ? app(CardAccess::class)->person((int) $data['person_id'], $type) : null;
        $people = $readers ? ['items' => [], 'next_page' => null] : app(CardQueries::class)->people($request->user(), $type, $search, $page);
        $listing = $readers ? ['items' => [], 'next_page' => null] : app(CardQueries::class)->listing($request->user(), true, $page);
        $names = User::whereIn('id', array_column($listing['items'], 'person_id'))->get()->mapWithKeys(fn ($user) => [$user->id => $user->full_name]);
        $devices = $readers ? CardReaderDevice::select(['id', 'name', 'kind', 'status', 'expires_at'])->orderByDesc('created_at')->limit(100)->get() : collect();
        $canWrite = app(ModuleGate::class)->writable('cards');
        $operation = Str::isUuid($request->input('operation_id', '')) ? $request->input('operation_id') : (string) Str::uuid();

        return response()->view('cards.workbench', compact('readers', 'school', 'account', 'type', 'page', 'search', 'selected', 'people', 'listing', 'names', 'devices', 'operation', 'messages', 'canWrite'), $status);
    }

    public function write(Request $request): Response|RedirectResponse
    {
        app(CardPanelScope::class)->authorize($request);
        $input = $request->except(['_token', 'expected_account_id', 'expected_school_id']);
        $id = (string) $request->route('card');
        $actor = $request->user();
        try {
            $result = match ($request->route('operation')) {
                'issue' => app(Cards::class)->issue($actor, $input),
                'freeze' => app(Cards::class)->freeze($actor, $id, $input),
                'replace' => app(Cards::class)->replace($actor, $id, $input),
                'restore' => app(Cards::class)->restore($actor, $id, $input),
                'portrait' => $this->uploadPortrait($request),
                'register' => app(CardReaders::class)->register($actor, $input),
                'revoke' => $this->revoke($request),
            };
            if ($request->route('operation') === 'register') {
                $qr = app(CardPrintExports::class)->qr($result['credential']);

                return response()->view('cards.pairing', compact('result', 'qr'), 200);
            }

            return redirect()->route($request->route('operation') === 'revoke' ? 'card-readers-panel.index' : 'cards-panel.index');
        } catch (ValidationException $error) {
            return $this->index($request, collect($error->errors())->flatten()->all(), 422);
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [409, 422, 423], true)) {
                throw $error;
            }

            return $this->index($request, [$error->getMessage()], $error->getStatusCode());
        }
    }

    private function uploadPortrait(Request $request): array
    {
        $photo = $request->file('photo');
        abort_unless($photo instanceof UploadedFile, 422, 'Choose a private card portrait.');

        return app(CardPortraits::class)->upload($request->user(), (int) $request->input('person_id'), (string) $request->input('type'), (string) $request->input('operation_id'), $photo);
    }

    private function revoke(Request $request): array
    {
        app(CardReaders::class)->revoke($request->user(), (string) $request->route('device'));

        return ['revoked' => true];
    }

    public function portrait(Request $request): Response
    {
        app(CardPanelScope::class)->authorize($request);
        $card = Card::findOrFail((string) $request->route('card'));
        $image = app(CardPortraits::class)->owner($request->user(), (int) $card->user_id, $card->type);

        return response($image['bytes'], 200, ['Content-Type' => 'image/jpeg']);
    }

    public function print(Request $request): Response
    {
        app(CardPanelScope::class)->authorize($request);
        try {
            return response(app(CardPrintExports::class)->output($request->user(), $request->except(['_token', 'expected_account_id', 'expected_school_id'])), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="school-cards.pdf"']);
        } catch (ValidationException $error) {
            return $this->index($request, collect($error->errors())->flatten()->all(), 422);
        }
    }
}
