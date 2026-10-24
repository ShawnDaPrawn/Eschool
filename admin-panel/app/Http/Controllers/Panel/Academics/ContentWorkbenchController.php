<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\ContentPanelRequest;
use App\Models\Academics\CurriculumTopic;
use App\Models\Academics\TeachingGroup;
use App\Policies\Academics\ContentPolicy;
use App\Services\Academics\Content\ContentFlow;
use App\Services\Academics\Content\ContentMarkdown;
use App\Services\Academics\Content\ContentQueries;
use App\Services\Academics\Content\ContentSettings;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ContentWorkbenchController extends Controller
{
    public function index(ContentPanelRequest $r): Response
    {
        $queries = app(ContentQueries::class);
        $selectedItem = $r->validated('item_id') === null ? null : app(ContentPolicy::class)->staff($r->user(), (int) $r->validated('item_id'));
        $year = $selectedItem?->session_year_id ?? $r->validated('session_year_id');
        $config = $queries->configuration($r->user(), $year === null ? null : (int) $year);
        $items = $queries->staff($r->user(), $config['session_year_id'], (int) $r->validated('page', 1));
        $item = $r->validated('item_id') === null ? null : $queries->show($r->user(), (int) $r->validated('item_id'), (int) $r->validated('history_page', 1));
        $version = $item === null ? null : ($item['draft'] ?? $item['published']);
        if ($r->validated('version_id') !== null) {
            $v = app(ContentPolicy::class)->version($r->user(), (int) $r->validated('version_id'));
            abort_unless($item !== null && (int) $v->content_item_id === $item['id'], 404);
            $version = $queries->versionData($v);
        }
        $group = $r->validated('group_id') === null ? ($config['groups'][0] ?? null) : collect($config['groups'])->firstWhere('id', (int) $r->validated('group_id'));
        abort_if($r->validated('group_id') !== null && $group === null, 404);
        $selection = $version['selection'] ?? ($group === null ? null : ['subject_id' => $group['subject_id'], 'grade_level_id' => $group['grade_level_id'], 'session_year_id' => $config['session_year_id'], 'title' => '', 'summary' => '', 'group_ids' => [$group['id']], 'topic_ids' => []]);
        $groups = $selection === null ? [] : array_values(array_filter($config['groups'], fn ($g) => $g['subject_id'] === $selection['subject_id'] && $g['grade_level_id'] === $selection['grade_level_id']));
        $topics = $selection === null ? [] : array_values(array_filter($config['topics'], fn ($t) => $t['subject_id'] === $selection['subject_id'] && $t['grade_level_id'] === $selection['grade_level_id']));
        $preview = $r->boolean('publication_preview') && $version !== null ? app(ContentFlow::class)->preview($r->user(), $version['id']) : null;

        return response()->view('academics.content-workbench', ['configuration' => $config, 'items' => $items, 'item' => $item, 'version' => $version, 'selection' => $selection, 'group' => $group, 'groups' => $groups, 'topics' => $topics, 'preview' => $preview, 'rendered' => is_string($version['markdown'] ?? null) && $version['markdown'] !== '' ? app(ContentMarkdown::class)->html($version['markdown']) : null]);
    }

    private function done(array $receipt, string $message): Response
    {
        return redirect()->route('academic-content.index', ['item_id' => $receipt['id']])->with('success', __($message));
    }

    public function create(ContentPanelRequest $r): Response
    {
        return $this->done(app(ContentFlow::class)->create($r->user(), $r->selectionInput(), $r->file('source')), 'Content queued. Refresh its status shortly.');
    }

    public function upload(ContentPanelRequest $r, int $item): Response
    {
        return $this->done(app(ContentFlow::class)->upload($r->user(), $item, $r->selectionInput(), $r->file('source')), 'Replacement queued. Published material stays available until review.');
    }

    public function save(ContentPanelRequest $r, int $version): Response
    {
        try {
            $saved = app(ContentFlow::class)->save($r->user(), $version, $r->selectionInput());
        } catch (HttpExceptionInterface $e) {
            if ($e->getStatusCode() !== 409) {
                throw $e;
            }
            $v = app(ContentPolicy::class)->version($r->user(), $version);
            $item = app(ContentQueries::class)->show($r->user(), (int) $v->content_item_id);

            return response()->view('academics.content-conflict', ['item' => $item, 'submitted' => $r->validated('markdown'), 'submittedTitle' => $r->validated('selection.title'), 'submittedGroups' => TeachingGroup::whereIn('id', $r->validated('selection.group_ids'))->pluck('name')->all(), 'submittedTopics' => CurriculumTopic::whereIn('id', $r->validated('selection.topic_ids'))->pluck('name')->all()], 409);
        }

        return $this->done($saved, 'Content draft saved');
    }

    public function retry(ContentPanelRequest $r, int $version): Response
    {
        return $this->done(app(ContentFlow::class)->retry($r->user(), $version, $r->validated()), 'Conversion queued for retry');
    }

    public function publish(ContentPanelRequest $r, int $version): Response
    {
        return $this->done(app(ContentFlow::class)->publish($r->user(), $version, $r->validated()), 'Reviewed content published');
    }

    public function archive(ContentPanelRequest $r, int $item): Response
    {
        return $this->done(app(ContentFlow::class)->archive($r->user(), $item, $r->validated()), 'Content withdrawn from learner libraries');
    }

    public function settings(ContentPanelRequest $r): Response
    {
        app(ContentSettings::class)->save($r->user(), $r->validated());

        return redirect()->route('academic-content.index')->with('success', __('Content controls saved'));
    }
}
