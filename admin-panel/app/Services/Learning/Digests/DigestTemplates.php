<?php

declare(strict_types=1);

namespace App\Services\Learning\Digests;

use App\Models\Learning\DigestLanguageTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DigestTemplates
{
    public function validateText(array $data): array
    {
        $value = Validator::make($data, [
            'language' => ['required', Rule::in(config('weekly_digests.languages'))],
            'title' => ['required', 'string', 'max:120'], 'body' => ['required', 'string', 'max:1200'],
            'notice_prompt' => ['required', 'string', 'max:120'],
            'actions' => ['required', 'array:practice,marks,attendance,calendar'],
            'actions.practice' => ['required', 'string', 'max:160'], 'actions.marks' => ['required', 'string', 'max:160'],
            'actions.attendance' => ['required', 'string', 'max:160'], 'actions.calendar' => ['required', 'string', 'max:160'],
        ])->validate();
        foreach ([$value['title'], $value['body'], $value['notice_prompt'], ...array_values($value['actions'])] as $text) {
            abort_unless(strip_tags($text) === $text && ! preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $text), 422, 'Use plain, readable template text.');
            preg_match_all('/\{([^{}]+)\}/u', $text, $tokens);
            abort_unless(array_diff($tokens[1], config('weekly_digests.tokens')) === [], 422, 'A template placeholder is not supported.');
            $remaining = preg_replace('/\{[^{}]+\}/u', '', $text);
            abort_if(str_contains($remaining, '{') || str_contains($remaining, '}'), 422, 'Use complete template placeholders.');
        }
        abort_if(str_contains($value['notice_prompt'], '{') || collect($value['actions'])->contains(fn ($text) => str_contains($text, '{')), 422, 'Notification and action instructions must contain no private record placeholders.');

        return $value;
    }

    public function create(User $actor, array $data): array
    {
        $authorize = fn () => app(DigestAuthority::class)->manager($actor, true);
        $authorize();
        $value = $this->validateText($data);

        return app(DigestOperations::class)->run($actor, 'template.create', $data, $authorize, function () use ($actor, $value): array {
            $version = (int) DigestLanguageTemplate::where('language', $value['language'])->max('version') + 1;
            $template = DigestLanguageTemplate::create(['language' => $value['language'], 'version' => $version,
                'title_template' => $value['title'], 'body_template' => $value['body'], 'action_templates' => $value['actions'], 'notice_prompt' => $value['notice_prompt'],
                'content_hash' => hash('sha256', json_encode($value, JSON_THROW_ON_ERROR)), 'created_by' => $actor->id]);

            return ['id' => (int) $template->id, 'revision' => (int) $template->revision];
        });
    }

    public function review(User $actor, int $id, array $data): array
    {
        $authorize = function () use ($actor, $id): void {
            app(DigestAuthority::class)->manager($actor, true);
            DigestLanguageTemplate::select(['id'])->findOrFail($id);
        };
        $authorize();
        Validator::make($data, ['revision' => ['required', 'integer', 'min:1'], 'decision' => ['required', 'in:approve,revoke']])->validate();

        return app(DigestOperations::class)->run($actor, 'template.review:'.$id, $data, $authorize, function () use ($actor, $id, $data): array {
            $template = DigestLanguageTemplate::lockForUpdate()->findOrFail($id);
            abort_unless((int) $template->revision === (int) $data['revision'], 409, 'The template review changed. Refresh before reviewing it.');
            $template->forceFill($data['decision'] === 'approve' ? ['approved_by' => $actor->id, 'approved_at' => now(), 'revoked_at' => null] : ['revoked_at' => now()]);
            $template->revision++;
            $template->save();

            return ['id' => $id, 'revision' => (int) $template->revision];
        });
    }

    public function approved(string $language): ?DigestLanguageTemplate
    {
        return DigestLanguageTemplate::where('language', $language)->whereNotNull('approved_at')->whereNull('revoked_at')->orderByDesc('version')->first();
    }

    public function render(DigestLanguageTemplate $template, array $values): array
    {
        abort_unless($template->approved_at !== null && $template->revoked_at === null, 409);
        $replacements = [];
        foreach (config('weekly_digests.tokens') as $token) {
            abort_unless(array_key_exists($token, $values) && (is_string($values[$token]) || is_int($values[$token])), 422);
            $replacements['{'.$token.'}'] = (string) $values[$token];
        }
        $title = strtr($template->title_template, $replacements);
        $body = strtr($template->body_template, $replacements);
        abort_if(mb_strlen($title) > 220 || mb_strlen($body) > 2000, 422, 'Keep a weekly summary short enough to read in one minute.');

        return ['title' => $title, 'summary' => $body];
    }
}
