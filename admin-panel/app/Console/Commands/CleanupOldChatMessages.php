<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ChatMessageCleanupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupOldChatMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-old-chat-messages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete chat messages (and their files) older than the configured retention period';

    /**
     * Execute the console command.
     */
    public function handle(ChatMessageCleanupService $service): int
    {
        $count = $service->deleteOlderThanConfiguredDays();

        Log::info('Cleaned up old chat messages', ['deleted' => $count]);
        $this->info("Deleted {$count} old chat message(s).");

        return self::SUCCESS;
    }
}
