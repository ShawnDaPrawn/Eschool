<?php

declare(strict_types=1);

namespace App\Jobs\Communication;

use App\Services\Communication\MessageDelivery;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 110;

    public function __construct(public int $schoolId, public int $messageId)
    {
        $this->onConnection('database')->onQueue('default')->beforeCommit();
    }

    public function backoff(): array
    {
        return [10, 30, 120];
    }

    public function handle(CurrentSchool $schools, MessageDelivery $delivery): void
    {
        $schools->run($this->schoolId, fn () => $delivery->process($this->messageId));
    }
}
