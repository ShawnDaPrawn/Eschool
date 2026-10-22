<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\IdempotencyKey;
use App\Models\User;
use App\Services\Platform\IdempotencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

/**
 * The Idempotency-Key header on API v2 writes, on test-only routes (no v2
 * write exists yet): a retry with the same key and payload gets the stored
 * answer and the work runs once; the same key with another payload is refused
 * (422); keys are per user and last 24 hours.
 */
class IdempotencyKeyTest extends TestCase
{
    use RefreshDatabase;

    /** How many times the test route's work actually ran. */
    private static int $runs = 0;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        self::$runs = 0;

        Route::middleware('api')->prefix('api/v2/_test')->group(function () {
            Route::middleware(['auth:sanctum', 'idempotent'])->group(function () {
                Route::post('orders', function (Request $request) {
                    self::$runs++;

                    return response()->json(['error' => false, 'message' => 'Created', 'data' => ['order' => self::$runs, 'item' => $request->input('item')], 'code' => 200], 201);
                })->name('test.orders');
                Route::get('orders', fn () => response()->json(['runs' => ++self::$runs]));
                Route::post('fails', function () {
                    self::$runs++;

                    throw new RuntimeException('Gateway timed out');
                });
                Route::post('refuses', function (Request $request) {
                    self::$runs++;
                    $request->validate(['amount' => 'required|integer']);
                });
            });
            Route::middleware(['auth:sanctum', 'idempotent:required'])->post('payments', function () {
                self::$runs++;

                return response()->json(['error' => false, 'data' => ['paid' => true]]);
            });
        });

