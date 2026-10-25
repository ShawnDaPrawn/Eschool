<?php

declare(strict_types=1);

namespace App\Services\Recognition;

use App\Models\Recognition\AwardPresentation;
use App\Models\Recognition\HousePoint;
use App\Models\User;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\PrivateNoticeDelivery;
use App\Services\Platform\Notifications\PrivateNoticeScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class RecognitionNotices implements PrivateNoticeDelivery, PrivateNoticeScope
{
    public const LINK = '/community/recognition';

    private const TYPES = ['recognition.points_changed' => ['house_points', HousePoint::class], 'recognition.award_presented' => ['awards', AwardPresentation::class]];

    public function changed(HousePoint|AwardPresentation $source): void
    {
        if ($source instanceof AwardPresentation && $source->state !== 'active') {
            return;
        }
        $type = $source instanceof HousePoint ? 'recognition.points_changed' : 'recognition.award_presented';
        $recipients = app(RecognitionAudience::class)->recipients((int) $source->learner_id, self::TYPES[$type][0]);
        if ($recipients === []) {
            return;
        }
        $notifier = app(Notifier::class);
        $notice = $notifier->makeNotice($type, __('Recognition update'), __('Open Houses and awards to review an update to your family recognition.'), self::LINK);
        $notice->delivery_payload = ['link' => self::LINK, 'recognition_source_id' => (int) $source->id, 'recognition_revision' => (int) ($source->revision ?? 1)];
        $notice->save();
        $notifier->assign($notice, $recipients);
    }

    public function apply(Builder $notifications, User $actor): void
    {
        $payload = $notifications->qualifyColumn('delivery_payload');
        $safe = "IF(JSON_VALID($payload), $payload, '{}')";
        $notifications->where(function (Builder $types) use ($actor, $safe) {
            $types->whereRaw('1 = 0');
            foreach (self::TYPES as $type => [$module, $class]) {
                try {
                    $learners = app(RecognitionAudience::class)->learners($actor, $module);
                } catch (HttpExceptionInterface|ModelNotFoundException) {
                    continue;
                }
                if ($learners === []) {
                    continue;
                }
                $model = new $class;
                $table = $model->getTable();
                $source = $class::whereIn('learner_id', $learners)->whereRaw("CAST($table.id AS CHAR) = JSON_UNQUOTE(JSON_EXTRACT($safe, '$.recognition_source_id'))");
                if ($class === AwardPresentation::class) {
                    $source->where('state', 'active')->whereRaw("CAST($table.revision AS CHAR) = JSON_UNQUOTE(JSON_EXTRACT($safe, '$.recognition_revision'))");
                } else {
                    $source->whereRaw("JSON_UNQUOTE(JSON_EXTRACT($safe, '$.recognition_revision')) = '1'");
                }
                $types->orWhere(fn (Builder $branch) => $branch->where('type', $type)->whereRaw("JSON_UNQUOTE(JSON_EXTRACT($safe, '$.link')) = ?", [self::LINK])->whereExists($source->selectRaw('1')->toBase()));
            }
        });
    }

    public function allows(User $actor, string $type, array $payload): bool
    {
        if (! isset(self::TYPES[$type]) || ($payload['link'] ?? null) !== self::LINK) {
            return false;
        }
        foreach (['recognition_source_id', 'recognition_revision'] as $key) {
            if ((! is_string($payload[$key] ?? null) && ! is_int($payload[$key] ?? null)) || preg_match('/^[1-9][0-9]*$/D', (string) $payload[$key]) !== 1 || filter_var($payload[$key], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                return false;
            }
        }
        [$module, $class] = self::TYPES[$type];
        try {
            $source = $class::findOrFail((int) $payload['recognition_source_id']);
            app(RecognitionAuthority::class)->family($actor, (int) $source->learner_id, $module);

            return in_array((int) $source->learner_id, app(RecognitionAudience::class)->learners($actor, $module), true)
                && ($source instanceof HousePoint ? (int) $payload['recognition_revision'] === 1 : $source->state === 'active' && (int) $source->revision === (int) $payload['recognition_revision']);
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }
}
