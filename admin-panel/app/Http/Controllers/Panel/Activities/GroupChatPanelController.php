<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Activities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activities\GroupChatPanelRequest;
use App\Models\Activities\ActivityGroup;
use App\Services\Activities\Chat\GroupChatAccess;
use App\Services\Activities\Chat\GroupChatMessages;
use App\Services\Activities\Chat\GroupChatRules;
use App\Services\Activities\Chat\GroupChatThreads;
use App\Services\Panel\SessionYearContext;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class GroupChatPanelController extends Controller
{
    public function index(GroupChatPanelRequest $request): Response
    {
        return $this->render($request);
    }

    public function render(GroupChatPanelRequest $request, array $messages = [], int $status = 200): Response
    {
        $actor = $request->user();
        $access = app(GroupChatAccess::class);
        $ids = $access->groupIds($actor, 'staff');
        $groups = ActivityGroup::whereIn('id', $ids)->orderBy('name')->paginate(25);
        $group = $request->route('group') === null ? null : $access->group($actor, (int) $request->route('group'), 'staff');
        $thread = $request->route('thread');
        $listing = $group === null ? null : app(GroupChatThreads::class)->listing($actor, (int) $group->id, 'staff', $request->query('before'));
        $conversation = $thread === null ? null : app(GroupChatMessages::class)->read($actor, (int) $group->id, (string) $thread, 'staff', $request->filled('before_message_id') ? (int) $request->input('before_message_id') : null);
        if ($conversation !== null && $conversation['items'] !== []) {
            app(GroupChatMessages::class)->markRead($actor, (int) $group->id, (string) $thread, 'staff', max(array_column($conversation['items'], 'id')));
        }
        $canWrite = $group !== null && $access->writable($actor, (int) $group->id, 'staff') && ! app(SessionYearContext::class)->isReadOnly();
        $draft = [];
        // Only validation failures retain bounded author input in this private
        // response. It is never flashed into the session or copied into a URL.
        if (! $request->isMethodSafe() && $status === 422) {
            foreach (['title' => 191, 'body' => app(GroupChatRules::class)->maxCharacters(), 'reason' => 2000, 'language' => 20] as $key => $limit) {
                if (is_string($request->input($key))) {
                    $draft[$key] = mb_substr($request->input($key), 0, $limit);
                }
            }
        }
        $accountId = (int) $actor->id;
        $schoolId = app(CurrentSchool::class)->id();
        $operationId = $status === 422 && Str::isUuid((string) $request->input('operation_id')) ? $request->input('operation_id') : (string) Str::uuid();

        return response()->view('activities.chat', compact('groups', 'group', 'listing', 'conversation', 'canWrite', 'draft', 'messages', 'accountId', 'schoolId', 'operationId'), $status);
    }

    public function lease(GroupChatPanelRequest $request): JsonResponse
    {
        // Scope middleware rechecks the exact page actor, school and allocation
        // before this metadata-only heartbeat, including a selected thread.
        return response()->json(['available' => true]);
    }

    public function write(GroupChatPanelRequest $request): Response|RedirectResponse
    {
        $group = (int) $request->route('group');
        $thread = (string) $request->route('thread');
        $input = $request->validated() + ['persona' => 'staff'];
        try {
            $result = match ($request->route('operation')) {
                'create' => app(GroupChatThreads::class)->create($request->user(), $group, $input),
                'send' => app(GroupChatMessages::class)->send($request->user(), $group, $thread, $input),
                'state' => app(GroupChatThreads::class)->close($request->user(), $group, $thread, $input),
                'remove' => app(GroupChatMessages::class)->remove($request->user(), $group, $thread, (int) $request->route('message'), $input),
                default => abort(404),
            };
        } catch (ValidationException $error) {
            return $this->render($request, $error->validator->errors()->all(), 422);
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [409, 422, 423], true)) {
                throw $error;
            }

            return $this->render($request, [__('Refresh this conversation before trying again.')], $error->getStatusCode());
        }

        return redirect()->route('group-chat-panel.show', ['group' => $group, 'thread' => $result['thread_id']]);
    }
}
