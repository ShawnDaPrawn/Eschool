<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Http\Middleware\PreserveDocumentFeedback;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Session\FileSessionHandler;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionManager;
use Illuminate\Session\Store;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DocumentFeedbackTest extends TestCase
{
    public static function backgrounds(): array
    {
        return [['json', []], ['html', ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']], ['html', ['HTTP_SEC_FETCH_DEST' => 'empty']], ['javascript', ['HTTP_SEC_FETCH_DEST' => 'script']]];
    }

    #[DataProvider('backgrounds')]
    public function test_background_requests_preserve_feedback_until_exactly_one_document_reads_it(string $kind, array $headers): void
    {
        $files = new Filesystem;
        $directory = sys_get_temp_dir().'/eschool-document-feedback-'.bin2hex(random_bytes(8));
        $files->makeDirectory($directory, 0700, true);
        config(['session.driver' => 'file', 'session.cookie' => 'feedback_session', 'session.files' => $directory, 'session.block_store' => 'file', 'session.lottery' => [0, 100]]);
        $id = sha1($directory);
        $initial = new Store('feedback_session', new FileSessionHandler($files, $directory, 120));
        $initial->setId($id);
        $initial->start();
        $initial->flash('success', 'Synthetic save completed.');
        $initial->flash('_old_input', ['private' => 'Do not extend private form input']);
        $initial->save();
        $request = function (string $type, array $server = []) use ($id): Response {
            $request = Request::create('http://localhost/feedback', 'GET', [], ['feedback_session' => $id], [], $server);
            $request->setRouteResolver(fn () => new Route(['GET'], '/feedback', fn () => null));
            $this->app->instance('request', $request);
            $manager = new SessionManager($this->app);
            $start = new StartSession($manager, fn () => $this->app->make('cache'));

            return $start->handle($request, fn ($r) => (new PreserveDocumentFeedback)->handle($r, function ($r) use ($type) {
                return new Response((string) $r->session()->get('success', ''), 200, ['Content-Type' => match ($type) {
                    'json' => 'application/json','javascript' => 'application/javascript',default => 'text/html'
                }]);
            }));
        };
        try {
            $request($kind, $headers);
            $request($kind, $headers);
            $fresh = new Store('feedback_session', new FileSessionHandler($files, $directory, 120));
            $fresh->setId($id);
            $fresh->start();
            $this->assertFalse($fresh->has('_old_input'), 'Private input does not gain extra lifetime.');
            $this->assertSame('Synthetic save completed.', $request('html', ['HTTP_SEC_FETCH_DEST' => 'document'])->getContent());
            $this->assertSame('', $request('html', ['HTTP_SEC_FETCH_DEST' => 'document'])->getContent(), 'Feedback is consumed by the first document, not retained indefinitely.');
        } finally {
            $files->deleteDirectory($directory);
        }
    }

    public function test_stateless_api_response_does_not_start_a_session(): void
    {
        $request = Request::create('http://localhost/api/feedback');
        $response = (new PreserveDocumentFeedback)->handle($request, fn () => response()->json(['ok' => true]));
        $this->assertFalse($request->hasSession());
        $this->assertSame(200, $response->getStatusCode());
    }
}
