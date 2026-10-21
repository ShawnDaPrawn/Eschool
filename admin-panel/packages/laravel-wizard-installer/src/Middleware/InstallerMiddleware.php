<?php

declare(strict_types=1);

namespace dacoto\LaravelWizardInstaller\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class InstallerMiddleware
{
    private const STARTED_EMPTY = 'installer.started_on_empty_database';

    /**
     * Handle an incoming request.
     *
     * @return RedirectResponse|mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $uri = $request->route() ? $request->route()->uri() : '';
        if (explode('/', $uri)[0] !== 'install' || $this->alreadyInstalled()) {
            abort(404);
        }

        // Without storage/installed, a database that already has users still
        // counts as installed: otherwise a new server or a cleared storage folder
        // would let anyone re-run the installer on a live school and point it at
        // their own database. The wizard creates users before its last steps, so
        // a browser that started on an empty database may carry on. Finishing
        // only writes storage/installed, so it is always allowed.
        if ($uri !== 'install/finish' && ! $request->session()->get(self::STARTED_EMPTY)) {
            if (self::databaseHasUsers()) {
                abort(404);
            }
            $request->session()->put(self::STARTED_EMPTY, true);
        }

        return $next($request);
    }

    /**
     * If application is already installed.
     */
    public function alreadyInstalled(): bool
    {
        return file_exists(storage_path('installed'));
    }

    public static function databaseHasUsers(): bool
    {
        try {
            return DB::table('users')->exists();
        } catch (Throwable) {
            return false;
        }
    }
}
