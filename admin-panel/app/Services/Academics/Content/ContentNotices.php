<?php

declare(strict_types=1);

namespace App\Services\Academics\Content;

use App\Jobs\Academics\NotifyContentReaders;
use App\Models\Academics\ContentRecipient;
use App\Models\Academics\ContentVersion;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ContentNotices
{
    public function send(int $id): int
    {
        return DB::transaction(function () use ($id) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $version = ContentVersion::with('item')->where('state', 'published')->find($id);
            if ($version === null || ! $version->item?->active || (int) $version->item->published_version_id !== $id || $version->item->notice_completed_at !== null) {
                return 0;
            }
            $today = app(AcademicAuthority::class)->today();
            $batch = max(1, min(100, (int) config('academic_content.notification_batch_size')));
            $ids = TeachingGroupMembership::whereIn('teaching_group_id', $version->metadata['group_ids'])->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)
                ->where('learner_id', '>', $version->item->notice_after_learner_id)->distinct()->orderBy('learner_id')->limit($batch + 1)->pluck('learner_id');
            $more = $ids->count() > $batch;
            $ids = $ids->take($batch);
            $sent = 0;
            foreach (Students::whereIn('id', $ids)->get() as $learner) {
                $recipients = app(GuardianService::class)->guardianUserIds($learner)->push((int) $learner->user_id)->unique();
                foreach (User::where('status', 1)->whereIn('id', $recipients)->get() as $user) {
                    try {
                        app(ContentPolicy::class)->reader($user, (int) $learner->id, $id);
                    } catch (HttpExceptionInterface|ModelNotFoundException $e) {
                        if ($e instanceof HttpExceptionInterface && ! in_array($e->getStatusCode(), [403, 404], true)) {
                            throw $e;
                        }

                        continue;
                    }
                    $recipient = ContentRecipient::firstOrCreate(['content_version_id' => $id, 'learner_id' => $learner->id, 'user_id' => $user->id]);
                    if ($recipient->notification_id !== null) {
                        continue;
                    }
                    $link = '/academics/content?learner='.$learner->id.'&version='.$id;
                    $notifier = app(Notifier::class);
                    $title = __('New learning material');
                    $body = __('New material is available for your subjects. Open Learning content.');
                    $notice = $notifier->makeNotice('academics.content_published', $title, $body, $link);
                    $notice->delivery_payload = ['link' => $link, 'content_version_id' => $id, 'content_learner_id' => (int) $learner->id];
                    $notice->save();
                    $recipient->update(['notification_id' => $notice->id]);
                    if ($notifier->send($user, 'academics.content_published', $title, $body, $link, $notice) !== []) {
                        $sent++;
                    }
                }
            }

            $version->item->update(['notice_after_learner_id' => $ids->last() ?? $version->item->notice_after_learner_id, 'notice_completed_at' => $more ? null : now()]);
            if ($more) {
                NotifyContentReaders::dispatch(app(CurrentSchool::class)->id(), $id);
            }

            return $sent;
        });
    }
}
