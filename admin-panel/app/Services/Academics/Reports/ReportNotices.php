<?php

declare(strict_types=1);

namespace App\Services\Academics\Reports;

use App\Models\Academics\ReportBatch;
use App\Models\Academics\ReportRecipient;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

class ReportNotices
{
    public function send(int $batchId): int
    {
        return DB::transaction(function () use ($batchId) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $batch = ReportBatch::with('cards')->where('state', 'released')->find($batchId);
            if ($batch === null) {
                return 0;
            }
            $sent = 0;
            foreach ($batch->cards as $card) {
                if ($card->generated_fingerprint !== $batch->fingerprint) {
                    continue;
                }
                $learner = Students::find($card->learner_id);
                if ($learner === null) {
                    continue;
                }
                $ids = app(GuardianService::class)->guardianUserIds($learner)->push((int) $learner->user_id)->unique();
                foreach (User::where('status', 1)->whereIn('id', $ids)->get() as $user) {
                    $recipient = ReportRecipient::firstOrCreate(['report_card_id' => $card->id, 'user_id' => $user->id]);
                    if ($recipient->notification_id !== null) {
                        continue;
                    }
                    $payload = $card->payload;
                    $title = __('Report published');
                    $body = __('A report for :name is ready. Open Reports to read it.', ['name' => $payload['learner_name']]);
                    $notifier = app(Notifier::class);
                    $notice = $notifier->makeNotice('academics.report_published', $title, $body, '/academics/reports');
                    $notice->delivery_payload = ['link' => '/academics/reports', 'report_card_id' => (int) $card->id, 'report_learner_id' => (int) $card->learner_id];
                    $notice->save();
                    $recipient->forceFill(['notification_id' => $notice->id])->save();
                    if ($notifier->send($user, 'academics.report_published', $title, $body, '/academics/reports', $notice) !== []) {
                        $sent++;
                    }
                }
            }

            return $sent;
        });
    }
}
