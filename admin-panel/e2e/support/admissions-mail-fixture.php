<?php

declare(strict_types=1);

use App\Jobs\Platform\SendMail;
use App\Mail\Admissions\ResumeApplicationMail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

// Read only synthetic encrypted Laravel jobs in the isolated test database.
// Never expose this helper as an HTTP route or run it against a real school.
abort_unless(app()->environment(['local', 'testing']) && str_ends_with((string) config('database.connections.'.config('database.default').'.database'), '_test'), 403);
foreach (DB::table('jobs')->orderByDesc('id')->limit(100)->get(['payload']) as $row) {
    $payload = json_decode($row->payload, true, 512, JSON_THROW_ON_ERROR);
    if (($payload['data']['commandName'] ?? null) !== SendMail::class) {
        continue;
    }
    $job = unserialize(Crypt::decrypt($payload['data']['command']), ['allowed_classes' => [SendMail::class, ResumeApplicationMail::class]]);
    if ($job instanceof SendMail && $job->mailable instanceof ResumeApplicationMail) {
        return ['url' => $job->mailable->resumeUrl];
    }
}
throw new RuntimeException('Expected a synthetic queued application resume email.');
