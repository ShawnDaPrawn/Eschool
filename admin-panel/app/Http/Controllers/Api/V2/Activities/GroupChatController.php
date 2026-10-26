<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Activities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activities\GroupChatRequest;
use App\Services\Activities\Chat\GroupChatMessages;
use App\Services\Activities\Chat\GroupChatRules;
use App\Services\Activities\Chat\GroupChatThreads;
use Illuminate\Http\JsonResponse;

class GroupChatController extends Controller
{
    private function envelope(array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => trans('data_fetch_successfully'), 'data' => $data, 'code' => 200]);
    }

    public function policy(GroupChatRequest $request): JsonResponse
    {
        return $this->envelope(app(GroupChatRules::class)->read($request->user()));
    }

    public function policySave(GroupChatRequest $request): JsonResponse
    {
        return $this->envelope(app(GroupChatRules::class)->save($request->user(), $request->validated()));
    }

    public function index(GroupChatRequest $request, int $group): JsonResponse
    {
        return $this->envelope(app(GroupChatThreads::class)->listing($request->user(), $group, $request->persona(), $request->validated('before')));
    }

    public function receipt(GroupChatRequest $request, int $group, string $operation): JsonResponse
    {
        return $this->envelope(app(GroupChatThreads::class)->receipt($request->user(), $group, $request->persona(), $operation));
    }

    public function create(GroupChatRequest $request, int $group): JsonResponse
    {
        return $this->envelope(app(GroupChatThreads::class)->create($request->user(), $group, $request->validated()));
    }

    public function show(GroupChatRequest $request, int $group, string $thread): JsonResponse
    {
        $before = $request->validated('before_message_id');

        return $this->envelope(app(GroupChatMessages::class)->read($request->user(), $group, $thread, $request->persona(), $before === null ? null : (int) $before));
    }

    public function send(GroupChatRequest $request, int $group, string $thread): JsonResponse
    {
        return $this->envelope(app(GroupChatMessages::class)->send($request->user(), $group, $thread, $request->validated()));
    }

    public function state(GroupChatRequest $request, int $group, string $thread): JsonResponse
    {
        return $this->envelope(app(GroupChatThreads::class)->close($request->user(), $group, $thread, $request->validated()));
    }

    public function remove(GroupChatRequest $request, int $group, string $thread, int $message): JsonResponse
    {
        return $this->envelope(app(GroupChatMessages::class)->remove($request->user(), $group, $thread, $message, $request->validated()));
    }

    public function markRead(GroupChatRequest $request, int $group, string $thread): JsonResponse
    {
        return $this->envelope(app(GroupChatMessages::class)->markRead($request->user(), $group, $thread, $request->persona(), (int) $request->validated('last_message_id')));
    }
}
