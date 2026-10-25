<?php

declare(strict_types=1);

namespace App\Services\Pastoral;

use App\Models\Pastoral\CoverAssignment;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Platform\Notifications\Notifier;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class CoverNotices
{
    public function assigned(CoverAssignment $cover): void
    {
        if ($cover->state !== 'assigned' || $cover->substitute_teacher_id === null) {
            return;
        }
        $teacher = Teacher::findOrFail($cover->substitute_teacher_id);
        $notifier = app(Notifier::class);
        $notice = $notifier->makeNotice('pastoral.cover_assigned', __('Relief cover updated'), __('Open Relief cover to review your assigned lesson.'), '/pastoral/cover');
        $notice->delivery_payload = ['link' => '/pastoral/cover', 'cover_assignment_id' => (int) $cover->id, 'cover_revision' => $cover->revision];
        $notice->save();
        $notifier->assign($notice, [(int) $teacher->user_id]);
    }

    public function allows(User $user, string $type, array $payload): bool
    {
        if ($type !== 'pastoral.cover_assigned' || ! isset($payload['cover_assignment_id'],$payload['cover_revision'])) {
            return false;
        }
        try {
            $actor = app(CoverAuthority::class)->actor($user);
            $cover = CoverAssignment::findOrFail((int) $payload['cover_assignment_id']);

            return $cover->revision === (int) $payload['cover_revision'] && $cover->state === 'assigned' && $cover->substitute_teacher_id !== null
                && app(CoverAuthority::class)->own($actor, (int) $cover->substitute_teacher_id) && app(CoverSources::class)->valid($cover);
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }
}
