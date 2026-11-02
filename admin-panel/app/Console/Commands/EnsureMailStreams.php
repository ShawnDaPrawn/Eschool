<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Platform\Mail\MailStreams;
use Illuminate\Console\Command;

/**
 * Sets up the Amazon SES configuration sets of the platform and of every
 * school that is not archived (an account and a bulk stream each), and checks
 * once a day whether SES still lets each one send. Does nothing while
 * mail.streams.enabled is off. engineering/specs/platform/ses-streams.md.
 */
class EnsureMailStreams extends Command
{
    protected $signature = 'mail:ensure-streams {--school= : Only this school (its id)}';

    protected $description = 'Set up and check the SES configuration sets of each school\'s account and bulk mail streams';

    public function handle(MailStreams $streams): int
    {
        if (! MailStreams::enabled()) {
            $this->info('SES streams are switched off.');

            return self::SUCCESS;
        }
        if (MailStreams::eventTopicArn() === null) {
            $this->error('No feedback SNS topic: set SES_SNS_TOPIC_ARNS (and MAIL_SES_STREAMS_TOPIC_ARN, if set, must be one of them).');

            return self::FAILURE;
        }
        if ((string) config('services.ses.options.ConfigurationSetName', '') !== '') {
            $this->warn('AWS_SES_CONFIGURATION_SET is set: unset it, so each mail goes through its own stream\'s set.');
        }
        $school = $this->option('school');
        if ($school !== null && preg_match('/^[1-9][0-9]*$/', (string) $school) !== 1) {
            $this->error('--school must be a school id.');

            return self::FAILURE;
        }
        $this->info('Set up or checked '.$streams->poll($school === null ? null : (int) $school).' configuration sets.');

        return self::SUCCESS;
    }
}
