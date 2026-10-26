<?php

declare(strict_types=1);

namespace Tests\Unit\Cards;

use App\Services\Cards\Crypto\AesCmac;
use App\Services\Cards\Crypto\SunVerifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class CardCryptographyTest extends TestCase
{
    /** RFC 4493 section 4 independent AES-CMAC known-answer vectors. */
    public static function cmacVectors(): array
    {
        return [
            ['', 'BB1D6929E95937287FA37D129B756746'],
            ['6BC1BEE22E409F96E93D7E117393172A', '070A16B46B4D4144F79BDD9DD04A287C'],
            ['6BC1BEE22E409F96E93D7E117393172AAE2D8A571E03AC9C9EB76FAC45AF8E5130C81C46A35CE411', 'DFA66747DE9AE63030CA32611497C827'],
            ['6BC1BEE22E409F96E93D7E117393172AAE2D8A571E03AC9C9EB76FAC45AF8E5130C81C46A35CE411E5FBC1191A0A52EFF69F2445DF4F9B17AD2B417BE66C3710', '51F0BEBF7E3B9D92FC49741779363CFE'],
        ];
    }

    #[DataProvider('cmacVectors')]
    public function test_cmac_matches_rfc4493(string $message, string $expected): void
    {
        $this->assertSame($expected, strtoupper(bin2hex((new AesCmac)->mac(hex2bin('2B7E151628AED2A6ABF7158809CF4F3C'), hex2bin($message)))));
    }

    public function test_sun_session_keys_match_nxp_an12196_revision2_table1(): void
    {
        $sun = new SunVerifier(new AesCmac);
        $key = hex2bin('5ACE7E50AB65D5D51FD5BF5A16B8205B');
        $uid = hex2bin('04C767F2066180');
        $counter = hex2bin('010000');
        $this->assertSame('66DA61797E23DECA5D8ECA13BBADF7A9', strtoupper(bin2hex($sun->sessionKey($key, $uid, $counter, true))));
        $this->assertSame('3A3E8110E05311F7A3FCF0D969BF2B48', strtoupper(bin2hex($sun->sessionKey($key, $uid, $counter))));
    }

    public function test_encrypted_picc_and_empty_input_mac_match_nxp_table4(): void
    {
        $result = (new SunVerifier(new AesCmac))->verify('EF963FF7828658A599F3041510671E88', '94EED9EE65337086', str_repeat("\0", 16), str_repeat("\0", 16), '04DE5F1EACC040');
        $this->assertSame(['uid' => '04DE5F1EACC040', 'counter' => 61], $result);
    }

    public function test_nonempty_ascii_mac_input_matches_nxp_table5(): void
    {
        $result = (new SunVerifier(new AesCmac))->verify('FD91EC264309878BE6345CBE53BADF40', 'ECC1E7F6C6C73BF6', str_repeat("\0", 16), str_repeat("\0", 16), '04958CAA5C5E80', 'CEE9A53E3E463EF1F459635736738962&cmac=');
        $this->assertSame(['uid' => '04958CAA5C5E80', 'counter' => 8], $result);
    }

    public static function invalidSun(): array
    {
        return [
            ['EF963FF7828658A599F3041510671E88', '94EED9EE65337080', '04DE5F1EACC040'],
            ['EF963FF7828658A599F3041510671E80', '94EED9EE65337086', '04DE5F1EACC040'],
            ['EF963FF7828658A599F3041510671E88', '94EED9EE65337086', '04DE5F1EACC041'],
            ['not-a-picc-proof', '94EED9EE65337086', '04DE5F1EACC040'],
        ];
    }

    #[DataProvider('invalidSun')]
    public function test_tampered_ciphertext_mac_and_uid_are_refused(string $picc, string $mac, string $uid): void
    {
        $this->expectException(UnexpectedValueException::class);
        (new SunVerifier(new AesCmac))->verify($picc, $mac, str_repeat("\0", 16), str_repeat("\0", 16), $uid);
    }
}
