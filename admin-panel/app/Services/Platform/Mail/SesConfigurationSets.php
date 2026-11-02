<?php

declare(strict_types=1);

namespace App\Services\Platform\Mail;

use Aws\SesV2\Exception\SesV2Exception;
use Aws\SesV2\SesV2Client;
use Closure;
use RuntimeException;

/**
 * Amazon SES configuration sets for the account and bulk streams (Action
 * Plan 2.3), through the SES v2 API: create or update a set with reputation
 * metrics and Virtual Deliverability Manager options, point its delivery,
 * bounce, complaint and reject events at the feedback SNS topic, and read
 * whether SES lets it send.
 *
 * Bound in the container (AppServiceProvider), so tests give it an
 * SesV2Client with a fake handler: no test calls AWS. The client is built
 * from services.ses on first use and never while mail.streams.enabled is off.
 * engineering/specs/platform/ses-streams.md.
 */
class SesConfigurationSets
{
    /** The event destination every set has, sending to the feedback topic. */
    public const EVENT_DESTINATION = 'navigateed-feedback';

    /** What POST /webhook/mail/ses records; opens and clicks stay in VDM. */
    public const EVENT_TYPES = ['DELIVERY', 'BOUNCE', 'COMPLAINT', 'REJECT'];

    private ?SesV2Client $client = null;

    /** @param Closure(): SesV2Client $factory */
    public function __construct(private readonly Closure $factory) {}

    public static function fromConfig(): self
    {
        return new self(function (): SesV2Client {
            $key = (string) config('services.ses.key');
            $secret = (string) config('services.ses.secret');
            $options = ['version' => '2019-09-27', 'region' => (string) config('services.ses.region', 'af-south-1')];
            if ($key !== '' && $secret !== '') {
                $options['credentials'] = ['key' => $key, 'secret' => $secret];
            }

            return new SesV2Client($options);
        });
    }

    /**
     * Creates the set, or brings an existing one's reputation and VDM options
     * up to date. An existing set's sending status is never changed: a set
     * someone paused stays paused.
     *
     * @param  array<string, string>  $tags
     * @return bool whether the set was created now
     */
    public function ensure(string $name, array $tags, bool $engagementTracking): bool
    {
        $vdm = [
            'DashboardOptions' => ['EngagementMetrics' => $engagementTracking ? 'ENABLED' : 'DISABLED'],
            'GuardianOptions' => ['OptimizedSharedDelivery' => 'ENABLED'],
        ];
        try {
            $this->client()->createConfigurationSet([
                'ConfigurationSetName' => $name,
                'ReputationOptions' => ['ReputationMetricsEnabled' => true],
                'SendingOptions' => ['SendingEnabled' => true],
                'VdmOptions' => $vdm,
                'Tags' => array_map(fn (string $key, string $value) => ['Key' => $key, 'Value' => $value], array_keys($tags), array_values($tags)),
            ]);

            return true;
        } catch (SesV2Exception $e) {
            if ($e->getAwsErrorCode() !== 'AlreadyExistsException') {
                throw $e;
            }
        }
        $this->client()->putConfigurationSetReputationOptions(['ConfigurationSetName' => $name, 'ReputationMetricsEnabled' => true]);
        $this->client()->putConfigurationSetVdmOptions(['ConfigurationSetName' => $name, 'VdmOptions' => $vdm]);

        return false;
    }

    /** Sends the set's delivery, bounce, complaint and reject events to the SNS topic. */
    public function publishEvents(string $name, string $topicArn): void
    {
        $destination = ['Enabled' => true, 'MatchingEventTypes' => self::EVENT_TYPES, 'SnsDestination' => ['TopicArn' => $topicArn]];
        try {
            $this->client()->createConfigurationSetEventDestination(['ConfigurationSetName' => $name, 'EventDestinationName' => self::EVENT_DESTINATION, 'EventDestination' => $destination]);
        } catch (SesV2Exception $e) {
            if ($e->getAwsErrorCode() !== 'AlreadyExistsException') {
                throw $e;
            }
            $this->client()->updateConfigurationSetEventDestination(['ConfigurationSetName' => $name, 'EventDestinationName' => self::EVENT_DESTINATION, 'EventDestination' => $destination]);
        }
    }

    /**
     * What SES says about the set now, or null when SES has none.
     *
     * @return array{sending_enabled: bool, engagement_tracking: bool, optimized_delivery: bool}|null
     */
    public function status(string $name): ?array
    {
        try {
            $result = $this->client()->getConfigurationSet(['ConfigurationSetName' => $name]);
        } catch (SesV2Exception $e) {
            if ($e->getAwsErrorCode() === 'NotFoundException') {
                return null;
            }

            throw $e;
        }
        $vdm = (array) ($result['VdmOptions'] ?? []);

        return [
            // Sending is on unless SES says the set is paused.
            'sending_enabled' => (bool) ($result['SendingOptions']['SendingEnabled'] ?? true),
            'engagement_tracking' => ($vdm['DashboardOptions']['EngagementMetrics'] ?? null) === 'ENABLED',
            'optimized_delivery' => ($vdm['GuardianOptions']['OptimizedSharedDelivery'] ?? null) === 'ENABLED',
        ];
    }

    private function client(): SesV2Client
    {
        if (! config('mail.streams.enabled')) {
            throw new RuntimeException('SES streams are switched off (MAIL_SES_STREAMS).');
        }

        return $this->client ??= ($this->factory)();
    }
}
