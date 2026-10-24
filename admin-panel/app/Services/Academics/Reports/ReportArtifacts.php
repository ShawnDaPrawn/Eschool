<?php

declare(strict_types=1);

namespace App\Services\Academics\Reports;

use App\Models\Academics\ReportCard;
use App\Models\Platform\School;
use App\Services\Platform\Tenancy\CurrentSchool;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ReportArtifacts
{
    public const DISK = 'academic_private';

    public function available(ReportCard $card, string $fingerprint): bool
    {
        return $this->bytes($card, $fingerprint) !== null;
    }

    private function bytes(ReportCard $card, string $fingerprint): ?string
    {
        if ($card->generated_fingerprint !== $fingerprint || $card->pdf_sha256 === null || ! $this->validPath($card)) {
            return null;
        }
        try {
            if (! Storage::disk(self::DISK)->exists($card->pdf_path)) {
                return null;
            }
            $bytes = Crypt::decryptString(Storage::disk(self::DISK)->get($card->pdf_path));
            if (str_starts_with($bytes, '%PDF-') && hash_equals($card->pdf_sha256, hash('sha256', $bytes))) {
                return $bytes;
            }
        } catch (Throwable) {
            // Do not put private bytes, paths or cryptographic errors in logs.
        }
        Log::warning('report_artifact_invalid', ['card_id' => (int) $card->id]);

        return null;
    }

    private function validPath(ReportCard $card): bool
    {
        $prefix = app(CurrentSchool::class)->storagePath('reports').'/'.$card->id.'/';

        return is_string($card->pdf_path) && str_starts_with($card->pdf_path, $prefix) && preg_match('/^[0-9a-f-]{36}\.enc$/D', substr($card->pdf_path, strlen($prefix))) === 1;
    }

    public function generate(int $cardId, string $fingerprint): void
    {
        $card = ReportCard::with('batch', 'comments')->find($cardId);
        if ($card === null || ! in_array($card->batch->state, ['approved', 'ready'], true) || $card->batch->fingerprint !== $fingerprint || $this->available($card, $fingerprint)) {
            return;
        }
        $payload = $card->payload;
        $comments = $card->comments->mapWithKeys(fn ($comment) => [$comment->scope_key => $comment->text])->all();
        $pdf = Pdf::setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false, 'defaultFont' => 'DejaVu Sans'])
            ->loadView('academics.reports.pdf', ['card' => $payload, 'design' => $card->batch->design, 'comments' => $comments, 'signatures' => $card->batch->signature_data ?? []])->setPaper('a4');
        $bytes = $pdf->output();
        if (! str_starts_with($bytes, '%PDF-')) {
            throw new RuntimeException('Report renderer did not produce a PDF.');
        }
        $path = app(CurrentSchool::class)->storagePath('reports').'/'.$cardId.'/'.Str::uuid().'.enc';
        $stored = false;
        try {
            if (! Storage::disk(self::DISK)->put($path, Crypt::encryptString($bytes))) {
                throw new RuntimeException('Private report artifact storage failed.');
            }
            DB::transaction(function () use ($cardId, $fingerprint, $path, $bytes, &$stored) {
                School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
                $card = ReportCard::with('batch')->findOrFail($cardId);
                $batch = $card->batch;
                if (! in_array($batch->state, ['approved', 'ready'], true) || $batch->fingerprint !== $fingerprint || $this->available($card, $fingerprint)) {
                    return;
                }
                $old = $this->validPath($card) ? $card->pdf_path : null;
                $card->forceFill(['pdf_path' => $path, 'pdf_sha256' => hash('sha256', $bytes), 'generated_fingerprint' => $fingerprint, 'generated_at' => now()])->save();
                if ($batch->cards()->where(fn ($q) => $q->whereNull('generated_fingerprint')->orWhere('generated_fingerprint', '!=', $fingerprint))->doesntExist()) {
                    $batch->forceFill(['state' => 'ready'])->save();
                }
                $stored = true;
                if ($old !== null && $old !== $path) {
                    DB::afterCommit(function () use ($old, $cardId) {
                        try {
                            Storage::disk(self::DISK)->delete($old);
                        } catch (Throwable) {
                            Log::warning('report_artifact_cleanup_pending', ['card_id' => $cardId]);
                        }
                    });
                }
            });
        } catch (Throwable $error) {
            Storage::disk(self::DISK)->delete($path);
            throw $error;
        } finally {
            if (! $stored) {
                Storage::disk(self::DISK)->delete($path);
            }
        }
    }

    public function read(ReportCard $card): string
    {
        $bytes = $this->bytes($card, $card->batch->fingerprint);
        abort_if($bytes === null, 409, __('The report PDF is unavailable. The school can retry generation before release or restore the published copy.'));

        return $bytes;
    }
}