        $this->parent = $this->user('Parent');
    }

    public function test_a_retry_gets_the_first_answer_and_the_work_runs_once(): void
    {
        $first = $this->write('orders', ['item' => 'book'], 'key-1')->assertCreated()->assertHeaderMissing('Idempotent-Replayed');
        $retry = $this->write('orders', ['item' => 'book'], 'key-1')->assertCreated()->assertHeader('Idempotent-Replayed', 'true');

        $this->assertSame(1, self::$runs);
        $this->assertSame($first->getContent(), $retry->getContent());
        $this->assertStringContainsString('application/json', (string) $retry->headers->get('Content-Type'));
    }

    public function test_it_stores_the_key_user_route_request_hash_and_answer_for_24_hours(): void
    {
        $this->write('orders', ['item' => 'book'], 'key-1');

        $record = IdempotencyKey::query()->sole();
        $this->assertSame($this->parent->id, $record->user_id);
        $this->assertSame('key-1', $record->key);
        $this->assertSame(hash('sha256', 'key-1'), $record->key_hash);
        $this->assertSame('POST', $record->method);
        $this->assertSame('test.orders', $record->route);
        $this->assertSame(64, strlen($record->request_hash));
        $this->assertSame(201, $record->response_status);
        $this->assertSame(['order' => 1, 'item' => 'book'], json_decode((string) $record->response_body, true)['data']);
        $this->assertEqualsWithDelta(now()->addHours(24)->timestamp, $record->expires_at->timestamp, 5);
    }

    public function test_the_same_key_with_a_different_payload_is_refused(): void
    {
        $this->write('orders', ['item' => 'book'], 'key-1')->assertCreated();

        $this->write('orders', ['item' => 'laptop'], 'key-1')
            ->assertStatus(422)
            ->assertExactJson(['error' => true, 'message' => trans('api_idempotency_key_reused'), 'data' => null, 'code' => 'idempotency_key_reused']);
        $this->assertSame(1, self::$runs);
    }

    public function test_field_order_does_not_make_a_different_request_but_another_path_does(): void
    {
        $this->write('orders', ['item' => 'book', 'qty' => 2], 'key-1')->assertCreated();
        $this->write('orders', ['qty' => 2, 'item' => 'book'], 'key-1')->assertHeader('Idempotent-Replayed', 'true');

        $this->write('refuses', ['item' => 'book', 'qty' => 2], 'key-1')->assertStatus(422)->assertJsonPath('code', 'idempotency_key_reused');
        $this->assertSame(1, self::$runs);
    }

    public function test_uploaded_files_count_as_part_of_the_payload(): void
    {
        $file = fn (string $content) => UploadedFile::fake()->createWithContent('proof.pdf', $content);

        $this->write('orders', ['item' => 'book', 'proof' => $file('receipt 1')], 'key-1', json: false)->assertCreated();
        $this->write('orders', ['item' => 'book', 'proof' => $file('receipt 1')], 'key-1', json: false)->assertHeader('Idempotent-Replayed', 'true');
        $this->write('orders', ['item' => 'book', 'proof' => $file('receipt 2')], 'key-1', json: false)->assertStatus(422);
        $this->assertSame(1, self::$runs);
    }

    public function test_keys_are_per_user(): void
    {
        $this->write('orders', ['item' => 'book'], 'key-1')->assertCreated();

        $this->parent = $this->user('Parent');
        $this->write('orders', ['item' => 'book'], 'key-1')->assertCreated()->assertHeaderMissing('Idempotent-Replayed');
        $this->assertSame(2, self::$runs);
    }

    public function test_a_key_can_be_used_again_after_24_hours(): void
    {
        $this->write('orders', ['item' => 'book'], 'key-1');

        $this->travel(25)->hours();
        $this->write('orders', ['item' => 'laptop'], 'key-1')->assertCreated()->assertJsonPath('data.item', 'laptop');
        $this->assertSame(2, self::$runs);
    }

    public function test_expired_keys_are_pruned(): void
    {
        $this->write('orders', ['item' => 'book'], 'key-1');
        $this->write('orders', ['item' => 'book'], 'key-2');
        IdempotencyKey::query()->where('key', 'key-1')->update(['expires_at' => now()->subMinute()]);

        $this->artisan('model:prune', ['--model' => [IdempotencyKey::class]])->assertSuccessful();

        $this->assertSame(['key-2'], IdempotencyKey::query()->pluck('key')->all());
    }

    public function test_a_retry_while_the_first_request_runs_is_told_to_wait(): void
    {
        // The first request has started (its row exists) but not answered yet.
        app(IdempotencyService::class)->begin($this->parent->id, 'key-1', Request::create('/api/v2/_test/orders', 'POST', ['item' => 'book']));

        $this->write('orders', ['item' => 'book'], 'key-1')->assertStatus(409)->assertJsonPath('code', 'idempotency_key_in_progress');
        $this->assertSame(0, self::$runs);

        // A first request that never answered has died; the key is free again.
        $this->travel(6)->minutes();
        $this->write('orders', ['item' => 'book'], 'key-1')->assertCreated();
    }

    public function test_a_failed_request_frees_the_key_and_a_refusal_is_kept(): void
    {
        $this->write('fails', ['item' => 'book'], 'key-1')->assertStatus(500);
        $this->write('fails', ['item' => 'book'], 'key-1')->assertStatus(500)->assertHeaderMissing('Idempotent-Replayed');
        $this->assertSame(2, self::$runs);

        // A validation refusal is the answer to that request; a retry gets it again.
        $this->write('refuses', ['amount' => 'lots'], 'key-2')->assertStatus(422);
        $this->write('refuses', ['amount' => 'lots'], 'key-2')->assertStatus(422)->assertHeader('Idempotent-Replayed', 'true');
        $this->assertSame(3, self::$runs);
    }

    public function test_without_a_key_writes_run_every_time_and_reads_are_never_touched(): void
    {
        $this->write('orders', ['item' => 'book'])->assertCreated();
        $this->write('orders', ['item' => 'book'])->assertCreated();

        $this->getJson('/api/v2/_test/orders', ['Idempotency-Key' => 'key-1'])->assertOk();
        $this->getJson('/api/v2/_test/orders', ['Idempotency-Key' => 'key-1'])->assertOk();

        $this->assertSame(4, self::$runs);
        $this->assertSame(0, IdempotencyKey::query()->count());
    }

    public function test_money_writes_need_a_key(): void
    {
        $this->write('payments', ['amount_cents' => 1000])->assertStatus(400)
            ->assertExactJson(['error' => true, 'message' => trans('api_idempotency_key_missing'), 'data' => null, 'code' => 'idempotency_key_missing']);
        $this->assertSame(0, self::$runs);

        $this->write('payments', ['amount_cents' => 1000], 'pay-1')->assertOk();
        $this->write('payments', ['amount_cents' => 1000], 'pay-1')->assertOk()->assertHeader('Idempotent-Replayed', 'true');
        $this->assertSame(1, self::$runs);
    }

    public function test_a_malformed_key_is_refused(): void
    {
        foreach ([str_repeat('k', 256), 'has space', 'naïve-key'] as $key) {
            $this->write('orders', ['item' => 'book'], $key)->assertStatus(400)->assertJsonPath('code', 'idempotency_key_invalid');
        }
        $this->write('orders', ['item' => 'book'], str_repeat('k', 255))->assertCreated();
        $this->write('orders', ['item' => 'book'], '0f8fad5b-d9cb-469f-a165-70867728950e')->assertCreated();
        $this->assertSame(2, self::$runs);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function write(string $path, array $data, ?string $key = null, bool $json = true): TestResponse
    {
        $this->app['auth']->forgetGuards();
        $headers = ['Authorization' => 'Bearer '.$this->parent->createToken('test')->plainTextToken, 'Accept' => 'application/json'];
        if ($key !== null) {
            $headers['Idempotency-Key'] = $key;
        }

        return $json
            ? $this->postJson("/api/v2/_test/$path", $data, $headers)
            : $this->call('POST', "/api/v2/_test/$path", array_filter($data, 'is_string'), [], array_filter($data, fn ($v) => $v instanceof UploadedFile), $this->transformHeadersToServerVars($headers));
    }

    private function user(string $role): User
    {
        $user = User::forceCreate([
            'first_name' => 'Pat', 'last_name' => 'Parent', 'email' => 'pat'.uniqid().'@example.com',
            'password' => Hash::make('secret123'), 'gender' => 'Female', 'mobile' => '0123456789',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
