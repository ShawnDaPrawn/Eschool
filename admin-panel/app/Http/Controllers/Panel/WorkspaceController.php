<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Services\Panel\Navigation;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class WorkspaceController extends Controller
{
    public function __invoke(Request $request, Navigation $navigation, string $section, ?string $group = null): View
    {
        abort_unless(array_key_exists($section, config('navigation.sections', [])), 404);
        $workspace = collect($navigation->sections($request->user()))->firstWhere('key', $section);
        abort_unless($workspace, 403);
        $selectedGroup = $group === null ? null : collect($workspace['groups'])->firstWhere('key', $group);
        abort_if($group !== null && $selectedGroup === null, 403);
        $mode = $request->query('view', $section === 'settings' ? 'configuration' : 'operations');
        abort_unless(in_array($mode, ['operations', 'configuration'], true), 404);
        $query = $request->query('q', '');
        abort_unless(is_string($query), 422);
        $search = mb_substr(trim($query), 0, 100);
        $groups = $selectedGroup ? [$selectedGroup] : $workspace['groups'];
        $hasConfiguration = collect($groups)->flatMap(fn (array $entry) => $entry['items'])->contains('configuration', true);
        $hasOperations = collect($groups)->flatMap(fn (array $entry) => $entry['items'])->contains('configuration', false);
        $displayGroups = [];
        foreach ($groups as $entry) {
            $entry['items'] = array_values(array_filter($entry['items'], function (array $item) use ($mode, $search): bool {
                if ($item['configuration'] !== ($mode === 'configuration')) {
                    return false;
                }

                return $search === '' || str_contains(mb_strtolower($item['label'].' '.implode(' ', $item['keywords'])), mb_strtolower($search));
            }));
            if ($entry['items'] !== []) {
                $displayGroups[] = $entry;
            }
        }

        return view('panel.workspace', compact('workspace', 'selectedGroup', 'displayGroups', 'mode', 'search', 'hasConfiguration', 'hasOperations'));
    }
}
