<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\TextMessagingRequest;
use App\Services\Panel\Navigation;
use App\Services\Platform\Notifications\NotificationInbox;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Notifications\QuietHours;
use App\Services\Platform\Notifications\StaffPrivateNotices;
use App\Services\Platform\Notifications\TextNumbers;
use App\Services\Platform\Notifications\UrgentMessaging;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PersonalNotificationsController extends Controller
{
    public function index(Request $request, NotificationInbox $inbox): View
    {
        $data = $request->validate(['page' => ['sometimes', 'integer', 'min:1', 'max:100000']]);

        return view('personal-notifications.index', [
            'inbox' => $inbox->page($request->user(), (int) ($data['page'] ?? 1)),
            'privateAvailable' => app(StaffPrivateNotices::class)->available($request->user()),
        ]);
    }

    public function show(Request $request, NotificationInbox $inbox, int $notice): View
    {
        $item = $inbox->show($request->user(), $notice);

        return view('personal-notifications.show', ['notice' => $item, 'source' => $this->source($request, $item['type'])]);
    }

    public function privateInbox(Request $request, StaffPrivateNotices $notices): View
    {
        $data = $request->validate(['page' => ['sometimes', 'integer', 'min:1', 'max:100000']]);

        $inbox = $notices->inbox($request->user(), (int) ($data['page'] ?? 1));
        foreach ($inbox['items'] as &$item) {
            $item['source'] = $this->source($request, $item['feature']);
        }
        unset($item);

        return view('personal-notifications.private', ['inbox' => $inbox]);
    }

    public function markRead(Request $request, StaffPrivateNotices $notices, int $notice): JsonResponse
    {
        $result = $notices->markRead($request->user(), $notice);

        return response()->json(['error' => false, 'message' => __('Notice marked as read.'), 'data' => $result, 'redirect' => route('panel.inbox.private')]);
    }

    public function preferences(Request $request, NotificationRules $rules): View
    {
        $input = $request->validate(['q' => ['nullable', 'string', 'max:120']]);
        $query = trim((string) ($input['q'] ?? ''));
        $preferences = collect($rules->preferences($request->user()))
            ->filter(fn (array $row) => $query === '' || mb_stripos($row['label'], $query) !== false);

        return view('personal-notifications.preferences', ['groups' => $preferences->groupBy('module'), 'query' => $query]);
    }

    public function quiet(Request $request, QuietHours $quiet): View
    {
        return view('personal-notifications.quiet', ['quiet' => $quiet->get($request->user()), 'zones' => array_combine(timezone_identifiers_list(), timezone_identifiers_list())]);
    }

    public function texts(TextMessagingRequest $request, UrgentMessaging $messaging, TextNumbers $numbers): View
    {
        return view('personal-notifications.texts', ['number' => $numbers->status($request->user()), 'notices' => $messaging->choices($request->user())]);
    }

    /** Only registered, currently permitted panel destinations, never stored URLs. */
    private function source(Request $request, string $type): ?array
    {
        $feature = explode('.', $type)[0];
        $key = [
            'group_chat' => 'group-conversations', 'school_assistance' => 'school-support-office',
            'progress' => 'learner-notes', 'consultations' => 'consultations',
            'wellbeing' => 'wellbeing', 'discipline' => 'discipline',
            'recognition' => 'awards', 'fixtures' => 'activity-fixtures',
            'comms' => 'school-messages',
        ][$feature] ?? null;
        if ($key === null) {
            return null;
        }

        return collect(app(Navigation::class)->items($request->user()))->firstWhere('key', $key);
    }
}
