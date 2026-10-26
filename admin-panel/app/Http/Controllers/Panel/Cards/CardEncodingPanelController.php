<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Cards;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cards\CardEncodingPanelRequest;
use App\Models\Cards\Card;
use App\Models\Cards\CardChipProfile;
use App\Models\Cards\CardReaderDevice;
use App\Services\Cards\CardAccess;
use App\Services\Cards\CardEncoding;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use FilesystemIterator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use ZipArchive;

class CardEncodingPanelController extends Controller
{
    public function index(CardEncodingPanelRequest $request, string $card): Response|JsonResponse
    {
        $identity = Card::findOrFail($card);
        app(CardAccess::class)->active($identity);
        if ($request->header('X-Encoding-Lease') === '1') {
            return response()->json(['active' => true]);
        }
        $school = app(CurrentSchool::class)->id();
        $account = (int) $request->user()->id;
        $plan = CardChipProfile::where('card_id', $identity->id)->first(['id', 'encoder_device_id', 'encoded_at']);
        $devices = CardReaderDevice::where('kind', 'encoder')->where('status', 'active')->whereNull('revoked_at')->where('expires_at', '>', now()->utc())
            ->orderBy('name')->limit(100)->get(['id', 'name']);
        $operation = (string) Str::uuid();
        $canWrite = app(ModuleGate::class)->writable('cards');

        return response()->view('cards.encoding', compact('identity', 'school', 'account', 'plan', 'devices', 'operation', 'canWrite'));
    }

    public function runtime(CardEncodingPanelRequest $request): Response
    {
        $root = base_path('tool/cards/encoder');
        abort_unless(is_file($root.'/nexencoder/__main__.py') && is_file($root.'/requirements.txt') && is_file($root.'/README.md'), 503, 'The local encoder utility is unavailable.');
        $temporary = tempnam(sys_get_temp_dir(), 'navigateed-encoder-');
        abort_unless(is_string($temporary), 503);
        try {
            $zip = new ZipArchive;
            abort_unless($zip->open($temporary, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 503);
            foreach (['README.md', 'requirements.txt'] as $file) {
                $zip->addFile($root.'/'.$file, $file);
            }
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/nexencoder', FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($file->isFile() && ! $file->isLink() && $file->getExtension() === 'py') {
                    $zip->addFile($file->getPathname(), substr($file->getPathname(), strlen($root) + 1));
                }
            }
            abort_unless($zip->close(), 503);
            $bytes = file_get_contents($temporary);
            abort_unless(is_string($bytes), 503);
        } finally {
            @unlink($temporary);
        }
        app(CardAccess::class)->encode($request->user(), false);

        return response($bytes, 200, ['Content-Type' => 'application/zip', 'Content-Disposition' => 'attachment; filename="navigateed-card-encoder.zip"']);
    }

    public function prepare(CardEncodingPanelRequest $request, string $card): Response|RedirectResponse
    {
        try {
            app(CardEncoding::class)->prepare($request->user(), $card, $request->safe()->except(['expected_account_id', 'expected_school_id']));
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [409, 422, 503], true)) {
                throw $error;
            }

            return response()->view('cards.invalid', ['messages' => [$error->getMessage()]], $error->getStatusCode());
        }

        return redirect()->route('cards-encoding.index', ['card' => $card, 'expected_account_id' => $request->user()->id, 'expected_school_id' => app(CurrentSchool::class)->id()]);
    }
}
