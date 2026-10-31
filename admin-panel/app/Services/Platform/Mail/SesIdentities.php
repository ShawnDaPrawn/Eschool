<?php

declare(strict_types=1);

namespace App\Services\Platform\Mail;

use Aws\Result;
use Aws\SesV2\Exception\SesV2Exception;
use Aws\SesV2\SesV2Client;
use Closure;
use RuntimeException;

/**
 * Amazon SES email identities for school sending domains (Action Plan 2.2),
 * through the SES v2 API: create a domain identity with Easy DKIM, set its
 * custom MAIL FROM domain, read whether it is verified, and delete it.
 *
 * Bound in the container (AppServiceProvider), so tests give it an SesV2Client
 * with a mock handler: no test calls AWS. The client is built from
 * services.ses on first use and never while mail.sending_domains.enabled is
 * off. engineering/specs/platform/sending-domains.md.
 */
class SesIdentities
{
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
     * Creates the domain identity with Easy DKIM.
     *
     * @return array{verified: bool, dkim_status: string|null, tokens: list<string>, mail_from_status: string|null, error: string|null}
     *
     * @throws SesIdentityExists when SES already has an identity of that name
     */
    public function create(string $domain, int $schoolId): array
    {
        try {
            $result = $this->client()->createEmailIdentity([
                'EmailIdentity' => $domain,
                'DkimSigningAttributes' => ['NextSigningKeyLength' => 'RSA_2048_BIT'],
                'Tags' => [['Key' => 'navigateed:school', 'Value' => (string) $schoolId]],
            ]);
        } catch (SesV2Exception $e) {
            if ($e->getAwsErrorCode() === 'AlreadyExistsException') {
                throw new SesIdentityExists($domain, previous: $e);
            }

            throw $e;
        }

        return $this->summary($result);
    }

    /** Sets the identity's custom MAIL FROM domain; SES falls back to its own while the MX record is missing. */
    public function setMailFrom(string $domain, string $mailFromDomain): void
    {
        $this->client()->putEmailIdentityMailFromAttributes([
            'EmailIdentity' => $domain,
            'MailFromDomain' => $mailFromDomain,
            'BehaviorOnMxFailure' => 'USE_DEFAULT_VALUE',
        ]);
    }

    /**
     * What SES says about the identity now, or null when SES has none.
     *
     * @return array{verified: bool, dkim_status: string|null, tokens: list<string>, mail_from_status: string|null, error: string|null}|null
     */
    public function status(string $domain): ?array
    {
        try {
            return $this->summary($this->client()->getEmailIdentity(['EmailIdentity' => $domain]));
        } catch (SesV2Exception $e) {
            if ($e->getAwsErrorCode() === 'NotFoundException') {
                return null;
            }

            throw $e;
        }
    }

    /** Deletes the identity; one SES no longer has counts as deleted. */
    public function delete(string $domain): void
    {
        try {
            $this->client()->deleteEmailIdentity(['EmailIdentity' => $domain]);
        } catch (SesV2Exception $e) {
            if ($e->getAwsErrorCode() !== 'NotFoundException') {
                throw $e;
            }
        }
    }

    /**
     * @return array{verified: bool, dkim_status: string|null, tokens: list<string>, mail_from_status: string|null, error: string|null}
     */
    private function summary(Result $result): array
    {
        $dkim = (array) ($result['DkimAttributes'] ?? []);
        $mailFrom = (array) ($result['MailFromAttributes'] ?? []);
        $tokens = array_values(array_filter(array_map('strval', (array) ($dkim['Tokens'] ?? [])), fn (string $token) => preg_match('/^[a-z0-9]{1,63}$/', $token) === 1));

        return [
            'verified' => (bool) ($result['VerifiedForSendingStatus'] ?? false),
            'dkim_status' => isset($dkim['Status']) ? (string) $dkim['Status'] : null,
            'tokens' => $tokens,
            'mail_from_status' => isset($mailFrom['MailFromDomainStatus']) ? (string) $mailFrom['MailFromDomainStatus'] : null,
            'error' => isset($result['VerificationInfo']['ErrorType']) ? (string) $result['VerificationInfo']['ErrorType'] : null,
        ];
    }

    private function client(): SesV2Client
    {
        if (! config('mail.sending_domains.enabled')) {
            throw new RuntimeException('School sending domains are switched off (MAIL_SENDING_DOMAINS).');
        }

        return $this->client ??= ($this->factory)();
    }
}
