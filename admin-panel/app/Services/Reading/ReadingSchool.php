<?php

declare(strict_types=1);

namespace App\Services\Reading;

use App\Models\Academics\GradeLevel;
use App\Models\Reading\ReadingBook;
use App\Models\Reading\ReadingHiddenItem;
use App\Models\Reading\ReadingSettings;
use App\Models\Reading\ReadingShelf;
use App\Models\Reading\ReadingShelfBook;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * A school's own choices about the reading library (Action Plan 4.4): which
 * of Navigate Ed's shelves and books its learners do not see, and its reading
 * badge switches. These are rows of the school's own; the catalogue is never
 * changed from a school.
 */
class ReadingSchool
{
    public const DEFAULTS = ['finished_badge' => false, 'days_badge' => false, 'days_required' => 4];

    public static function hideRules(): array
    {
        return ['kind' => ['required', Rule::in(['book', 'shelf'])], 'item_id' => ['required', 'integer', 'min:1'], 'hidden' => ['required', 'boolean']];
    }

    public static function settingsRules(): array
    {
        return ['finished_badge' => ['required', 'boolean'], 'days_badge' => ['required', 'boolean'], 'days_required' => ['required', 'integer', 'between:2,7'],
            'revision' => ['required', 'integer', 'min:0']];
    }

    public function overview(User $actor): array
    {
        app(ReadingAccess::class)->leader($actor);
        $grades = GradeLevel::query()->pluck('name', 'sort_order');
        $shelves = ReadingShelf::query()->where('active', true)->orderBy('grade_from')->orderBy('name')->orderBy('id')->limit(200)->get();
        $entries = ReadingShelfBook::query()->whereIn('reading_shelf_id', $shelves->pluck('id'))->orderBy('position')->get();
        $books = ReadingBook::query()->whereIn('id', $entries->pluck('reading_book_id')->unique())->where('rights_status', ReadingBook::CLEARED)->get()->keyBy('id');
        $hidden = ReadingHiddenItem::query()->get();
        $hiddenShelves = $hidden->where('kind', 'shelf')->pluck('item_id')->map(fn ($id) => (int) $id)->all();
        $hiddenBooks = $hidden->where('kind', 'book')->pluck('item_id')->map(fn ($id) => (int) $id)->all();
        $settings = ReadingSettings::query()->first();

        return [
            'shelves' => $shelves->map(fn (ReadingShelf $shelf) => ['id' => (int) $shelf->id, 'name' => $shelf->name, 'description' => $shelf->description,
                'grades' => $grades[$shelf->grade_from] === $grades[$shelf->grade_to] ? (string) $grades[$shelf->grade_from] : $grades[$shelf->grade_from].' – '.$grades[$shelf->grade_to],
                'hidden' => in_array((int) $shelf->id, $hiddenShelves, true),
                'books' => $entries->where('reading_shelf_id', $shelf->id)->filter(fn ($e) => isset($books[$e->reading_book_id]))
                    ->map(fn ($e) => ['id' => (int) $e->reading_book_id, 'title' => $books[$e->reading_book_id]->title, 'author' => $books[$e->reading_book_id]->author,
                        'level' => ReadingCatalogue::LEVELS[$books[$e->reading_book_id]->reading_level] ?? '', 'hidden' => in_array((int) $e->reading_book_id, $hiddenBooks, true)])->values()->all()])->all(),
            'settings' => ($settings === null ? self::DEFAULTS : $settings->only(array_keys(self::DEFAULTS))) + ['revision' => (int) ($settings->revision ?? 0)],
            'awardsOn' => app(ModuleGate::class)->allows('awards'),
        ];
    }

    /** Hide or show a shelf or a book for this school's learners. */
    public function hide(User $actor, array $input): bool
    {
        $actor = app(ReadingAccess::class)->leader($actor, true);
        $data = Validator::make($input, self::hideRules())->validate();
        $id = (int) $data['item_id'];
        $hidden = filter_var($data['hidden'], FILTER_VALIDATE_BOOLEAN);
        abort_unless($data['kind'] === 'shelf' ? ReadingShelf::query()->whereKey($id)->exists() : ReadingBook::query()->whereKey($id)->exists(), 404);

        return DB::transaction(function () use ($actor, $data, $id, $hidden): bool {
            $row = ReadingHiddenItem::query()->where('kind', $data['kind'])->where('item_id', $id)->lockForUpdate()->first();
            if (($row !== null) === $hidden) {
                return false;
            }
            if ($hidden) {
                $row = ReadingHiddenItem::query()->create(['kind' => $data['kind'], 'item_id' => $id, 'hidden_by' => $actor->id]);
            } else {
                $row->delete();
            }
            Audit::record('reading_'.$data['kind'].($hidden ? '_hidden' : '_shown'), $row, [], ['kind' => $data['kind'], 'item_id' => $id, 'changed_by' => $actor->id]);

            return true;
        });
    }

    public function configure(User $actor, array $input): ReadingSettings
    {
        $actor = app(ReadingAccess::class)->leader($actor, true);
        $data = Validator::make($input, self::settingsRules())->validate();
        $finished = filter_var($data['finished_badge'], FILTER_VALIDATE_BOOLEAN);
        $days = filter_var($data['days_badge'], FILTER_VALIDATE_BOOLEAN);

        return DB::transaction(function () use ($actor, $data, $finished, $days): ReadingSettings {
            $row = ReadingSettings::query()->lockForUpdate()->first() ?? new ReadingSettings(['revision' => 0]);
            abort_unless((int) $row->revision === (int) $data['revision'], 409, __('The reading badge settings changed. Reload the page and try again.'));
            $fields = ['finished_badge' => $finished, 'days_badge' => $days, 'days_required' => (int) $data['days_required']];
            $row->fill($fields + ['switched_on_by' => $finished || $days ? (int) $actor->id : $row->switched_on_by, 'revision' => (int) $row->revision + 1])->save();
            Audit::record('reading_badges_configured', $row, [], $fields + ['revision' => $row->revision, 'changed_by' => $actor->id]);

            return $row;
        });
    }
}
