<?php

declare(strict_types=1);

namespace App\Services\Live;

use Illuminate\Support\Str;

/** Private physical channels are segmented per authenticated credential lease. */
class LiveChannels
{
    public static function name(int $school, int $user, string $lease, ?int $group = null): string
    {
        abort_unless($school > 0 && $user > 0 && Str::isUuid($lease) && ($group === null || $group > 0), 422);

        return 'private-ne.school.'.$school.($group === null ? '' : '.group.'.$group).'.user.'.$user.'.session.'.$lease;
    }

    /** @return array{school:int,user:int,lease:string,group:?int} */
    public static function parse(string $channel): array
    {
        abort_unless(strlen($channel) <= 200 && preg_match('/^private-ne[.]school[.]([1-9][0-9]*)(?:[.]group[.]([1-9][0-9]*))?[.]user[.]([1-9][0-9]*)[.]session[.]([a-f0-9-]{36})$/D', $channel, $parts) === 1, 422);
        abort_unless(Str::isUuid($parts[4]), 422);
        foreach ([1, 3] as $index) {
            abort_unless(filter_var($parts[$index], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false, 422);
        }
        $group = ($parts[2] ?? '') === '' ? null : filter_var($parts[2], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        abort_if($group === false, 422);

        return ['school' => (int) $parts[1], 'user' => (int) $parts[3], 'lease' => $parts[4], 'group' => $group];
    }
}
