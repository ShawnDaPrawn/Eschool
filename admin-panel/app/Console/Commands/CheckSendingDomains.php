<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Platform\Mail\SendingDomains;
use Illuminate\Console\Command;

/**
 * Asks Amazon SES about each school's sending domain that is waiting for its
 * DNS records (every ten minutes) or verified (once a day), and records the
 * answer. Does nothing while mail.sending_domains.enabled is off.
 * engineering/specs/platform/sending-domains.md.
 */
class CheckSendingDomains extends Command
{
    protected $signature = 'mail:check-sending-domains';

    protected $description = 'Check school sending domains with Amazon SES and mark them verified';

    public function handle(SendingDomains $domains): int
    {
        if (! SendingDomains::enabled()) {
            $this->info('School sending domains are switched off.');

            return self::SUCCESS;
        }
        $this->info('Checked '.$domains->poll().' sending domains.');

        return self::SUCCESS;
    }
}
