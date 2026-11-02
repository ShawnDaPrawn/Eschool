<?php

declare(strict_types=1);

namespace Tests\Unit\Platform;

use App\Services\Platform\Saas\Totp;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The authenticator app codes for platform staff two-factor sign-in (SA.2)
 * against the published test vectors. engineering/specs/platform/hq-two-factor.md.
 */
class TotpTest extends TestCase
{
    /** The RFC 6238 appendix B seed for SHA1: the ASCII string "12345678901234567890". */
    private const RFC_KEY = '12345678901234567890';

    /** @return array<string, array{int, string}> Unix time => 8-digit TOTP (RFC 6238 appendix B, SHA1) */
    public static function rfc6238(): array
    {
        return [
            '59' => [59, '94287082'],
            '1111111109' => [1111111109, '07081804'],
            '1111111111' => [1111111111, '14050471'],
            '1234567890' => [1234567890, '89005924'],
            '2000000000' => [2000000000, '69279037'],
            '20000000000' => [20000000000, '65353130'],
        ];
    }

    #[DataProvider('rfc6238')]
    public function test_totp_matches_the_rfc_6238_sha1_vectors(int $time, string $expected): void
    {
        $this->assertSame($expected, Totp::hotp(self::RFC_KEY, Totp::step($time), 8));
        // The same, through the Base32 secret an authenticator app is given, cut to 6 digits.
        $this->assertSame(substr($expected, 2), Totp::code(Totp::base32Encode(self::RFC_KEY), Totp::step($time)));
    }

    public function test_hotp_matches_the_rfc_4226_vectors(): void
    {
        $expected = ['755224', '287082', '359152', '969429', '338314', '254676', '287922', '162583', '399871', '520489'];
        foreach ($expected as $counter => $code) {
            $this->assertSame($code, Totp::hotp(self::RFC_KEY, $counter));
        }
    }

    public function test_base32_round_trips_and_matches_rfc_4648(): void
    {
        $this->assertSame('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', Totp::base32Encode(self::RFC_KEY));
        $this->assertSame('MZXW6YTBOI', Totp::base32Encode('foobar'));
        $this->assertSame('foobar', Totp::base32Decode('mzxw 6ytb oi======'));
        $secret = Totp::generateSecret();
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secret);
        $this->assertSame(20, strlen(Totp::base32Decode($secret)));
        $this->assertNotSame($secret, Totp::generateSecret());
    }

    public function test_verify_accepts_one_step_either_side_and_refuses_reuse(): void
    {
        $secret = Totp::base32Encode(self::RFC_KEY);
        $time = 1111111111;
        $step = Totp::step($time);

        $this->assertSame($step, Totp::verify($secret, Totp::code($secret, $step), $time));
        $this->assertSame($step - 1, Totp::verify($secret, Totp::code($secret, $step - 1), $time));
        $this->assertSame($step + 1, Totp::verify($secret, Totp::code($secret, $step + 1), $time));
        $this->assertNull(Totp::verify($secret, Totp::code($secret, $step - 2), $time));
        $this->assertNull(Totp::verify($secret, Totp::code($secret, $step + 2), $time));
        // A step already used, or any before it, never works again.
        $this->assertNull(Totp::verify($secret, Totp::code($secret, $step), $time, $step));
        $this->assertNull(Totp::verify($secret, Totp::code($secret, $step - 1), $time, $step));
        $this->assertSame($step + 1, Totp::verify($secret, Totp::code($secret, $step + 1), $time, $step));
        foreach (['', '12345', '1234567', 'abcdef', '14050471'] as $bad) {
            $this->assertNull(Totp::verify($secret, $bad, $time));
        }
    }

    public function test_the_setup_address_names_the_issuer_account_and_parameters(): void
    {
        $uri = Totp::uri('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 'thandi@navigateed.example', 'NavigateEd HQ');

        $this->assertSame('otpauth://totp/NavigateEd%20HQ:thandi%40navigateed.example?secret=GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ&issuer=NavigateEd%20HQ&algorithm=SHA1&digits=6&period=30', $uri);
    }
}
