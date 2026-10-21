<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\ResponseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

/**
 * ResponseService builds the API envelope {error, message, data, code} and
 * returns it. It used to send the response and exit(), so nothing after it ran
 * and tests had to call the API on a separate server. That this test gets past
 * each call at all is part of what it checks.
 */
class ResponseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_response_is_returned_not_sent(): void
    {
        $response = ResponseService::successResponse('Data Fetched Successfully', ['id' => 1], ['extra' => 'x']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['error' => false, 'message' => 'Data Fetched Successfully', 'data' => ['id' => 1], 'code' => 200, 'extra' => 'x'], $response->getData(true));
        $this->assertSame(201, ResponseService::successResponse('Created', null, [], 201)->getData(true)['code']);
    }

    public function test_error_and_validation_responses_keep_the_envelope(): void
    {
        config(['app.debug' => false]);

        $error = ResponseService::errorResponse('no_permission_message', null, 403);
        $this->assertSame(200, $error->getStatusCode());
        $this->assertSame(['error' => true, 'message' => trans('no_permission_message'), 'data' => null, 'code' => 403, 'details' => ''], $error->getData(true));

        $validation = ResponseService::validationError('The name field is required.', ['name']);
        $this->assertSame(['error' => true, 'message' => 'The name field is required.', 'data' => ['name'], 'code' => config('constants.RESPONSE_CODE.VALIDATION_ERROR'), 'details' => ''], $validation->getData(true));

        $warning = ResponseService::warningResponse('Careful', ['a'], 7);
        $this->assertSame(['error' => false, 'warning' => true, 'code' => 7, 'message' => 'Careful', 'data' => ['a']], $warning->getData(true));
    }

    public function test_an_error_with_an_exception_is_logged_without_showing_it(): void
    {
        config(['app.debug' => false]);
        Log::spy();

        $response = ResponseService::errorResponse('error_occurred', null, 103, new RuntimeException('database password in here'));

        $this->assertSame(103, $response->getData(true)['code']);
        $this->assertStringNotContainsString('database password', $response->getContent());
        Log::shouldHaveReceived('error')->atLeast()->once();
    }

    /**
     * A helper that has to stop the request throws its response. When a catch
     * block hands that exception to errorResponse(), the helper's response comes
     * back unchanged and nothing is logged, as when the helper used to exit().
     */
    public function test_a_thrown_response_passes_through_error_response(): void
    {
        Log::spy();
        $thrown = ResponseService::errorResponse('Invalid Child ID Passed.', null, 105);

        try {
            throw new HttpResponseException($thrown);
        } catch (\Throwable $e) {
            $response = ResponseService::errorResponse('error_occurred', null, 103, $e);
        }

        $this->assertSame($thrown, $response);
        $this->assertSame(105, $response->getData(true)['code']);
        Log::shouldNotHaveReceived('error');
    }

    public function test_permission_guards_throw_the_error_response(): void
    {
        $this->seedLikeInstaller();
        $user = User::forceCreate([
            'first_name' => 'No', 'last_name' => 'Role', 'email' => 'norole@example.com', 'password' => Hash::make('secret123'),
            'gender' => 'Male', 'mobile' => '0000000000',
        ]);
        $this->actingAs($user);

        foreach ([
            fn () => ResponseService::noPermissionThenSendJson('holiday-list'),
            fn () => ResponseService::noAnyPermissionThenSendJson(['holiday-list', 'holiday-create']),
        ] as $guard) {
            try {
                $guard();
                $this->fail('The guard let a user without the permission through');
            } catch (HttpResponseException $e) {
                $this->assertTrue($e->getResponse()->getData(true)['error']);
            }
        }

        $this->actingAs($this->superAdmin());
        $this->assertTrue(ResponseService::noPermissionThenSendJson('holiday-list'));
        $this->assertTrue(ResponseService::noAnyPermissionThenSendJson(['holiday-list', 'nothing']));
    }
}
