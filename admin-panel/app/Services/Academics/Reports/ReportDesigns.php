<?php

declare(strict_types=1);

namespace App\Services\Academics\Reports;

use App\Models\Academics\ReportCommentBank;
use App\Models\Academics\ReportTemplate;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ReportDesigns
{
    public static function rules(): array
    {
        return ['id' => ['sometimes', 'integer', 'min:1'], 'revision' => ['required_with:id', 'integer', 'min:1'], 'name' => ['required', 'string', 'max:191'],
            'design' => ['required', 'array:heading,introduction,footer,sections,show_crest'], 'design.heading' => ['required', 'string', 'max:191'],
            'design.introduction' => ['present', 'nullable', 'string', 'max:3000'], 'design.footer' => ['present', 'nullable', 'string', 'max:3000'],
            'design.sections' => ['required', 'array', 'min:1', 'max:4', 'contains:subjects'], 'design.sections.*' => ['required', 'in:subjects,attendance,subject_comments,homeroom_comment', 'distinct'],
            'design.show_crest' => ['required', 'boolean']];
    }

    public function save(User $actor, array $input): array
    {
        app(AcademicAuthority::class)->manage($actor);
        $data = Validator::make($input, self::rules())->validate();
        $data['design']['show_crest'] = (bool) $data['design']['show_crest'];

        return DB::transaction(function () use ($actor, $data) {
            app(AcademicAuthority::class)->manage($actor);
            $row = isset($data['id']) ? ReportTemplate::lockForUpdate()->findOrFail($data['id']) : new ReportTemplate(['revision' => 0]);
            abort_if($row->exists && $row->revision !== (int) $data['revision'], 409, __('The report template changed. Reload before saving.'));
            $before = $row->getAttributes();
            $row->fill(['name' => $data['name'], 'design' => $data['design'], 'revision' => $row->revision + 1])->save();
            Audit::record('academics.report_template_saved', $row, $before, $row->getAttributes());

            return $this->data($row);
        });
    }

    public static function bankRules(): array
    {
        return ['id' => ['sometimes', 'integer', 'min:1'], 'revision' => ['required_with:id', 'integer', 'min:1'], 'scope' => ['required', 'in:homeroom,subject'],
            'subject_id' => ['present', 'nullable', 'required_if:scope,subject', 'prohibited_if:scope,homeroom', 'integer', 'min:1'], 'label' => ['required', 'string', 'max:191'], 'text' => ['required', 'string', 'max:3000'], 'active' => ['required', 'boolean']];
    }

    public function bank(User $actor, array $input): array
    {
        app(AcademicAuthority::class)->manage($actor);
        $data = Validator::make($input, self::bankRules())->validate();

        return DB::transaction(function () use ($actor, $data) {
            app(AcademicAuthority::class)->manage($actor);
            if ($data['subject_id'] !== null) {
                Subject::findOrFail($data['subject_id']);
            }
            $row = isset($data['id']) ? ReportCommentBank::lockForUpdate()->findOrFail($data['id']) : new ReportCommentBank(['revision' => 0]);
            abort_if($row->exists && $row->revision !== (int) $data['revision'], 409, __('The comment bank changed. Reload before saving.'));
            $before = $row->getAttributes();
            $row->fill(array_diff_key($data, ['id' => true, 'revision' => true]) + ['revision' => $row->revision + 1])->save();
            Audit::record('academics.report_comment_bank_saved', $row, $before, $row->getAttributes());

            return ['id' => (int) $row->id, 'revision' => $row->revision];
        });
    }

    public function data(ReportTemplate $template): array
    {
        return ['id' => (int) $template->id, 'name' => $template->name, 'revision' => $template->revision, 'design' => $template->design];
    }

    /** Embed only bounded decoded raster bytes, never URLs, SVG or paths in HTML. */
    public function crest(): ?string
    {
        $path = app(CurrentSchool::class)->school()?->crest_path;
        if (! is_string($path) || str_contains($path, '..') || str_contains($path, ':') || str_starts_with($path, '/')) {
            return null;
        }
        $disk = Storage::disk('public');
        if (! $disk->exists($path) || $disk->size($path) > 1048576) {
            return null;
        }
        $bytes = $disk->get($path);
        $size = @getimagesizefromstring($bytes);
        if (! $size || ! in_array($size[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true) || $size[0] > 2048 || $size[1] > 2048) {
            return null;
        }
        $image = @imagecreatefromstring($bytes);
        if (! $image) {
            return null;
        }
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
