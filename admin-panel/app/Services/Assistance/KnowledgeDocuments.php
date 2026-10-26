<?php

declare(strict_types=1);

namespace App\Services\Assistance;

use App\Models\Assistance\KnowledgeDocument;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\Content\ContentMarkdown;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class KnowledgeDocuments
{
    public function publish(User $actor, array $input): array
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'document_key' => ['nullable', 'uuid'],
            'expected_revision' => ['required', 'integer', 'min:0'], 'title' => ['required', 'string', 'max:191'],
            'markdown' => ['required', 'string', 'max:16000'], 'source_reference' => ['required', 'string', 'max:2048'],
            'identifiers_reviewed' => ['required', 'accepted'], 'expires_on' => ['nullable', 'date_format:Y-m-d']])->validate();
        $access = app(AssistanceAccess::class);

        return app(AssistanceOperations::class)->once($actor, 'knowledge.publish', $data,
            fn () => $access->office($actor, true), function () use ($actor, $data): array {
                $old = isset($data['document_key']) ? KnowledgeDocument::where('document_key', $data['document_key'])->latest('revision')->firstOrFail() : null;
                abort_unless((int) ($old?->revision ?? 0) === (int) $data['expected_revision'], 409, 'The school document changed.');
                $today = app(AcademicAuthority::class)->today();
                abort_if(isset($data['expires_on']) && $data['expires_on'] < $today, 422, 'Choose a current document expiry.');
                $markdown = app(ContentMarkdown::class)->clean($data['markdown']);
                abort_if(trim($markdown) === '' || strlen($markdown) > 32000, 422);
                $row = KnowledgeDocument::create(['id' => (string) Str::uuid(), 'document_key' => $old?->document_key ?? (string) Str::uuid(),
                    'revision' => (int) ($old?->revision ?? 0) + 1, 'approved_by' => $actor->id,
                    'title' => trim(strip_tags($data['title'])), 'markdown' => $markdown, 'content_hash' => hash('sha256', $markdown),
                    'source_reference' => trim($data['source_reference']), 'identifiers_reviewed' => true,
                    'state' => 'published', 'expires_on' => $data['expires_on'] ?? null, 'published_at' => now()->utc()]);
                if ($old !== null) {
                    $old->update(['state' => 'withdrawn']);
                }

                return $this->metadata($row);
            });
    }

    public function metadata(KnowledgeDocument $row): array
    {
        return ['id' => $row->id, 'document_key' => $row->document_key, 'revision' => $row->revision,
            'title' => $row->title, 'source_reference' => $row->source_reference, 'expires_on' => $row->expires_on,
            'state' => $row->state];
    }

    public function familyList(User $actor, int $learner, int $page = 1): array
    {
        app(AssistanceAccess::class)->family($actor, $learner);
        $rows = KnowledgeDocument::where('state', 'published')->where('identifiers_reviewed', true)
            ->where(fn ($q) => $q->whereNull('expires_on')->orWhere('expires_on', '>=', app(AcademicAuthority::class)->today()))
            ->orderBy('title')->orderBy('id')->paginate(20, ['*'], 'page', max(1, $page));
        app(AssistanceAccess::class)->family($actor, $learner);

        return ['page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'items' => $rows->map(fn ($row) => $this->metadata($row))->all()];
    }

    public function familyRead(User $actor, int $learner, string $id): KnowledgeDocument
    {
        app(AssistanceAccess::class)->family($actor, $learner);
        $meta = KnowledgeDocument::select('id', 'state', 'expires_on', 'identifiers_reviewed')->findOrFail($id);
        abort_unless($meta->state === 'published' && $meta->identifiers_reviewed
            && ($meta->expires_on === null || $meta->expires_on >= app(AcademicAuthority::class)->today()), 404);

        return KnowledgeDocument::findOrFail($id);
    }

    public function withdraw(User $actor, string $id, array $input): array
    {
        return app(AssistanceOperations::class)->once($actor, 'knowledge.withdraw', ['id' => $id] + $input,
            function () use ($actor, $id): void {
                app(AssistanceAccess::class)->office($actor, true);
                KnowledgeDocument::findOrFail($id);
            },
            function () use ($id): array {
                $row = KnowledgeDocument::findOrFail($id);
                $row->update(['state' => 'withdrawn']);

                return $this->metadata($row);
            });
    }
}
