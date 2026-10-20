<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChatMessage;
use Illuminate\Support\Facades\Storage;

class ChatMessageCleanupService
{
    /**
     * Delete chat messages older than the number of days configured in
     * the `automatically_messages_removed_days` setting (defaults to 30).
     *
     * Shared by the legacy `delete-chat-message/cron-job` endpoint and the
     * scheduled `app:cleanup-old-chat-messages` command.
     */
    public function deleteOlderThanConfiguredDays(): int
    {
        $days = (int) (getSettings()['automatically_messages_removed_days'] ?? 30);

        $cutoff = now()->subDays($days);

        $messages = ChatMessage::with('file')->whereDate('date', '<', $cutoff)->get();

        return $this->deleteMessagesWithFiles($messages);
    }

    /**
     * Delete chat messages whose date falls within the inclusive range.
     * Used by the manual date-range delete on the Chat Settings page.
     */
    public function deleteBetweenDates(string $fromDate, string $toDate): int
    {
        $from = date('Y-m-d', strtotime($fromDate));
        $to = date('Y-m-d', strtotime($toDate));

        $messages = ChatMessage::with('file')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->get();

        return $this->deleteMessagesWithFiles($messages);
    }

    /**
     * Delete the given chat messages together with their stored files.
     * Returns the number of messages deleted.
     */
    public function deleteMessagesWithFiles(iterable $messages): int
    {
        $count = 0;

        foreach ($messages as $message) {
            if ($message->file) {
                foreach ($message->file as $file) {
                    if ($file) {
                        if (Storage::disk('public')->exists($file->file_name)) {
                            Storage::disk('public')->delete($file->file_name);
                        }
                        $file->delete();
                    }
                }
            }
            $message->delete();
            $count++;
        }

        return $count;
    }
}
