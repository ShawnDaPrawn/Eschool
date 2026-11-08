<?php

declare(strict_types=1);

namespace Tests\Unit\Learning\Marking;

use App\Services\Learning\Marking\MarkingEngine;
use App\Services\Learning\Marking\Profiles\GenericProfile;
use App\Services\Learning\Marking\Profiles\MathematicsProfile;
use App\Services\Learning\Marking\Profiles\PhysicalSciencesProfile;
use App\Services\Learning\Marking\SubjectProfile;
use PhpToken;
use PHPUnit\Framework\TestCase;

/**
 * A passing accuracy run counts only for the engine and profile versions it
 * measured (Action Plan 3.18), so a change in how the engine or a profile
 * marks must come with a new version. This pins each version to its code,
 * ignoring comments and whitespace: when it fails, bump
 * MarkingEngine::VERSION or the profile's version() (learner marking in the
 * subject then stays off until a new run passes and an approver switches it
 * on), and record the new fingerprint here.
 * engineering/specs/learning/marking-accuracy-gate.md.
 */
class MarkingVersionsTest extends TestCase
{
    /** The engine: every marking file except the profiles, the paper flow, the profile chooser and the accuracy gate. */
    private const ENGINE = [
        'rules-v1' => 'b51cdf0b7d31efae09258ba3d939eaf4032d0fb8eb919ac44040b468d85f3a2b',
    ];

    private const PROFILES = [
        'mathematics-v1' => 'f284d237867e9101aa8886e4857684793892df7874478038b38f8a569125bccc',
        'physical_sciences-v1' => '9ede98a8058b48ee975f8978c529ab10f3203eb056d7e09f3e5572cf4b788d07',
        'generic-v1' => '39804662286e4ee10d8699c8b3f6bd0feda6d70a86de63511b3d97b277429c14',
    ];

    private const NOT_ENGINE = ['PaperMarking.php', 'SubjectProfiles.php'];

    public function test_the_engine_version_matches_its_code(): void
    {
        $this->assertArrayHasKey(MarkingEngine::VERSION, self::ENGINE, 'A new engine version: record its fingerprint '.$this->engine().' here.');
        $this->assertSame(self::ENGINE[MarkingEngine::VERSION], $this->engine(),
            'The engine\'s code changed: bump MarkingEngine::VERSION (a new passing accuracy run is then needed) and record the new fingerprint.');
    }

    public function test_each_profile_version_matches_its_code(): void
    {
        foreach ([new MathematicsProfile, new PhysicalSciencesProfile, new GenericProfile] as $profile) {
            $fingerprint = $this->profile($profile);
            $this->assertArrayHasKey($profile->version(), self::PROFILES, 'A new profile version: record its fingerprint '.$fingerprint.' here.');
            $this->assertSame(self::PROFILES[$profile->version()], $fingerprint,
                $profile->key().': the profile\'s code changed: bump its version() (a new passing accuracy run is then needed) and record the new fingerprint.');
        }
    }

    private function engine(): string
    {
        $base = dirname(__DIR__, 4).'/app/Services/Learning/Marking';
        $files = array_merge(glob($base.'/*.php') ?: [], glob($base.'/Rules/*.php') ?: []);
        $files = array_values(array_filter($files, fn (string $file) => ! in_array(basename($file), self::NOT_ENGINE, true)));
        sort($files);

        return hash('sha256', implode("\n", array_map(fn (string $file) => substr($file, strlen($base)).':'.$this->code($file), $files)));
    }

    private function profile(SubjectProfile $profile): string
    {
        return hash('sha256', $this->code((string) (new \ReflectionClass($profile))->getFileName()));
    }

    /** The file's tokens without comments or whitespace. */
    private function code(string $file): string
    {
        $tokens = array_filter(PhpToken::tokenize((string) file_get_contents($file)), fn (PhpToken $token) => ! $token->is([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT]));

        return implode(' ', array_map(fn (PhpToken $token) => $token->text, $tokens));
    }
}
