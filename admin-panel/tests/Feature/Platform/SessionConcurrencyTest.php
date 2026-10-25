<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Session\FileSessionHandler;
use Illuminate\Session\Store;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/** Exercise the real session middleware in independent PHP processes. */
class SessionConcurrencyTest extends TestCase
{
    public static function lockStores(): array
    {
        return [['file'], ['database']];
    }

    #[DataProvider('lockStores')]
    public function test_late_ajax_reader_cannot_restore_the_previous_selected_year(string $lockStore): void
    {
        $files = new Filesystem;
        $directory = sys_get_temp_dir().'/eschool-session-race-'.bin2hex(random_bytes(8));
        $files->makeDirectory($directory, 0700, true);
        $worker = $directory.'/worker.php';
        $files->put($worker, <<<'CODE'
<?php
require $argv[2].'/vendor/autoload.php';
$app = require $argv[2].'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'file', 'session.cookie' => 'laravel_session', 'session.files' => $argv[3], 'session.block_store' => $argv[4], 'session.lottery' => [0, 100]]);
$request = Illuminate\Http\Request::create('http://localhost/session-race', 'GET', [], ['laravel_session' => sha1($argv[3])]);
$request->setRouteResolver(fn () => new Illuminate\Routing\Route(['GET'], '/session-race', fn () => null));
$app->instance('request', $request);
$manager = new Illuminate\Session\SessionManager($app);
$middleware = new Illuminate\Session\Middleware\StartSession($manager, fn () => $app->make('cache'));
$middleware->handle($request, function ($request) use ($argv) {
    if ($argv[1] === 'reader') {
        if ((int) $request->session()->get('session_year') !== 2) {
            throw new RuntimeException('Reader did not load the original session.');
        }
        touch($argv[3].'/reader-started');
        usleep(700000);
    } else {
        $request->session()->put('session_year', 1);
    }
    return new Illuminate\Http\Response('ok');
});
CODE);
        $session = new Store('laravel_session', new FileSessionHandler($files, $directory, 120));
        $session->setId(sha1($directory));
        $session->start();
        $session->put('session_year', 2);
        $session->save();
        $reader = new Process([PHP_BINARY, $worker, 'reader', base_path(), $directory, $lockStore]);
        $writer = new Process([PHP_BINARY, $worker, 'writer', base_path(), $directory, $lockStore]);
        try {
            $reader->start();
            $deadline = microtime(true) + 15;
            while (! is_file($directory.'/reader-started') && $reader->isRunning() && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertFileExists($directory.'/reader-started', 'The separate process must first read the old session.');
            $writer->mustRun();
            $reader->wait();
            $this->assertTrue($reader->isSuccessful(), $reader->getErrorOutput());
            $latest = new Store('laravel_session', new FileSessionHandler($files, $directory, 120));
            $latest->setId(sha1($directory));
            $latest->start();
            $this->assertSame(1, $latest->get('session_year'), 'The latest selected year must survive a concurrent stale AJAX reader.');
        } finally {
            $reader->stop();
            $writer->stop();
            $files->deleteDirectory($directory);
        }
    }
}
