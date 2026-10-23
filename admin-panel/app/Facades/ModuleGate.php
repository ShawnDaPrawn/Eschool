<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/** @method static bool allows(string $key)
 * @method static bool writable(string $key)
 */
class ModuleGate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\Platform\Modules\ModuleGate::class;
    }
}
