<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Platform\Subscriptions\SubscriptionPrompts;
use Illuminate\Console\Command;

/**
 * Learning plan prompts to guardians (Action Plan 3.20). Off by default: a
 * school sends them only after switching subscription.prompt on in its
 * notification rules. Prints counts only.
 */
class PromptLearnerSubscriptions extends Command
{
    protected $signature = 'subscriptions:prompt';

    protected $description = 'Tell guardians about learning plans, where the school has switched the prompts on';

    public function handle(SubscriptionPrompts $prompts): int
    {
        $result = $prompts->run();
        $this->info(sprintf('Learning plan prompts: %d schools checked, %d guardians told about %d children.', $result['schools'], $result['guardians'], $result['prompts']));

        return self::SUCCESS;
    }
}
